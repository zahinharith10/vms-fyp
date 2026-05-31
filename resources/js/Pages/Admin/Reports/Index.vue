<script setup>
import AdminAuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const reports = [
    {
        title: 'User Report',
        description: 'Export all Residents, Guards, and Delivery Personnel data including their basic information and access levels.',
        url: route('admin.reports.export-users'),
        icon: '👥',
        color: 'text-indigo-600 bg-indigo-100',
    },
    {
        title: 'House Units Report',
        description: 'Export a list of all registered house units, blocks, floors, and the total number of residents in each.',
        url: route('admin.reports.export-units'),
        icon: '🏢',
        color: 'text-emerald-600 bg-emerald-100',
    },
    {
        title: 'Visit Applications Report',
        description: 'Export all visit and delivery requests, including their approval status and who approved them.',
        url: route('admin.reports.export-applications'),
        icon: '📋',
        color: 'text-blue-600 bg-blue-100',
    },
    {
        title: 'Visit Records Report',
        description: 'Export the actual entry and exit logs (sessions) for all visitors, including exact check-in times and duration.',
        url: route('admin.reports.export-records'),
        icon: '⏱️',
        color: 'text-orange-600 bg-orange-100',
    },
];

</script>

<template>
    <Head title="System Reports" />

    <AdminAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">System Reports</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-8">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight">Generate CSV Reports</h3>
                    <p class="text-gray-500 mt-1">Download raw data from the system for analytics, backup, and auditing purposes.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                    <div 
                        v-for="(report, index) in reports" 
                        :key="index"
                        class="bg-white overflow-hidden shadow-sm border border-gray-100 sm:rounded-3xl hover:shadow-md transition-shadow p-6 flex flex-col justify-between"
                    >
                        <div class="flex items-start">
                            <div :class="['h-14 w-14 rounded-2xl flex items-center justify-center text-3xl shrink-0', report.color]">
                                {{ report.icon }}
                            </div>
                            <div class="ml-5">
                                <h4 class="text-lg font-black text-gray-900 tracking-tight">{{ report.title }}</h4>
                                <p class="text-sm text-gray-500 mt-2">{{ report.description }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <a 
                                :href="report.url" 
                                target="_blank"
                                class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-xs font-black uppercase tracking-widest rounded-xl transition"
                            >
                                <span class="mr-2">📥</span> Download CSV
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminAuthenticatedLayout>
</template>
