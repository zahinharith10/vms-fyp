<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import FaceCapture from '@/Components/FaceCapture.vue';
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    visit: Object,
    visitor: Object,
    hostName: String,
    qrCodeSvg: String,
    isCurrentUser: Boolean,
});

// Check if visitor profile is completed (phone must also be present and not a placeholder)
const isProfileComplete = computed(() => {
    return props.visitor && 
           props.visitor.photo && 
           props.visitor.ic_number && 
           props.visitor.face_descriptor && 
           props.visitor.vehicle_number && 
           props.visitor.vehicle_number !== '-' &&
           props.visitor.phone &&
           props.visitor.phone !== '-';
});

const detectType = (ic) => ic && /^\d{6}-\d{2}-\d{4}$/.test(ic) ? 'citizen' : 'international';
const citizenType = ref(detectType(props.visitor?.ic_number || ''));
const countryOfOrigin = ref('');

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

const form = useForm({
    ic_number: props.visitor?.ic_number || '',
    vehicle_number: props.visitor?.vehicle_number && props.visitor.vehicle_number !== '-' ? props.visitor.vehicle_number : '',
    phone: props.visitor?.phone && props.visitor.phone !== '-' ? props.visitor.phone : '',
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

// OTP verification fields
const otpSent = ref(false);
const otpCode = ref('');
const otpError = ref('');
const otpSuccess = ref('');
const isSendingOtp = ref(false);
const isVerifyingOtp = ref(false);

const sendVerificationOtp = async () => {
    isSendingOtp.value = true;
    otpError.value = '';
    otpSuccess.value = '';
    try {
        const response = await axios.post(route('visitor.otp.send'), {
            email: props.visitor.email,
            login_type: 'visitor'
        });
        if (response.data.success) {
            otpSent.value = true;
            otpSuccess.value = response.data.message || 'Verification code sent successfully to your email!';
        } else {
            otpError.value = response.data.message || 'Failed to send OTP.';
        }
    } catch (err) {
        otpError.value = err.response?.data?.message || 'Error sending OTP. Please try again.';
    } finally {
        isSendingOtp.value = false;
    }
};

const verifyVerificationOtp = async () => {
    isVerifyingOtp.value = true;
    otpError.value = '';
    try {
        const response = await axios.post(route('visitor.otp.verify'), {
            email: props.visitor.email,
            otp: otpCode.value,
            login_type: 'visitor'
        });
        if (response.data.success) {
            window.location.reload();
        } else {
            otpError.value = response.data.message || 'Invalid verification code.';
        }
    } catch (err) {
        otpError.value = err.response?.data?.message || 'Invalid verification code. Please try again.';
    } finally {
        isVerifyingOtp.value = false;
    }
};
</script>

<template>
    <Head title="Guest Entry Pass" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-950 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-200">
        <!-- Logo Header -->
        <div class="mb-6 flex flex-col items-center">
            <img src="/Logo.png" alt="Sri Ayu Apartment" class="w-40 h-auto" />
            <h1 class="text-2xl font-black text-gray-800 dark:text-white tracking-tighter uppercase italic mt-2">Sri Ayu Residency</h1>
        </div>

        <!-- Expired Pass View -->
        <div v-if="visit.status === 'Expired'" class="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <!-- Banner -->
            <div class="bg-gradient-to-r from-red-500 to-red-650 p-6 text-white text-center">
                <span class="text-5xl block mb-2">⚠️</span>
                <h2 class="text-xl font-bold uppercase tracking-wide">Pass Expired</h2>
                <p class="text-xs text-red-100 mt-1">This guest pass is no longer valid</p>
            </div>
            <div class="p-8 text-center space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold leading-relaxed">
                    This pre-approved pass has expired. Pre-approved guest passes are only valid for 24 hours after approval.
                </p>
                <div class="p-4 bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900 rounded-2xl text-xs font-bold text-red-850 dark:text-red-400 text-left">
                    Please contact the resident of unit <span class="font-black underline">{{ visit.unit_number }}</span> to request a new entry pass.
                </div>
            </div>
        </div>

        <!-- Secure Email Verification View (If not logged in as the correct visitor) -->
        <div v-else-if="!isCurrentUser" class="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <!-- Banner -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 p-6 text-white text-center">
                <span class="text-5xl block mb-2">🔒</span>
                <h2 class="text-xl font-bold uppercase tracking-wide">Secure Guest Pass</h2>
                <p class="text-xs text-indigo-200 mt-1">Verification required to view this pass</p>
            </div>

            <!-- Form Body -->
            <div class="p-8 space-y-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold text-center leading-relaxed">
                    This digital entry pass is secured for:
                </p>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-center">
                    <span class="text-base font-black text-indigo-600 dark:text-indigo-400 select-all">{{ visitor.name }}</span>
                </div>

                <div v-if="otpSent" class="space-y-4">
                    <div>
                        <label for="otp" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Enter Verification Code (OTP)</label>
                        <input 
                            id="otp" 
                            type="text" 
                            placeholder="e.g. 123456"
                            v-model="otpCode" 
                            class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-center text-gray-800 dark:text-gray-200 py-3 px-4 text-lg tracking-widest"
                            required
                        />
                    </div>

                    <button 
                        @click="verifyVerificationOtp"
                        :disabled="isVerifyingOtp || !otpCode"
                        class="w-full inline-flex items-center justify-center px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl shadow-lg transition disabled:opacity-50 uppercase tracking-wider text-xs"
                    >
                        {{ isVerifyingOtp ? 'Verifying...' : 'Verify & View Pass' }}
                    </button>
                    
                    <button 
                        @click="sendVerificationOtp"
                        :disabled="isSendingOtp"
                        class="w-full text-center text-xs font-bold text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition"
                    >
                        {{ isSendingOtp ? 'Resending...' : 'Resend Verification Code' }}
                    </button>
                </div>

                <div v-else class="space-y-4">
                    <button 
                        @click="sendVerificationOtp"
                        :disabled="isSendingOtp"
                        class="w-full inline-flex items-center justify-center px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl shadow-lg transition disabled:opacity-50 uppercase tracking-wider text-xs"
                    >
                        {{ isSendingOtp ? 'Sending code...' : 'Send Verification Code' }}
                    </button>
                </div>

                <!-- Alert Messages -->
                <div v-if="otpSuccess" class="p-4 bg-green-50 dark:bg-green-950/30 border-l-4 border-green-500 rounded-r-2xl text-xs font-bold text-green-800 dark:text-green-400">
                    {{ otpSuccess }}
                </div>
                <div v-if="otpError" class="p-4 bg-red-50 dark:bg-red-950/30 border-l-4 border-red-500 rounded-r-2xl text-xs font-bold text-red-800 dark:text-red-400">
                    {{ otpError }}
                </div>
            </div>
        </div>

        <!-- 1. Active Pass View (Profile Completed & Authenticated) -->
        <div v-else-if="isProfileComplete" class="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800 relative">
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
                <div class="flex flex-col items-center justify-center p-4 bg-emerald-50 dark:bg-emerald-950/30 rounded-3xl border border-emerald-100 dark:border-emerald-900">
                    <div class="w-64 h-64 flex items-center justify-center bg-white p-4 rounded-2xl shadow-sm" v-html="qrCodeSvg"></div>
                    <span class="text-xs font-black tracking-widest uppercase text-emerald-700 dark:text-emerald-400 mt-4">Scan Code at Guardhouse</span>
                </div>

                <!-- Guest & Host details -->
                <div class="text-left space-y-3 bg-gray-50 dark:bg-gray-800/50 p-5 rounded-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase">Guest Name</span>
                        <span class="text-sm font-black text-gray-800 dark:text-gray-200">{{ visitor.name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase">Vehicle Number</span>
                        <span class="text-sm font-black text-gray-800 dark:text-gray-200 uppercase">{{ visitor.vehicle_number }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase">Host Unit</span>
                        <span class="text-sm font-black text-gray-800 dark:text-gray-200">{{ visit.unit_number }}</span>
                    </div>
                    <div class="flex justify-between pb-1">
                        <span class="text-xs font-bold text-gray-400 uppercase">Pre-Approved By</span>
                        <span class="text-sm font-black text-gray-800 dark:text-gray-200">{{ hostName }}</span>
                    </div>
                </div>

                <!-- Visitor Login Link inside the card -->
                <div class="py-2.5 border-y border-dashed border-gray-200 dark:border-gray-700 flex justify-between items-center text-xs px-2 my-4">
                    <span class="font-bold text-gray-500">Looking for your dashboard?</span>
                    <Link :href="route('welcome', { email: visitor.email })" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-black uppercase tracking-wider px-3.5 py-2 rounded-xl transition-all">
                        Log In
                    </Link>
                </div>

                <!-- Info Banner -->
                <div class="p-4 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-800 dark:text-indigo-300 rounded-2xl text-xs font-semibold leading-relaxed text-left border-l-4 border-indigo-500">
                    💡 **Gate Instruction:** When arriving at the gate, simply present this QR code to the scanner. The gate check-in will authorize automatically!
                </div>
            </div>
        </div>

        <!-- 2. Profile Registration View (Complete Details & Authenticated) -->
        <div v-else class="w-full max-w-2xl bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <!-- Banner -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 p-6 text-white text-center">
                <span class="text-5xl block mb-2">👋</span>
                <h2 class="text-xl font-bold uppercase tracking-wide">Welcome, {{ visitor.name }}!</h2>
                <p class="text-xs text-indigo-200 mt-1">Your visit to unit {{ visit.unit_number }} has been pre-approved by {{ hostName }}.</p>
            </div>

            <!-- Steps & Form -->
            <div class="p-6">
                <!-- Helper Alert -->
                <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-300 border-l-4 border-indigo-500 rounded-r-2xl text-sm font-bold">
                    🛡️ **Selfie & Identity verification required:** Please capture a selfie (face verification) and enter your vehicle details below to activate your digital entry pass!
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column: Details -->
                    <div class="space-y-4">
                        <!-- Identity Type Toggle + IC/Passport -->
                        <div>
                            <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Identity Type</label>
                            <div class="flex rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 mb-3">
                                <button type="button" @click="citizenType = 'citizen'; onCitizenTypeChange()"
                                    class="flex-1 py-2 text-xs font-black uppercase tracking-wider transition-all"
                                    :class="citizenType === 'citizen' ? 'bg-indigo-600 text-white' : 'bg-gray-55 dark:bg-gray-800 text-gray-400 hover:bg-gray-100'">
                                    🇲🇾 Malaysian / PR
                                </button>
                                <button type="button" @click="citizenType = 'international'; onCitizenTypeChange()"
                                    class="flex-1 py-2 text-xs font-black uppercase tracking-wider transition-all border-l border-gray-200 dark:border-gray-700"
                                    :class="citizenType === 'international' ? 'bg-indigo-600 text-white' : 'bg-gray-55 dark:bg-gray-800 text-gray-400 hover:bg-gray-100'">
                                    🌍 International
                                </button>
                            </div>
                            
                            <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                                {{ citizenType === 'citizen' ? 'IC Number' : 'Passport Number' }}
                            </label>
                            <input v-if="citizenType === 'citizen'"
                                :value="form.ic_number"
                                @input="formatIC"
                                type="text"
                                placeholder="e.g. 950101-14-1234"
                                maxlength="14"
                                class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4" 
                                required 
                            />
                            <input v-else
                                v-model="form.ic_number"
                                type="text"
                                placeholder="Enter Passport Number"
                                class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4" 
                                required 
                            />
                            <p v-if="citizenType === 'citizen'" class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-widest mt-1">Format: ######-##-#### (12 digits)</p>
                            <div v-if="form.errors.ic_number" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.ic_number }}</div>
                        </div>

                        <!-- Country of Origin (International only) -->
                        <div v-if="citizenType === 'international'">
                            <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Country of Origin</label>
                            <select v-model="countryOfOrigin"
                                class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4">
                                <option value="">Select Country</option>
                                <option>Australia</option><option>Bangladesh</option><option>Brunei</option>
                                <option>Cambodia</option><option>Canada</option><option>China</option>
                                <option>France</option><option>Germany</option><option>India</option>
                                <option>Indonesia</option><option>Japan</option><option>Laos</option>
                                <option>Myanmar</option><option>New Zealand</option><option>Philippines</option>
                                <option>Singapore</option><option>South Korea</option><option>Thailand</option>
                                <option>United Kingdom</option><option>United States</option><option>Vietnam</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label for="phone" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Phone Number <span class="text-red-500">*</span></label>
                            <input 
                                id="phone" 
                                type="tel" 
                                placeholder="e.g. 0123456789"
                                v-model="form.phone" 
                                class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4" 
                                required 
                            />
                            <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.phone }}</div>
                        </div>

                        <!-- Vehicle Plate -->
                        <div>
                            <label for="vehicle_number" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Vehicle Plate Number</label>
                            <input 
                                id="vehicle_number" 
                                type="text" 
                                placeholder="e.g. ABC1234"
                                v-model="form.vehicle_number" 
                                class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4 uppercase" 
                                required 
                            />
                            <div v-if="form.errors.vehicle_number" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.vehicle_number }}</div>
                        </div>

                        <!-- Camera Instructions -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl">
                            <h4 class="text-xs font-black text-gray-500 dark:text-gray-450 uppercase tracking-wider mb-2">📸 Camera Instructions</h4>
                            <ul class="list-disc list-inside text-xs text-gray-600 dark:text-gray-400 space-y-1">
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
                    <div class="flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-3xl p-6 min-h-[300px]">
                        <h3 class="text-xs font-black text-gray-500 dark:text-gray-450 uppercase tracking-wider mb-4">Face Scan Verification</h3>
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
