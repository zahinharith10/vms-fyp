<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { formatMalaysiaDate } from '@/utils/datetime';

const props = defineProps({
    visitors: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce(function (value) {
    router.get(route('admin.visitors.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const isViewModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isPhotoZoomOpen = ref(false);
const selectedVisitor = ref(null);

const deleteForm = useForm({});

const openViewModal = (visitor) => {
    selectedVisitor.value = visitor;
    isViewModalOpen.value = true;
};

const openDeleteModal = (visitor) => {
    selectedVisitor.value = visitor;
    isDeleteModalOpen.value = true;
};

const openPhotoZoom = () => {
    if (selectedVisitor.value?.photo) {
        isPhotoZoomOpen.value = true;
    }
};

const closePhotoZoom = () => {
    isPhotoZoomOpen.value = false;
};

const closeModals = () => {
    isViewModalOpen.value = false;
    isDeleteModalOpen.value = false;
    isPhotoZoomOpen.value = false;
    setTimeout(() => {
        selectedVisitor.value = null;
    }, 300);
};

const confirmDelete = () => {
    if (selectedVisitor.value) {
        deleteForm.delete(route('admin.visitors.destroy', selectedVisitor.value.id), {
            onSuccess: () => closeModals(),
        });
    }
};
</script>

<template>
    <Head title="Manage Visitors" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Visitors</h2>
        </template>

        <template #actions>
            <div class="flex gap-4 items-center">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search visitors..."
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm w-64"
                />
                <div class="flex gap-2">
                    <a :href="route('visitor.login')" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-500 text-sm flex items-center transition duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        Visitor Login Portal
                    </a>
                    <Link :href="route('admin.visitors.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-150">
                        Register Visitor
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">IC Number</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Plate Number</th>
                                    <th class="px-6 py-3 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="visitor in visitors?.data || []" :key="visitor.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-no-wrap">
                                        <img
                                            v-if="visitor.photo"
                                            :src="'/storage/' + visitor.photo"
                                            class="h-10 w-10 rounded-full object-cover cursor-pointer ring-2 ring-transparent hover:ring-indigo-400 transition"
                                            @click="openViewModal(visitor)"
                                            alt="Photo"
                                        />
                                        <div
                                            v-else
                                            class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 cursor-pointer hover:bg-indigo-100 transition"
                                            @click="openViewModal(visitor)"
                                        >
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap">
                                        <!-- Clickable Name -->
                                        <button @click="openViewModal(visitor)" class="text-left group">
                                            <div class="text-sm font-semibold text-indigo-600 group-hover:text-indigo-800 group-hover:underline transition-colors duration-150">{{ visitor.name }}</div>
                                            <div class="text-xs text-gray-400">{{ visitor.phone || 'No phone' }}</div>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-700">
                                        {{ visitor.email || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-700">
                                        {{ visitor.ic_number || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-700 font-mono uppercase">
                                        {{ visitor.vehicle_number || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right text-sm leading-5 font-medium">
                                        <Link :href="route('admin.visitors.edit', visitor.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                        <button @click="openDeleteModal(visitor)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="(visitors?.data || []).length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No visitors found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <Pagination class="mt-6" :links="visitors.links" />
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
                        <div v-if="isViewModalOpen && selectedVisitor" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden z-10">
                            <!-- Header gradient -->
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
                                    <img
                                        v-if="selectedVisitor.photo"
                                        :src="'/storage/' + selectedVisitor.photo"
                                        class="h-28 w-28 rounded-full object-cover border-4 border-white shadow-lg cursor-zoom-in hover:scale-105 transition-transform duration-200"
                                        @click="openPhotoZoom"
                                        title="Click to zoom"
                                    />
                                    <div v-else class="h-28 w-28 rounded-full bg-gray-200 border-4 border-white shadow-lg flex items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <!-- Face recognition badge -->
                                    <span
                                        class="absolute bottom-1 right-1 h-5 w-5 rounded-full border-2 border-white"
                                        :class="selectedVisitor.face_descriptor ? 'bg-green-500' : 'bg-gray-400'"
                                        :title="selectedVisitor.face_descriptor ? 'Face Enrolled' : 'Face Not Enrolled'"
                                    ></span>
                                </div>
                            </div>

                            <!-- Name & registered date -->
                            <div class="text-center mt-3 px-6">
                                <h3 class="text-xl font-bold text-gray-900">{{ selectedVisitor.name }}</h3>
                                <p class="text-xs text-gray-400 mt-1">Registered on {{ formatMalaysiaDate(selectedVisitor.created_at) }}</p>
                            </div>

                            <!-- Info Grid -->
                            <div class="mt-5 px-6 pb-6 space-y-3">
                                <!-- Phone -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="flex-shrink-0 h-9 w-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Phone</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ selectedVisitor.phone || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="flex-shrink-0 h-9 w-9 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Email</p>
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ selectedVisitor.email || 'N/A' }}</p>
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
                                        <p class="text-sm font-semibold text-gray-800">{{ selectedVisitor.ic_number || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Vehicle Plate -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="flex-shrink-0 h-9 w-9 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2.5-2.5m0 0L7 16m-1.5-2.5H13m0 0l2.5 2.5M13 16h4a1 1 0 001-1v-3.65a1 1 0 00-.293-.707L15 8H13v8z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Plate Number</p>
                                        <p class="text-sm font-semibold text-gray-800 uppercase font-mono tracking-wider">{{ selectedVisitor.vehicle_number || 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Face Recognition Status -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="flex-shrink-0 h-9 w-9 rounded-lg flex items-center justify-center" :class="selectedVisitor.face_descriptor ? 'bg-green-100' : 'bg-gray-100'">
                                        <svg class="h-5 w-5" :class="selectedVisitor.face_descriptor ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Face Recognition</p>
                                        <span
                                            class="text-sm font-semibold"
                                            :class="selectedVisitor.face_descriptor ? 'text-green-600' : 'text-gray-400'"
                                        >{{ selectedVisitor.face_descriptor ? 'Enrolled' : 'Not Enrolled' }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-3 pt-2">
                                    <Link
                                        :href="route('admin.visitors.edit', selectedVisitor.id)"
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

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Visitor"
            :message="'Are you sure you want to delete ' + selectedVisitor?.name + '? This will permanently remove their record and all associated data.'"
            :loading="deleteForm.processing"
            @close="closeModals"
            @confirm="confirmDelete"
        />
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
                    v-if="isPhotoZoomOpen && selectedVisitor?.photo"
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
                                :src="'/storage/' + selectedVisitor.photo"
                                class="max-h-[85vh] max-w-[85vw] rounded-2xl shadow-2xl object-contain"
                                :alt="selectedVisitor.name"
                            />
                            <button
                                @click="closePhotoZoom"
                                class="absolute -top-3 -right-3 bg-white rounded-full h-8 w-8 flex items-center justify-center shadow-lg hover:bg-gray-100 transition"
                            >
                                <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <p class="text-center text-white/70 text-sm mt-3">{{ selectedVisitor.name }}</p>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>
