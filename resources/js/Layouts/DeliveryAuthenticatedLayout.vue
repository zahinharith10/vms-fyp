<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const showingNavigationDropdown = ref(false);
const delivery = usePage().props.auth.user;
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-xl min-h-screen hidden md:block border-r border-gray-100 flex flex-col">
            <div class="p-8 border-b border-gray-50">
                <Link :href="route('delivery.dashboard')" class="flex flex-col items-center">
                    <ApplicationLogo class="block h-12 w-auto fill-current text-orange-600 mb-2" />
                    <span class="font-black text-xs uppercase tracking-widest text-gray-400">Delivery Portal</span>
                </Link>
            </div>
            
            <nav class="mt-8 px-6 space-y-3 flex-1">
                <div class="px-4 mb-6">
                     <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Authenticated</p>
                     <p class="text-sm font-black text-gray-900 truncate tracking-tight">{{ delivery?.name || 'Loading...' }}</p>
                     <p class="text-[10px] font-bold text-orange-600 uppercase tracking-widest">{{ delivery?.company || 'Delivery Personnel' }}</p>
                </div>

                <Link :href="route('delivery.dashboard')" class="flex items-center px-4 py-3 text-gray-600 hover:bg-orange-50 hover:text-orange-700 rounded-xl transition-all duration-200" :class="{ 'bg-orange-50 text-orange-700 font-bold shadow-sm': route().current('delivery.dashboard') }">
                    <span class="mr-3 text-xl">🚚</span> Dashboard
                </Link>

                <Link :href="route('delivery.profile')" class="flex items-center px-4 py-3 text-gray-600 hover:bg-orange-50 hover:text-orange-700 rounded-xl transition-all duration-200" :class="{ 'bg-orange-50 text-orange-700 font-bold shadow-sm': route().current('delivery.profile') }">
                    <span class="mr-3 text-xl">👤</span> My Profile
                </Link>

                <div class="border-t border-gray-100 my-8"></div>

                <Link :href="route('delivery.logout')" method="post" as="button" class="w-full text-left flex items-center px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 group">
                    <span class="mr-3 text-xl group-hover:scale-110 transition-transform">🚪</span> 
                    <span class="font-bold text-sm">Log Out</span>
                </Link>
            </nav>

            <!-- Status Badge -->
            <div class="p-6 border-t border-gray-50">
                <div class="bg-indigo-900 rounded-2xl p-4 shadow-lg shadow-indigo-100 italic">
                    <div class="flex items-center text-white">
                        <div class="h-2 w-2 bg-green-400 rounded-full animate-pulse mr-2"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest">System Active</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Mobile Header -->
            <header class="bg-white shadow-sm md:hidden flex justify-between items-center p-6 border-b border-gray-100">
                <div class="font-black text-gray-900 tracking-tighter uppercase italic">Delivery Portal</div>
                <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="text-gray-500 focus:outline-none bg-gray-50 p-2 rounded-xl">
                     <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </header>
            
            <!-- Mobile Menu Dropdown -->
             <div v-if="showingNavigationDropdown" class="md:hidden bg-white border-b border-gray-100 p-6 space-y-4 animate-in slide-in-from-top duration-300">
                 <Link :href="route('delivery.dashboard')" class="block py-3 font-bold text-gray-700">Dashboard</Link>
                 <Link :href="route('delivery.profile')" class="block py-3 font-bold text-gray-700">Profile</Link>
                 <Link :href="route('delivery.logout')" method="post" as="button" class="block py-3 text-red-600 font-bold w-full text-left">Log Out</Link>
             </div>

            <!-- Page Heading -->
            <header class="bg-white border-b border-gray-100" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-8 py-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
