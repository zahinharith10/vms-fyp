<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    residents: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce(function (value) {
    router.get(route('admin.residents.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const isDeleteModalOpen = ref(false);
const selectedResident = ref(null);

const deleteForm = useForm({});

const openDeleteModal = (resident) => {
    selectedResident.value = resident;
    isDeleteModalOpen.value = true;
};

const closeModals = () => {
    isDeleteModalOpen.value = false;
    setTimeout(() => { selectedResident.value = null; }, 200);
};

const confirmDelete = () => {
    if (selectedResident.value) {
        deleteForm.delete(route('admin.residents.destroy', selectedResident.value.id), {
            onSuccess: () => closeModals(),
        });
    }
};</script>

<template>
    <Head title="Manage Residents" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Residents</h2>
        </template>

        <template #actions>
            <div class="flex gap-4 items-center">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search residents..." 
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm w-64"
                />
                <div class="flex gap-2">
                    <a :href="route('resident.login')" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-500 text-sm flex items-center transition duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Resident Login Portal
                </a>
                    <Link :href="route('admin.residents.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-150">
                        Register Resident
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
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider w-16">No.</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Name <span class="text-[10px] text-gray-400 font-normal normal-case ml-1">(Click to view details)</span></th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(resident, index) in residents?.data || []" :key="resident.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500 font-semibold">
                                        {{ (residents.from || 1) + index }}.
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 font-medium text-gray-900">
                                        <Link :href="route('admin.residents.show', resident.id)" class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline transition duration-150 text-left">
                                            {{ resident.name }}
                                        </Link>
                                        <div class="text-xs text-gray-400">{{ resident.ic_number }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-700">
                                        <span v-if="resident.house_unit" class="bg-gray-100 px-2 py-1 rounded">
                                            {{ resident.house_unit.formatted_unit }}
                                        </span>
                                        <span v-else class="text-red-500 text-xs">Unassigned</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                                        <div>{{ resident.phone }}</div>
                                        <div class="text-xs">{{ resident.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                                              :class="{
                                                  'bg-green-100 text-green-800': resident.type === 'owner',
                                                  'bg-blue-100 text-blue-800': resident.type === 'tenant',
                                                  'bg-purple-100 text-purple-800': resident.type === 'family'
                                              }">
                                            {{ resident.type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                                              :class="{
                                                  'bg-green-100 text-green-800': resident.status === 'active',
                                                  'bg-red-100 text-red-800': resident.status === 'inactive'
                                              }">
                                            {{ resident.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right text-sm leading-5 font-medium">
                                        <Link :href="route('admin.residents.edit', resident.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                        <button @click="openDeleteModal(resident)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="(residents?.data || []).length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No residents found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <Pagination class="mt-6" :links="residents.links" />
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Resident"
            :message="'Are you sure you want to delete ' + selectedResident?.name + '? This will permanently remove their resident account and access to the system.'"
            :loading="deleteForm.processing"
            @close="closeModals"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
