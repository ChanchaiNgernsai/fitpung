<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    gyms: Array
});

const showGymTable = ref(true);
const showInbox = ref(false);

const pendingGyms = computed(() => {
    return props.gyms.filter(gym => !gym.is_approved);
});

const approve = (id) => {
    router.post(route('admin.gym.approve', id));
};

const reject = (id) => {
    if (confirm("Are you sure you want to reject this gym? It will be hidden from public.")) {
         router.post(route('admin.gym.reject', id));
    }
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleString();
}
</script>

<template>
    <Head title="System Management" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    System Management (จัดการระบบ)
                </h2>
                <!-- Top Right Links/Stats -->
                <div class="flex gap-4 items-center">
                    <button @click="showGymTable = true" class="text-sm font-bold text-gray-600 hover:text-primary transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                        All Gyms
                    </button>
                    <!-- Inbox Badge -->
                    <div class="indicator cursor-pointer" @click="showInbox = !showInbox">
                        <span v-if="pendingGyms.length > 0" class="indicator-item badge badge-error badge-xs"></span>
                        <div class="text-sm font-bold text-gray-600 hover:text-primary transition-colors flex items-center gap-1">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                             <span v-if="pendingGyms.length > 0">Inbox ({{ pendingGyms.length }})</span>
                             <span v-else>Inbox</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Inbox Section (Collapsible) -->
                <transition name="slide-fade">
                    <div v-if="showInbox && pendingGyms.length > 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg border-l-4 border-warning relative">
                        <!-- Close Button -->
                         <button @click="showInbox = false" class="absolute top-2 right-2 btn btn-xs btn-ghost btn-circle">✕</button>
                        
                        <div class="p-6">
                            <h3 class="text-lg font-bold flex items-center gap-2 mb-4 text-warning-content">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                New Gym Requests
                            </h3>
                            <div class="grid gap-4">
                                <div v-for="gym in pendingGyms" :key="gym.id" class="alert shadow-sm border border-base-200 bg-base-50 flex-row items-center">
                                    <div class="flex-1">
                                        <div class="font-bold flex items-center gap-2">
                                            {{ gym.name }} 
                                            <span class="badge badge-sm badge-ghost">by {{ gym.user?.name }}</span>
                                        </div>
                                        <div class="text-xs opacity-60">Submitted: {{ formatDate(gym.created_at) }}</div>
                                    </div>
                                    <div class="flex-none gap-2">
                                        <Link :href="route('gyms.show', gym.id)" class="btn btn-sm btn-ghost">View Details</Link>
                                        <button @click="approve(gym.id)" class="btn btn-sm btn-success text-white">Approve</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Gym Management Table Section -->
                 <transition name="slide-fade">
                <div v-if="showGymTable" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 relative">
                     <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold flex items-center gap-2">
                            Gym Registry
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr class="bg-base-200">
                                    <th>Gym Name</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                    <th>Public</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="gym in gyms" :key="gym.id" class="hover">
                                    <td>
                                        <div class="font-bold text-primary">{{ gym.name }}</div>
                                        <div class="text-[10px] opacity-50">{{ formatDate(gym.created_at) }}</div>
                                    </td>
                                    <td>
                                        <div class="font-bold">{{ gym.user ? gym.user.name : 'Unknown' }}</div>
                                        <div class="text-xs opacity-50">{{ gym.user ? gym.user.email : '' }}</div>
                                    </td>
                                    <td>
                                        <span v-if="gym.is_approved" class="badge badge-success badge-sm text-white gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                            Approved
                                        </span>
                                        <span v-else class="badge badge-warning badge-sm text-white">Pending</span>
                                    </td>
                                    <td>
                                        <span v-if="gym.is_public" class="badge badge-info badge-sm text-white">Public</span>
                                        <span v-else class="badge badge-ghost badge-sm">Private</span>
                                    </td>
                                    <td class="flex gap-2">
                                        <Link :href="route('gyms.show', gym.id)" class="btn btn-xs btn-outline">View</Link>
                                        <button v-if="!gym.is_approved" @click="approve(gym.id)" class="btn btn-xs btn-success text-white">Approve</button>
                                        <button v-else @click="reject(gym.id)" class="btn btn-xs btn-error text-white">Reject</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                </transition>

                <!-- Empty State / Dashboard Home - REMOVED per user request -->

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}
</style>
