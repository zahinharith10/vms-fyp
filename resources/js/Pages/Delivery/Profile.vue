<script setup>
import DeliveryAuthenticatedLayout from '@/Layouts/DeliveryAuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import FaceCapture from '@/Components/FaceCapture.vue';

const delivery = usePage().props.auth.user;

const form = useForm({
    _method: 'PATCH',
    name: delivery.name,
    phone: delivery.phone,
    vehicle_number: delivery.vehicle_number || '',
    face_descriptor: null,
    photo: null,
});

const status = ref(null);
const showFaceCapture = ref(false);
const faceCaptureRef = ref(null);
const isFaceDetected = ref(false);
const currentDescriptor = ref(null);
const capturedPhotoPreview = ref(null);
const isZoomOpen = ref(false);

const onFaceDetected = (detection) => {
    if (detection) {
        currentDescriptor.value = Array.from(detection.descriptor);
        isFaceDetected.value = true;
    } else {
        isFaceDetected.value = false;
        currentDescriptor.value = null;
    }
};

const capturePhoto = async () => {
    if (faceCaptureRef.value && isFaceDetected.value) {
        const file = await faceCaptureRef.value.getSnapshot();
        if (file) {
            capturedPhotoPreview.value = URL.createObjectURL(file);
            form.photo = file;
            form.face_descriptor = currentDescriptor.value;
        }
    }
};

const retakePhoto = () => {
    capturedPhotoPreview.value = null;
    form.photo = null;
    form.face_descriptor = null;
};

const confirmPhotoAndUpdate = () => {
    showFaceCapture.value = false;
};

const updateProfile = async () => {
    form.post(route('delivery.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            status.value = 'Profile updated successfully.';
            showFaceCapture.value = false;
            capturedPhotoPreview.value = null;
            setTimeout(() => status.value = null, 3000);
        },
    });
};
</script>

<template>
    <Head title="Delivery Profile" />

    <DeliveryAuthenticatedLayout>
        <template #header>
            <h2 class="text-3xl font-black text-gray-900 tracking-tighter uppercase italic">My Profile</h2>
            <p class="text-gray-400 font-bold text-xs uppercase tracking-widest mt-1">Manage your identity and vehicle information.</p>
        </template>

        <!-- Zoom Modal -->
        <div v-if="isZoomOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90" @click="isZoomOpen = false">
            <div class="relative max-w-4xl max-h-screen p-4">
                 <img :src="capturedPhotoPreview || (delivery.photo ? `/storage/${delivery.photo}` : null)" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl" v-if="capturedPhotoPreview || delivery.photo" />
                <button class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300" @click="isZoomOpen = false">&times;</button>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Profile Information -->
                <div class="p-10 bg-white shadow-2xl shadow-gray-100 sm:rounded-[40px] border border-gray-100">
                    <!-- Avatar Section -->
                    <div class="flex items-center mb-10">
                        <div 
                            class="h-20 w-20 bg-orange-100 rounded-3xl flex items-center justify-center overflow-hidden transform -rotate-3 cursor-pointer hover:rotate-0 transition-transform shadow-lg border-2 border-orange-50"
                            @click="isZoomOpen = true"
                        >
                            <img v-if="capturedPhotoPreview" :src="capturedPhotoPreview" class="h-full w-full object-cover" />
                            <img v-else-if="delivery.photo" :src="`/storage/${delivery.photo}`" class="h-full w-full object-cover" />
                             <div v-else class="text-4xl">🏢</div>
                        </div>
                        <div class="ml-6">
                            <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter italic">Personnel Details</h3>
                            <p class="text-sm font-bold text-orange-600 uppercase tracking-widest">{{ delivery.company }} Driver Account</p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1 cursor-pointer hover:text-orange-500" @click="isZoomOpen = true">Click photo to zoom</p>
                        </div>
                    </div>

                    <form @submit.prevent="updateProfile" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Driver Full Name</label>
                                <input id="name" type="text" v-model="form.name" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner" required />
                                <div v-if="form.errors.name" class="mt-2 text-xs text-red-500 font-black italic uppercase tracking-widest">{{ form.errors.name }}</div>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Phone Number</label>
                                <input id="phone" type="text" v-model="form.phone" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner" required />
                                <div v-if="form.errors.phone" class="mt-2 text-xs text-red-500 font-black italic uppercase tracking-widest">{{ form.errors.phone }}</div>
                            </div>

                            <!-- Vehicle Number -->
                            <div>
                                <label for="vehicle_number" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Vehicle Plate Number</label>
                                <input id="vehicle_number" type="text" v-model="form.vehicle_number" class="block w-full border-gray-100 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-orange-500 rounded-2xl py-4 font-bold text-gray-700 shadow-inner uppercase italic" required />
                                <div v-if="form.errors.vehicle_number" class="mt-2 text-xs text-red-500 font-black italic uppercase tracking-widest">{{ form.errors.vehicle_number }}</div>
                            </div>

                            <!-- IC Number (Locked) -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">IC Number (Verified)</label>
                                <div class="block w-full bg-gray-100 py-4 px-4 rounded-2xl font-black text-gray-400 italic tracking-widest">
                                    {{ delivery.ic_number }}
                                </div>
                                <p class="mt-2 text-[10px] font-bold text-gray-300 uppercase tracking-widest italic">Contact Admin to change verified identity.</p>
                            </div>
                        </div>

                         <!-- Face Verification Section -->
                        <div class="border-t border-gray-100 pt-8">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h4 class="text-sm font-black text-gray-900 uppercase tracking-widest italic">Face Verification</h4>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Manage your visual identity for building access.</p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="showFaceCapture = !showFaceCapture"
                                    class="text-[10px] font-black uppercase tracking-widest text-orange-600 hover:text-orange-800 underline transition-colors"
                                >
                                    {{ showFaceCapture ? 'Cancel Update' : 'Update Photo' }}
                                </button>
                            </div>

                            <div v-show="showFaceCapture" class="bg-gray-50 p-6 rounded-[32px] border border-gray-100 shadow-inner">
                                <!-- Capture Mode -->
                                <div v-if="!capturedPhotoPreview">
                                    <FaceCapture ref="faceCaptureRef" @face-detected="onFaceDetected" />
                                    <div class="mt-4 flex flex-col items-center">
                                        <div class="mb-2 text-center">
                                            <span v-if="isFaceDetected" class="text-green-600 text-[10px] font-black uppercase tracking-widest animate-pulse">Identity Verified - Ready to Scan</span>
                                            <span v-else class="text-red-400 text-[10px] font-black uppercase tracking-widest">Scanning facial features...</span>
                                        </div>
                                         <button 
                                            type="button"
                                            @click="capturePhoto"
                                            :disabled="!isFaceDetected"
                                            class="px-8 py-3 bg-orange-600 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-xl shadow-orange-200"
                                        >
                                            📸 Snap Photo
                                        </button>
                                    </div>
                                </div>

                                <!-- Review Mode -->
                                <div v-else class="flex flex-col items-center">
                                    <img :src="capturedPhotoPreview" class="h-64 w-64 object-cover rounded-[32px] border-4 border-white shadow-2xl shadow-orange-100 mb-6 transform rotate-1" />
                                    <div class="flex space-x-6">
                                        <button 
                                            type="button"
                                            @click="retakePhoto"
                                            class="px-8 py-3 bg-gray-200 text-gray-600 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-gray-300 transition-all"
                                        >
                                            Retake
                                        </button>
                                        <button 
                                            type="button"
                                            @click="confirmPhotoAndUpdate"
                                            class="px-8 py-3 bg-green-500 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-green-600 transition-all shadow-xl shadow-green-100"
                                        >
                                            Use This Photo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-10 border-t border-gray-50">
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="status" class="text-xs text-green-600 font-black mr-6 uppercase tracking-widest flex items-center italic">
                                    <span class="mr-2 text-sm italic">✓</span> {{ status }}
                                </p>
                            </Transition>

                            <button
                                type="submit"
                                :disabled="form.processing || showFaceCapture"
                                class="inline-flex items-center px-10 py-4 bg-orange-600 border border-transparent rounded-2xl font-black text-xs text-white uppercase tracking-widest hover:bg-orange-700 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-xl shadow-orange-100 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Update Information
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Guidance Info -->
                <div class="bg-gray-900 rounded-[40px] p-10 text-white relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row items-center">
                        <div class="h-16 w-16 bg-white/10 rounded-2xl flex items-center justify-center text-3xl mb-4 md:mb-0 md:mr-8">📢</div>
                        <div>
                            <p class="font-black text-white text-[10px] uppercase tracking-widest mb-1 italic">Security Notice</p>
                            <p class="text-gray-400 text-sm font-bold tracking-tight">Your phone number is used for authentication. Changing it will require you to log in again with the new number. Your vehicle plate number must match the one displayed on your delivery vehicle.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DeliveryAuthenticatedLayout>
</template>
