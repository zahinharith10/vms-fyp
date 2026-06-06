<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    guard: Object,
});

const shifts = Array.isArray(props.guard.shift)
    ? props.guard.shift
    : (props.guard.shift ? [props.guard.shift] : []);

const shiftConfig = {
    Morning:   { color: 'bg-amber-100 text-amber-800 border-amber-200',   dot: 'bg-amber-400',   icon: '🌅', hours: '07:00 – 15:00' },
    Afternoon: { color: 'bg-indigo-100 text-indigo-800 border-indigo-200', dot: 'bg-indigo-400',  icon: '☀️', hours: '15:00 – 23:00' },
    Night:     { color: 'bg-slate-100 text-slate-800 border-slate-200',    dot: 'bg-slate-500',   icon: '🌙', hours: '23:00 – 07:00' },
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-MY', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
};
</script>

<template>
    <Head :title="`Guard — ${guard.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.guards.index')" class="text-gray-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guard Profile</h2>
            </div>
        </template>

        <template #actions>
            <div class="flex gap-2">
                <Link :href="route('admin.guards.edit', guard.id)" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 font-semibold text-sm transition duration-150 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Guard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Hero Card -->
                <div class="bg-white shadow-sm rounded-2xl border border-gray-100">
                    <!-- Banner with avatar anchored to its bottom -->
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-t-2xl relative" style="height: 140px;">
                        <!-- Status badge -->
                        <div class="absolute top-4 right-4">
                            <span :class="guard.status === 'Active'
                                ? 'bg-green-400/20 text-green-100 border border-green-400/30'
                                : 'bg-red-400/20 text-red-100 border border-red-400/30'"
                                class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider">
                                {{ guard.status }}
                            </span>
                        </div>

                        <!-- Avatar absolutely pinned, straddles banner/content boundary -->
                        <div class="absolute left-6" style="bottom: -48px;">
                            <img v-if="guard.photo" :src="'/storage/' + guard.photo"
                                class="h-24 w-24 rounded-2xl object-cover border-4 border-white shadow-lg" alt="Guard photo">
                            <div v-else class="h-24 w-24 rounded-2xl bg-indigo-100 border-4 border-white shadow-lg flex items-center justify-center">
                                <svg class="h-12 w-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Content: left-padded to clear the avatar -->
                    <div class="px-6 pb-6" style="padding-top: 60px;">
                        <div class="ml-28">
                            <h1 class="text-2xl font-black text-gray-900">{{ guard.name }}</h1>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2.5 py-0.5 rounded-full">
                                    🪪 {{ guard.employee_id }}
                                </span>
                            </div>
                        </div>

                        <!-- Shifts -->
                        <div class="flex flex-wrap gap-2 mt-5">
                            <span v-if="shifts.length === 0" class="text-sm text-gray-400 italic">No shift assigned</span>
                            <div v-for="shift in shifts" :key="shift"
                                :class="shiftConfig[shift]?.color || 'bg-gray-100 text-gray-800 border-gray-200'"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-sm font-bold">
                                <span>{{ shiftConfig[shift]?.icon || '⏰' }}</span>
                                <span>{{ shift }}</span>
                                <span class="text-xs font-medium opacity-70">{{ shiftConfig[shift]?.hours }}</span>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Personal Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <span class="text-lg">👤</span>
                            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Personal Information</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4">
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">Full Name</dt>
                                <dd class="text-sm font-semibold text-gray-900">{{ guard.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">IC / Passport Number</dt>
                                <dd class="text-sm font-semibold text-gray-900 font-mono">{{ guard.ic_number || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">Address</dt>
                                <dd class="text-sm font-semibold text-gray-900 whitespace-pre-line">{{ guard.address || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">Registered</dt>
                                <dd class="text-sm font-semibold text-gray-900">{{ formatDate(guard.created_at) }}</dd>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Employment -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <span class="text-lg">📋</span>
                            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Contact & Employment</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4">
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">Employee ID</dt>
                                <dd class="text-sm font-black text-indigo-700 font-mono">{{ guard.employee_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">Email Address</dt>
                                <dd class="text-sm font-semibold text-gray-900">
                                    <a :href="'mailto:' + guard.email" class="text-indigo-600 hover:underline">{{ guard.email || '—' }}</a>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">Phone Number</dt>
                                <dd class="text-sm font-semibold text-gray-900">
                                    <a :href="'tel:' + guard.phone" class="text-indigo-600 hover:underline">{{ guard.phone || '—' }}</a>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">Account Status</dt>
                                <dd>
                                    <span :class="guard.status === 'Active'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider inline-block">
                                        {{ guard.status }}
                                    </span>
                                </dd>
                            </div>
                        </div>
                    </div>

                    <!-- Shift Schedule -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 md:col-span-2">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <span class="text-lg">⏰</span>
                            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Assigned Shift Schedule</h3>
                        </div>
                        <div class="px-6 py-5">
                            <div v-if="shifts.length === 0" class="text-center py-8 text-gray-400 text-sm italic">
                                No shifts have been assigned yet.
                            </div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div v-for="shift in shifts" :key="shift"
                                    :class="shiftConfig[shift]?.color || 'bg-gray-50 border-gray-200'"
                                    class="rounded-xl border p-4 flex flex-col items-center text-center gap-2">
                                    <span class="text-3xl">{{ shiftConfig[shift]?.icon || '⏰' }}</span>
                                    <div class="font-black text-base">{{ shift }}</div>
                                    <div class="text-xs font-semibold opacity-75">{{ shiftConfig[shift]?.hours }}</div>
                                </div>

                                <!-- Unassigned shifts shown as greyed out -->
                                <div v-for="s in ['Morning','Afternoon','Night'].filter(x => !shifts.includes(x))" :key="s"
                                    class="rounded-xl border border-dashed border-gray-200 p-4 flex flex-col items-center text-center gap-2 opacity-40">
                                    <span class="text-3xl grayscale">{{ shiftConfig[s]?.icon }}</span>
                                    <div class="font-black text-base text-gray-400">{{ s }}</div>
                                    <div class="text-xs font-semibold text-gray-400">{{ shiftConfig[s]?.hours }}</div>
                                    <span class="text-[9px] uppercase tracking-widest text-gray-300 font-bold">Not Assigned</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
