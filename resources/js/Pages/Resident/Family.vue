<script setup>
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    resident: Object,
    familyMembers: Array,
});
</script>

<template>
    <Head title="My Family" />

    <ResidentAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Family</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header Card -->
                <div class="p-8 bg-white shadow-xl sm:rounded-3xl border border-gray-100 dark:bg-gray-900 dark:border-gray-800 transition-colors duration-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center">
                            <div class="h-16 w-16 bg-emerald-100 dark:bg-emerald-950/55 rounded-2xl flex items-center justify-center text-3xl">
                                👨‍👩‍👧
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tighter">Household Members</h3>
                                <p class="text-sm font-bold text-gray-500 dark:text-gray-400">View all family members registered to your unit.</p>
                            </div>
                        </div>
                        <div class="flex-shrink-0" v-if="resident.house_unit">
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30">
                                🏠 Unit {{ resident.house_unit.formatted_unit }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Family Members Grid -->
                <div v-if="familyMembers && familyMembers.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div 
                        v-for="member in familyMembers" 
                        :key="member.id" 
                        class="p-6 bg-white shadow-lg sm:rounded-3xl border border-gray-100 dark:bg-gray-900 dark:border-gray-800 hover:shadow-xl hover:scale-[1.01] transition-all duration-200 flex flex-col justify-between"
                    >
                        <div>
                            <!-- User Avatar & Status Header -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 rounded-2xl flex items-center justify-center font-black text-lg shadow-inner">
                                        {{ member.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <h4 class="font-black text-gray-900 dark:text-white leading-tight">{{ member.name }}</h4>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-500 dark:text-indigo-400">Family Member</span>
                                    </div>
                                </div>
                                <div>
                                    <span 
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                                        :class="member.status === 'active' 
                                            ? 'bg-green-50 text-green-700 border border-green-100 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/30' 
                                            : 'bg-yellow-50 text-yellow-700 border border-yellow-100 dark:bg-yellow-950/30 dark:text-yellow-400 dark:border-yellow-900/30'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="member.status === 'active' ? 'bg-green-500' : 'bg-yellow-500'"></span>
                                        {{ member.status === 'active' ? 'Active' : 'Pending' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Details list -->
                            <div class="space-y-3.5 border-t border-gray-50 dark:border-gray-800 pt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">📞 Phone</span>
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ member.phone || '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">✉️ Email</span>
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate max-w-[200px]" :title="member.email">{{ member.email || '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">🪪 IC Number</span>
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ member.ic_number || '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-12 bg-white shadow-xl sm:rounded-3xl border border-gray-100 text-center dark:bg-gray-900 dark:border-gray-800 transition-colors duration-200">
                    <div class="text-6xl mb-4">👥</div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tighter">No Family Members Yet</h3>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 max-w-md mx-auto mt-2">
                        There are currently no family members registered under your unit. Once registered by the office management admin, they will appear here.
                    </p>
                </div>

                <!-- Information Notice Alert -->
                <div class="p-6 bg-indigo-50 border border-indigo-100 dark:bg-indigo-950/20 dark:border-indigo-900/30 rounded-3xl flex gap-4 transition-colors duration-200">
                    <div class="text-2xl flex-shrink-0">💡</div>
                    <div>
                        <h4 class="text-sm font-black text-indigo-900 dark:text-indigo-300 uppercase tracking-wide">Adding Family Members</h4>
                        <p class="text-xs text-indigo-700 dark:text-indigo-400 font-bold mt-1 leading-relaxed">
                            For security and validation, resident owners cannot add or register family members directly from this portal. All family members must be registered through the office management admin. Once the admin completes the registration, the member's profile will automatically display here.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </ResidentAuthenticatedLayout>
</template>
