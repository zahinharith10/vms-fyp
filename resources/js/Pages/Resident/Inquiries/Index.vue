<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    inquiries: Array,
});

const expandedInquiryId = ref(null);
const toggleExpand = (id) => {
    expandedInquiryId.value = expandedInquiryId.value === id ? null : id;
};

const replyForm = useForm({
    message: '',
});

const submitReply = (inquiryId) => {
    replyForm.post(route('resident.inquiries.reply', inquiryId), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
        }
    });
};

const endForm = useForm({});
const endInquiry = (inquiryId) => {
    if (!confirm('Are you sure you want to end this inquiry? You will no longer be able to send replies.')) return;
    endForm.post(route('resident.inquiries.end', inquiryId), {
        preserveScroll: true,
    });
};

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
                    @click="toggleExpand(inquiry.id)"
                    class="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-gray-800 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-200 cursor-pointer"
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
                            <h5 class="font-black text-gray-900 dark:text-white" :class="{ 'truncate': expandedInquiryId !== inquiry.id }">
                                {{ inquiry.subject }}
                            </h5>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" :class="{ 'line-clamp-2': expandedInquiryId !== inquiry.id, 'whitespace-pre-wrap': expandedInquiryId === inquiry.id }">
                                {{ inquiry.message }}
                            </p>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <div class="h-10 w-10 rounded-2xl flex items-center justify-center bg-gray-50 dark:bg-gray-800/50">
                                <span class="text-lg transition-transform duration-200" :class="{ 'rotate-180': expandedInquiryId === inquiry.id }">
                                    👇
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded Chat Content -->
                    <div v-if="expandedInquiryId === inquiry.id" class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-800 space-y-4" @click.stop>
                        <!-- Conversation Thread -->
                        <div class="space-y-3">
                            <h6 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Conversation History</h6>

                            <div v-if="inquiry.messages && inquiry.messages.length > 0" class="space-y-3 flex flex-col gap-2">
                                <div
                                    v-for="msg in inquiry.messages"
                                    :key="msg.id"
                                    class="flex flex-col max-w-[85%] rounded-2xl p-3 text-xs leading-relaxed"
                                    :class="msg.sender_type === 'User'
                                        ? 'bg-indigo-600 text-white ml-auto rounded-tr-none'
                                        : 'bg-gray-50 dark:bg-gray-800/80 text-gray-800 dark:text-gray-200 mr-auto rounded-tl-none border border-gray-100 dark:border-gray-700'"
                                >
                                    <div class="flex items-center gap-1.5 text-[8px] font-black uppercase tracking-wider mb-1"
                                        :class="msg.sender_type === 'User' ? 'text-indigo-200' : 'text-gray-400 dark:text-gray-500'">
                                        <span>{{ msg.sender_name }}</span>
                                        <span>•</span>
                                        <span>{{ formatDate(msg.created_at) }}</span>
                                    </div>
                                    <p class="whitespace-pre-wrap">{{ msg.message }}</p>
                                </div>
                            </div>

                            <p v-else class="text-xs text-gray-400 dark:text-gray-500 italic">No replies yet. The management team will review your inquiry shortly.</p>
                        </div>

                        <!-- Chat reply form and End button -->
                        <div v-if="inquiry.status !== 'Resolved'" class="space-y-4 pt-2">
                            <!-- Reply form -->
                            <form @submit.prevent="submitReply(inquiry.id)" class="space-y-2">
                                <textarea
                                    v-model="replyForm.message"
                                    rows="2"
                                    placeholder="Type your reply to the management..."
                                    class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl text-xs font-medium text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all p-3"
                                    required
                                ></textarea>
                                <div class="flex justify-between items-center">
                                    <!-- End button -->
                                    <button
                                        type="button"
                                        @click="endInquiry(inquiry.id)"
                                        class="px-4 py-2 bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-950/40 text-red-600 dark:text-red-400 font-black text-[10px] uppercase tracking-wider rounded-xl border border-red-100/50 dark:border-red-900/50 transition-all"
                                    >
                                        🛑 End Inquiry
                                    </button>

                                    <button
                                        type="submit"
                                        :disabled="replyForm.processing"
                                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-[10px] uppercase tracking-wider rounded-xl transition-all shadow-sm flex items-center gap-1.5 disabled:opacity-50"
                                    >
                                        <span>✉️</span> Send Reply
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Resolved/Closed Banner -->
                        <div v-else class="pt-2 text-center bg-gray-50/50 dark:bg-gray-850/50 rounded-2xl p-4 border border-gray-100/30 dark:border-gray-800/30">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400 text-[10px] font-black uppercase tracking-widest rounded-full">
                                Resolved & Closed
                            </span>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">This inquiry has been closed and resolved. Thank you!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ResidentAuthenticatedLayout>
</template>
