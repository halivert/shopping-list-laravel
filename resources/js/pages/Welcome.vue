<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3"

const page = usePage()
</script>

<template>
    <Head title="Bienvenido">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a] lg:justify-center lg:p-8"
    >
        <header
            class="not-has-[nav]:hidden mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="page.props.auth.user"
                    :href="route('home')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Inicio
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Iniciar sesión
                    </Link>
                </template>
            </nav>
        </header>
        <div
            class="duration-750 starting:opacity-0 flex w-full items-center justify-center opacity-100 transition-opacity lg:grow"
        ></div>
        <div class="h-14.5 hidden lg:block"></div>
    </div>
</template>
