<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    PointElement, 
    LinearScale, 
    CategoryScale, 
    BarElement,
    ArcElement,
    Filler
} from 'chart.js';
import { Line, Doughnut, Bar } from 'vue-chartjs';

ChartJS.register(
    Title, Tooltip, Legend, LineElement, PointElement, LinearScale, CategoryScale, BarElement, ArcElement, Filler
);

const props = defineProps({
    stats: Object,
    charts: Object,
    recentActivity: Array,
});

// Chart 1: Visit Trends (Line/Area)
const trendData = {
    labels: props.charts.trends.labels,
    datasets: [{
        label: 'Daily Visits',
        data: props.charts.trends.data,
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 6,
    }]
};

const trendOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            mode: 'index',
            intersect: false,
            padding: 12,
            backgroundColor: '#1e293b',
            titleFont: { weight: 'bold' }
        }
    },
    scales: {
        y: { beginAtZero: true, grid: { display: false } },
        x: { grid: { display: false } }
    }
};

// Chart 2: Visitor Distribution (Doughnut)
const distData = {
    labels: props.charts.distribution.labels,
    datasets: [{
        data: props.charts.distribution.data,
        backgroundColor: ['#6366f1', '#f97316'],
        borderWidth: 0,
        hoverOffset: 4
    }]
};

// Chart 3: Visit Purposes (Bar)
const purposeData = {
    labels: props.charts.purposes.labels,
    datasets: [{
        label: 'Visits',
        data: props.charts.purposes.data,
        backgroundColor: 'rgba(99, 102, 241, 0.8)',
        borderRadius: 8,
    }]
};

const purposeOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { beginAtZero: true, grid: { display: false } },
        y: { grid: { display: false } }
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-slate-800 leading-tight">System Command Center</h2>
        </template>

        <div class="py-10 bg-slate-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Top Metric Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center">
                        <div class="h-14 w-14 bg-indigo-50 rounded-2xl flex items-center justify-center mr-4">
                            <span class="text-2xl">🏘️</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Residents</p>
                            <p class="text-2xl font-black text-slate-900 leading-tight">{{ stats.total_residents }}</p>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center">
                        <div class="h-14 w-14 bg-orange-50 rounded-2xl flex items-center justify-center mr-4">
                            <span class="text-2xl">📦</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Visitors</p>
                            <p class="text-2xl font-black text-slate-900 leading-tight">{{ stats.total_visitors }}</p>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center">
                        <div class="h-14 w-14 bg-blue-50 rounded-2xl flex items-center justify-center mr-4">
                            <span class="text-2xl">👮</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Guards</p>
                            <p class="text-2xl font-black text-slate-900 leading-tight">{{ stats.total_guards }}</p>
                        </div>
                    </div>
                    <div class="bg-indigo-600 p-6 rounded-[2.5rem] shadow-xl shadow-indigo-100 flex items-center">
                        <div class="h-14 w-14 bg-white/20 rounded-2xl flex items-center justify-center mr-4">
                            <span class="text-2xl">🔦</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-indigo-100 uppercase tracking-widest leading-none mb-1">Active Now</p>
                            <p class="text-2xl font-black text-white leading-tight">{{ stats.active_visitors }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    <!-- Left: Charts -->
                    <div class="lg:col-span-8 space-y-10">
                        <!-- Visit Trends -->
                        <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100">
                            <div class="flex justify-between items-center mb-8">
                                <h3 class="text-xl font-black text-slate-800">Visit Trends <span class="text-indigo-600">.</span></h3>
                                <span class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">Last 14 Days</span>
                            </div>
                            <div class="h-80 relative">
                                <Line :data="trendData" :options="trendOptions" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <!-- Distribution -->
                            <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100">
                                <h3 class="text-lg font-black text-slate-800 mb-6">User Mix</h3>
                                <div class="h-48 relative">
                                    <Doughnut :data="distData" :options="{ responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle' } } } }" />
                                </div>
                            </div>
                            <!-- Purposes -->
                            <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100">
                                <h3 class="text-lg font-black text-slate-800 mb-6">Top Purposes</h3>
                                <div class="h-48 relative">
                                    <Bar :data="purposeData" :options="purposeOptions" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Activity Feed -->
                    <div class="lg:col-span-4 lg:sticky lg:top-10">
                        <div class="bg-slate-900 p-8 rounded-[3rem] shadow-2xl text-white h-full max-h-[calc(100vh-160px)] flex flex-col">
                            <div class="flex items-center justify-between mb-8">
                                <h3 class="text-xl font-black italic tracking-tighter">Live Activity</h3>
                                <span class="flex h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                            </div>

                            <div class="space-y-6 overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="log in recentActivity" :key="log.type + log.id" class="flex items-center group">
                                    <div class="h-12 w-12 rounded-xl bg-white/10 p-0.5 mr-4 flex-shrink-0">
                                        <img v-if="log.photo" :src="'/storage/' + log.photo" class="h-full w-full object-cover rounded-[10px]" />
                                        <div v-else class="h-full w-full flex items-center justify-center text-sm font-black">{{ log.name.charAt(0) }}</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start mb-0.5">
                                            <p class="font-black text-sm truncate pr-2">{{ log.name }}</p>
                                            <span class="text-[8px] font-black px-2 py-0.5 rounded-full uppercase" 
                                                :class="log.status === 'Checked In' ? 'bg-green-500' : 'bg-orange-500'">
                                                {{ log.status }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ log.type }} • {{ log.time }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 pt-6 border-t border-white/10">
                                <Link :href="route('admin.delivery.logs.index')" class="text-xs font-black text-indigo-400 uppercase tracking-widest hover:text-white transition">View All Logs →</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 2px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
</style>
