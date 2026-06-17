<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    personnel: Object,
    logs: Array,
});

const statusColor = (status) => {
    if (!status) return 'bg-gray-400 text-white';
    const s = status.toLowerCase();
    if (s.includes('on-site') || s.includes('entered') || s.includes('approved')) return 'bg-emerald-100 text-emerald-800';
    if (s.includes('pending')) return 'bg-amber-100 text-amber-800';
    if (s.includes('rejected')) return 'bg-red-100 text-red-800';
    if (s.includes('completed') || s.includes('checked out')) return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getVisualStatus = (log) => {
    if (log.exit_time) return 'Completed';
    if (log.entry_time) return 'On-Site';
    return log.status || 'Pending';
};

const deliveryStats = computed(() => {
    const logsList = props.logs || [];
    return {
        total: logsList.length,
        active: logsList.filter(l => l.entry_time && !l.exit_time).length,
        completed: logsList.filter(l => l.exit_time).length,
        pending: logsList.filter(l => l.status === 'Pending' && !l.entry_time).length,
        rejected: logsList.filter(l => l.status === 'Rejected').length,
    };
});
</script>

<template>
    <Head :title="`Personnel: ${personnel.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.delivery.personnel.index')" class="text-gray-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Delivery Personnel Profile</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ── Personnel Profile Card ──────────────────────── -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <!-- Photo -->
                            <div class="md:col-span-1 flex flex-col items-center">
                                <div class="w-32 h-32 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center mb-4">
                                    <img v-if="personnel.photo" :src="'/storage/' + personnel.photo" class="w-full h-full object-cover" />
                                    <span v-else class="text-4xl font-bold text-gray-400">{{ personnel.name.charAt(0) }}</span>
                                </div>
                                <h3 class="text-lg font-bold text-center text-gray-900">{{ personnel.name }}</h3>
                                <p class="text-sm text-gray-500 text-center mt-1">
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-bold mr-1">{{ personnel.company }}</span>
                                    Courier
                                </p>
                            </div>

                            <!-- Details -->
                            <div class="md:col-span-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Contact Information -->
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Contact Information</h4>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Email</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ personnel.email || 'Not provided' }}</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Phone</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ personnel.phone || 'Not provided' }}</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">IC/Passport Number</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ personnel.ic_number }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Vehicle & Other Info -->
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Delivery Profile Details</h4>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Vehicle Type & Plate</label>
                                                <p class="text-sm text-gray-900 font-medium uppercase font-mono tracking-wider">
                                                    {{ personnel.vehicle_type }} — {{ personnel.vehicle_number }}
                                                </p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Status</label>
                                                <p class="text-sm font-medium mt-0.5">
                                                    <span :class="[personnel.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']" class="px-2 py-0.5 rounded text-xs font-bold">
                                                        {{ personnel.status }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Registered Date</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ formatDate(personnel.created_at) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Delivery Statistics ──────────────────────────── -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Total Trips</div>
                        <div class="text-3xl font-bold text-gray-900 mt-2">{{ deliveryStats.total }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-emerald-500">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Active (On-Site)</div>
                        <div class="text-3xl font-bold text-emerald-600 mt-2">{{ deliveryStats.active }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-400">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Completed</div>
                        <div class="text-3xl font-bold text-blue-600 mt-2">{{ deliveryStats.completed }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-amber-500">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Pending</div>
                        <div class="text-3xl font-bold text-amber-600 mt-2">{{ deliveryStats.pending }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Rejected</div>
                        <div class="text-3xl font-bold text-red-600 mt-2">{{ deliveryStats.rejected }}</div>
                    </div>
                </div>

                <!-- ── Delivery History ────────────────────────────── -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Delivery History</h3>
                        
                        <div v-if="logs && logs.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Destination Unit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Host</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Approved By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(log.created_at) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded font-semibold">{{ log.destination }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ log.host_name || '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ log.approved_by || '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColor(getVisualStatus(log))}`">
                                                {{ getVisualStatus(log) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <Link :href="route('admin.delivery.logs.show', log.id)" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                                View Details
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-8">
                            <p class="text-gray-500">No delivery logs found for this courier.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
