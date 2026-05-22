<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import FaceCapture from '@/Components/FaceCapture.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Face Recognition Test" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Face Recognition Test</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4">Camera & AI Test</h3>
                        <p class="mb-4 text-sm text-gray-600">Please allow camera access. The AI models will load from the server.</p>
                        
                        <div class="bg-gray-100 p-4 rounded flex justify-center">
                            <FaceCapture />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
