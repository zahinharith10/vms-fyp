<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    inquiries: Array,
});

const statusClass = (status) => {
    if (status === 'Pending') return 'bg-yellow-100 dark:bg-yellow-950/40 text-yellow-700 dark:text-yellow-400';
    if (status === 'Resolved') return 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-400';
    return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400';
};

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Contact Us" />

    <ResidentAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Contact Us</h2>
        </template>

        <div class="max-w-3xl mx-auto">
            <!-- Hero Banner -->
            <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 rounded-3xl p-8 mb-6 shadow-lg shadow-indigo-200/40 dark:shadow-indigo-950/30 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-48 h-48 bg-white/5 rounded-full -translate-y-12 translate-x-12"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full translate-y-8 -translate-x-8"></div>
                <div class="relative z-10">
                    <div class="text-4xl mb-3">💬</div>
                    <h3 class="text-2xl font-black mb-1">Have a question?</h3>
                    <p class="text-indigo-200 text-sm font-medium">We're here to help. Submit your inquiry and the management team will get back to you.</p>
                    <Link
                        :href="route('resident.inquiries.create')"
                        class="mt-5 inline-flex items-center gap-2 bg-white text-indigo-700 font-black px-5 py-2.5 rounded-2xl text-sm shadow-md hover:bg-indigo-50 transition-all duration-200"
                    >
                        <span>✉️</span> New Inquiry
                    </Link>
                </div>
            </div>

            <!-- Inquiries List -->
            <div class="space-y-4">
                <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest px-1">My Inquiries ({{ inquiries.length }})</h4>

                <div v-if="inquiries.length === 0" class="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-800">
                    <div class="h-16 w-16 bg-indigo-50 dark:bg-indigo-950/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">📭</span>
                    </div>
                    <h3 class="text-lg font-black text-gray-800 dark:text-white">No inquiries yet</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Submit your first inquiry using the button above.</p>
                </div>

                <div
                    v-for="inquiry in inquiries"
                    :key="inquiry.id"
                    class="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-gray-800 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-200"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest"
                                    :class="statusClass(inquiry.status)"
                                >
                                    {{ inquiry.status }}
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">{{ formatDate(inquiry.created_at) }}</span>
                            </div>
                            <h5 class="font-black text-gray-900 dark:text-white">{{ inquiry.subject }}</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1.5 whitespace-pre-wrap">{{ inquiry.message }}</p>

                            <!-- Admin Reply -->
                            <div v-if="inquiry.reply" class="mt-4 pl-4 border-l-2 border-emerald-500 space-y-1 bg-emerald-50/30 dark:bg-emerald-950/10 p-3.5 rounded-r-2xl rounded-bl-2xl">
                                <div class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">
                                    <span>✉️</span>
                                    <span>Reply from Admin</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ inquiry.reply }}</p>
                                <p class="text-[9px] text-gray-400 dark:text-gray-500 font-medium mt-1">Replied on {{ formatDate(inquiry.replied_at) }}</p>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <div class="h-10 w-10 rounded-2xl flex items-center justify-center"
                                :class="inquiry.status === 'Resolved' ? 'bg-green-50 dark:bg-green-950/30' : 'bg-yellow-50 dark:bg-yellow-950/30'">
                                <span class="text-xl">{{ inquiry.status === 'Resolved' ? '✅' : '⏳' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ResidentAuthenticatedLayout>
</template>
