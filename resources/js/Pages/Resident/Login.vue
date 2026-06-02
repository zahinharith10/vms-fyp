<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    recaptcha_token: '',
});

onMounted(() => {
    window.grecaptcha.ready(() => {
        window.grecaptcha.render('recaptcha-container', {
            sitekey: import.meta.env.VITE_RECAPTCHA_SITE_KEY,
            theme: 'light',
        });
    });
});

const submit = () => {
    form.recaptcha_token = window.grecaptcha.getResponse();
    console.log('reCAPTCHA token:', form.recaptcha_token);
    if (!form.recaptcha_token) {
        alert('Please check the reCAPTCHA checkbox');
        return;
    }
    form.post('/resident/login');
};
</script>

<template>
    <Head title="Resident Login" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <Link href="/">
                <img src="/Logo.png" class="w-40 h-auto" />
            </Link>
        </div>

        <div class="mb-6 mt-6 text-center">
            <h1 class="text-3xl font-bold text-gray-900">Resident Portal</h1>
            <p class="text-gray-600">Please sign in to continue</p>
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 text-center">
            {{ status }}
        </div>

        <!-- Flash Success Banner -->
        <div v-if="$page.props.flash.success" class="w-full sm:max-w-md mt-6 p-4 bg-green-50 border border-green-200 rounded-2xl text-green-800 text-sm font-bold flex items-center shadow-sm">
            <span class="text-lg mr-2">🎉</span> {{ $page.props.flash.success }}
        </div>

        <!-- Flash Error Banner -->
        <div v-if="$page.props.flash.error" class="w-full sm:max-w-md mt-6 p-4 bg-red-50 border border-red-200 rounded-2xl text-red-800 text-sm font-bold flex items-center shadow-sm">
            <span class="text-lg mr-2">❌</span> {{ $page.props.flash.error }}
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <form @submit.prevent="submit">
                <div>
                    <label class="block font-medium text-sm text-gray-700">Email</label>
                    <input v-model="form.email" type="email" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required autofocus />
                    <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Password</label>
                    <input v-model="form.password" type="password" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required />
                    <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                </div>

                <div class="mt-4 flex justify-center">
                    <div id="recaptcha-container"></div>
                </div>

                <div v-if="form.errors.recaptcha" class="text-red-600 text-sm mt-2">{{ form.errors.recaptcha }}</div>

                <div class="flex items-center justify-end mt-4">
                    <Link href="/resident/forgot-password" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Forgot your password?
                    </Link>

                    <button type="submit" class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Log in
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
