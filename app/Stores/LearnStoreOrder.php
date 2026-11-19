<?php

namespace App\Stores;

use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Learns a store's order from the order in which products were bought
 * (priced, see ShoppingDayItem::$purchased_at) on past shopping days there.
 *
 * Each product has a score from 0 (start of the route) to 1 (end). For every
 * trip, the bought products take the scores they already had, reassigned in
 * the order they were bought, and each score moves towards that observation:
 *
 *     score = (1 - WEIGHT) * previous + WEIGHT * observed
 *
 * Reassigning existing scores (instead of spreading the trip from 0 to 1)
 * keeps partial trips local: buying 5 products doesn't push them to the
 * ends of the store. The weight makes one odd trip (going back for a
 * forgotten product) nudge the order, while a consistent change wins after
 * a few trips: 2 for neighbours, 3-4 for products far apart. Dragging
 * products in the sort page is the quick fix for big changes.
 */
class LearnStoreOrder
{
    public const WEIGHT = 0.4;

    /**
     * Learns from every trip in $store not learned yet (except $exclude, the
     * day being started) and rewrites the store positions by score.
     */
    public function __invoke(Store $store, ?ShoppingDay $exclude = null): void
    {
        $days = $store->shoppingDays()
            ->whereNull('learned_at')
            ->when($exclude, fn ($query) => $query->whereKeyNot($exclude->getKey()))
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        if ($days->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($store, $days) {
            $scores = $this->currentScores($store);

            foreach ($days->where('learn_order', true) as $day) {
                $scores = $this->learnTrip($scores, $this->route($day));
            }

            $this->save($store, $scores);

            ShoppingDay::query()
                ->whereKey($days->modelKeys())
                ->update(['learned_at' => now()]);
        });
    }

    /**
     * Scores by product id. Rows without score (e.g. from the initial store
     * migration) get scores spread evenly following their positions.
     *
     * @return array<string, float>
     */
    private function currentScores(Store $store): array
    {
        $rows = DB::table('store_product')
            ->where('store_id', $store->id)
            ->orderBy('position')
            ->get(['product_id', 'score']);

        if ($rows->contains(fn ($row) => $row->score === null)) {
            $last = max($rows->count() - 1, 1);

            return $rows->values()->mapWithKeys(
                fn ($row, int $i) => [$row->product_id => $i / $last]
            )->all();
        }

        return $rows->mapWithKeys(
            fn ($row) => [$row->product_id => (float) $row->score]
        )->all();
    }

    /**
     * Product ids bought on $day, in the order they were bought.
     *
     * @return array<int, string>
     */
    private function route(ShoppingDay $day): array
    {
        return $day->items()->reorder()
            ->whereNotNull('purchased_at')
            ->orderBy('purchased_at')
            ->pluck('product_id')
            ->all();
    }

    /**
     * @param array<string, float> $scores
     * @param array<int, string> $route
     * @return array<string, float>
     */
    private function learnTrip(array $scores, array $route): array
    {
        if (count($route) < 2) {
            return $scores;
        }

        $previous = $this->withNewcomers($scores, $route);

        // The scores the bought products already had, handed out again in
        // the order they were bought
        $slots = $this->spread(
            collect($route)->map(fn ($id) => $previous[$id])->sort()->values()->all(),
            count($previous)
        );

        foreach ($route as $i => $productId) {
            $scores[$productId] = (1 - self::WEIGHT) * $previous[$productId]
                + self::WEIGHT * $slots[$i];
        }

        return $scores;
    }

    /**
     * Keeps the observed slots at least half the average gap between the
     * store products apart, centred where they were. Without it two swapped
     * products would get each other's scores and only meet in the middle:
     * the distance shrinks every trip but they never cross.
     *
     * @param array<int, float> $slots sorted ascending
     * @return array<int, float>
     */
    private function spread(array $slots, int $productsInStore): array
    {
        $minGap = 0.5 / max($productsInStore - 1, 1);
        $spread = $slots;

        for ($i = 1; $i < count($spread); $i++) {
            $spread[$i] = max($spread[$i], $spread[$i - 1] + $minGap);
        }

        $shift = (array_sum($slots) - array_sum($spread)) / count($slots);

        return array_map(fn (float $slot) => $slot + $shift, $spread);
    }

    /**
     * Gives bought products that have no score yet one between the scores
     * of their closest scored neighbours in the route.
     *
     * @param array<string, float> $scores
     * @param array<int, string> $route
     * @return array<string, float>
     */
    private function withNewcomers(array $scores, array $route): array
    {
        $route = array_values($route);

        foreach ($route as $i => $productId) {
            if (isset($scores[$productId])) {
                continue;
            }

            $before = $this->closestScore($scores, array_reverse(array_slice($route, 0, $i)));
            $after = $this->closestScore($scores, array_slice($route, $i + 1));

            $scores[$productId] = match (true) {
                $before !== null && $after !== null => ($before + $after) / 2,
                $before !== null => min($before + 0.05, 1.0),
                $after !== null => max($after - 0.05, 0.0),
                default => $i / max(count($route) - 1, 1),
            };
        }

        return $scores;
    }

    /**
     * @param array<string, float> $scores
     * @param array<int, string> $productIds
     */
    private function closestScore(array $scores, array $productIds): ?float
    {
        foreach ($productIds as $productId) {
            if (isset($scores[$productId])) {
                return $scores[$productId];
            }
        }

        return null;
    }

    /**
     * Rewrites the store rows: positions follow the scores.
     *
     * @param array<string, float> $scores
     */
    private function save(Store $store, array $scores): void
    {
        asort($scores);

        $store->products()->sync(
            collect($scores)->keys()->values()->mapWithKeys(
                fn (string $id, int $position) => [$id => [
                    'position' => $position,
                    'score' => $scores[$id],
                ]]
            )
        );
    }
}
