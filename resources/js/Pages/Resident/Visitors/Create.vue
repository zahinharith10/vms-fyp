<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    purpose: 'Casual Visit',
});

const submit = () => {
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
                    <Link :href="route('resident.visitors.index')" class="inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800 transition">
                        ← Back to Visitors List
                    </Link>
                </div>

                <!-- Registration Card -->
                <div class="p-8 bg-white shadow-xl sm:rounded-3xl border border-gray-100">
                    <div class="flex items-center mb-8">
                        <div class="h-16 w-16 bg-indigo-100 rounded-2xl flex items-center justify-center text-3xl">
                            ➕
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-black text-gray-900 uppercase tracking-tighter">Pre-Register Visitor (VIP Entry)</h3>
                            <p class="text-sm font-bold text-gray-500">Provide guest information to generate a pre-approved digital pass.</p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Visitor Details Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div class="col-span-1 md:col-span-2">
                                <label for="name" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Visitor's Full Name</label>
                                <input 
                                    id="name" 
                                    type="text" 
                                    placeholder="e.g. John Doe"
                                    v-model="form.name" 
                                    class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 py-3 px-4" 
                                    required 
                                />
                                <div v-if="form.errors.name" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.name }}</div>
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label for="phone" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Visitor's Phone Number</label>
                                <input 
                                    id="phone" 
                                    type="text" 
                                    placeholder="e.g. 0123456789"
                                    v-model="form.phone" 
                                    class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 py-3 px-4" 
                                    required 
                                />
                                <div v-if="form.errors.phone" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.phone }}</div>
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label for="email" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Visitor's Email Address</label>
                                <input 
                                    id="email" 
                                    type="email" 
                                    placeholder="e.g. visitor@example.com"
                                    v-model="form.email" 
                                    class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 py-3 px-4" 
                                    required 
                                />
                                <div v-if="form.errors.email" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.email }}</div>
                            </div>

                            <!-- Purpose of Visit -->
                            <div class="col-span-1 md:col-span-2">
                                <label for="purpose" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Purpose of Visit</label>
                                <select 
                                    id="purpose" 
                                    v-model="form.purpose" 
                                    class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm font-bold text-gray-700 py-3 px-4"
                                >
                                    <option value="Casual Visit">Casual Visit (Family/Friend)</option>
                                    <option value="Contractor / Repair">Contractor / Repair Service</option>
                                    <option value="Delivery Service">Delivery Service</option>
                                    <option value="Emergency Visit">Emergency Visit</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div v-if="form.errors.purpose" class="mt-2 text-sm text-red-600 font-bold">{{ form.errors.purpose }}</div>
                            </div>
                        </div>

                        <!-- Info Alert -->
                        <div class="p-4 bg-indigo-50 border-l-4 border-indigo-500 rounded-r-2xl text-sm font-semibold text-indigo-800">
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
