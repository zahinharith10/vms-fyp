<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminAuthenticatedLayout from '@/Layouts/AdminAuthenticatedLayout.vue';
import ResidentAuthenticatedLayout from '@/Layouts/ResidentAuthenticatedLayout.vue';
import GuardAuthenticatedLayout from '@/Layouts/GuardAuthenticatedLayout.vue';
import DeliveryAuthenticatedLayout from '@/Layouts/DeliveryAuthenticatedLayout.vue';
import VisitorAuthenticatedLayout from '@/Layouts/VisitorAuthenticatedLayout.vue';

const page = usePage();
// Determine which layout to use based on the user's role/guard
const user = page.props.auth?.user || page.props.auth?.admin || page.props.auth?.resident || page.props.auth?.guard || page.props.auth?.personnel || page.props.auth?.visitor;

const role = computed(() => page.props.auth?.role || 'guest');

const layout = computed(() => {
    switch (role.value) {
        case 'admin': return AdminAuthenticatedLayout;
        case 'resident': return ResidentAuthenticatedLayout;
        case 'guard': return GuardAuthenticatedLayout;
        case 'delivery': return DeliveryAuthenticatedLayout;
        case 'visitor': return VisitorAuthenticatedLayout;
        default: return 'div'; // Fallback
    }
});

const activeTab = ref(role.value === 'guest' || role.value === 'admin' ? 'resident' : role.value);

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // Safe fallbacks based on role
        if (role.value === 'resident' && window.route) {
            router.visit(window.route('resident.dashboard'));
        } else if (role.value === 'admin' && window.route) {
            router.visit(window.route('admin.dashboard'));
        } else if (role.value === 'guard' && window.route) {
            router.visit(window.route('guard.dashboard'));
        } else {
            router.visit('/');
        }
    }
};
</script>

<template>
    <Head title="User Manual" />

    <component :is="layout">
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">System User Manual</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-3xl border border-gray-100 dark:border-gray-800">
                    
                    <!-- Tabs - Only show if user is NOT visitor -->
                    <div v-if="role !== 'resident' && role !== 'visitor'" class="flex flex-wrap border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <button @click="activeTab = 'resident'" :class="['px-6 py-4 text-sm font-black uppercase tracking-widest transition-colors', activeTab === 'resident' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-white dark:bg-gray-900 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300']">For Residents</button>
                        <button @click="activeTab = 'guard'" :class="['px-6 py-4 text-sm font-black uppercase tracking-widest transition-colors', activeTab === 'guard' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-white dark:bg-gray-900 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300']">For Guards</button>
                        <button @click="activeTab = 'delivery'" :class="['px-6 py-4 text-sm font-black uppercase tracking-widest transition-colors', activeTab === 'delivery' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-white dark:bg-gray-900 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300']">For Deliveries</button>
                        <button @click="activeTab = 'visitor'" :class="['px-6 py-4 text-sm font-black uppercase tracking-widest transition-colors', activeTab === 'visitor' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-white dark:bg-gray-900 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300']">For Visitors</button>
                        <button v-if="role === 'admin'" @click="activeTab = 'admin'" :class="['px-6 py-4 text-sm font-black uppercase tracking-widest transition-colors', activeTab === 'admin' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-white dark:bg-gray-900 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300']">For Admins</button>
                    </div>

                    <!-- Content -->
                    <div class="p-8 prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200">
                        
                        <div v-show="activeTab === 'resident'" class="space-y-6">
                            <h3 class="text-2xl font-black text-indigo-700 dark:text-indigo-400">Resident Portal Guide</h3>
                            <p>Welcome to your Resident Dashboard! Here is how you can manage your visitors and deliveries efficiently:</p>
                            
                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">1. Pre-Registering a Visitor (VIP Pass)</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Navigate to <strong>My Visitors</strong> from the sidebar.</li>
                                <li>Click the <strong>Pre-Register Guest</strong> button.</li>
                                <li>Fill in their name, phone, email, and purpose.</li>
                                <li>Once submitted, click <strong>Share Pass</strong> on the history table to send them an instant QR code link via WhatsApp!</li>
                            </ul>

                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">2. Approving Walk-in Visitors</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>If a visitor arrives without a QR code, the guard will register them.</li>
                                <li>You will see a <strong>Pending Approval</strong> notification on your dashboard.</li>
                                <li>Go to the <strong>Pending</strong> tab to instantly Approve or Reject the request.</li>
                            </ul>

                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">3. Managing Deliveries</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>You can manually approve incoming riders in the <strong>Deliveries</strong> tab.</li>
                                <li><em>Pro Tip:</em> Go to your <strong>Profile</strong> and enable <strong>Auto-Approve Deliveries</strong> to skip manual requests for verified food and parcel riders!</li>
                            </ul>
                        </div>

                        <div v-show="activeTab === 'guard'" class="space-y-6">
                            <h3 class="text-2xl font-black text-indigo-700 dark:text-indigo-400">Guard Scanner & Registration Guide</h3>
                            <p>As a guard, your primary tool is the dashboard and scanner. Ensure smooth traffic flow using these steps:</p>
                            
                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">1. Scanning QR Codes</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Go to <strong>Scan QR</strong>.</li>
                                <li>Point the camera at the visitor's or rider's phone.</li>
                                <li>The system will automatically recognize if they are entering or exiting, and process the Check-In/Check-Out.</li>
                            </ul>

                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">2. Registering Walk-in Guests</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>If they do not have a QR, go to <strong>Register Visitor</strong> or <strong>Register Delivery</strong>.</li>
                                <li>Select the unit they wish to visit. The resident will be pinged for approval.</li>
                                <li>Instruct the guest to wait until the status turns <strong>Approved</strong>.</li>
                            </ul>
                            
                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">3. Monitoring Active Logs</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Use the <strong>Active Logs</strong> page to see who is currently inside the premises in real-time.</li>
                                <li>Ensure nobody overstays their permitted duration.</li>
                            </ul>
                        </div>

                        <div v-show="activeTab === 'delivery'" class="space-y-6">
                            <h3 class="text-2xl font-black text-indigo-700 dark:text-indigo-400">Delivery Personnel Guide</h3>
                            <p>For efficient drops at the residency, utilize the multi-stop routing feature.</p>
                            
                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">1. Single & Multi-Stop Deliveries</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>On your dashboard, switch between <strong>Single Unit</strong> and <strong>Multiple Units</strong>.</li>
                                <li>If you have 5 parcels for different houses, use Multi-Stop to ping all residents at once.</li>
                                <li>You will receive a unified QR Code for the gate.</li>
                            </ul>

                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">2. Fast-Track Entry</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Some residents have "Auto-Approve" turned on. If you request a drop to those units, your trip is instantly approved!</li>
                                <li>Show the active QR Code to the guard upon arrival.</li>
                            </ul>
                        </div>

                        <div v-show="activeTab === 'visitor'" class="space-y-6">
                            <h3 class="text-2xl font-black text-indigo-700 dark:text-indigo-400">Visitor Guide</h3>
                            
                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">1. Receiving a Guest Pass</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>If a resident pre-registers you, you will receive a link.</li>
                                <li>Click it to activate your Digital Guest Pass and generate your QR Code.</li>
                            </ul>

                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">2. Requesting a Visit</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Log in with your Email Address and OTP.</li>
                                <li>Go to your dashboard, select the Unit and purpose, and submit a request.</li>
                                <li>Once the resident approves, a QR code will be generated for gate access.</li>
                            </ul>
                        </div>

                        <div v-show="activeTab === 'admin'" class="space-y-6">
                            <h3 class="text-2xl font-black text-indigo-700 dark:text-indigo-400">Administrator Guide</h3>
                            
                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">1. User & Unit Management</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Use the sidebar to CRUD (Create, Read, Update, Delete) Guards, Residents, and House Units.</li>
                                <li>Ensure unit numbers match standard formatting for routing correctness.</li>
                            </ul>

                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 border-l-4 border-indigo-500 pl-3">2. Reports & Audits</h4>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Go to the <strong>Reports</strong> tab to extract detailed CSV logs for all visits, users, and activity.</li>
                                <li>These reports are essential for security audits and tracking historical data.</li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
