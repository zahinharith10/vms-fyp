<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/guard/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password - Guard" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <Link href="/" class="flex flex-col items-center">
                <img src="/Logo.png" class="w-40 h-auto" />
                <span class="mt-4 text-2xl font-bold text-gray-800">Guard Access</span>
            </Link>
        </div>

        <div class="mb-6 mt-6 text-center">
            <h1 class="text-3xl font-bold text-gray-900">Reset Password</h1>
            <p class="text-gray-600">Please enter your new password below</p>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <form @submit.prevent="submit">
                <div>
                    <label class="block font-medium text-sm text-gray-700">Email</label>
                    <input v-model="form.email" type="email" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1 bg-gray-100" required readonly />
                    <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Password</label>
                    <input v-model="form.password" type="password" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required autofocus autocomplete="new-password" />
                    <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Confirm Password</label>
                    <input v-model="form.password_confirmation" type="password" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required autocomplete="new-password" />
                    <div v-if="form.errors.password_confirmation" class="text-red-600 text-sm mt-1">{{ form.errors.password_confirmation }}</div>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <button type="submit" class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
