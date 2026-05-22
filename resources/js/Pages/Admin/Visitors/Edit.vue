<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    visitor: Object,
});

const form = useForm({
    name: props.visitor.name,
    phone: props.visitor.phone,
    ic_number: props.visitor.ic_number,
    vehicle_number: props.visitor.vehicle_number,
    photo: null,
    _method: 'put', // Method spoofing for file upload
});

const submit = () => {
    form.post(route('admin.visitors.update', props.visitor.id));
};

// Camera Logic
const showCamera = ref(false);
const videoEl = ref(null);
const canvasEl = ref(null);
const stream = ref(null);

const startCamera = async () => {
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ video: true });
        showCamera.value = true;
        // Wait for DOM update
        setTimeout(() => {
            if (videoEl.value) {
                videoEl.value.srcObject = stream.value;
            }
        }, 100);
    } catch (err) {
        alert("Camera access denied or error: " + err.message);
    }
};

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
    showCamera.value = false;
};

const capturePhoto = () => {
    if (!videoEl.value || !canvasEl.value) return;
    
    const context = canvasEl.value.getContext('2d');
    canvasEl.value.width = videoEl.value.videoWidth;
    canvasEl.value.height = videoEl.value.videoHeight;
    context.drawImage(videoEl.value, 0, 0, canvasEl.value.width, canvasEl.value.height);
    
    canvasEl.value.toBlob(blob => {
        if (blob) {
            const file = new File([blob], "camera_capture.jpg", { type: "image/jpeg" });
            form.photo = file;
            stopCamera();
        }
    }, 'image/jpeg', 0.95);
};
</script>

<template>
    <Head title="Edit Visitor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Visitor</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="max-w-md">
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Name</label>
                                <input v-model="form.name" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required />
                                <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Phone</label>
                                <input v-model="form.phone" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" />
                                <div v-if="form.errors.phone" class="text-red-600 text-sm mt-1">{{ form.errors.phone }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">IC Number</label>
                                <input v-model="form.ic_number" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required />
                                <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Plate Number (Optional)</label>
                                <input v-model="form.vehicle_number" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" />
                                <div v-if="form.errors.vehicle_number" class="text-red-600 text-sm mt-1">{{ form.errors.vehicle_number }}</div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Photo</label>
                                <div v-if="visitor.photo" class="mb-2">
                                    <img :src="'/storage/' + visitor.photo" class="h-20 w-20 rounded object-cover" />
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="startCamera" class="px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-500 text-sm" v-if="!showCamera">
                                            Use Camera
                                        </button>
                                        <input type="file" @input="form.photo = $event.target.files[0]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" accept="image/*" v-if="!showCamera" />
                                    </div>
                                    
                                    <div v-if="showCamera" class="mt-2 border p-2 rounded bg-black">
                                        <video ref="videoEl" autoplay playsinline class="w-full h-auto max-h-64 object-contain mb-2"></video>
                                        <div class="flex justify-center gap-2">
                                            <button type="button" @click="capturePhoto" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500 font-bold">
                                                Capture
                                            </button>
                                            <button type="button" @click="stopCamera" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                    <canvas ref="canvasEl" class="hidden"></canvas>

                                    <div v-if="form.photo && !showCamera" class="mt-2 p-2 bg-gray-100 rounded flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Selected: {{ form.photo.name }}</span>
                                        <button type="button" @click="form.photo = null" class="text-red-500 text-sm hover:underline">Remove</button>
                                    </div>
                                </div>

                                <div v-if="form.errors.photo" class="text-red-600 text-sm mt-1">{{ form.errors.photo }}</div>
                            </div>

                            <div class="flex items-center gap-4 mt-6">
                                <button type="submit" class="bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 px-4 py-2" :disabled="form.processing">
                                    Update Visitor
                                </button>
                                <Link :href="route('admin.visitors.index')" class="text-gray-600 underline text-sm">Cancel</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
