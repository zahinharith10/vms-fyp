<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    units: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce(function (value) {
    router.get(route('admin.units.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const isDeleteModalOpen = ref(false);
const selectedUnit = ref(null);

const deleteForm = useForm({});

const openDeleteModal = (unit) => {
    selectedUnit.value = unit;
    isDeleteModalOpen.value = true;
};

const closeModals = () => {
    isDeleteModalOpen.value = false;
    setTimeout(() => {
        selectedUnit.value = null;
    }, 200);
};

const confirmDelete = () => {
    if (selectedUnit.value) {
        deleteForm.delete(route('admin.units.destroy', selectedUnit.value.id), {
            onSuccess: () => closeModals(),
        });
    }
};
</script>

<template>
    <Head title="House Units" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">House Units</h2>
        </template>

        <template #actions>
            <div class="flex gap-4 items-center">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search units..." 
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm w-64"
                />
                <Link :href="route('admin.units.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-150">
                    Add New Unit
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4 flex justify-between items-center">
                            <p class="text-sm text-gray-600">Total House Units: <span class="font-bold text-indigo-600">{{ units?.total || 0 }}</span></p>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Unit (Block-Floor-Number)</th>
                                    <th class="px-6 py-3 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(unit, index) in units?.data || []" :key="unit.id">
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm text-gray-500">
                                        {{ (units?.current_page - 1) * units?.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 font-medium text-gray-900">
                                        {{ unit.formatted_unit }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right text-sm leading-5 font-medium">
                                        <Link :href="route('admin.units.edit', unit.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                        <button @click="openDeleteModal(unit)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="(units?.data || []).length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No house units found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <Pagination class="mt-6" :links="units.links" />
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete House Unit"
            :message="'Are you sure you want to delete unit ' + selectedUnit?.formatted_unit + '? This will also affect residents assigned to this unit.'"
            :loading="deleteForm.processing"
            @close="closeModals"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
