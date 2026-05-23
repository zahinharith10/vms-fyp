<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DeliveryAuthenticatedLayout from '@/Layouts/DeliveryAuthenticatedLayout.vue';

const props = defineProps({
    delivery: Object,
    logs: Array,
    activeLog: Object,
    qrCodeSvg: String,
    houseUnits: Object, // { block: { floor: [units] } }
});

const block = ref('');
const floor = ref('');
const unit = ref('');

const tripForm = useForm({
    unit_number: '',
});

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
    unit.value = '';
};

const onFloorChange = () => {
    unit.value = '';
};

const submitTrip = () => {
    tripForm.unit_number = `${block.value} - ${floor.value} - ${unit.value}`;
    tripForm.post(route('delivery.trips.store'), {
        preserveScroll: true,
        onSuccess: () => {
            block.value = '';
            floor.value = '';
            unit.value = '';
        }
    });
};
</script>

<template>
    <Head title="Delivery Dashboard" />

    <DeliveryAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Delivery Dashboard</h2>
        </template>

        <!-- Flash Messages -->
        <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p class="font-bold">Success</p>
            <p>{{ $page.props.flash.success }}</p>
        </div>

        <div v-if="$page.props.flash.error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p class="font-bold">Error</p>
            <p>{{ $page.props.flash.error }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Request Entry Pass Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Request Entry Pass</h3>
                    <p class="text-sm text-gray-500 mb-4">Enter your destination unit number below.</p>
                    
                    <form @submit.prevent="submitTrip">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Destination Unit</label>
                            
                            <div class="space-y-3">
                                <!-- Block selection -->
                                <div>
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

                                <!-- Floor selection -->
                                <div>
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

                                <!-- Unit selection -->
                                <div>
                                    <label class="block text-gray-500 text-xs font-bold mb-1 uppercase tracking-wider">Unit No.</label>
                                    <select
                                        v-model="unit"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
                                        :class="!floor ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700'"
                                        :disabled="!floor"
                                        required
                                    >
                                        <option value="" disabled>{{ floor ? 'Select Unit' : 'Select Floor first' }}</option>
                                        <option v-for="u in unitOptions" :key="u" :value="u">Unit {{ u }}</option>
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" v-model="tripForm.unit_number">
                            <div v-if="tripForm.errors.unit_number" class="text-red-500 text-xs mt-1">{{ tripForm.errors.unit_number }}</div>

                            <!-- Preview selected unit -->
                            <div v-if="block && floor && unit" class="mt-4 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-black text-indigo-750 tracking-widest text-center">
                                📍 Destination: {{ block }} - {{ floor }} - {{ unit }}
                            </div>
                        </div>
                        
                        <button 
                            type="submit" 
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" 
                            :disabled="tripForm.processing"
                        >
                            {{ tripForm.processing ? 'Requesting...' : 'Submit Request' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Active Pass & Recent Logs -->
            <div class="space-y-6">
                <!-- Active QR Pass -->
                <div v-if="activeLog" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex flex-col items-center">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 w-full mb-4">Active Entry Pass</h3>
                        
                        <span 
                            class="px-3 py-1 rounded-full text-xs font-bold mb-4"
                            :class="{
                                'bg-yellow-100 text-yellow-800': activeLog.status === 'Pending',
                                'bg-green-100 text-green-800': activeLog.status === 'Approved',
                                'bg-blue-100 text-blue-800': activeLog.status === 'Checked In'
                            }"
                        >
                            {{ activeLog.status }}
                        </span>
                        
                        <!-- QR Code -->
                        <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm mb-4">
                            <div v-html="qrCodeSvg" class="h-48 w-48 flex justify-center items-center"></div>
                        </div>
                        
                        <div class="text-center text-sm space-y-1">
                            <p class="font-bold text-gray-700">Vehicle: {{ delivery.vehicle_number }}</p>
                            <p class="text-gray-500">Destination: Unit {{ activeLog.destination }}</p>
                            
                            <p class="text-xs text-gray-400 mt-3 max-w-sm">
                                {{ activeLog.status === 'Approved' ? 'Show this QR code to the guard at the gate to enter instantly.' : (activeLog.status === 'Checked In' ? 'Please present this same QR code to check out when leaving.' : 'Please wait for the resident of unit ' + activeLog.destination + ' to approve entry.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recent Entry Logs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex flex-col h-full">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Recent Entry Logs</h3>
                        
                        <div v-if="logs && logs.length > 0" class="space-y-3">
                            <div v-for="log in logs" :key="log.id" class="p-3 rounded border border-gray-200 text-sm bg-gray-50">
                                <div class="flex justify-between">
                                    <span class="font-bold">Unit {{ log.destination }}</span>
                                    <span :class="{
                                        'text-yellow-600': log.status === 'Pending',
                                        'text-blue-600': log.status === 'Approved',
                                        'text-green-600': log.status === 'Checked In',
                                        'text-gray-650': log.status === 'Checked Out'
                                    }" class="font-bold">{{ log.status }}</span>
                                </div>
                                <div class="text-gray-500 text-xs mt-1">
                                    Entry: {{ log.entry_time || 'N/A' }} 
                                    <span v-if="log.exit_time"> | Exit: {{ log.exit_time }}</span>
                                    <span v-else-if="log.status === 'Checked In'"> | Checked In</span>
                                </div>
                                <div class="text-gray-400 text-[10px] mt-1">Date: {{ new Date(log.created_at).toLocaleDateString('en-GB') }}</div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-sm italic py-8 text-center flex-1">
                            No recent logs found.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-center text-sm text-gray-500">
                Please present your vehicle registration and face at the guard post for verification.
            </div>
        </div>
    </DeliveryAuthenticatedLayout>
</template>
