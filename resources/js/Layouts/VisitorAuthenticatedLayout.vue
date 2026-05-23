<script setup>
import { ref, onErrorCaptured } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const showingNavigationDropdown = ref(false);
const page = usePage();
// Defensive access to visitor to prevent crash if null
const visitor = page.props.auth ? page.props.auth.user : null;

const renderError = ref(null);

onErrorCaptured((err) => {
    console.error('Layout Error Captured:', err);
    renderError.value = err.toString();
    return false; // Prevent propagation
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-200">
        <!-- Error Alert -->
        <div v-if="renderError" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-xl max-w-lg w-full">
                <h3 class="text-red-600 font-bold text-lg mb-2">Application Error</h3>
                <pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded text-xs overflow-auto max-h-60 mb-4">{{ renderError }}</pre>
                <button @click="renderError = null" class="bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-300">Dismiss</button>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shadow-sm min-h-screen hidden md:block transition-colors duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                <Link :href="route('visitor.dashboard')" class="flex items-center">
                    <ApplicationLogo class="block h-12 w-auto fill-current text-indigo-600" />
                    <span class="ml-2 font-bold text-base text-gray-800 dark:text-white">Visitor Portal</span>
                </Link>
                <ThemeToggle />
            </div>
            
            <nav class="mt-6 px-4 space-y-2">
                <div class="px-4 mb-4">
                     <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Welcome,</p>
                     <p class="text-sm font-bold text-gray-800 truncate">{{ visitor?.name || 'Guest' }}</p>
                </div>

                <Link :href="route('visitor.dashboard')" class="flex items-center px-4 py-3 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 rounded-lg transition-all duration-200" :class="{ 'bg-indigo-50 text-indigo-700 font-bold shadow-sm': route().current('visitor.dashboard') }">
                    <span class="mr-3 text-xl">📊</span> Dashboard
                </Link>

                <Link :href="route('visitor.visits.history')" class="flex items-center px-4 py-3 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 rounded-lg transition-all duration-200" :class="{ 'bg-indigo-50 text-indigo-700 font-bold shadow-sm': route().current('visitor.visits.history') }">
                    <span class="mr-3 text-xl">🕒</span> Visit History
                </Link>

                <Link :href="route('visitor.profile')" class="flex items-center px-4 py-3 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 rounded-lg transition-all duration-200" :class="{ 'bg-indigo-50 text-indigo-700 font-bold shadow-sm': route().current('visitor.profile') }">
                    <span class="mr-3 text-xl">👤</span> My Profile
                </Link>

                <div class="border-t border-gray-200 my-4"></div>

                <Link :href="route('visitor.logout')" method="post" as="button" class="w-full text-left flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-md">
                    <span class="mr-2">🚪</span> Log Out
                </Link>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Mobile Header -->
            <header class="bg-white shadow md:hidden flex justify-between items-center p-4">
                <div class="font-bold text-lg">Visitor Portal</div>
                <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="text-gray-500 focus:outline-none">
                     <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </header>
            
            <!-- Mobile Menu Dropdown -->
             <div v-if="showingNavigationDropdown" class="md:hidden bg-white border-b border-gray-200 p-4">
                 <Link :href="route('visitor.dashboard')" class="block py-2 text-gray-700">Dashboard</Link>
                 <Link :href="route('visitor.visits.history')" class="block py-2 text-gray-700">Visit History</Link>
                 <Link :href="route('visitor.logout')" method="post" as="button" class="block py-2 text-red-600 w-full text-left">Log Out</Link>
             </div>

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
