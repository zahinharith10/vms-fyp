<script setup>
import { ref, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link } from '@inertiajs/vue3';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';

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
    <div class="flex min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md min-h-screen hidden md:block">
            <div class="p-6 border-b border-gray-200">
                <Link :href="route('admin.dashboard')" class="flex items-center">
                    <ApplicationLogo class="block h-20 w-auto fill-current text-gray-800" />
                    <span class="ml-2 font-bold text-xl text-gray-800">Admin Panel</span>
                </Link>
            </div>
            
            <nav class="mt-6 px-4 space-y-2">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Modules</p>
                
                <Link :href="route('admin.dashboard')" class="flex items-center px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200" :class="{ 'bg-gray-200 font-bold': route().current('admin.dashboard') }">
                    Dashboard
                </Link>

                <Link :href="route('admin.residents.index')" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-md" :class="{ 'bg-gray-200 font-bold': route().current('admin.residents.*') }">
                     Residents
                </Link>
                <!-- Visitor Dropdown -->
                <div>
                    <button @click="showingVisitorDropdown = !showingVisitorDropdown" class="flex items-center w-full px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-md justify-between" :class="{ 'bg-gray-200 font-bold': route().current('admin.visitors.*') || route().current('admin.visit-logs.*') }">
                        <span class="flex items-center">
                            Visitor Management
                        </span>
                        <svg class="w-4 h-4 ml-2 transition-transform duration-200" :class="{ 'transform rotate-180': showingVisitorDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="showingVisitorDropdown" class="mt-2 pl-4 space-y-2">
                        <Link :href="route('admin.visitors.index')" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-md" :class="{ 'text-indigo-600 font-bold': route().current('admin.visitors.index') || route().current('admin.visitors.edit') || route().current('admin.visitors.create') }">
                            Manage Profiles
                        </Link>
                        <Link :href="route('admin.visit-logs.index')" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-md" :class="{ 'text-indigo-600 font-bold': route().current('admin.visit-logs.*') }">
                            Visit Monitoring
                        </Link>
                    </div>
                </div>
                
                <!-- Delivery Service Dropdown -->
                <div>
                    <button @click="showingDeliveryDropdown = !showingDeliveryDropdown" class="flex items-center w-full px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-md justify-between" :class="{ 'bg-gray-200 font-bold': route().current('admin.delivery.*') }">
                        <span class="flex items-center">
                            Delivery Service
                        </span>
                        <svg class="w-4 h-4 ml-2 transition-transform duration-200" :class="{ 'transform rotate-180': showingDeliveryDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div v-show="showingDeliveryDropdown" class="mt-2 pl-4 space-y-2">
                        <Link :href="route('admin.delivery.personnel.index')" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-md" :class="{ 'text-indigo-600 font-bold': route().current('admin.delivery.personnel.*') }">
                            Manage Personnel
                        </Link>
                        <Link :href="route('admin.delivery.logs.index')" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-md" :class="{ 'text-indigo-600 font-bold': route().current('admin.delivery.logs.*') }">
                            View Logs
                        </Link>
                    </div>
                </div>
                
                <Link :href="route('admin.guards.index')" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-md" :class="{ 'bg-gray-200 font-bold': route().current('admin.guards.*') }">
                     Guards
                </Link>

                <Link :href="route('admin.units.index')" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-md" :class="{ 'bg-gray-200 font-bold': route().current('admin.units.*') }">
                     House Units
                </Link>

                <div class="border-t border-gray-200 my-4"></div>

                <Link :href="route('admin.profile')" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-md" :class="{ 'bg-gray-200 font-bold': route().current('admin.profile') }">
                     Profile Settings
                </Link>

                <Link :href="route('admin.logout')" method="post" as="button" class="w-full text-left flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-md">
                    Log Out
                </Link>
            </nav>
        </aside>

        <!-- Main Content (with Mobile Header) -->
        <div class="flex-1 flex flex-col">
            <!-- Mobile Header -->
            <header class="bg-white shadow md:hidden flex justify-between items-center p-4">
                <div class="font-bold text-lg">Admin Panel</div>
                <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="text-gray-500 focus:outline-none">
                     <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </header>
            
            <!-- Mobile Menu Dropdown -->
             <div v-if="showingNavigationDropdown" class="md:hidden bg-white border-b border-gray-200 p-4">
                 <Link :href="route('admin.dashboard')" class="block py-2 text-gray-700">Dashboard</Link>
                 <Link :href="route('admin.visitors.index')" class="block py-2 text-gray-700">Visitors</Link>
                 <Link :href="route('admin.profile')" class="block py-2 text-gray-700">Profile Settings</Link>
                 <Link :href="route('admin.logout')" method="post" as="button" class="block py-2 text-red-600 w-full text-left">Log Out</Link>
             </div>

            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 flex justify-between items-center">
                    <div>
                        <slot name="header" />
                    </div>
                    <div class="flex items-center gap-4">
                        <slot name="actions" />
                        <NotificationDropdown />
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm">
                    <p class="text-sm font-bold text-green-800">{{ $page.props.flash.success }}</p>
                </div>

                <div v-if="$page.props.flash.error" class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
                    <p class="text-sm font-bold text-red-800">{{ $page.props.flash.error }}</p>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
