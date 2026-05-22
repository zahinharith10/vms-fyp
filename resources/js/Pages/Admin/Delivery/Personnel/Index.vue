<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { ref } from 'vue';

defineProps({
    personnel: Array,
});

const isDeleteModalOpen = ref(false);
const selectedPersonnel = ref(null);

const deleteForm = useForm({});

const openDeleteModal = (person) => {
    selectedPersonnel.value = person;
    isDeleteModalOpen.value = true;
};

const closeModals = () => {
    isDeleteModalOpen.value = false;
    setTimeout(() => {
        selectedPersonnel.value = null;
    }, 200);
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
                                <tr v-for="p in personnel" :key="p.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <img v-if="p.photo" :src="'/storage/' + p.photo" class="h-10 w-10 rounded-full object-cover" />
                                        <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                            No
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ p.name }}</div>
                                        <div class="text-sm text-gray-500">{{ p.phone }}</div>
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
