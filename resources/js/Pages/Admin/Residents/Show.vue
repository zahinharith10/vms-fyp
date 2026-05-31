<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMalaysiaDate, formatMalaysiaTime } from '@/utils/datetime';

const props = defineProps({
    resident:       Object,
    unitResidents:  Array,
    visits:         Array,
});

// ── Visit history helpers ─────────────────────────────────────────────────────
const filterStatus = ref('All');
const searchVisitor = ref('');

const filteredVisits = computed(() => {
    return props.visits.filter(v => {
        const matchStatus = filterStatus.value === 'All' || v.status === filterStatus.value;
        const matchSearch = v.visitor?.name?.toLowerCase().includes(searchVisitor.value.toLowerCase()) ?? true;
        return matchStatus && matchSearch;
    });
});

const getStatusClass = (status) => {
    switch (status) {
        case 'Checked In':  return 'bg-green-100 text-green-800 border-green-200';
        case 'Checked Out': return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'Pending':     return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'Approved':    return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Rejected':    return 'bg-red-100 text-red-800 border-red-200';
        default:            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getFirstCheckIn = (v) => {
    if (v.sessions?.length) return v.sessions[0].check_in_time;
    return v.check_in_time ?? null;
};

const getLastCheckOut = (v) => {
    if (v.sessions?.length) return v.sessions[v.sessions.length - 1].check_out_time ?? null;
    return v.check_out_time ?? null;
};

const formatDuration = (v) => {
    const totalMins = v.total_duration_minutes;
    if (!totalMins || totalMins <= 0) return '-';
    const h = Math.floor(totalMins / 60);
    const m = totalMins % 60;
    return h > 0 ? `${h} hr ${m} min` : `${m} min`;
};
</script>

<template>
    <Head :title="`Resident – ${resident.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.residents.index')" class="text-gray-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Resident Profile</h2>
            </div>
        </template>

        <template #actions>
            <Link :href="route('admin.residents.edit', resident.id)"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Resident
            </Link>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ── Resident Info Card ──────────────────────────────────────── -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <!-- Header strip -->
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 py-5 flex items-center gap-5">
                        <div class="h-16 w-16 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-2xl font-black text-white ring-2 ring-white/40 shadow">
                            {{ resident.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white">{{ resident.name }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-bold text-indigo-100">
                                    {{ resident.house_unit?.formatted_unit ?? 'Unassigned' }}
                                </span>
                                <span class="w-1 h-1 rounded-full bg-indigo-300"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide border"
                                    :class="{
                                        'bg-green-100 text-green-700 border-green-200': resident.type === 'owner',
                                        'bg-blue-100 text-blue-700 border-blue-200':   resident.type === 'tenant',
                                        'bg-purple-100 text-purple-700 border-purple-200': resident.type === 'family',
                                    }">
                                    {{ resident.type }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide border"
                                    :class="resident.status === 'active' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200'">
                                    {{ resident.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Info grid -->
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- IC Number -->
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                            <div class="h-10 w-10 bg-yellow-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-black uppercase tracking-wide">IC Number</p>
                                <p class="text-sm font-semibold text-gray-800">{{ resident.ic_number || 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                            <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-black uppercase tracking-wide">Phone</p>
                                <p class="text-sm font-semibold text-gray-800">{{ resident.phone }}</p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                            <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400 font-black uppercase tracking-wide">Email</p>
                                <p class="text-sm font-semibold text-gray-800 break-all">{{ resident.email }}</p>
                            </div>
                        </div>

                        <!-- House Unit -->
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                            <div class="h-10 w-10 bg-teal-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-black uppercase tracking-wide">House Unit</p>
                                <span v-if="resident.house_unit" class="px-3 py-0.5 text-xs font-black bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg">
                                    {{ resident.house_unit.formatted_unit }}
                                </span>
                                <span v-else class="text-xs text-red-500 font-bold">Unassigned</span>
                            </div>
                        </div>

                        <!-- Auto-approve -->
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                            <div class="h-10 w-10 bg-pink-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-black uppercase tracking-wide">Auto-Approve Deliveries</p>
                                <p class="text-sm font-semibold mt-0.5" :class="resident.auto_approve_deliveries ? 'text-green-600' : 'text-gray-500'">
                                    {{ resident.auto_approve_deliveries ? 'Enabled' : 'Disabled' }}
                                </p>
                            </div>
                        </div>

                        <!-- Total visits -->
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                            <div class="h-10 w-10 bg-orange-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-black uppercase tracking-wide">Total Visits (Unit)</p>
                                <p class="text-2xl font-black text-indigo-600">{{ visits.length }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Co-Residents ────────────────────────────────────────────── -->
                <div v-if="unitResidents.length > 0" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-black text-gray-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
                        </svg>
                        Other Residents in This Unit
                    </h4>
                    <div class="flex flex-wrap gap-3">
                        <Link v-for="r in unitResidents" :key="r.id" :href="route('admin.residents.show', r.id)"
                            class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-colors group">
                            <div class="h-7 w-7 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-black text-indigo-700">
                                {{ r.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 group-hover:text-indigo-700">{{ r.name }}</p>
                                <p class="text-[10px] text-gray-400 capitalize">{{ r.type }}</p>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- ── Visit History ───────────────────────────────────────────── -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <h4 class="text-sm font-black text-gray-700 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Visit History — Unit {{ resident.house_unit?.formatted_unit ?? '-' }}
                        </h4>
                        <div class="flex items-center gap-3">
                            <select v-model="filterStatus" class="border-gray-300 rounded-md shadow-sm text-xs focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="All">All Statuses</option>
                                <option value="Checked In">Checked In</option>
                                <option value="Checked Out">Checked Out</option>
                                <option value="Approved">Approved</option>
                                <option value="Pending">Pending</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </span>
                                <input v-model="searchVisitor" type="text" placeholder="Search visitor..." class="pl-8 text-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-40" />
                            </div>
                            <span class="text-xs text-gray-400 font-medium">{{ filteredVisits.length }} records</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Visitor</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Check-In</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Check-Out</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Sessions</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Duration</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="visit in filteredVisits" :key="visit.id" class="hover:bg-gray-50 transition-colors">
                                    <!-- Visitor -->
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="h-8 w-8 rounded-full overflow-hidden bg-gray-100 shrink-0">
                                                <img v-if="visit.visitor?.photo" :src="'/storage/' + visit.visitor.photo" class="h-full w-full object-cover" />
                                                <div v-else class="h-full w-full flex items-center justify-center text-xs font-black text-gray-400">
                                                    {{ visit.visitor?.name?.charAt(0) ?? '?' }}
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ visit.visitor?.name ?? 'Unknown' }}</p>
                                                <p class="text-xs text-gray-400">{{ visit.visitor?.phone ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Purpose -->
                                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-600">{{ visit.purpose }}</td>

                                    <!-- Status -->
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <span :class="getStatusClass(visit.status)" class="px-2 py-0.5 text-xs font-bold rounded-full border uppercase tracking-wider">
                                            {{ visit.status }}
                                        </span>
                                    </td>

                                    <!-- Check-in -->
                                    <td class="px-5 py-3 whitespace-nowrap text-xs text-gray-500">
                                        <div v-if="getFirstCheckIn(visit)">
                                            <p class="font-bold text-gray-800">{{ formatMalaysiaTime(getFirstCheckIn(visit), { withSeconds: true }) }}</p>
                                            <p>{{ formatMalaysiaDate(getFirstCheckIn(visit)) }}</p>
                                        </div>
                                        <span v-else class="italic">–</span>
                                    </td>

                                    <!-- Check-out -->
                                    <td class="px-5 py-3 whitespace-nowrap text-xs text-gray-500">
                                        <div v-if="getLastCheckOut(visit)">
                                            <p class="font-bold text-gray-800">{{ formatMalaysiaTime(getLastCheckOut(visit), { withSeconds: true }) }}</p>
                                            <p>{{ formatMalaysiaDate(getLastCheckOut(visit)) }}</p>
                                        </div>
                                        <span v-else-if="visit.status === 'Checked In'" class="text-indigo-600 font-black animate-pulse">On-Site</span>
                                        <span v-else class="italic">–</span>
                                    </td>

                                    <!-- Sessions -->
                                    <td class="px-5 py-3 whitespace-nowrap text-xs text-gray-500 font-bold">
                                        <span class="px-2 py-0.5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-md">
                                            {{ visit.sessions_count }} {{ visit.sessions_count === 1 ? 'session' : 'sessions' }}
                                        </span>
                                    </td>

                                    <!-- Duration -->
                                    <td class="px-5 py-3 whitespace-nowrap text-xs font-bold text-indigo-600">
                                        {{ formatDuration(visit) }}
                                    </td>
                                </tr>

                                <tr v-if="filteredVisits.length === 0">
                                    <td colspan="7" class="px-5 py-10 text-center text-gray-400 italic">
                                        No visit records found for this unit.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
