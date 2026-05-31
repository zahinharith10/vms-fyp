<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { ref, computed } from 'vue';
import { formatMalaysiaDate, formatMalaysiaTime } from '@/utils/datetime';

const props = defineProps({
    logs: Array,
});

const selectedPersonnel = ref(null);
const isModalOpen = ref(false);
const isPhotoZoomOpen = ref(false);

const filterStatus = ref('All');
const searchQuery = ref('');

const openModal = (personnel) => {
    selectedPersonnel.value = personnel;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    isPhotoZoomOpen.value = false;
    setTimeout(() => {
        selectedPersonnel.value = null;
    }, 200);
};

const openPhotoZoom = () => {
    if (selectedPersonnel.value?.photo) {
        isPhotoZoomOpen.value = true;
    }
};

const closePhotoZoom = () => {
    isPhotoZoomOpen.value = false;
};

const getStatusLabel = (log) => {
    if (log.exit_time) return 'Completed';
    if (log.entry_time) return 'On-Site';
    return log.status;
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

const formatDuration = (log) => {
    if (log.entry_time) {
        const start = new Date(log.entry_time);
        const end = log.exit_time ? new Date(log.exit_time) : new Date();
        const diffMs = end - start;
        if (diffMs < 0) return '-';
        return formatMinutes(Math.floor(diffMs / 60000));
    }
    return '-';
};

const formatMinutes = (totalMins) => {
    const hours = Math.floor(totalMins / 60);
    const mins = totalMins % 60;
    if (hours > 0) {
        return `${hours} hr ${mins} min`;
    }
    return `${mins} min`;
};

const filteredLogs = computed(() => {
    return props.logs.filter(log => {
        let matchesStatus = true;
        const visualStatus = log.exit_time ? 'Completed' : (log.entry_time ? 'Entered' : log.status);
        
        if (filterStatus.value !== 'All') {
            matchesStatus = visualStatus === filterStatus.value;
        }

        const name = log.personnel?.name || '';
        const destination = log.destination || '';
        const company = log.personnel?.company || '';
        const vehicle = log.personnel?.vehicle_number || '';
        const email = log.personnel?.email || '';
        
        const matchesSearch = name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             destination.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                             company.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                             vehicle.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                             email.toLowerCase().includes(searchQuery.value.toLowerCase());
                             
        return matchesStatus && matchesSearch;
    });
});
</script>

<template>
    <Head title="Delivery History" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Delivery History</h2>
        </template>

        <template #actions>
            <a :href="route('admin.delivery.logs.export')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
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
                                    <option value="All">All Deliveries</option>
                                    <option value="Entered">Active (On-Site)</option>
                                    <option value="Completed">History (Checked Out)</option>
                                    <option value="Approved">Approved (Not In)</option>
                                    <option value="Pending">Pending Approval</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                                
                                <div class="relative w-full md:w-64">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </span>
                                    <input v-model="searchQuery" type="text" placeholder="Search name, vehicle or unit..." class="pl-10 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm w-full" />
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
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Personnel</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Destination Unit</th>
                                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Company & Vehicle</th>
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
                                                <div class="h-8 w-8 rounded-full overflow-hidden bg-gray-100 mr-3 cursor-pointer" @click="openModal(log.personnel)">
                                                    <img v-if="log.personnel.photo" :src="'/storage/' + log.personnel.photo" class="h-full w-full object-cover" />
                                                    <div v-else class="h-full w-full flex items-center justify-center text-xs font-bold text-gray-400">
                                                        {{ log.personnel.name.charAt(0) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-bold text-gray-900 cursor-pointer hover:text-indigo-600 font-bold transition-colors" @click="openModal(log.personnel)">
                                                        {{ log.personnel.name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">{{ log.personnel.phone }}</div>
                                                    <div class="text-xs text-gray-400 mt-0.5">{{ log.personnel.email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-lg font-black text-sm border border-indigo-100">
                                                {{ log.destination }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col gap-1 items-start">
                                                <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                                    {{ log.personnel.company }}
                                                </span>
                                                <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">
                                                    {{ log.personnel.vehicle_number }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusClass(getStatusLabel(log))" class="px-2 py-1 text-xs font-bold rounded-full border uppercase tracking-wider">
                                                {{ getStatusLabel(log) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                            <div v-if="log.entry_time">
                                                <p class="font-bold text-gray-800">{{ formatMalaysiaTime(log.entry_time, { withSeconds: true }) }}</p>
                                                <p>{{ formatMalaysiaDate(log.entry_time) }}</p>
                                            </div>
                                            <div v-else class="italic">Not Entered</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                            <div v-if="log.exit_time">
                                                <p class="font-bold text-gray-800">{{ formatMalaysiaTime(log.exit_time, { withSeconds: true }) }}</p>
                                                <p>{{ formatMalaysiaDate(log.exit_time) }}</p>
                                            </div>
                                            <div v-else-if="log.entry_time" class="text-indigo-600 font-black animate-pulse">On-Site</div>
                                            <div v-else class="italic">-</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-bold text-indigo-600">
                                            {{ formatDuration(log) }}
                                        </td>
                                    </tr>
                                    <tr v-if="filteredLogs.length === 0">
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">
                                            No delivery history found matching your criteria.
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

    <!-- Personnel Info Modal -->
    <Modal :show="isModalOpen" @close="closeModal" maxWidth="lg">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-bold text-indigo-600">Personnel Information</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div v-if="selectedPersonnel" class="grid grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Name</p>
                        <p class="text-lg font-bold text-gray-900">{{ selectedPersonnel.name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Phone</p>
                        <p class="text-gray-700 font-bold">{{ selectedPersonnel.phone }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Email</p>
                        <p class="text-gray-700 font-bold break-all">{{ selectedPersonnel.email || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Company</p>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-lg">{{ selectedPersonnel.company }}</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">IC Number</p>
                        <p class="text-gray-700">{{ selectedPersonnel.ic_number || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Plate Number</p>
                        <p class="text-gray-900 font-bold uppercase tracking-wider">{{ selectedPersonnel.vehicle_number || 'N/A' }}</p>
                    </div>
                </div>
                
                <div class="flex flex-col items-center justify-center bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <div class="w-32 h-40 rounded-xl overflow-hidden shadow-lg border-2 border-white mb-4 cursor-pointer hover:scale-105 transition duration-200 group relative" @click="openPhotoZoom">
                        <img v-if="selectedPersonnel.photo" :src="'/storage/' + selectedPersonnel.photo" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400 italic text-xs">No Photo</div>
                        <div v-if="selectedPersonnel.photo" class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition duration-200">
                            <span class="text-white text-xs font-bold bg-black/60 px-2 py-1 rounded-md">Zoom 🔍</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-tighter">Biometric ID Profile</p>
                </div>
            </div>
        </div>
    </Modal>

    <!-- Photo Zoom Lightbox -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isPhotoZoomOpen && selectedPersonnel?.photo"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm cursor-zoom-out"
                @click="closePhotoZoom"
            >
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-75"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-75"
                >
                    <div v-if="isPhotoZoomOpen" class="relative" @click.stop>
                        <img
                            :src="'/storage/' + selectedPersonnel.photo"
                            class="max-h-[85vh] max-w-[85vw] rounded-2xl shadow-2xl object-contain"
                            :alt="selectedPersonnel.name"
                        />
                        <button
                            @click="closePhotoZoom"
                            class="absolute -top-3 -right-3 bg-white rounded-full h-8 w-8 flex items-center justify-center shadow-lg hover:bg-gray-100 transition"
                        >
                            <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <p class="text-center text-white/70 text-sm mt-3 font-semibold">{{ selectedPersonnel.name }}</p>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
