<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    visitors: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce(function (value) {
    router.get(route('admin.visitors.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const isPhotoModalOpen = ref(false);
const isInfoModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedVisitor = ref(null);

const deleteForm = useForm({});

const openPhotoModal = (visitor) => {
    selectedVisitor.value = visitor;
    isPhotoModalOpen.value = true;
};

const openInfoModal = (visitor) => {
    selectedVisitor.value = visitor;
    isInfoModalOpen.value = true;
};

const openDeleteModal = (visitor) => {
    selectedVisitor.value = visitor;
    isDeleteModalOpen.value = true;
};

const closeModals = () => {
    isPhotoModalOpen.value = false;
    isInfoModalOpen.value = false;
    isDeleteModalOpen.value = false;
    setTimeout(() => {
        selectedVisitor.value = null;
    }, 200);
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
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">IC Number</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Plate Number</th>
                                    <th class="px-6 py-3 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="visitor in visitors?.data || []" :key="visitor.id">
                                    <td class="px-6 py-4 whitespace-no-wrap">
                                        <button @click="openPhotoModal(visitor)" class="focus:outline-none" v-if="visitor.photo">
                                            <img :src="'/storage/' + visitor.photo" class="h-10 w-10 rounded-full object-cover border-2 border-transparent hover:border-indigo-500 transition-colors" alt="Photo">
                                        </button>
                                        <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs">N/A</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 font-bold text-indigo-600 cursor-pointer hover:text-indigo-900" @click="openInfoModal(visitor)">
                                        {{ visitor.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-700">
                                        {{ visitor.phone || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-700">
                                        {{ visitor.ic_number || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-700">
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

        <!-- Photo Modal -->
        <Modal :show="isPhotoModalOpen" @close="closeModals" maxWidth="md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ selectedVisitor?.name }}'s Photo</h3>
                    <button @click="closeModals" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex justify-center">
                    <img v-if="selectedVisitor?.photo" :src="'/storage/' + selectedVisitor.photo" class="rounded-lg shadow-lg max-w-full h-auto" :alt="selectedVisitor?.name">
                </div>
            </div>
        </Modal>

        <!-- Info Modal -->
        <Modal :show="isInfoModalOpen" @close="closeModals" maxWidth="lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg font-bold text-indigo-600">Visitor Information</h3>
                    <button @click="closeModals" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div v-if="selectedVisitor" class="grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase">Name</p>
                            <p class="text-lg font-medium text-gray-900">{{ selectedVisitor.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase">Phone Number</p>
                            <p class="text-gray-700">{{ selectedVisitor.phone || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase">IC Number</p>
                            <p class="text-gray-700">{{ selectedVisitor.ic_number || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase">Plate Number</p>
                            <p class="text-gray-900 font-bold uppercase tracking-wider">{{ selectedVisitor.vehicle_number || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase">Face Recognition Status</p>
                            <p class="flex items-center">
                                <span v-if="selectedVisitor.face_descriptor" class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Enrolled</span>
                                <span v-else class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Not Enrolled</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center justify-center border-l pl-4">
                        <div class="w-32 h-32 rounded-lg overflow-hidden border-2 border-gray-100 mb-2">
                            <img v-if="selectedVisitor.photo" :src="'/storage/' + selectedVisitor.photo" class="w-full h-full object-cover" alt="Profile">
                            <div v-else class="w-full h-full bg-gray-100 flex items-center justify-center text-gray-400 italic">No Photo</div>
                        </div>
                        <p class="text-xs text-gray-500 italic">Registered on {{ new Date(selectedVisitor.created_at).toLocaleDateString('en-GB') }}</p>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Visitor"
            :message="'Are you sure you want to delete ' + selectedVisitor?.name + '? This will permanently remove their record and all associated data.'"
            :loading="deleteForm.processing"
            @close="closeModals"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
