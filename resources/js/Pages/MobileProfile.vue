<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Profile" />

        <!-- Header -->
        <header class="flex items-center justify-between p-6">
            <button @click="$window.history.back()" class="size-10 rounded-full bg-white  shadow-sm border border-gray-100  flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-900 font-bold">arrow_back</span>
            </button>
            <h1 class="text-xs font-black uppercase tracking-[0.3em] text-gray-400">Account Profile</h1>
            <Link :href="route('mobile.settings')" class="size-10 rounded-full bg-white  shadow-sm border border-gray-100  flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-900 font-bold">settings</span>
            </Link>
        </header>

        <!-- Profile Detail -->
        <div class="pb-8">
            <!-- User Intro -->
            <div class="px-6 py-4 flex flex-col items-center">
            <div class="relative mb-6">
                <div class="size-36 rounded-full border-4 border-[var(--theme-color)] p-1.5 shadow-2xl shadow-[var(--theme-color)]/20">
                    <div class="size-full rounded-full overflow-hidden bg-gray-100">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjuchVWDk_IRP1TbfrAkzG8C4dA-u_ZSX_bmaJ7iTLsz349d2YCZwMsRA1jv1NHNq-FTa1WuuTrctIi_d9WHJb2VI1NZrJ3p_BqZcczzKpP4SZPQj3B_XX6EDlPU5fbHMh9GznMXlc3-Koi2GaRlWBu-73j1pHp39bRwxLX-V_fo3bm3pe---4bpS8o-nSgL6mxkoqqAL8GatxFr8B0_Jqchl4PZb4VDP9b3_v-iSeR5UM_i9ZA9WxigaAtHyyyxzav-yqEqFoT0U" class="size-full object-cover">
                    </div>
                </div>
                <div class="absolute bottom-2 right-2 size-8 bg-[var(--theme-color)] rounded-full border-4 border-white  flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-sm fill-icon">verified</span>
                </div>
            </div>
                <div class="text-center mb-8">
                <h2 class="text-3xl font-black italic uppercase tracking-tighter text-gray-900 leading-none">Alex Rivera</h2>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)] mt-3">Elite Athlete • Level 42</p>
            </div>

            <button class="w-full py-5 bg-[var(--theme-color)] text-white font-black italic uppercase tracking-widest rounded-[24px] shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all mb-10">
                Edit Profile
            </button>
            </div>

            <!-- Quick Stats -->
            <section class="px-6 py-4">
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white  p-5 rounded-[24px] border border-gray-100  shadow-sm flex flex-col items-center text-center">
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Weight</span>
                        <span class="text-lg font-black text-gray-900 leading-none">85<span class="text-[10px] ml-0.5 text-gray-400">kg</span></span>
                    </div>
                    <div class="bg-white  p-5 rounded-[24px] border border-gray-100  shadow-sm flex flex-col items-center text-center">
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Height</span>
                        <span class="text-lg font-black text-gray-900 leading-none">182<span class="text-[10px] ml-0.5 text-gray-400">cm</span></span>
                    </div>
                    <div class="bg-white  p-5 rounded-[24px] border border-gray-100  shadow-sm flex flex-col items-center text-center">
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Goal</span>
                        <span class="text-[10px] font-black text-gray-900 uppercase leading-tight mt-1">Muscle Gain</span>
                    </div>
                </div>
            </section>

            <!-- Metrics List -->
            <div class="px-6 w-full space-y-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Performance</h3>
                    <span class="text-[10px] font-bold text-[var(--theme-color)] uppercase tracking-widest">History</span>
                </div>
                <div class="space-y-3">
                    <div class="bg-white  p-5 rounded-[24px] border border-gray-100  flex items-center justify-between shadow-sm transition-all hover:scale-[1.02]">
                        <div class="flex items-center gap-4">
                            <div class="size-12 rounded-2xl bg-[var(--theme-color)]/10 flex items-center justify-center text-[var(--theme-color)]">
                                <span class="material-symbols-outlined fill-icon">fitness_center</span>
                            </div>
                            <div class="ml-1 flex-1 text-left">
                                <h4 class="font-black uppercase text-sm text-gray-900 leading-none mb-1">Workouts</h4>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">128 Completed</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-[var(--theme-color)] leading-none">82%</p>
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Progress</p>
                        </div>
                    </div>
                    
                    <div class="bg-white  rounded-[28px] border border-gray-100  overflow-hidden shadow-sm mt-6">
                        <a href="#" class="flex items-center justify-between p-5 hover:bg-gray-50  border-b border-gray-100  transition-colors">
                            <div class="flex items-center gap-4">
                                <span class="material-symbols-outlined text-gray-600">workspace_premium</span>
                                <span class="font-black uppercase text-[10px] tracking-widest text-gray-600 ">Personal Bests</span>
                            </div>
                            <span class="material-symbols-outlined text-gray-500 font-bold text-sm">arrow_forward_ios</span>
                        </a>
                        <a href="#" class="flex items-center justify-between p-5 hover:bg-gray-50  transition-colors">
                            <div class="flex items-center gap-4">
                                <span class="material-symbols-outlined text-gray-400">logout</span>
                                <span class="font-black uppercase text-[10px] tracking-widest text-red-500">Sign Out</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
