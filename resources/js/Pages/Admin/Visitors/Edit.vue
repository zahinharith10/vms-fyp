<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    visitor: Object,
});

// Auto-detect type from stored value
const detectType = (ic) => ic && /^\d{6}-\d{2}-\d{4}$/.test(ic) ? 'citizen' : 'international';
const citizenType = ref(detectType(props.visitor?.ic_number || ''));
const countryOfOrigin = ref('');

const form = useForm({
    name: props.visitor.name,
    phone: props.visitor.phone,
    ic_number: props.visitor.ic_number,
    vehicle_number: props.visitor.vehicle_number,
    photo: null,
    _method: 'put',
});

// IC input mask
const formatIC = (e) => {
    let digits = e.target.value.replace(/\D/g, '').slice(0, 12);
    let masked = digits;
    if (digits.length > 6) masked = digits.slice(0, 6) + '-' + digits.slice(6);
    if (digits.length > 8) masked = digits.slice(0, 6) + '-' + digits.slice(6, 8) + '-' + digits.slice(8);
    form.ic_number = masked;
};

const submit = () => {
    if (!confirm('Are you sure you want to update this visitor?')) return;
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

                            <!-- Identity Type Toggle + IC/Passport -->
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700 mb-1">Identity Type</label>
                                <div class="flex rounded-md overflow-hidden border border-gray-300 mb-3">
                                    <button type="button" @click="citizenType = 'citizen'; form.ic_number = ''; countryOfOrigin = ''"
                                        class="flex-1 py-2 text-xs font-bold uppercase tracking-wider transition-all"
                                        :class="citizenType === 'citizen' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                                        🇲🇾 Malaysian / PR
                                    </button>
                                    <button type="button" @click="citizenType = 'international'; form.ic_number = ''; countryOfOrigin = ''"
                                        class="flex-1 py-2 text-xs font-bold uppercase tracking-wider transition-all border-l border-gray-300"
                                        :class="citizenType === 'international' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                                        🌍 International
                                    </button>
                                </div>
                                <label class="block font-medium text-sm text-gray-700">
                                    {{ citizenType === 'citizen' ? 'IC Number' : 'Passport Number' }}
                                </label>
                                <input v-if="citizenType === 'citizen'"
                                    :value="form.ic_number" @input="formatIC"
                                    type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                                    placeholder="e.g. 950101-14-1234" maxlength="14" required />
                                <input v-else
                                    v-model="form.ic_number"
                                    type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1"
                                    placeholder="Enter Passport Number" required />
                                <p v-if="citizenType === 'citizen'" class="text-xs text-gray-400 mt-1">Format: ######-##-#### (12 digits)</p>
                                <div v-if="form.errors.ic_number" class="text-red-600 text-sm mt-1">{{ form.errors.ic_number }}</div>
                            </div>

                            <div v-if="citizenType === 'international'" class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Country of Origin</label>
                                <select v-model="countryOfOrigin" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1">
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
