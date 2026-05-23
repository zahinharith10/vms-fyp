<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';
import { formatMalaysiaDateTime } from '@/utils/datetime';

defineProps({
    logs: Array,
});

const selectedPersonnel = ref(null);
const isModalOpen = ref(false);

const openModal = (personnel) => {
    selectedPersonnel.value = personnel;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => {
        selectedPersonnel.value = null;
    }, 200);
};

</script>

<template>
    <Head title="Delivery Logs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Delivery Logs</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Personnel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="log in logs" :key="log.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ formatMalaysiaDateTime(log.entry_time || log.created_at, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</div>
                                        <div v-if="log.exit_time" class="text-xs text-gray-500">Exit: {{ formatMalaysiaDateTime(log.exit_time, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <img v-if="log.personnel.photo" :src="'/storage/' + log.personnel.photo" class="h-8 w-8 rounded-full object-cover">
                                                <div v-else class="h-8 w-8 rounded-full bg-gray-200"></div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 cursor-pointer hover:text-indigo-600 font-bold" @click="openModal(log.personnel)">{{ log.personnel.name }}</div>
                                                <div class="text-xs text-gray-500">{{ log.personnel.phone }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ log.personnel.company }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ log.destination }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ log.personnel.vehicle_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': log.status === 'Pending',
                                                'bg-green-100 text-green-800': log.status === 'Approved' || log.entry_time,
                                                'bg-red-100 text-red-800': log.status === 'Rejected',
                                                'bg-gray-100 text-gray-800': log.exit_time
                                            }">
                                            {{ log.exit_time ? 'Completed' : (log.entry_time ? 'Entered' : log.status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="logs.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No delivery logs found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                    <div class="w-32 h-40 rounded-xl overflow-hidden shadow-lg border-2 border-white mb-4">
                        <img v-if="selectedPersonnel.photo" :src="'/storage/' + selectedPersonnel.photo" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400 italic text-xs">No Photo</div>
                    </div>
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-tighter">Biometric ID Profile</p>
                </div>
            </div>
        </div>
    </Modal>
</template>
