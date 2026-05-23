<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref } from 'vue';

const form = useForm({
    email: '',
    login_type: 'visitor', // Default to visitor
});

const showOtpStep = ref(false);
const otpCode = ref('');
const errorMessage = ref(null);

const requestOtp = async () => {
    errorMessage.value = null;
    try {
        const response = await axios.post(route('visitor.otp.send'), {
            email: form.email,
            login_type: form.login_type
        });
        if (response.data.success) {
            showOtpStep.value = true;
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Something went wrong. Please try again.';
    }
};

const verifyOtp = async () => {
    errorMessage.value = null;
    try {
        const response = await axios.post(route('visitor.otp.verify'), {
            email: form.email,
            otp: otpCode.value,
            login_type: form.login_type
        });
        if (response.data.success) {
            if (response.data.redirect) {
                window.location.href = response.data.redirect;
            }
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Invalid or expired OTP code.';
    }
};

const backToEmail = () => {
    showOtpStep.value = false;
    otpCode.value = '';
    errorMessage.value = null;
};
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <Link href="/">
                <img src="/Logo.png" alt="Sri Ayu Apartment" class="w-40 h-auto" />
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-8 py-8 bg-white shadow-2xl overflow-hidden sm:rounded-3xl border border-gray-100">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">Welcome to Sri Ayu</h2>
                <p class="text-sm text-gray-500 mt-1">Sign in with your registered email</p>
            </div>
            
            <div v-if="errorMessage" class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                <p class="text-xs font-bold text-red-700 uppercase tracking-widest">{{ errorMessage }}</p>
            </div>

            <!-- Step 1: Email Entry -->
            <form v-if="!showOtpStep" @submit.prevent="requestOtp" class="space-y-6">
                <!-- Login type: Visitor vs Delivery -->
                <div>
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest text-center mb-3">
                        First, choose how you are visiting
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="form.login_type = 'visitor'"
                            class="flex flex-col items-center rounded-2xl border-2 p-4 transition-all duration-200"
                            :class="form.login_type === 'visitor'
                                ? 'border-indigo-500 bg-indigo-50 shadow-md ring-2 ring-indigo-200'
                                : 'border-gray-200 bg-gray-50 hover:border-indigo-300 hover:bg-indigo-50/50'"
                        >
                            <span class="text-3xl mb-2" aria-hidden="true">👤</span>
                            <span class="text-sm font-black uppercase tracking-wide"
                                :class="form.login_type === 'visitor' ? 'text-indigo-700' : 'text-gray-600'">
                                Visitor
                            </span>
                            <span class="text-[10px] text-center mt-1 leading-tight"
                                :class="form.login_type === 'visitor' ? 'text-indigo-600' : 'text-gray-400'">
                                Guest visiting a resident
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="form.login_type = 'delivery'"
                            class="flex flex-col items-center rounded-2xl border-2 p-4 transition-all duration-200"
                            :class="form.login_type === 'delivery'
                                ? 'border-orange-500 bg-orange-50 shadow-md ring-2 ring-orange-200'
                                : 'border-gray-200 bg-gray-50 hover:border-orange-300 hover:bg-orange-50/50'"
                        >
                            <span class="text-3xl mb-2" aria-hidden="true">📦</span>
                            <span class="text-sm font-black uppercase tracking-wide"
                                :class="form.login_type === 'delivery' ? 'text-orange-700' : 'text-gray-600'">
                                Delivery
                            </span>
                            <span class="text-[10px] text-center mt-1 leading-tight"
                                :class="form.login_type === 'delivery' ? 'text-orange-600' : 'text-gray-400'">
                                Courier, food, or parcel
                            </span>
                        </button>
                    </div>
                    <p class="text-[10px] text-center text-gray-400 mt-2 italic">
                        Tap the option that matches you before entering your email
                    </p>
                </div>

                <div>
                    <label for="email" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Authenticated Email Address</label>
                    <input id="email" type="email" placeholder="e.g. yourname@example.com" class="mt-1 block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm py-4 font-bold text-gray-700" v-model="form.email" required autofocus />
                    <div v-if="form.errors.email" class="text-red-500 font-bold text-xs mt-2 uppercase italic tracking-widest">{{ form.errors.email }}</div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-4 bg-indigo-600 border border-transparent rounded-2xl font-black text-sm text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-indigo-200">
                        Request OTP Code
                    </button>
                </div>
            </form>

            <!-- Step 2: OTP Verification -->
            <div v-else class="space-y-6">
                <div class="text-center mb-6">
                    <div class="text-green-600 text-4xl mb-2">✓</div>
                    <span
                        class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-3"
                        :class="form.login_type === 'delivery' ? 'bg-orange-100 text-orange-700' : 'bg-indigo-100 text-indigo-700'"
                    >
                        {{ form.login_type === 'delivery' ? '📦 Delivery' : '👤 Visitor' }}
                    </span>
                    <p class="text-sm font-bold text-gray-600">OTP sent to</p>
                    <p class="text-lg font-black text-gray-900 break-all">{{ form.email }}</p>
                    <button @click="backToEmail" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold underline mt-2">Change Email</button>
                </div>

                <div>
                    <label for="otp" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Enter OTP Code</label>
                    <input 
                        id="otp" 
                        type="text" 
                        placeholder="6-digit code" 
                        class="mt-1 block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm py-4 font-bold text-gray-700 text-center text-2xl tracking-widest" 
                        v-model="otpCode" 
                        maxlength="6"
                        autofocus
                    />
                    <p class="text-xs text-gray-400 mt-2 text-center italic">Check your email for the verification code.</p>
                </div>

                <div class="pt-2">
                    <button 
                        @click="verifyOtp" 
                        :disabled="!otpCode || form.processing"
                        class="w-full inline-flex items-center justify-center px-6 py-4 bg-green-600 border border-transparent rounded-2xl font-black text-sm text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-green-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Verify & Continue
                    </button>
                </div>
            </div>
            

        </div>
    </div>
</template>
