<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    delivery: Object,
    logs: Array,
    activeLog: Object,
    qrCodeSvg: String,
});

const block = ref('');
const floor = ref('');
const unit = ref('');

const tripForm = useForm({
    unit_number: '',
});

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
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tighter uppercase italic leading-none">Duty Dashboard</h2>
                    <p class="text-orange-600 font-bold text-xs uppercase tracking-widest mt-1">Personnel ID: #DP-{{ delivery?.id?.toString().padStart(4, '0') || '0000' }}</p>
                </div>
                <div class="flex items-center px-6 py-3 bg-orange-100 rounded-2xl border border-orange-200">
                    <span class="text-orange-700 font-black text-sm uppercase tracking-widest">{{ delivery.company }} Official</span>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-3xl shadow-xl shadow-gray-100 border border-gray-100 transform hover:scale-[1.02] transition-transform duration-300">
                    <div class="flex items-center mb-4">
                        <div class="h-12 w-12 bg-blue-100 rounded-2xl flex items-center justify-center text-2xl">🚛</div>
                        <div class="ml-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Active Vehicle</p>
                            <p class="text-lg font-black text-gray-900 tracking-tight">{{ delivery.vehicle_number }}</p>
                        </div>
                    </div>
                    <div class="h-1 w-full bg-blue-50 rounded-full">
                        <div class="h-1 bg-blue-500 rounded-full w-2/3"></div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-xl shadow-gray-100 border border-gray-100 transform hover:scale-[1.02] transition-transform duration-300">
                    <div class="flex items-center mb-4">
                        <div class="h-12 w-12 bg-green-100 rounded-2xl flex items-center justify-center text-2xl">✅</div>
                        <div class="ml-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Duty Status</p>
                            <p class="text-lg font-black text-gray-900 tracking-tight">{{ delivery.status }}</p>
                        </div>
                    </div>
                    <div class="h-1 w-full bg-green-50 rounded-full">
                        <div class="h-1 bg-green-500 rounded-full w-full"></div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-xl shadow-gray-100 border border-gray-100 transform hover:scale-[1.02] transition-transform duration-300">
                    <div class="flex items-center mb-4">
                        <div class="h-12 w-12 bg-orange-100 rounded-2xl flex items-center justify-center text-2xl">📦</div>
                        <div class="ml-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Recent Trips</p>
                            <p class="text-lg font-black text-gray-900 tracking-tight">{{ logs?.length || 0 }} Entries</p>
                        </div>
                    </div>
                    <div class="h-1 w-full bg-orange-50 rounded-full">
                        <div class="h-1 bg-orange-500 rounded-full w-1/3"></div>
                    </div>
                </div>
            </div>

            <!-- Main Panel: QR Pass / Trip Registration -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Welcome & Trip Info Card -->
                <div class="lg:col-span-2 bg-indigo-900 rounded-[40px] p-10 text-white relative overflow-hidden shadow-2xl shadow-indigo-200 flex flex-col justify-between min-h-[350px]">
                    <div class="relative z-10">
                        <span class="px-4 py-1.5 bg-white/10 rounded-full text-xs font-black uppercase tracking-widest text-indigo-200 border border-white/10">Rider Portal</span>
                        <h3 class="text-4xl font-black tracking-tighter mt-6 mb-4 italic leading-tight">Welcome back, {{ delivery?.name?.split(' ')[0] || 'Driver' }}!</h3>
                        <p class="text-indigo-200 text-lg font-bold leading-relaxed max-w-lg">
                            Access granted to the Resident Management System. Register your destination unit to instantly create an entry pass.
                        </p>
                    </div>

                    <div class="relative z-10 mt-8 flex flex-wrap gap-4">
                        <Link :href="route('delivery.profile')" class="px-8 py-4 bg-white text-indigo-900 rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-indigo-50 transition-colors shadow-lg">
                            Update Profile
                        </Link>
                    </div>

                    <!-- Abstract Design Elements -->
                    <div class="absolute -top-20 -right-20 w-80 h-80 bg-indigo-500 rounded-full opacity-20 blur-3xl"></div>
                    <div class="absolute -bottom-20 -right-20 w-60 h-60 bg-white rounded-full opacity-10 blur-2xl"></div>
                </div>

                <!-- Right Side Panel: Active QR Pass OR Create Trip Form -->
                <div class="bg-white rounded-[40px] p-8 shadow-2xl shadow-gray-100 border border-gray-100 flex flex-col justify-center items-center">
                    <!-- CASE A: Has active trip -->
                    <div v-if="activeLog" class="w-full text-center space-y-6">
                        <div class="flex items-center justify-between border-b pb-4 border-gray-100">
                            <span class="text-xs font-black text-gray-400 uppercase tracking-widest text-left">Active Trip Pass</span>
                            <span 
                                class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                :class="{
                                    'bg-yellow-100 text-yellow-800 border border-yellow-200': activeLog.status === 'Pending',
                                    'bg-green-100 text-green-800 border border-green-200': activeLog.status === 'Approved',
                                    'bg-blue-100 text-blue-800 border border-blue-200': activeLog.status === 'Checked In'
                                }"
                            >
                                {{ activeLog.status }}
                            </span>
                        </div>

                        <!-- QR Code Container -->
                        <div class="p-4 bg-gray-50 rounded-3xl border border-gray-100/50 flex justify-center items-center shadow-inner relative group">
                            <div v-html="qrCodeSvg" class="h-60 w-60 flex justify-center items-center"></div>
                            <div class="absolute inset-0 bg-white/95 rounded-3xl flex flex-col items-center justify-center p-6 transition-all duration-300 opacity-0 group-hover:opacity-100">
                                <span class="text-3xl mb-2">🚗</span>
                                <span class="font-black text-gray-800 text-sm uppercase tracking-wider">{{ delivery.vehicle_number }}</span>
                                <span class="text-xs text-gray-400 font-bold uppercase mt-1">Destination: Unit {{ activeLog.destination }}</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <h4 class="font-black text-gray-900 uppercase tracking-tight text-lg italic">
                                {{ activeLog.status === 'Approved' ? '🎉 Entry Authorized!' : (activeLog.status === 'Checked In' ? '⚡ Currently Checked In' : '⏳ Awaiting Resident Approval') }}
                            </h4>
                            <p class="text-xs text-gray-400 font-bold leading-relaxed px-4">
                                {{ activeLog.status === 'Approved' ? 'Show this QR code to the guard at the gate to enter instantly.' : (activeLog.status === 'Checked In' ? 'Please present this same QR code to check out when leaving.' : 'Please wait for the resident of unit ' + activeLog.destination + ' to approve entry.') }}
                            </p>
                        </div>
                    </div>

                    <!-- CASE B: No active trip - Show form -->
                    <div v-else class="w-full space-y-6">
                        <div class="border-b pb-4 border-gray-100">
                            <h4 class="text-lg font-black text-gray-900 uppercase tracking-tighter italic">Request Entry Pass</h4>
                            <p class="text-xs text-gray-400 font-bold mt-1">Enter your destination unit number below.</p>
                        </div>

                        <form @submit.prevent="submitTrip" class="space-y-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Destination Unit</label>
                                <div class="flex space-x-2">
                                    <input v-model="block" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold px-3 py-3 text-sm text-center" placeholder="Block" required>
                                    <input v-model="floor" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold px-3 py-3 text-sm text-center" placeholder="Floor" required>
                                    <input v-model="unit" type="number" min="1" class="w-1/3 bg-gray-50 border-gray-100 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-bold px-3 py-3 text-sm text-center" placeholder="Unit" required>
                                </div>
                                <div v-if="tripForm.errors.unit_number" class="text-red-500 text-xs font-bold mt-2">{{ tripForm.errors.unit_number }}</div>
                            </div>

                            <button 
                                type="submit"
                                :disabled="tripForm.processing"
                                class="w-full bg-orange-600 hover:bg-orange-700 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-100 transition-all flex items-center justify-center uppercase tracking-widest text-xs"
                            >
                                {{ tripForm.processing ? 'Requesting...' : '⚡ Generate Entry Pass' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-[40px] shadow-2xl shadow-gray-100 border border-gray-100 overflow-hidden">
                <div class="p-10 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter italic">Recent Entry Logs</h3>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Last 10 security checkpoints</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-10 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Entry Date</th>
                                <th class="px-10 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Entry Time</th>
                                <th class="px-10 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Exit Time</th>
                                <th class="px-10 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Destination</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-10 py-6 font-bold text-gray-600">{{ new Date(log.created_at).toLocaleDateString('en-GB') }}</td>
                                <td class="px-10 py-6">
                                    <span class="px-4 py-2 bg-green-50 text-green-700 rounded-xl font-black text-xs uppercase tracking-widest border border-green-100">
                                        {{ log.entry_time }}
                                    </span>
                                </td>
                                <td class="px-10 py-6">
                                    <span v-if="log.exit_time" class="px-4 py-2 bg-red-50 text-red-700 rounded-xl font-black text-xs uppercase tracking-widest border border-red-100">
                                        {{ log.exit_time }}
                                    </span>
                                    <span v-else class="text-gray-400 font-bold italic tracking-wider">In Progress...</span>
                                </td>
                                <td class="px-10 py-6 font-black text-gray-900 group-hover:text-indigo-600 transition-colors uppercase italic">{{ log.destination }}</td>
                            </tr>
                            <tr v-if="logs.length === 0">
                                <td colspan="4" class="px-10 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="h-20 w-20 bg-gray-50 rounded-full flex items-center justify-center text-4xl mb-4">📭</div>
                                        <p class="text-gray-400 font-black uppercase tracking-widest text-xs">No entry logs found for this personnel.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DeliveryAuthenticatedLayout>
</template>
