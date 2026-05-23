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
            <h2 class="text-2xl font-black text-center mb-8 text-gray-900 tracking-tighter uppercase italic">Access Portal</h2>
            
            <div v-if="errorMessage" class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                <p class="text-xs font-bold text-red-700 uppercase tracking-widest">{{ errorMessage }}</p>
            </div>

            <!-- Step 1: Email Entry -->
            <form v-if="!showOtpStep" @submit.prevent="requestOtp" class="space-y-6">
                <!-- Login Type Toggle -->
                <div class="bg-gray-100 p-1.5 rounded-2xl flex relative">
                   <button 
                       type="button"
                       @click="form.login_type = 'visitor'"
                       class="flex-1 py-3 text-sm font-black transition-all duration-300 rounded-xl relative z-10 uppercase tracking-widest"
                       :class="form.login_type === 'visitor' ? 'text-indigo-600 bg-white shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                   >
                       Visitor
                   </button>
                   <button 
                       type="button"
                       @click="form.login_type = 'delivery'"
                       class="flex-1 py-3 text-sm font-black transition-all duration-300 rounded-xl relative z-10 uppercase tracking-widest"
                       :class="form.login_type === 'delivery' ? 'text-indigo-600 bg-white shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                   >
                       Delivery
                   </button>
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
