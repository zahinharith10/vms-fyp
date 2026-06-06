<script setup>
import DeliveryAuthenticatedLayout from '@/Layouts/DeliveryAuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMalaysiaDateTime } from '@/utils/datetime';

const props = defineProps({
    logs: Array,
});

const CANCELLED_STATUSES = ['Cancelled', 'Rejected'];

const groupedLogs = computed(() => {
    const groups = [];
    const processedRunIds = new Set();

    for (const log of props.logs || []) {
        if (log.run && log.run.type === 'multi' && !processedRunIds.has(log.run.id)) {
            processedRunIds.add(log.run.id);
            const runLogs = (props.logs || []).filter(l => l.run?.id === log.run.id);
            groups.push({
                type: 'multi',
                run: log.run,
                logs: runLogs,
                created_at: log.run.created_at,
            });
        } else if (!log.run || log.run.type === 'single') {
            groups.push({
                type: 'single',
                log: log,
                created_at: log.created_at,
            });
        }
    }

    return groups;
});

const filterStatus = ref('All');

const filteredGroups = computed(() => {
    if (filterStatus.value === 'All') return groupedLogs.value;
    return groupedLogs.value.filter(group => {
        if (group.type === 'multi') {
            return group.run.status === filterStatus.value ||
                   group.logs.some(l => l.status === filterStatus.value);
        }
        const displayStatus = group.log.exit_time && !CANCELLED_STATUSES.includes(group.log.status)
            ? 'Completed' : group.log.status;
        return displayStatus === filterStatus.value;
    });
});

const getRunDisplayStatus = (group) => {
    if (group.type === 'multi') return group.run.status;
    return group.log.exit_time && !CANCELLED_STATUSES.includes(group.log.status)
        ? 'Completed'
        : group.log.status;
};

const cancelTrip = (group) => {
    if (confirm('Are you sure you want to cancel this delivery trip?')) {
        const id = group.type === 'multi' ? group.run.id : group.log.id;
        router.delete(route('delivery.trips.cancel', id));
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
    <Head title="Delivery History" />

    <DeliveryAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">My Delivery History</h2>
        </template>

        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm dark:shadow-indigo-950/10 sm:rounded-lg border border-transparent dark:border-gray-800">
            <div class="p-6">
                <!-- Filter Controls -->
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 sm:mb-0">All Delivery Trips</h3>
                    <div class="flex space-x-2">
                        <select v-model="filterStatus" class="border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 dark:focus:border-indigo-600 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-500 focus:ring-opacity-50 text-sm">
                            <option value="All">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Checked In">Checked In</option>
                            <option value="Checked Out">Checked Out</option>
                            <option value="Completed">Completed</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- History List -->
                <div v-if="filteredGroups.length > 0" class="space-y-4">
                    <div v-for="group in filteredGroups" :key="group.type === 'multi' ? group.run.id : group.log.id" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-5 flex flex-col sm:flex-row sm:items-center justify-between transition-all hover:shadow-md dark:hover:shadow-indigo-950/20">
                        
                        <div class="mb-4 sm:mb-0">
                            <!-- Header Title + Overall Status Badge -->
                            <div class="flex items-center space-x-3 mb-1 flex-wrap gap-y-1">
                                <span v-if="group.type === 'single'" class="text-xl font-black text-indigo-900 dark:text-indigo-400">Unit {{ group.log.destination }}</span>
                                <span v-else class="text-xl font-black text-orange-600 dark:text-orange-400">Multi-Stop Trip</span>
                                
                                <span :class="{
                                    'bg-yellow-100 dark:bg-yellow-950/40 text-yellow-800 dark:text-yellow-400': getRunDisplayStatus(group) === 'Pending',
                                    'bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-400': getRunDisplayStatus(group) === 'Approved',
                                    'bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-400': getRunDisplayStatus(group) === 'Checked In' || getRunDisplayStatus(group) === 'Completed',
                                    'bg-gray-150 dark:bg-gray-800 text-gray-800 dark:text-gray-300': getRunDisplayStatus(group) === 'Checked Out',
                                    'bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-400': getRunDisplayStatus(group) === 'Rejected',
                                    'bg-orange-100 dark:bg-orange-950/40 text-orange-800 dark:text-orange-400': getRunDisplayStatus(group) === 'Cancelled',
                                }" class="px-2 py-1 text-xs font-bold rounded-full uppercase tracking-wider">
                                    {{ getRunDisplayStatus(group) }}
                                </span>
                            </div>

                            <!-- Single Stop details -->
                            <div v-if="group.type === 'single'" class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                                Resident: <span class="text-gray-900 dark:text-gray-100">{{ group.log.host_name || '—' }}</span>
                            </div>

                            <!-- Single Stop Timing & Duration Details -->
                            <div v-if="group.type === 'single' && group.log.entry_time" class="mt-3 space-y-1 bg-gray-100/60 dark:bg-gray-900/40 p-2.5 rounded-lg text-xs max-w-md">
                                <div class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest text-[9px] mb-1">Delivery Timing & Duration:</div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-gray-600 dark:text-gray-400 gap-x-4">
                                    <span>📥 In: {{ formatMalaysiaDateTime(group.log.entry_time) }}</span>
                                    <span v-if="group.log.exit_time">📤 Out: {{ formatMalaysiaDateTime(group.log.exit_time) }}</span>
                                    <span v-else class="text-green-600 font-bold">On-Site</span>
                                </div>
                                <div class="border-t border-gray-250/50 dark:border-gray-800/50 my-1 pt-1 flex justify-between font-bold text-indigo-700 dark:text-indigo-400">
                                    <span>Duration:</span>
                                    <span>{{ formatDuration(group.log.total_duration_minutes) }}</span>
                                </div>
                            </div>

                            <!-- Multi Stop destinations itinerary -->
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-1.5 space-y-2 max-w-md" v-else-if="group.type === 'multi'">
                                <div class="font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest text-[9px]">Itinerary Stops:</div>
                                <div v-for="log in group.logs" :key="log.id" class="flex flex-col p-2 bg-white dark:bg-gray-900/40 rounded-xl border border-gray-150/50 dark:border-gray-800/30">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-bold text-gray-800 dark:text-gray-200">📍 Unit {{ log.destination }}</span>
                                            <span v-if="log.host_name" class="text-gray-500 dark:text-gray-400 ml-1">(👤 {{ log.host_name }})</span>
                                        </div>
                                        <span :class="{
                                            'text-yellow-600 dark:text-yellow-400': log.status === 'Pending',
                                            'text-blue-600 dark:text-blue-400': log.status === 'Approved',
                                            'text-green-600 dark:text-green-400': log.status === 'Checked In',
                                            'text-gray-500 dark:text-gray-400': log.status === 'Checked Out',
                                            'text-red-500 dark:text-red-400': CANCELLED_STATUSES.includes(log.status),
                                        }" class="text-[10px] font-black uppercase tracking-wider">
                                            {{ log.status }}
                                        </span>
                                    </div>
                                    <!-- Stop timing -->
                                    <div v-if="log.entry_time" class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap gap-x-3 gap-y-0.5 border-t border-gray-100 dark:border-gray-800/30 pt-1">
                                        <span>📥 In: {{ formatMalaysiaDateTime(log.entry_time) }}</span>
                                        <span v-if="log.exit_time">📤 Out: {{ formatMalaysiaDateTime(log.exit_time) }}</span>
                                        <span v-else class="text-green-600 font-bold">On-Site</span>
                                        <span v-if="log.total_duration_minutes !== null" class="font-bold text-indigo-600 dark:text-indigo-400 ml-auto">Duration: {{ formatDuration(log.total_duration_minutes) }}</span>
                                    </div>
                                </div>

                                <!-- Overall Multi-Stop Trip Timing & Duration Details -->
                                <div v-if="group.run.entry_time" class="mt-3 space-y-1 bg-gray-100/60 dark:bg-gray-900/40 p-2.5 rounded-lg text-xs">
                                    <div class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest text-[9px] mb-1">Overall Trip Timing & Duration:</div>
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between text-gray-600 dark:text-gray-400 gap-x-4">
                                        <span>📥 First Entry: {{ formatMalaysiaDateTime(group.run.entry_time) }}</span>
                                        <span v-if="group.run.exit_time">📤 Final Exit: {{ formatMalaysiaDateTime(group.run.exit_time) }}</span>
                                        <span v-else class="text-green-600 font-bold">On-Site</span>
                                    </div>
                                    <div class="border-t border-gray-250/50 dark:border-gray-800/50 my-1 pt-1 flex justify-between font-bold text-indigo-700 dark:text-indigo-400">
                                        <span>Total Duration:</span>
                                        <span>{{ formatDuration(group.run.total_duration_minutes) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Requested date -->
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Requested on: {{ formatMalaysiaDateTime(group.created_at) }}
                            </div>
                        </div>

                        <!-- Actions section -->
                        <div class="flex-shrink-0 flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3">
                            <Link v-if="['Pending', 'Approved', 'Checked In'].includes(getRunDisplayStatus(group))" :href="route('delivery.dashboard')" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-800 active:bg-indigo-900 dark:active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                View on Dashboard
                            </Link>
                            
                            <button 
                                v-if="['Pending', 'Approved'].includes(getRunDisplayStatus(group))" 
                                @click="cancelTrip(group)" 
                                class="inline-flex items-center justify-center px-4 py-2 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/30 rounded-md font-semibold text-xs text-red-600 dark:text-red-400 uppercase tracking-widest hover:bg-red-100 dark:hover:bg-red-950/50 hover:border-red-300 dark:hover:border-red-900/50 transition ease-in-out duration-150"
                            >
                                Cancel
                            </button>

                            <span v-if="['Rejected', 'Checked Out', 'Cancelled', 'Completed'].includes(getRunDisplayStatus(group))" class="text-sm text-gray-400 dark:text-gray-500 italic px-2">
                                No Actions
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-800/50 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-300 font-medium text-lg">No trips found.</p>
                    <p class="text-sm text-gray-400 dark:text-gray-400 mt-1">You haven't made any delivery requests matching this criteria yet.</p>
                    <Link :href="route('delivery.dashboard')" class="inline-block mt-4 text-indigo-600 dark:text-indigo-400 font-bold hover:underline dark:hover:text-indigo-300">
                        Request a New Delivery →
                    </Link>
                </div>

            </div>
        </div>
    </DeliveryAuthenticatedLayout>
</template>
