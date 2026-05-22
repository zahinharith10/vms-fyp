<script setup>
import AuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    unit: Object,
});

const form = useForm({
    block: props.unit.block,
    floor: props.unit.floor,
    unit_number: props.unit.unit_number,
});

const submit = () => {
    form.put(route('admin.units.update', props.unit.id));
};
</script>

<template>
    <Head title="Edit House Unit" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit House Unit</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="max-w-md">
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Block</label>
                                <input v-model="form.block" type="number" min="0" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required />
                                <div v-if="form.errors.block" class="text-red-600 text-sm mt-1">{{ form.errors.block }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">Floor Level</label>
                                <input v-model="form.floor" type="number" min="0" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required placeholder="e.g. 10" />
                                <div v-if="form.errors.floor" class="text-red-600 text-sm mt-1">{{ form.errors.floor }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700">House Number</label>
                                <input v-model="form.unit_number" type="number" min="0" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required placeholder="e.g. 05" />
                                <div v-if="form.errors.unit_number" class="text-red-600 text-sm mt-1">{{ form.errors.unit_number }}</div>
                            </div>

                            <div class="flex items-center gap-4 mt-6">
                                <button type="submit" class="bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 px-4 py-2" :disabled="form.processing">
                                    Update Unit
                                </button>
                                <Link :href="route('admin.units.index')" class="text-gray-600 underline text-sm">Cancel</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
