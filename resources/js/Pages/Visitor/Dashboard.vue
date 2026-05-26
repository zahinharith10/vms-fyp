<script setup>
import VisitorAuthenticatedLayout from '@/Layouts/VisitorAuthenticatedLayout.vue';
import ParkingMap from '@/Components/ParkingMap.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { formatMalaysiaDate } from '@/utils/datetime';

const props = defineProps({
    visitor: Object,
    houseUnits: Object, // { block: { floor: [units] } }
});

const form = useForm({
    unit_number: '',
    purpose: 'Visit Friend/Family',
});

const block = ref('');
const floor = ref('');
const house_number = ref('');
const customPurpose = ref('');

// Cascading options derived from houseUnits map
const blockOptions = computed(() => Object.keys(props.houseUnits || {}).sort((a, b) => Number(a) - Number(b)));

const floorOptions = computed(() => {
    if (!block.value || !props.houseUnits?.[block.value]) return [];
    return Object.keys(props.houseUnits[block.value]).sort((a, b) => Number(a) - Number(b));
});

const unitOptions = computed(() => {
    if (!block.value || !floor.value || !props.houseUnits?.[block.value]?.[floor.value]) return [];
    return [...props.houseUnits[block.value][floor.value]].sort((a, b) => Number(a) - Number(b));
});

const onBlockChange = () => {
    floor.value = '';
    house_number.value = '';
};

const onFloorChange = () => {
    house_number.value = '';
};

const submitVisit = () => {
    form.unit_number = `${block.value}-${floor.value}-${house_number.value}`;
    
    const finalPurpose = form.purpose === 'Other' ? customPurpose.value : form.purpose;
    
    form.transform((data) => ({
        ...data,
        purpose: finalPurpose,
    })).post(route('visitor.visits.store'), {
        onSuccess: () => {
            form.reset();
            block.value = '';
            floor.value = '';
            house_number.value = '';
            customPurpose.value = '';
        },
    });
};

const showMapVisitId = ref(null);
const toggleMap = (visitId) => {
    showMapVisitId.value = showMapVisitId.value === visitId ? null : visitId;
};

// Real-time: Listen to each active visit's channel for status updates
const echoChannels = [];
onMounted(() => {
    if (!window.Echo || !props.visitor?.visits) return;
    props.visitor.visits.forEach((visit) => {
        if (['Pending', 'Approved', 'Checked In'].includes(visit.status)) {
            const ch = window.Echo.channel(`visit.${visit.id}`)
                .listen('.visit.status.updated', () => {
                    router.reload({ only: ['visitor'], preserveState: true, preserveScroll: true });
                });
            echoChannels.push(`visit.${visit.id}`);
        }
    });
});

onUnmounted(() => {
    echoChannels.forEach((ch) => window.Echo.leaveChannel(ch));
});
</script>

<template>
    <Head title="Visitor Dashboard" />

    <VisitorAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Visitor Dashboard</h2>
        </template>



        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- New Visit Form -->
            <div class="bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl transition-all duration-300">
                <div class="p-8">
                    <h3 class="text-lg font-black mb-6 text-gray-800 dark:text-white">Request New Visit</h3>
                    <form @submit.prevent="submitVisit">
                        <div class="mb-4">
                            <label class="block text-gray-800 dark:text-gray-250 text-sm font-black uppercase tracking-wider mb-2">Destination Unit</label>

                            <!-- Block dropdown -->
                            <div class="mb-3">
                                <label class="block text-gray-400 dark:text-gray-500 text-[10px] font-black mb-1.5 uppercase tracking-widest">Block</label>
                                <select
                                    v-model="block"
                                    @change="onBlockChange"
                                    class="shadow border border-gray-200 dark:border-gray-800 rounded-xl w-full py-3 px-4 text-gray-800 dark:text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-950 transition-all font-bold"
                                    required
                                >
                                    <option value="" disabled>Select Block</option>
                                    <option v-for="b in blockOptions" :key="b" :value="b">Block {{ b }}</option>
                                </select>
                            </div>

                            <!-- Floor dropdown -->
                            <div class="mb-3">
                                <label class="block text-gray-400 dark:text-gray-500 text-[10px] font-black mb-1.5 uppercase tracking-widest">Floor</label>
                                <select
                                    v-model="floor"
                                    @change="onFloorChange"
                                    class="shadow border border-gray-200 dark:border-gray-800 rounded-xl w-full py-3 px-4 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-950 transition-all font-bold"
                                    :class="!block ? 'text-gray-400 dark:text-gray-600 cursor-not-allowed' : 'text-gray-800 dark:text-gray-200'"
                                    :disabled="!block"
                                    required
                                >
                                    <option value="" disabled>{{ block ? 'Select Floor' : 'Select Block first' }}</option>
                                    <option v-for="f in floorOptions" :key="f" :value="f">Floor {{ f }}</option>
                                </select>
                            </div>

                            <!-- Unit No. dropdown -->
                            <div class="mb-4">
                                <label class="block text-gray-400 dark:text-gray-500 text-[10px] font-black mb-1.5 uppercase tracking-widest">Unit No.</label>
                                <select
                                    v-model="house_number"
                                    class="shadow border border-gray-200 dark:border-gray-800 rounded-xl w-full py-3 px-4 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-950 transition-all font-bold"
                                    :class="!floor ? 'text-gray-400 dark:text-gray-600 cursor-not-allowed' : 'text-gray-800 dark:text-gray-200'"
                                    :disabled="!floor"
                                    required
                                >
                                    <option value="" disabled>{{ floor ? 'Select Unit' : 'Select Floor first' }}</option>
                                    <option v-for="u in unitOptions" :key="u" :value="u">Unit {{ u }}</option>
                                </select>
                            </div>

                            <input type="hidden" v-model="form.unit_number">
                            <div v-if="form.errors.unit_number" class="text-red-500 text-xs mt-1">{{ form.errors.unit_number }}</div>

                            <!-- Preview selected unit -->
                            <div v-if="block && floor && house_number" class="mt-2 px-3 py-2 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/30 rounded-xl text-xs font-black text-indigo-700 dark:text-indigo-400 tracking-widest">
                                📍 Unit: {{ block }}-{{ floor }}-{{ house_number }}
                            </div>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-850 dark:text-gray-250 text-sm font-black uppercase tracking-wider mb-2">Purpose</label>
                            <select v-model="form.purpose" class="shadow border border-gray-200 dark:border-gray-800 rounded-xl w-full py-3 px-4 text-gray-800 dark:text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-950 transition-all font-bold mb-3">
                                <option>Visit Friend/Family</option>
                                <option>Other</option>
                            </select>

                            <!-- Custom Purpose Text Box -->
                            <div v-if="form.purpose === 'Other'" class="mt-2">
                                <label class="block text-gray-400 dark:text-gray-500 text-[10px] font-black uppercase tracking-widest mb-1">Please specify purpose</label>
                                <input 
                                    v-model="customPurpose" 
                                    type="text" 
                                    class="shadow border border-indigo-200 dark:border-indigo-900/30 rounded-xl w-full py-3 px-4 text-gray-800 dark:text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-950 transition-all font-bold" 
                                    placeholder="Enter your purpose here..." 
                                    required
                                />
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-650 dark:hover:bg-indigo-700 text-white font-black py-4 rounded-xl focus:outline-none transition-all shadow-md dark:shadow-none shadow-indigo-100 uppercase tracking-widest text-xs" :disabled="form.processing">
                            Submit Request
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Visits List -->
            <div class="bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl transition-all duration-300">
                <div class="p-8 flex flex-col h-full">
                    <h3 class="text-lg font-black mb-6 text-gray-800 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-4">Recent Visits</h3>
                    <div v-if="visitor?.visits && visitor.visits.length > 0" class="space-y-3">
                        <div v-for="visit in visitor.visits" :key="visit.id" class="p-4 rounded-2xl border border-gray-150 dark:border-gray-800/60 text-sm  dark:bg-gray-850/40 transition-all">
                            <div class="flex justify-between">
                                <span class="font-black text-gray-900 dark:text-white">UNIT {{ visit.unit_number }}</span>
                                <span :class="{
                                    'text-yellow-600 dark:text-yellow-400': visit.status === 'Pending',
                                    'text-blue-600 dark:text-blue-400': visit.status === 'Approved',
                                    'text-green-600 dark:text-green-400': visit.status === 'Checked In',
                                    'text-gray-550 dark:text-gray-400': visit.status === 'Checked Out',
                                    'text-red-650 dark:text-red-400': visit.status === 'Rejected'
                                }" class="font-black text-xs uppercase tracking-wider">{{ visit.status }}</span>
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs mt-1 font-medium">{{ visit.purpose }} • {{ formatMalaysiaDate(visit.created_at) }}</div>
                            <div v-if="visit.parking_lot_number" class="mt-2">
                                <div class="text-indigo-650 dark:text-indigo-400 font-bold text-xs flex items-center gap-1 mb-1.5">
                                    <span>🅿️</span> Assigned: <span class="bg-indigo-50 dark:bg-indigo-950/30 px-2 py-0.5 rounded border border-indigo-100 dark:border-indigo-900/30 uppercase tracking-widest text-[10px] font-black text-indigo-700 dark:text-indigo-400">Lot {{ visit.parking_lot_number }}</span>
                                </div>
                                <button
                                    v-if="visit.status === 'Checked In'"
                                    @click="toggleMap(visit.id)"
                                    class="mt-1 text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1 transition-colors"
                                >
                                    <span>🗺️</span>
                                    {{ showMapVisitId === visit.id ? 'Hide Map' : 'Show Parking Map' }}
                                </button>
                                <!-- Inline parking map -->
                                <div v-if="showMapVisitId === visit.id" class="mt-3 bg-gray-900 dark:bg-gray-950 rounded-xl p-3">
                                    <ParkingMap :assigned-lot="visit.parking_lot_number" />
                                </div>
                            </div>
                            
                            <div v-if="['Approved', 'Checked In', 'Temporarily Out'].includes(visit.status)" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <Link :href="route('visitor.visits.qr', visit.id)" class="inline-block text-indigo-600 dark:text-indigo-400 font-black text-xs uppercase tracking-widest">
                                    View QR Code
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-gray-500 dark:text-gray-400 text-sm italic py-8 text-center flex-1">
                        No recent visits found.
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-center">
                        <Link :href="route('visitor.visits.history')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-black text-sm uppercase tracking-widest flex items-center justify-center">
                            View Full History <span class="ml-1">→</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-white dark:bg-gray-900 border border-transparent dark:border-gray-800/80 overflow-hidden shadow-sm dark:shadow-indigo-950/5 sm:rounded-3xl transition-all duration-300">
            <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400 font-medium">
                Please present your face at the guard post for verification.
            </div>
        </div>
    </VisitorAuthenticatedLayout>
</template>
