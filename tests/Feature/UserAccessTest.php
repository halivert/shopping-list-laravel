<?php

use App\Models\Access;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('No users can viewAny', function () {
    $user = User::factory()->create();

    Log::shouldReceive('info')
        ->with('User trying to access everyone', [
            'user' => $user->id,
            'userName' => $user->name
        ])
        ->once();

    $viewAnyResult = $user->can('viewAny', User::class);
    expect($viewAnyResult)->toBe(false);
});

test('No users can create other users', function () {
    $user = User::factory()->create();

    Log::shouldReceive('info')
        ->with('User trying to create someone', [
            'user' => $user->id,
            'userName' => $user->name
        ])
        ->once();

    $createResult = $user->can('create', User::class);
    expect($createResult)->toBe(false);
});

test('User can access it\'s own profile', function () {
    $user = User::factory()->create();

    $viewResult = $user->can('view', $user);
    expect($viewResult)->toBe(true);

    $updateResult = $user->can('update', $user);
    expect($updateResult)->toBe(true);

    $deleteResult = $user->can('delete', $user);
    expect($deleteResult)->toBe(true);
});

test('User can access other user profile if access exists', function () {
    [$userA, $userB] = User::factory(2)->create();

    // User A can access user B data
    $access = new Access;
    $access->user_id = $userA->id;
    $access->user_email = $userA->email;
    $access->accessible()->associate($userB);
    $access->save();

    $viewResult = $userA->can('view', $userB);
    expect($viewResult)->toBe(true);

    $updateResult = $userA->can('update', $userB);
    expect($updateResult)->toBe(false);

    $deleteResult = $userA->can('delete', $userB);
    expect($deleteResult)->toBe(false);
});

test('User cannot access other user profile if no access exists', function () {
    [$userA, $userB] = User::factory(2)->create();

    // User A can access user B data
    // but user B can't access user A data
    $access = new Access;
    $access->user_id = $userA->id;
    $access->user_email = $userA->email;
    $access->accessible()->associate($userB);
    $access->save();

    $viewResult = $userB->can('view', $userA);
    expect($viewResult)->toBe(false);

    $updateResult = $userB->can('update', $userA);
    expect($updateResult)->toBe(false);

    $deleteResult = $userB->can('delete', $userA);
    expect($deleteResult)->toBe(false);
});

test('User cannot access other user profile by default', function () {
    [$userA, $userB] = User::factory(2)->create();

    $viewResult = $userB->can('view', $userA);
    expect($viewResult)->toBe(false);

    $updateResult = $userB->can('update', $userA);
    expect($updateResult)->toBe(false);

    $deleteResult = $userB->can('delete', $userA);
    expect($deleteResult)->toBe(false);

    $viewResult = $userA->can('view', $userB);
    expect($viewResult)->toBe(false);

    $updateResult = $userA->can('update', $userB);
    expect($updateResult)->toBe(false);

    $deleteResult = $userA->can('delete', $userB);
    expect($deleteResult)->toBe(false);
});
