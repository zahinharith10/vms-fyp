<script setup>
import { ref, onMounted } from 'vue';

const isDark = ref(false);

onMounted(() => {
    // Initialize theme based on document class
    isDark.value = document.documentElement.classList.contains('dark');
});

const toggleTheme = () => {
    if (isDark.value) {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
        isDark.value = false;
    } else {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
        isDark.value = true;
    }
};
</script>

<template>
    <button 
        @click="toggleTheme" 
        class="relative inline-flex items-center justify-center p-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all focus:outline-none shadow-sm group overflow-hidden"
        title="Toggle Theme"
    >
        <!-- Ambient hover glow effect -->
        <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-indigo-500/10 to-purple-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>

        <!-- Sun Icon (shown in dark mode) -->
        <svg v-if="isDark" class="w-5 h-5 text-yellow-400 relative z-10 animate-[spin_8s_linear_infinite]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707-.707m12.728 0l-.707.707M6.343 6.343l-.707-.707m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L12 12" />
        </svg>

        <!-- Moon Icon (shown in light mode) -->
        <svg v-else class="w-5 h-5 text-indigo-600 relative z-10 transition-transform duration-300 group-hover:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>
</template>
