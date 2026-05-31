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
    unit_number: '', // Optional for delivery
    host_name: '',
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
    if (!currentDescriptor.value) return;
    
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

const submitDelivery = async () => {
    if (!currentDescriptor.value) return;
    
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
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">IC / ID Number</label>
                                        <input v-model="visitorForm.ic_number" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold dark:placeholder-gray-500" placeholder="Enter IC Number">
                                        <div v-if="visitorForm.errors.ic_number" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.ic_number }}</div>
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
                                        <textarea v-model="visitorForm.purpose" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" rows="2"></textarea>
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
                                            <input v-model="deliveryForm.company" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold dark:placeholder-gray-500" placeholder="Lalamove / Grab">
                                            <div v-if="deliveryForm.errors.company" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.company }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">IC / ID Number</label>
                                        <input v-model="deliveryForm.ic_number" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                        <div v-if="deliveryForm.errors.ic_number" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.ic_number }}</div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Vehicle No.</label>
                                            <input v-model="deliveryForm.vehicle_number" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                            <div v-if="deliveryForm.errors.vehicle_number" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.vehicle_number }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Unit (Opt)</label>
                                            <select v-model="deliveryForm.unit_number" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold px-2 py-2 text-sm">
                                                <option value="">Select Unit (Optional)</option>
                                                <option v-for="unit in units" :key="unit.id" :value="unit.formatted_unit">
                                                    {{ unit.formatted_unit }}
                                                </option>
                                            </select>
                                            <div v-if="deliveryForm.errors.unit_number" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.unit_number }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Person to Meet (Host Name - Opt)</label>
                                        <input v-model="deliveryForm.host_name" type="text" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold dark:placeholder-gray-500" placeholder="Who are you delivering to?">
                                        <div v-if="deliveryForm.errors.host_name" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.host_name }}</div>
                                    </div>
                                </div>
                                <button 
                                    @click="submitDelivery"
                                    :disabled="!isFaceDetected || deliveryForm.processing"
                                    class="mt-8 w-full bg-orange-600 hover:bg-orange-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-100 dark:shadow-none transition-all disabled:opacity-50"
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
