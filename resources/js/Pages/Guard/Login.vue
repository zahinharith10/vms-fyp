<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    status: String,
});

const form = useForm({
    employee_id: '',
    password: '',
    remember: false,
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
    form.post(route('guard.login'));
};
</script>

<template>
    <Head title="Guard Login" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <Link href="/" class="flex flex-col items-center">
                <img src="/Logo.png" class="w-40 h-auto" />
                <span class="mt-4 text-2xl font-bold text-gray-800">Guard Access</span>
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div>
                    <label for="employee_id" class="block font-medium text-sm text-gray-700">Employee ID</label>
                    <input id="employee_id" type="text" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.employee_id" required autofocus />
                    <div v-if="form.errors.employee_id" class="text-red-600 text-sm mt-2">{{ form.errors.employee_id }}</div>
                </div>

                <div class="mt-4">
                    <label for="password" class="block font-medium text-sm text-gray-700">Password</label>
                    <input id="password" type="password" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.password" required />
                    <div v-if="form.errors.password" class="text-red-600 text-sm mt-2">{{ form.errors.password }}</div>
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" v-model="form.remember" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <div class="mt-4 flex justify-center">
                    <div id="recaptcha-container"></div>
                </div>

                <div v-if="form.errors.recaptcha" class="text-red-600 text-sm mt-2">{{ form.errors.recaptcha }}</div>

                <div class="flex items-center justify-end mt-4">
                    <Link href="/guard/forgot-password" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Forgot your password?
                    </Link>

                    <button class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Log in
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
