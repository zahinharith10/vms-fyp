<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import VisitorAuthenticatedLayout from '@/Layouts/VisitorAuthenticatedLayout.vue';
import ParkingMap from '@/Components/ParkingMap.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    visit: Object,
    qrCodeSvg: String,
});

const showMap = ref(false);
const checkoutType = ref('final'); // 'final' or 'temp'

let echoChannel = null;

onMounted(() => {
    if (!window.Echo) return;

    // Listen for real-time status updates on this specific visit
    echoChannel = window.Echo.channel(`visit.${props.visit.id}`)
        .listen('.visit.status.updated', (e) => {
            // Reload only the visit prop from server — instant, no full page reload
            router.reload({
                only: ['visit'],
                preserveState: true,
                preserveScroll: true,
            });
        });
});

onUnmounted(() => {
    if (echoChannel) {
        window.Echo.leaveChannel(`visit.${props.visit.id}`);
    }
});

const qrToken = computed(() => {
    return `${props.visit.qr_code_token}:${checkoutType.value}`;
});

const displayToken = computed(() => {
    const token = props.visit.qr_code_token;
    if (!token) return '';
    if (token.startsWith('PRE_REG_')) {
        return 'PRE_REG_' + token.substring(8, 12);
    }
    return token.substring(0, 8);
});
</script>


<template>
    <Head title="My QR Code" />

    <VisitorAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visitor QR Code</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex flex-col items-center">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-gray-800">Your QR Code</h3>
                            <p class="text-sm text-gray-500 mt-2">Purpose: {{ visit.purpose }}</p>
                            <p class="text-sm text-gray-500">Unit: {{ visit.unit_number }}</p>
                        </div>
                        
                        <div v-if="visit.status === 'Checked In'" class="mb-8 w-full max-w-xs">
                            <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-3 text-center">Checkout Intent</label>
                            <div class="flex bg-gray-100 p-1 rounded-2xl border border-gray-200">
                                <button 
                                    @click="checkoutType = 'temp'"
                                    :class="checkoutType === 'temp' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase transition-all"
                                >
                                    🏃 Temporary
                                </button>
                                <button 
                                    @click="checkoutType = 'final'"
                                    :class="checkoutType === 'final' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase transition-all"
                                >
                                    🏁 Final
                                </button>
                            </div>
                            <p class="text-[10px] text-center mt-2 font-bold" :class="checkoutType === 'temp' ? 'text-orange-600' : 'text-green-600'">
                                {{ checkoutType === 'temp' ? 'Going out for a bit? Use this to re-enter later.' : 'Finished your visit? Use this for final checkout.' }}
                            </p>
                        </div>

                        <div v-if="visit.status === 'Temporarily Out'" class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-2xl text-center w-full max-w-xs">
                            <span class="text-2xl block mb-1">🏃</span>
                            <h4 class="text-sm font-black text-orange-950 uppercase tracking-widest">Temporarily Out</h4>
                            <p class="text-[10px] text-orange-700 font-bold mt-0.5 leading-relaxed">
                                You have checked out temporarily. Present this QR code at the gate to re-enter.
                            </p>
                        </div>

                        <div class="bg-white p-4 border-2 border-gray-200 rounded-lg shadow-lg">
                            <img :src="route('qr.dynamic') + '?data=' + encodeURIComponent(qrToken)" class="h-[250px] w-[250px] block object-contain" alt="Visitor Pass QR" />
                        </div>
                        
                        <!-- Parking Map Section -->
                        <div v-if="visit.parking_lot_number" class="mt-6 w-full">
                            <div class="border-t border-gray-100 pt-6">
                                <button
                                    @click="showMap = !showMap"
                                    class="w-full flex items-center justify-between px-5 py-3.5 rounded-2xl border-2 transition-all font-black text-sm uppercase tracking-widest"
                                    :class="showMap ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'"
                                >
                                    <span class="flex items-center gap-2">
                                        <span>🗺️</span>
                                        View Parking Map — Lot {{ visit.parking_lot_number }}
                                    </span>
                                    <span class="text-lg transition-transform duration-300" :class="showMap ? 'rotate-180' : ''">⌄</span>
                                </button>

                                <div v-if="showMap" class="mt-4 bg-gray-50 rounded-2xl p-4 border border-gray-100">
                                    <ParkingMap :assigned-lot="visit.parking_lot_number" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 w-full text-center">
                            <Link :href="route('visitor.dashboard')" class="text-indigo-600 hover:text-indigo-900 underline font-bold">
                                Back to Dashboard
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <span class="text-blue-400">ℹ️</span>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Visitor Instructions</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <li>Keep this screen open when approaching the guard house.</li>
                                    <li>The guard will scan this QR code to check you in.</li>
                                    <li>Ensure your screen brightness is sufficient for scanning.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </VisitorAuthenticatedLayout>
</template>
