<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import FaceCapture from '@/Components/FaceCapture.vue';
import { ref, onMounted } from 'vue';
import { FaceMatcher, LabeledFaceDescriptors } from 'face-api.js';
import axios from 'axios';

const props = defineProps({
    phone: String,
    email: String,
});

const citizenType = ref('citizen'); // 'citizen' or 'international'
const countryOfOrigin = ref('');

const form = useForm({
    name: '',
    phone: props.phone,
    email: props.email || '',
    ic_number: '',
    vehicle_number: '',
    face_descriptor: null,
    photo: null,
});

const currentDescriptor = ref(null);
const isFaceDetected = ref(false);
const duplicateError = ref(null);
let faceMatcher = null;

onMounted(async () => {
    // Skip client-side dup check on public page
});

const onFaceDetected = (detection) => {
    if (detection) {
        currentDescriptor.value = Array.from(detection.descriptor);
        isFaceDetected.value = true;
    } else {
        isFaceDetected.value = false;
        currentDescriptor.value = null;
    }
};

const faceCaptureRef = ref(null);

// IC input mask: ######-##-####
const formatIC = (e) => {
    let digits = e.target.value.replace(/\D/g, '').slice(0, 12);
    let masked = digits;
    if (digits.length > 6) masked = digits.slice(0, 6) + '-' + digits.slice(6);
    if (digits.length > 8) masked = digits.slice(0, 6) + '-' + digits.slice(6, 8) + '-' + digits.slice(8);
    form.ic_number = masked;
};

const onCitizenTypeChange = () => {
    form.ic_number = '';
    countryOfOrigin.value = '';
};

const captureAndSubmit = async () => {
    if (!currentDescriptor.value) return;
    
    form.face_descriptor = currentDescriptor.value;
    
    if (faceCaptureRef.value) {
        const photo = await faceCaptureRef.value.getSnapshot();
        if (photo) {
            form.photo = photo;
        }
    }
    
    form.post(route('visitor.store'));
};
</script>

<template>
    <Head title="Register Visitor" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
         <div>
            <img src="/Logo.png" class="w-40 h-auto" />
        </div>

        <div class="w-full sm:max-w-2xl mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
             <h2 class="text-xl font-bold text-center mb-6 text-gray-800">New Visitor Registration</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left: Form -->
                <div>
                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">Phone Information</label>
                        <input 
                            v-model="form.phone"
                            type="text" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                            placeholder="e.g. 0123456789"
                        />
                        <div v-if="form.errors.phone" class="text-red-600 text-sm mt-1">{{ form.errors.phone }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">Full Name</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                            placeholder="Enter your name"
                        />
                        <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">Email Address</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1 bg-gray-50"
                            placeholder="Enter your email"
                            readonly
                        />
                        <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                    </div>

                    <!-- Citizen Type Toggle -->
                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700 mb-1">Identity Type</label>
                        <div class="flex rounded-md overflow-hidden border border-gray-300">
                            <button
                                type="button"
                                @click="citizenType = 'citizen'; onCitizenTypeChange()"
                                class="flex-1 py-2 text-xs font-bold uppercase tracking-wider transition-all"
                                :class="citizenType === 'citizen' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                            >🇲🇾 Malaysian / PR</button>
                            <button
                                type="button"
                                @click="citizenType = 'international'; onCitizenTypeChange()"
                                class="flex-1 py-2 text-xs font-bold uppercase tracking-wider transition-all border-l border-gray-300"
                                :class="citizenType === 'international' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                            >🌍 International</button>
                        </div>
                    </div>

                    <!-- IC / Passport Field -->
                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">
                            {{ citizenType === 'citizen' ? 'IC Number' : 'Passport Number' }}
                        </label>
                        <input 
                            v-if="citizenType === 'citizen'"
                            :value="form.ic_number"
                            @input="formatIC"
                            type="text" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                            placeholder="e.g. 950101-14-1234"
                            maxlength="14"
                            required
                        />
                        <input 
                            v-else
                            v-model="form.ic_number"
                            type="text" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                            placeholder="Enter Passport Number"
                            required
                        />
                        <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
                        <p v-if="citizenType === 'citizen'" class="text-xs text-gray-400 mt-1">Format: ######-##-#### (12 digits)</p>
                    </div>

                    <!-- Country of Origin (International only) -->
                    <div v-if="citizenType === 'international'" class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">Country of Origin</label>
                        <select
                            v-model="countryOfOrigin"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                        >
                            <option value="">Select Country</option>
                            <option>Afghanistan</option><option>Albania</option><option>Algeria</option>
                            <option>Australia</option><option>Austria</option><option>Bangladesh</option>
                            <option>Belgium</option><option>Brazil</option><option>Brunei</option>
                            <option>Cambodia</option><option>Canada</option><option>China</option>
                            <option>Denmark</option><option>Egypt</option><option>Finland</option>
                            <option>France</option><option>Germany</option><option>Ghana</option>
                            <option>Hong Kong</option><option>India</option><option>Indonesia</option>
                            <option>Iran</option><option>Iraq</option><option>Ireland</option>
                            <option>Italy</option><option>Japan</option><option>Jordan</option>
                            <option>Kenya</option><option>South Korea</option><option>Kuwait</option>
                            <option>Laos</option><option>Lebanon</option><option>Libya</option>
                            <option>Maldives</option><option>Mexico</option><option>Morocco</option>
                            <option>Myanmar</option><option>Nepal</option><option>Netherlands</option>
                            <option>New Zealand</option><option>Nigeria</option><option>Norway</option>
                            <option>Oman</option><option>Pakistan</option><option>Palestine</option>
                            <option>Philippines</option><option>Poland</option><option>Portugal</option>
                            <option>Qatar</option><option>Russia</option><option>Saudi Arabia</option>
                            <option>Singapore</option><option>Somalia</option><option>South Africa</option>
                            <option>Spain</option><option>Sri Lanka</option><option>Sudan</option>
                            <option>Sweden</option><option>Switzerland</option><option>Syria</option>
                            <option>Taiwan</option><option>Thailand</option><option>Turkey</option>
                            <option>UAE</option><option>Uganda</option><option>UK</option>
                            <option>Ukraine</option><option>USA</option><option>Vietnam</option>
                            <option>Yemen</option><option>Zimbabwe</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">Vehicle Plate Number</label>
                        <input 
                            v-model="form.vehicle_number"
                            type="text" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1 uppercase"
                            placeholder="e.g. ABC1234"
                            required
                        />
                        <div v-if="form.errors.vehicle_number" class="text-red-600 text-sm mt-1">{{ form.errors.vehicle_number }}</div>
                    </div>

                    <div class="mt-6">
                        <p class="text-sm text-gray-600 mb-2">Instructions:</p>
                        <ul class="list-disc list-inside text-sm text-gray-600">
                            <li>Position face in the camera frame.</li>
                            <li>Wait for the green box.</li>
                            <li>Click "Register" to save.</li>
                        </ul>
                    </div>
                    
                    <button 
                        @click="captureAndSubmit"
                        :disabled="!isFaceDetected || !form.name || form.processing"
                        class="mt-6 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed w-full md:w-auto"
                    >
                        {{ form.processing ? 'Saving...' : 'Register' }}
                    </button>
                </div>

                <!-- Right: Camera -->
                <div class="flex flex-col items-center justify-center bg-gray-50 rounded p-4">
                    <h3 class="text-lg font-medium mb-4">Face Scan</h3>
                    <FaceCapture ref="faceCaptureRef" @face-detected="onFaceDetected" />
                    <div class="mt-2 text-sm" :class="isFaceDetected ? 'text-green-600 font-bold' : 'text-red-500'">
                        {{ isFaceDetected ? 'Face Detected' : 'No Face Detected' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
