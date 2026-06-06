<script setup>
import AdminAuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    admin: Object,
});

const isEditing = ref(false);

const form = useForm({
    name: props.admin.name,
    email: props.admin.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.patch(route('admin.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
            isEditing.value = false;
        },
    });
};

const cancelEdit = () => {
    isEditing.value = false;
    form.reset();
};
</script>

<template>
    <Head title="Admin Profile" />

    <AdminAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profile Settings</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section class="max-w-xl">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Admin Information</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Update your account's profile information, email address, and password.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <label for="name" class="block font-medium text-sm text-gray-700">Name</label>
                                <input
                                    id="name"
                                    type="text"
                                    :disabled="!isEditing"
                                    :class="[!isEditing ? 'bg-gray-55 border-transparent text-gray-500 shadow-none cursor-not-allowed' : 'bg-white border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-gray-800']"
                                    class="rounded-md shadow-sm mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                />
                                <div v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
                                <input
                                    id="email"
                                    type="email"
                                    :disabled="!isEditing"
                                    :class="[!isEditing ? 'bg-gray-55 border-transparent text-gray-500 shadow-none cursor-not-allowed' : 'bg-white border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-gray-800']"
                                    class="rounded-md shadow-sm mt-1 block w-full"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                />
                                <div v-if="form.errors.email" class="text-sm text-red-600 mt-2">{{ form.errors.email }}</div>
                            </div>

                            <div v-if="isEditing" class="pt-4 border-t border-gray-200 mt-6">
                                <h3 class="text-md font-medium text-gray-900 mb-4">Change Password</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="password" class="block font-medium text-sm text-gray-700">New Password <span class="text-gray-400 font-normal">(Leave blank to keep current)</span></label>
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                                            autocomplete="new-password"
                                        />
                                        <div v-if="form.errors.password" class="text-sm text-red-600 mt-2">{{ form.errors.password }}</div>
                                    </div>

                                    <div>
                                        <label for="password_confirmation" class="block font-medium text-sm text-gray-700">Confirm Password</label>
                                        <input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            type="password"
                                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                                            autocomplete="new-password"
                                        />
                                        <div v-if="form.errors.password_confirmation" class="text-sm text-red-600 mt-2">{{ form.errors.password_confirmation }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <template v-if="!isEditing">
                                    <button type="button" @click="isEditing = true" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        ✏️ Edit Profile
                                    </button>
                                </template>
                                <template v-else>
                                    <button type="button" @click="cancelEdit" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-350 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Cancel
                                    </button>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :disabled="form.processing">
                                        Save Changes
                                    </button>
                                </template>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </AdminAuthenticatedLayout>
</template>
