<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('resident.visitors.index'));
    }
};
import { ref } from 'vue';

const purposeOption = ref('Friends/Family');
const customPurpose = ref('');

const form = useForm({
    name: '',
    email: '',
    purpose: '',
});

const submit = () => {
    form.purpose = purposeOption.value === 'Other' ? (customPurpose.value || 'Other') : purposeOption.value;
    form.post(route('resident.visitors.store'));
};
</script>

<template>
    <Head title="Pre-Register Visitor" />

    <ResidentAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pre-Register Visitor</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-6">
                    <button @click="goBack" class="inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800 transition">
                        ← Back
                    </button>
                </div>

                <!-- Registration Card -->
                <div class="p-8 bg-white dark:bg-gray-900 shadow-xl dark:shadow-none sm:rounded-3xl border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center mb-8">
                        <div class="h-16 w-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-3xl">
                            ➕
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-black text-gray-900 dark:text-gray-100 uppercase tracking-tighter">Pre-Register Visitor (VIP Entry)</h3>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Provide guest information to generate a pre-approved digital pass.</p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Visitor Details Section -->
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Visitor's Full Name</label>
                                <input 
                                    id="name" 
                                    type="text" 
                                    placeholder="e.g. John Doe"
                                    v-model="form.name" 
                                    class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4" 
                                    required 
                                />
                                <div v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400 font-bold">{{ form.errors.name }}</div>
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label for="email" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Visitor's Email Address</label>
                                <input 
                                    id="email" 
                                    type="email" 
                                    placeholder="e.g. visitor@example.com"
                                    v-model="form.email" 
                                    class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4" 
                                    required 
                                />
                                <div v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400 font-bold">{{ form.errors.email }}</div>
                            </div>

                            <!-- Purpose of Visit -->
                            <div>
                                <label for="purpose" class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Purpose of Visit</label>
                                <select 
                                    id="purpose-select" 
                                    v-model="purposeOption" 
                                    class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4 mb-3"
                                >
                                    <option value="Friends/Family">Friends / Family</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Other">Other</option>
                                </select>
                                
                                <input 
                                    v-if="purposeOption === 'Other'"
                                    type="text" 
                                    placeholder="Please specify other purpose"
                                    v-model="customPurpose"
                                    class="block w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 dark:text-gray-300 py-3 px-4"
                                    required
                                />
                                <div v-if="form.errors.purpose" class="mt-2 text-sm text-red-600 dark:text-red-400 font-bold">{{ form.errors.purpose }}</div>
                            </div>
                        </div>

                        <!-- Info Alert -->
                        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 border-l-4 border-indigo-500 rounded-r-2xl text-sm font-semibold text-indigo-800 dark:text-indigo-300">
                            💡 **How VIP Pre-Registration Works:** Once registered, we will generate an instant approved entry pass. You can copy the unique link and send it to your guest. If the guest is completely new to the system, they will simply take a quick selfie to activate their QR code before arriving!
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end pt-4">
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl shadow-lg transition disabled:opacity-50"
                            >
                                {{ form.processing ? 'Registering...' : 'Pre-Register Guest' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ResidentAuthenticatedLayout>
</template>
