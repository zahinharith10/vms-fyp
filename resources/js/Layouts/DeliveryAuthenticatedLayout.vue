<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const showingNavigationDropdown = ref(false);
const delivery = usePage().props.auth.user;
</script>

<template>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-200">
        <!-- Sidebar -->
        <aside class="w-64 shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shadow-sm min-h-screen hidden md:block transition-colors duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                <Link :href="route('delivery.dashboard')" class="flex items-center">
                    <ApplicationLogo class="block h-12 w-auto fill-current text-indigo-600" />
                    <span class="ml-2 font-bold text-base text-gray-800 dark:text-white">Delivery Portal</span>
                </Link>
                <ThemeToggle />
            </div>

            <nav class="mt-6 px-4 space-y-2">
                <div class="px-4 mb-4">
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Welcome,</p>
                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate">{{ delivery?.name || 'Rider' }}</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold truncate">{{ delivery?.company || '' }}</p>
                </div>

                <Link :href="route('delivery.dashboard')" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-lg transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('delivery.dashboard') }">
                    <span class="mr-3 text-xl">🚚</span> Dashboard
                </Link>

                <Link :href="route('delivery.dashboard', { tab: 'history' })" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-lg transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('delivery.dashboard') && $page.url.includes('tab=history') }">
                    <span class="mr-3 text-xl">📋</span> History
                </Link>

                <Link :href="route('delivery.profile')" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-lg transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('delivery.profile') }">
                    <span class="mr-3 text-xl">👤</span> My Profile
                </Link>

                <div class="border-t border-gray-200 dark:border-gray-800 my-4"></div>

                <Link :href="route('manual.index')" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-lg transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('manual.index') }">
                    <span class="mr-3 text-xl">📖</span> User Manual
                </Link>

                <div class="border-t border-gray-200 dark:border-gray-800 my-4"></div>

                <Link :href="route('delivery.logout')" method="post" as="button" class="w-full text-left flex items-center px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-md">
                    <span class="mr-2">🚪</span> Log Out
                </Link>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Mobile Header -->
            <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm md:hidden flex justify-between items-center p-4">
                <Link :href="route('delivery.dashboard')" class="flex items-center gap-2">
                    <div class="flex-shrink-0" style="width:32px;height:32px;">
                        <img src="/Logo.png" alt="Sri Ayu Apartment" style="width:32px;height:32px;object-fit:contain;" />
                    </div>
                    <span class="font-bold text-gray-800 dark:text-white">Delivery Portal</span>
                </Link>
                <div class="flex items-center gap-3">
                    <ThemeToggle />
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="text-gray-500 dark:text-gray-400 focus:outline-none">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Mobile Menu Dropdown -->
            <div v-if="showingNavigationDropdown" class="md:hidden bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 p-4 space-y-2">
                  <Link :href="route('delivery.dashboard', { tab: 'history' })" class="block py-2 text-gray-700 dark:text-gray-300 font-semibold">History</Link>
                  <Link :href="route('delivery.profile')" class="block py-2 text-gray-700 dark:text-gray-300 font-semibold">My Profile</Link>
                  <Link :href="route('manual.index')" class="block py-2 text-gray-700 dark:text-gray-300 font-semibold">User Manual</Link>
                  <Link :href="route('delivery.logout')" method="post" as="button" class="block py-2 text-red-600 dark:text-red-400 w-full text-left font-semibold">Log Out</Link>
            </div>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 flex justify-between items-center">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-50 dark:bg-green-950/30 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm">
                    <p class="text-sm font-bold text-green-800 dark:text-green-400">{{ $page.props.flash.success }}</p>
                </div>

                <div v-if="$page.props.flash.error" class="mb-6 bg-red-50 dark:bg-red-950/30 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
                    <p class="text-sm font-bold text-red-800 dark:text-red-400">{{ $page.props.flash.error }}</p>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
