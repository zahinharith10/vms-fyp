<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3'; // Import router
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import axios from 'axios';

// Scanner state
const scannerActive = ref(false);
const scanResult = ref(null);
const errorMessage = ref(null);
let html5QrCode = null;

const startScanner = async () => {
    errorMessage.value = null;
    scannerActive.value = true; // Show div FIRST
    
    // Wait for Vue to update the DOM
    await new Promise(resolve => setTimeout(resolve, 100));
    
    try {
        const { Html5Qrcode } = await import('html5-qrcode');
        html5QrCode = new Html5Qrcode("qr-reader");
        
        await html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            onScanFailure
        );
    } catch (err) {
        errorMessage.value = "Camera access denied or error: " + err.message;
        scannerActive.value = false;
    }
};

const stopScanner = async () => {
    if (html5QrCode && scannerActive.value) {
        try {
            await html5QrCode.stop();
        } catch (err) {
            console.error("Error stopping scanner:", err);
        }
    }
    scannerActive.value = false;
};

const onScanSuccess = async (decodedText) => {
    await stopScanner();
    scanResult.value = decodedText;
    lookupAndRedirect(decodedText);
};

const onScanFailure = (error) => {
    // Ignore scan failures (no QR detected in frame)
};

const lookupAndRedirect = async (token) => {
    try {
        const response = await axios.post(route('guard.scan.lookup'), { token });
        const visitId = response.data.visit.id;
        const intent = response.data.visit.checkout_intent || 'final';
        
        if (response.data.is_delivery) {
            router.visit(route('guard.scan.verify-delivery', visitId) + '?intent=' + intent);
        } else {
            router.visit(route('guard.scan.verify', visitId) + '?intent=' + intent);
        }
    } catch (err) {
        if (err.response?.status === 404) {
            errorMessage.value = err.response.data.message || 'Invalid QR code.';
        } else {
            errorMessage.value = 'An error occurred while looking up the visit.';
        }
    }
};

const resetScanner = () => {
    scanResult.value = null;
    errorMessage.value = null;
};

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<template>
    <Head title="Scan Visitor QR" />

    <GuardAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visitor Entry Point</h2>
        </template>

        <div class="max-w-2xl mx-auto">
            <!-- Scanner Section -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden mb-8 border border-gray-100">
                <div class="bg-indigo-600 p-4 text-white flex justify-between items-center">
                    <span class="text-xs font-black uppercase tracking-widest">Digital Scanner</span>
                    <span v-if="scannerActive" class="flex items-center text-[10px] font-bold">
                        <span class="h-2 w-2 bg-red-400 rounded-full mr-2 animate-pulse"></span> CAMERA ACTIVE
                    </span>
                </div>

                <div class="p-8">
                    <div v-if="!scannerActive" class="text-center py-10">
                        <div class="mb-6">
                            <div class="h-24 w-24 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1l-3 3h2v5H7l3 3-3 3h2v5M17 13h-4v4h4v-4zM7 9H3v4h4V9zM17 5h-4v4h4V5zM7 5H3v4h4V5z"></path></svg>
                            </div>
                            <h3 class="text-xl font-black text-gray-800">Ready to Scan?</h3>
                            <p class="text-gray-500 text-sm mt-1">Position the visitor's QR code in the frame.</p>
                        </div>
                        <button 
                            @click="startScanner" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 px-10 rounded-2xl text-lg shadow-lg shadow-indigo-100 transition-all active:scale-95"
                        >
                            📷 Activate Scanner
                        </button>
                    </div>

                    <!-- QR Reader Container -->
                    <div class="relative">
                        <div id="qr-reader" class="mx-auto overflow-hidden rounded-2xl border-4 border-gray-100 shadow-inner" style="max-width: 400px;" v-show="scannerActive"></div>
                        <div v-if="scannerActive" class="absolute inset-0 pointer-events-none border-[40px] border-black/20 flex items-center justify-center">
                             <div class="h-64 w-64 border-2 border-dashed border-indigo-400/50 rounded-xl"></div>
                        </div>
                    </div>

                    <div v-if="scannerActive" class="text-center mt-6">
                        <button @click="stopScanner" class="bg-red-50 text-red-600 font-black px-6 py-2 rounded-xl text-sm hover:bg-red-100 transition">
                            Cancel Scan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl mb-8 flex items-center">
                <span class="text-3xl mr-4">⚠️</span>
                <div>
                     <p class="font-black uppercase text-xs tracking-widest mb-1">Scan Error</p>
                     <p class="font-bold">{{ errorMessage }}</p>
                </div>
                <button @click="resetScanner" class="ml-auto bg-white px-4 py-2 rounded-xl text-sm font-black shadow-sm ring-1 ring-red-200">Retry</button>
            </div>
        </div>
    </GuardAuthenticatedLayout>
</template>
