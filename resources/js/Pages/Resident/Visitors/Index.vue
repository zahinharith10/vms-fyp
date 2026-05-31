<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { formatMalaysiaDate, formatMalaysiaDateTime } from '@/utils/datetime';

const props = defineProps({
    visits: Array,
    deliveries: Array,
});

const pendingVisits = computed(() => props.visits.filter(v => v.status === 'Pending'));
const historyVisits = computed(() => props.visits.filter(v => v.status !== 'Pending'));

const pendingDeliveries = computed(() => props.deliveries.filter(log => log.status === 'Pending'));
const historyDeliveries = computed(() => props.deliveries.filter(log => log.status !== 'Pending'));

const totalPending = computed(() => pendingVisits.value.length + pendingDeliveries.value.length);
const activeTab = ref(totalPending.value > 0 ? 'pending' : 'visitors');
const isDetailsModalOpen = ref(false);
const selectedVisit = ref(null);

const isShareModalOpen = ref(false);
const selectedShareVisit = ref(null);
const isCopied = ref(false);

const openDetailsModal = (visit) => {
    selectedVisit.value = visit;
    isDetailsModalOpen.value = true;
};

const closeModals = () => {
    isDetailsModalOpen.value = false;
    setTimeout(() => {
        selectedVisit.value = null;
    }, 200);
};

const openShareModal = (visit) => {
    selectedShareVisit.value = visit;
    isShareModalOpen.value = true;
    isCopied.value = false;
};

const closeShareModal = () => {
    isShareModalOpen.value = false;
    setTimeout(() => {
        selectedShareVisit.value = null;
    }, 200);
};

const shareUrl = computed(() => {
    if (!selectedShareVisit.value) return '';
    return `${window.location.origin}/pass/${selectedShareVisit.value.qr_code_token}`;
});

const whatsappUrl = computed(() => {
    if (!selectedShareVisit.value) return '';
    const message = `Hi! Here is your Sri Ayu Residency Pre-Approved Digital Guest Pass. Please click the link below to activate your QR code before arriving:\n\n${shareUrl.value}`;
    return `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;
});

const copyToClipboard = () => {
    navigator.clipboard.writeText(shareUrl.value);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
};

const formatDuration = (visit) => {
    if (!visit) return null;
    
    // If it's a delivery log (uses entry_time and exit_time instead of check_in_time)
    if (visit.entry_time) {
        const start = new Date(visit.entry_time);
        const end = visit.exit_time ? new Date(visit.exit_time) : new Date();
        const diffMs = end - start;
        if (diffMs < 0) return null;
        return formatMinutes(Math.floor(diffMs / 60000));
    }

    // For visitors: use sessions[] as authoritative source (supports unlimited temp leaves)
    if (visit.sessions && visit.sessions.length > 0) {
        let totalMins = 0;
        for (const session of visit.sessions) {
            const start = new Date(session.check_in_time);
            const end = session.check_out_time
                ? new Date(session.check_out_time)
                : (visit.status === 'Checked In' ? new Date() : start);
            const diffMs = end - start;
            if (diffMs > 0) totalMins += Math.floor(diffMs / 60000);
        }
        return totalMins > 0 ? formatMinutes(totalMins) : null;
    }

    let totalMins = 0;
    let hasData = false;

    // Session 1: First check-in to First check-out
    if (visit.first_check_in_time) {
        hasData = true;
        const start1 = new Date(visit.first_check_in_time);
        const end1 = visit.first_check_out_time 
            ? new Date(visit.first_check_out_time) 
            : (visit.status === 'Checked In' ? new Date() : start1);
        
        const diffMs1 = end1 - start1;
        if (diffMs1 > 0) {
            totalMins += Math.floor(diffMs1 / 60000);
        }
    }

    // Session 2: Second check-in to Second check-out
    if (visit.second_check_in_time) {
        hasData = true;
        const start2 = new Date(visit.second_check_in_time);
        const end2 = visit.second_check_out_time 
            ? new Date(visit.second_check_out_time) 
            : (visit.status === 'Checked In' ? new Date() : start2);
        
        const diffMs2 = end2 - start2;
        if (diffMs2 > 0) {
            totalMins += Math.floor(diffMs2 / 60000);
        }
    }

    // Fallback to legacy fields if no multi-entry fields exist
    if (!hasData && visit.check_in_time) {
        const start = new Date(visit.check_in_time);
        const end = visit.check_out_time ? new Date(visit.check_out_time) : new Date();
        const diffMs = end - start;
        if (diffMs > 0) {
            totalMins = Math.floor(diffMs / 60000);
            hasData = true;
        }
    }

    if (!hasData) return null;
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

// --- Real-Time WebSocket Listeners ---
const newVisitToast = ref(null);  // { visitor_name, purpose }
const echoChannels = [];

onMounted(() => {
    // Deep-link active tab via query parameters
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab');
    if (tabParam && ['pending', 'visitors', 'deliveries'].includes(tabParam)) {
        activeTab.value = tabParam;
    }

    if (!window.Echo) return;
    const page = usePage();
    const resident = page.props.auth?.resident;
    
    // 1) Listen for NEW visit requests on this unit's channel
    if (resident?.unit_number) {
        const safeUnit = resident.unit_number.replace(/[/ \\]/g, '-');
        const unitCh = window.Echo.channel(`unit.${safeUnit}`)
            .listen('.visit.requested', (e) => {
                newVisitToast.value = e;
                // Auto-dismiss toast after 6 seconds
                setTimeout(() => { newVisitToast.value = null; }, 6000);
                // Reload visits list in real-time
                router.reload({ only: ['visits'], preserveState: true, preserveScroll: true });
            });
        echoChannels.push(`unit.${safeUnit}`);
    }

    // 2) Listen for status changes on each existing visit's channel
    props.visits.forEach((visit) => {
        const ch = window.Echo.channel(`visit.${visit.id}`)
            .listen('.visit.status.updated', () => {
                router.reload({ only: ['visits'], preserveState: true, preserveScroll: true });
            });
        echoChannels.push(`visit.${visit.id}`);
    });
});

onUnmounted(() => {
    echoChannels.forEach((ch) => window.Echo.leaveChannel(ch));
});
</script>

<template>
    <Head title="My Visitors" />

    <ResidentAuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Visitors</h2>
                <Link :href="route('resident.visitors.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest px-4 py-2.5 rounded-xl shadow-lg transition">
                    Pre-Register Guest
                </Link>
            </div>
        </template>

        <!-- Real-time Toast Notification -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-y-[-20px]" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-[-20px]">
            <div v-if="newVisitToast" class="fixed top-5 right-5 z-50 bg-indigo-600 text-white rounded-2xl shadow-2xl px-5 py-4 flex items-start gap-3 max-w-xs">
                <span class="text-2xl">🔔</span>
                <div>
                    <p class="font-black text-sm">New Visit Request!</p>
                    <p class="text-xs text-indigo-200 mt-0.5"><span class="font-bold text-white">{{ newVisitToast.visitor_name }}</span> wants to visit.</p>
                    <p class="text-xs text-indigo-300 mt-0.5">Purpose: {{ newVisitToast.purpose }}</p>
                </div>
                <button @click="newVisitToast = null" class="ml-auto text-indigo-300 hover:text-white text-lg leading-none">×</button>
            </div>
        </Transition>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Tab Switching -->
                <div class="flex space-x-4 mb-6">
                    <button 
                        @click="activeTab = 'pending'"
                        class="px-6 py-2 rounded-xl font-black uppercase tracking-widest text-xs transition-all"
                        :class="activeTab === 'pending' ? 'bg-yellow-500 text-white shadow-lg' : 'bg-white text-gray-400 hover:text-gray-600'"

                    >
                        Pending ({{ totalPending }})
                    </button>
                    <button 
                        @click="activeTab = 'visitors'"
                        class="px-6 py-2 rounded-xl font-black uppercase tracking-widest text-xs transition-all"
                        :class="activeTab === 'visitors' ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-gray-400 hover:text-gray-600'"
                    >
                        Visitors ({{ historyVisits.length }})
                    </button>
                    <button 
                        @click="activeTab = 'deliveries'"
                        class="px-6 py-2 rounded-xl font-black uppercase tracking-widest text-xs transition-all"
                        :class="activeTab === 'deliveries' ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-gray-400 hover:text-gray-600'"
                    >
                        Deliveries ({{ historyDeliveries.length }})
                    </button>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <!-- Visitors Table -->
                        <div v-if="activeTab === 'visitors' || (activeTab === 'pending' && pendingVisits.length > 0)" class="mb-8">
                            <h3 v-if="activeTab === 'pending'" class="font-bold text-gray-700 mb-4 uppercase text-sm tracking-wider">Pending Visitors</h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Visitor</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Person to Visit</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Checked in → Checked out</th>
                                        <th class="px-6 py-3 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="visit in (activeTab === 'pending' ? pendingVisits : historyVisits)" :key="visit.id">
                                    <td class="px-6 py-4 whitespace-no-wrap">
                                        <div class="flex items-center">
                                            <!-- Visitor Photo -->
                                            <div class="h-10 w-10 flex-shrink-0 mr-3">
                                                <img 
                                                    v-if="visit.visitor?.photo" 
                                                    :src="`/storage/${visit.visitor.photo}`" 
                                                    alt="Visitor photo"
                                                    class="h-10 w-10 rounded-full object-cover border-2 border-gray-200"
                                                />
                                                <div 
                                                    v-else
                                                    class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold"
                                                >
                                                    {{ visit.visitor?.name ? visit.visitor.name.charAt(0).toUpperCase() : '?' }}
                                                </div>
                                            </div>
                                            <!-- Visitor Info -->
                                            <div @click="openDetailsModal(visit)" class="cursor-pointer hover:bg-gray-50 p-1 rounded-lg transition-colors group">
                                                <div class="text-sm font-bold tracking-tight" :class="visit.visitor?.name && visit.visitor?.photo ? 'text-gray-900 group-hover:text-indigo-600' : 'text-orange-500 italic'">
                                                    {{ visit.visitor?.name && visit.visitor?.photo ? visit.visitor.name : 'Incomplete Profile' }}
                                                </div>
                                                <div class="text-xs text-gray-400 font-medium tracking-tight">{{ visit.visitor?.phone || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm text-gray-700">
                                        {{ visit.purpose }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm">
                                        <span v-if="visit.host_name" class="font-semibold text-indigo-700">👤 {{ visit.host_name }}</span>
                                        <span v-else class="text-gray-400 italic">—</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': visit.status === 'Pending',
                                                'bg-blue-100 text-blue-800': visit.status === 'Approved',
                                                'bg-red-100 text-red-800': visit.status === 'Rejected',
                                                'bg-green-100 text-green-800': visit.status === 'Checked In',
                                                'bg-gray-100 text-gray-800': visit.status === 'Checked Out'
                                            }">
                                            {{ visit.status }}
                                        </span>
                                        <div v-if="visit.approved_by" class="text-[10px] text-gray-500 dark:text-gray-400 font-bold mt-1">
                                            by {{ visit.approved_by }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 space-y-1">
                                        <div v-if="visit.check_in_time" class="flex items-center gap-1">
                                            <span class="font-bold text-green-500 uppercase tracking-wider" style="font-size:9px">Checked in</span>
                                            <span class="font-medium text-green-700">{{ formatMalaysiaDateTime(visit.check_in_time) }}</span>
                                        </div>
                                        <div v-if="visit.check_out_time" class="flex items-center gap-1">
                                            <span class="font-bold text-gray-400 uppercase tracking-wider" style="font-size:9px">Checked out</span>
                                            <span class="font-medium text-gray-600">{{ formatMalaysiaDateTime(visit.check_out_time) }}</span>
                                        </div>
                                        <div v-if="!visit.check_in_time && !visit.check_out_time" class="text-gray-400 italic">—</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right text-sm font-medium">
                                        <!-- View QR (Only for Approved or Checked In) -->
                                        <Link 
                                            v-if="['Approved', 'Checked In'].includes(visit.status)"
                                            :href="route('resident.visitors.qr', visit.id)" 
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded mr-2"
                                        >
                                            View QR
                                        </Link>

                                        <!-- Share Pass Option for Pre-Registered Guests -->
                                        <button 
                                            v-if="visit.status === 'Approved' && visit.qr_code_token && visit.qr_code_token.startsWith('PRE_REG_')"
                                            @click="openShareModal(visit)"
                                            class="text-teal-600 hover:text-teal-900 bg-teal-50 px-3 py-1 rounded font-bold transition mr-2"
                                        >
                                            Share Pass
                                        </button>

                                        <!-- Approval Buttons (Only for Pending) -->
                                        <template v-if="visit.status === 'Pending'">
                                            <Link 
                                                :href="route('resident.visitors.approve', visit.id)" 
                                                method="post" 
                                                as="button"
                                                class="text-green-600 hover:text-green-900 bg-green-50 px-3 py-1 rounded mr-2 font-bold"
                                            >
                                                Approve
                                            </Link>
                                            <Link 
                                                :href="route('resident.visitors.reject', visit.id)" 
                                                method="post" 
                                                as="button"
                                                class="text-orange-600 hover:text-orange-900 bg-orange-50 px-3 py-1 rounded mr-2 font-bold"
                                            >
                                                Reject
                                            </Link>
                                        </template>

                                    </td>
                                </tr>
                                <tr v-if="(activeTab === 'pending' ? pendingVisits : historyVisits).length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No {{ activeTab === 'pending' ? 'pending requests' : 'visitors' }} found.</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>

                        <!-- Deliveries Table -->
                        <div v-if="activeTab === 'deliveries' || (activeTab === 'pending' && pendingDeliveries.length > 0)">
                            <h3 v-if="activeTab === 'pending'" class="font-bold text-gray-700 mb-4 uppercase text-sm tracking-wider border-t pt-6">Pending Deliveries</h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Personnel</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Person to Visit</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Checked in → Checked out</th>
                                        <th class="px-6 py-3 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="log in (activeTab === 'pending' ? pendingDeliveries : historyDeliveries)" :key="log.id">
                                    <td class="px-6 py-4 whitespace-no-wrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0 mr-3">
                                                <img 
                                                    v-if="log.personnel?.photo" 
                                                    :src="`/storage/${log.personnel.photo}`" 
                                                    class="h-10 w-10 rounded-full object-cover border-2 border-gray-200"
                                                />
                                                <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold">
                                                    {{ log.personnel?.name?.charAt(0).toUpperCase() }}
                                                </div>
                                            </div>
                                            <div @click="openDetailsModal(log)" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 p-1 rounded-lg transition-colors group">
                                                <div class="text-sm font-bold text-gray-900 dark:text-gray-150 group-hover:text-indigo-600">{{ log.personnel?.name }}</div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500 font-medium">{{ log.personnel?.vehicle_number }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm text-gray-700">
                                        {{ log.personnel?.company }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm">
                                        <span v-if="log.host_name" class="font-semibold text-indigo-700">👤 {{ log.host_name }}</span>
                                        <span v-else class="text-gray-400 italic">—</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': log.status === 'Pending',
                                                'bg-blue-100 text-blue-800': log.status === 'Approved',
                                                'bg-red-100 text-red-800': log.status === 'Rejected',
                                                'bg-green-100 text-green-800': log.status === 'Checked In' || log.entry_time,
                                                'bg-gray-100 text-gray-800': log.exit_time
                                            }">
                                            {{ log.exit_time ? 'Completed' : (log.entry_time ? 'In Progress' : log.status) }}
                                        </span>
                                        <div v-if="log.approved_by" class="text-[10px] text-gray-500 dark:text-gray-400 font-bold mt-1">
                                            by {{ log.approved_by }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 space-y-1">
                                        <div v-if="log.entry_time" class="flex items-center gap-1">
                                            <span class="font-bold text-green-500 uppercase tracking-wider" style="font-size:9px">Checked in</span>
                                            <span class="font-medium text-green-700">{{ formatMalaysiaDateTime(log.entry_time) }}</span>
                                        </div>
                                        <div v-if="log.exit_time" class="flex items-center gap-1">
                                            <span class="font-bold text-gray-400 uppercase tracking-wider" style="font-size:9px">Checked out</span>
                                            <span class="font-medium text-gray-600">{{ formatMalaysiaDateTime(log.exit_time) }}</span>
                                        </div>
                                        <div v-if="!log.entry_time && !log.exit_time" class="text-gray-400 italic">—</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right text-sm font-medium">
                                        <!-- Approval Buttons (Only for Pending) -->
                                        <template v-if="log.status === 'Pending'">
                                            <Link 
                                                :href="route('resident.deliveries.approve', log.id)" 
                                                method="post" 
                                                as="button"
                                                class="text-green-600 hover:text-green-900 bg-green-50 px-3 py-1 rounded mr-2 font-bold"
                                            >
                                                Approve
                                            </Link>
                                            <Link 
                                                :href="route('resident.deliveries.reject', log.id)" 
                                                method="post" 
                                                as="button"
                                                class="text-orange-600 hover:text-orange-900 bg-orange-50 px-3 py-1 rounded font-bold"
                                            >
                                                Reject
                                            </Link>
                                        </template>
                                        <span v-else class="text-gray-400 italic text-xs">No actions</span>
                                    </td>
                                </tr>
                                <tr v-if="(activeTab === 'pending' ? pendingDeliveries : historyDeliveries).length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No {{ activeTab === 'pending' ? 'pending delivery requests' : 'delivery logs' }} found.</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>

                        <!-- Empty State for Pending Tab when both are empty -->
                        <div v-if="activeTab === 'pending' && totalPending === 0" class="text-center py-8 text-gray-500">
                            No pending requests found.
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Visitor Details Modal -->
        <div v-if="isDetailsModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="closeModals" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-black text-white uppercase tracking-widest" id="modal-title">Visitor Information</h3>
                        <button @click="closeModals" class="text-white hover:text-indigo-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="p-8">
                        <div class="flex flex-col items-center mb-8">
                            <div class="relative">
                                <img 
                                    v-if="selectedVisit?.visitor?.photo || selectedVisit?.personnel?.photo" 
                                    :src="`/storage/${selectedVisit.visitor?.photo || selectedVisit.personnel?.photo}`" 
                                    class="h-32 w-32 rounded-3xl object-cover shadow-xl border-4 border-white"
                                />
                                <div v-else class="h-32 w-32 rounded-3xl bg-indigo-50 flex items-center justify-center text-indigo-300 text-4xl font-black">
                                    {{ (selectedVisit?.visitor?.name || selectedVisit?.personnel?.name || '?').charAt(0).toUpperCase() }}
                                </div>
                                <div v-if="selectedVisit?.visitor?.photo || selectedVisit?.personnel?.photo" class="absolute -bottom-2 -right-2 bg-indigo-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg">
                                    Verified
                                </div>
                                <div v-else class="absolute -bottom-2 -right-2 bg-orange-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg">
                                    Pending Info
                                </div>
                            </div>
                            <h4 class="mt-4 text-xl font-black text-gray-900 tracking-tight">{{ selectedVisit?.visitor?.name || selectedVisit?.personnel?.name || 'Incomplete Profile' }}</h4>
                            <p class="text-indigo-500 font-bold text-sm tracking-widest uppercase">{{ selectedVisit?.status }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-6 bg-gray-50 p-6 rounded-3xl border border-gray-100">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Phone Number</p>
                                <p class="text-sm font-bold text-gray-800">{{ selectedVisit?.visitor?.phone || selectedVisit?.personnel?.phone }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Vehicle Plate</p>
                                <p class="text-sm font-bold text-gray-800 uppercase">{{ selectedVisit?.visitor?.vehicle_number || selectedVisit?.personnel?.vehicle_number || 'None' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Registration Date</p>
                                <p class="text-sm font-bold text-gray-800">{{ selectedVisit ? formatMalaysiaDate(selectedVisit.created_at) : '-' }}</p>
                            </div>
                            <div v-if="selectedVisit?.parking_lot_number">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Parking Lot</p>
                                <p class="text-sm font-bold text-indigo-600 uppercase">🅿️ Lot {{ selectedVisit.parking_lot_number }}</p>
                            </div>

                            <div v-if="selectedVisit?.check_in_time || selectedVisit?.entry_time" class="col-span-2 border-t border-gray-200/50 pt-4 mt-2 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">First Checked in</p>
                                    <p class="text-xs font-bold text-gray-800">
                                        {{ formatMalaysiaDateTime(selectedVisit.check_in_time || selectedVisit.entry_time, { year: 'numeric' }) }}
                                    </p>
                                </div>
                                <div v-if="selectedVisit?.check_out_time || selectedVisit?.exit_time">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Last Checked out</p>
                                    <p class="text-xs font-bold text-gray-800">
                                        {{ formatMalaysiaDateTime(selectedVisit.check_out_time || selectedVisit.exit_time, { year: 'numeric' }) }}
                                    </p>
                                </div>
                                <div v-else class="flex items-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-green-100 text-green-800 animate-pulse uppercase tracking-wider">
                                        Active On-Site
                                    </span>
                                </div>
                                <div class="col-span-2 border-t border-gray-100/50 pt-4 mt-2 grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Total Sessions</p>
                                        <p class="text-sm font-black text-gray-800">
                                            🔄 {{ selectedVisit?.sessions && selectedVisit.sessions.length > 0 ? selectedVisit.sessions.length : 1 }} {{ (selectedVisit?.sessions && selectedVisit.sessions.length > 0 ? selectedVisit.sessions.length : 1) === 1 ? 'session' : 'sessions' }}
                                        </p>
                                    </div>
                                    <div v-if="formatDuration(selectedVisit)">
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Total Duration</p>
                                        <p class="text-sm font-black text-indigo-650">
                                            ⏱️ {{ formatDuration(selectedVisit) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 p-4 border-l-4 border-indigo-500 bg-indigo-50 rounded-r-2xl">
                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Purpose of Visit / Company</p>
                            <p class="text-sm font-medium text-gray-700 italic">
                                "{{ selectedVisit?.purpose || ('Delivery Personnel (' + (selectedVisit?.personnel?.company || 'Unknown') + ')') }}"
                            </p>
                        </div>

                        <div v-if="selectedVisit?.host_name" class="mt-4 p-4 border-l-4 border-emerald-500 bg-emerald-50 rounded-r-2xl">
                            <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Person to Visit</p>
                            <p class="text-sm font-bold text-emerald-800">👤 {{ selectedVisit.host_name }}</p>
                        </div>

                        <div v-if="selectedVisit?.approved_by" class="mt-4 p-4 border-l-4 border-indigo-500 bg-indigo-50/50 rounded-r-2xl">
                            <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">
                                {{ selectedVisit.status === 'Rejected' ? 'Rejected By' : 'Accepted/Approved By' }}
                            </p>
                            <p class="text-sm font-bold text-indigo-900">👤 {{ selectedVisit.approved_by }}</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-8 py-6 flex justify-end">
                        <button @click="closeModals" class="px-6 py-2 bg-gray-800 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-gray-700 transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share Guest Pass Modal -->
        <div v-if="isShareModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="share-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="closeShareModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-black text-white uppercase tracking-widest" id="share-modal-title">Share Guest Pass</h3>
                        <button @click="closeShareModal" class="text-white hover:text-indigo-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="p-8 space-y-6">
                        <div class="text-center">
                            <span class="text-5xl block mb-2">🎫</span>
                            <h4 class="text-xl font-black text-gray-900 tracking-tight">Pre-Approved Guest Pass</h4>
                            <p class="text-xs text-indigo-500 font-bold uppercase tracking-wider mt-1">Ready to share with {{ selectedShareVisit?.visitor?.name }}</p>
                        </div>

                        <!-- Details card -->
                        <div class="bg-gray-50 p-5 rounded-2xl border border-gray-200 text-sm space-y-2">
                            <div class="flex justify-between">
                                <span class="text-xs font-bold text-gray-400 uppercase">Guest</span>
                                <span class="font-bold text-gray-800">{{ selectedShareVisit?.visitor?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs font-bold text-gray-400 uppercase">Phone</span>
                                <span class="font-bold text-gray-800">{{ selectedShareVisit?.visitor?.phone }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs font-bold text-gray-400 uppercase">Purpose</span>
                                <span class="font-bold text-gray-800">{{ selectedShareVisit?.purpose }}</span>
                            </div>
                        </div>

                        <!-- Link input and copy -->
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest">Shareable Pass Link</label>
                            <div class="flex gap-2">
                                <input 
                                    type="text" 
                                    readonly 
                                    :value="shareUrl" 
                                    class="flex-1 bg-gray-100 border border-gray-200 rounded-xl px-4 py-2.5 font-bold text-sm text-gray-600 focus:outline-none"
                                />
                                <button 
                                    @click="copyToClipboard"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest px-4 py-2.5 rounded-xl transition"
                                >
                                    {{ isCopied ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <!-- WhatsApp Action -->
                        <a 
                            :href="whatsappUrl" 
                            target="_blank"
                            class="w-full flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-black text-sm uppercase tracking-wider py-4 rounded-2xl shadow-lg shadow-emerald-100 transition"
                        >
                            <span class="text-lg">💬</span> Share on WhatsApp
                        </a>
                    </div>

                    <div class="bg-gray-50 px-8 py-6 flex justify-end border-t border-gray-100">
                        <button @click="closeShareModal" class="px-6 py-2 bg-gray-800 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-gray-700 transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ResidentAuthenticatedLayout>
</template>
