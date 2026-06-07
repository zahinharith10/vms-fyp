<script setup>
import VisitorAuthenticatedLayout from '@/Layouts/VisitorAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    autofill: Object,
});

const form = useForm({
    name: props.autofill?.name || '',
    email: props.autofill?.email || '',
    phone: props.autofill?.phone || '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post(route('visitor.inquiries.store'));
};
</script>

<template>
    <Head title="New Inquiry" />

    <VisitorAuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('visitor.inquiries.index')" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">New Inquiry</h2>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <!-- Form Header -->
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 px-8 py-6 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-indigo-100 dark:bg-indigo-950/50 rounded-2xl flex items-center justify-center">
                            <span class="text-xl">✉️</span>
                        </div>
                        <div>
                            <h3 class="font-black text-gray-900 dark:text-white">Submit an Inquiry</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Our management team will respond as soon as possible.</p>
                        </div>
                    </div>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submit" class="p-8 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Name -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Full Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                placeholder="Your full name"
                                required
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Phone Number</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                placeholder="e.g. 0123456789"
                            />
                            <p v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Email Address</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            placeholder="your@email.com"
                            required
                        />
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Subject -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Subject</label>
                        <input
                            v-model="form.subject"
                            type="text"
                            class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            placeholder="What is your inquiry about?"
                            required
                        />
                        <p v-if="form.errors.subject" class="text-red-500 text-xs mt-1">{{ form.errors.subject }}</p>
                    </div>

                    <!-- Message -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Message</label>
                        <textarea
                            v-model="form.message"
                            rows="5"
                            class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"
                            placeholder="Describe your inquiry in detail..."
                            required
                        ></textarea>
                        <p v-if="form.errors.message" class="text-red-500 text-xs mt-1">{{ form.errors.message }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-black py-3 rounded-2xl text-sm uppercase tracking-widest transition-all shadow-md shadow-indigo-200 dark:shadow-none"
                        >
                            {{ form.processing ? 'Submitting...' : 'Submit Inquiry' }}
                        </button>
                        <Link
                            :href="route('visitor.inquiries.index')"
                            class="px-5 py-3 rounded-2xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 font-black text-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </VisitorAuthenticatedLayout>
</template>
