<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Link, router } from '@inertiajs/vue3';
import { formatMalaysiaTime } from '@/utils/datetime';

const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
let pollingInterval = null;

const fetchNotifications = async () => {
    try {
        const response = await axios.get(route('notifications.index'));
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.unread_count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    }
};

const markAsRead = async () => {
    if (unreadCount.value === 0) return;
    try {
        await axios.post(route('notifications.read'));
        unreadCount.value = 0;
        // Optionally update the list status locally
        notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date() }));
    } catch (error) {
        console.error('Failed to mark notifications as read:', error);
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        markAsRead();
    }
};

const handleNotificationClick = (notification) => {
    isOpen.value = false;
    const isAdmin = window.location.pathname.startsWith('/admin');
    const type = notification.data?.type ?? '';

    if (type.startsWith('inquiry_')) {
        // Route to the correct inquiry page
        if (isAdmin) {
            router.visit(route('admin.inquiries.index'));
        } else if (window.location.pathname.startsWith('/resident')) {
            router.visit(route('resident.inquiries.index'));
        } else if (window.location.pathname.startsWith('/visitor')) {
            router.visit(route('visitor.inquiries.index'));
        } else if (window.location.pathname.startsWith('/delivery')) {
            router.visit(route('delivery.inquiries.index'));
        }
        return;
    }

    if (isAdmin) {
        if (notification.data && notification.data.visit_id) {
            router.visit(route('admin.visit-logs.show', notification.data.visit_id));
        } else {
            router.visit(route('admin.visit-logs.index'));
        }
    } else {
        router.visit(route('resident.visitors.index'));
    }
};

onMounted(() => {
    fetchNotifications();
    // Poll every 30 seconds
    pollingInterval = setInterval(fetchNotifications, 30000);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

</script>

<template>
    <div class="relative">
        <!-- Bell Icon -->
        <button @click="toggleDropdown" class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <!-- Badge -->
            <span v-if="unreadCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full border-2 border-white">
                {{ unreadCount }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <div v-if="isOpen" @click.away="isOpen = false" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 overflow-hidden transform origin-top-right transition-all duration-200 scale-100 opacity-100">
            <div class="p-4 border-b border-gray-50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-800">Notifications</h3>
                <span class="text-xs text-gray-400 font-medium">{{ unreadCount }} New</span>
            </div>
            
            <div class="max-h-96 overflow-y-auto scrollbar-hide">
                <div v-if="notifications.length > 0">
                    <div v-for="notification in notifications" :key="notification.id" @click="handleNotificationClick(notification)" class="p-4 border-b border-gray-50 hover:bg-indigo-50/20 active:bg-indigo-100/30 transition-colors cursor-pointer" :class="{ 'bg-indigo-50/40': !notification.read_at }">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <span v-if="notification.data.type === 'visitor_arrival'" class="h-8 w-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs">
                                    ✓
                                </span>
                                <span v-else-if="notification.data.type && notification.data.type.startsWith('inquiry_')" class="h-8 w-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xs">
                                    ✉
                                </span>
                                <span v-else class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs">
                                    👤
                                </span>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm text-gray-800 font-medium leading-tight">
                                    {{ notification.data.message }}
                                </p>
                                <div class="flex justify-between items-center mt-1">
                                    <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider">
                                        {{ notification.data.type.replace('_', ' ') }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">
                                        {{ formatMalaysiaTime(notification.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="p-8 text-center">
                    <p class="text-sm text-gray-400 italic">No notifications yet.</p>
                </div>
            </div>
            
            <div class="p-3 bg-gray-50 text-center border-t border-gray-100">
                <button @click="isOpen = false" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                    Close Panel
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
