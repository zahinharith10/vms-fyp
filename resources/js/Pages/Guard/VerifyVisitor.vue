<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import FaceCapture from '@/Components/FaceCapture.vue';
import { euclideanDistance } from 'face-api.js';
import axios from 'axios';

const props = defineProps({
    visit: Object,
    parking: Object,
});

const isLoading = ref(false);
const faceVerified = ref(false);
const verificationError = ref(null);
const visitData = ref(props.visit);
const pollingInterval = ref(null);
const hasTriggeredAutoCheckIn = ref(false);

const startPolling = () => {
    if (pollingInterval.value) return;
    
    pollingInterval.value = setInterval(async () => {
        try {
            // Select route based on type
            const showRoute = props.visit.is_delivery 
                ? route('guard.scan.show-delivery', visitData.value.id)
                : route('guard.scan.show', visitData.value.id);

            const response = await axios.get(showRoute, {
                headers: { 'Accept': 'application/json' }
            });
            
            if (response.data.visit) {
                visitData.value = response.data.visit;
                if (visitData.value.status === 'Approved') {
                    stopPolling();
                }
            }
        } catch (err) {
            console.error("Polling error:", err);
        }
    }, 5000);
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

onMounted(() => {
    if (visitData.value.status === 'Pending') {
        startPolling();
    } else if (visitData.value.status === 'Checked In') {
        // Automatically trigger check-out based on intent
        const isTemporary = visitData.value.checkout_intent === 'temp';
        setTimeout(() => {
            checkOut(isTemporary);
        }, 1000); // 1s delay for visual feedback
    } else if (visitData.value.status === 'Temporarily Out') {
        // Automatically trigger check-in (re-entry)
        // Note: Face verification might be required depending on policy, 
        // but for "express" re-entry, we can automate it if face matches in handleFaceDetected
    }
});

onBeforeUnmount(() => {
    stopPolling();
});

const handleFaceDetected = (detection) => {
    if (!detection || !visitData.value?.visitor?.face_descriptor) {
        faceVerified.value = false;
        return;
    }

    try {
        const storedDescriptor = typeof visitData.value.visitor.face_descriptor === 'string' 
            ? JSON.parse(visitData.value.visitor.face_descriptor)
            : visitData.value.visitor.face_descriptor;
            
        const distance = euclideanDistance(detection.descriptor, Object.values(storedDescriptor));
        
        // Threshold of 0.6 is common for face recognition
        if (distance < 0.5) {
            faceVerified.value = true;
            verificationError.value = null;

            // Automatically trigger check-in if Approved or Temporarily Out
            if ((visitData.value.status === 'Approved' || visitData.value.status === 'Temporarily Out') && !hasTriggeredAutoCheckIn.value) {
                hasTriggeredAutoCheckIn.value = true;
                isLoading.value = true;
                setTimeout(() => {
                    checkIn(true); // Bypass verification check because we already verified it!
                }, 500); // 500ms delay for high-speed premium feedback
            }
        } else {
            faceVerified.value = false;
            verificationError.value = "Face does not match visitor records.";
        }
    } catch (err) {
        console.error("Verification error:", err);
        verificationError.value = "Error during face verification.";
    }
};

const checkIn = async (bypassVerification = false) => {
    if (!bypassVerification && !faceVerified.value && visitData.value.status === 'Approved') return;
    
    isLoading.value = true;
    try {
        const checkInRoute = props.visit.is_delivery 
            ? route('guard.scan.checkin-delivery') 
            : route('guard.scan.checkin');
            
        const payload = props.visit.is_delivery 
            ? { log_id: visitData.value.id } 
            : { visit_id: visitData.value.id };

        const response = await axios.post(checkInRoute, payload);
        
        if (response.data.success) {
            // Redirect to active logs
            router.visit(route('guard.logs.active'));
        } else {
            alert(response.data.message || 'Check-in failed.');
            hasTriggeredAutoCheckIn.value = false; // Reset on failure so they can try again
        }
    } catch (err) {
        alert(err.response?.data?.message || 'An error occurred during check-in.');
        hasTriggeredAutoCheckIn.value = false; // Reset on failure so they can try again
    } finally {
        isLoading.value = false;
    }
};

const checkOut = async (isTemporary = false) => {
    isLoading.value = true;
    try {
        const checkOutRoute = props.visit.is_delivery 
            ? route('guard.scan.checkout-delivery') 
            : route('guard.scan.checkout');
            
        const payload = props.visit.is_delivery 
            ? { log_id: visitData.value.id, is_temporary: isTemporary } 
            : { visit_id: visitData.value.id, is_temporary: isTemporary };

        const response = await axios.post(checkOutRoute, payload);
        
        if (response.data.success) {
            router.visit(route('guard.scan'));
        } else {
            alert(response.data.message || 'Check-out failed.');
        }
    } catch (err) {
        alert(err.response?.data?.message || 'An error occurred during check-out.');
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="Verify Visitor" />

    <GuardAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verify Visitor Entry</h2>
        </template>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                <div class="bg-slate-900 p-4 flex justify-between items-center">
                     <span class="text-xs font-black text-indigo-400 uppercase tracking-widest">Verification Result</span>
                        <span 
                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter"
                        :class="{
                            'bg-yellow-400 text-yellow-900': visitData.status === 'Pending',
                            'bg-blue-400 text-blue-900': visitData.status === 'Approved',
                            'bg-green-400 text-green-900': visitData.status === 'Checked In',
                            'bg-orange-400 text-orange-900': visitData.status === 'Temporarily Out',
                            'bg-gray-400 text-gray-900': visitData.status === 'Checked Out',
                            'bg-red-400 text-red-900': visitData.status === 'Rejected'
                        }"
                    >
                        {{ visitData.status }}
                    </span>
                </div>

                <div class="p-8">
                    <div class="flex items-center mb-8 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <img 
                            v-if="visitData.visitor.photo" 
                            :src="'/storage/' + visitData.visitor.photo" 
                            class="h-24 w-24 rounded-2xl object-cover mr-6 shadow-md border-2 border-white"
                        >
                        <div v-else class="h-24 w-24 rounded-2xl bg-gray-200 flex items-center justify-center mr-6 border-2 border-white">
                            <span class="text-gray-400 text-4xl">👤</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Visitor Name</p>
                            <p class="text-2xl font-black text-gray-800 leading-tight">{{ visitData.visitor.name }}</p>
                            <div class="flex gap-4 mt-1">
                                <p class="text-indigo-600 font-bold">{{ visitData.visitor.phone }}</p>
                                <p v-if="visitData.visitor.vehicle_number" class="text-gray-500 font-black uppercase tracking-wider text-sm border-l pl-4 border-gray-200">
                                    🚗 {{ visitData.visitor.vehicle_number }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">destination</p>
                            <p class="font-black text-gray-800 mt-1">UNIT {{ visitData.unit_number }}</p>
                        </div>
                        <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">visitation purpose</p>
                            <p class="font-black text-gray-800 mt-1 truncate">{{ visitData.purpose }}</p>
                        </div>
                        <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Visitor Parking</p>
                            <p v-if="visitData.parking_lot_number" class="font-black text-indigo-650 mt-1">🅿️ LOT {{ visitData.parking_lot_number }}</p>
                            <p v-else-if="!props.visit.is_delivery && parking && visitData.status === 'Approved'" class="font-black mt-1" :class="parking.available > 0 ? 'text-green-650' : 'text-red-500'">
                                {{ parking.available > 0 ? `Auto Assign (${parking.available} free)` : '🚨 PARKING FULL' }}
                            </p>
                            <p v-else class="font-black text-gray-400 mt-1">N/A</p>
                        </div>
                    </div>

                    <!-- Face Recognition Section -->
                    <div v-if="visitData.status === 'Approved' || visitData.status === 'Temporarily Out'" class="mb-8">
                        <div class="bg-indigo-50 rounded-2xl p-6 border border-indigo-100">
                            <h4 class="text-sm font-black text-indigo-900 uppercase tracking-widest mb-4 flex items-center">
                                <span class="text-xl mr-2">📸</span> {{ visitData.status === 'Approved' ? 'Identity Verification Required' : 'Re-entry Verification' }}
                            </h4>
                            
                            <div class="bg-white rounded-xl p-4 shadow-inner">
                                <FaceCapture :allow-upload="false" @face-detected="handleFaceDetected" />
                            </div>

                            <div v-if="faceVerified" class="mt-4 p-3 bg-green-100 text-green-700 rounded-xl flex items-center justify-between font-bold text-sm">
                                <span class="flex items-center"><span class="mr-2">✅</span> Identity Verified: Match Confirmed</span>
                                <span class="text-xs uppercase tracking-wider text-green-600 animate-pulse font-black flex items-center">
                                    Checking in... <span class="ml-2 inline-block h-3 w-3 rounded-full bg-green-600 animate-ping"></span>
                                </span>
                            </div>
                            <div v-else-if="props.visit.is_delivery" class="mt-4 p-3 bg-orange-50 text-orange-800 rounded-xl flex items-center font-bold text-xs border border-orange-100">
                                <span class="mr-2">⚡</span> Express Pass: Face match is optional for delivery riders. You can authorize entry instantly.
                            </div>
                            <div v-else-if="verificationError" class="mt-4 p-3 bg-red-100 text-red-700 rounded-xl flex items-center font-bold text-sm">
                                <span class="mr-2">❌</span> {{ verificationError }}
                            </div>
                            <div v-else class="mt-4 p-3 bg-indigo-100 text-indigo-700 rounded-xl flex items-center font-bold text-sm animate-pulse">
                                <span class="mr-2">🔍</span> Waiting for face detection...
                            </div>
                        </div>
                    </div>

                    <!-- Auto Checkout Section -->
                    <div v-if="visitData.status === 'Checked In'" class="mb-8">
                        <div class="bg-indigo-50 rounded-2xl p-6 border border-indigo-100 text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent mb-3"></div>
                            <h4 class="text-sm font-black text-indigo-900 uppercase tracking-widest mb-1">
                                {{ visitData.checkout_intent === 'temp' ? '⚡ Processing Temporary Leave' : '⚡ Processing Final Check-out' }}
                            </h4>
                            <p class="text-xs text-indigo-700 font-medium">
                                Automating transaction based on the visitor's choice...
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <button 
                            v-if="visitData.status === 'Approved'"
                            @click="checkIn"
                            class="col-span-2 bg-green-600 hover:bg-green-700 text-white font-black py-5 rounded-2xl text-lg shadow-lg shadow-green-100 transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isLoading || (!faceVerified && !props.visit.is_delivery)"
                        >
                            ✅ AUTHORIZE CHECK-IN
                        </button>
                        <template v-if="visitData.status === 'Checked In'">
                            <button 
                                @click="checkOut(false)"
                                class="col-span-1 bg-red-600 hover:bg-red-750 text-white font-black py-5 rounded-2xl text-sm shadow-lg shadow-red-100 transition-all flex items-center justify-center uppercase tracking-wider"
                                :disabled="isLoading"
                            >
                                🚪 Final Check-Out
                            </button>
                            <button 
                                @click="checkOut(true)"
                                class="col-span-1 bg-orange-600 hover:bg-orange-750 text-white font-black py-5 rounded-2xl text-sm shadow-lg shadow-orange-100 transition-all flex items-center justify-center uppercase tracking-wider"
                                :disabled="isLoading"
                            >
                                ⏱️ Temporary Leave
                            </button>
                        </template>
                        
                        <Link 
                            :href="route('guard.scan')"
                            class="col-span-2 text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-black py-4 rounded-2xl transition-all border border-gray-200"
                        >
                            🔄 SCAN NEW CODE
                        </Link>
                    </div>

                    <div v-if="visitData.status === 'Pending'" class="mt-6 p-4 bg-yellow-50 rounded-xl border border-yellow-200 flex items-start">
                         <span class="mr-3 text-lg">⚠️</span>
                         <p class="text-xs text-yellow-800 font-medium">
                            <strong class="uppercase block text-[10px] mb-1">Access Suspended</strong>
                            This visit is waiting for resident approval. The visitor cannot be checked in until approved.
                         </p>
                    </div>
                </div>
            </div>
        </div>
    </GuardAuthenticatedLayout>
</template>
