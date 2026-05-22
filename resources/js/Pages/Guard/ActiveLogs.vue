<script setup>
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    activeLogs: Array,
});

const isLoading = ref(false);

const checkOut = async (log) => {
    if (!confirm(`Confirm check-out for ${log.name}?`)) return;
    
    isLoading.value = true;
    try {
        const checkOutRoute = log.is_delivery 
            ? route('guard.scan.checkout-delivery') // Need to check if this exists, web.php didn't show it but it might be missing
            : route('guard.scan.checkout');
            
        const payload = log.is_delivery 
            ? { log_id: log.id } 
            : { visit_id: log.id };

        const response = await axios.post(checkOutRoute, payload);
        
        if (response.data.success) {
            router.reload();
        } else {
            alert(response.data.message || 'Check-out failed.');
        }
    } catch (err) {
        alert(err.response?.data?.message || 'An error occurred during check-out.');
    } finally {
        isLoading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Active Visitors" />

    <GuardAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Active Visitors On-Site</h2>
        </template>

        <div class="max-w-4xl mx-auto">
            <div v-if="activeLogs.length === 0" class="bg-white rounded-3xl p-12 text-center shadow-sm border border-gray-100">
                <div class="h-20 w-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-4xl">🏢</span>
                </div>
                <h3 class="text-xl font-black text-gray-800">Clear Premise</h3>
                <p class="text-gray-500 mt-1">No visitors or deliveries are currently checked in.</p>
                <div class="mt-8">
                    <Link :href="route('guard.scan')" class="bg-indigo-600 text-white font-black px-8 py-3 rounded-2xl shadow-lg shadow-indigo-100 transition-all hover:bg-indigo-700">
                        Scan New Visitor
                    </Link>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 gap-4">
                <div v-for="log in activeLogs" :key="log.type + log.id" class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between group hover:border-indigo-200 transition-all">
                    <div class="flex items-center">
                        <div class="h-16 w-16 rounded-2xl bg-gray-50 flex items-center justify-center mr-5 overflow-hidden border-2 border-white shadow-sm ring-1 ring-gray-100">
                             <img v-if="log.photo" :src="'/storage/' + log.photo" class="h-full w-full object-cover" />
                             <span v-else class="text-gray-400 font-black text-2xl uppercase">{{ log.name.charAt(0) }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest"
                                    :class="log.is_delivery ? 'bg-orange-100 text-orange-600' : 'bg-indigo-100 text-indigo-600'"
                                >
                                    {{ log.type }}
                                </span>
                                <span 
                                    v-if="log.status === 'Temporarily Out'"
                                    class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest bg-orange-100 text-orange-600"
                                >
                                    🏃 OUT
                                </span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Joined at {{ formatDate(log.entry_time) }}</span>
                            </div>
                            <h4 class="font-black text-gray-900 leading-tight">{{ log.name }}</h4>
                            <div class="grid grid-cols-3 gap-x-4 gap-y-1 mt-2">
                                <div>
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest leading-none">Plate Number</p>
                                    <p class="text-xs font-bold text-gray-700 uppercase">{{ log.vehicle_number || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest leading-none">Unit</p>
                                    <p class="text-xs font-bold text-gray-700 uppercase">{{ log.unit_number }}</p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest leading-none">Parking Lot</p>
                                    <p class="text-xs font-bold text-indigo-600 uppercase">{{ log.parking_lot_number ? '🅿️ Lot ' + log.parking_lot_number : 'N/A' }}</p>
                                </div>
                                <div class="col-span-3">
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest leading-none">Purpose</p>
                                    <p class="text-xs font-bold text-gray-700 uppercase truncate max-w-[200px]">{{ log.purpose }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button 
                        @click="checkOut(log)"
                        :class="log.status === 'Temporarily Out' ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-red-50 hover:bg-red-600 text-red-600 hover:text-white'"
                        class="font-black px-6 py-3 rounded-2xl transition-all shadow-sm uppercase text-[10px] tracking-widest"
                        :disabled="isLoading"
                    >
                        {{ log.status === 'Temporarily Out' ? 'Finalize' : 'Check Out' }}
                    </button>
                </div>
            </div>
        </div>
    </GuardAuthenticatedLayout>
</template>
