<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMalaysiaDate, formatMalaysiaTime } from '@/utils/datetime';

const props = defineProps({
    logs: Array,
});

const filterStatus = ref('All');
const searchQuery = ref('');
const expandedRows = ref(new Set());

const filteredLogs = computed(() => {
    return props.logs.filter(log => {
        const matchesStatus = filterStatus.value === 'All' || log.status === filterStatus.value;
        const matchesSearch = log.visitor.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             log.unit_number.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesStatus && matchesSearch;
    });
});

const toggleRow = (logId) => {
    if (expandedRows.value.has(logId)) {
        expandedRows.value.delete(logId);
    } else {
        expandedRows.value.add(logId);
    }
    // Trigger reactivity
    expandedRows.value = new Set(expandedRows.value);
};

const isExpanded = (logId) => expandedRows.value.has(logId);

const hasSessions = (log) => log.sessions && log.sessions.length > 0;

// Use the first session's check_in_time as the authoritative arrival time.
// Falls back to log.check_in_time for legacy visits with no session rows.
const getFirstCheckIn = (log) => {
    if (hasSessions(log)) return log.sessions[0].check_in_time;
    return log.check_in_time || log.entry_time || null;
};

// Use the last session's check_out_time as the authoritative exit time.
const getLastCheckOut = (log) => {
    if (hasSessions(log)) return log.sessions[log.sessions.length - 1].check_out_time || null;
    return log.check_out_time || log.exit_time || null;
};

const getStatusClass = (status) => {
    switch (status) {
        case 'Checked In': return 'bg-green-100 text-green-800 border-green-200';
        case 'Checked Out': return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'Pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'Approved': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Rejected': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getSessionLabel = (index, total) => {
    if (total === 1) return 'Visit';
    if (index === 0) return '1st Session';
    if (index === 1) return '2nd Session';
    if (index === 2) return '3rd Session';
    return `${index + 1}th Session`;
};

const formatDuration = (log) => {
    // If it's a delivery log (uses entry_time and exit_time instead of check_in_time)
    if (log.entry_time) {
        const start = new Date(log.entry_time);
        const end = log.exit_time ? new Date(log.exit_time) : new Date();
        const diffMs = end - start;
        if (diffMs < 0) return '-';
        return formatMinutes(Math.floor(diffMs / 60000));
    }

    // For visitors: use the robust backend-computed total_duration_minutes
    if (log.total_duration_minutes !== undefined && log.total_duration_minutes !== null) {
        return log.total_duration_minutes > 0 ? formatMinutes(log.total_duration_minutes) : '-';
    }

    return '-';
};

const formatSessionDuration = (session, logStatus) => {
    if (!session.check_in_time) return '-';
    const start = new Date(session.check_in_time);
    const end = session.check_out_time
        ? new Date(session.check_out_time)
        : (logStatus === 'Checked In' ? new Date() : null);
    if (!end) return '-';
    const diffMs = end - start;
    if (diffMs < 0) return '-';
    return formatSeconds(Math.floor(diffMs / 1000));
};

// For session-level: precise HH:MM:SS computed from timestamps
const formatSeconds = (totalSecs) => {
    const hours = Math.floor(totalSecs / 3600);
    const mins  = Math.floor((totalSecs % 3600) / 60);
    const secs  = totalSecs % 60;
    const parts = [];
    if (hours > 0) parts.push(`${hours}h`);
    parts.push(`${String(mins).padStart(hours > 0 ? 2 : 1, '0')}m`);
    parts.push(`${String(secs).padStart(2, '0')}s`);
    return parts.join(' ');
};

// For overall total (backend-rounded to minutes)
const formatMinutes = (totalMins) => {
    const hours = Math.floor(totalMins / 60);
    const mins = totalMins % 60;
    if (hours > 0) {
        return `${hours} hr ${mins} min`;
    }
    return `${mins} min`;
};
</script>

<template>
    <Head title="Visit Monitoring" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visit History</h2>
        </template>

        <template #actions>
            <a :href="route('admin.visit-logs.export')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export CSV
            </a>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Filters -->
                        <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                            <div class="flex items-center space-x-4 w-full md:w-auto">
                                <select v-model="filterStatus" class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="All">All Visits</option>
                                    <option value="Checked In">Active (Checked In)</option>
                                    <option value="Checked Out">History (Checked Out)</option>
                                    <option value="Approved">Approved (Not In)</option>
                                    <option value="Pending">Pending Approval</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                                
                                <div class="relative w-full md:w-64">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </span>
                                    <input v-model="searchQuery" type="text" placeholder="Search name or unit..." class="pl-10 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm w-full" />
                                </div>
                            </div>
                            
                            <div class="text-sm font-medium text-gray-500">
                                Showing {{ filteredLogs.length }} records
                            </div>
                        </div>

                        <!-- Hint -->
                        <p class="text-xs text-gray-400 mb-3 italic">
                            💡 Click the <strong>▾ arrow</strong> in the Sessions column to expand individual session times.
                        </p>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Visitor</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Unit</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Arrival/Entry</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Exit</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Sessions</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Duration</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <template v-for="log in filteredLogs" :key="log.id">
                                        <!-- Main Row -->
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="h-8 w-8 rounded-full overflow-hidden bg-gray-100 mr-3">
                                                        <img v-if="log.visitor.photo" :src="'/storage/' + log.visitor.photo" class="h-full w-full object-cover" />
                                                        <div v-else class="h-full w-full flex items-center justify-center text-xs font-bold text-gray-400">
                                                            {{ log.visitor.name.charAt(0) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-bold text-gray-900">{{ log.visitor.name }}</div>
                                                        <div class="text-xs text-gray-500">{{ log.visitor.phone }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-lg font-black text-sm border border-indigo-100">
                                                    {{ log.unit_number }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {{ log.purpose }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="getStatusClass(log.status)" class="px-2 py-1 text-xs font-bold rounded-full border uppercase tracking-wider">
                                                    {{ log.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                                <div v-if="getFirstCheckIn(log)">
                                                    <p class="font-bold text-gray-800">{{ formatMalaysiaTime(getFirstCheckIn(log), { withSeconds: true }) }}</p>
                                                    <p>{{ formatMalaysiaDate(getFirstCheckIn(log)) }}</p>
                                                </div>
                                                <div v-else class="italic">Not Entered</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                                <div v-if="getLastCheckOut(log)">
                                                    <p class="font-bold text-gray-800">{{ formatMalaysiaTime(getLastCheckOut(log), { withSeconds: true }) }}</p>
                                                    <p>{{ formatMalaysiaDate(getLastCheckOut(log)) }}</p>
                                                </div>
                                                <div v-else-if="log.status === 'Checked In'" class="text-indigo-600 font-black animate-pulse">On-Site</div>
                                                <div v-else class="italic">-</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-bold">
                                                <div class="flex items-center gap-1">
                                                    <span class="px-2 py-0.5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-md">
                                                        {{ log.sessions_count }} {{ log.sessions_count === 1 ? 'session' : 'sessions' }}
                                                    </span>
                                                    <!-- Expand chevron button — only shown for multi-session visits -->
                                                    <button
                                                        v-if="hasSessions(log) && log.sessions.length > 1"
                                                        @click.stop="toggleRow(log.id)"
                                                        class="p-1 rounded hover:bg-indigo-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                                        :title="isExpanded(log.id) ? 'Collapse sessions' : 'Expand sessions'"
                                                    >
                                                        <svg
                                                            :class="['w-3.5 h-3.5 text-indigo-500 transition-transform duration-200', isExpanded(log.id) ? 'rotate-180' : '']"
                                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                        >
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-bold text-indigo-600">
                                                {{ formatDuration(log) }}
                                            </td>
                                        </tr>

                                        <!-- Expanded Session Breakdown Row -->
                                        <tr v-if="hasSessions(log) && log.sessions.length > 1 && isExpanded(log.id)" :key="'expanded-' + log.id">
                                            <td colspan="8" class="px-0 py-0 bg-indigo-50 border-t border-indigo-100">
                                                <div class="px-8 py-4">
                                                    <p class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-3">Session Breakdown</p>
                                                    <div class="flex flex-col gap-2">
                                                        <div
                                                            v-for="(session, index) in log.sessions"
                                                            :key="session.id"
                                                            class="flex items-center gap-4 bg-white rounded-lg border border-indigo-100 px-4 py-3 shadow-sm"
                                                        >
                                                            <!-- Session label -->
                                                            <div class="w-24 shrink-0">
                                                                <span class="px-2 py-0.5 rounded-md text-xs font-black bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                                    {{ getSessionLabel(index, log.sessions.length) }}
                                                                </span>
                                                            </div>

                                                            <!-- Check-in -->
                                                            <div class="flex items-center gap-2 min-w-0">
                                                                <div class="w-2 h-2 rounded-full bg-green-500 shrink-0"></div>
                                                                <div>
                                                                    <p class="text-xs text-gray-400">Check-in</p>
                                                                    <p class="text-sm font-bold text-gray-800">
                                                                        {{ formatMalaysiaTime(session.check_in_time, { withSeconds: true }) }}
                                                                    </p>
                                                                    <p class="text-xs text-gray-400">{{ formatMalaysiaDate(session.check_in_time) }}</p>
                                                                </div>
                                                            </div>

                                                            <!-- Arrow -->
                                                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                            </svg>

                                                            <!-- Check-out / Temporary Leave -->
                                                            <div class="flex items-center gap-2 min-w-0">
                                                                <div
                                                                    :class="[
                                                                        'w-2 h-2 rounded-full shrink-0',
                                                                        session.check_out_time ? 'bg-red-400' : 'bg-yellow-400 animate-pulse'
                                                                    ]"
                                                                ></div>
                                                                <div>
                                                                    <p class="text-xs text-gray-400">
                                                                        {{ session.check_out_time
                                                                            ? (index < log.sessions.length - 1 ? 'Temporary Leave' : 'Check-out')
                                                                            : 'Still On-Site' }}
                                                                    </p>
                                                                    <p v-if="session.check_out_time" class="text-sm font-bold text-gray-800">
                                                                        {{ formatMalaysiaTime(session.check_out_time, { withSeconds: true }) }}
                                                                    </p>
                                                                    <p v-if="session.check_out_time" class="text-xs text-gray-400">{{ formatMalaysiaDate(session.check_out_time) }}</p>
                                                                    <p v-else class="text-sm font-bold text-yellow-600 animate-pulse">On-Site</p>
                                                                </div>
                                                            </div>

                                                            <!-- Session duration -->
                                                            <div class="ml-auto shrink-0">
                                                                <span class="px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-lg text-xs font-bold">
                                                                    {{ formatSessionDuration(session, log.status) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <tr v-if="filteredLogs.length === 0">
                                        <td colspan="8" class="px-6 py-8 text-center text-gray-500 italic">
                                            No visit logs found matching your criteria.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
