<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('admin.visit-logs.index'));
    }
};
import { computed } from 'vue';
import { formatMalaysiaDate, formatMalaysiaTime } from '@/utils/datetime';

const props = defineProps({
    visit: Object,
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

const getStatusDotClass = (status) => {
    switch (status) {
        case 'Checked In':  return 'bg-green-500';
        case 'Checked Out': return 'bg-gray-400';
        case 'Pending':     return 'bg-yellow-500';
        case 'Approved':    return 'bg-blue-500';
        case 'Rejected':    return 'bg-red-500';
        default:            return 'bg-gray-400';
    }
};

const hasSessions = computed(() => {
    return props.visit.sessions && props.visit.sessions.length > 0;
});

const getFirstCheckIn = computed(() => {
    if (hasSessions.value) return props.visit.sessions[0].check_in_time;
    return props.visit.check_in_time ?? props.visit.first_check_in_time ?? null;
});

const getLastCheckOut = computed(() => {
    if (hasSessions.value) return props.visit.sessions[props.visit.sessions.length - 1].check_out_time || null;
    return props.visit.check_out_time ?? props.visit.second_check_out_time ?? null;
});

const formatDuration = (totalMins) => {
    if (!totalMins || totalMins <= 0) return '-';
    const h = Math.floor(totalMins / 60);
    const m = totalMins % 60;
    return h > 0 ? `${h} hr ${m} min` : `${m} min`;
};

const formatSessionDuration = (session) => {
    if (!session.check_in_time) return '-';
    const start = new Date(session.check_in_time);
    const end = session.check_out_time
        ? new Date(session.check_out_time)
        : (props.visit.status === 'Checked In' ? new Date() : null);
    if (!end) return '-';
    const diffMs = end - start;
    if (diffMs < 0) return '-';
    const totalSecs = Math.floor(diffMs / 1000);
    const hours = Math.floor(totalSecs / 3600);
    const mins  = Math.floor((totalSecs % 3600) / 60);
    const secs  = totalSecs % 60;
    const parts = [];
    if (hours > 0) parts.push(`${hours}h`);
    parts.push(`${String(mins).padStart(hours > 0 ? 2 : 1, '0')}m`);
    parts.push(`${String(secs).padStart(2, '0')}s`);
    return parts.join(' ');
};

const getSessionLabel = (index, total) => {
    if (total === 1) return 'Visit Session';
    if (index === 0) return '1st Session';
    if (index === 1) return '2nd Session';
    if (index === 2) return '3rd Session';
    return `${index + 1}th Session`;
};
</script>

<template>
    <Head :title="`Visit Details #${visit.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <button @click="goBack" class="text-gray-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visit Pass {{ visit.id }} Details</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Header Hero section -->
                <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 h-28 relative">
                        <div class="absolute right-6 top-6">
                            <span :class="getStatusClass(visit.status)" class="px-4 py-1.5 text-xs font-black rounded-full border bg-white uppercase tracking-widest shadow-sm">
                                {{ visit.status }}
                            </span>
                        </div>
                    </div>

                    <div class="px-8 pb-8 relative">
                        <!-- Visitor photo positioned over the gradient edge -->
                        <div class="absolute -top-12 left-8 h-24 w-24 rounded-2xl border-4 border-white overflow-hidden bg-gray-100 shadow-md">
                            <img v-if="visit.visitor?.photo" :src="'/storage/' + visit.visitor.photo" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center text-3xl font-black text-indigo-400">
                                {{ visit.visitor?.name?.charAt(0) ?? '?' }}
                            </div>
                        </div>

                        <!-- Name and info area -->
                        <div class="pl-28 pt-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <Link
                                    :href="route('admin.visitors.show', visit.visitor?.id)"
                                    class="group inline-flex items-center gap-2"
                                    title="View visitor profile"
                                >
                                    <h3 class="text-2xl font-black text-gray-900 group-hover:text-indigo-600 transition-colors duration-200 underline-offset-2 group-hover:underline">
                                        {{ visit.visitor?.name ?? 'Unknown Visitor' }}
                                    </h3>
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 transition-colors duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </Link>
                                <p class="text-sm text-gray-500 font-medium mt-0.5">Visitor Profile &amp; Entry Log — <span class="text-indigo-500 font-semibold">click name to view full profile</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Visitor and Visit Information (2 cols) -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Visitor Profile details -->
                        <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-6 space-y-4">
                            <h4 class="text-sm font-black text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Visitor Personal Details
                            </h4>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">IC / Passport Number</span>
                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ visit.visitor?.ic_number || 'N/A' }}</p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Phone Number</span>
                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                        <a v-if="visit.visitor?.phone" :href="`tel:${visit.visitor.phone}`" class="text-indigo-600 hover:underline">
                                            {{ visit.visitor.phone }}
                                        </a>
                                        <span v-else>N/A</span>
                                    </p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Registered Vehicle</span>
                                    <p class="text-sm font-black text-indigo-600 mt-0.5 uppercase tracking-wide">
                                        {{ visit.visitor?.vehicle_number || 'No Vehicle' }}
                                    </p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Registration Date</span>
                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                        {{ formatMalaysiaDate(visit.visitor?.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Visit Log Parameters -->
                        <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-6 space-y-4">
                            <h4 class="text-sm font-black text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Visit parameters
                            </h4>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-3 bg-gray-50 rounded-xl sm:col-span-2">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Purpose of Visit</span>
                                    <p class="text-sm font-medium text-gray-800 mt-0.5">{{ visit.purpose || '-' }}</p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Destination Unit</span>
                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                        <span class="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 rounded-md font-black text-xs border border-indigo-100">
                                            {{ visit.unit_number }}
                                        </span>
                                    </p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Host Resident Name</span>
                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ visit.host_name || '-' }}</p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Assigned Parking Lot</span>
                                    <p class="text-sm font-black text-gray-800 mt-0.5">
                                        {{ visit.parking_lot_number 
                                            ? 'Lot ' + visit.parking_lot_number 
                                            : (visit.visitor?.vehicle_number && visit.visitor?.vehicle_number !== '-' && visit.visitor?.vehicle_number.toLowerCase() !== 'n/a' 
                                                ? 'Outside / Drop Off' 
                                                : 'N/A') 
                                        }}
                                    </p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Approved By</span>
                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ visit.approved_by || '-' }}</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right: Duration and Timeline breakdown -->
                    <div class="space-y-6">
                        
                        <!-- Timing & Duration Summary -->
                        <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-6 space-y-4">
                            <h4 class="text-sm font-black text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Visit Stay Summary
                            </h4>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                    <span class="text-xs text-gray-400 font-bold uppercase">First Arrival</span>
                                    <span class="text-xs font-bold text-gray-800 text-right">
                                        <template v-if="getFirstCheckIn">
                                            <p>{{ formatMalaysiaTime(getFirstCheckIn, { withSeconds: true }) }}</p>
                                            <p class="text-[10px] text-gray-400">{{ formatMalaysiaDate(getFirstCheckIn) }}</p>
                                        </template>
                                        <span v-else class="italic text-gray-400">Not Check In</span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                    <span class="text-xs text-gray-400 font-bold uppercase">Final Exit</span>
                                    <span class="text-xs font-bold text-gray-800 text-right">
                                        <template v-if="getLastCheckOut">
                                            <p>{{ formatMalaysiaTime(getLastCheckOut, { withSeconds: true }) }}</p>
                                            <p class="text-[10px] text-gray-400">{{ formatMalaysiaDate(getLastCheckOut) }}</p>
                                        </template>
                                        <span v-else-if="visit.status === 'Checked In'" class="text-indigo-600 font-black animate-pulse">On-Site</span>
                                        <span v-else class="italic text-gray-400">-</span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                    <span class="text-xs text-gray-400 font-bold uppercase">Total Sessions</span>
                                    <span class="px-2 py-0.5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-md text-xs font-bold">
                                        {{ visit.sessions_count }} {{ visit.sessions_count === 1 ? 'session' : 'sessions' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-xs text-gray-400 font-bold uppercase">Total Duration</span>
                                    <span class="text-sm font-black text-indigo-600">
                                        {{ formatDuration(visit.total_duration_minutes) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline Sessions details -->
                        <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-6 space-y-4">
                            <h4 class="text-sm font-black text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Sessions Timeline
                            </h4>

                            <div v-if="hasSessions" class="flow-root">
                                <ul class="-mb-8">
                                    <li v-for="(session, index) in visit.sessions" :key="session.id">
                                        <div class="relative pb-8">
                                            <!-- Connector line -->
                                            <span v-if="index !== visit.sessions.length - 1" class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            
                                            <div class="relative flex items-start space-x-3">
                                                <!-- Icon/Dot Indicator -->
                                                <div class="relative">
                                                    <span class="h-10 w-10 rounded-full flex items-center justify-center ring-8 ring-white shadow-sm border"
                                                        :class="session.check_out_time ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-green-50 border-green-200 text-green-600 animate-pulse'">
                                                        <svg v-if="!session.check_out_time" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                                        </svg>
                                                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                        </svg>
                                                    </span>
                                                </div>

                                                <div class="min-w-0 flex-1 bg-gray-50/50 hover:bg-gray-50 rounded-xl p-3 border border-gray-100 transition-colors">
                                                    <div class="flex justify-between items-center">
                                                        <p class="text-xs font-black text-gray-700 uppercase">{{ getSessionLabel(index, visit.sessions.length) }}</p>
                                                        <span class="text-[10px] font-bold text-indigo-600 bg-white border border-indigo-100 px-2 py-0.5 rounded-full">
                                                            {{ formatSessionDuration(session) }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-2 space-y-1.5 text-xs">
                                                        <div class="flex items-center gap-1.5 text-gray-600">
                                                            <div class="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></div>
                                                            <span class="font-bold text-gray-800">
                                                                {{ formatMalaysiaTime(session.check_in_time, { withSeconds: true }) }}
                                                            </span>
                                                            <span class="text-gray-400">on {{ formatMalaysiaDate(session.check_in_time) }}</span>
                                                        </div>

                                                        <div class="flex items-center gap-1.5 text-gray-600">
                                                            <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="session.check_out_time ? 'bg-red-500' : 'bg-yellow-500 animate-pulse'"></div>
                                                            <template v-if="session.check_out_time">
                                                                <span class="font-bold text-gray-800">
                                                                    {{ formatMalaysiaTime(session.check_out_time, { withSeconds: true }) }}
                                                                </span>
                                                                <span class="text-gray-400">on {{ formatMalaysiaDate(session.check_out_time) }}</span>
                                                            </template>
                                                            <span v-else class="font-bold text-yellow-600 animate-pulse">On-Site</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Legacy visits with no session table entries -->
                            <div v-else class="space-y-4">
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-black text-gray-700 uppercase">Single Check-In Entry</span>
                                    </div>
                                    <div class="space-y-1 text-xs text-gray-600">
                                        <p>Check-In: <span class="font-bold text-gray-800">{{ formatMalaysiaTime(visit.check_in_time, { withSeconds: true }) }}</span> ({{ formatMalaysiaDate(visit.check_in_time) }})</p>
                                        <p>Check-Out: 
                                            <span v-if="visit.check_out_time" class="font-bold text-gray-800">
                                                {{ formatMalaysiaTime(visit.check_out_time, { withSeconds: true }) }} ({{ formatMalaysiaDate(visit.check_out_time) }})
                                            </span>
                                            <span v-else-if="visit.status === 'Checked In'" class="font-bold text-yellow-600 animate-pulse">On-Site</span>
                                            <span v-else class="italic text-gray-400">-</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
