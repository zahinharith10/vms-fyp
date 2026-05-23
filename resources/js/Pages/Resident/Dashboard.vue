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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 text-gray-900 flex justify-between items-center">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800">Welcome Back, {{ $page.props.auth.user.name }}!</h3>
                            <p class="text-gray-600 mt-1">Logged in as a <strong>{{ $page.props.auth.user.type }}</strong>.</p>
                        </div>
                        <div class="hidden sm:block">
                            <div class="bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 flex flex-col items-center">
                                <span class="text-xs text-indigo-500 uppercase font-bold">My Unit</span>
                                <span class="text-lg font-bold text-indigo-700" v-if="$page.props.auth.user.house_unit">
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
                        class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-gray-400 hover:shadow-md hover:scale-[1.02] transition-all duration-200 cursor-pointer"
                    >
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Total Visitors</div>
                        <div class="mt-2 text-3xl font-black text-gray-900">{{ stats.total_visitors }}</div>
                        <div class="text-xs text-gray-400 mt-1 italic">Lifetime total</div>
                    </Link>

                    <!-- Pending Approval -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'pending' })"
                        class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-400 hover:shadow-md hover:scale-[1.02] transition-all duration-200 cursor-pointer"
                    >
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Pending Approval</div>
                        <div class="mt-2 text-3xl font-black text-yellow-600">{{ stats.pending_requests }}</div>
                        <div class="text-xs text-gray-400 mt-1 italic">Action required</div>
                    </Link>

                    <!-- Upcoming Visitors -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'visitors' })"
                        class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-400 hover:shadow-md hover:scale-[1.02] transition-all duration-200 cursor-pointer"
                    >
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Upcoming Guests</div>
                        <div class="mt-2 text-3xl font-black text-blue-600">{{ stats.upcoming_visitors }}</div>
                        <div class="text-xs text-gray-400 mt-1 italic">Approved guests</div>
                    </Link>

                    <!-- Upcoming Deliveries -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'deliveries' })"
                        class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-orange-500 hover:shadow-md hover:scale-[1.02] transition-all duration-200 cursor-pointer"
                    >
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Upcoming Deliveries</div>
                        <div class="mt-2 text-3xl font-black text-orange-600">{{ stats.upcoming_deliveries }}</div>
                        <div class="text-xs text-gray-400 mt-1 italic">Approved riders</div>
                    </Link>

                    <!-- Active Now -->
                    <Link 
                        :href="route('resident.visitors.index', { tab: 'visitors' })"
                        class="block bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-400 hover:shadow-md hover:scale-[1.02] transition-all duration-200 cursor-pointer"
                    >
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Active Visitors</div>
                        <div class="mt-2 text-3xl font-black text-green-600">{{ stats.active_visitors }}</div>
                        <div class="text-xs text-gray-400 mt-1 italic">Currently on-site</div>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Quick Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 col-span-1">
                         <h3 class="font-bold text-gray-800 mb-4 uppercase text-sm tracking-wider">Quick Actions</h3>
                         <div class="space-y-3">
                             <Link :href="route('resident.visitors.index')" class="w-full flex items-center bg-gray-50 text-gray-700 font-bold py-3 rounded-lg hover:bg-gray-100 transition px-4 border border-gray-200">
                                 <span class="mr-3 text-lg">📋</span> View Visit History
                             </Link>
                             <Link :href="route('resident.visitors.create')" class="w-full flex items-center bg-indigo-50 text-indigo-700 font-bold py-3 rounded-lg hover:bg-indigo-100 transition px-4 border border-indigo-200">
                                 <span class="mr-3 text-lg">➕</span> Register a Visitor
                             </Link>
                         </div>
                    </div>

                    <!-- Information Card -->
                    <div class="bg-indigo-900 overflow-hidden shadow-lg sm:rounded-lg p-6 col-span-1 md:col-span-2 text-white relative">
                        <div class="relative z-10">
                            <h3 class="font-bold mb-4 uppercase text-sm tracking-wider opacity-80">Resident Portal Info</h3>
                            <p class="text-lg leading-relaxed">
                                Manage your guests with ease. From here you can <span class="text-indigo-300 font-bold">approve or reject</span> incoming requests, and view <span class="text-indigo-300 font-bold">active guests</span> in real-time.
                            </p>
                            <p class="text-sm text-indigo-200 mt-3 leading-relaxed font-medium">
                                💡 **Deliveries Auto-Approval:** You can enable the toggle button in your <Link :href="route('resident.profile')" class="text-white underline font-bold hover:text-indigo-100 transition">Profile Settings</Link> to allow delivery services (Grab, Shopee, etc.) to be approved automatically without manual gate requests!
                            </p>
                            <div class="mt-6 flex flex-wrap gap-4">
                                <div class="bg-white/10 p-4 rounded-lg flex-1 min-w-[120px]">
                                    <span class="text-2xl block mb-1">🛡️</span>
                                    <span class="text-xs uppercase font-bold opacity-70 tracking-tighter">Security</span>
                                    <p class="text-sm font-medium">Safe & Monitored</p>
                                </div>
                                <div class="bg-white/10 p-4 rounded-lg flex-1 min-w-[120px]">
                                    <span class="text-2xl block mb-1">⚡</span>
                                    <span class="text-xs uppercase font-bold opacity-70 tracking-tighter">Efficiency</span>
                                    <p class="text-sm font-medium">Quick Approvals</p>
                                </div>
                                <div class="bg-white/10 p-4 rounded-lg flex-1 min-w-[120px]">
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
