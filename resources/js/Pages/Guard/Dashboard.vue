<script setup>
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    auth: Object,
    stats: Object,
    approvedDeliveries: Array,
    parkingLots: Array,
});

import { useForm } from '@inertiajs/vue3';
const checkInForm = useForm({
    log_id: null,
});

const checkInDelivery = (id) => {
    checkInForm.log_id = id;
    checkInForm.post(route('guard.scan.checkin-delivery'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success notification handled by flash messages
        }
    });
};

const selectedLot = ref(null);
const isLotModalOpen = ref(false);

const showLotDetails = (lot) => {
    selectedLot.value = lot;
    isLotModalOpen.value = true;
};

const closeLotModal = () => {
    isLotModalOpen.value = false;
    setTimeout(() => {
        selectedLot.value = null;
    }, 200);
};
</script>

<template>
    <Head title="Guard Dashboard" />

    <GuardAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Duty Dashboard</h2>
        </template>

        <!-- Welcome Section -->
        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm dark:shadow-indigo-950/10 sm:rounded-3xl border border-transparent dark:border-gray-800/80 mb-8 transition-all duration-300">
            <div class="p-8 text-gray-900 dark:text-white flex justify-between items-center">
                <div>
                    <h3 class="text-3xl font-black text-gray-800 dark:text-white">Welcome, Officer {{ auth.user.name }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 font-medium">You are currently monitoring the main point of entry.</p>
                </div>
                <div class="hidden lg:block">
                    <div class="bg-indigo-600 dark:bg-indigo-650 px-6 py-3 rounded-2xl shadow-lg dark:shadow-none flex flex-col items-center">
                        <span class="text-[10px] text-indigo-100 uppercase font-black tracking-widest">Duty Status</span>
                        <span class="text-white font-black">ACTIVE</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-12">
            <!-- Visitors Today -->
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-transparent dark:border-gray-800/80 border-l-4 border-l-indigo-500 dark:border-l-indigo-500 transition-all duration-300">
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Check-ins Today</div>
                <div class="mt-2 text-4xl font-black text-indigo-600 dark:text-indigo-400">{{ stats.visitors_today }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 italic font-bold">Total today</div>
            </div>

            <!-- Active Visitors -->
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-transparent dark:border-gray-800/80 border-l-4 border-l-green-500 dark:border-l-green-500 relative overflow-hidden transition-all duration-300">
                <div class="text-[10px] font-black text-green-600 dark:text-green-400 uppercase tracking-widest flex items-center">
                    <span class="h-2 w-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> Active Now
                </div>
                <div class="mt-2 text-4xl font-black text-green-700 dark:text-green-400">{{ stats.active_visitors }}</div>
                <div class="text-[10px] text-green-600/60 dark:text-green-450 mt-1 italic font-bold">Currently on-visit</div>
                <div class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-10 text-gray-900 dark:text-white">
                    <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                </div>
            </div>

            <!-- Approved (Upcoming) -->
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-transparent dark:border-gray-800/80 border-l-4 border-l-blue-500 dark:border-l-blue-500 transition-all duration-300">
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Expected Queue</div>
                <div class="mt-2 text-4xl font-black text-blue-600 dark:text-blue-400">{{ stats.approved_upcoming }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 italic font-bold">Approved arrivals</div>
            </div>

            <!-- Pending Resident Approval -->
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-transparent dark:border-gray-800/80 border-l-4 border-l-yellow-500 dark:border-l-yellow-500 transition-all duration-300">
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Wait Count</div>
                <div class="mt-2 text-4xl font-black text-yellow-600 dark:text-yellow-400">{{ stats.pending_approvals }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 italic font-bold">Waiting for resident</div>
            </div>

            <!-- Visitor Parking -->
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-transparent dark:border-gray-800/80 border-l-4 relative overflow-hidden transition-all duration-300" :class="stats.occupied_parking === stats.total_parking ? 'border-red-500 dark:border-red-500' : 'border-indigo-500 dark:border-indigo-500'">
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Visitor Parking</div>
                <div class="mt-2 text-4xl font-black" :class="stats.occupied_parking === stats.total_parking ? 'text-red-600 dark:text-red-400' : 'text-indigo-650 dark:text-indigo-400'">{{ stats.occupied_parking }} / {{ stats.total_parking }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 italic font-bold">{{ stats.total_parking - stats.occupied_parking }} spots free</div>
                <div class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-10 text-gray-900 dark:text-white">
                    <span class="text-8xl select-none">🅿️</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions Grid -->
        <h3 class="text-sm font-black text-gray-400 dark:text-gray-500 mb-6 uppercase tracking-[0.2em]">Operational Controls</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Scan Action -->
            <Link :href="route('guard.scan')" class="group bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-650 dark:hover:bg-indigo-700 p-8 rounded-3xl shadow-xl dark:shadow-none transition-all duration-300 hover:scale-[1.03] flex flex-col items-center justify-center border-b-8 border-indigo-850 dark:border-indigo-800">
                <div class="bg-white/20 p-5 rounded-2xl mb-4 group-hover:rotate-12 transition-transform">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1l-3 3h2v5H7l3 3-3 3h2v5M17 13h-4v4h4v-4zM7 9H3v4h4V9zM17 5h-4v4h4V5zM7 5H3v4h4V5z"></path></svg>
                </div>
                <span class="text-xl font-black text-white tracking-tight">OPEN SCANNER</span>
                <p class="text-indigo-200 mt-1 text-xs font-bold uppercase tracking-widest">Secure Entry Point</p>
            </Link>
            
            <!-- Info Card (Resident-style) -->
            <div class="md:col-span-2 bg-slate-900 dark:bg-slate-950 rounded-3xl p-8 text-white relative overflow-hidden border border-transparent dark:border-gray-800/80 shadow-2xl transition-all duration-300">
                <div class="relative z-10">
                    <h3 class="text-xs font-black text-indigo-400 uppercase tracking-widest mb-4">Security Protocol</h3>
                    <p class="text-lg font-medium leading-relaxed max-w-lg">
                        Ensure all visitors present a <span class="text-indigo-400 font-bold">valid QR code</span>. If a visitor is not listed, they must register their details.
                    </p>
                    <div class="mt-8 grid grid-cols-2 gap-4">
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10 dark:border-white/5">
                             <span class="text-2xl block mb-2">📸</span>
                             <span class="text-[10px] font-black uppercase text-indigo-300">Identity</span>
                             <p class="text-xs mt-1 font-bold">Photo Match Required</p>
                        </div>
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10 dark:border-white/5">
                             <span class="text-2xl block mb-2">⚡</span>
                             <span class="text-[10px] font-black uppercase text-indigo-300">Scanning</span>
                             <p class="text-xs mt-1 font-bold">Instant Scan</p>
                        </div>
                    </div>
                </div>
                <!-- Decoration -->
                <div class="absolute -right-10 -bottom-10 opacity-10 blur-2xl flex items-center justify-center">
                    <div class="h-64 w-64 bg-indigo-500 rounded-full"></div>
                </div>
            </div>
        </div>

        <!-- Manual Entry Queue (Approved Deliveries) -->
        <div v-if="approvedDeliveries && approvedDeliveries.length > 0" class="mt-12">
            <h3 class="text-sm font-black text-gray-400 dark:text-gray-500 mb-6 uppercase tracking-[0.2em]">Ready for Entry (Manual Registrations)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="log in approvedDeliveries" :key="log.id" class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800/80 flex items-center justify-between transition-all duration-300">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 flex items-center justify-center mr-4 overflow-hidden border border-indigo-100 dark:border-indigo-900/30">
                             <img v-if="log.personnel.photo" :src="'/storage/' + log.personnel.photo" class="h-full w-full object-cover" />
                             <span v-else class="text-indigo-400 font-black text-xl">{{ log.personnel.name.charAt(0) }}</span>
                        </div>
                        <div>
                            <h4 class="font-black text-gray-900 dark:text-white leading-tight">{{ log.personnel.name }}</h4>
                            <p class="text-[10px] text-indigo-500 dark:text-indigo-400 font-black uppercase tracking-widest mt-1">{{ log.personnel.company }} • {{ log.personnel.vehicle_number }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase mt-0.5">Destination: {{ log.destination }}</p>
                        </div>
                    </div>
                    <button 
                        @click="checkInDelivery(log.id)"
                        class="bg-green-600 hover:bg-green-700 text-white text-[10px] font-black px-4 py-2 rounded-xl transition-all shadow-md dark:shadow-none shadow-green-100 uppercase tracking-widest"
                        :disabled="checkInForm.processing"
                    >
                        Check In
                    </button>
                </div>
            </div>
        </div>

        <!-- Visitor Parking Lots Visual Grid -->
        <div class="mt-12">
            <h3 class="text-sm font-black text-gray-400 dark:text-gray-500 mb-6 uppercase tracking-[0.2em]">Visitor Parking Lot Status</h3>
            <div class="bg-white dark:bg-gray-900 rounded-3xl p-8 shadow-sm border border-gray-100 dark:border-gray-800/80 transition-all duration-300">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div>
                        <span class="text-2xl font-black text-gray-800 dark:text-white">15-Lot Visitor Parking Bay</span>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 font-bold">Real-time occupancy of visitor parking spaces</p>
                    </div>
                    <div class="flex space-x-6 text-xs font-bold uppercase tracking-wider">
                        <div class="flex items-center text-green-600 dark:text-green-400">
                            <span class="h-3.5 w-3.5 rounded bg-green-500 mr-2 border border-green-650 dark:border-green-600"></span>
                            Available ({{ stats.total_parking - stats.occupied_parking }})
                        </div>
                        <div class="flex items-center text-red-600 dark:text-red-400">
                            <span class="h-3.5 w-3.5 rounded bg-red-500 mr-2 border border-red-650 dark:border-red-600"></span>
                            Occupied ({{ stats.occupied_parking }})
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 md:grid-cols-8 gap-4">
                    <div 
                        v-for="lot in parkingLots" 
                        :key="lot.lot_number"
                        class="relative group rounded-2xl p-4 flex flex-col items-center justify-between border-2 transition-all duration-300 select-none shadow-sm cursor-pointer hover:shadow-md"
                        :class="lot.status === 'Occupied' 
                            ? 'bg-red-50/50 dark:bg-red-950/20 border-red-200 dark:border-red-900/30 hover:border-red-400 dark:hover:border-red-750' 
                            : 'bg-green-50/50 dark:bg-green-950/20 border-green-200 dark:border-green-900/30 hover:border-green-400 dark:hover:border-green-750'"
                        @click="lot.status === 'Occupied' ? showLotDetails(lot) : null"
                    >
                        <div class="text-[10px] font-black tracking-widest uppercase mb-1" :class="lot.status === 'Occupied' ? 'text-red-500 dark:text-red-400' : 'text-green-655 dark:text-green-400'">
                            Lot {{ String(lot.lot_number).padStart(2, '0') }}
                        </div>
                        <div class="text-2xl mb-1">
                            {{ lot.status === 'Occupied' ? '🚗' : '🅿️' }}
                        </div>
                        <div class="text-[10px] font-black uppercase text-center w-full truncate" :class="lot.status === 'Occupied' ? 'text-red-650 dark:text-red-300' : 'text-green-500 dark:text-green-500'">
                            {{ lot.status === 'Occupied' ? lot.vehicle_number : 'Empty' }}
                        </div>

                        <!-- Tooltip on Hover for Occupied Lots -->
                        <div v-if="lot.status === 'Occupied'" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-slate-900 dark:bg-slate-950 text-white text-xs rounded-xl p-3 opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity duration-200 z-20 shadow-xl border border-slate-700 dark:border-slate-800">
                            <div class="font-black text-indigo-400 border-b border-slate-800 dark:border-slate-900 pb-1 mb-1.5 uppercase tracking-wide">Lot Details</div>
                            <div class="space-y-1 font-bold text-left">
                                <div><span class="text-slate-400">Name:</span> {{ lot.visitor_name }}</div>
                                <div><span class="text-slate-400">Plate:</span> {{ lot.vehicle_number }}</div>
                                <div><span class="text-slate-400">Unit:</span> Unit {{ lot.unit_number }}</div>
                            </div>
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-slate-900 dark:border-t-slate-950"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lot Details Modal (for Mobile/Click) -->
        <div v-if="isLotModalOpen && selectedLot" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="lot-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="closeLotModal" class="fixed inset-0 bg-slate-950 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 dark:border-gray-800 border-t-8 border-red-500 dark:border-t-red-500 transition-all duration-300">
                    <div class="px-6 py-4 flex justify-between items-center border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-widest" id="lot-modal-title">
                            Visitor Parking Lot {{ String(selectedLot.lot_number).padStart(2, '0') }}
                        </h3>
                        <button @click="closeLotModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4 bg-gray-50 dark:bg-gray-950 p-6 rounded-2xl border border-gray-100 dark:border-gray-850 text-sm">
                            <div class="flex justify-between">
                                <span class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase">Visitor Name</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200">{{ selectedLot.visitor_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase">Vehicle Plate</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200 uppercase">{{ selectedLot.vehicle_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase">Destination Unit</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200">Unit {{ selectedLot.unit_number }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-950 px-6 py-4 flex justify-end">
                        <button @click="closeLotModal" class="px-6 py-2 bg-gray-800 dark:bg-gray-800 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-gray-700 dark:hover:bg-gray-700/80 transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </GuardAuthenticatedLayout>
</template>

