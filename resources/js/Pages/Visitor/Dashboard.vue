<script setup>
import VisitorAuthenticatedLayout from '@/Layouts/VisitorAuthenticatedLayout.vue';
import ParkingMap from '@/Components/ParkingMap.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visitor Dashboard</h2>
        </template>

        <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 mx-6 mt-4" role="alert">
            <p class="font-bold">Success</p>
            <p>{{ $page.props.flash.success }}</p>
        </div>

        <div v-if="$page.props.flash.error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 mx-6 mt-4" role="alert">
            <p class="font-bold">Error</p>
            <p>{{ $page.props.flash.error }}</p>
        </div>

        <div v-if="!visitor" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 mx-6 mt-4" role="alert">
            <p class="font-bold">Error</p>
            <p>Visitor data could not be loaded.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- New Visit Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Request New Visit</h3>
                    <form @submit.prevent="submitVisit">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Destination Unit</label>

                            <!-- Block dropdown -->
                            <div class="mb-2">
                                <label class="block text-gray-500 text-xs font-bold mb-1 uppercase tracking-wider">Block</label>
                                <select
                                    v-model="block"
                                    @change="onBlockChange"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
                                    required
                                >
                                    <option value="" disabled>Select Block</option>
                                    <option v-for="b in blockOptions" :key="b" :value="b">Block {{ b }}</option>
                                </select>
                            </div>

                            <!-- Floor dropdown -->
                            <div class="mb-2">
                                <label class="block text-gray-500 text-xs font-bold mb-1 uppercase tracking-wider">Floor</label>
                                <select
                                    v-model="floor"
                                    @change="onFloorChange"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
                                    :class="!block ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700'"
                                    :disabled="!block"
                                    required
                                >
                                    <option value="" disabled>{{ block ? 'Select Floor' : 'Select Block first' }}</option>
                                    <option v-for="f in floorOptions" :key="f" :value="f">Floor {{ f }}</option>
                                </select>
                            </div>

                            <!-- Unit No. dropdown -->
                            <div class="mb-2">
                                <label class="block text-gray-500 text-xs font-bold mb-1 uppercase tracking-wider">Unit No.</label>
                                <select
                                    v-model="house_number"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
                                    :class="!floor ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700'"
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
                            <div v-if="block && floor && house_number" class="mt-2 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-black text-indigo-700 tracking-widest">
                                📍 Unit: {{ block }}-{{ floor }}-{{ house_number }}
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Purpose</label>
                            <select v-model="form.purpose" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline mb-3">
                                <option>Visit Friend/Family</option>
                                <option>Maintenance</option>
                                <option>Other</option>
                            </select>

                            <!-- Custom Purpose Text Box -->
                            <div v-if="form.purpose === 'Other'" class="mt-2">
                                <label class="block text-gray-600 text-[10px] font-black uppercase tracking-widest mb-1">Please specify purpose</label>
                                <input 
                                    v-model="customPurpose" 
                                    type="text" 
                                    class="shadow appearance-none border border-indigo-200 rounded-xl w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-bold" 
                                    placeholder="Enter your purpose here..." 
                                    required
                                />
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" :disabled="form.processing">
                            Submit Request
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Visits List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 flex flex-col h-full">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Recent Visits</h3>
                    <div v-if="visitor?.visits && visitor.visits.length > 0" class="space-y-3">
                        <div v-for="visit in visitor.visits" :key="visit.id" class="p-3 rounded border border-gray-200 text-sm bg-gray-50">
                            <div class="flex justify-between">
                                <span class="font-bold">{{ visit.unit_number }}</span>
                                <span :class="{
                                    'text-yellow-600': visit.status === 'Pending',
                                    'text-blue-600': visit.status === 'Approved',
                                    'text-green-600': visit.status === 'Checked In',
                                    'text-gray-600': visit.status === 'Checked Out',
                                    'text-red-600': visit.status === 'Rejected'
                                }" class="font-bold">{{ visit.status }}</span>
                            </div>
                            <div class="text-gray-500 text-xs mt-1">{{ visit.purpose }} - {{ new Date(visit.created_at).toLocaleDateString('en-GB') }}</div>
                            <div v-if="visit.parking_lot_number" class="mt-2">
                                <div class="text-indigo-650 font-bold text-xs flex items-center gap-1 mb-1.5">
                                    <span>🅿️</span> Assigned: <span class="bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 uppercase tracking-widest text-[10px] font-black text-indigo-700">Lot {{ visit.parking_lot_number }}</span>
                                </div>
                                <button
                                    v-if="visit.status === 'Checked In'"
                                    @click="toggleMap(visit.id)"
                                    class="mt-1 text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-colors"
                                >
                                    <span>🗺️</span>
                                    {{ showMapVisitId === visit.id ? 'Hide Map' : 'Show Parking Map' }}
                                </button>
                                <!-- Inline parking map -->
                                <div v-if="showMapVisitId === visit.id" class="mt-3 bg-gray-900 rounded-xl p-3">
                                    <ParkingMap :assigned-lot="visit.parking_lot_number" />
                                </div>
                            </div>
                            
                            <div v-if="['Approved', 'Checked In', 'Temporarily Out'].includes(visit.status)" class="mt-2 pt-2 border-t border-gray-100">
                                <Link :href="route('visitor.visits.qr', visit.id)" class="inline-block text-indigo-600 font-bold hover:underline">
                                    View QR Code
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-gray-500 text-sm italic py-8 text-center flex-1">
                        No recent visits found.
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                        <Link :href="route('visitor.visits.history')" class="text-indigo-600 hover:text-indigo-800 font-bold text-sm uppercase tracking-wider flex items-center justify-center">
                            View Full History <span class="ml-1">→</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-center text-sm text-gray-500">
                Please present your face at the guard post for verification.
            </div>
        </div>
    </VisitorAuthenticatedLayout>
</template>
