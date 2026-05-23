<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    resident: Object,
    stats: Object,
});
</script>

<template>
    <Head title="Resident Dashboard" />

    <ResidentAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome Section -->
                <div class="bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl mb-8 transition-all duration-300">
                    <div class="p-8 text-gray-900 dark:text-white flex justify-between items-center">
                        <div>
                            <h3 class="text-2xl font-black text-gray-800 dark:text-white">Welcome Back, {{ $page.props.auth.user.name }}!</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">Logged in as a <strong>{{ $page.props.auth.user.type }}</strong>.</p>
                        </div>
                        <div class="hidden sm:block">
                            <div class="bg-indigo-50 dark:bg-indigo-950/20 px-5 py-3 rounded-2xl border border-indigo-100 dark:border-indigo-900/30 flex flex-col items-center">
                                <span class="text-xs text-indigo-500 dark:text-indigo-400 uppercase font-bold tracking-wide">My Unit</span>
                                <span class="text-lg font-black text-indigo-700 dark:text-indigo-300" v-if="$page.props.auth.user.house_unit">
                                    {{ $page.props.auth.user.house_unit.formatted_unit }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <!-- Total Visitors -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'visitors' })"
                        class="block bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl p-6 border-l-4 border-l-gray-400 dark:border-l-gray-450 hover:shadow-md hover:scale-[1.02] transition-all duration-350 cursor-pointer"
                    >
                        <div class="text-gray-500 dark:text-gray-450 text-xs font-black uppercase tracking-wider">Total Visitors</div>
                        <div class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ stats.total_visitors }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 italic font-medium">Lifetime total</div>
                    </Link>

                    <!-- Pending Approval -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'pending' })"
                        class="block bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl p-6 border-l-4 border-l-yellow-400 dark:border-l-yellow-500 hover:shadow-md hover:scale-[1.02] transition-all duration-350 cursor-pointer"
                    >
                        <div class="text-gray-500 dark:text-gray-450 text-xs font-black uppercase tracking-wider">Pending Approval</div>
                        <div class="mt-2 text-3xl font-black text-yellow-600 dark:text-yellow-400">{{ stats.pending_requests }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 italic font-medium">Action required</div>
                    </Link>

                    <!-- Upcoming Visitors -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'visitors' })"
                        class="block bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl p-6 border-l-4 border-l-blue-400 dark:border-l-blue-500 hover:shadow-md hover:scale-[1.02] transition-all duration-350 cursor-pointer"
                    >
                        <div class="text-gray-500 dark:text-gray-450 text-xs font-black uppercase tracking-wider">Upcoming Guests</div>
                        <div class="mt-2 text-3xl font-black text-blue-600 dark:text-blue-400">{{ stats.upcoming_visitors }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 italic font-medium">Approved guests</div>
                    </Link>

                    <!-- Upcoming Deliveries -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'deliveries' })"
                        class="block bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl p-6 border-l-4 border-l-orange-500 dark:border-l-orange-500 hover:shadow-md hover:scale-[1.02] transition-all duration-350 cursor-pointer"
                    >
                        <div class="text-gray-500 dark:text-gray-450 text-xs font-black uppercase tracking-wider">Upcoming Deliveries</div>
                        <div class="mt-2 text-3xl font-black text-orange-600 dark:text-orange-400">{{ stats.upcoming_deliveries }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 italic font-medium">Approved riders</div>
                    </Link>

                    <!-- Active Now -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'visitors' })"
                        class="block bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl p-6 border-l-4 border-l-green-400 dark:border-l-green-500 hover:shadow-md hover:scale-[1.02] transition-all duration-350 cursor-pointer"
                    >
                        <div class="text-gray-500 dark:text-gray-450 text-xs font-black uppercase tracking-wider">Active Visitors</div>
                        <div class="mt-2 text-3xl font-black text-green-600 dark:text-green-400">{{ stats.active_visitors }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 italic font-medium">Currently on-site</div>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl p-6 col-span-1 transition-all duration-300">
                         <h3 class="font-black text-gray-800 dark:text-white mb-6 uppercase text-sm tracking-wider">Quick Actions</h3>
                         <div class="space-y-3">
                             <Link :href="route('resident.visitors.index')" class="w-full flex items-center bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-black py-4 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/80 transition px-4 border border-gray-200 dark:border-gray-700">
                                 <span class="mr-3 text-lg">📋</span> View Visit History
                             </Link>
                             <Link :href="route('resident.visitors.create')" class="w-full flex items-center bg-indigo-50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-400 font-black py-4 rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition px-4 border border-indigo-200 dark:border-indigo-900/30">
                                 <span class="mr-3 text-lg">➕</span> Register a Visitor
                             </Link>
                         </div>
                    </div>

                    <!-- Information Card -->
                    <div class="bg-indigo-900 dark:bg-slate-950 overflow-hidden shadow-lg dark:shadow-none border border-transparent dark:border-gray-800 sm:rounded-3xl p-8 col-span-1 md:col-span-2 text-white relative transition-all duration-300">
                        <div class="relative z-10">
                            <h3 class="font-black mb-4 uppercase text-sm tracking-wider text-indigo-400 dark:text-indigo-400 opacity-90">Resident Portal Info</h3>
                            <p class="text-lg leading-relaxed">
                                Manage your guests with ease. From here you can <span class="text-indigo-300 dark:text-indigo-400 font-bold">approve or reject</span> incoming requests, and view <span class="text-indigo-300 dark:text-indigo-400 font-bold">active guests</span> in real-time.
                            </p>
                            <p class="text-sm text-indigo-200 dark:text-indigo-300 mt-3 leading-relaxed font-medium">
                                💡 **Deliveries Auto-Approval:** You can enable the toggle button in your <Link :href="route('resident.profile')" class="text-white underline font-bold hover:text-indigo-100 dark:hover:text-indigo-300 transition">Profile Settings</Link> to allow delivery services (Grab, Shopee, etc.) to be approved automatically without manual gate requests!
                            </p>
                            <div class="mt-6 flex flex-wrap gap-4">
                                <div class="bg-white/10 dark:bg-white/5 p-4 rounded-xl flex-1 min-w-[120px] border border-white/5">
                                    <span class="text-2xl block mb-1">🛡️</span>
                                    <span class="text-xs uppercase font-bold opacity-70 tracking-tighter">Security</span>
                                    <p class="text-sm font-medium">Safe & Monitored</p>
                                </div>
                                <div class="bg-white/10 dark:bg-white/5 p-4 rounded-xl flex-1 min-w-[120px] border border-white/5">
                                    <span class="text-2xl block mb-1">⚡</span>
                                    <span class="text-xs uppercase font-bold opacity-70 tracking-tighter">Efficiency</span>
                                    <p class="text-sm font-medium">Quick Approvals</p>
                                </div>
                                <div class="bg-white/10 dark:bg-white/5 p-4 rounded-xl flex-1 min-w-[120px] border border-white/5">
                                    <span class="text-2xl block mb-1">📦</span>
                                    <span class="text-xs uppercase font-bold opacity-70 tracking-tighter">Deliveries</span>
                                    <p class="text-sm font-medium">Auto-Approved</p>
                                </div>
                            </div>
                        </div>
                        <div class="absolute bottom-0 right-0 opacity-10 pointer-events-none">
                             <svg class="h-48 w-48" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ResidentAuthenticatedLayout>
</template>
