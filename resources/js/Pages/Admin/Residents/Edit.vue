<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    resident: Object,
    units: Array,
});

const form = useForm({
    name: props.resident.name,
    phone: props.resident.phone,
    email: props.resident.email,
    ic_number: props.resident.ic_number,
    type: props.resident.type,
    status: props.resident.status,
    house_unit_id: props.resident.house_unit_id,
    password: '',
});

const submit = () => {
    form.put(route('admin.residents.update', props.resident.id));
};
</script>

<template>
    <Head title="Edit Resident" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Resident</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="max-w-lg">
                             <!-- Name -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Resident Name</label>
                                <input v-model="form.name" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required />
                                <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>

                            <!-- House Unit -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">House Unit</label>
                                <select v-model="form.house_unit_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required>
                                    <option value="" disabled>Select Unit</option>
                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                        {{ unit.formatted_unit }}
                                    </option>
                                </select>
                                <div v-if="form.errors.house_unit_id" class="text-red-600 text-sm mt-1">{{ form.errors.house_unit_id }}</div>
                            </div>

                            <!-- Resident Type -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Type</label>
                                <select v-model="form.type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1">
                                    <option value="owner">Owner</option>
                                    <option value="tenant">Tenant</option>
                                    <option value="family">Family Member</option>
                                </select>
                                <div v-if="form.errors.type" class="text-red-600 text-sm mt-1">{{ form.errors.type }}</div>
                            </div>

                            <!-- Status -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Account Status</label>
                                <select v-model="form.status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">{{ form.errors.status }}</div>
                                <p class="text-xs text-gray-500 mt-1">Inactive residents cannot log in to the system.</p>
                            </div>

                            <!-- Phone -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Phone Number</label>
                                <input v-model="form.phone" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required />
                                <div v-if="form.errors.phone" class="text-red-600 text-sm mt-1">{{ form.errors.phone }}</div>
                            </div>

                            <!-- IC Number -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">IC Number/Passport</label>
                                <input v-model="form.ic_number" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" />
                                <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Email Address (Optional)</label>
                                <input v-model="form.email" type="email" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" />
                                <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Reset Password (Optional)</label>
                                <input v-model="form.password" type="password" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" placeholder="Leave blank to keep current password" />
                                <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                            </div>

                            <div class="flex items-center gap-4 mt-6">
                                <button type="submit" class="bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 px-4 py-2" :disabled="form.processing">
                                    Save Changes
                                </button>
                                <Link :href="route('admin.residents.index')" class="text-gray-600 underline text-sm">Cancel</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
