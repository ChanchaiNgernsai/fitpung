<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import MobileLayout from '@/Layouts/MobileLayout.vue';

const props = defineProps({
    featuredGyms: Array
});

const allRecommendations = computed(() => {
    const list = [];
    if (!props.featuredGyms) return list;
    
    props.featuredGyms.forEach(gym => {
        if (gym.recommendations && Array.isArray(gym.recommendations)) {
            gym.recommendations.forEach(rec => {
                list.push({
                    ...rec,
                    gymId: gym.id,
                    gymName: gym.name,
                    image: gym.image_path ? `/storage/${gym.image_path}` : '/images/gorila/DumbbellLateralRaise.png'
                });
            });
        }
    });
    return list;
});
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Elite Mobile" />

        <!-- Header -->
        <header class="flex items-center justify-between p-6 pb-2">
            <div class="flex items-center gap-4">
                <div class="size-16 rounded-2xl bg-white shadow-xl border border-gray-100 flex items-center justify-center p-1">
                    <img src="/images/gorila/GorillaLogo.png" class="size-12 object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-black uppercase italic tracking-tighter text-gray-900 leading-none">FitPung</h1>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)] mt-1.5 ml-0.5">Elite Fitness</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button class="relative p-2 rounded-full bg-white shadow-sm border border-gray-100">
                    <span class="material-symbols-outlined text-gray-900">notifications</span>
                    <span class="absolute top-2.5 right-2.5 size-2 bg-[var(--theme-color)] rounded-full border-2 border-white"></span>
                </button>
                <div class="size-10 rounded-full bg-gray-200 overflow-hidden border-2 border-[var(--theme-color)]/20">
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAvptxpU7Ppa2JgT01czZwNufdNosJ_klrOU7YUBqtRumJxfPZwDuN7uT9Qls2CozEHWDlHPxZ5TpHrQ7PyGmmv_WKIoLoiUkshYos7oCbL3Tqpok8ULOd7tpyiikAog25n0DTsdWcodws94SJFGBo25tvPXAybv_E--CK3YDEJSiII5jOWQqB6XJN_RcRYFYTqV8BqdCTBJGCixQwyO3EMyFR8DvyhNacIrRKKMmV4fDSz_LVor40kj6i6e49ylZcRhNRNSfaNt3Y" alt="User" class="w-full h-full object-cover">
                </div>
            </div>
        </header>

        <!-- Hero Card -->
        <div class="px-6 py-4">
            <div class="relative overflow-hidden rounded-[40px] bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#0f172a] p-10 flex flex-col justify-end min-h-[240px] shadow-2xl">
                <div class="absolute top-0 right-0 w-full h-full pointer-events-none [mask-image:linear-gradient(to_left,black_20%,transparent_80%)]">
                     <img src="/images/gorila/ConcentrationCurl.png" class="w-full h-full object-contain object-right opacity-90 brightness-110 translate-x-6 scale-110">
                </div>
                <div class="relative z-10 space-y-3">
                    <span class="inline-block px-3 py-1 rounded-full bg-[var(--theme-color)]/20 text-[var(--theme-color)] text-[10px] font-extrabold uppercase tracking-widest">FitPung Elite</span>
                    <h1 class="text-white text-3xl font-black leading-[0.9] tracking-tighter italic uppercase">STAY<br />FOCUSED.</h1>
                    <p class="text-gray-400 text-[10px] max-w-[60%] leading-relaxed">Push your limits today with your personalized plan.</p>
                </div>
            </div>
        </div>

        <!-- Workout Stats Grid -->
        <div class="px-6 py-2">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white p-6 rounded-[24px] border border-gray-50 shadow-sm flex flex-col items-center text-center">
                    <span class="material-symbols-outlined text-[var(--theme-color)] mb-2">fitness_center</span>
                    <span class="text-2xl font-black text-gray-900 leading-none">12</span>
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mt-2">Sets Done</span>
                </div>
                <div class="bg-white p-6 rounded-[24px] border border-gray-50 shadow-sm flex flex-col items-center text-center">
                    <span class="material-symbols-outlined text-blue-500 mb-2">timer</span>
                    <span class="text-2xl font-black text-gray-900 leading-none">48<span class="text-sm ml-1 text-gray-400">min</span></span>
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mt-2">Total Time</span>
                </div>
            </div>
        </div>

        <!-- Workout Today -->
        <div class="px-6 py-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-black uppercase italic text-gray-900">Workout Recommendation</h3>
                <button class="text-[10px] font-bold text-[var(--theme-color)] uppercase tracking-widest">Edit Plan</button>
            </div>

            <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar -mx-6 px-6 pb-4">
                <div v-for="rec in allRecommendations" :key="rec.title + rec.gymId" 
                    class="min-w-[85%] snap-center bg-white rounded-[32px] overflow-hidden shadow-xl shadow-gray-200/40 border border-gray-50">
                    <div class="relative h-44 w-full overflow-hidden">
                        <img :src="rec.image" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-8">
                            <span class="bg-[var(--theme-color)] text-white text-[8px] font-black uppercase tracking-widest px-2 py-1 rounded-md w-fit mb-2">{{ rec.badge }}</span>
                            <h4 class="text-white text-2xl font-black uppercase italic tracking-tighter leading-tight">{{ rec.title }}</h4>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="flex items-center gap-4 mb-4">
                             <div class="flex items-center gap-2">
                                 <span class="material-symbols-outlined text-gray-400 text-base">schedule</span>
                                 <span class="text-[9px] font-black text-gray-700 uppercase tracking-widest">{{ rec.duration }} Min</span>
                             </div>
                             <div class="flex items-center gap-2">
                                 <span class="material-symbols-outlined text-gray-400 text-base">local_fire_department</span>
                                 <span class="text-[9px] font-black text-gray-700 uppercase tracking-widest">{{ rec.calories }} kcal</span>
                             </div>
                             <div class="flex items-center gap-2">
                                 <span class="material-symbols-outlined text-gray-400 text-base">trending_up</span>
                                 <span class="text-[9px] font-black text-gray-700 uppercase tracking-widest">{{ rec.badge }}</span>
                             </div>
                        </div>
                        <p v-if="rec.subtitle && rec.subtitle !== '--'" class="text-[11px] text-gray-400 font-medium leading-relaxed mb-8">
                            {{ rec.subtitle }}
                        </p>
                        <div v-else class="h-8"></div>
                        <Link :href="route('gyms.recommend', rec.gymId)" class="w-full bg-[var(--theme-color)] text-white font-black uppercase tracking-[0.2em] py-5 rounded-[24px] flex items-center justify-center gap-3 shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all italic text-sm">
                            <span class="material-symbols-outlined fill-icon text-lg">play_arrow</span>
                            View Guided Plan
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
