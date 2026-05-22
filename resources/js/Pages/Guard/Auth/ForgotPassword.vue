<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/guard/forgot-password');
};
</script>

<template>
    <Head title="Forgot Password - Guard" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <Link href="/" class="flex flex-col items-center">
                <img src="/Logo.png" class="w-40 h-auto" />
                <span class="mt-4 text-2xl font-bold text-gray-800">Guard Access</span>
            </Link>
        </div>

        <div class="mb-4 mt-6 text-center max-w-md">
            <p class="text-sm text-gray-600">
                Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
            </p>
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 text-center">
            {{ status }}
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <form @submit.prevent="submit">
                <div>
                    <label class="block font-medium text-sm text-gray-700">Email</label>
                    <input v-model="form.email" type="email" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required autofocus />
                    <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <button type="submit" class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Email Password Reset Link
                    </button>
                </div>
            </form>
        </div>
        
        <div class="mt-4 text-center">
            <Link href="/guard/login" class="text-sm text-gray-600 hover:text-gray-900 underline">
                &larr; Back to Login
            </Link>
        </div>
    </div>
</template>
