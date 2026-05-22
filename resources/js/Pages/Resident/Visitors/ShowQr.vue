<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    visit: Object,
    qrCodeSvg: String,
});
</script>

<template>
    <Head title="Visitor QR Code" />

    <ResidentAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visitor QR Code</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex flex-col items-center">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-gray-800">{{ visit.visitor?.name }}</h3>
                            <p class="text-gray-600">{{ visit.visitor?.phone }}</p>
                            <p class="text-sm text-gray-500 mt-2">Purpose: {{ visit.purpose }}</p>
                        </div>
                        
                        <div class="bg-white p-4 border-2 border-gray-200 rounded-lg shadow-lg" v-html="qrCodeSvg"></div>
                        
                        <p class="text-sm text-gray-500 mt-4">Show this QR code to the guard at the entrance.</p>
                        
                        <Link :href="route('resident.visitors.index')" class="mt-6 text-indigo-600 hover:text-indigo-900 underline">
                            Back to My Visitors
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </ResidentAuthenticatedLayout>
</template>
