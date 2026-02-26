<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { useI18n } from '@/language';

const { t } = useI18n();

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

        <header class="flex items-center justify-between p-6 pb-2 transition-colors">
            <div class="flex items-center gap-4">
                <div class="size-16 rounded-2xl bg-[var(--card-bg)] shadow-xl border border-[var(--border-color)] flex items-center justify-center p-1 transition-colors">
                    <img src="/images/gorila/GorillaLogo.png" class="size-12 object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-black uppercase italic tracking-tighter text-[var(--text-main)] leading-none transition-colors">FitPung</h1>
                    <p class="text-sm font-black uppercase tracking-tight text-[var(--theme-color)] mt-0.5 ml-0.5">{{ t('home.elite') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button class="relative p-2 rounded-full bg-[var(--card-bg)] shadow-sm border border-[var(--border-color)] transition-colors">
                    <span class="material-symbols-outlined text-[var(--text-main)] transition-colors">notifications</span>
                    <span class="absolute top-2.5 right-2.5 size-2 bg-[var(--theme-color)] rounded-full border-2 border-[var(--card-bg)] transition-colors"></span>
                </button>
                <div class="size-10 rounded-full bg-[var(--page-bg)] overflow-hidden border-2 border-[var(--theme-color)]/20 transition-colors">
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
                    <span class="inline-block px-3 py-1 rounded-full bg-[var(--theme-color)]/20 text-[var(--theme-color)] text-xs font-extrabold uppercase tracking-wider">{{ t('home.elite') }}</span>
                    <h1 class="text-white text-3xl font-black leading-[0.9] tracking-tighter italic uppercase whitespace-pre-wrap">{{ t('home.stay_focused') }}</h1>
                    <p class="text-gray-300 text-xs max-w-[60%] leading-relaxed">{{ t('home.hero_subtitle') }}</p>
                </div>
            </div>
        </div>

        <!-- Workout Stats Grid -->
        <div class="px-6 py-2">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-[var(--card-bg)] p-6 rounded-[24px] border border-[var(--border-color)] shadow-sm flex flex-col items-center text-center transition-colors">
                    <span class="material-symbols-outlined text-[var(--theme-color)] mb-2">fitness_center</span>
                    <span class="text-2xl font-black text-[var(--text-main)] leading-none transition-colors">{{ setsDone }}</span>
                    <span class="text-xs font-black text-[var(--text-muted)] uppercase tracking-wider mt-2 transition-colors">{{ t('home.sets_done') }}</span>
                </div>
                <!-- Workouts Completed Card (Replaces Time) -->
                <div class="bg-[var(--card-bg)] p-6 rounded-[24px] border border-[var(--border-color)] shadow-sm flex flex-col items-center text-center transition-colors">
                    <span class="material-symbols-outlined text-blue-500 mb-2">azm</span>
                    <span class="text-2xl font-black text-[var(--text-main)] leading-none transition-colors">{{ workoutsCompleted }}</span>
                    <span class="text-xs font-black text-[var(--text-muted)] uppercase tracking-wider mt-2 transition-colors">{{ t('home.workouts_done') }}</span>
                </div>
            </div>
        </div>

        <!-- Workout Feed (Instagram Style) -->
        <div class="px-0 py-6 transition-colors">
            <div class="px-6 flex items-center justify-between mb-6">
                <h3 class="text-lg font-black uppercase italic text-[var(--text-main)] tracking-tight transition-colors">{{ t('home.daily_feed') }}</h3>
                <button class="text-xs font-black text-[var(--theme-color)] uppercase tracking-wider bg-[var(--theme-color)]/5 px-3 py-1.5 rounded-full transition-colors">{{ t('home.for_you') }}</button>
            </div>

            <div class="px-6 space-y-8">
                <div v-for="rec in allRecommendations" :key="rec.id" class="bg-[var(--card-bg)] border border-[var(--border-color)] rounded-[10px] overflow-hidden shadow-sm transition-colors">
                    <!-- Post Header -->
                    <Link :href="route('gyms.white-map', rec.gymId)" class="px-4 py-3 flex items-center gap-3 active:opacity-70 transition-all">
                        <div class="size-10 rounded-full border border-[var(--border-color)] overflow-hidden bg-[var(--page-bg)] flex items-center justify-center transition-colors">
                            <img v-if="rec.gymImage" :src="rec.gymImage" class="w-full h-full object-cover">
                            <span v-else class="material-symbols-outlined text-[var(--text-muted)] text-lg transition-colors">fitness_center</span>
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-black text-[var(--text-main)] uppercase italic leading-none transition-colors">{{ rec.gymName }}</span>
                                <span class="material-symbols-outlined text-[10px] text-[var(--theme-color)] fill-icon transition-colors">verified</span>
                            </div>
                            <span class="text-[11px] text-[var(--text-muted)] font-bold uppercase tracking-tight mt-0.5 transition-colors">{{ rec.gymLocation }}</span>
                        </div>
                    </Link>

                    <!-- Post Image -->
                    <div class="px-4">
                        <div class="relative w-full aspect-square bg-gray-100 overflow-hidden rounded-[10px]">
                            <img :src="rec.image" class="w-full h-full object-cover">
                            <div class="absolute top-4 right-4 h-8 px-3 rounded-full bg-black/40 backdrop-blur-md border border-white/20 flex items-center justify-center text-white text-xs font-black uppercase tracking-wider">
                                <span class="block translate-y-[0.5px]">{{ rec.badge }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Interaction Bar -->
                    <div class="px-4 pt-4 pb-2 flex items-center gap-5 transition-colors">
                        <button @click="toggleLike(rec.id)" class="transition-all active:scale-125">
                            <span class="material-symbols-outlined text-2xl transition-colors"
                                :class="likedWorkoutIds.includes(rec.id) ? 'text-[var(--theme-color)] fill-icon' : 'text-[var(--text-main)]'">
                                fitness_center
                            </span>
                        </button>
                        <button class="transition-transform active:scale-125">
                            <span class="material-symbols-outlined text-2xl text-[var(--text-main)] leading-none transition-colors">chat_bubble</span>
                        </button>
                    </div>

                    <!-- Post Caption & Stats -->
                    <div class="px-4 space-y-3 transition-colors">
                        <div>
                            <p class="text-xs leading-relaxed transition-colors">
                                <span class="font-black uppercase italic mr-2 text-[var(--text-main)]">{{ rec.gymName }}</span>
                                <span class="text-[var(--text-main)] font-medium transition-colors">{{ rec.title }}</span>
                            </p>
                            <p v-if="rec.subtitle" class="text-[11px] text-[var(--text-muted)] mt-1 leading-relaxed line-clamp-2 transition-colors">
                                {{ rec.subtitle }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4 py-2 opacity-60 transition-colors">
                            <div class="flex items-center gap-1.5 transition-colors">
                                <span class="material-symbols-outlined text-sm text-[var(--text-muted)] transition-colors">schedule</span>
                                <span class="text-[9px] font-black text-[var(--text-main)] uppercase tracking-widest transition-colors">{{ rec.duration }} Min</span>
                            </div>
                            <div class="flex items-center gap-1.5 transition-colors">
                                <span class="material-symbols-outlined text-sm text-[var(--text-muted)] transition-colors">local_fire_department</span>
                                <span class="text-[9px] font-black text-[var(--text-main)] uppercase tracking-widest transition-colors">{{ rec.calories }} kcal</span>
                            </div>
                        </div>

                        <Link :href="route('gyms.recommend', rec.gymId)" class="w-full bg-[var(--theme-color)] text-white font-black uppercase tracking-wider py-2.5 rounded-[10px] flex items-center justify-center gap-2 shadow-md shadow-[var(--theme-color)]/10 active:scale-95 transition-all italic text-xs mt-4 mb-2">
                            <span class="material-symbols-outlined fill-icon text-sm">play_arrow</span>
                            {{ t('home.start_guided') }}
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
