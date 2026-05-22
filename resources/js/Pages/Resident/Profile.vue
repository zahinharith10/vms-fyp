<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const resident = usePage().props.resident;

const form = useForm({
    name: resident.name,
    email: resident.email,
    phone: resident.phone,
    auto_approve_deliveries: resident.auto_approve_deliveries || false,
    password: '',
    password_confirmation: '',
});

const status = ref(null);

const updateProfile = () => {
    form.patch(route('resident.profile.update'), {
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
    <Head title="My Profile" />

    <ResidentAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Profile</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Profile Information & Password -->
                <div class="p-8 bg-white shadow-xl sm:rounded-3xl border border-gray-100">
                    <div class="flex items-center mb-8">
                        <div class="h-16 w-16 bg-indigo-100 rounded-2xl flex items-center justify-center text-3xl">
                            👤
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-black text-gray-900 uppercase tracking-tighter">Profile Details</h3>
                            <p class="text-sm font-bold text-gray-500">Update your personal information and security settings.</p>
                        </div>
                    </div>

                    <form @submit.prevent="updateProfile" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Full Name</label>
                                <input id="name" type="text" v-model="form.name" class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700" required />
                                <div v-if="form.errors.name" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.name }}</div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Email Address</label>
                                <input id="email" type="email" v-model="form.email" class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700" required />
                                <div v-if="form.errors.email" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.email }}</div>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Phone Number</label>
                                <input id="phone" type="text" v-model="form.phone" class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700" required />
                                <div v-if="form.errors.phone" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.phone }}</div>
                            </div>

                            <!-- House Unit (Display Only) -->
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">House Unit</label>
                                <div class="bg-gray-50 px-4 py-2 rounded-xl text-gray-500 font-bold border border-gray-100">
                                    {{ resident.house_unit.block }}-{{ resident.house_unit.unit_number }}
                                </div>
                            </div>
                        </div>

                        <!-- Auto-Approve Deliveries Toggle -->
                        <div class="border-t border-gray-100 pt-6">
                            <div class="flex items-center justify-between p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100/50">
                                <div class="flex-1 pr-4">
                                    <h4 class="text-sm font-black text-gray-900 uppercase tracking-widest">Auto-Approve Deliveries</h4>
                                    <p class="text-xs text-gray-400 font-bold mt-1 leading-relaxed">
                                        When enabled, deliveries from Grab, FoodPanda, etc. to your unit will be approved automatically without requesting manual confirmation.
                                    </p>
                                </div>
                                <div class="flex items-center">
                                    <button
                                        type="button"
                                        @click="form.auto_approve_deliveries = !form.auto_approve_deliveries"
                                        :class="form.auto_approve_deliveries ? 'bg-indigo-600' : 'bg-gray-200'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 shadow-sm"
                                    >
                                        <span
                                            :class="form.auto_approve_deliveries ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        ></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <h4 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-4">Security</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="password" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">New Password (Optional)</label>
                                    <input id="password" type="password" v-model="form.password" class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700" placeholder="••••••••" />
                                    <div v-if="form.errors.password" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.password }}</div>
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Confirm New Password</label>
                                    <input id="password_confirmation" type="password" v-model="form.password_confirmation" class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700" placeholder="••••••••" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-6 border-t border-gray-50">
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="status" class="text-sm text-green-600 font-black mr-4 uppercase tracking-widest flex items-center">
                                    <span class="mr-2">✅</span> {{ status }}
                                </p>
                            </Transition>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-8 py-3 bg-indigo-600 border border-transparent rounded-2xl font-black text-sm text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-indigo-200 disabled:opacity-50"
                            >
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ResidentAuthenticatedLayout>
</template>
