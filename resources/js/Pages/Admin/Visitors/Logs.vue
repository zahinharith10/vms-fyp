<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    logs: Array,
});

const filterStatus = ref('All');
const searchQuery = ref('');

const filteredLogs = computed(() => {
    return props.logs.filter(log => {
        const matchesStatus = filterStatus.value === 'All' || log.status === filterStatus.value;
        const matchesSearch = log.visitor.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             log.unit_number.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesStatus && matchesSearch;
    });
});

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

const formatDuration = (log) => {
    // If it's a delivery log (uses entry_time and exit_time instead of check_in_time)
    if (log.entry_time) {
        const start = new Date(log.entry_time);
        const end = log.exit_time ? new Date(log.exit_time) : new Date();
        const diffMs = end - start;
        if (diffMs < 0) return '-';
        return formatMinutes(Math.floor(diffMs / 60000));
    }

    // For visitors: use sessions[] as authoritative source (supports unlimited temp leaves)
    if (log.sessions && log.sessions.length > 0) {
        let totalMins = 0;
        for (const session of log.sessions) {
            const start = new Date(session.check_in_time);
            const end = session.check_out_time
                ? new Date(session.check_out_time)
                : (log.status === 'Checked In' ? new Date() : start);
            const diffMs = end - start;
            if (diffMs > 0) totalMins += Math.floor(diffMs / 60000);
        }
        return totalMins > 0 ? formatMinutes(totalMins) : '-';
    }

    // Fallback: legacy first/second columns for old records without sessions
    let totalMins = 0;
    let hasData = false;

    // Session 1: First check-in to First check-out
    if (log.first_check_in_time) {
        hasData = true;
        const start1 = new Date(log.first_check_in_time);
        const end1 = log.first_check_out_time 
            ? new Date(log.first_check_out_time) 
            : (log.status === 'Checked In' ? new Date() : start1);
        
        const diffMs1 = end1 - start1;
        if (diffMs1 > 0) {
            totalMins += Math.floor(diffMs1 / 60000);
        }
    }

    // Session 2: Second check-in to Second check-out
    if (log.second_check_in_time) {
        hasData = true;
        const start2 = new Date(log.second_check_in_time);
        const end2 = log.second_check_out_time 
            ? new Date(log.second_check_out_time) 
            : (log.status === 'Checked In' ? new Date() : start2);
        
        const diffMs2 = end2 - start2;
        if (diffMs2 > 0) {
            totalMins += Math.floor(diffMs2 / 60000);
        }
    }

    // Fallback to legacy fields if no multi-entry fields exist
    if (!hasData && log.check_in_time) {
        const start = new Date(log.check_in_time);
        const end = log.check_out_time ? new Date(log.check_out_time) : new Date();
        const diffMs = end - start;
        if (diffMs > 0) {
            totalMins = Math.floor(diffMs / 60000);
            hasData = true;
        }
    }

    if (!hasData) return '-';
    return formatMinutes(totalMins);
};

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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visit Monitoring</h2>
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
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Duration</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-gray-50 transition-colors">
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
                                            <div v-if="log.check_in_time || log.entry_time">
                                                <p class="font-bold text-gray-800">{{ new Date(log.check_in_time || log.entry_time).toLocaleTimeString() }}</p>
                                                <p>{{ new Date(log.check_in_time || log.entry_time).toLocaleDateString() }}</p>
                                            </div>
                                            <div v-else class="italic">Not Entered</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                            <div v-if="log.check_out_time || log.exit_time">
                                                <p class="font-bold text-gray-800">{{ new Date(log.check_out_time || log.exit_time).toLocaleTimeString() }}</p>
                                                <p>{{ new Date(log.check_out_time || log.exit_time).toLocaleDateString() }}</p>
                                            </div>
                                            <div v-else-if="log.status === 'Checked In'" class="text-indigo-600 font-black animate-pulse">On-Site</div>
                                            <div v-else class="italic">-</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-bold text-indigo-600">
                                            {{ formatDuration(log) }}
                                        </td>
                                    </tr>
                                    <tr v-if="filteredLogs.length === 0">
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">
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
