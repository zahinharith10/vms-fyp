<script setup>
import VisitorAuthenticatedLayout from '@/Layouts/VisitorAuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatMalaysiaDateTime } from '@/utils/datetime';

const props = defineProps({
    visitor: Object,
});

const visits = computed(() => props.visitor?.visits || []);

// Optional: basic filtering by status
const filterStatus = ref('All');

const filteredVisits = computed(() => {
    if (filterStatus.value === 'All') return visits.value;
    return visits.value.filter(v => v.status === filterStatus.value);
});

const cancelVisit = (id) => {
    if (confirm('Are you sure you want to cancel this visit request?')) {
        router.delete(route('visitor.visits.destroy', id));
    }
};

const formatDuration = (mins) => {
    if (!mins && mins !== 0) return '—';
    if (mins < 60) return `${mins} min${mins === 1 ? '' : 's'}`;
    const hrs = Math.floor(mins / 60);
    const remMins = mins % 60;
    return `${hrs} hr${hrs === 1 ? '' : 's'} ${remMins} min${remMins === 1 ? '' : 's'}`;
};
</script>

<template>
    <Head title="Visit History" />

    <VisitorAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">My Visit History</h2>
        </template>

        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm dark:shadow-indigo-950/10 sm:rounded-lg border border-transparent dark:border-gray-800">
            <div class="p-6">
                <!-- Filter Controls -->
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 sm:mb-0">All Visits</h3>
                    <div class="flex space-x-2">
                        <select v-model="filterStatus" class="border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 dark:focus:border-indigo-600 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-500 focus:ring-opacity-50 text-sm">
                            <option value="All">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Checked In">Checked In</option>
                            <option value="Checked Out">Checked Out</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- History List -->
                <div v-if="filteredVisits.length > 0" class="space-y-4">
                    <div v-for="visit in filteredVisits" :key="visit.id" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-5 flex flex-col sm:flex-row sm:items-center justify-between transition-all hover:shadow-md dark:hover:shadow-indigo-950/20">
                        
                        <div class="mb-4 sm:mb-0">
                            <div class="flex items-center space-x-3 mb-1">
                                <span class="text-xl font-black text-indigo-900 dark:text-indigo-400">{{ visit.unit_number }}</span>
                                <span :class="{
                                    'bg-yellow-100 dark:bg-yellow-950/40 text-yellow-800 dark:text-yellow-400': visit.status === 'Pending',
                                    'bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-400': visit.status === 'Approved',
                                    'bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-400': visit.status === 'Checked In',
                                    'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300': visit.status === 'Checked Out',
                                    'bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-400': visit.status === 'Rejected',
                                    'bg-orange-100 dark:bg-orange-950/40 text-orange-800 dark:text-orange-400': visit.status === 'Cancelled',
                                }" class="px-2 py-1 text-xs font-bold rounded-full uppercase tracking-wider">
                                    {{ visit.status }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                                Purpose: <span class="text-gray-900 dark:text-gray-100">{{ visit.purpose }}</span>
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Requested on: {{ formatMalaysiaDateTime(visit.created_at) }}
                            </div>
                            <div v-if="visit.parking_lot_number" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold mt-1.5 flex items-center gap-1">
                                <span>🅿️</span> Assigned Parking: <span class="bg-indigo-50 dark:bg-indigo-950/30 px-2 py-0.5 rounded border border-indigo-100 dark:border-indigo-900/30 uppercase tracking-widest text-[9px] font-black text-indigo-600 dark:text-indigo-400">Lot {{ visit.parking_lot_number }}</span>
                            </div>

                            <!-- Visit Timing & Duration Details -->
                            <div v-if="visit.check_in_time || visit.sessions?.length > 0" class="mt-3 space-y-1 bg-gray-100/60 dark:bg-gray-900/40 p-2.5 rounded-lg text-xs max-w-md">
                                <div class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest text-[9px] mb-1">Visit Timing & Duration:</div>
                                <div v-if="visit.sessions?.length > 0">
                                    <div v-for="sess in visit.sessions" :key="sess.id" class="flex flex-col sm:flex-row sm:items-center justify-between text-gray-600 dark:text-gray-400 gap-x-4 mb-0.5">
                                        <span>📥 In: {{ formatMalaysiaDateTime(sess.check_in_time) }}</span>
                                        <span v-if="sess.check_out_time">📤 Out: {{ formatMalaysiaDateTime(sess.check_out_time) }}</span>
                                        <span v-else class="text-green-600 font-bold">On-Site</span>
                                    </div>
                                </div>
                                <div v-else class="flex flex-col sm:flex-row sm:items-center justify-between text-gray-600 dark:text-gray-400 gap-x-4">
                                    <span>📥 In: {{ formatMalaysiaDateTime(visit.check_in_time) }}</span>
                                    <span v-if="visit.check_out_time">📤 Out: {{ formatMalaysiaDateTime(visit.check_out_time) }}</span>
                                    <span v-else class="text-green-600 font-bold">On-Site</span>
                                </div>
                                <div class="border-t border-gray-200/50 dark:border-gray-800/50 my-1 pt-1 flex justify-between font-bold text-indigo-700 dark:text-indigo-400">
                                    <span>Total Duration:</span>
                                    <span>{{ formatDuration(visit.total_duration_minutes) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3">
                            <Link v-if="['Approved', 'Checked In', 'Temporarily Out'].includes(visit.status)" :href="route('visitor.visits.qr', visit.id)" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-800 active:bg-indigo-900 dark:active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                View QR Code
                            </Link>
                            
                            <button 
                                v-if="['Pending', 'Approved'].includes(visit.status)" 
                                @click="cancelVisit(visit.id)" 
                                class="inline-flex items-center justify-center px-4 py-2 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/30 rounded-md font-semibold text-xs text-red-600 dark:text-red-400 uppercase tracking-widest hover:bg-red-100 dark:hover:bg-red-950/50 hover:border-red-300 dark:hover:border-red-900/50 transition ease-in-out duration-150"
                            >
                                Cancel
                            </button>

                            <span v-if="['Rejected', 'Checked Out', 'Cancelled'].includes(visit.status)" class="text-sm text-gray-400 dark:text-gray-500 italic px-2">
                                No Actions
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-800/50 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-300 font-medium text-lg">No visits found.</p>
                    <p class="text-sm text-gray-400 dark:text-gray-400 mt-1">You haven't made any visit requests matching this criteria yet.</p>
                    <Link :href="route('visitor.dashboard')" class="inline-block mt-4 text-indigo-600 dark:text-indigo-400 font-bold hover:underline dark:hover:text-indigo-300">
                        Request a New Visit →
                    </Link>
                </div>

            </div>
        </div>
    </VisitorAuthenticatedLayout>
</template>
