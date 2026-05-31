<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import FaceCapture from '@/Components/FaceCapture.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    visit: Object,
    visitor: Object,
    hostName: String,
    qrCodeSvg: String,
});

// Check if visitor profile is completed
const isProfileComplete = computed(() => {
    return props.visitor && 
           props.visitor.photo && 
           props.visitor.ic_number && 
           props.visitor.face_descriptor && 
           props.visitor.vehicle_number && 
           props.visitor.vehicle_number !== '-';
});

const form = useForm({
    ic_number: props.visitor?.ic_number || '',
    vehicle_number: props.visitor?.vehicle_number && props.visitor.vehicle_number !== '-' ? props.visitor.vehicle_number : '',
    face_descriptor: null,
    photo: null,
});

const currentDescriptor = ref(null);
const isFaceDetected = ref(false);
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

const captureAndSubmit = async () => {
    if (!currentDescriptor.value) return;
    
    form.face_descriptor = currentDescriptor.value;
    
    if (faceCaptureRef.value) {
        const photo = await faceCaptureRef.value.getSnapshot();
        if (photo) {
            form.photo = photo;
        }
    }
    
    form.post(route('public.pass.complete', props.visit.qr_code_token), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Guest Entry Pass" />

    <div class="min-h-screen bg-gray-100 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <!-- Logo Header -->
        <div class="mb-6 flex flex-col items-center">
            <img src="/Logo.png" alt="Sri Ayu Apartment" class="w-40 h-auto" />
            <h1 class="text-2xl font-black text-gray-800 tracking-tighter uppercase italic mt-2">Sri Ayu Residency</h1>
        </div>

        <!-- 1. Active Pass View (Profile Completed) -->
        <div v-if="isProfileComplete" class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 relative">
            <!-- Glassmorphic Banner -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 p-6 text-white text-center relative overflow-hidden">
                <span class="absolute top-2 right-4 bg-white/20 text-xs px-3 py-1 rounded-full font-black tracking-widest uppercase">Pre-Approved</span>
                <span class="text-5xl block mb-2">🎫</span>
                <h2 class="text-xl font-bold uppercase tracking-wide">Digital Guest Pass</h2>
                <p class="text-xs text-emerald-100 mt-1">Check-in at gate is fully approved</p>
            </div>

            <!-- Pass Body -->
            <div class="p-6 text-center space-y-6">
                <!-- QR Code Block -->
                <div class="flex flex-col items-center justify-center p-4 bg-emerald-50 rounded-3xl border border-emerald-100">
                    <div class="w-64 h-64 flex items-center justify-center bg-white p-4 rounded-2xl shadow-sm" v-html="qrCodeSvg"></div>
                    <span class="text-xs font-black tracking-widest uppercase text-emerald-700 mt-4">Scan Code at Guardhouse</span>
                </div>

                <!-- Guest & Host details -->
                <div class="text-left space-y-3 bg-gray-50 p-5 rounded-2xl border border-gray-200">
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase">Guest Name</span>
                        <span class="text-sm font-black text-gray-800">{{ visitor.name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase">Guest Email</span>
                        <span class="text-sm font-black text-gray-800">{{ visitor.email }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase">Vehicle Number</span>
                        <span class="text-sm font-black text-gray-800 uppercase">{{ visitor.vehicle_number }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase">Host Unit</span>
                        <span class="text-sm font-black text-gray-800">{{ visit.unit_number }}</span>
                    </div>
                    <div class="flex justify-between pb-1">
                        <span class="text-xs font-bold text-gray-400 uppercase">Pre-Approved By</span>
                        <span class="text-sm font-black text-gray-800">{{ hostName }}</span>
                    </div>
                </div>

                <!-- Visitor Login Link inside the card -->
                <div class="py-2.5 border-y border-dashed border-gray-200/80 flex justify-between items-center text-xs px-2 my-4">
                    <span class="font-bold text-gray-500">Looking for your dashboard?</span>
                    <Link :href="route('welcome', { email: visitor.email })" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-black uppercase tracking-wider px-3.5 py-2 rounded-xl transition-all">
                        Log In
                    </Link>
                </div>

                <!-- Info Banner -->
                <div class="p-4 bg-indigo-50 text-indigo-800 rounded-2xl text-xs font-semibold leading-relaxed text-left border-l-4 border-indigo-500">
                    💡 **Gate Instruction:** When arriving at the gate, simply present this QR code to the scanner. The gate check-in will authorize automatically!
                </div>
            </div>
        </div>

        <!-- 2. Profile Registration View (Complete Details) -->
        <div v-else class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
            <!-- Banner -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 p-6 text-white text-center">
                <span class="text-5xl block mb-2">👋</span>
                <h2 class="text-xl font-bold uppercase tracking-wide">Welcome, {{ visitor.name }}!</h2>
                <p class="text-xs text-indigo-200 mt-1">Your visit to unit {{ visit.unit_number }} has been pre-approved by {{ hostName }}.</p>
            </div>

            <!-- Steps & Form -->
            <div class="p-6">
                <!-- Helper Alert -->
                <div class="mb-6 p-4 bg-indigo-50 text-indigo-900 border-l-4 border-indigo-500 rounded-r-2xl text-sm font-bold">
                    🛡️ **Selfie & Identity verification required:** Please capture a selfie (face verification) and enter your vehicle details below to activate your digital entry pass!
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column: Details -->
                    <div class="space-y-4">
                        <!-- IC Number -->
                        <div>
                            <label for="ic_number" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">IC/Passport Number</label>
                            <input 
                                id="ic_number" 
                                type="text" 
                                placeholder="Enter IC or Passport"
                                v-model="form.ic_number" 
                                class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 py-3 px-4" 
                                required 
                            />
                            <div v-if="form.errors.ic_number" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.ic_number }}</div>
                        </div>

                        <!-- Vehicle Plate -->
                        <div>
                            <label for="vehicle_number" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Vehicle Plate Number</label>
                            <input 
                                id="vehicle_number" 
                                type="text" 
                                placeholder="e.g. ABC1234"
                                v-model="form.vehicle_number" 
                                class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 py-3 px-4 uppercase" 
                                required 
                            />
                            <div v-if="form.errors.vehicle_number" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.vehicle_number }}</div>
                        </div>

                        <!-- Camera Instructions -->
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-2xl">
                            <h4 class="text-xs font-black text-gray-500 uppercase tracking-wider mb-2">📸 Camera Instructions</h4>
                            <ul class="list-disc list-inside text-xs text-gray-600 space-y-1">
                                <li>Ensure your face is clearly visible.</li>
                                <li>Look directly at the camera.</li>
                                <li>Wait for the green face detection indicator.</li>
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            @click="captureAndSubmit"
                            :disabled="!isFaceDetected || form.processing"
                            class="w-full inline-flex items-center justify-center px-6 py-4 bg-indigo-600 border border-transparent rounded-2xl font-black text-sm text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 transition shadow-lg shadow-indigo-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ form.processing ? 'Activating Pass...' : 'Activate My Pass' }}
                        </button>
                    </div>

                    <!-- Right Column: Camera Frame -->
                    <div class="flex flex-col items-center justify-center bg-gray-50 border border-gray-200 rounded-3xl p-6 min-h-[300px]">
                        <h3 class="text-xs font-black text-gray-500 uppercase tracking-wider mb-4">Face Scan Verification</h3>
                        <FaceCapture ref="faceCaptureRef" :allowUpload="false" @face-detected="onFaceDetected" />
                        <div class="mt-4 text-xs font-bold" :class="isFaceDetected ? 'text-green-600' : 'text-red-500'">
                            {{ isFaceDetected ? '✓ Face Ready' : '⚠ Waiting for Face...' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
