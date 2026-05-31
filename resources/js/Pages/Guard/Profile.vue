<script setup>
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const guard = usePage().props.guard;

const form = useForm({
    name: guard.name,
    email: guard.email,
    phone: guard.phone,
    password: '',
    password_confirmation: '',
});

const status = ref(null);

const updateProfile = () => {
    form.patch(route('guard.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
            status.value = 'Profile updated successfully.';
            setTimeout(() => status.value = null, 3000);
        },
    });
};
</script>

<template>
    <Head title="Guard Profile" />

    <GuardAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">My Profile</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-8 bg-white dark:bg-gray-900 shadow-xl sm:rounded-3xl border border-gray-100 dark:border-gray-800 transition-colors duration-200">
                    <div class="flex items-center mb-8">
                        <div class="h-16 w-16 bg-blue-100 dark:bg-blue-950/60 rounded-2xl flex items-center justify-center text-3xl text-blue-600 dark:text-blue-400">
                            🛡️
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tighter">Duty Profile</h3>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Manage your officer account details and security.</p>
                        </div>
                    </div>

                    <form @submit.prevent="updateProfile" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Full Name</label>
                                <input id="name" type="text" v-model="form.name"
                                    class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-200 transition-colors duration-200"
                                    required />
                                <div v-if="form.errors.name" class="mt-2 text-sm text-red-500 dark:text-red-400 font-bold">{{ form.errors.name }}</div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Email Address</label>
                                <input id="email" type="email" v-model="form.email"
                                    class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-200 transition-colors duration-200"
                                    required />
                                <div v-if="form.errors.email" class="mt-2 text-sm text-red-500 dark:text-red-400 font-bold">{{ form.errors.email }}</div>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Phone Number</label>
                                <input id="phone" type="text" v-model="form.phone"
                                    class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-200 transition-colors duration-200"
                                    required />
                                <div v-if="form.errors.phone" class="mt-2 text-sm text-red-500 dark:text-red-400 font-bold">{{ form.errors.phone }}</div>
                            </div>

                             <!-- Employee ID (Locked) -->
                             <div>
                                <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Employee ID</label>
                                <div class="bg-gray-50 dark:bg-gray-800/70 px-4 py-2 rounded-xl text-gray-500 dark:text-gray-400 font-mono font-bold border border-gray-100 dark:border-gray-700 flex items-center justify-between transition-colors duration-200">
                                    {{ guard.employee_id }}
                                    <span class="text-[10px] bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 px-2 py-0.5 rounded uppercase">Locked</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-800 pt-6">
                            <h4 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Security</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="password" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">New Password (Optional)</label>
                                    <input id="password" type="password" v-model="form.password"
                                        class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-200 dark:placeholder-gray-500 transition-colors duration-200"
                                        placeholder="••••••••" />
                                    <div v-if="form.errors.password" class="mt-2 text-sm text-red-500 dark:text-red-400 font-bold">{{ form.errors.password }}</div>
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Confirm New Password</label>
                                    <input id="password_confirmation" type="password" v-model="form.password_confirmation"
                                        class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-200 dark:placeholder-gray-500 transition-colors duration-200"
                                        placeholder="••••••••" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-6 border-t border-gray-50 dark:border-gray-800">
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="status" class="text-sm text-green-600 dark:text-green-400 font-black mr-4 uppercase tracking-widest flex items-center">
                                    <span class="mr-2">✅</span> {{ status }}
                                </p>
                            </Transition>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-8 py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-900 border border-transparent rounded-2xl font-black text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition ease-in-out duration-150 shadow-lg shadow-blue-200 dark:shadow-blue-950/30 disabled:opacity-50"
                            >
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </GuardAuthenticatedLayout>
</template>
