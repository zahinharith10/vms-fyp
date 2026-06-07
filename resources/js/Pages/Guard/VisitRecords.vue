<script setup>
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { formatMalaysiaTime } from '@/utils/datetime';
import { ref, computed } from 'vue';

const props = defineProps({
    visitRecords: Array,
});

const searchQuery = ref('');
const filterType = ref('All');
const filterStatus = ref('All');

const filteredRecords = computed(() => {
    return props.visitRecords.filter(log => {
        // Filter by Type
        if (filterType.value !== 'All' && log.type !== filterType.value) {
            return false;
        }
        
        // Filter by Status
        if (filterStatus.value !== 'All' && log.status !== filterStatus.value) {
            return false;
        }
        
        // Search query
        if (searchQuery.value.trim() !== '') {
            const query = searchQuery.value.toLowerCase();
            const nameMatch = log.name?.toLowerCase().includes(query);
            const phoneMatch = log.phone?.toLowerCase().includes(query);
            const plateMatch = log.vehicle_number?.toLowerCase().includes(query);
            const unitMatch = log.unit_number?.toLowerCase().includes(query);
            const purposeMatch = log.purpose?.toLowerCase().includes(query);
            return nameMatch || phoneMatch || plateMatch || unitMatch || purposeMatch;
        }
        
        return true;
    });
});

const resetFilters = () => {
    searchQuery.value = '';
    filterType.value = 'All';
    filterStatus.value = 'All';
};
</script>

<template>
    <Head title="Visit Records" />

    <GuardAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">All Visit Records</h2>
        </template>

        <div class="max-w-4xl mx-auto">
            <div v-if="visitRecords.length === 0" class="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-800 transition-colors duration-200">
                <div class="h-20 w-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-4xl">📋</span>
                </div>
                <h3 class="text-xl font-black text-gray-800 dark:text-white">No Records Found</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1">There are no visit or delivery records yet.</p>
            </div>

            <div v-else>
                <!-- Search & Filter Controls -->
                <div class="bg-white dark:bg-gray-900 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 mb-6 transition-all duration-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search Input -->
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 dark:text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search name, phone, plate, unit..."
                                class="pl-10 pr-10 py-2.5 w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-indigo-500 rounded-2xl text-sm font-medium text-gray-800 dark:text-gray-200 transition-colors"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <select
                                v-model="filterType"
                                class="py-2.5 pl-4 pr-10 w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-indigo-500 rounded-2xl text-sm font-semibold text-gray-700 dark:text-gray-300 transition-colors cursor-pointer"
                            >
                                <option value="All">All Types</option>
                                <option value="Visitor">Visitors Only</option>
                                <option value="Delivery">Deliveries Only</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <select
                                v-model="filterStatus"
                                class="py-2.5 pl-4 pr-10 w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-indigo-500 rounded-2xl text-sm font-semibold text-gray-700 dark:text-gray-300 transition-colors cursor-pointer"
                            >
                                <option value="All">All Statuses</option>
                                <option value="Checked In">Checked In</option>
                                <option value="Checked Out">Checked Out</option>
                                <option value="Temporarily Out">Temporarily Out</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Empty State for Filtered Results -->
                <div v-if="filteredRecords.length === 0" class="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-800 transition-colors duration-200">
                    <div class="h-20 w-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-4xl">🔍</span>
                    </div>
                    <h3 class="text-xl font-black text-gray-800 dark:text-white">No Matching Records</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Try adjusting your search query or filters.</p>
                    <button
                        @click="resetFilters"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-xs font-bold rounded-2xl border border-indigo-100 dark:border-indigo-900 transition-colors"
                    >
                        Reset Filters
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 gap-4">
                    <div v-for="log in filteredRecords" :key="log.type + log.id" class="bg-white dark:bg-gray-900 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-between group hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-200">
                    <div class="flex items-center">
                        <div class="h-16 w-16 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center mr-5 overflow-hidden border-2 border-white dark:border-gray-700 shadow-sm ring-1 ring-gray-100 dark:ring-gray-700">
                             <img v-if="log.photo" :src="'/storage/' + log.photo" class="h-full w-full object-cover" />
                             <span v-else class="text-gray-400 dark:text-gray-500 font-black text-2xl uppercase">{{ log.name.charAt(0) }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest"
                                    :class="log.is_delivery ? 'bg-orange-100 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400' : 'bg-indigo-100 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400'"
                                >
                                    {{ log.type }}
                                </span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest"
                                    :class="{
                                        'bg-green-100 dark:bg-green-950/40 text-green-600 dark:text-green-400': log.status === 'Checked In',
                                        'bg-red-100 dark:bg-red-950/40 text-red-600 dark:text-red-400': log.status === 'Checked Out',
                                        'bg-yellow-100 dark:bg-yellow-950/40 text-yellow-600 dark:text-yellow-400': log.status === 'Temporarily Out',
                                        'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400': log.status === 'Pending' || log.status === 'Approved'
                                    }"
                                >
                                    {{ log.status }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tighter">Created at {{ formatMalaysiaTime(log.created_at) }}</span>
                            </div>
                            <h4 class="font-black text-gray-900 dark:text-white leading-tight">{{ log.name }}</h4>
                            <div class="grid grid-cols-3 gap-x-4 gap-y-1 mt-2">
                                <div>
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Plate Number</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">{{ log.vehicle_number || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Unit</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">{{ log.unit_number }}</p>
                                </div>
                                <div v-if="log.parking_lot_number">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Parking Lot</p>
                                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">🅿️ Lot {{ log.parking_lot_number }}</p>
                                </div>
                                <div v-if="log.entry_time">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Entry Time</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ formatMalaysiaTime(log.entry_time) }}</p>
                                </div>
                                <div v-if="log.exit_time">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Exit Time</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ formatMalaysiaTime(log.exit_time) }}</p>
                                </div>
                                <div class="col-span-3">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Purpose</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase truncate max-w-[200px]">{{ log.purpose }}</p>
                                </div>

                                <!-- Sessions Timeline (Visitor only) -->
                                <div v-if="!log.is_delivery && log.sessions && log.sessions.length > 0" class="col-span-3 mt-2">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none mb-1.5">Sessions ({{ log.sessions.length }})</p>
                                    <div class="space-y-1.5">
                                        <div
                                            v-for="(session, index) in log.sessions"
                                            :key="index"
                                            class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800/50 rounded-xl px-3 py-2 border border-gray-100 dark:border-gray-800"
                                        >
                                            <span class="text-[10px] font-black text-indigo-500 dark:text-indigo-400 w-5 text-center">#{{ index + 1 }}</span>
                                            <div class="flex items-center gap-3 flex-1 flex-wrap">
                                                <div>
                                                    <p class="text-[7px] font-black text-gray-400 dark:text-gray-600 uppercase tracking-widest leading-none">Check-In</p>
                                                    <p class="text-[10px] font-bold text-green-600 dark:text-green-400">{{ formatMalaysiaTime(session.check_in_time) }}</p>
                                                </div>
                                                <span class="text-gray-300 dark:text-gray-700 text-xs">→</span>
                                                <div>
                                                    <p class="text-[7px] font-black text-gray-400 dark:text-gray-600 uppercase tracking-widest leading-none">Check-Out</p>
                                                    <p class="text-[10px] font-bold" :class="session.check_out_time ? 'text-red-500 dark:text-red-400' : 'text-yellow-500 dark:text-yellow-400'">
                                                        {{ session.check_out_time ? formatMalaysiaTime(session.check_out_time) : 'Still Inside' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </GuardAuthenticatedLayout>
</template>
