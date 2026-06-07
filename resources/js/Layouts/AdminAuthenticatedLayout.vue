<script setup>
import { ref, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link } from '@inertiajs/vue3';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const showingNavigationDropdown = ref(false);
const showingDeliveryDropdown = ref(false);
const showingVisitorDropdown = ref(false);

onMounted(() => {
    if (route().current('admin.delivery.*')) {
        showingDeliveryDropdown.value = true;
    }
    if (route().current('admin.visitors.*') || route().current('admin.visit-logs.*')) {
        showingVisitorDropdown.value = true;
    }
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-200">
        <!-- Sidebar -->
        <aside class="w-64 shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shadow-sm min-h-screen hidden md:block transition-colors duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                <Link :href="route('admin.dashboard')" class="flex items-center">
                    <ApplicationLogo class="block h-20 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    <span class="ml-2 font-bold text-xl text-gray-800 dark:text-white">Admin Panel</span>
                </Link>
            </div>
            
            <nav class="mt-6 px-4 space-y-2">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Management</p>
                
                <Link :href="route('admin.dashboard')" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.dashboard') }">
                    Dashboard
                </Link>

                <Link :href="route('admin.residents.index')" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.residents.*') }">
                     Residents
                </Link>
                <!-- Visitor Dropdown -->
                <div>
                    <button @click="showingVisitorDropdown = !showingVisitorDropdown" class="flex items-center w-full px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md justify-between transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.visitors.*') || route().current('admin.visit-logs.*') }">
                        <span class="flex items-center">
                            Visitor Management
                        </span>
                        <svg class="w-4 h-4 ml-2 transition-transform duration-200" :class="{ 'transform rotate-180': showingVisitorDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="showingVisitorDropdown" class="mt-2 pl-4 space-y-2">
                        <Link :href="route('admin.visitors.index')" class="flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/20': route().current('admin.visitors.index') || route().current('admin.visitors.edit') || route().current('admin.visitors.create') }">
                            Manage Profiles
                        </Link>
                        <Link :href="route('admin.visit-logs.index')" class="flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/20': route().current('admin.visit-logs.*') }">
                            Visit History
                        </Link>
                    </div>
                </div>
                
                <!-- Delivery Service Dropdown -->
                <div>
                    <button @click="showingDeliveryDropdown = !showingDeliveryDropdown" class="flex items-center w-full px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md justify-between transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.delivery.*') }">
                        <span class="flex items-center">
                            Delivery Service
                        </span>
                        <svg class="w-4 h-4 ml-2 transition-transform duration-200" :class="{ 'transform rotate-180': showingDeliveryDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="showingDeliveryDropdown" class="mt-2 pl-4 space-y-2">
                        <Link :href="route('admin.delivery.personnel.index')" class="flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/20': route().current('admin.delivery.personnel.*') }">
                            Manage Personnel
                        </Link>
                        <Link :href="route('admin.delivery.logs.index')" class="flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/20': route().current('admin.delivery.logs.*') }">
                            Delivery History
                        </Link>
                    </div>
                </div>
                
                <Link :href="route('admin.guards.index')" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.guards.*') }">
                     Guards
                </Link>

                <Link :href="route('admin.units.index')" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.units.*') }">
                     House Units
                </Link>

                <div class="border-t border-gray-200 dark:border-gray-800 my-4"></div>

                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">System Tools</p>

                <Link :href="route('admin.reports.index')" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.reports.*') }">
                     Reports
                </Link>

                <Link :href="route('admin.manual.index')" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.manual.index') }">
                     User Manual
                </Link>

                <div class="border-t border-gray-200 dark:border-gray-800 my-4"></div>

                <Link :href="route('admin.profile')" class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-all duration-200" :class="{ 'bg-gray-200 dark:bg-gray-800 font-bold text-gray-900 dark:text-white': route().current('admin.profile') }">
                     Profile Settings
                </Link>

                <Link :href="route('admin.logout')" method="post" as="button" class="w-full text-left flex items-center px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-md transition-all duration-200">
                    Log Out
                </Link>
            </nav>
        </aside>

        <!-- Main Content (with Mobile Header) -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Header -->
            <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm md:hidden flex justify-between items-center p-4 transition-colors duration-200">
                <div class="font-bold text-lg text-gray-800 dark:text-white">Admin Panel</div>
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
                 <Link :href="route('admin.dashboard')" class="block py-2 text-gray-700 dark:text-gray-300 font-semibold">Dashboard</Link>
                 <Link :href="route('admin.visitors.index')" class="block py-2 text-gray-700 dark:text-gray-300 font-semibold">Visitors</Link>
                 <Link :href="route('admin.reports.index')" class="block py-2 text-gray-700 dark:text-gray-300 font-semibold">Reports</Link>
                 <Link :href="route('admin.manual.index')" class="block py-2 text-gray-700 dark:text-gray-300 font-semibold" :class="{ 'text-indigo-600 dark:text-indigo-400': route().current('admin.manual.index') }">User Manual</Link>
                 <Link :href="route('admin.profile')" class="block py-2 text-gray-700 dark:text-gray-300 font-semibold">Profile Settings</Link>
                 <Link :href="route('admin.logout')" method="post" as="button" class="block py-2 text-red-600 dark:text-red-400 w-full text-left font-semibold">Log Out</Link>
             </div>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm transition-colors duration-200" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="shrink-0">
                        <slot name="header" />
                    </div>
                    <div class="flex flex-wrap items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                        <slot name="actions" />
                        <div class="flex items-center gap-3 shrink-0">
                            <ThemeToggle />
                            <NotificationDropdown />
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 min-w-0">
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
