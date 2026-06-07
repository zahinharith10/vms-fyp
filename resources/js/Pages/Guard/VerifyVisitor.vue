<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import FaceCapture from '@/Components/FaceCapture.vue';
import Modal from '@/Components/Modal.vue';
import { euclideanDistance } from 'face-api.js';
import axios from 'axios';

const props = defineProps({
    visit: Object,
    parking: Object,
});

const isLoading = ref(false);
const faceVerified = ref(false);
const verificationError = ref(null);
const showSuccessModal = ref(false);
const showCheckoutModal = ref(false);
const checkoutIsTemporary = ref(false);
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
        // Show popup immediately, then auto-checkout after 2s
        const isTemporary = visitData.value.checkout_intent === 'temp';
        checkoutIsTemporary.value = isTemporary;
        showCheckoutModal.value = true;
        setTimeout(() => {
            checkOut(isTemporary);
        }, 2000); // 2s so guard sees the popup before redirect
    } else if (visitData.value.status === 'Temporarily Out') {
        // Face verification required for re-entry — handled in handleFaceDetected
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
            showSuccessModal.value = true;

            // Automatically trigger check-in if Approved or Temporarily Out
            if ((visitData.value.status === 'Approved' || visitData.value.status === 'Temporarily Out') && !hasTriggeredAutoCheckIn.value) {
                hasTriggeredAutoCheckIn.value = true;
                setTimeout(() => {
                    checkIn(true); // Bypass verification check — already verified via face match
                }, 2000); // 2s delay so guard can see the popup before redirect
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
            ? (visitData.value.run_id ? { run_id: visitData.value.run_id } : { log_id: visitData.value.id })
            : { visit_id: visitData.value.id };

        const response = await axios.post(checkInRoute, payload);
        
        if (response.data.success) {
            // Redirect to active logs
            router.visit(route('guard.logs.active'));
        } else {
            alert(response.data.message || 'Check-in failed.');
            hasTriggeredAutoCheckIn.value = false; // Reset on failure so they can try again
            showSuccessModal.value = false;
        }
    } catch (err) {
        alert(err.response?.data?.message || 'An error occurred during check-in.');
        hasTriggeredAutoCheckIn.value = false; // Reset on failure so they can try again
        showSuccessModal.value = false;
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
            ? (visitData.value.run_id
                ? { run_id: visitData.value.run_id, is_temporary: isTemporary }
                : { log_id: visitData.value.id, is_temporary: isTemporary })
            : { visit_id: visitData.value.id, is_temporary: isTemporary };

        const response = await axios.post(checkOutRoute, payload);
        
        if (response.data.success) {
            router.visit(route('guard.scan'));
        } else {
            showCheckoutModal.value = false;
            alert(response.data.message || 'Check-out failed.');
        }
    } catch (err) {
        showCheckoutModal.value = false;
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
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl dark:shadow-indigo-950/10 overflow-hidden border border-gray-100 dark:border-gray-800/80 transition-all duration-300">
                <div class="bg-slate-900 dark:bg-slate-950 p-5 flex justify-between items-center border-b border-gray-800/50">
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
                    <!-- Visitor Details Card -->
                    <div class="flex items-center mb-8 bg-gray-50 dark:bg-gray-800/40 p-5 rounded-2xl border border-gray-100 dark:border-gray-800/50 transition-all">
                        <img 
                            v-if="visitData.visitor.photo" 
                            :src="'/storage/' + visitData.visitor.photo" 
                            class="h-24 w-24 rounded-2xl object-cover mr-6 shadow-md border-2 border-white dark:border-gray-700"
                        >
                        <div v-else class="h-24 w-24 rounded-2xl bg-gray-200 dark:bg-gray-800 flex items-center justify-center mr-6 border-2 border-white dark:border-gray-700">
                            <span class="text-gray-400 dark:text-gray-650 text-4xl">👤</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Visitor Name</p>
                            <p class="text-2xl font-black text-gray-800 dark:text-white leading-tight mt-0.5">{{ visitData.visitor.name }}</p>
                            <div class="flex gap-4 mt-1.5">
                                <p class="text-indigo-600 dark:text-indigo-400 font-bold text-sm">{{ visitData.visitor.phone }}</p>
                                <p v-if="visitData.visitor.vehicle_number" class="text-gray-500 dark:text-gray-400 font-black uppercase tracking-wider text-xs border-l pl-4 border-gray-200 dark:border-gray-700 flex items-center">
                                    🚗 {{ visitData.visitor.vehicle_number }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Multi-stop destinations -->
                    <div
                        v-if="visitData.is_multi && visitData.destinations?.length"
                        class="mb-6 bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/30 rounded-2xl p-4"
                    >
                        <p class="text-[10px] font-black text-orange-600 uppercase tracking-widest mb-2">Delivery stops ({{ visitData.destinations.length }})</p>
                        <ul class="space-y-1">
                            <li
                                v-for="(destination, index) in visitData.destinations"
                                :key="destination"
                                class="text-sm font-bold text-gray-800 dark:text-gray-200"
                            >
                                {{ index + 1 }}. {{ destination }}
                            </li>
                        </ul>
                    </div>

                    <!-- Meta Information Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gray-50/50 dark:bg-gray-800/20 p-4 rounded-xl border border-gray-100 dark:border-gray-800/40">
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">destination</p>
                            <p class="font-black text-gray-800 dark:text-gray-200 mt-1">UNIT {{ visitData.unit_number }}</p>
                        </div>
                        <div class="bg-gray-50/50 dark:bg-gray-800/20 p-4 rounded-xl border border-gray-100 dark:border-gray-800/40">
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">visitation purpose</p>
                            <p class="font-black text-gray-800 dark:text-gray-200 mt-1 truncate">{{ visitData.purpose }}</p>
                        </div>
                        <div class="bg-gray-50/50 dark:bg-gray-800/20 p-4 rounded-xl border border-gray-100 dark:border-gray-800/40">
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Visitor Parking</p>
                            <p v-if="visitData.parking_lot_number" class="font-black text-indigo-650 dark:text-indigo-400 mt-1">🅿️ LOT {{ visitData.parking_lot_number }}</p>
                            <p v-else-if="!props.visit.is_delivery && parking && visitData.status === 'Approved'" class="font-black mt-1" :class="parking.available > 0 ? 'text-green-650 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                                {{ parking.available > 0 ? `Auto Assign (${parking.available} free)` : '🚨 PARKING FULL' }}
                            </p>
                            <p v-else class="font-black text-gray-400 dark:text-gray-600 mt-1">N/A</p>
                        </div>
                    </div>

                    <!-- Face Recognition Section -->
                    <div v-if="visitData.status === 'Approved' || visitData.status === 'Temporarily Out'" class="mb-8">
                        <div class="bg-indigo-50/50 dark:bg-indigo-950/20 rounded-2xl p-6 border border-indigo-100/60 dark:border-indigo-900/30">
                            <h4 class="text-sm font-black text-indigo-900 dark:text-indigo-300 uppercase tracking-widest mb-4 flex items-center">
                                <span class="text-xl mr-2">📸</span> {{ visitData.status === 'Approved' ? 'Identity Verification Required' : 'Re-entry Verification' }}
                            </h4>
                            
                            <div class="bg-white dark:bg-gray-950 rounded-xl p-4 shadow-inner dark:shadow-black/20">
                                <FaceCapture :allow-upload="false" @face-detected="handleFaceDetected" />
                            </div>

                            <!-- Success Banner -->
                            <div v-if="faceVerified" class="mt-4 p-3 bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-400 border border-green-200/30 rounded-xl flex items-center justify-between font-bold text-sm">
                                <span class="flex items-center"><span class="mr-2">✅</span> Identity Verified: Match Confirmed</span>
                                <span class="text-xs uppercase tracking-wider text-green-600 dark:text-green-400 animate-pulse font-black flex items-center">
                                    Checking in... <span class="ml-2 inline-block h-3 w-3 rounded-full bg-green-600 dark:bg-green-400 animate-ping"></span>
                                </span>
                            </div>
                            
                            <!-- Delivery Banner -->
                            <div v-else-if="props.visit.is_delivery" class="mt-4 p-3 bg-orange-50 dark:bg-orange-950/20 text-orange-850 dark:text-orange-400 rounded-xl flex items-center font-bold text-xs border border-orange-100 dark:border-orange-900/30">
                                <span class="mr-2">⚡</span> Express Pass: Face match is optional for delivery riders. You can authorize entry instantly.
                            </div>
                            
                            <!-- Error Banner -->
                            <div v-else-if="verificationError" class="mt-4 p-3 bg-red-100 dark:bg-red-950/30 text-red-700 dark:text-red-400 border border-red-200/30 rounded-xl flex items-center font-bold text-sm">
                                <span class="mr-2">❌</span> {{ verificationError }}
                            </div>
                            
                            <!-- Pending Banner -->
                            <div v-else class="mt-4 p-3 bg-indigo-100/60 dark:bg-indigo-950/40 text-indigo-750 dark:text-indigo-300 border border-indigo-200/20 rounded-xl flex items-center font-bold text-sm animate-pulse">
                                <span class="mr-2">🔍</span> Waiting for face detection...
                            </div>
                        </div>
                    </div>

                    <!-- Auto Checkout Section -->
                    <div v-if="visitData.status === 'Checked In'" class="mb-8">
                        <div class="bg-indigo-50/50 dark:bg-indigo-950/20 rounded-2xl p-6 border border-indigo-100/60 dark:border-indigo-900/30 text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-650 dark:border-indigo-400 border-t-transparent mb-3"></div>
                            <h4 class="text-sm font-black text-indigo-900 dark:text-indigo-300 uppercase tracking-widest mb-1">
                                {{ visitData.checkout_intent === 'temp' ? '⚡ Processing Temporary Leave' : '⚡ Processing Final Check-out' }}
                            </h4>
                            <p class="text-xs text-indigo-700 dark:text-indigo-400 font-medium">
                                Automating transaction based on the visitor's choice...
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <button 
                            v-if="visitData.status === 'Approved'"
                            @click="checkIn"
                            class="col-span-2 bg-green-600 hover:bg-green-700 text-white font-black py-5 rounded-2xl text-lg shadow-lg shadow-green-600/10 dark:shadow-none transition-all duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isLoading || (!faceVerified && !props.visit.is_delivery)"
                        >
                            ✅ AUTHORIZE CHECK-IN
                        </button>
                        <template v-if="visitData.status === 'Checked In'">
                            <button 
                                @click="checkOut(false)"
                                class="col-span-1 bg-red-600 hover:bg-red-700 text-white font-black py-5 rounded-2xl text-sm shadow-lg shadow-red-650/10 dark:shadow-none transition-all duration-300 flex items-center justify-center uppercase tracking-wider"
                                :disabled="isLoading"
                            >
                                🚪 Final Check-Out
                            </button>
                            <button 
                                @click="checkOut(true)"
                                class="col-span-1 bg-orange-650 hover:bg-orange-700 text-white font-black py-5 rounded-2xl text-sm shadow-lg shadow-orange-650/10 dark:shadow-none transition-all duration-300 flex items-center justify-center uppercase tracking-wider"
                                :disabled="isLoading"
                            >
                                ⏱️ Temporary Leave
                            </button>
                        </template>
                        
                        <Link 
                            :href="route('guard.scan')"
                            class="col-span-2 text-center bg-gray-100 hover:bg-gray-250 dark:bg-gray-800 dark:hover:bg-gray-700/80 text-gray-800 dark:text-gray-200 font-black py-4 rounded-2xl transition-all duration-300 border border-gray-200 dark:border-gray-750 flex items-center justify-center gap-2"
                        >
                            🔄 SCAN NEW CODE
                        </Link>
                    </div>

                    <!-- Warnings -->
                    <div v-if="visitData.status === 'Pending'" class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-950/20 rounded-xl border border-yellow-200 dark:border-yellow-900/30 flex items-start">
                         <span class="mr-3 text-lg">⚠️</span>
                         <div class="text-xs text-yellow-850 dark:text-yellow-400 font-medium">
                            <strong class="uppercase block text-[10px] mb-1 font-black">Access Suspended</strong>
                            This visit is waiting for resident approval. The visitor cannot be checked in until approved.
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ Face Match → Check-In Success Modal -->
        <Modal :show="showSuccessModal" max-width="md" :closeable="false">
            <div class="p-8 text-center dark:bg-gray-900">
                <div class="mx-auto flex items-center justify-center h-28 w-28 rounded-full bg-green-50 dark:bg-green-950/40 border-2 border-green-200 dark:border-green-800/50 mb-6 shadow-lg animate-bounce">
                    <span class="text-6xl">✅</span>
                </div>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2 tracking-tight">
                    Face Match Successful!
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
                    Identity verified for
                    <span class="font-extrabold text-green-600 dark:text-green-400">{{ visitData.visitor.name }}</span>.
                    <br>Entry is being authorized automatically.
                </p>
                <div class="bg-green-50 dark:bg-green-950/30 border border-green-100 dark:border-green-900/40 p-5 rounded-2xl">
                    <div class="flex items-center justify-center gap-3">
                        <div class="animate-spin rounded-full h-5 w-5 border-2 border-green-600 dark:border-green-400 border-t-transparent"></div>
                        <span class="text-sm font-black text-green-700 dark:text-green-400 uppercase tracking-widest">Checking In...</span>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- 🚪 Checkout / Temporary Leave Modal -->
        <Modal :show="showCheckoutModal" max-width="md" :closeable="false">
            <div class="p-8 text-center dark:bg-gray-900">
                <div
                    class="mx-auto flex items-center justify-center h-28 w-28 rounded-full mb-6 shadow-lg animate-bounce border-2"
                    :class="checkoutIsTemporary
                        ? 'bg-orange-50 dark:bg-orange-950/40 border-orange-200 dark:border-orange-800/50'
                        : 'bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-800/50'"
                >
                    <span class="text-6xl">{{ checkoutIsTemporary ? '⏱️' : '🚪' }}</span>
                </div>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2 tracking-tight">
                    {{ checkoutIsTemporary ? 'Temporary Leave' : 'Final Check-Out' }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
                    <span class="font-extrabold" :class="checkoutIsTemporary ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400'">
                        {{ visitData.visitor.name }}
                    </span>
                    {{ checkoutIsTemporary ? ' is stepping out temporarily.' : ' is checking out.' }}
                    <br>Processing automatically...
                </p>
                <div
                    class="p-5 rounded-2xl border"
                    :class="checkoutIsTemporary
                        ? 'bg-orange-50 dark:bg-orange-950/30 border-orange-100 dark:border-orange-900/40'
                        : 'bg-red-50 dark:bg-red-950/30 border-red-100 dark:border-red-900/40'"
                >
                    <div class="flex items-center justify-center gap-3">
                        <div
                            class="animate-spin rounded-full h-5 w-5 border-2 border-t-transparent"
                            :class="checkoutIsTemporary ? 'border-orange-500 dark:border-orange-400' : 'border-red-600 dark:border-red-400'"
                        ></div>
                        <span
                            class="text-sm font-black uppercase tracking-widest"
                            :class="checkoutIsTemporary ? 'text-orange-700 dark:text-orange-400' : 'text-red-700 dark:text-red-400'"
                        >
                            {{ checkoutIsTemporary ? 'Processing Temporary Leave...' : 'Processing Check-Out...' }}
                        </span>
                    </div>
                </div>
            </div>
        </Modal>
    </GuardAuthenticatedLayout>
</template>
