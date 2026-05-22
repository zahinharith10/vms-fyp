<template>
    <div v-if="links && links.length > 3" class="mt-6 flex justify-between items-center">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, p) in links" :key="p">
                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded bg-gray-50"
                    v-html="link.label" />
                <Link v-else
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500 transition duration-150 ease-in-out"
                    :class="{ 'bg-indigo-600 text-white hover:bg-indigo-700 border-indigo-600': link.active, 'bg-white text-gray-700': !link.active }" 
                    :href="link.url" 
                    v-html="link.label" 
                    preserve-scroll 
                    preserve-state />
            </template>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => []
    }
});
</script>
