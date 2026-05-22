<script setup>
import VisitorAuthenticatedLayout from '@/Layouts/VisitorAuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import FaceCapture from '@/Components/FaceCapture.vue';

const page = usePage();
const visitor = page.props.visitor || page.props.auth.user;

const form = useForm({
    _method: 'PATCH',
    name: visitor?.name || '',
    phone: visitor?.phone || '',
    ic_number: visitor?.ic_number || '',
    vehicle_number: visitor?.vehicle_number || '',
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
    // face capture component is still mounted, just re-shown via v-if logic in template
};

const confirmPhotoAndUpdate = () => {
    // Photo is already in form.photo from capture step
    // Just closing the capture UI validation? 
    // Actually, user might want to just "Confirm" the local change then "Save Profile" later?
    // User request: "choose to continue update or retake".
    // So "Confirm" just sets the state as "Ready to Save".
    
    // We already set form.photo in capturePhoto. 
    // We can hide the capture UI or just leave it showing the "Success" state?
    // Let's just scroll or focus on Save button, or auto-save?
    // "continue update" implies proceeding. Let's assume manual Save Profile click is still needed for final submission.
    showFaceCapture.value = false; 
    // We update the main avatar preview to show the new candidate?
    // visitor.photo is prop, can't mutate. We can use a local preview for the main avatar.
};

const updateProfile = async () => {
    form.post(route('visitor.profile.update'), {
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
    <Head title="My Profile" />

    <VisitorAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Profile</h2>
        </template>

        <!-- Zoom Modal -->
        <div v-if="isZoomOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90" @click="isZoomOpen = false">
            <div class="relative max-w-4xl max-h-screen p-4">
                <img :src="capturedPhotoPreview || (visitor.photo ? `/storage/${visitor.photo}` : null)" class="max-w-full max-h-[90vh] object-contain rounded-lg" v-if="capturedPhotoPreview || visitor.photo" />
                <button class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300" @click="isZoomOpen = false">&times;</button>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash.info" class="p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 font-bold rounded shadow-sm">
                    {{ $page.props.flash.info }}
                </div>
                <div v-if="$page.props.flash.success" class="p-4 bg-green-50 border-l-4 border-green-400 text-green-700 font-bold rounded shadow-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Missing Info Warning -->
                <div v-if="!visitor.name" class="p-6 bg-orange-50 border-2 border-orange-200 rounded-3xl animate-pulse">
                    <div class="flex items-center">
                        <span class="text-3xl mr-4">🚧</span>
                        <div>
                            <h3 class="font-black text-orange-800 uppercase tracking-widest text-xs">Action Required: Profile Incomplete</h3>
                            <p class="text-orange-700 font-bold text-sm">Please provide your full name and other details to generate your security QR code.</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Information -->
                <div class="p-8 bg-white shadow-xl sm:rounded-3xl border border-gray-100">
                    <!-- Avatar Section -->
                    <div class="flex items-center mb-8">
                        <div 
                            class="h-20 w-20 bg-purple-100 rounded-2xl flex items-center justify-center overflow-hidden cursor-pointer hover:opacity-80 transition-opacity border-2 border-purple-100"
                            @click="isZoomOpen = true"
                        >
                             <img v-if="capturedPhotoPreview" :src="capturedPhotoPreview" class="h-full w-full object-cover" />
                             <img v-else-if="visitor.photo" :src="`/storage/${visitor.photo}`" class="h-full w-full object-cover" />
                             <span v-else class="text-3xl">👋</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-black text-gray-900 uppercase tracking-tighter">Your Profile</h3>
                            <p class="text-sm font-bold text-gray-500">Update your visitor information for faster check-ins.</p>
                            <p class="text-xs text-purple-600 font-bold mt-1 cursor-pointer" @click="isZoomOpen = true">Click photo to zoom</p>
                        </div>
                    </div>

                    <form @submit.prevent="updateProfile" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Full Name</label>
                                <input id="name" type="text" v-model="form.name" class="block w-full border-gray-200 focus:border-purple-500 focus:ring-purple-500 rounded-xl shadow-sm font-bold text-gray-700" required />
                                <div v-if="form.errors.name" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.name }}</div>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Phone Number</label>
                                <input id="phone" type="text" v-model="form.phone" class="block w-full border-gray-200 focus:border-purple-500 focus:ring-purple-500 rounded-xl shadow-sm font-bold text-gray-700" required />
                                <div v-if="form.errors.phone" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.phone }}</div>
                            </div>

                            <!-- IC Number -->
                            <div>
                                <label for="ic_number" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">IC / Identification Number</label>
                                <input id="ic_number" type="text" v-model="form.ic_number" class="block w-full border-gray-200 focus:border-purple-500 focus:ring-purple-500 rounded-xl shadow-sm font-bold text-gray-700" placeholder="Required" required />
                                <div v-if="form.errors.ic_number" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.ic_number }}</div>
                            </div>

                            <!-- Vehicle Number -->
                            <div>
                                <label for="vehicle_number" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Vehicle Plate Number</label>
                                <input id="vehicle_number" type="text" v-model="form.vehicle_number" class="block w-full border-gray-200 focus:border-purple-500 focus:ring-purple-500 rounded-xl shadow-sm font-bold text-gray-700 uppercase" placeholder="e.g. ABC1234" required />
                                <div v-if="form.errors.vehicle_number" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.vehicle_number }}</div>
                            </div>
                        </div>

                        <!-- Face Verification Section -->
                        <div class="border-t border-gray-100 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-sm font-black text-gray-900 uppercase tracking-widest">Face Verification</h4>
                                    <p class="text-xs text-gray-500 font-bold">Manage your registered face for guard house access.</p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="showFaceCapture = !showFaceCapture"
                                    class="text-xs font-black uppercase tracking-widest text-purple-600 hover:text-purple-800 underline"
                                >
                                    {{ showFaceCapture ? 'Cancel Update' : 'Update Photo' }}
                                </button>
                            </div>

                            <div v-show="showFaceCapture" class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                                <!-- Capture Mode -->
                                <div v-if="!capturedPhotoPreview">
                                    <FaceCapture ref="faceCaptureRef" @face-detected="onFaceDetected" />
                                    <div class="mt-4 flex flex-col items-center">
                                        <div class="mb-2">
                                            <span v-if="isFaceDetected" class="text-green-600 text-xs font-black uppercase tracking-widest">Face Detected</span>
                                            <span v-else class="text-red-400 text-xs font-black uppercase tracking-widest">Position face in frame...</span>
                                        </div>
                                        <button 
                                            type="button"
                                            @click="capturePhoto"
                                            :disabled="!isFaceDetected"
                                            class="px-6 py-2 bg-indigo-600 text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md"
                                        >
                                            📸 Snap Photo
                                        </button>
                                    </div>
                                </div>

                                <!-- Review Mode -->
                                <div v-else class="flex flex-col items-center">
                                    <img :src="capturedPhotoPreview" class="h-64 w-64 object-cover rounded-xl border-4 border-white shadow-lg mb-4" />
                                    <div class="flex space-x-4">
                                        <button 
                                            type="button"
                                            @click="retakePhoto"
                                            class="px-6 py-2 bg-gray-200 text-gray-700 text-xs font-bold uppercase tracking-widest rounded-full hover:bg-gray-300 transition-all"
                                        >
                                            Retake
                                        </button>
                                        <button 
                                            type="button"
                                            @click="confirmPhotoAndUpdate"
                                            class="px-6 py-2 bg-green-500 text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-green-600 transition-all shadow-lg"
                                        >
                                            Use This Photo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-6 border-t border-gray-50">
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="status" class="text-sm text-green-600 font-black mr-4 uppercase tracking-widest flex items-center">
                                    <span class="mr-2">✅</span> {{ status }}
                                </p>
                            </Transition>

                            <button
                                type="submit"
                                :disabled="form.processing || showFaceCapture"
                                class="inline-flex items-center px-8 py-3 bg-purple-600 border border-transparent rounded-2xl font-black text-sm text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-purple-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Save Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Guidance Info -->
                <div class="bg-indigo-50 rounded-3xl p-6 border border-indigo-100">
                    <div class="flex">
                        <span class="text-2xl mr-4">ℹ️</span>
                        <div>
                            <p class="font-black text-indigo-900 text-xs uppercase tracking-widest mb-1">Identity Information</p>
                            <p class="text-indigo-700 text-sm font-bold">Keeping your name and phone number accurate ensures that residents can recognize your requests and guards can verify your entry quickly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </VisitorAuthenticatedLayout>
</template>
