<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import FaceCapture from '@/Components/FaceCapture.vue';

const props = defineProps({
    phone: String,
    email: String
});

const form = useForm({
    name: '',
    email: props.email || '',
    company: '',
    phone: props.phone || '',
    vehicle_type: 'Motorcycle',
    vehicle_number: '',
    ic_number: '',
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

const captureAndSubmit = async () => {
    if (!currentDescriptor.value) return;
    
    form.face_descriptor = currentDescriptor.value;
    
    if (faceCaptureRef.value) {
        const photo = await faceCaptureRef.value.getSnapshot();
        if (photo) {
            form.photo = photo;
        }
    }
    
    form.post(route('delivery.store'));
};
</script>

<template>
    <Head title="Delivery Registration" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 pb-20">
        <div class="mb-8 mt-8">
            <Link href="/">
                <img src="/Logo.png" class="w-40 h-auto" />
            </Link>
        </div>

        <div class="w-full sm:max-w-5xl px-10 py-12 bg-white shadow-2xl overflow-hidden sm:rounded-[40px] border border-gray-100">
            <div class="flex items-center mb-10">
                <div class="h-16 w-16 bg-orange-100 rounded-2xl flex items-center justify-center text-3xl mr-6 transform -rotate-3 transition-transform hover:rotate-0 duration-300">
                    🚚
                </div>
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tighter uppercase italic leading-none">Join Delivery Fleet</h2>
                    <p class="text-orange-600 font-bold text-xs uppercase tracking-widest mt-1">Register for Resident Access</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-12">
                <!-- Left: Form -->
                <div class="md:col-span-12 lg:col-span-7 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Name -->
                        <div class="md:col-span-2">
                            <label for="name" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Driver Full Name</label>
                            <input id="name" type="text" v-model="form.name" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner transition-all duration-200" required autofocus />
                            <div v-if="form.errors.name" class="mt-2 text-xs text-red-500 font-black italic tracking-widest uppercase">{{ form.errors.name }}</div>
                        </div>

                        <!-- Company -->
                        <div>
                            <label for="company" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Delivery Company</label>
                            <select id="company" v-model="form.company" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner transition-all" required>
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
                            <div v-if="form.errors.company" class="mt-2 text-xs text-red-500 font-black italic tracking-widest uppercase">{{ form.errors.company }}</div>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Phone Number</label>
                            <input id="phone" type="text" v-model="form.phone" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner" required />
                            <div v-if="form.errors.phone" class="mt-2 text-xs text-red-500 font-black italic tracking-widest uppercase">{{ form.errors.phone }}</div>
                        </div>

                        <!-- Vehicle Type -->
                        <div>
                            <label for="vehicle_type" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Vehicle Type</label>
                            <select id="vehicle_type" v-model="form.vehicle_type" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner" required>
                                <option value="Motorcycle">Motorcycle</option>
                                <option value="Car">Car</option>
                                <option value="Van">Van</option>
                                <option value="Lorry">Lorry</option>
                            </select>
                        </div>

                        <!-- Vehicle Number -->
                        <div>
                            <label for="vehicle_number" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Plate Number</label>
                            <input id="vehicle_number" type="text" v-model="form.vehicle_number" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner uppercase italic" placeholder="e.g. ABC 1234" required />
                            <div v-if="form.errors.vehicle_number" class="mt-2 text-xs text-red-500 font-black italic tracking-widest uppercase">{{ form.errors.vehicle_number }}</div>
                        </div>

                        <!-- IC Number -->
                        <div class="md:col-span-2">
                            <label for="ic_number" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">IC / ID Number</label>
                            <input id="ic_number" type="text" v-model="form.ic_number" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner" required />
                            <div v-if="form.errors.ic_number" class="mt-2 text-xs text-red-500 font-black italic tracking-widest uppercase">{{ form.errors.ic_number }}</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Identity Verification -->
                <div class="md:col-span-12 lg:col-span-5 flex flex-col items-center justify-center bg-gray-50 rounded-[40px] p-8 border border-gray-100">
                    <h3 class="text-xl font-black text-gray-900 mb-2 uppercase tracking-tighter italic">Face Verification</h3>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-8 text-center">Identity is mandatory for security entrance</p>
                    
                    <div class="relative group cursor-pointer overflow-hidden rounded-[32px] border-4 transition-all duration-300 shadow-inner" :class="isFaceDetected ? 'border-green-400 shadow-green-100' : 'border-gray-200 shadow-gray-200'">
                        <FaceCapture ref="faceCaptureRef" @face-detected="onFaceDetected" />
                        
                        <div v-if="!isFaceDetected" class="absolute inset-0 bg-gray-900/40 backdrop-blur-[2px] flex items-center justify-center pointer-events-none transition-all duration-500">
                            <div class="flex flex-col items-center animate-pulse">
                                <span class="text-3xl mb-2">👤</span>
                                <span class="text-[10px] font-black text-white uppercase tracking-widest">Awaiting Detection</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center flex-col">
                        <div class="flex items-center space-x-2 bg-white px-6 py-3 rounded-full shadow-sm border border-gray-100">
                            <div class="h-2 w-2 rounded-full transition-colors duration-300" :class="isFaceDetected ? 'bg-green-500 animate-pulse' : 'bg-red-400'"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest transition-colors duration-300" :class="isFaceDetected ? 'text-green-700' : 'text-red-400'">
                                {{ isFaceDetected ? 'Identity Recognized' : 'Position Face Clearly' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-8 text-center space-y-4">
                        <p class="text-xs text-gray-400 font-bold mx-auto max-w-xs leading-relaxed italic">By registering, you agree to biometric identity verification at security checkpoints.</p>
                    </div>
                </div>
            </div>

            <div class="mt-12 pt-12 border-t border-gray-50 flex flex-col md:flex-row items-center justify-between gap-8">
                <Link href="/" class="text-xs font-black text-gray-400 uppercase tracking-widest hover:text-orange-600 transition-colors order-2 md:order-1 flex items-center">
                    <span class="mr-2 text-lg">←</span> Back to Access Login
                </Link>
                
                <button 
                    @click="captureAndSubmit"
                    :disabled="!isFaceDetected || !form.name || form.processing"
                    class="w-full md:w-auto inline-flex items-center justify-center px-16 py-5 bg-orange-600 border border-transparent rounded-[24px] font-black text-sm text-white uppercase tracking-widest hover:bg-orange-700 active:bg-orange-800 focus:outline-none focus:ring-4 focus:ring-orange-200 transition-all duration-300 shadow-2xl shadow-orange-100 disabled:opacity-30 disabled:cursor-not-allowed order-1 md:order-2 group"
                >
                    <span v-if="form.processing" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Verifying...
                    </span>
                    <span v-else class="flex items-center">
                        Complete Registration <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
