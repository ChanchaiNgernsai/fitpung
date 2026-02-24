<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import MobileLayout from '@/Layouts/MobileLayout.vue';

const props = defineProps({
    gyms: Array,
});

const favoriteGymIds = ref([]);
const currentTab = ref('all'); // 'all' | 'favorites'

onMounted(() => {
    const saved = localStorage.getItem('favorite_gyms');
    if (saved) {
        favoriteGymIds.value = JSON.parse(saved);
    }
});

const filteredGyms = computed(() => {
    if (currentTab.value === 'all') return props.gyms || [];
    return (props.gyms || []).filter(gym => favoriteGymIds.value.includes(gym.id));
});

const toggleFavorite = (event, gymId) => {
    event.preventDefault();
    event.stopPropagation();
    
    const index = favoriteGymIds.value.indexOf(gymId);
    if (index === -1) {
        favoriteGymIds.value.push(gymId);
    } else {
        favoriteGymIds.value.splice(index, 1);
    }
    
    localStorage.setItem('favorite_gyms', JSON.stringify(favoriteGymIds.value));
};

const formatImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/images/')) return path;
    return `/storage/${path}`;
};
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Explore Gyms" />

        <!-- Header -->
        <header class="flex items-center justify-between p-6 pb-4 bg-[var(--nav-bg)] backdrop-blur-md sticky top-0 z-20 border-b border-[var(--border-color)] transition-colors">
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)]">FitPung Elite</span>
                <h1 class="text-2xl font-black tracking-tighter uppercase italic text-[var(--text-main)] leading-none mt-1 transition-colors">Explore Gyms</h1>
            </div>
            <button class="relative p-2 rounded-full bg-[var(--card-bg)] shadow-sm border border-[var(--border-color)] transition-colors">
                <span class="material-symbols-outlined text-[var(--text-main)] transition-colors">search</span>
            </button>
        </header>

        <!-- Gym Explorer -->
        <div class="px-6 py-6 flex-1">
            <div class="flex items-center justify-between mb-8">
                <div class="flex gap-4">
                    <button @click="currentTab = 'all'" 
                        :class="['text-[10px] font-black uppercase tracking-[0.2em] transition-all pb-1 border-b-2', currentTab === 'all' ? 'text-[var(--text-main)] border-[var(--theme-color)]' : 'text-[var(--text-muted)] border-transparent']">
                        All Gyms
                    </button>
                    <button @click="currentTab = 'favorites'" 
                        :class="['text-[10px] font-black uppercase tracking-[0.2em] transition-all pb-1 border-b-2', currentTab === 'favorites' ? 'text-[var(--text-main)] border-[var(--theme-color)]' : 'text-[var(--text-muted)] border-transparent']">
                        Favorites ({{ favoriteGymIds.length }})
                    </button>
                </div>
                <span class="bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest">Active</span>
            </div>

            <div class="space-y-4 transition-colors">
                <Link v-for="gym in filteredGyms" :key="gym.id" :href="route('gyms.white-map', gym.id)" class="bg-[var(--card-bg)] p-5 rounded-[24px] border border-[var(--border-color)] shadow-sm flex items-center gap-4 active:scale-95 transition-all">
                    <div class="size-16 rounded-2xl bg-[var(--page-bg)] overflow-hidden border border-[var(--border-color)] flex-shrink-0 transition-colors">
                        <img v-if="gym.image_path" :src="formatImageUrl(gym.image_path)" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center transition-colors">
                            <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">fitness_center</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 transition-colors">
                              <h4 class="text-base font-black uppercase italic leading-none truncate text-[var(--text-main)] transition-colors">{{ gym.name }}</h4>
                              <span class="material-symbols-outlined text-[var(--theme-color)] text-sm fill-icon transition-colors">verified</span>
                         </div>
                        <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase tracking-widest truncate transition-colors">{{ gym.location || 'Professional Workout Space' }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button @click="(e) => toggleFavorite(e, gym.id)" class="transition-all active:scale-125">
                            <span class="material-symbols-outlined text-[28px] transition-colors translate-y-px"
                                :class="favoriteGymIds.includes(gym.id) ? 'text-[var(--theme-color)] fill-icon' : 'text-[var(--text-muted)]'">
                                favorite
                            </span>
                        </button>
                        <div class="size-10 rounded-full border-2 border-[var(--theme-color)] p-0.5">
                            <div class="size-full rounded-full bg-[#f8f6f6] flex items-center justify-center text-[var(--theme-color)] flex-shrink-0">
                                <span class="material-symbols-outlined text-sm font-bold">arrow_forward_ios</span>
                            </div>
                        </div>
                    </div>
                </Link>
                
                <!-- Empty State -->
                <div v-if="filteredGyms.length === 0" class="py-20 flex flex-col items-center justify-center text-center transition-colors">
                    <div class="size-20 rounded-full bg-[var(--page-bg)] flex items-center justify-center mb-4 transition-colors">
                        <span class="material-symbols-outlined text-4xl text-[var(--text-muted)] transition-colors">fitness_center</span>
                    </div>
                    <h3 class="text-sm font-black uppercase italic text-[var(--text-muted)] transition-colors">No Favorites Yet</h3>
                    <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase tracking-widest mt-2 px-10 transition-colors">Start by adding some gyms to your favorites list.</p>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
 Josephson
