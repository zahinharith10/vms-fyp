<script setup>
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import FaceCapture from '@/Components/FaceCapture.vue';
import { ref } from 'vue';
import axios from 'axios';

defineProps({
    units: Array,
});

const registrationType = ref('visitor'); // 'visitor' or 'delivery'

const purposeOption = ref('Friends/Family');
const customPurpose = ref('');

// Citizen type state for each form
const visitorCitizenType = ref('citizen');
const visitorCountry = ref('');
const deliveryCitizenType = ref('citizen');
const deliveryCountry = ref('');
const deliveryOtherCompany = ref('');

// IC input mask: ######-##-####
const formatICFor = (formRef, e) => {
    let digits = e.target.value.replace(/\D/g, '').slice(0, 12);
    let masked = digits;
    if (digits.length > 6) masked = digits.slice(0, 6) + '-' + digits.slice(6);
    if (digits.length > 8) masked = digits.slice(0, 6) + '-' + digits.slice(6, 8) + '-' + digits.slice(8);
    formRef.ic_number = masked;
};

const deliveryMode = ref('single'); // 'single' or 'multi'
const tempDeliveryUnit = ref('');
const tempDeliveryHost = ref('');
const deliveryStops = ref([]); // Array of { unit_number, host_name }

const addDeliveryStop = () => {
    if (!tempDeliveryUnit.value || !tempDeliveryHost.value.trim()) return;

    if (deliveryStops.value.some(stop => stop.unit_number === tempDeliveryUnit.value)) {
        alert('This unit is already added.');
        return;
    }

    deliveryStops.value.push({
        unit_number: tempDeliveryUnit.value,
        host_name: tempDeliveryHost.value.trim(),
    });

    tempDeliveryUnit.value = '';
    tempDeliveryHost.value = '';
};

const removeDeliveryStop = (index) => {
    deliveryStops.value.splice(index, 1);
};

const visitorForm = useForm({
    name: '',
    email: '',
    phone: '',
    ic_number: '',
    vehicle_number: '',
    unit_number: '',
    host_name: '',
    purpose: '',
    face_descriptor: null,
    photo: null,
});

const deliveryForm = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    vehicle_number: '',
    ic_number: '',
    delivery_type: 'single',
    unit_number: '',
    unit_numbers: [],
    host_name: '',
    host_names: [],
    face_descriptor: null,
    photo: null,
});

const isFaceDetected = ref(false);
const currentDescriptor = ref(null);
const faceCaptureRef = ref(null);

const onFaceDetected = (detection) => {
    if (detection) {
        currentDescriptor.value = Array.from(detection.descriptor);
        isFaceDetected.value = true;
    } else {
        isFaceDetected.value = false;
        currentDescriptor.value = null;
    }
};

const submitVisitor = async () => {
    visitorForm.clearErrors();
    if (!currentDescriptor.value) return;

    visitorForm.purpose = purposeOption.value === 'Other'
        ? (customPurpose.value || 'Other')
        : purposeOption.value;

    visitorForm.face_descriptor = currentDescriptor.value;
    
    if (faceCaptureRef.value) {
        const photo = await faceCaptureRef.value.getSnapshot();
        if (photo) {
            visitorForm.photo = photo;
        }
    }

    // Use manual axios to handle JSON response with redirect info
    try {
        const response = await axios.post(route('guard.register.visitor'), visitorForm.data(), {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (response.data.redirect) {
            router.visit(response.data.redirect);
        }
    } catch (err) {
        if (err.response?.data?.errors) {
            visitorForm.setError(err.response.data.errors);
        } else {
            alert('An error occurred during registration.');
        }
    }
};

const validateDeliveryForm = () => {
    let isValid = true;
    deliveryForm.clearErrors();

    // Validate phone number (Malaysian format)
    const phoneRegex = /^(?:\+?6)?01[0-9](?:[- ]?\d){7,8}$/;
    if (!deliveryForm.phone) {
        deliveryForm.setError('phone', 'The phone number field is required.');
        isValid = false;
    } else if (!phoneRegex.test(deliveryForm.phone)) {
        deliveryForm.setError('phone', 'The phone number must be a valid Malaysian mobile number (e.g. 012-3456789).');
        isValid = false;
    }

    // Validate IC / Passport number
    if (deliveryCitizenType.value === 'citizen') {
        const icRegex = /^(?:\d{6}-\d{2}-\d{4}|\d{12})$/;
        if (!deliveryForm.ic_number) {
            deliveryForm.setError('ic_number', 'The IC number field is required.');
            isValid = false;
        } else if (!icRegex.test(deliveryForm.ic_number)) {
            deliveryForm.setError('ic_number', 'The IC Number must be a valid Malaysian IC (e.g. 950101-14-1234).');
            isValid = false;
        }
    } else {
        const passportRegex = /^[a-zA-Z0-9]{6,20}$/;
        if (!deliveryForm.ic_number) {
            deliveryForm.setError('ic_number', 'The Passport number field is required.');
            isValid = false;
        } else if (!passportRegex.test(deliveryForm.ic_number)) {
            deliveryForm.setError('ic_number', 'The Passport Number must be 6-20 alphanumeric characters.');
            isValid = false;
        }
    }

    return isValid;
};

const submitDelivery = async () => {
    deliveryForm.clearErrors();
    if (!validateDeliveryForm()) return;
    if (!currentDescriptor.value) return;

    // If "Others" selected, use the typed name instead
    if (deliveryForm.company === 'Others') {
        deliveryForm.company = deliveryOtherCompany.value.trim() || 'Others';
    }

    deliveryForm.delivery_type = deliveryMode.value;
    if (deliveryMode.value === 'single') {
        deliveryForm.unit_numbers = [];
        deliveryForm.host_names = [];
    } else {
        deliveryForm.unit_number = '';
        deliveryForm.host_name = 'Multi-stop'; // dummy to pass validation
        deliveryForm.unit_numbers = deliveryStops.value.map(stop => stop.unit_number);
        deliveryForm.host_names = deliveryStops.value.map(stop => stop.host_name);
    }

    deliveryForm.face_descriptor = currentDescriptor.value;
    
    if (faceCaptureRef.value) {
        const photo = await faceCaptureRef.value.getSnapshot();
        if (photo) {
            deliveryForm.photo = photo;
        }
    }

    try {
        const response = await axios.post(route('guard.register.delivery'), deliveryForm.data(), {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (response.data.redirect) {
            router.visit(response.data.redirect);
        }
    } catch (err) {
        if (err.response?.data?.errors) {
            deliveryForm.setError(err.response.data.errors);
        } else {
            alert('An error occurred during registration.');
        }
    }
};
</script>

<template>
    <Head title="Direct Registration" />

    <GuardAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Direct Entry Registration</h2>
        </template>

        <div class="max-w-4xl mx-auto">
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-800 transition-colors duration-200">
                <!-- Registration Type Selector -->
                <div class="flex border-b border-gray-100 dark:border-gray-800">
                    <button 
                        @click="registrationType = 'visitor'"
                        class="flex-1 py-4 text-sm font-black uppercase tracking-widest transition-all"
                        :class="registrationType === 'visitor' ? 'bg-indigo-600 text-white' : 'bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700/60'"
                    >
                        👤 Standard Visitor
                    </button>
                    <button 
                        @click="registrationType = 'delivery'"
                        class="flex-1 py-4 text-sm font-black uppercase tracking-widest transition-all"
                        :class="registrationType === 'delivery' ? 'bg-orange-600 text-white' : 'bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700/60'"
                    >
                        📦 Delivery Service
                    </button>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <!-- Left: Form Fields -->
                        <div>
                            <div v-if="registrationType === 'visitor'">
                                <h3 class="text-lg font-black text-gray-800 dark:text-white mb-6 uppercase tracking-tight">Visitor Particulars</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Full Name</label>
                                        <input v-model="visitorForm.name" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold dark:placeholder-gray-500" placeholder="As per IC/Passport">
                                        <div v-if="visitorForm.errors.name" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.name }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Email Address</label>
                                        <input v-model="visitorForm.email" type="email" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold dark:placeholder-gray-500" placeholder="visitor@example.com">
                                        <div v-if="visitorForm.errors.email" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.email }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Phone Number</label>
                                        <input v-model="visitorForm.phone" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold dark:placeholder-gray-500" placeholder="012-XXXXXXX">
                                        <div v-if="visitorForm.errors.phone" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.phone }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Identity Type</label>
                                        <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 mb-2">
                                            <button type="button" @click="visitorCitizenType = 'citizen'; visitorForm.ic_number = ''; visitorCountry = ''"
                                                class="flex-1 py-1.5 text-[10px] font-black uppercase tracking-wider transition-all"
                                                :class="visitorCitizenType === 'citizen' ? 'bg-indigo-600 text-white' : 'bg-gray-50 dark:bg-gray-800 text-gray-400'">
                                                🇲🇾 MY / PR
                                            </button>
                                            <button type="button" @click="visitorCitizenType = 'international'; visitorForm.ic_number = ''; visitorCountry = ''"
                                                class="flex-1 py-1.5 text-[10px] font-black uppercase tracking-wider transition-all border-l border-gray-200 dark:border-gray-700"
                                                :class="visitorCitizenType === 'international' ? 'bg-indigo-600 text-white' : 'bg-gray-50 dark:bg-gray-800 text-gray-400'">
                                                🌍 Intl
                                            </button>
                                        </div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                                            {{ visitorCitizenType === 'citizen' ? 'IC Number' : 'Passport Number' }}
                                        </label>
                                        <input v-if="visitorCitizenType === 'citizen'"
                                            :value="visitorForm.ic_number"
                                            @input="formatICFor(visitorForm, $event)"
                                            type="text"
                                            class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold dark:placeholder-gray-500"
                                            placeholder="e.g. 950101-14-1234" maxlength="14" />
                                        <input v-else
                                            v-model="visitorForm.ic_number"
                                            type="text"
                                            class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold dark:placeholder-gray-500"
                                            placeholder="Enter Passport Number" />
                                        <div v-if="visitorForm.errors.ic_number" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.ic_number }}</div>
                                    </div>
                                    <div v-if="visitorCitizenType === 'international'">
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Country of Origin</label>
                                        <select v-model="visitorCountry" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold text-sm">
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
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Vehicle Plate Number</label>
                                        <input v-model="visitorForm.vehicle_number" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold uppercase dark:placeholder-gray-500" placeholder="e.g. ABC1234" required>
                                        <div v-if="visitorForm.errors.vehicle_number" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.vehicle_number }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Destination Unit</label>
                                        <select v-model="visitorForm.unit_number" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" required>
                                            <option value="" disabled>Select Unit</option>
                                            <option v-for="unit in units" :key="unit.id" :value="unit.formatted_unit">
                                                {{ unit.formatted_unit }}
                                            </option>
                                        </select>
                                        <div v-if="visitorForm.errors.unit_number" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.unit_number }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Person to Meet (Host Name)</label>
                                        <input v-model="visitorForm.host_name" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold dark:placeholder-gray-500" placeholder="Who are you visiting?" required>
                                        <div v-if="visitorForm.errors.host_name" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.host_name }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Purpose of Visit</label>
                                        <select v-model="purposeOption" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold mb-2" required>
                                            <option value="Friends/Family">Friends / Family</option>
                                            <option value="Maintenance">Maintenance</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <div v-if="purposeOption === 'Other'" class="mt-1">
                                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Please specify</label>
                                            <input
                                                v-model="customPurpose"
                                                type="text"
                                                placeholder="Enter purpose here..."
                                                class="w-full bg-gray-50 dark:bg-gray-800 border-indigo-200 dark:border-indigo-900/40 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold"
                                                required
                                            />
                                        </div>
                                        <div v-if="visitorForm.errors.purpose" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.purpose }}</div>
                                    </div>
                                </div>
                                <button 
                                    @click="submitVisitor"
                                    :disabled="!isFaceDetected || visitorForm.processing"
                                    class="mt-8 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-indigo-100 dark:shadow-none transition-all disabled:opacity-50"
                                >
                                    {{ visitorForm.processing ? 'PROCESSING...' : '✅ REGISTER & REQUEST ENTRY' }}
                                </button>
                            </div>

                            <div v-else>
                                <h3 class="text-lg font-black text-orange-600 dark:text-orange-400 mb-6 uppercase tracking-tight">Personnel Particulars</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Full Name</label>
                                        <input v-model="deliveryForm.name" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                        <div v-if="deliveryForm.errors.name" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.name }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Email Address</label>
                                        <input v-model="deliveryForm.email" type="email" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold dark:placeholder-gray-500" placeholder="delivery@example.com">
                                        <div v-if="deliveryForm.errors.email" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.email }}</div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Phone</label>
                                            <input v-model="deliveryForm.phone" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                            <div v-if="deliveryForm.errors.phone" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.phone }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Company</label>
                                            <select v-model="deliveryForm.company" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold text-sm" required>
                                                <option value="">Select Company</option>
                                                <option value="Grab">Grab</option>
                                                <option value="Shopee">Shopee</option>
                                                <option value="FoodPanda">FoodPanda</option>
                                                <option value="Lalamove">Lalamove</option>
                                                <option value="DHL">DHL</option>
                                                <option value="PosLaju">PosLaju</option>
                                                <option value="J&T">J&T</option>
                                                <option value="Others">Others</option>
                                            </select>
                                            <input
                                                v-if="deliveryForm.company === 'Others'"
                                                v-model="deliveryOtherCompany"
                                                type="text"
                                                class="w-full mt-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold text-sm px-3 py-2"
                                                placeholder="Please specify company name"
                                            />
                                            <div v-if="deliveryForm.errors.company" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.company }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Identity Type</label>
                                        <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 mb-2">
                                            <button type="button" @click="deliveryCitizenType = 'citizen'; deliveryForm.ic_number = ''; deliveryCountry = ''"
                                                class="flex-1 py-1.5 text-[10px] font-black uppercase tracking-wider transition-all"
                                                :class="deliveryCitizenType === 'citizen' ? 'bg-orange-600 text-white' : 'bg-gray-50 dark:bg-gray-800 text-gray-400'">
                                                🇲🇾 MY / PR
                                            </button>
                                            <button type="button" @click="deliveryCitizenType = 'international'; deliveryForm.ic_number = ''; deliveryCountry = ''"
                                                class="flex-1 py-1.5 text-[10px] font-black uppercase tracking-wider transition-all border-l border-gray-200 dark:border-gray-700"
                                                :class="deliveryCitizenType === 'international' ? 'bg-orange-600 text-white' : 'bg-gray-50 dark:bg-gray-800 text-gray-400'">
                                                🌍 Intl
                                            </button>
                                        </div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                                            {{ deliveryCitizenType === 'citizen' ? 'IC Number' : 'Passport Number' }}
                                        </label>
                                        <input v-if="deliveryCitizenType === 'citizen'"
                                            :value="deliveryForm.ic_number"
                                            @input="formatICFor(deliveryForm, $event)"
                                            type="text"
                                            class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold"
                                            placeholder="e.g. 950101-14-1234" maxlength="14" />
                                        <input v-else
                                            v-model="deliveryForm.ic_number"
                                            type="text"
                                            class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold"
                                            placeholder="Enter Passport Number" />
                                        <div v-if="deliveryForm.errors.ic_number" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.ic_number }}</div>
                                    </div>
                                    <div v-if="deliveryCitizenType === 'international'">
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Country of Origin</label>
                                        <select v-model="deliveryCountry" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold text-sm">
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
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Vehicle No.</label>
                                        <input v-model="deliveryForm.vehicle_number" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                        <div v-if="deliveryForm.errors.vehicle_number" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.vehicle_number }}</div>
                                    </div>

                                    <!-- Single / Multi Toggle -->
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Delivery Type</p>
                                        <div class="grid grid-cols-2 gap-3">
                                            <button
                                                type="button"
                                                @click="deliveryMode = 'single'"
                                                class="flex flex-col items-center rounded-2xl border-2 p-3 transition-all"
                                                :class="deliveryMode === 'single'
                                                    ? 'border-orange-500 bg-orange-50 dark:bg-orange-950 dark:border-orange-600 shadow-md ring-2 ring-orange-200 dark:ring-orange-850'
                                                    : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:border-orange-350 dark:hover:border-orange-600'"
                                            >
                                                <span class="text-xl mb-1">📦</span>
                                                <span class="text-xs font-black uppercase" :class="deliveryMode === 'single' ? 'text-orange-700 dark:text-orange-300' : 'text-gray-600 dark:text-gray-400'">Single</span>
                                                <span class="text-[9px] mt-0.5 text-gray-400 dark:text-gray-500">One unit only</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="deliveryMode = 'multi'"
                                                class="flex flex-col items-center rounded-2xl border-2 p-3 transition-all"
                                                :class="deliveryMode === 'multi'
                                                    ? 'border-orange-500 bg-orange-50 dark:bg-orange-950 dark:border-orange-600 shadow-md ring-2 ring-orange-200 dark:ring-orange-850'
                                                    : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:border-orange-350 dark:hover:border-orange-600'"
                                            >
                                                <span class="text-xl mb-1">🛒</span>
                                                <span class="text-xs font-black uppercase" :class="deliveryMode === 'multi' ? 'text-orange-700 dark:text-orange-300' : 'text-gray-600 dark:text-gray-400'">Many</span>
                                                <span class="text-[9px] mt-0.5 text-gray-400 dark:text-gray-500">Several units</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Conditional Inputs: Single Mode -->
                                    <div v-if="deliveryMode === 'single'" class="space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Destination Unit</label>
                                            <select v-model="deliveryForm.unit_number" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold px-2 py-2 text-sm">
                                                <option value="">Select Unit</option>
                                                <option v-for="unit in units" :key="unit.id" :value="unit.formatted_unit">
                                                    {{ unit.formatted_unit }}
                                                </option>
                                            </select>
                                            <div v-if="deliveryForm.errors.unit_number" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.unit_number }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Person to Meet (Host Name)</label>
                                            <input v-model="deliveryForm.host_name" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold dark:placeholder-gray-500" placeholder="Who are you delivering to?">
                                            <div v-if="deliveryForm.errors.host_name" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.host_name }}</div>
                                        </div>
                                    </div>

                                    <!-- Conditional Inputs: Multi Mode -->
                                    <div v-else class="space-y-4">
                                        <div class="bg-orange-50/50 dark:bg-orange-950/20 border border-orange-100/60 dark:border-orange-900/30 rounded-2xl p-4 space-y-3">
                                            <p class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest">Add Destination Stops</p>
                                            
                                            <div>
                                                <label class="block text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Select Stop Unit</label>
                                                <select v-model="tempDeliveryUnit" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold text-xs">
                                                    <option value="">Select Unit</option>
                                                    <option v-for="unit in units" :key="unit.id" :value="unit.formatted_unit">
                                                        {{ unit.formatted_unit }}
                                                    </option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Resident to Visit</label>
                                                <input v-model="tempDeliveryHost" type="text" placeholder="Resident full name for this unit" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold text-xs">
                                            </div>

                                            <button
                                                type="button"
                                                @click="addDeliveryStop"
                                                :disabled="!tempDeliveryUnit || !tempDeliveryHost.trim()"
                                                class="w-full py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-black text-[10px] uppercase tracking-wider transition-all disabled:opacity-50"
                                            >
                                                ➕ ADD STOP
                                            </button>
                                        </div>

                                        <!-- Stops List -->
                                        <div class="space-y-2">
                                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                                Stops Added ({{ deliveryStops.length }})
                                            </p>
                                            <ul v-if="deliveryStops.length > 0" class="space-y-2">
                                                <li
                                                    v-for="(stop, index) in deliveryStops"
                                                    :key="stop.unit_number"
                                                    class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 dark:text-gray-200"
                                                >
                                                    <div class="flex flex-col">
                                                        <span class="text-orange-600 dark:text-orange-450 font-black">UNIT {{ stop.unit_number }}</span>
                                                        <span class="text-gray-400 dark:text-gray-500">👤 {{ stop.host_name }}</span>
                                                    </div>
                                                    <button type="button" class="text-red-500 font-black uppercase text-[10px]" @click="removeDeliveryStop(index)">
                                                        Remove
                                                    </button>
                                                </li>
                                            </ul>
                                            <p v-else class="text-xs text-gray-400 italic text-center py-2">No stops added yet. (Min 2 required)</p>
                                            
                                            <div v-if="deliveryForm.errors.unit_numbers" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.unit_numbers }}</div>
                                            <div v-if="deliveryForm.errors.host_names" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.host_names }}</div>
                                        </div>
                                    </div>
                                </div>
                                <button 
                                    @click="submitDelivery"
                                    :disabled="!isFaceDetected || deliveryForm.processing || (deliveryMode === 'multi' && deliveryStops.length < 2) || (deliveryMode === 'single' && (!deliveryForm.unit_number || !deliveryForm.host_name))"
                                    class="mt-8 w-full bg-orange-600 hover:bg-orange-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-100 dark:shadow-none transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ deliveryForm.processing ? 'PROCESSING...' : '✅ REGISTER & REQUEST ENTRY' }}
                                </button>
                            </div>
                        </div>

                        <!-- Right: Biometrics -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-3xl p-6 flex flex-col items-center border border-gray-100 dark:border-gray-700 transition-colors duration-200">
                             <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-6">Biometric Enrollment</h4>
                             
                             <div class="w-full max-w-sm bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-inner mb-6">
                                 <FaceCapture ref="faceCaptureRef" :allow-upload="false" @face-detected="onFaceDetected" />
                             </div>

                             <div class="text-center">
                                 <div v-if="isFaceDetected" class="flex items-center text-green-600 font-black text-sm uppercase">
                                     <span class="h-3 w-3 bg-green-500 rounded-full mr-2 animate-ping"></span> Face Locked
                                 </div>
                                 <div v-else class="text-red-400 font-black text-sm uppercase animate-pulse">
                                     Waiting for Face...
                                 </div>
                                 <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold mt-2 leading-relaxed">
                                     The visitor must look directly into the camera<br>to enroll their biometric ID.
                                 </p>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuardAuthenticatedLayout>
</template>
