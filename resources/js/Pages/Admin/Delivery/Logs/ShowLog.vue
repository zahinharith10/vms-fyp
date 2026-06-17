<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatMalaysiaDate, formatMalaysiaTime, formatMalaysiaDateTime } from '@/utils/datetime';

const props = defineProps({
    log: Object,
});

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('admin.delivery.logs.index'));
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'On-Site':
        case 'Entered':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'Completed':
            return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'Pending':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'Approved':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Rejected':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getStatusDotClass = (status) => {
    switch (status) {
        case 'On-Site':
        case 'Entered':
            return 'bg-green-500';
        case 'Completed':
            return 'bg-gray-400';
        case 'Pending':
            return 'bg-yellow-500';
        case 'Approved':
            return 'bg-blue-500';
        case 'Rejected':
            return 'bg-red-500';
        default:
            return 'bg-gray-400';
    }
};

const getVisualStatus = computed(() => {
    if (props.log.exit_time) return 'Completed';
    if (props.log.entry_time) return 'On-Site';
    return props.log.status || 'Pending';
});

const formatDuration = (totalMins) => {
    if (!totalMins || totalMins <= 0) return '-';
    const h = Math.floor(totalMins / 60);
    const m = totalMins % 60;
    return h > 0 ? `${h} hr ${m} min` : `${m} min`;
};
</script>

<template>
    <Head :title="`Delivery Pass Details #${log.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <button @click="goBack" class="text-gray-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Delivery Pass Details #{{ log.id }}</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Header Hero section -->
                <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                    <div class="h-32 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-600"></div>

                    <div class="px-8 pb-8 relative">
                        <!-- Courier photo positioned over the gradient edge -->
                        <div class="absolute -top-12 left-8 h-24 w-24 rounded-2xl border-4 border-white overflow-hidden bg-gray-100 shadow-md">
                            <img v-if="log.personnel?.photo" :src="'/storage/' + log.personnel.photo" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center text-3xl font-black text-indigo-400">
                                {{ log.personnel?.name?.charAt(0) ?? '?' }}
                            </div>
                        </div>

                        <!-- Name and info area -->
                        <div class="pl-28 pt-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <Link
                                    :href="route('admin.delivery.personnel.show', log.personnel?.id)"
                                    class="group inline-flex items-center gap-2"
                                    title="View courier profile"
                                >
                                    <h3 class="text-2xl font-black text-gray-900 group-hover:text-indigo-600 transition-colors duration-200 underline-offset-2 group-hover:underline">
                                        {{ log.personnel?.name ?? 'Unknown Courier' }}
                                    </h3>
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 transition-colors duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </Link>
                                <p class="text-sm text-gray-500 font-medium mt-0.5">
                                    Delivery Personnel Profile &amp; Entry Log — <span class="text-indigo-500 font-semibold">click name to view full profile</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Courier and Delivery Information (2 cols) -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Core Status Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <!-- Status -->
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pass Status</span>
                                <div class="mt-2 flex items-center gap-1.5">
                                    <span :class="getStatusDotClass(getVisualStatus)" class="h-2.5 w-2.5 rounded-full shrink-0"></span>
                                    <span :class="getStatusClass(getVisualStatus)" class="px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">
                                        {{ getVisualStatus }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Entry Time -->
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Entry Time</span>
                                <div class="mt-2 text-xs font-bold text-gray-850">
                                    <p v-if="log.entry_time">{{ formatMalaysiaTime(log.entry_time) }}</p>
                                    <p v-if="log.entry_time" class="text-[10px] text-gray-400 font-normal mt-0.5">{{ formatMalaysiaDate(log.entry_time) }}</p>
                                    <span v-else class="text-gray-400 font-normal italic">Not Entered</span>
                                </div>
                            </div>

                            <!-- Exit Time -->
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Exit Time</span>
                                <div class="mt-2 text-xs font-bold text-gray-850">
                                    <p v-if="log.exit_time">{{ formatMalaysiaTime(log.exit_time) }}</p>
                                    <p v-if="log.exit_time" class="text-[10px] text-gray-400 font-normal mt-0.5">{{ formatMalaysiaDate(log.exit_time) }}</p>
                                    <span v-else-if="log.entry_time" class="text-green-600 font-black animate-pulse uppercase tracking-wider" style="font-size: 10px">On-Site</span>
                                    <span v-else class="text-gray-400 font-normal italic">-</span>
                                </div>
                            </div>

                            <!-- Duration -->
                            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Duration</span>
                                <div class="mt-2 text-sm font-black text-indigo-600">
                                    {{ formatDuration(log.total_duration_minutes) }}
                                </div>
                            </div>
                        </div>

                        <!-- Courier Profile Information Card -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Courier Profile Details</h4>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Full Name</label>
                                    <span class="font-bold text-gray-900 mt-1 block">{{ log.personnel?.name || 'N/A' }}</span>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Company</label>
                                    <span class="inline-block px-2.5 py-0.5 bg-blue-100 text-blue-800 rounded-md text-xs font-bold mt-1">
                                        {{ log.personnel?.company || 'N/A' }}
                                    </span>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Phone Number</label>
                                    <span class="font-bold text-gray-800 mt-1 block">{{ log.personnel?.phone || 'N/A' }}</span>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">IC / Passport Number</label>
                                    <span class="font-bold text-gray-800 mt-1 block">{{ log.personnel?.ic_number || 'N/A' }}</span>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Vehicle Type</label>
                                    <span class="font-bold text-gray-800 mt-1 block">{{ log.personnel?.vehicle_type || 'N/A' }}</span>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Vehicle Plate Number</label>
                                    <span class="font-mono font-bold text-gray-900 uppercase tracking-wider mt-1 block">{{ log.personnel?.vehicle_number || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Request details -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Delivery Pass Details</h4>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Destination Unit</label>
                                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-lg font-black text-sm border border-indigo-100 inline-block mt-1">
                                        {{ log.destination }}
                                    </span>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Resident Host</label>
                                    <span class="font-bold text-gray-900 mt-1 block">👤 {{ log.host_name || 'N/A' }}</span>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Approved / Checked In By</label>
                                    <span class="font-bold text-gray-800 mt-1 block">👤 {{ log.approved_by || 'N/A' }}</span>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Pass Created Date</label>
                                    <span class="font-medium text-gray-800 mt-1 block">{{ formatMalaysiaDateTime(log.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Check In / Check Out Session Timeline -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Security Entry Timeline</h4>
                            </div>
                            
                            <div class="p-6">
                                <div v-if="log.entry_time" class="relative pl-6 border-l-2 border-indigo-200 space-y-6">
                                    <!-- Event 1: Checked In -->
                                    <div class="relative">
                                        <!-- Dot icon absolute -->
                                        <div class="absolute -left-[31px] top-0.5 bg-green-500 rounded-full h-4.5 w-4.5 border-4 border-white flex items-center justify-center shadow-sm"></div>
                                        <div>
                                            <span class="text-xs font-black text-green-600 uppercase tracking-wide">Check-In Scan</span>
                                            <p class="text-sm font-bold text-gray-800 mt-0.5">{{ formatMalaysiaTime(log.entry_time, { withSeconds: true }) }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ formatMalaysiaDate(log.entry_time) }}</p>
                                            <p class="text-xs text-gray-400 mt-1">Courier scanned QR code and entered the gate.</p>
                                        </div>
                                    </div>

                                    <!-- Event 2: Checked Out -->
                                    <div class="relative">
                                        <!-- Dot icon absolute -->
                                        <div :class="[log.exit_time ? 'bg-indigo-600' : 'bg-gray-300 animate-pulse']" class="absolute -left-[31px] top-0.5 rounded-full h-4.5 w-4.5 border-4 border-white flex items-center justify-center shadow-sm"></div>
                                        <div>
                                            <span :class="[log.exit_time ? 'text-indigo-600' : 'text-gray-500']" class="text-xs font-black uppercase tracking-wide">
                                                {{ log.exit_time ? 'Check-Out Scan' : 'Active (Still On-Site)' }}
                                            </span>
                                            <div v-if="log.exit_time">
                                                <p class="text-sm font-bold text-gray-800 mt-0.5">{{ formatMalaysiaTime(log.exit_time, { withSeconds: true }) }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">{{ formatMalaysiaDate(log.exit_time) }}</p>
                                                <p class="text-xs text-gray-400 mt-1">Courier checked out and exited the residency premises.</p>
                                            </div>
                                            <div v-else>
                                                <p class="text-xs text-gray-400 mt-1 italic">Waiting for exit gate check-out scan...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-8 text-gray-500">
                                    <span class="text-3xl block mb-2">🔏</span>
                                    <p class="text-sm font-semibold">Digital Pass Registered</p>
                                    <p class="text-xs text-gray-400 mt-1">This pass has not been scanned at the security entrance gate yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
