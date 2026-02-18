<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';

const props = defineProps({
    gyms: Array,
});
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Explore Gyms" />

        <!-- Header -->
        <header class="flex items-center justify-between p-6 pb-4 bg-white/80 backdrop-blur-md sticky top-0 z-20 border-b border-gray-100">
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)]">FitPung Elite</span>
                <h1 class="text-2xl font-black tracking-tighter uppercase italic text-gray-900 leading-none mt-1">Explore Gyms</h1>
            </div>
            <button class="relative p-2 rounded-full bg-white shadow-sm border border-gray-100">
                <span class="material-symbols-outlined text-gray-900">search</span>
            </button>
        </header>

        <!-- Gym Explorer -->
        <div class="px-6 py-6 flex-1">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Featured Workspaces</h3>
                <span class="bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest">Active</span>
            </div>

            <div class="space-y-4">
                <Link v-for="gym in gyms" :key="gym.id" :href="route('gyms.white-map', gym.id)" class="bg-white p-5 rounded-[24px] border border-gray-100 shadow-sm flex items-center gap-4 active:scale-95 transition-all">
                    <div class="size-16 rounded-2xl bg-gray-100 overflow-hidden border border-gray-50 flex-shrink-0">
                        <img v-if="gym.image_path" :src="`/storage/${gym.image_path}`" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-gray-300">fitness_center</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                             <h4 class="text-base font-black uppercase italic leading-none truncate text-gray-900">{{ gym.name }}</h4>
                             <span class="material-symbols-outlined text-[var(--theme-color)] text-sm fill-icon">verified</span>
                        </div>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest truncate">{{ gym.location || 'Professional Workout Space' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-full border-2 border-[var(--theme-color)] p-0.5">
                            <div class="size-full rounded-full bg-[#f8f6f6] flex items-center justify-center text-[var(--theme-color)] flex-shrink-0">
                                <span class="material-symbols-outlined text-sm">arrow_forward_ios</span>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </MobileLayout>
</template>
