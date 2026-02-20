<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import MobileLayout from '@/Layouts/MobileLayout.vue';

const props = defineProps({
    featuredGyms: Array
});

const likedWorkoutIds = ref([]);
const setsDone = ref(0);
const workoutsCompleted = ref(0);

onMounted(() => {
    // Load Liked Workouts
    const savedLikes = localStorage.getItem('liked_workouts');
    if (savedLikes) {
        likedWorkoutIds.value = JSON.parse(savedLikes);
    }

    // Load Stats from History
    const savedSets = localStorage.getItem('fitpung_sets_done');
    if (savedSets) {
        setsDone.value = parseInt(savedSets, 10);
    }

    const savedHistory = localStorage.getItem('fitpung_workout_history');
    if (savedHistory) {
        const history = JSON.parse(savedHistory);
        workoutsCompleted.value = history.length;
    }
});

const toggleLike = (workoutId) => {
    const index = likedWorkoutIds.value.indexOf(workoutId);
    if (index === -1) {
        likedWorkoutIds.value.push(workoutId);
    } else {
        likedWorkoutIds.value.splice(index, 1);
    }
    localStorage.setItem('liked_workouts', JSON.stringify(likedWorkoutIds.value));
};

const formatImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/images/')) return path;
    return `/storage/${path}`;
};

const allRecommendations = computed(() => {
    const list = [];
    if (!props.featuredGyms) return list;
    
    props.featuredGyms.forEach(gym => {
        if (gym.recommendations && Array.isArray(gym.recommendations)) {
            gym.recommendations.forEach(rec => {
                const uniqueId = `${gym.id}-${rec.id || Math.random()}`;
                list.push({
                    ...rec,
                    id: uniqueId,
                    gymId: gym.id,
                    gymName: gym.name,
                    gymLocation: gym.location || 'Professional Space',
                    gymImage: formatImageUrl(gym.image_path) || '/images/gorila/GorillaLogo.png',
                    // Try recommendation image first, then gym main image, then fallback
                    image: formatImageUrl(rec.image) || formatImageUrl(gym.image_path) || '/images/gorila/DumbbellLateralRaise.png'
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
                    <span class="text-2xl font-black text-gray-900 leading-none">{{ setsDone }}</span>
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mt-2">Sets Done</span>
                </div>
                <!-- Workouts Completed Card (Replaces Time) -->
                <div class="bg-white p-6 rounded-[24px] border border-gray-50 shadow-sm flex flex-col items-center text-center">
                    <span class="material-symbols-outlined text-blue-500 mb-2">azm</span>
                    <span class="text-2xl font-black text-gray-900 leading-none">{{ workoutsCompleted }}</span>
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mt-2">Workouts Done</span>
                </div>
            </div>
        </div>

        <!-- Workout Feed (Instagram Style) -->
        <div class="px-0 py-6">
            <div class="px-6 flex items-center justify-between mb-6">
                <h3 class="text-lg font-black uppercase italic text-gray-900 tracking-tight">Daily Feed</h3>
                <button class="text-[10px] font-black text-[var(--theme-color)] uppercase tracking-widest bg-[var(--theme-color)]/5 px-3 py-1.5 rounded-full">For You</button>
            </div>

            <div class="px-6 space-y-8">
                <div v-for="rec in allRecommendations" :key="rec.id" class="bg-white border border-gray-100 rounded-[10px] overflow-hidden shadow-sm">
                    <!-- Post Header -->
                    <Link :href="route('gyms.white-map', rec.gymId)" class="px-4 py-3 flex items-center gap-3 active:opacity-70 transition-opacity">
                        <div class="size-10 rounded-full border border-gray-100 overflow-hidden bg-gray-50 flex items-center justify-center">
                            <img v-if="rec.gymImage" :src="rec.gymImage" class="w-full h-full object-cover">
                            <span v-else class="material-symbols-outlined text-gray-300 text-lg">fitness_center</span>
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-black text-gray-900 uppercase italic leading-none">{{ rec.gymName }}</span>
                                <span class="material-symbols-outlined text-[10px] text-[var(--theme-color)] fill-icon">verified</span>
                            </div>
                            <span class="text-[11px] text-gray-400 font-bold uppercase tracking-tight mt-0.5">{{ rec.gymLocation }}</span>
                        </div>
                    </Link>

                    <!-- Post Image -->
                    <div class="px-4">
                        <div class="relative w-full aspect-square bg-gray-100 overflow-hidden rounded-[10px]">
                            <img :src="rec.image" class="w-full h-full object-cover">
                            <div class="absolute top-4 right-4 h-8 px-3 rounded-full bg-black/40 backdrop-blur-md border border-white/20 flex items-center justify-center text-white text-[9px] font-black uppercase tracking-widest">
                                <span class="block translate-y-[0.5px]">{{ rec.badge }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Interaction Bar -->
                    <div class="px-4 pt-4 pb-2 flex items-center gap-5">
                        <button @click="toggleLike(rec.id)" class="transition-all active:scale-125">
                            <span class="material-symbols-outlined text-2xl transition-colors"
                                :class="likedWorkoutIds.includes(rec.id) ? 'text-[var(--theme-color)] fill-icon' : 'text-gray-900'">
                                fitness_center
                            </span>
                        </button>
                        <button class="transition-transform active:scale-125">
                            <span class="material-symbols-outlined text-2xl text-gray-900 leading-none">chat_bubble</span>
                        </button>
                    </div>

                    <!-- Post Caption & Stats -->
                    <div class="px-4 space-y-3">
                        <div>
                            <p class="text-xs leading-relaxed">
                                <span class="font-black uppercase italic mr-2">{{ rec.gymName }}</span>
                                <span class="text-gray-800 font-medium">{{ rec.title }}</span>
                            </p>
                            <p v-if="rec.subtitle" class="text-[11px] text-gray-500 mt-1 leading-relaxed line-clamp-2">
                                {{ rec.subtitle }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4 py-2 opacity-60">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm text-gray-400">schedule</span>
                                <span class="text-[9px] font-black text-gray-900 uppercase tracking-widest">{{ rec.duration }} Min</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm text-gray-400">local_fire_department</span>
                                <span class="text-[9px] font-black text-gray-900 uppercase tracking-widest">{{ rec.calories }} kcal</span>
                            </div>
                        </div>

                        <Link :href="route('gyms.recommend', rec.gymId)" class="w-full bg-[var(--theme-color)] text-white font-black uppercase tracking-widest py-2.5 rounded-[10px] flex items-center justify-center gap-2 shadow-md shadow-[var(--theme-color)]/10 active:scale-95 transition-all italic text-[11px] mt-4 mb-2">
                            <span class="material-symbols-outlined fill-icon text-sm">play_arrow</span>
                            Start Guided Workout
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
