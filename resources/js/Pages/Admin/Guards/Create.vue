<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const citizenType = ref('citizen');
const countryOfOrigin = ref('');

const form = useForm({
    name: '',
    employee_id: '',
    ic_number: '',
    phone: '',
    address: '',
    email: '',
    password: '',
    shift: ['Morning'],
    status: 'Active',
    photo: null,
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
    if (!confirm('Are you sure you want to save this guard?')) return;
    form.post(route('admin.guards.store'));
};
</script>

<template>
    <Head title="Add Guard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Guard</h2>
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

                                <!-- Employee ID (Auto-generated) -->
                                <!-- <div>
                                    <label class="block text-sm font-medium text-gray-700">Employee ID</label>
                                    <input v-model="form.employee_id" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.employee_id" class="text-red-600 text-sm mt-1">{{ form.errors.employee_id }}</div>
                                </div> -->

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email Address (Login)</label>
                                    <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                                </div>

                                <!-- Password -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Password</label>
                                    <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                                </div>

                                <!-- Identity Type Toggle + IC/Passport -->
                                <div>
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

                                <div v-if="citizenType === 'international'">
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

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                    <div v-if="form.errors.phone" class="text-red-600 text-sm mt-1">{{ form.errors.phone }}</div>
                                </div>

                                <!-- Shift -->
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Shift Assignment(s)</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <label class="flex items-start p-2.5 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                            <input type="checkbox" v-model="form.shift" value="Morning" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                            <div class="ml-2.5">
                                                <span class="block text-xs font-bold text-gray-800">Morning Shift</span>
                                                <span class="block text-[10px] text-gray-500 font-medium">07:00 – 15:00<br>(7:00 AM - 3:00 PM)</span>
                                            </div>
                                        </label>
                                        <label class="flex items-start p-2.5 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                            <input type="checkbox" v-model="form.shift" value="Afternoon" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                            <div class="ml-2.5">
                                                <span class="block text-xs font-bold text-gray-800">Afternoon Shift</span>
                                                <span class="block text-[10px] text-gray-500 font-medium">15:00 – 23:00<br>(3:00 PM - 11:00 PM)</span>
                                            </div>
                                        </label>
                                        <label class="flex items-start p-2.5 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                            <input type="checkbox" v-model="form.shift" value="Night" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                            <div class="ml-2.5">
                                                <span class="block text-xs font-bold text-gray-800">Night Shift</span>
                                                <span class="block text-[10px] text-gray-500 font-medium">23:00 – 07:00<br>(11:00 PM - 7:00 AM)</span>
                                            </div>
                                        </label>
                                    </div>
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
                                <input type="file" @input="form.photo = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                <div v-if="form.errors.photo" class="text-red-600 text-sm mt-1">{{ form.errors.photo }}</div>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Link :href="route('admin.guards.index')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 disabled:opacity-50">Save Guard</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
