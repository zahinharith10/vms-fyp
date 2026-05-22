<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    personnel: Object,
});

const form = useForm({
    _method: 'put',
    name: props.personnel.name,
    company: props.personnel.company,
    vehicle_type: props.personnel.vehicle_type,
    vehicle_number: props.personnel.vehicle_number,
    phone: props.personnel.phone,
    ic_number: props.personnel.ic_number,
    photo: null,
    status: props.personnel.status,
});

const submit = () => {
    form.post(route('admin.delivery.personnel.update', props.personnel.id), {
        forceFormData: true,
    });
};

const companies = [
    'GrabFood', 'ShopeeFood', 'FoodPanda', 'Lalamove', 'J&T Express', 
    'PosLaju', 'DHL', 'NinjaVan', 'Pizza Hut', 'Domino\'s', 'McDonald\'s', 'Other'
];
</script>

<template>
    <Head title="Edit Delivery Personnel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit: {{ personnel.name }}</h2>
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

                                <!-- Company -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Company</label>
                                    <select v-model="form.company" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="" disabled>Select Company</option>
                                        <option v-for="c in companies" :key="c" :value="c">{{ c }}</option>
                                    </select>
                                    <div v-if="form.errors.company" class="text-red-600 text-sm mt-1">{{ form.errors.company }}</div>
                                </div>

                                <!-- Vehicle Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                                    <select v-model="form.vehicle_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="Motorcycle">Motorcycle</option>
                                        <option value="Car">Car</option>
                                        <option value="Van">Van</option>
                                        <option value="Lorry">Lorry</option>
                                    </select>
                                    <div v-if="form.errors.vehicle_type" class="text-red-600 text-sm mt-1">{{ form.errors.vehicle_type }}</div>
                                </div>

                                <!-- Vehicle Number -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Vehicle Number Plate</label>
                                    <input v-model="form.vehicle_number" type="text" placeholder="e.g. VAE 1234" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.vehicle_number" class="text-red-600 text-sm mt-1">{{ form.errors.vehicle_number }}</div>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.phone" class="text-red-600 text-sm mt-1">{{ form.errors.phone }}</div>
                                </div>

                                <!-- IC Number -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">IC Number / Passport</label>
                                    <input v-model="form.ic_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="Active">Active</option>
                                        <option value="Banned">Banned</option>
                                    </select>
                                    <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">{{ form.errors.status }}</div>
                                </div>
                            </div>

                            <!-- Photo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Photo</label>
                                <div v-if="personnel.photo" class="mb-2">
                                    <img :src="'/storage/' + personnel.photo" class="h-20 w-20 rounded object-cover" />
                                </div>
                                <input type="file" @input="form.photo = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                <div v-if="form.errors.photo" class="text-red-600 text-sm mt-1">{{ form.errors.photo }}</div>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Link :href="route('admin.delivery.personnel.index')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 disabled:opacity-50">Update Personnel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
