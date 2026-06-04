<script setup>
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { formatMalaysiaTime } from '@/utils/datetime';

const props = defineProps({
    visitRecords: Array,
});
</script>

<template>
    <Head title="Visit Records" />

    <GuardAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">All Visit Records</h2>
        </template>

        <div class="max-w-4xl mx-auto">
            <div v-if="visitRecords.length === 0" class="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-800 transition-colors duration-200">
                <div class="h-20 w-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-4xl">📋</span>
                </div>
                <h3 class="text-xl font-black text-gray-800 dark:text-white">No Records Found</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1">There are no visit or delivery records yet.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-4">
                <div v-for="log in visitRecords" :key="log.type + log.id" class="bg-white dark:bg-gray-900 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-between group hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-200">
                    <div class="flex items-center">
                        <div class="h-16 w-16 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center mr-5 overflow-hidden border-2 border-white dark:border-gray-700 shadow-sm ring-1 ring-gray-100 dark:ring-gray-700">
                             <img v-if="log.photo" :src="'/storage/' + log.photo" class="h-full w-full object-cover" />
                             <span v-else class="text-gray-400 dark:text-gray-500 font-black text-2xl uppercase">{{ log.name.charAt(0) }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest"
                                    :class="log.is_delivery ? 'bg-orange-100 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400' : 'bg-indigo-100 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400'"
                                >
                                    {{ log.type }}
                                </span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest"
                                    :class="{
                                        'bg-green-100 dark:bg-green-950/40 text-green-600 dark:text-green-400': log.status === 'Checked In',
                                        'bg-red-100 dark:bg-red-950/40 text-red-600 dark:text-red-400': log.status === 'Checked Out',
                                        'bg-yellow-100 dark:bg-yellow-950/40 text-yellow-600 dark:text-yellow-400': log.status === 'Temporarily Out',
                                        'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400': log.status === 'Pending' || log.status === 'Approved'
                                    }"
                                >
                                    {{ log.status }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tighter">Created at {{ formatMalaysiaTime(log.created_at) }}</span>
                            </div>
                            <h4 class="font-black text-gray-900 dark:text-white leading-tight">{{ log.name }}</h4>
                            <div class="grid grid-cols-3 gap-x-4 gap-y-1 mt-2">
                                <div>
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Plate Number</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">{{ log.vehicle_number || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Unit</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">{{ log.unit_number }}</p>
                                </div>
                                <div v-if="log.parking_lot_number">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Parking Lot</p>
                                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">🅿️ Lot {{ log.parking_lot_number }}</p>
                                </div>
                                <div v-if="log.entry_time">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Entry Time</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ formatMalaysiaTime(log.entry_time) }}</p>
                                </div>
                                <div v-if="log.exit_time">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Exit Time</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ formatMalaysiaTime(log.exit_time) }}</p>
                                </div>
                                <div class="col-span-3">
                                    <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none">Purpose</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase truncate max-w-[200px]">{{ log.purpose }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuardAuthenticatedLayout>
</template>
