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
    // Ideally we should still check for duplicates, effectively preventing double registration
    // We might need a public API for this or just skip client-side check if insecure.
    // For now, let's skip client-side dup check on public page to avoid exposing all user data,
    // OR we can make a specific API that only returns "Match/No Match" without names.
    // Let's keep it simple and skip client-side check for now, trusting the backend unique phone constraint.
    // But face uniqueness is harder. 
    // Let's leave it for now.
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

                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">IC Number</label>
                        <input 
                            v-model="form.ic_number"
                            type="text" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                            placeholder="Enter IC/Passport Number"
                            required
                        />
                        <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
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
