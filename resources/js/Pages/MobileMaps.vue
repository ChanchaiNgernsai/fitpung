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
            <div class="flex items-center justify-between mb-8">
                <div class="flex gap-4">
                    <button @click="currentTab = 'all'" 
                        :class="['text-[10px] font-black uppercase tracking-[0.2em] transition-all pb-1 border-b-2', currentTab === 'all' ? 'text-gray-900 border-[var(--theme-color)]' : 'text-gray-400 border-transparent']">
                        All Gyms
                    </button>
                    <button @click="currentTab = 'favorites'" 
                        :class="['text-[10px] font-black uppercase tracking-[0.2em] transition-all pb-1 border-b-2', currentTab === 'favorites' ? 'text-gray-900 border-[var(--theme-color)]' : 'text-gray-400 border-transparent']">
                        Favorites ({{ favoriteGymIds.length }})
                    </button>
                </div>
                <span class="bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest">Active</span>
            </div>

            <div class="space-y-4">
                <Link v-for="gym in filteredGyms" :key="gym.id" :href="route('gyms.white-map', gym.id)" class="bg-white p-5 rounded-[24px] border border-gray-100 shadow-sm flex items-center gap-4 active:scale-95 transition-all">
                    <div class="size-16 rounded-2xl bg-gray-100 overflow-hidden border border-gray-50 flex-shrink-0">
                        <img v-if="gym.image_path" :src="formatImageUrl(gym.image_path)" class="w-full h-full object-cover">
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
                    <div class="flex items-center gap-4">
                        <button @click="(e) => toggleFavorite(e, gym.id)" class="transition-all active:scale-125">
                            <span class="material-symbols-outlined text-[28px] transition-colors translate-y-px"
                                :class="favoriteGymIds.includes(gym.id) ? 'text-[var(--theme-color)] fill-icon' : 'text-gray-200'">
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
                <div v-if="filteredGyms.length === 0" class="py-20 flex flex-col items-center justify-center text-center">
                    <div class="size-20 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-4xl text-gray-200">fitness_center</span>
                    </div>
                    <h3 class="text-sm font-black uppercase italic text-gray-400">No Favorites Yet</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-2 px-10">Start by adding some gyms to your favorites list.</p>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
 Josephson
