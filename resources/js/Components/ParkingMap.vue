<script setup>
import { computed } from 'vue';

const props = defineProps({
    assignedLot: {
        type: Number,
        default: null,
    },
});

// Top inner edge (Lots 1-8)
// Bottom inner edge (Lots 9-15)
const lots = [];
for (let i = 0; i < 8; i++) {
    lots.push({ number: i + 1, x: 230 + i * 35, y: 242, w: 25, h: 25 });
}
for (let i = 0; i < 7; i++) {
    lots.push({ number: i + 9, x: 265 + i * 35, y: 473, w: 25, h: 25 });
}

const assignedLotData = computed(() =>
    props.assignedLot ? lots.find(l => l.number === props.assignedLot) : null
);

const navigationPath = computed(() => {
    const lot = assignedLotData.value;
    if (!lot) return '';
    const cx = lot.x + lot.w / 2;
    const cy = lot.y + lot.h / 2;
    
    if (lot.number <= 8) {
        return `M 540 100 L 540 220 L ${cx} 220 L ${cx} ${cy}`;
    } else {
        return `M 540 100 L 540 220 L 650 220 L 650 520 L ${cx} 520 L ${cx} ${cy}`;
    }
});
</script>

<template>
    <div class="w-full">
        <!-- Map Header -->
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Parking Navigation</p>
                <p v-if="assignedLot" class="text-sm font-black text-indigo-600 mt-0.5">
                    🅿️ Follow the route to Lot {{ assignedLot }}
                </p>
            </div>
            <div v-if="assignedLot" class="flex items-center gap-2 text-xs font-bold">
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-sm bg-indigo-500"></span>
                    <span class="text-gray-500">Your Lot</span>
                </span>
            </div>
        </div>

        <!-- SVG Map -->
        <div class="rounded-2xl overflow-hidden border border-gray-800 shadow-xl bg-slate-900">
            <!-- viewBox carefully calculated to fit the 35-degree rotated layout perfectly -->
            <svg viewBox="100 -150 800 800" class="w-full h-auto" xmlns="http://www.w3.org/2000/svg">
                
                <!-- Apply Rotation for Site Map Orientation (matches user's image) -->
                <g transform="rotate(35, 400, 300)">
                    
                    <!-- Main Road (Jalan 14/55a) -->
                    <rect x="-100" y="80" width="1000" height="70" fill="#334155" />
                    <text x="350" y="125" fill="#64748b" font-size="28" font-weight="bold" letter-spacing="4">JALAN 14/55A</text>

                    <!-- Entrance Road -->
                    <rect x="510" y="120" width="60" height="120" fill="#334155" />

                    <!-- Loop Road -->
                    <rect x="150" y="220" width="500" height="300" rx="60" fill="none" stroke="#334155" stroke-width="40" />
                    
                    <!-- Dashed center lines for roads -->
                    <rect x="150" y="220" width="500" height="300" rx="60" fill="none" stroke="#fbbf24" stroke-width="1.5" stroke-dasharray="14 8" opacity="0.4" />
                    <line x1="540" y1="120" x2="540" y2="220" stroke="#fbbf24" stroke-width="1.5" stroke-dasharray="14 8" opacity="0.4" />

                    <!-- Residential Blocks -->
                    <rect x="220" y="270" width="360" height="40" rx="4" fill="#1e293b" stroke="#475569" stroke-width="2" />
                    <text x="400" y="295" fill="#475569" font-size="14" font-weight="bold" text-anchor="middle">BLOCK A</text>

                    <rect x="220" y="350" width="360" height="40" rx="4" fill="#1e293b" stroke="#475569" stroke-width="2" />
                    <text x="400" y="375" fill="#475569" font-size="14" font-weight="bold" text-anchor="middle">BLOCK B</text>

                    <rect x="220" y="430" width="360" height="40" rx="4" fill="#1e293b" stroke="#475569" stroke-width="2" />
                    <text x="400" y="455" fill="#475569" font-size="14" font-weight="bold" text-anchor="middle">BLOCK C</text>

                    <!-- Swimming Pool -->
                    <circle cx="620" cy="540" r="25" fill="#0ea5e9" opacity="0.6" />
                    <circle cx="620" cy="540" r="20" fill="#38bdf8" opacity="0.8" />
                    <text x="620" y="580" fill="#64748b" font-size="12" font-weight="bold" text-anchor="middle">POOL</text>

                    <!-- Sri Ayu Marker -->
                    <circle cx="540" cy="190" r="14" fill="#ef4444" />
                    <circle cx="540" cy="190" r="6" fill="#ffffff" />
                    <text x="560" y="195" fill="#f87171" font-size="16" font-weight="bold">Entrance Guard</text>

                    <!-- Animated navigation path -->
                    <path v-if="assignedLotData"
                        :d="navigationPath"
                        fill="none"
                        stroke="#818cf8"
                        stroke-width="4"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-dasharray="10 6"
                        opacity="0.9"
                        class="nav-path-anim"
                    />

                    <!-- Parking Spots -->
                    <g v-for="lot in lots" :key="lot.number">
                        <!-- Spot box -->
                        <rect
                            :x="lot.x" :y="lot.y"
                            :width="lot.w" :height="lot.h"
                            :fill="lot.number === assignedLot ? '#4338ca' : '#1e293b'"
                            :stroke="lot.number === assignedLot ? '#818cf8' : '#475569'"
                            stroke-width="1.5"
                            rx="2"
                        />
                        <!-- Highlight glow -->
                        <rect
                            v-if="lot.number === assignedLot"
                            :x="lot.x - 2" :y="lot.y - 2"
                            :width="lot.w + 4" :height="lot.h + 4"
                            fill="none"
                            stroke="#818cf8"
                            stroke-width="2"
                            rx="4"
                            opacity="0.8"
                            class="glow-ring"
                        />
                        <text
                            :x="lot.x + lot.w / 2"
                            :y="lot.y + lot.h / 2 + 1"
                            text-anchor="middle"
                            dominant-baseline="middle"
                            :fill="lot.number === assignedLot ? '#ffffff' : '#94a3b8'"
                            :font-size="lot.number === assignedLot ? '12' : '10'"
                            font-weight="bold"
                        >
                            {{ lot.number }}
                        </text>
                    </g>
                    
                    <!-- Destination Pin -->
                    <g v-if="assignedLotData">
                        <circle
                            :cx="assignedLotData.x + assignedLotData.w / 2"
                            :cy="assignedLotData.y + assignedLotData.h / 2"
                            r="8"
                            fill="#818cf8"
                            class="pulse-dot"
                        />
                    </g>
                </g>
            </svg>
        </div>

        <!-- Step-by-step directions -->
        <div v-if="assignedLotData" class="mt-4 space-y-2">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Directions</p>
            <div class="flex items-start gap-3 text-sm">
                <span class="flex-shrink-0 h-6 w-6 rounded-full bg-indigo-600 text-white text-[10px] font-black flex items-center justify-center">1</span>
                <p class="text-gray-600 font-medium">Enter through the main guard house at Jalan 14/55A.</p>
            </div>
            <div class="flex items-start gap-3 text-sm">
                <span class="flex-shrink-0 h-6 w-6 rounded-full bg-indigo-600 text-white text-[10px] font-black flex items-center justify-center">2</span>
                <p class="text-gray-600 font-medium">
                    {{ assignedLotData.number <= 8 ? 'Turn RIGHT immediately after the entrance.' : 'Turn LEFT and follow the road around the blocks.' }}
                </p>
            </div>
            <div class="flex items-start gap-3 text-sm">
                <span class="flex-shrink-0 h-6 w-6 rounded-full bg-indigo-600 text-white text-[10px] font-black flex items-center justify-center">3</span>
                <p class="text-gray-600 font-medium">
                    Park in <span class="font-black text-indigo-600">Lot {{ assignedLot }}</span>
                    located at the {{ assignedLotData.number <= 8 ? 'top' : 'bottom' }} parking area.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.nav-path-anim {
    stroke-dashoffset: 0;
    animation: dash-flow 1.5s linear infinite;
}

@keyframes dash-flow {
    from { stroke-dashoffset: 52; }
    to   { stroke-dashoffset: 0; }
}

.pulse-dot {
    animation: pulse-ring 1.4s ease-in-out infinite;
}

@keyframes pulse-ring {
    0%, 100% { r: 8; opacity: 0.9; }
    50%       { r: 12; opacity: 0.5; }
}

.glow-ring {
    animation: glow-pulse 1.8s ease-in-out infinite;
}

@keyframes glow-pulse {
    0%, 100% { opacity: 0.4; }
    50%       { opacity: 0.9; }
}
</style>
