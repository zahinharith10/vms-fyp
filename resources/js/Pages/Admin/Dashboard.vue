<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    LineElement, PointElement,
    LinearScale, CategoryScale,
    BarElement, ArcElement, Filler
} from 'chart.js';
import { Line, Doughnut, Bar } from 'vue-chartjs';

ChartJS.register(
    Title, Tooltip, Legend, LineElement, PointElement,
    LinearScale, CategoryScale, BarElement, ArcElement, Filler
);

const props = defineProps({
    stats:                Object,
    charts:               Object,
    activeOnSite:         Array,
    mostFrequentVisitor:  Object,
    filters:              Object,
});

const filterForm = ref({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const submitFilter = () => {
    router.get(route('admin.dashboard'), {
        start_date: filterForm.value.start_date,
        end_date: filterForm.value.end_date,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    filterForm.value.start_date = '';
    filterForm.value.end_date = '';
    router.get(route('admin.dashboard'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// ── Chart 1: Visit + Delivery Trends (Dual-Line) ────────────────
const trendData = computed(() => ({
    labels: props.charts.trends.labels,
    datasets: [
        {
            label: 'Visits',
            data: props.charts.trends.visits,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.08)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 5,
        },
        {
            label: 'Deliveries',
            data: props.charts.trends.deliveries,
            borderColor: '#f97316',
            backgroundColor: 'rgba(249, 115, 22, 0.08)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 5,
        },
    ]
}));
const trendOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: true, position: 'top', labels: { usePointStyle: true, pointStyle: 'circle', padding: 20 } },
        tooltip: { padding: 12, backgroundColor: '#1e293b', titleFont: { weight: 'bold' } }
    },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(156, 163, 175, 0.15)' } },
        x: { grid: { display: false } }
    }
};

// ── Chart 2: Visitor Distribution (Doughnut) ────────────────────
const distData = {
    labels: props.charts.distribution.labels,
    datasets: [{
        data: props.charts.distribution.data,
        backgroundColor: ['#6366f1', '#f97316'],
        borderWidth: 0,
        hoverOffset: 6
    }]
};
const distOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } }
    }
};

// ── Chart 3: Resident Types (Doughnut) ─────────────────────────
const residentData = {
    labels: props.charts.residents.labels,
    datasets: [{
        data: props.charts.residents.data,
        backgroundColor: ['#10b981', '#3b82f6'],
        borderWidth: 0,
        hoverOffset: 6
    }]
};

// ── Chart 4: Visitor Peak Times (Bar) ────────────────────
const peakTimeData = computed(() => ({
    labels: props.charts.visit_times.labels,
    datasets: [{
        label: 'Visits',
        data: props.charts.visit_times.data,
        backgroundColor: ['#38bdf8', '#f59e0b', '#6366f1'],
        borderRadius: 8,
    }]
}));
const peakTimeOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(156, 163, 175, 0.15)' }, ticks: { precision: 0 } },
        x: { grid: { display: false } }
    }
};

// ── Chart 5: Guard Shifts (Bar) ─────────────────────────────────
const shiftData = {
    labels: props.charts.shifts.labels,
    datasets: [{
        label: 'Guards',
        data: props.charts.shifts.data,
        backgroundColor: ['#6366f1', '#f59e0b', '#0ea5e9'],
        borderRadius: 10,
    }]
};
const shiftOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(156, 163, 175, 0.15)' }, ticks: { precision: 0 } },
        x: { grid: { display: false } }
    }
};

// ── Helpers ─────────────────────────────────────────────────────
const statusColor = (status) => {
    if (!status) return 'bg-slate-400';
    const s = status.toLowerCase();
    if (s.includes('checked in') || s.includes('approved')) return 'bg-emerald-500';
    if (s.includes('pending'))   return 'bg-amber-500';
    if (s.includes('rejected'))  return 'bg-red-500';
    if (s.includes('completed') || s.includes('checked out')) return 'bg-blue-500';
    return 'bg-slate-400';
};

const todayDelta = props.stats.today_visits - props.stats.yesterday_visits;
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-slate-800 leading-tight">Admin Main Dashboard</h2>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- ── Row 1: Primary KPI Cards ──────────────────────── -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">

                    <!-- Residents -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center text-xl">🏘️</div>
                            <Link :href="route('admin.residents.index')" class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest hover:underline">View →</Link>
                        </div>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_residents }}</p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Total Residents</p>
                        <div class="mt-2 flex gap-2">
                            <span class="text-[10px] bg-emerald-50 text-emerald-600 font-bold px-2 py-0.5 rounded-full">{{ stats.owner_count }} owners</span>
                            <span class="text-[10px] bg-blue-50 text-blue-600 font-bold px-2 py-0.5 rounded-full">{{ stats.family_count }} family</span>
                        </div>
                    </div>

                    <!-- House Units -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 bg-emerald-50 rounded-xl flex items-center justify-center text-xl">🏠</div>
                            <Link :href="route('admin.units.index')" class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest hover:underline">View →</Link>
                        </div>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_house_units }}</p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">House Units</p>
                    </div>

                    <!-- Guards -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 bg-blue-50 rounded-xl flex items-center justify-center text-xl">👮</div>
                            <Link :href="route('admin.guards.index')" class="text-[10px] font-bold text-blue-500 uppercase tracking-widest hover:underline">View →</Link>
                        </div>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_guards }}</p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Total Guards</p>
                        <div class="mt-2">
                            <span class="text-[10px] bg-blue-50 text-blue-600 font-bold px-2 py-0.5 rounded-full">{{ stats.active_guards }} active</span>
                        </div>
                    </div>

                    <!-- Visitors -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 bg-purple-50 rounded-xl flex items-center justify-center text-xl">👤</div>
                            <Link :href="route('admin.visitors.index')" class="text-[10px] font-bold text-purple-500 uppercase tracking-widest hover:underline">View →</Link>
                        </div>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_visitors }}</p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Registered Visitors</p>
                    </div>

                    <!-- Delivery Personnel -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 bg-orange-50 rounded-xl flex items-center justify-center text-xl">📦</div>
                            <Link :href="route('admin.delivery.personnel.index')" class="text-[10px] font-bold text-orange-500 uppercase tracking-widest hover:underline">View →</Link>
                        </div>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_delivery_personnel }}</p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Delivery Personnel</p>
                    </div>
                </div>

                <!-- ── Row 2: Live Status Banner ──────────────────────── -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <!-- Active Now -->
                    <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg shadow-indigo-200">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-white animate-pulse"></span>
                            <p class="text-xs font-black uppercase tracking-widest text-indigo-200">Active Right Now</p>
                        </div>
                        <p class="text-4xl font-black">{{ stats.active_now }}</p>
                        <div class="mt-2 flex gap-3 text-[10px] font-bold text-indigo-200 uppercase tracking-widest">
                            <span>{{ stats.active_visits }} visits</span>
                            <span>·</span>
                            <span>{{ stats.active_deliveries }} deliveries</span>
                        </div>
                    </div>

                    <!-- Today's Activity -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-3">Today's Activity</p>
                        <div class="flex items-end gap-4">
                            <div>
                                <p class="text-4xl font-black text-slate-900">{{ stats.today_visits }}</p>
                                <p class="text-xs font-semibold text-slate-400">visits</p>
                            </div>
                            <div class="text-sm font-bold pb-1" :class="todayDelta >= 0 ? 'text-emerald-500' : 'text-red-500'">
                                {{ todayDelta >= 0 ? '▲' : '▼' }} {{ Math.abs(todayDelta) }} vs yesterday
                            </div>
                        </div>
                        <div class="mt-2 text-[11px] font-semibold text-slate-500">
                            + {{ stats.today_deliveries }} deliveries today
                        </div>
                    </div>

                    <!-- Pending Approvals -->
                    <div class="rounded-2xl p-5 shadow-sm border"
                         :class="stats.total_pending > 0 ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-100'">
                        <p class="text-xs font-black uppercase tracking-widest mb-3"
                           :class="stats.total_pending > 0 ? 'text-amber-500' : 'text-slate-400'">
                            ⏳ Pending Approvals
                        </p>
                        <p class="text-4xl font-black"
                           :class="stats.total_pending > 0 ? 'text-amber-600' : 'text-slate-900'">
                            {{ stats.total_pending }}
                        </p>
                        <div class="mt-2 flex gap-3 text-[10px] font-bold uppercase tracking-widest"
                             :class="stats.total_pending > 0 ? 'text-amber-500' : 'text-slate-400'">
                            <span>{{ stats.pending_visits }} visits</span>
                            <span>·</span>
                            <span>{{ stats.pending_deliveries }} deliveries</span>
                        </div>
                    </div>
                </div>

                <!-- ── Row 2b: Top 5 Most Frequent Visitors ─────────────────── -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl p-6 text-white shadow-lg shadow-purple-200">
                        <div class="flex items-start justify-between mb-5">
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-purple-200 mb-2">📊 Top Visitors</p>
                                <h3 class="text-xl font-black">Most Frequent Visitors</h3>
                            </div>
                            <span class="text-3xl">⭐</span>
                        </div>

                        <div v-if="mostFrequentVisitor && mostFrequentVisitor.length > 0" class="space-y-3">
                            <div v-for="(visitor, index) in mostFrequentVisitor" :key="visitor.id" 
                                 class="flex items-center gap-3 p-3 bg-white/10 rounded-xl hover:bg-white/15 transition cursor-pointer">
                                <div class="flex-shrink-0 flex items-center justify-center">
                                    <span class="text-lg font-black bg-white/20 rounded-full w-8 h-8 flex items-center justify-center">
                                        {{ index + 1 }}
                                    </span>
                                </div>
                                <div class="h-12 w-12 rounded-xl bg-white/20 flex-shrink-0 overflow-hidden flex items-center justify-center text-lg font-black">
                                    <img v-if="visitor.photo" :src="'/storage/' + visitor.photo"
                                         class="h-full w-full object-cover" />
                                    <span v-else class="text-white">{{ visitor.name.charAt(0) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold truncate">{{ visitor.name }}</p>
                                    <p class="text-xs text-purple-200 font-semibold truncate">
                                        {{ visitor.email }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <p class="text-lg font-black">{{ visitor.visits_count }}</p>
                                    <p class="text-[10px] text-purple-200 font-semibold">visits</p>
                                </div>
                                <Link :href="route('admin.visitors.show', visitor.id)"
                                      class="flex-shrink-0 h-10 w-10 rounded-lg bg-white/20 hover:bg-white/30 transition flex items-center justify-center text-lg">
                                    →
                                </Link>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-purple-200">
                            <p class="font-semibold">No visitor data available</p>
                        </div>
                    </div>
                </div>

                <!-- ── Row 3: Charts + Activity Feed ─────────────────── -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Left: Charts -->
                    <div class="lg:col-span-8 space-y-6">

                        <!-- Visit + Delivery Trends -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5">
                                <div>
                                    <h3 class="text-base font-black text-slate-800">Visit & Delivery Trends</h3>
                                    <p class="text-xs text-slate-400 font-semibold mt-0.5">Showing visitor and delivery trends over the selected period</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1">
                                        <label for="start_date" class="text-[9px] font-black text-slate-400 uppercase tracking-wider">From</label>
                                        <input type="date" id="start_date" v-model="filterForm.start_date" @change="submitFilter" class="bg-transparent border-0 p-0 text-xs font-bold text-slate-700 focus:ring-0 w-28 cursor-pointer animate-none" />
                                    </div>
                                    <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1">
                                        <label for="end_date" class="text-[9px] font-black text-slate-400 uppercase tracking-wider">To</label>
                                        <input type="date" id="end_date" v-model="filterForm.end_date" @change="submitFilter" class="bg-transparent border-0 p-0 text-xs font-bold text-slate-700 focus:ring-0 w-28 cursor-pointer animate-none" />
                                    </div>
                                    <button v-if="filters.start_date || filters.end_date" @click="resetFilter" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                                        Reset
                                    </button>
                                    <span v-else class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-3 py-1.5 rounded-full uppercase tracking-widest">Default (Last 14 Days)</span>
                                </div>
                            </div>
                            <div class="h-64 relative">
                                <Line :data="trendData" :options="trendOptions" />
                            </div>
                        </div>

                        <!-- 2×2 Mini Charts -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                            <!-- Visitor Distribution -->
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                                <h3 class="text-sm font-black text-slate-800 mb-4">Visitor vs Delivery Mix</h3>
                                <div class="h-44 relative">
                                    <Doughnut :data="distData" :options="distOptions" />
                                </div>
                            </div>

                            <!-- Resident Types -->
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                                <h3 class="text-sm font-black text-slate-800 mb-4">Resident Types</h3>
                                <div class="h-44 relative">
                                    <Doughnut :data="residentData" :options="distOptions" />
                                </div>
                            </div>

                            <!-- Visitor Peak Times -->
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                                <h3 class="text-sm font-black text-slate-800 mb-4">Visitor Peak Arrival Times</h3>
                                <div class="h-44 relative">
                                    <Bar :data="peakTimeData" :options="peakTimeOptions" />
                                </div>
                            </div>

                            <!-- Guard Shifts -->
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                                <h3 class="text-sm font-black text-slate-800 mb-1">Guard Shift Coverage</h3>
                                <p class="text-[10px] text-slate-400 font-semibold mb-4">Guards assigned per shift</p>
                                <div class="h-44 relative">
                                    <Bar :data="shiftData" :options="shiftOptions" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Activity Feed -->
                    <div class="lg:col-span-4">
                        <div class="bg-slate-900 p-6 rounded-2xl shadow-2xl text-white sticky top-6">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-base font-black tracking-tight">Currently in Premise</h3>
                                <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            </div>

                            <div class="space-y-4 overflow-y-auto max-h-[520px] pr-1 custom-scrollbar">
                                <div v-for="log in activeOnSite" :key="log.type + log.id"
                                     class="flex items-center gap-3 group">
                                    <div class="h-10 w-10 rounded-xl bg-white/10 flex-shrink-0 overflow-hidden flex items-center justify-center text-sm font-black">
                                        <img v-if="log.photo" :src="'/storage/' + log.photo"
                                             class="h-full w-full object-cover" />
                                        <span v-else>{{ log.name.charAt(0) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start gap-1">
                                            <p class="text-sm font-bold truncate">{{ log.name }}</p>
                                            <span class="text-[8px] font-black px-2 py-0.5 rounded-full uppercase flex-shrink-0"
                                                  :class="statusColor(log.status)">
                                                {{ log.status }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-widest truncate">
                                            {{ log.type }}
                                            <span v-if="log.unit"> · {{ log.unit }}</span>
                                            · {{ log.time }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="!activeOnSite?.length"
                                     class="text-center text-slate-500 text-sm py-8">
                                    No visitors currently on site
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-t border-white/10 flex justify-between items-center">
                                <Link :href="route('admin.visit-logs.index')"
                                      class="text-xs font-black text-indigo-400 uppercase tracking-widest hover:text-white transition">
                                    All Visit Logs →
                                </Link>
                                <Link :href="route('admin.delivery.logs.index')"
                                      class="text-xs font-black text-orange-400 uppercase tracking-widest hover:text-white transition">
                                    Delivery Logs →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Row 4: Quick Navigation ────────────────────────── -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-slate-500 uppercase tracking-widest mb-4">Quick Navigation</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                        <Link :href="route('admin.residents.index')"
                              class="flex flex-col items-center gap-2 p-4 bg-slate-50 rounded-xl hover:bg-indigo-50 hover:text-indigo-700 transition group">
                            <span class="text-2xl">🏘️</span>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-indigo-700">Residents</span>
                        </Link>
                        <Link :href="route('admin.units.index')"
                              class="flex flex-col items-center gap-2 p-4 bg-slate-50 rounded-xl hover:bg-emerald-50 hover:text-emerald-700 transition group">
                            <span class="text-2xl">🏠</span>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-emerald-700">House Units</span>
                        </Link>
                        <Link :href="route('admin.guards.index')"
                              class="flex flex-col items-center gap-2 p-4 bg-slate-50 rounded-xl hover:bg-blue-50 hover:text-blue-700 transition group">
                            <span class="text-2xl">👮</span>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-blue-700">Guards</span>
                        </Link>
                        <Link :href="route('admin.visitors.index')"
                              class="flex flex-col items-center gap-2 p-4 bg-slate-50 rounded-xl hover:bg-purple-50 hover:text-purple-700 transition group">
                            <span class="text-2xl">👤</span>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-purple-700">Visitors</span>
                        </Link>
                        <Link :href="route('admin.delivery.personnel.index')"
                              class="flex flex-col items-center gap-2 p-4 bg-slate-50 rounded-xl hover:bg-orange-50 hover:text-orange-700 transition group">
                            <span class="text-2xl">📦</span>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-orange-700">Delivery</span>
                        </Link>
                        <Link :href="route('admin.visit-logs.index')"
                              class="flex flex-col items-center gap-2 p-4 bg-slate-50 rounded-xl hover:bg-rose-50 hover:text-rose-700 transition group">
                            <span class="text-2xl">📋</span>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-rose-700">Visit Logs</span>
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
</style>
