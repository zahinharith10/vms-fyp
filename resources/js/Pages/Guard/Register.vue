<script setup>
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import FaceCapture from '@/Components/FaceCapture.vue';
import { ref } from 'vue';
import axios from 'axios';

const visitorBlock = ref('');
const visitorFloor = ref('');
const visitorNumber = ref('');

const deliveryBlock = ref('');
const deliveryFloor = ref('');
const deliveryNumber = ref('');

const registrationType = ref('visitor'); // 'visitor' or 'delivery'

const visitorForm = useForm({
    name: '',
    phone: '',
    ic_number: '',
    vehicle_number: '',
    unit_number: '',
    purpose: '',
    face_descriptor: null,
    photo: null,
});

const deliveryForm = useForm({
    name: '',
    phone: '',
    company: '',
    vehicle_number: '',
    ic_number: '',
    unit_number: '', // Optional for delivery
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
    
    visitorForm.unit_number = `${visitorBlock.value} - ${visitorFloor.value} - ${visitorNumber.value}`;

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
    
    if (deliveryBlock.value && deliveryNumber.value) {
        deliveryForm.unit_number = `${deliveryBlock.value} - ${deliveryFloor.value} - ${deliveryNumber.value}`;
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Direct Entry Registration</h2>
        </template>

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                <!-- Registration Type Selector -->
                <div class="flex border-b border-gray-100">
                    <button 
                        @click="registrationType = 'visitor'"
                        class="flex-1 py-4 text-sm font-black uppercase tracking-widest transition-all"
                        :class="registrationType === 'visitor' ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'"
                    >
                        👤 Standard Visitor
                    </button>
                    <button 
                        @click="registrationType = 'delivery'"
                        class="flex-1 py-4 text-sm font-black uppercase tracking-widest transition-all"
                        :class="registrationType === 'delivery' ? 'bg-orange-600 text-white' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'"
                    >
                        📦 Delivery Service
                    </button>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <!-- Left: Form Fields -->
                        <div>
                            <div v-if="registrationType === 'visitor'">
                                <h3 class="text-lg font-black text-gray-800 mb-6 uppercase tracking-tight">Visitor Particulars</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Full Name</label>
                                        <input v-model="visitorForm.name" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" placeholder="As per IC/Passport">
                                        <div v-if="visitorForm.errors.name" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.name }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Phone Number</label>
                                        <input v-model="visitorForm.phone" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" placeholder="012-XXXXXXX">
                                        <div v-if="visitorForm.errors.phone" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.phone }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">IC / ID Number</label>
                                        <input v-model="visitorForm.ic_number" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" placeholder="Enter IC Number">
                                        <div v-if="visitorForm.errors.ic_number" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.ic_number }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Vehicle Plate Number</label>
                                        <input v-model="visitorForm.vehicle_number" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold uppercase" placeholder="e.g. ABC1234" required>
                                        <div v-if="visitorForm.errors.vehicle_number" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.vehicle_number }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Destination Unit</label>
                                        <div class="flex space-x-2">
                                            <input v-model="visitorBlock" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" placeholder="Block">
                                            <input v-model="visitorFloor" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" placeholder="Floor">
                                            <input v-model="visitorNumber" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" placeholder="Unit">
                                        </div>
                                        <div v-if="visitorForm.errors.unit_number" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.unit_number }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Purpose of Visit</label>
                                        <textarea v-model="visitorForm.purpose" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold" rows="2"></textarea>
                                        <div v-if="visitorForm.errors.purpose" class="text-red-500 text-xs mt-1">{{ visitorForm.errors.purpose }}</div>
                                    </div>
                                </div>
                                <button 
                                    @click="submitVisitor"
                                    :disabled="!isFaceDetected || visitorForm.processing"
                                    class="mt-8 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-indigo-100 transition-all disabled:opacity-50"
                                >
                                    {{ visitorForm.processing ? 'PROCESSING...' : '✅ REGISTER & REQUEST ENTRY' }}
                                </button>
                            </div>

                            <div v-else>
                                <h3 class="text-lg font-black text-gray-800 mb-6 uppercase tracking-tight text-orange-600">Personnel Particulars</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Full Name</label>
                                        <input v-model="deliveryForm.name" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                        <div v-if="deliveryForm.errors.name" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.name }}</div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Phone</label>
                                            <input v-model="deliveryForm.phone" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                            <div v-if="deliveryForm.errors.phone" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.phone }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Company</label>
                                            <input v-model="deliveryForm.company" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold" placeholder="Lalamove / Grab">
                                            <div v-if="deliveryForm.errors.company" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.company }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">IC / ID Number</label>
                                        <input v-model="deliveryForm.ic_number" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                        <div v-if="deliveryForm.errors.ic_number" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.ic_number }}</div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Vehicle No.</label>
                                            <input v-model="deliveryForm.vehicle_number" type="text" class="w-full bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold">
                                            <div v-if="deliveryForm.errors.vehicle_number" class="text-red-500 text-xs mt-1">{{ deliveryForm.errors.vehicle_number }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Unit (Opt)</label>
                                            <div class="flex space-x-1">
                                                <input v-model="deliveryBlock" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold px-2 py-2 text-sm" placeholder="Blk">
                                                <input v-model="deliveryFloor" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold px-2 py-2 text-sm" placeholder="Flr">
                                                <input v-model="deliveryNumber" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold px-2 py-2 text-sm" placeholder="No.">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button 
                                    @click="submitDelivery"
                                    :disabled="!isFaceDetected || deliveryForm.processing"
                                    class="mt-8 w-full bg-orange-600 hover:bg-orange-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-100 transition-all disabled:opacity-50"
                                >
                                    {{ deliveryForm.processing ? 'PROCESSING...' : '✅ REGISTER & REQUEST ENTRY' }}
                                </button>
                            </div>
                        </div>

                        <!-- Right: Biometrics -->
                        <div class="bg-gray-50 rounded-3xl p-6 flex flex-col items-center border border-gray-100">
                             <h4 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-6">Biometric Enrollment</h4>
                             
                             <div class="w-full max-w-sm bg-white p-4 rounded-2xl shadow-inner mb-6">
                                 <FaceCapture ref="faceCaptureRef" :allow-upload="false" @face-detected="onFaceDetected" />
                             </div>

                             <div class="text-center">
                                 <div v-if="isFaceDetected" class="flex items-center text-green-600 font-black text-sm uppercase">
                                     <span class="h-3 w-3 bg-green-500 rounded-full mr-2 animate-ping"></span> Face Locked
                                 </div>
                                 <div v-else class="text-red-400 font-black text-sm uppercase animate-pulse">
                                     Waiting for Face...
                                 </div>
                                 <p class="text-[10px] text-gray-400 font-bold mt-2 leading-relaxed">
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
