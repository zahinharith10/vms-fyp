<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    guard: Object,
});

const form = useForm({
    _method: 'put',
    name: props.guard.name,
    employee_id: props.guard.employee_id,
    ic_number: props.guard.ic_number,
    phone: props.guard.phone,
    address: props.guard.address,
    email: props.guard.email,
    password: '',
    shift: props.guard.shift,
    status: props.guard.status,
    photo: null,
});

const submit = () => {
    form.post(route('admin.guards.update', props.guard.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Edit Guard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Guard: {{ guard.name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                                </div>

                                <!-- Employee ID (Read-only) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Employee ID</label>
                                    <input v-model="form.employee_id" type="text" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 cursor-not-allowed" />
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email Address (Login)</label>
                                    <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                                </div>

                                <!-- Password -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Password (Leave blank to keep current)</label>
                                    <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                                </div>

                                <!-- IC Number -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">IC Number / Passport</label>
                                    <input v-model="form.ic_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.phone" class="text-red-600 text-sm mt-1">{{ form.errors.phone }}</div>
                                </div>

                                <!-- Shift -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Shift</label>
                                    <select v-model="form.shift" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="Morning">Morning</option>
                                        <option value="Night">Night</option>
                                        <option value="Rotating">Rotating</option>
                                    </select>
                                    <div v-if="form.errors.shift" class="text-red-600 text-sm mt-1">{{ form.errors.shift }}</div>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                    <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">{{ form.errors.status }}</div>
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                <div v-if="form.errors.address" class="text-red-600 text-sm mt-1">{{ form.errors.address }}</div>
                            </div>

                            <!-- Photo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Photo</label>
                                <div v-if="guard.photo" class="mb-2">
                                    <img :src="'/storage/' + guard.photo" class="h-20 w-20 rounded object-cover" />
                                </div>
                                <input type="file" @input="form.photo = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                <div v-if="form.errors.photo" class="text-red-600 text-sm mt-1">{{ form.errors.photo }}</div>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Link :href="route('admin.guards.index')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 disabled:opacity-50">Update Guard</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
