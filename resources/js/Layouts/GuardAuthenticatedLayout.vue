<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const showingNavigationDropdown = ref(false);
const guard = usePage().props.auth.user;
</script>

<template>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-200">
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shadow-sm min-h-screen hidden md:block relative flex flex-col transition-colors duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                <Link :href="route('guard.dashboard')" class="flex items-center">
                    <ApplicationLogo class="block h-12 w-auto fill-current text-indigo-600" />
                    <span class="ml-2 font-bold text-base text-gray-800 dark:text-white tracking-tight">Guard Portal</span>
                </Link>
                <ThemeToggle />
            </div>
            
            <nav class="mt-6 px-4 space-y-1 flex-1">
                <div class="px-4 mb-4">
                     <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Active Duty</p>
                     <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate">{{ guard.name }}</p>
                </div>

                <Link :href="route('guard.dashboard')" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-xl transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('guard.dashboard') }">
                    <span class="mr-3 text-xl">📊</span> Dashboard
                </Link>

                <Link :href="route('guard.scan')" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-xl transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('guard.scan') }">
                    <span class="mr-3 text-xl">📷</span> QR Scanner
                </Link>

                <Link :href="route('guard.register')" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-xl transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('guard.register') }">
                    <span class="mr-3 text-xl">📝</span> Register New
                </Link>

                <Link :href="route('guard.logs.active')" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-xl transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('guard.logs.active') }">
                    <span class="mr-3 text-xl">📈</span> Active Visitors
                </Link>

                <Link :href="route('guard.profile')" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 hover:text-indigo-700 dark:hover:text-indigo-400 rounded-xl transition-all duration-200" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 font-bold shadow-sm': route().current('guard.profile') }">
                    <span class="mr-3 text-xl">👤</span> My Profile
                </Link>

                <div class="border-t border-gray-100 dark:border-gray-800 my-6"></div>

                <Link :href="route('guard.logout')" method="post" as="button" class="w-full text-left flex items-center px-4 py-3 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-xl transition-all duration-200">
                    <span class="mr-3 text-xl">🚪</span> Log Out
                </Link>
            </nav>
        </aside>

        <!-- Main Content (with Mobile Header) -->
        <div class="flex-1 flex flex-col">
            <!-- Mobile Header -->
            <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm md:hidden flex justify-between items-center p-4 sticky top-0 z-50">
                <div class="flex items-center">
                    <ApplicationLogo class="h-8 w-auto text-indigo-600" />
                    <span class="ml-2 font-black text-gray-800 dark:text-white text-sm tracking-tighter">GUARD</span>
                </div>
                <div class="flex items-center gap-3">
                    <ThemeToggle />
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="text-gray-500 dark:text-gray-400 p-2 rounded-lg hover:bg-gray-150 transition">
                         <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </header>
            
            <!-- Mobile Menu Dropdown -->
             <div v-if="showingNavigationDropdown" class="md:hidden bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 p-4 space-y-2">
                 <Link :href="route('guard.dashboard')" class="block px-4 py-2 text-gray-700 dark:text-gray-300 font-bold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400': route().current('guard.dashboard') }">Dashboard</Link>
                 <Link :href="route('guard.scan')" class="block px-4 py-2 text-gray-700 dark:text-gray-300 font-bold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400': route().current('guard.scan') }">QR Scanner</Link>
                 <Link :href="route('guard.register')" class="block px-4 py-2 text-gray-700 dark:text-gray-300 font-bold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400': route().current('guard.register') }">Register New</Link>
                 <Link :href="route('guard.logs.active')" class="block px-4 py-2 text-gray-700 dark:text-gray-300 font-bold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400': route().current('guard.logs.active') }">Active Visitors</Link>
                 <Link :href="route('guard.profile')" class="block px-4 py-2 text-gray-700 dark:text-gray-300 font-bold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition" :class="{ 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400': route().current('guard.profile') }">My Profile</Link>
                 <Link :href="route('guard.logout')" method="post" as="button" class="block w-full text-left px-4 py-2 text-red-600 dark:text-red-400 font-bold rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition">Log Out</Link>
             </div>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 lg:p-10">
                <div class="max-w-7xl mx-auto">
                    <!-- Flash Messages -->
                    <div v-if="$page.props.flash.success" class="mb-8 bg-green-50 dark:bg-green-950/30 border-l-4 border-green-500 p-4 rounded-r-2xl shadow-sm animate-fade-in-down">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-green-500 text-xl font-bold">✅</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-black text-green-800 dark:text-green-400 uppercase tracking-tight">Success</p>
                                <p class="text-xs font-bold text-green-700/80 mt-0.5">{{ $page.props.flash.success }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="$page.props.flash.error" class="mb-8 bg-red-50 dark:bg-red-950/30 border-l-4 border-red-500 p-4 rounded-r-2xl shadow-sm animate-fade-in-down">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-red-500 text-xl font-bold">🚨</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-black text-red-800 dark:text-red-400 uppercase tracking-tight">Attention Required</p>
                                <p class="text-xs font-bold text-red-700/80 mt-0.5">{{ $page.props.flash.error }}</p>
                            </div>
                        </div>
                    </div>

                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
