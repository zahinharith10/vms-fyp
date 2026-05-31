<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    guards: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce(function (value) {
    router.get(route('admin.guards.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const isDeleteModalOpen = ref(false);
const selectedGuard = ref(null);

const deleteForm = useForm({});

const openDeleteModal = (guard) => {
    selectedGuard.value = guard;
    isDeleteModalOpen.value = true;
};

const closeModals = () => {
    isDeleteModalOpen.value = false;
    setTimeout(() => {
        selectedGuard.value = null;
    }, 200);
};

const confirmDelete = () => {
    if (selectedGuard.value) {
        deleteForm.delete(route('admin.guards.destroy', selectedGuard.value.id), {
            onSuccess: () => closeModals(),
        });
    }
};
</script>

<template>
    <Head title="Manage Guards" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Guards</h2>
        </template>

        <template #actions>
            <div class="flex gap-4 items-center">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search guards..." 
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm w-64"
                />
                <div class="flex gap-2">
                    <a :href="route('guard.login')" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-500 text-sm flex items-center transition duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Guard Login Portal
                </a>
                    <Link :href="route('admin.guards.create')" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 font-bold text-sm transition duration-150">
                        Add New Guard
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Shift Hours Reference Card -->
                <div class="bg-indigo-50/60 border border-indigo-100/80 rounded-2xl p-4 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 transition duration-200">
                    <div>
                        <h3 class="text-sm font-black text-indigo-900 flex items-center gap-1.5 uppercase tracking-wide">
                            ⏰ Shift Hours Reference
                        </h3>
                        <p class="text-xs text-indigo-700 font-medium mt-0.5">Quick reference guide for guard scheduling shifts.</p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-[11px] font-bold text-gray-700">
                        <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-gray-100 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span>Morning:</span>
                            <span class="text-gray-500 font-extrabold">07:00 – 15:00 (7 AM - 3 PM)</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-gray-100 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                            <span>Afternoon:</span>
                            <span class="text-gray-500 font-extrabold">15:00 – 23:00 (3 PM - 11 PM)</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-gray-100 shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <span>Night:</span>
                            <span class="text-gray-500 font-extrabold">23:00 – 07:00 (11 PM - 7 AM)</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name / ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="(guards?.data || []).length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No guards found.</td>
                                    </tr>
                                    <tr v-for="guard in guards?.data || []" :key="guard.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <img v-if="guard.photo" :src="'/storage/' + guard.photo" class="h-10 w-10 rounded-full object-cover" alt="Photo">
                                            <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ guard.name }}</div>
                                            <div class="text-xs text-gray-500">{{ guard.employee_id }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">{{ guard.email }}</div>
                                            <div class="text-xs text-gray-500">{{ guard.phone }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="s in (Array.isArray(guard.shift) ? guard.shift : [guard.shift])" :key="s" class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-black uppercase tracking-wider rounded bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                    {{ s }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                                :class="guard.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                                {{ guard.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <Link :href="route('admin.guards.edit', guard.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                            <button @click="openDeleteModal(guard)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <Pagination class="mt-6" :links="guards.links" />
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Guard"
            :message="'Are you sure you want to delete ' + selectedGuard?.name + '? This will permanently remove their employment record and system access.'"
            :loading="deleteForm.processing"
            @close="closeModals"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
