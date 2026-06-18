<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    personnel: Object,
});

const detectType = (ic) => ic && /^\d{6}-\d{2}-\d{4}$/.test(ic) ? 'citizen' : 'international';
const citizenType = ref(detectType(props.personnel?.ic_number || ''));
const countryOfOrigin = ref('');

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

// IC input mask
const formatIC = (e) => {
    let digits = e.target.value.replace(/\D/g, '').slice(0, 12);
    let masked = digits;
    if (digits.length > 6) masked = digits.slice(0, 6) + '-' + digits.slice(6);
    if (digits.length > 8) masked = digits.slice(0, 6) + '-' + digits.slice(6, 8) + '-' + digits.slice(8);
    form.ic_number = masked;
};

const validateForm = () => {
    let isValid = true;
    form.clearErrors();

    // Validate phone number (Malaysian format)
    const phoneRegex = /^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/;
    if (!form.phone) {
        form.setError('phone', 'The phone number field is required.');
        isValid = false;
    } else if (!phoneRegex.test(form.phone)) {
        form.setError('phone', 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789).');
        isValid = false;
    }

    // Validate IC / Passport number
    if (citizenType.value === 'citizen') {
        const icRegex = /^(?:\d{6}-\d{2}-\d{4}|\d{12})$/;
        if (!form.ic_number) {
            form.setError('ic_number', 'The IC number field is required.');
            isValid = false;
        } else if (!icRegex.test(form.ic_number)) {
            form.setError('ic_number', 'The IC Number must be a valid Malaysian IC (e.g. 950101-14-1234).');
            isValid = false;
        }
    } else {
        const passportRegex = /^[a-zA-Z0-9]{6,20}$/;
        if (!form.ic_number) {
            form.setError('ic_number', 'The Passport number field is required.');
            isValid = false;
        } else if (!passportRegex.test(form.ic_number)) {
            form.setError('ic_number', 'The Passport Number must be 6-20 alphanumeric characters.');
            isValid = false;
        }
    }

    return isValid;
};

const submit = () => {
    if (!validateForm()) return;
    if (!confirm('Are you sure you want to update this delivery personnel?')) return;
    form.post(route('admin.delivery.personnel.update', props.personnel.id), {
        forceFormData: true,
    });
};

const companies = [
    'GrabFood', 'ShopeeFood', 'FoodPanda', 'Lalamove', 'J&T Express', 
    'PosLaju', 'DHL', 'NinjaVan', 'Pizza Hut', "Domino's", "McDonald's", 'Other'
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

                                <!-- Identity Type Toggle + IC/Passport -->
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Identity Type</label>
                                    <div class="flex rounded-md overflow-hidden border border-gray-300 mb-2">
                                        <button type="button" @click="citizenType = 'citizen'; form.ic_number = ''; countryOfOrigin = ''"
                                            class="flex-1 py-1.5 text-xs font-bold uppercase tracking-wider transition-all"
                                            :class="citizenType === 'citizen' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                                            🇲🇾 Malaysian / PR
                                        </button>
                                        <button type="button" @click="citizenType = 'international'; form.ic_number = ''; countryOfOrigin = ''"
                                            class="flex-1 py-1.5 text-xs font-bold uppercase tracking-wider transition-all border-l border-gray-300"
                                            :class="citizenType === 'international' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                                            🌍 International
                                        </button>
                                    </div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        {{ citizenType === 'citizen' ? 'IC Number' : 'Passport Number' }}
                                    </label>
                                    <input v-if="citizenType === 'citizen'"
                                        :value="form.ic_number" @input="formatIC"
                                        type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="e.g. 950101-14-1234" maxlength="14" required />
                                    <input v-else
                                        v-model="form.ic_number"
                                        type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter Passport Number" required />
                                    <p v-if="citizenType === 'citizen'" class="text-xs text-gray-400 mt-1">Format: ######-##-#### (12 digits)</p>
                                    <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
                                </div>

                                <div v-if="citizenType === 'international'" class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Country of Origin</label>
                                    <select v-model="countryOfOrigin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select Country</option>
                                        <option>Australia</option><option>Bangladesh</option><option>Brunei</option>
                                        <option>Cambodia</option><option>Canada</option><option>China</option>
                                        <option>France</option><option>Germany</option><option>India</option>
                                        <option>Indonesia</option><option>Iran</option><option>Japan</option>
                                        <option>South Korea</option><option>Kuwait</option><option>Laos</option>
                                        <option>Myanmar</option><option>Nepal</option><option>Nigeria</option>
                                        <option>Oman</option><option>Pakistan</option><option>Philippines</option>
                                        <option>Qatar</option><option>Russia</option><option>Saudi Arabia</option>
                                        <option>Singapore</option><option>South Africa</option><option>Spain</option>
                                        <option>Sri Lanka</option><option>Thailand</option><option>Turkey</option>
                                        <option>UAE</option><option>UK</option><option>Ukraine</option>
                                        <option>USA</option><option>Vietnam</option>
                                    </select>
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
