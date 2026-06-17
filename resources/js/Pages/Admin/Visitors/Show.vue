<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    visitor: Object,
    visits: Array,
});

const statusColor = (status) => {
    if (!status) return 'bg-gray-400 text-white';
    const s = status.toLowerCase();
    if (s.includes('checked in') || s.includes('approved')) return 'bg-emerald-100 text-emerald-800';
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

const visitStats = computed(() => {
    return {
        totalVisits: props.visits?.length || 0,
        approvedVisits: props.visits?.filter(v => v.status === 'Approved' || v.status === 'Checked In').length || 0,
        pendingVisits: props.visits?.filter(v => v.status === 'Pending').length || 0,
        rejectedVisits: props.visits?.filter(v => v.status === 'Rejected').length || 0,
    };
});
</script>

<template>
    <Head :title="`Visitor: ${visitor.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.visitors.index')" class="text-gray-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visitor Profile</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ── Visitor Profile Card ──────────────────────── -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <!-- Photo -->
                            <div class="md:col-span-1 flex flex-col items-center">
                                <div class="w-32 h-32 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center mb-4">
                                    <img v-if="visitor.photo" :src="'/storage/' + visitor.photo" class="w-full h-full object-cover" />
                                    <span v-else class="text-4xl font-bold text-gray-400">{{ visitor.name.charAt(0) }}</span>
                                </div>
                                <h3 class="text-lg font-bold text-center text-gray-900">{{ visitor.name }}</h3>
                                <p class="text-sm text-gray-500 text-center mt-1">Registered Visitor</p>
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
                                                <p class="text-sm text-gray-900 font-medium">{{ visitor.email || 'Not provided' }}</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Phone</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ visitor.phone || 'Not provided' }}</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">IC Number</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ visitor.ic_number }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Vehicle & Other Info -->
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Additional Information</h4>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Vehicle Number</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ visitor.vehicle_number || 'Not provided' }}</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Registered Date</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ formatDate(visitor.created_at) }}</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-500 uppercase">Last Updated</label>
                                                <p class="text-sm text-gray-900 font-medium">{{ formatDate(visitor.updated_at) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Visit Statistics ──────────────────────────── -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Total Visits</div>
                        <div class="text-3xl font-bold text-gray-900 mt-2">{{ visitStats.totalVisits }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-emerald-500">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Approved</div>
                        <div class="text-3xl font-bold text-emerald-600 mt-2">{{ visitStats.approvedVisits }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-amber-500">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Pending</div>
                        <div class="text-3xl font-bold text-amber-600 mt-2">{{ visitStats.pendingVisits }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
                        <div class="text-gray-600 text-sm font-semibold uppercase tracking-wide">Rejected</div>
                        <div class="text-3xl font-bold text-red-600 mt-2">{{ visitStats.rejectedVisits }}</div>
                    </div>
                </div>

                <!-- ── Visit History ────────────────────────────── -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Visit History</h3>
                        
                        <div v-if="visits && visits.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Visit Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Unit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Purpose</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Host</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="visit in visits" :key="visit.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(visit.created_at) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ visit.unit_number || '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ visit.purpose || '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ visit.host_name || '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColor(visit.status)}`">
                                                {{ visit.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <Link :href="route('admin.visit-logs.show', visit.id)" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-8">
                            <p class="text-gray-500">No visit records found for this visitor.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
