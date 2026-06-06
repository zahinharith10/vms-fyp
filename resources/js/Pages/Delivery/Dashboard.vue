<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import DeliveryAuthenticatedLayout from '@/Layouts/DeliveryAuthenticatedLayout.vue';
import { formatMalaysiaDate } from '@/utils/datetime';

const props = defineProps({
    delivery: Object,
    logs: Array,
    activeLog: Object,
    activeRun: Object,
    qrCodeSvg: String,
    houseUnits: Object,
});

const deliveryMode = ref('single'); // single | multi
const block = ref('');
const floor = ref('');
const unit = ref('');
const stopList = ref([]);
const tempHostName = ref('');



const tripForm = useForm({
    delivery_type: 'single',
    unit_number: '',
    unit_numbers: [],
    host_name: '',
    host_names: [],
});

const blockOptions = computed(() => Object.keys(props.houseUnits || {}).sort((a, b) => Number(a) - Number(b)));

const floorOptions = computed(() => {
    if (!block.value || !props.houseUnits?.[block.value]) return [];
    return Object.keys(props.houseUnits[block.value]).sort((a, b) => Number(a) - Number(b));
});

const unitOptions = computed(() => {
    if (!block.value || !floor.value || !props.houseUnits?.[block.value]?.[floor.value]) return [];
    return [...props.houseUnits[block.value][floor.value]].sort((a, b) => Number(a) - Number(b));
});

const currentUnitLabel = computed(() => {
    if (!block.value || !floor.value || !unit.value) return '';
    return `${block.value} - ${floor.value} - ${unit.value}`;
});

const canAddStop = computed(() => Boolean(currentUnitLabel.value) && Boolean(tempHostName.value.trim()));

const onBlockChange = () => {
    floor.value = '';
    unit.value = '';
};

const onFloorChange = () => {
    unit.value = '';
};

const resetSelectors = () => {
    block.value = '';
    floor.value = '';
    unit.value = '';
};

const handleModeChange = (mode) => {
    deliveryMode.value = mode;
    resetSelectors();
    stopList.value = [];
    tempHostName.value = '';
    tripForm.host_name = '';
    tripForm.host_names = [];
};

const addStop = () => {
    if (!canAddStop.value) return;

    const label = currentUnitLabel.value;

    if (stopList.value.some(stop => stop.unit_number === label)) {
        alert('This unit is already added.');
        return;
    }

    stopList.value.push({
        unit_number: label,
        host_name: tempHostName.value.trim(),
    });
    
    resetSelectors();
    tempHostName.value = '';
};

const removeStop = (index) => {
    stopList.value.splice(index, 1);
};

const submitTrip = () => {
    tripForm.delivery_type = deliveryMode.value;

    if (deliveryMode.value === 'single') {
        tripForm.unit_number = currentUnitLabel.value;
        tripForm.unit_numbers = [];
    } else {
        tripForm.unit_number = '';
        tripForm.unit_numbers = stopList.value.map(stop => stop.unit_number);
        tripForm.host_names = stopList.value.map(stop => stop.host_name);
        tripForm.host_name = 'Multi-stop'; // dummy value to pass validation
    }

    tripForm.post(route('delivery.trips.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetSelectors();
            stopList.value = [];
            tempHostName.value = '';
            tripForm.host_name = '';
            tripForm.host_names = [];
        },
    });
};

const cancelTrip = () => {
    if (confirm('Are you sure you want to cancel this delivery trip?')) {
        if (props.activeRun) {
            // Multi or single-run cancel
            useForm({}).delete(route('delivery.trips.cancel', { run: props.activeRun.id }), {
                preserveScroll: true,
                onSuccess: () => { window.location.reload(); },
            });
        } else if (props.activeLog) {
            // Standalone single log cancel (no run wrapper)
            useForm({}).delete(route('delivery.trips.cancel', { run: props.activeLog.delivery_run_id ?? props.activeLog.id }), {
                preserveScroll: true,
                onSuccess: () => { window.location.reload(); },
            });
        }
    }
};

const runStatusLabel = (run) => {
    if (!run) return '';
    if (run.type === 'multi') {
        return `Multi-stop (${run.logs?.length || 0} units)`;
    }
    return 'Single delivery';
};

const allStopsApproved = computed(() => {
    if (props.activeRun?.type === 'multi' && props.activeRun.logs?.length) {
        return props.activeRun.logs.every(log => log.status === 'Approved');
    }
    if (props.activeLog) {
        return props.activeLog.status === 'Approved';
    }
    return false;
});

// True when a trip is in-progress — blocks new submissions
const hasActiveTrip = computed(() => !!(props.activeRun || props.activeLog));

// Flash messages from server
const page = usePage();
const flashError = computed(() => page.props.flash?.error ?? null);
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const CANCELLED_STATUSES = ['Cancelled', 'Rejected'];

const groupedLogs = computed(() => {
    const groups = [];
    const processedRunIds = new Set();

    for (const log of props.logs || []) {
        if (log.run && log.run.type === 'multi' && !processedRunIds.has(log.run.id)) {
            processedRunIds.add(log.run.id);
            const runLogs = props.logs.filter(l => l.run?.id === log.run.id);
            groups.push({
                type: 'multi',
                run: log.run,
                logs: runLogs,
                created_at: log.run.created_at,
            });
        } else if (!log.run || log.run.type === 'single') {
            groups.push({
                type: 'single',
                log: log,
                created_at: log.created_at,
            });
        }
    }

    return groups;
});

// Active tab: only show entries that are NOT yet finished and NOT cancelled/rejected
const activeGroupedLogs = computed(() => {
    return groupedLogs.value.filter(group => {
        if (group.type === 'multi') {
            return !CANCELLED_STATUSES.includes(group.run.status)
                && !group.logs.every(l => l.exit_time !== null);
        }
        return !CANCELLED_STATUSES.includes(group.log.status)
            && group.log.exit_time === null
            && group.log.status !== 'Checked Out';
    });
});


</script>

<template>
    <Head title="Delivery Dashboard" />

    <DeliveryAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Delivery Dashboard</h2>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-white border-b pb-2">Request Entry Pass</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Choose single or multi-stop delivery, then add your destination unit(s).</p>

                    <!-- Active trip warning banner -->
                    <div v-if="hasActiveTrip" class="mb-5 flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-xl">
                        <span class="text-xl flex-shrink-0">⚠️</span>
                        <div>
                            <p class="text-sm font-bold text-amber-800 dark:text-amber-300">You already have an active delivery trip.</p>
                            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">Please complete or cancel your current trip before starting a new one.</p>
                        </div>
                    </div>

                    <!-- Server flash error -->
                    <div v-if="flashError" class="mb-5 flex items-start gap-3 p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/50 rounded-xl">
                        <span class="text-xl flex-shrink-0">🚫</span>
                        <p class="text-sm font-bold text-red-700 dark:text-red-400">{{ flashError }}</p>
                    </div>

                    <!-- Server flash success -->
                    <div v-if="flashSuccess" class="mb-5 flex items-start gap-3 p-4 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800/50 rounded-xl">
                        <span class="text-xl flex-shrink-0">✅</span>
                        <p class="text-sm font-bold text-green-700 dark:text-green-400">{{ flashSuccess }}</p>
                    </div>

                    <form @submit.prevent="submitTrip" class="space-y-5">
                        <div>
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest text-center mb-3">
                                What type of delivery is this?
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    @click="handleModeChange('single')"
                                    class="flex flex-col items-center rounded-2xl border-2 p-4 transition-all"
                                    :class="deliveryMode === 'single'
                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950 dark:border-indigo-600 shadow-md ring-2 ring-indigo-200 dark:ring-indigo-800'
                                        : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:border-indigo-300 dark:hover:border-indigo-600'"
                                >
                                    <span class="text-2xl mb-1">📦</span>
                                    <span class="text-sm font-black uppercase" :class="deliveryMode === 'single' ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400'">Single</span>
                                    <span class="text-[10px] text-center mt-1" :class="deliveryMode === 'single' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500'">One unit only</span>
                                </button>
                                <button
                                    type="button"
                                    @click="handleModeChange('multi')"
                                    class="flex flex-col items-center rounded-2xl border-2 p-4 transition-all"
                                    :class="deliveryMode === 'multi'
                                        ? 'border-orange-500 bg-orange-50 dark:bg-orange-950 dark:border-orange-600 shadow-md ring-2 ring-orange-200 dark:ring-orange-800'
                                        : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:border-orange-300 dark:hover:border-orange-600'"
                                >
                                    <span class="text-2xl mb-1">🛒</span>
                                    <span class="text-sm font-black uppercase" :class="deliveryMode === 'multi' ? 'text-orange-700 dark:text-orange-300' : 'text-gray-600 dark:text-gray-400'">Many</span>
                                    <span class="text-[10px] text-center mt-1" :class="deliveryMode === 'multi' ? 'text-orange-600 dark:text-orange-400' : 'text-gray-400 dark:text-gray-500'">Several units</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-gray-700 dark:text-white text-sm font-bold">
                                {{ deliveryMode === 'single' ? 'Destination unit' : 'Add destination units' }}
                            </label>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-gray-500 text-xs font-bold mb-1 uppercase tracking-wider">Block</label>
                                    <select
                                        v-model="block"
                                        @change="onBlockChange"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <option value="" disabled>Select Block</option>
                                        <option v-for="b in blockOptions" :key="b" :value="b">Block {{ b }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-500 dark:text-gray-400 text-xs font-bold mb-1 uppercase tracking-wider">Floor</label>
                                    <select
                                        v-model="floor"
                                        @change="onFloorChange"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                        :class="!block ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 dark:text-gray-200'"
                                        :disabled="!block"
                                    >
                                        <option value="" disabled>{{ block ? 'Select Floor' : 'Select Block first' }}</option>
                                        <option v-for="f in floorOptions" :key="f" :value="f">Floor {{ f }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-500 dark:text-gray-400 text-xs font-bold mb-1 uppercase tracking-wider">Unit No.</label>
                                    <select
                                        v-model="unit"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                        :class="!floor ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 dark:text-gray-200'"
                                        :disabled="!floor"
                                    >
                                        <option value="" disabled>{{ floor ? 'Select Unit' : 'Select Floor first' }}</option>
                                        <option v-for="u in unitOptions" :key="u" :value="u">Unit {{ u }}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="currentUnitLabel" class="px-3 py-2 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 rounded-lg text-xs font-black text-indigo-700 dark:text-indigo-300 tracking-widest text-center">
                                📍 Selected: {{ currentUnitLabel }}
                            </div>

                            <div v-if="deliveryMode === 'multi' && currentUnitLabel" class="mt-3">
                                <label class="block text-gray-700 dark:text-white text-sm font-bold mb-1">Person to Visit for unit {{ currentUnitLabel }} <span class="text-red-500">*</span></label>
                                <input
                                    v-model="tempHostName"
                                    type="text"
                                    placeholder="Enter resident's full name for this unit"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>

                            <button
                                v-if="deliveryMode === 'multi'"
                                type="button"
                                @click="addStop"
                                :disabled="!canAddStop"
                                class="w-full py-2 px-4 rounded-lg font-bold text-sm border-2 border-dashed transition"
                                :class="canAddStop
                                    ? 'border-orange-400 dark:border-orange-600 text-orange-700 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-950/20'
                                    : 'border-gray-200 dark:border-gray-800 text-gray-400 dark:text-gray-600 cursor-not-allowed'"
                            >
                                + Add unit to list
                            </button>

                            <div v-if="deliveryMode === 'multi'" class="space-y-2">
                                <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                    Stops added ({{ stopList.length }}) — minimum 2 required
                                </p>
                                <ul v-if="stopList.length" class="space-y-2">
                                    <li
                                        v-for="(stop, index) in stopList"
                                        :key="stop.unit_number"
                                        class="flex items-center justify-between bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/40 rounded-lg px-3 py-2 text-sm font-bold text-gray-800 dark:text-gray-200"
                                    >
                                        <div class="flex flex-col">
                                            <span class="text-xs text-orange-600 dark:text-orange-400">Unit: {{ stop.unit_number }}</span>
                                            <span class="text-sm">👤 Resident: {{ stop.host_name }}</span>
                                        </div>
                                        <button type="button" class="text-red-500 text-xs font-black uppercase" @click="removeStop(index)">Remove</button>
                                    </li>
                                </ul>
                                <p v-else class="text-xs text-gray-400 italic text-center py-2">No units added yet.</p>
                            </div>

                            <div v-if="tripForm.errors.unit_number" class="text-red-500 text-xs">{{ tripForm.errors.unit_number }}</div>
                            <div v-if="tripForm.errors.unit_numbers" class="text-red-500 text-xs">{{ tripForm.errors.unit_numbers }}</div>
                            <div v-if="tripForm.errors['unit_numbers.0']" class="text-red-500 text-xs">Please check all destination units.</div>
                        </div>

                        <div v-if="deliveryMode === 'single'">
                            <label class="block text-gray-700 dark:text-white text-sm font-bold mb-1">Person to Visit <span class="text-red-500">*</span></label>
                            <input
                                v-model="tripForm.host_name"
                                type="text"
                                placeholder="Enter the full name of the resident you are delivering to"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white dark:bg-gray-800 dark:border-gray-700"
                                :class="tripForm.errors.host_name ? 'border-red-500' : ''"
                            />
                            <div v-if="tripForm.errors.host_name" class="text-red-500 text-xs mt-1">{{ tripForm.errors.host_name }}</div>
                        </div>

                        <button
                            type="submit"
                            class="w-full font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline text-sm transition-all"
                            :class="hasActiveTrip
                                ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                : 'bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50'"
                            :disabled="tripForm.processing || hasActiveTrip || (deliveryMode === 'multi' && stopList.length < 2) || (deliveryMode === 'single' && !currentUnitLabel)"
                        >
                            <span v-if="hasActiveTrip">⛔ Active Trip In Progress</span>
                            <span v-else>{{ tripForm.processing ? 'Requesting...' : (deliveryMode === 'multi' ? 'Submit multi-stop request' : 'Submit request') }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div v-if="activeRun || activeLog" class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex flex-col items-center">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white border-b pb-2 w-full mb-4">Active Entry</h3>
                        <span
                            v-if="activeRun"
                            class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-2 bg-orange-100 dark:bg-orange-950 text-orange-700 dark:text-orange-300"
                        >
                            {{ runStatusLabel(activeRun) }}
                        </span>

                        <span
                            class="px-3 py-1 rounded-full text-xs font-bold mb-4"
                            :class="{
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-400': (activeRun?.status || activeLog?.status) === 'Pending',
                                'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400': (activeRun?.status || activeLog?.status) === 'Approved',
                                'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400': (activeRun?.status || activeLog?.status) === 'Checked In',
                            }"
                        >
                            {{ activeRun?.status || activeLog?.status }}
                        </span>

                        <div v-if="allStopsApproved" class="bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm mb-4">
                            <div v-html="qrCodeSvg" class="h-48 w-48 flex justify-center items-center bg-white p-2 rounded shadow-inner"></div>
                        </div>
                        <div v-else class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-900/40 rounded-lg p-4 mb-4 text-center">
                            <p class="text-yellow-800 dark:text-yellow-300 font-bold text-sm">⏳ Waiting for resident approval</p>
                            <p class="text-yellow-600 dark:text-yellow-400 text-xs mt-1">QR code will appear once all residents approve this request</p>
                        </div>

                        <div class="text-center text-sm space-y-2 w-full">
                            <p class="font-bold text-gray-700 dark:text-gray-300">Vehicle: {{ delivery.vehicle_number }}</p>

                            <button
                                v-if="(activeRun?.status === 'Pending' || activeRun?.status === 'Approved' || activeLog?.status === 'Pending' || activeLog?.status === 'Approved')"
                                @click="cancelTrip"
                                class="mt-3 w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline text-sm"
                            >
                                Cancel Request
                            </button>

                            <div v-if="activeRun?.type === 'multi' && activeRun.logs?.length" class="text-left bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/40 rounded-xl p-3">
                                <p class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest mb-2">Stops</p>
                                <ul class="space-y-1">
                                    <li
                                        v-for="log in activeRun.logs"
                                        :key="log.id"
                                        class="flex justify-between text-xs font-bold text-gray-700 dark:text-gray-300"
                                    >
                                        <span>{{ log.destination }}</span>
                                        <span
                                            :class="{
                                                'text-yellow-600 dark:text-yellow-400': log.status === 'Pending',
                                                'text-green-600 dark:text-green-400': log.status === 'Approved',
                                                'text-blue-600 dark:text-blue-400': log.status === 'Checked In',
                                                'text-gray-500 dark:text-gray-400': log.status === 'Checked Out',
                                                'text-red-500 dark:text-red-400': log.status === 'Rejected',
                                            }"
                                        >{{ log.status }}</span>
                                    </li>
                                </ul>
                            </div>
                            <p v-else class="text-gray-500 dark:text-gray-400">Destination: Unit {{ activeLog?.destination }}</p>

                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 max-w-sm mx-auto">
                                Show this QR code at the guard post. Each unit’s resident must approve their stop before entry.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex flex-col h-full">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white border-b pb-2">Recent Entry Logs</h3>

                        <div v-if="activeGroupedLogs && activeGroupedLogs.length > 0" class="space-y-3">
                            <div v-for="group in activeGroupedLogs" :key="group.type === 'multi' ? group.run.id : group.log.id" class="p-3 rounded border border-gray-200 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">
                                <div v-if="group.type === 'multi'" class="space-y-2">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="text-[10px] text-orange-600 dark:text-orange-400 font-bold uppercase">Multi-stop trip ({{ group.logs.length }} units)</span>
                                            <div class="mt-1 space-y-1">
                                                <div v-for="log in group.logs" :key="log.id" class="flex justify-between items-center">
                                                    <span class="font-bold dark:text-white text-xs">{{ log.destination }}</span>
                                                    <span
                                                        class="font-bold text-xs"
                                                        :class="{
                                                            'text-yellow-600 dark:text-yellow-400': log.status === 'Pending',
                                                            'text-blue-600 dark:text-blue-400': log.status === 'Approved',
                                                            'text-green-600 dark:text-green-400': log.status === 'Checked In',
                                                            'text-gray-500 dark:text-gray-400': log.status === 'Checked Out',
                                                            'text-red-500 dark:text-red-400': log.status === 'Rejected' || log.status === 'Cancelled',
                                                        }"
                                                    >{{ log.status }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-gray-400 dark:text-gray-500 text-[10px]">Date: {{ formatMalaysiaDate(group.created_at) }}</div>
                                </div>
                                <div v-else class="space-y-1">
                                    <div class="flex justify-between">
                                        <span class="font-bold dark:text-white">Unit {{ group.log.destination }}</span>
                                        <span
                                            class="font-bold"
                                            :class="{
                                                'text-yellow-600 dark:text-yellow-400': group.log.status === 'Pending',
                                                'text-blue-600 dark:text-blue-400': group.log.status === 'Approved',
                                                'text-green-600 dark:text-green-400': group.log.status === 'Checked In',
                                                'text-gray-500 dark:text-gray-400': group.log.status === 'Checked Out',
                                                'text-red-500 dark:text-red-400': group.log.status === 'Rejected' || group.log.status === 'Cancelled',
                                            }"
                                        >{{ group.log.status }}</span>
                                    </div>
                                    <div v-if="group.log.host_name" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold">👤 For: {{ group.log.host_name }}</div>
                                </div>
                                <div v-if="group.type === 'single'" class="text-gray-400 dark:text-gray-500 text-[10px] mt-1">Date: {{ formatMalaysiaDate(group.created_at) }}</div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 dark:text-gray-400 text-sm italic py-8 text-center flex-1">
                            No recent logs found.
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <div class="mt-6 bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-center text-sm text-gray-500 dark:text-white">
                Please present your vehicle registration and face at the guard post for verification.
            </div>
        </div>
    </DeliveryAuthenticatedLayout>
</template>
