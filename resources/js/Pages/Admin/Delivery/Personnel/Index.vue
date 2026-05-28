<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { ref } from 'vue';

defineProps({
    personnel: Array,
});

const isDeleteModalOpen = ref(false);
const isViewModalOpen = ref(false);
const selectedPersonnel = ref(null);

const deleteForm = useForm({});

const openDeleteModal = (person) => {
    selectedPersonnel.value = person;
    isDeleteModalOpen.value = true;
};

const openViewModal = (person) => {
    selectedPersonnel.value = person;
    isViewModalOpen.value = true;
};

const closeModals = () => {
    isDeleteModalOpen.value = false;
    isViewModalOpen.value = false;
    setTimeout(() => {
        selectedPersonnel.value = null;
    }, 300);
};

const confirmDelete = () => {
    if (selectedPersonnel.value) {
        deleteForm.delete(route('admin.delivery.personnel.destroy', selectedPersonnel.value.id), {
            onSuccess: () => closeModals(),
        });
    }
};
</script>

<template>
    <Head title="Delivery Personnel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Delivery Personnel</h2>
        </template>

        <template #actions>
            <Link :href="route('admin.delivery.personnel.create')" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 font-bold text-sm transition duration-150">
                Add Personnel
            </Link>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="p in personnel" :key="p.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <img v-if="p.photo" :src="'/storage/' + p.photo" class="h-10 w-10 rounded-full object-cover cursor-pointer ring-2 ring-transparent hover:ring-indigo-400 transition" @click="openViewModal(p)" />
                                        <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 cursor-pointer hover:bg-indigo-100 transition" @click="openViewModal(p)">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <!-- Clickable Name -->
                                        <button @click="openViewModal(p)" class="text-left group">
                                            <div class="text-sm font-semibold text-indigo-600 group-hover:text-indigo-800 group-hover:underline transition-colors duration-150">{{ p.name }}</div>
                                            <div class="text-sm text-gray-500">{{ p.phone }}</div>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ p.company }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div>{{ p.vehicle_number }}</div>
                                        <div class="text-xs">{{ p.vehicle_type }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="p.status === 'Active'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                        <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            {{ p.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link :href="route('admin.delivery.personnel.edit', p.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                        <button @click="openDeleteModal(p)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="personnel.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No delivery personnel found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Details Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="isViewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModals"></div>

                    <!-- Modal Card -->
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="isViewModalOpen && selectedPersonnel" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden z-10">
                            <!-- Header with gradient -->
                            <div class="relative h-28 bg-gradient-to-br from-indigo-600 to-purple-600">
                                <button @click="closeModals" class="absolute top-4 right-4 text-white/80 hover:text-white transition">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Photo (overlapping the header) -->
                            <div class="flex justify-center -mt-14">
                                <div class="relative">
                                    <img v-if="selectedPersonnel.photo" :src="'/storage/' + selectedPersonnel.photo" class="h-28 w-28 rounded-full object-cover border-4 border-white shadow-lg" />
                                    <div v-else class="h-28 w-28 rounded-full bg-gray-200 border-4 border-white shadow-lg flex items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <!-- Status badge -->
                                    <span
                                        class="absolute bottom-1 right-1 h-5 w-5 rounded-full border-2 border-white"
                                        :class="selectedPersonnel.status === 'Active' ? 'bg-green-500' : 'bg-red-400'"
                                    ></span>
                                </div>
                            </div>

                            <!-- Name & Company -->
                            <div class="text-center mt-3 px-6">
                                <h3 class="text-xl font-bold text-gray-900">{{ selectedPersonnel.name }}</h3>
                                <span class="inline-block mt-1 px-3 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ selectedPersonnel.company }}</span>
                            </div>

                            <!-- Info Grid -->
                            <div class="mt-6 px-6 pb-6 space-y-3">
                                <!-- Phone -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="flex-shrink-0 h-9 w-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Phone</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ selectedPersonnel.phone }}</p>
                                    </div>
                                </div>

                                <!-- IC Number -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="flex-shrink-0 h-9 w-9 bg-yellow-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">IC Number</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ selectedPersonnel.ic_number }}</p>
                                    </div>
                                </div>

                                <!-- Vehicle Number -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="flex-shrink-0 h-9 w-9 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Vehicle</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ selectedPersonnel.vehicle_number }} <span class="text-gray-400 font-normal">· {{ selectedPersonnel.vehicle_type }}</span></p>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="flex-shrink-0 h-9 w-9 rounded-lg flex items-center justify-center" :class="selectedPersonnel.status === 'Active' ? 'bg-green-100' : 'bg-red-100'">
                                        <svg class="h-5 w-5" :class="selectedPersonnel.status === 'Active' ? 'text-green-600' : 'text-red-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Status</p>
                                        <span
                                            class="text-sm font-semibold"
                                            :class="selectedPersonnel.status === 'Active' ? 'text-green-600' : 'text-red-500'"
                                        >{{ selectedPersonnel.status }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-3 pt-2">
                                    <Link
                                        :href="route('admin.delivery.personnel.edit', selectedPersonnel.id)"
                                        class="flex-1 text-center py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors duration-150"
                                    >
                                        Edit Details
                                    </Link>
                                    <button
                                        @click="closeModals"
                                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-sm transition-colors duration-150"
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Delivery Personnel"
            :message="'Are you sure you want to delete ' + selectedPersonnel?.name + '? This will permanently remove their records from the system.'"
            :loading="deleteForm.processing"
            @close="closeModals"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
