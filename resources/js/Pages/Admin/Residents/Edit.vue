<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    resident: Object,
    units: Array,
});

const detectType = (ic) => ic && /^\d{6}-\d{2}-\d{4}$/.test(ic) ? 'citizen' : 'international';
const citizenType = ref(detectType(props.resident?.ic_number || ''));
const countryOfOrigin = ref('');

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

// IC input mask
const formatIC = (e) => {
    let digits = e.target.value.replace(/\D/g, '').slice(0, 12);
    let masked = digits;
    if (digits.length > 6) masked = digits.slice(0, 6) + '-' + digits.slice(6);
    if (digits.length > 8) masked = digits.slice(0, 6) + '-' + digits.slice(6, 8) + '-' + digits.slice(8);
    form.ic_number = masked;
};

const submit = () => {
    if (!confirm('Are you sure you want to update this resident?')) return;
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

                            <!-- Identity Type Toggle + IC/Passport -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700 mb-1">Identity Type</label>
                                <div class="flex rounded-md overflow-hidden border border-gray-300 mb-3">
                                    <button type="button" @click="citizenType = 'citizen'; form.ic_number = ''; countryOfOrigin = ''"
                                        class="flex-1 py-2 text-xs font-bold uppercase tracking-wider transition-all"
                                        :class="citizenType === 'citizen' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                                        🇲🇾 Malaysian / PR
                                    </button>
                                    <button type="button" @click="citizenType = 'international'; form.ic_number = ''; countryOfOrigin = ''"
                                        class="flex-1 py-2 text-xs font-bold uppercase tracking-wider transition-all border-l border-gray-300"
                                        :class="citizenType === 'international' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                                        🌍 International
                                    </button>
                                </div>
                                <label class="block font-medium text-sm text-gray-700">
                                    {{ citizenType === 'citizen' ? 'IC Number' : 'Passport Number' }}
                                </label>
                                <input v-if="citizenType === 'citizen'"
                                    :value="form.ic_number" @input="formatIC"
                                    type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                                    placeholder="e.g. 950101-14-1234" maxlength="14" />
                                <input v-else
                                    v-model="form.ic_number"
                                    type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                                    placeholder="Enter Passport Number" />
                                <p v-if="citizenType === 'citizen'" class="text-xs text-gray-400 mt-1">Format: ######-##-#### (12 digits)</p>
                                <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
                            </div>

                            <div v-if="citizenType === 'international'" class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Country of Origin</label>
                                <select v-model="countryOfOrigin" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1">
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
