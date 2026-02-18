<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Performance" />

        <!-- Header -->
        <header class="flex items-center justify-between px-6 pt-8 pb-4">
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)]">FitPung Elite</span>
                <h1 class="text-3xl font-black tracking-tighter uppercase italic text-gray-900 leading-none mt-1">Performance</h1>
            </div>
            <div class="relative">
                <div class="size-10 rounded-full border-2 border-[var(--theme-color)] p-0.5">
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjuchVWDk_IRP1TbfrAkzG8C4dA-u_ZSX_bmaJ7iTLsz349d2YCZwMsRA1jv1NHNq-FTa1WuuTrctIi_d9WHJb2VI1NZrJ3p_BqZcczzKpP4SZPQj3B_XX6EDlPU5fbHMh9GznMXlc3-Koi2GaRlBBu-73j1pHp39bRwxLX-V_fo3bm3pe---4bpS8o-nSgL6mxkoqqAL8GatxFr8B0_Jqchl4PZb4VDP9b3_v-iSeR5UM_i9ZA9WxigaAtHyyyxzav-yqEqFoT0U" class="size-full rounded-full object-cover">
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="px-6">
            <!-- Time Filter -->
            <div class="mt-4 flex p-1 bg-gray-100  rounded-2xl">
                <button class="flex-1 py-2 text-[10px] font-black rounded-xl bg-white  shadow-sm text-gray-900  uppercase tracking-widest">Week</button>
                <button class="flex-1 py-2 text-[10px] font-black text-gray-500 uppercase tracking-widest">Month</button>
                <button class="flex-1 py-2 text-[10px] font-black text-gray-500 uppercase tracking-widest">Year</button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-3 mt-6">
                <div class="flex flex-col items-center justify-center p-4 bg-white  rounded-[24px] border border-gray-100  shadow-sm">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Days</span>
                    <span class="text-xl font-black text-[var(--theme-color)]">24</span>
                    <span class="text-[9px] font-black text-green-500 uppercase tracking-tighter mt-1">+12%</span>
                </div>
                <div class="flex flex-col items-center justify-center p-4 bg-white  rounded-[24px] border border-gray-100  shadow-sm">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Sets</span>
                    <span class="text-xl font-black text-gray-900">412</span>
                    <span class="text-[9px] font-black text-green-500 uppercase tracking-tighter mt-1">+5%</span>
                </div>
                <div class="flex flex-col items-center justify-center p-4 bg-white  rounded-[24px] border border-gray-100  shadow-sm">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Hours</span>
                    <span class="text-xl font-black text-gray-900">18.5</span>
                    <span class="text-[9px] font-black text-green-500 uppercase tracking-tighter mt-1">+8%</span>
                </div>
            </div>

            <!-- Chart Simulation -->
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Consistency View</h2>
                    <span class="text-[10px] font-bold text-[var(--theme-color)] uppercase">Avg 5.2/wk</span>
                </div>
                <div class="relative h-40 w-full bg-white  rounded-[24px] p-6 border border-gray-100  shadow-sm">
                    <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 300 100">
                        <path d="M0,80 Q30,70 60,85 T120,40 T180,60 T240,20 T300,50" fill="none" stroke="var(--theme-color)" stroke-width="4" stroke-linecap="round"></path>
                        <circle cx="60" cy="85" fill="var(--theme-color)" r="4" stroke="white" stroke-width="2"></circle>
                        <circle cx="120" cy="40" fill="var(--theme-color)" r="4" stroke="white" stroke-width="2"></circle>
                        <circle cx="240" cy="20" fill="var(--theme-color)" r="4" stroke="white" stroke-width="2"></circle>
                    </svg>
                    <div class="flex justify-between mt-3 text-[9px] font-black text-gray-400 uppercase tracking-widest">
                        <span>Mon</span><span>Wed</span><span>Fri</span><span>Sun</span>
                    </div>
                </div>
            </div>

            <!-- Volume Breakdown -->
            <div class="mt-8 pb-8">
                <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-4">Volume Breakdown</h2>
                <div class="bg-white  rounded-[24px] p-6 border border-gray-100  shadow-sm">
                    <div class="flex items-end justify-between h-32 gap-3">
                        <div class="flex flex-col items-center w-full gap-2">
                             <div class="w-full bg-gray-100  rounded-t-lg h-[40%] transition-all duration-1000"></div>
                             <span class="text-[8px] font-black text-gray-400 uppercase">Push</span>
                        </div>
                        <div class="flex flex-col items-center w-full gap-2">
                             <div class="w-full bg-[var(--theme-color)] rounded-t-lg h-[85%] transition-all duration-1000"></div>
                             <span class="text-[8px] font-black text-[var(--theme-color)] uppercase">Pull</span>
                        </div>
                        <div class="flex flex-col items-center w-full gap-2">
                             <div class="w-full bg-gray-100  rounded-t-lg h-[60%] transition-all duration-1000"></div>
                             <span class="text-[8px] font-black text-gray-400 uppercase">Legs</span>
                        </div>
                        <div class="flex flex-col items-center w-full gap-2">
                             <div class="w-full bg-gray-100  rounded-t-lg h-[30%] transition-all duration-1000"></div>
                             <span class="text-[8px] font-black text-gray-400 uppercase">Core</span>
                        </div>
                        <div class="flex flex-col items-center w-full gap-2">
                             <div class="w-full bg-[var(--theme-color)]/40 rounded-t-lg h-[50%] transition-all duration-1000"></div>
                             <span class="text-[8px] font-black text-gray-400 uppercase">Cardio</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
