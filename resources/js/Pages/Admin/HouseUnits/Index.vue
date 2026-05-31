<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Modal from '@/Components/Modal.vue';
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
const isDetailModalOpen = ref(false);
const selectedUnit = ref(null);

const deleteForm = useForm({});

const openDeleteModal = (unit) => {
    selectedUnit.value = unit;
    isDeleteModalOpen.value = true;
};

const openDetailModal = (unit) => {
    selectedUnit.value = unit;
    isDetailModalOpen.value = true;
};

const closeModals = () => {
    isDeleteModalOpen.value = false;
    isDetailModalOpen.value = false;
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
};</script>

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
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Unit (Block-Floor-Number) <span class="text-[10px] text-gray-400 font-normal normal-case ml-2">(Click to view residents)</span></th>
                                    <th class="px-6 py-3 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(unit, index) in units?.data || []" :key="unit.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm text-gray-500">
                                        {{ (units?.current_page - 1) * units?.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 font-medium">
                                        <button @click="openDetailModal(unit)" class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline transition duration-150 text-left">
                                            {{ unit.formatted_unit }}
                                        </button>
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

        <!-- House Unit Residents Modal -->
        <Modal :show="isDetailModalOpen" @close="closeModals" maxWidth="2xl">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6 border-b pb-3">
                    <div>
                        <h3 class="text-xl font-black text-indigo-600">House Unit Information</h3>
                        <p class="text-xs text-gray-500 font-medium">Registered residents and owners for Unit <span class="font-bold text-gray-800">{{ selectedUnit?.formatted_unit }}</span></p>
                    </div>
                    <button @click="closeModals" class="text-gray-400 hover:text-gray-500 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div v-if="selectedUnit">
                    <div v-if="selectedUnit.residents && selectedUnit.residents.length > 0" class="space-y-6">
                        <!-- Residents List -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="res in selectedUnit.residents" :key="res.id" class="border border-gray-100 rounded-2xl p-4 bg-gray-50/50 hover:bg-gray-50 transition duration-150 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <!-- Header Card -->
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-bold text-base leading-tight">
                                                <Link :href="route('admin.residents.index', { search: res.name })" class="text-gray-900 hover:text-indigo-600 hover:underline transition duration-150" title="Click to view in residents list">
                                                    {{ res.name }}
                                                </Link>
                                            </h4>
                                            <p class="text-[10px] font-black uppercase tracking-wider mt-1"
                                               :class="{
                                                   'text-indigo-600': res.type === 'owner',
                                                   'text-blue-600': res.type === 'tenant',
                                                   'text-teal-600': res.type === 'family'
                                               }"
                                            >
                                               {{ res.type }}
                                            </p>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-full border tracking-widest"
                                            :class="res.status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'">
                                            {{ res.status }}
                                        </span>
                                    </div>

                                    <!-- Details -->
                                    <div class="space-y-1.5 text-xs text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            <span class="font-medium text-gray-800">{{ res.phone }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            <span class="font-medium text-gray-800 break-all">{{ res.email }}</span>
                                        </div>
                                        <div class="flex items-center gap-2" v-if="res.ic_number">
                                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                            <span class="font-medium text-gray-800">{{ res.ic_number }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-else class="flex flex-col items-center justify-center p-8 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                        <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p class="text-sm font-semibold text-gray-600">No Registered Residents</p>
                        <p class="text-xs text-gray-400 mt-1">There are currently no residents registered for this unit.</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-6 flex justify-end">
                    <button
                        @click="closeModals"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm transition-colors duration-150 shadow-md shadow-indigo-100"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
