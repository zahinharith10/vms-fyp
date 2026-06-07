<script setup>
import AdminAuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    inquiries: Array,
    filters: Object,
});

// Local reactive filters
const search = ref(props.filters?.search || '');
const userType = ref(props.filters?.user_type || 'All');
const status = ref(props.filters?.status || 'All');

const applyFilters = () => {
    router.get(route('admin.inquiries.index'), {
        search: search.value || undefined,
        user_type: userType.value !== 'All' ? userType.value : undefined,
        status: status.value !== 'All' ? status.value : undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    search.value = '';
    userType.value = 'All';
    status.value = 'All';
    router.get(route('admin.inquiries.index'), {}, { preserveState: false });
};

// Debounce search
let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
};

// Resolve inquiry
const resolveForm = useForm({});
const resolveInquiry = (id) => {
    resolveForm.post(route('admin.inquiries.resolve', id), {
        preserveScroll: true,
    });
};

// Delete inquiry
const deleteForm = useForm({});
const deleteInquiry = (id) => {
    if (!confirm('Are you sure you want to delete this inquiry?')) return;
    deleteForm.delete(route('admin.inquiries.destroy', id), {
        preserveScroll: true,
    });
};

// Selected inquiry for detail modal
const selectedInquiry = ref(null);
const openDetail = (inquiry) => selectedInquiry.value = inquiry;
const closeDetail = () => selectedInquiry.value = null;

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-MY', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const userTypeColor = (type) => {
    if (type === 'Visitor') return 'bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300';
    if (type === 'Resident') return 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300';
    if (type === 'Delivery') return 'bg-orange-100 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300';
    return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';
};

const statusColor = (s) => {
    if (s === 'Pending') return 'bg-yellow-100 dark:bg-yellow-950/40 text-yellow-700 dark:text-yellow-400';
    if (s === 'Resolved') return 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-400';
    return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';
};

const pendingCount = computed(() => props.inquiries.filter(i => i.status === 'Pending').length);
</script>

<template>
    <Head title="Inquiries" />

    <AdminAuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <span class="text-2xl">💬</span>
                <h2 class="font-bold text-xl text-gray-800 dark:text-white leading-tight">
                    Inquiries
                    <span v-if="pendingCount > 0" class="ml-2 inline-flex items-center justify-center h-5 px-2 bg-yellow-400 dark:bg-yellow-500 text-white text-xs font-black rounded-full">
                        {{ pendingCount }}
                    </span>
                </h2>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ inquiries.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-yellow-100 dark:border-yellow-900/30 shadow-sm">
                    <p class="text-[10px] font-black text-yellow-500 uppercase tracking-widest">Pending</p>
                    <p class="text-2xl font-black text-yellow-600 dark:text-yellow-400 mt-1">{{ inquiries.filter(i => i.status === 'Pending').length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-green-100 dark:border-green-900/30 shadow-sm">
                    <p class="text-[10px] font-black text-green-500 uppercase tracking-widest">Resolved</p>
                    <p class="text-2xl font-black text-green-600 dark:text-green-400 mt-1">{{ inquiries.filter(i => i.status === 'Resolved').length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">From Residents</p>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ inquiries.filter(i => i.user_type === 'Resident').length }}</p>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl p-5 border border-gray-100 dark:border-gray-800 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 dark:text-gray-500 pointer-events-none">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Search name, email, subject..."
                            class="pl-10 pr-4 py-2.5 w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl text-sm font-medium text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                        />
                    </div>

                    <!-- User Type Filter -->
                    <select
                        v-model="userType"
                        @change="applyFilters"
                        class="py-2.5 pl-4 pr-10 w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl text-sm font-semibold text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer"
                    >
                        <option value="All">All User Types</option>
                        <option value="Visitor">Visitors</option>
                        <option value="Resident">Residents</option>
                        <option value="Delivery">Delivery</option>
                    </select>

                    <!-- Status Filter -->
                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="py-2.5 pl-4 pr-10 w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl text-sm font-semibold text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer"
                    >
                        <option value="All">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>

                <div v-if="search || userType !== 'All' || status !== 'All'" class="mt-3 flex justify-end">
                    <button @click="resetFilters" class="text-xs font-black text-indigo-600 dark:text-indigo-400 hover:underline uppercase tracking-widest">
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Inquiries Table -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <!-- Empty State -->
                <div v-if="inquiries.length === 0" class="p-12 text-center">
                    <div class="h-16 w-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">📭</span>
                    </div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white">No Inquiries Found</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">No inquiries match your current filters.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/30">
                                <th class="text-left px-5 py-3.5 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">From</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Subject</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest hidden md:table-cell">Type</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Status</th>
                                <th class="text-left px-5 py-3.5 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest hidden lg:table-cell">Date</th>
                                <th class="text-right px-5 py-3.5 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr
                                v-for="inquiry in inquiries"
                                :key="inquiry.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors group"
                            >
                                <!-- From -->
                                <td class="px-5 py-4">
                                    <div>
                                        <p class="font-black text-sm text-gray-900 dark:text-white">{{ inquiry.name }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ inquiry.email }}</p>
                                    </div>
                                </td>

                                <!-- Subject -->
                                <td class="px-5 py-4 max-w-[200px]">
                                    <button
                                        @click="openDetail(inquiry)"
                                        class="text-left group/subject"
                                    >
                                        <p class="font-bold text-sm text-gray-800 dark:text-gray-200 truncate group-hover/subject:text-indigo-600 dark:group-hover/subject:text-indigo-400 transition-colors">{{ inquiry.subject }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ inquiry.message }}</p>
                                    </button>
                                </td>

                                <!-- Type -->
                                <td class="px-5 py-4 hidden md:table-cell">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest" :class="userTypeColor(inquiry.user_type)">
                                        {{ inquiry.user_type }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest" :class="statusColor(inquiry.status)">
                                        {{ inquiry.status }}
                                    </span>
                                </td>

                                <!-- Date -->
                                <td class="px-5 py-4 hidden lg:table-cell">
                                    <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">{{ formatDate(inquiry.created_at) }}</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- View Detail -->
                                        <button
                                            @click="openDetail(inquiry)"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 transition-all"
                                            title="View details"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Resolve -->
                                        <button
                                            v-if="inquiry.status === 'Pending'"
                                            @click="resolveInquiry(inquiry.id)"
                                            :disabled="resolveForm.processing"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/30 transition-all"
                                            title="Mark as resolved"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            @click="deleteInquiry(inquiry.id)"
                                            :disabled="deleteForm.processing"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all"
                                            title="Delete inquiry"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="selectedInquiry" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="closeDetail">
                    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 w-full max-w-lg overflow-hidden">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">💬</span>
                                <div>
                                    <h3 class="font-black text-gray-900 dark:text-white">Inquiry Detail</h3>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ formatDate(selectedInquiry.created_at) }}</p>
                                </div>
                            </div>
                            <button @click="closeDetail" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-6 py-5 space-y-4">
                            <!-- Badges -->
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest" :class="userTypeColor(selectedInquiry.user_type)">
                                    {{ selectedInquiry.user_type }}
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest" :class="statusColor(selectedInquiry.status)">
                                    {{ selectedInquiry.status }}
                                </span>
                            </div>

                            <!-- Sender Info -->
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Name</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ selectedInquiry.name }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Email</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ selectedInquiry.email }}</span>
                                </div>
                                <div v-if="selectedInquiry.phone" class="flex justify-between text-sm">
                                    <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Phone</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ selectedInquiry.phone }}</span>
                                </div>
                            </div>

                            <!-- Subject & Message -->
                            <div>
                                <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Subject</p>
                                <p class="font-black text-gray-900 dark:text-white">{{ selectedInquiry.subject }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Message</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-4">{{ selectedInquiry.message }}</p>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-3">
                            <button
                                v-if="selectedInquiry.status === 'Pending'"
                                @click="resolveInquiry(selectedInquiry.id); closeDetail()"
                                class="px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white font-black text-sm rounded-2xl transition-all shadow-sm"
                            >
                                ✅ Mark Resolved
                            </button>
                            <button
                                @click="deleteInquiry(selectedInquiry.id); closeDetail()"
                                class="px-5 py-2.5 bg-red-50 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-950/50 text-red-600 dark:text-red-400 font-black text-sm rounded-2xl border border-red-100 dark:border-red-900 transition-all"
                            >
                                🗑️ Delete
                            </button>
                            <button
                                @click="closeDetail"
                                class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-black text-sm rounded-2xl transition-all"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminAuthenticatedLayout>
</template>
