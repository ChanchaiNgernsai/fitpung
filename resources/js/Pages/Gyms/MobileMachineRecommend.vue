<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';

const props = defineProps({
    gym: Object,
    equipments: Array
});

const isLoaded = ref(false);

const recommendations = computed(() => props.gym.recommendations || []);
const selectedRecIndex = ref(0);
const activeRecommendation = computed(() => recommendations.value[selectedRecIndex.value] || null);

// --- State Management ---
const expandedMachineName = ref(null);
const workoutSets = ref([]);

const toggleExpansion = (machine) => {
    if (expandedMachineName.value === machine.name) {
        expandedMachineName.value = null;
        return;
    }

    expandedMachineName.value = machine.name;
    
    // Pre-fill sets based on recommendation details
    if (machine.details) {
        const numSets = parseInt(machine.details.sets) || 1;
        workoutSets.value = Array.from({ length: numSets }, () => ({
            weight: machine.details.weight || 10,
            reps: machine.details.reps || 12,
            isCompleted: false
        }));
    } else {
        workoutSets.value = [{ weight: 10, reps: 12, isCompleted: false }];
    }
};

const addNewSet = () => {
    const lastSet = workoutSets.value[workoutSets.value.length - 1];
    workoutSets.value.push({
        weight: lastSet ? lastSet.weight : 10,
        reps: lastSet ? lastSet.reps : 12,
        isCompleted: false
    });
};

const removeSet = (index) => {
    if (workoutSets.value.length > 1) {
        workoutSets.value.splice(index, 1);
    }
};

const toggleSetCompletion = (index) => {
    workoutSets.value[index].isCompleted = !workoutSets.value[index].isCompleted;
};

const weightOptions = Array.from({ length: 80 }, (_, i) => (i + 1) * 2.5);

const recommendedMachines = computed(() => {
    if (!activeRecommendation.value) return [];
    // Get unique machines by name that are included in the recommendation
    const recommendedExercises = activeRecommendation.value.exercises;
    
    return recommendedExercises.map(exe => {
        const name = typeof exe === 'string' ? exe : exe.name;
        const machine = props.gym.items.find(item => item.name === name);
        return {
            ...machine,
            details: typeof exe === 'string' ? null : exe
        };
    }).filter(m => m.id); // Ensure we found a matching machine
});

const viewBox = computed(() => {
    const defaultBounds = '0 0 1000 800';
    if (!props.gym.room_config || !props.gym.room_config.points) return defaultBounds;
    
    const pointsStr = props.gym.room_config.points;
    const points = pointsStr.trim().split(/\s+/).map(p => { 
        const parts = p.split(',').map(n => parseFloat(n));
        return parts.length < 2 ? null : { x: parts[0], y: parts[1] }; 
    }).filter(p => p !== null);

    if (points.length === 0) return defaultBounds;
    
    let minX = Math.min(...points.map(p => p.x));
    let maxX = Math.max(...points.map(p => p.x));
    let minY = Math.min(...points.map(p => p.y));
    let maxY = Math.max(...points.map(p => p.y));

    if (props.gym.items && props.gym.items.length > 0) {
        props.gym.items.forEach(item => {
            const ix = parseFloat(item.x);
            const iy = parseFloat(item.y);
            const iw = parseFloat(item.width) || 100;
            const ih = parseFloat(item.height) || 100;
            if (!isNaN(ix) && !isNaN(iy)) {
                minX = Math.min(minX, ix - iw/2);
                maxX = Math.max(maxX, ix + iw/2);
                minY = Math.min(minY, iy - ih/2);
                maxY = Math.max(maxY, iy + ih/2);
            }
        });
    }

    const contentW = maxX - minX;
    const contentH = maxY - minY;
    
    // Optimized vertical ratio for phone screens (matching the 3/4 aspect ratio of container)
    const padX = Math.max(contentW * 0.15, 50);
    const padY = Math.max(contentH * 0.15, 50);
    
    const targetW = contentW + (padX * 2);
    const targetH = contentH + (padY * 2);
    
    // Ensure we maintain a good aspect ratio for the mobile container
    const containerAspect = 3/4;
    let finalW = targetW;
    let finalH = targetH;
    
    if (finalW / finalH > containerAspect) {
        // Too wide, increase height
        finalH = finalW / containerAspect;
    } else {
        // Too tall, increase width
        finalW = finalH * containerAspect;
    }

    // Center the content within the expanded box
    const centerX = minX + contentW / 2;
    const centerY = minY + contentH / 2;
    
    return `${centerX - finalW / 2} ${centerY - finalH / 2} ${finalW} ${finalH}`;
});

onMounted(() => {
    setTimeout(() => {
        isLoaded.value = true;
    }, 300);
});

const handleMachineClick = (machine) => {
    toggleExpansion({
        ...machine,
        details: activeRecommendation.value?.exercises.find(e => (typeof e === 'string' ? e : e.name) === machine.name)
    });
};

const isRecommended = (machine) => {
    if (!activeRecommendation.value) return false;
    // Check if the machine's name is in the recommended exercises list
    return activeRecommendation.value.exercises.some(e => (typeof e === 'string' ? e : e.name) === machine.name);
};

// SVG Interaction logic
const getMachineClass = (machine) => {
    const recommended = isRecommended(machine);
    return [
        'interactive-machine cursor-pointer transition-all duration-300',
        recommended ? 'opacity-100' : 'opacity-20',
    ];
};

</script>

<template>
    <MobileLayout>
        <Head :title="'Recommendations - ' + gym.name" />

        <!-- Header -->
        <header class="flex items-center justify-between p-6 pb-4 bg-white/80  backdrop-blur-md sticky top-0 z-20 border-b border-gray-100 ">
            <div class="flex items-center gap-4">
                <Link :href="route('mobile.maps')" class="size-10 rounded-full bg-white  shadow-sm border border-gray-100  flex items-center justify-center active:scale-90 transition-all">
                    <span class="material-symbols-outlined text-gray-900 font-bold">arrow_back</span>
                </Link>
                <div class="flex flex-col">
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)]">GUIDED PATH</span>
                    <h1 class="text-xl font-black tracking-tighter uppercase italic text-gray-900 leading-none mt-1">
                        {{ activeRecommendation?.title || 'Recommended Machines' }}
                    </h1>
                </div>
            </div>
            <button class="size-10 rounded-full bg-white  shadow-sm border border-gray-100  flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-900 fill-icon">smart_toy</span>
            </button>
        </header>

        <div class="p-6">
            <!-- Recommendation Legend -->
            <div class="bg-[var(--theme-color)]/5 border border-[var(--theme-color)]/10 rounded-3xl p-5 mb-8 flex items-center gap-4">
                <div class="size-12 rounded-2xl bg-[var(--theme-color)] flex items-center justify-center shadow-lg shadow-[var(--theme-color)]/20">
                    <span class="material-symbols-outlined text-white">magic_button</span>
                </div>
                <div>
                    <h4 class="text-sm font-black uppercase italic text-gray-900">{{ activeRecommendation?.badge || 'Smart Match' }}</h4>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider leading-relaxed">
                        {{ activeRecommendation?.subtitle || 'Machines specifically chosen for your workout goals.' }}
                    </p>
                </div>
            </div>

            <!-- Interactive Map Container -->
            <div class="relative bg-white  rounded-[40px] border border-gray-100  shadow-2xl shadow-gray-200/50  p-4 overflow-hidden aspect-[3/4] flex flex-col">
                <div class="flex items-center justify-between mb-4 px-2">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Gym Floor Plan</span>
                    <div class="flex gap-2">
                        <div class="size-2 rounded-full bg-green-500 animate-pulse"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-green-500">Live View</span>
                    </div>
                </div>

                <div class="flex-1 relative bg-gray-50  rounded-[32px] overflow-hidden border border-gray-50  italic">
                    <!-- SVG Map Implementation -->
                    <div class="w-full h-full p-4 flex items-center justify-center transform transition-all duration-700" :class="{ 'opacity-100 scale-100': isLoaded, 'opacity-0 scale-95': !isLoaded }">
                        <svg :viewBox="viewBox" class="w-full h-full">
                            <!-- Floor Plan -->
                            <polygon v-if="gym.room_config.points" :points="gym.room_config.points" 
                                class="fill-[#f8f6f6] stroke-[var(--theme-color)]/20" 
                                stroke-width="4" 
                                stroke-linejoin="round" />
                            
                            <!-- Drawing ALL machines -->
                             <g v-for="machine in gym.items" :key="machine.id" 
                                @click="handleMachineClick(machine)"
                                :transform="`translate(${machine.x}, ${machine.y}) rotate(${machine.rotation})`"
                                class="cursor-pointer group">
                                
                                <!-- Highlight for Recommended -->
                                <circle v-if="isRecommended(machine)" 
                                    cx="0" cy="0" :r="Math.max(machine.width, machine.height) * 0.45" 
                                    class="fill-[var(--theme-color)]/40 stroke-[var(--theme-color)] stroke-[2] animate-pulse" />
                                
                                <!-- Machine Image -->
                                <image 
                                    :href="machine.src" 
                                    :x="-machine.width/2" 
                                    :y="-machine.height/2" 
                                    :width="machine.width" 
                                    :height="machine.height"
                                    :class="isRecommended(machine) ? 'opacity-100 brightness-125' : 'opacity-30 grayscale'"
                                    class="transition-all duration-500"
                                />
 
                                <!-- label for recommended -->
                                <text v-if="isRecommended(machine)" 
                                    x="0" :y="machine.height/2 + 15" 
                                    text-anchor="middle" 
                                    class="text-[11px] font-black fill-[var(--theme-color)] stroke-[var(--theme-color)] stroke-[0.5] italic uppercase tracking-tighter"
                                    :transform="`rotate(${-machine.rotation})`"
                                >
                                    {{ machine.name }}
                                </text>
                             </g>
                        </svg>
                    </div>

                    <!-- Reset / Controls Overlay -->
                    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-white/90  backdrop-blur-md px-6 py-3 rounded-full border border-gray-100  shadow-xl flex items-center gap-4">
                        <button class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-gray-900">
                            <span class="material-symbols-outlined text-sm font-bold">restart_alt</span>
                            Reset
                        </button>
                        <div class="w-px h-4 bg-gray-200 "></div>
                        <button class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-[var(--theme-color)]">
                            <span class="material-symbols-outlined text-sm font-bold">zoom_in</span>
                            Auto Focus
                        </button>
                    </div>
                </div>
            </div>

            <!-- List of Recommended Exercises -->
            <div class="mt-8 space-y-4 pb-20">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 px-2">Exercises in this Plan</h3>
                
                <div v-for="(machine, idx) in recommendedMachines" :key="idx" class="space-y-3">
                    <!-- Machine Card -->
                    <div 
                        @click="toggleExpansion(machine)"
                        class="bg-white p-4 rounded-[28px] border transition-all shadow-sm cursor-pointer flex items-center gap-4"
                        :class="expandedMachineName === machine.name ? 'border-[var(--theme-color)] ring-4 ring-[var(--theme-color)]/5' : 'border-gray-100 active:scale-95'"
                    >
                        <div class="size-16 rounded-2xl bg-gray-50 flex items-center justify-center overflow-hidden border border-gray-100">
                            <img :src="machine.src" class="size-10 object-contain">
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-black uppercase italic text-gray-900 leading-none mb-1">{{ machine.name }}</h4>
                            <div v-if="machine.details" class="flex gap-2">
                                <!-- Sets Box -->
                                <div class="flex flex-col items-start">
                                    <span class="text-[7px] font-black text-gray-300 uppercase tracking-widest pl-1 mb-0.5">Sets</span>
                                    <div class="px-2.5 py-1 bg-white rounded-lg border border-gray-100 shadow-sm flex items-center justify-center min-w-[32px]">
                                        <span class="text-[9px] font-black text-gray-900">{{ machine.details.sets }}</span>
                                    </div>
                                </div>
                                <!-- Reps Box -->
                                <div class="flex flex-col items-start">
                                    <span class="text-[7px] font-black text-gray-300 uppercase tracking-widest pl-1 mb-0.5">Reps</span>
                                    <div class="px-2.5 py-1 bg-white rounded-lg border border-gray-100 shadow-sm flex items-center justify-center min-w-[32px]">
                                        <span class="text-[9px] font-black text-gray-900">{{ machine.details.reps }}</span>
                                    </div>
                                </div>
                                <!-- Weight Box -->
                                <div class="flex flex-col items-start">
                                    <span class="text-[7px] font-black text-gray-300 uppercase tracking-widest pl-1 mb-0.5">Weight</span>
                                    <div class="px-2.5 py-1 bg-white rounded-lg border border-gray-100 shadow-sm flex items-center justify-center min-w-[44px]">
                                        <span class="text-[9px] font-black text-gray-900">{{ machine.details.weight }}kg</span>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Perfect for {{ activeRecommendation?.title }}</p>
                        </div>
                        <button class="size-10 rounded-full border-2 border-[var(--theme-color)] flex items-center justify-center text-[var(--theme-color)] transition-transform duration-300"
                            :class="{ 'rotate-90 bg-[var(--theme-color)] text-white': expandedMachineName === machine.name }">
                            <span class="material-symbols-outlined text-xl">{{ expandedMachineName === machine.name ? 'keyboard_arrow_down' : 'play_arrow' }}</span>
                        </button>
                    </div>

                    <!-- Inline Details (Accordion) -->
                    <transition 
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="transform -translate-y-4 opacity-0 scale-95"
                        enter-to-class="transform translate-y-0 opacity-100 scale-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100 scale-100"
                        leave-to-class="transform -translate-y-4 opacity-0 scale-95"
                    >
                        <div v-if="expandedMachineName === machine.name" class="bg-gray-50/50 rounded-[32px] p-6 border border-gray-100 space-y-6 mx-2">
                             <!-- Info Pills -->
                             <div v-if="machine.details" class="flex gap-4">
                                <div class="flex-1 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Target Weight</p>
                                    <p class="text-xl font-black italic text-gray-900">{{ machine.details.weight }}<span class="text-xs ml-0.5 opacity-40">KG</span></p>
                                </div>
                                <div class="flex-1 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Target Reps</p>
                                    <p class="text-xl font-black italic text-gray-900">{{ machine.details.reps }}<span class="text-xs ml-0.5 opacity-40">REPS</span></p>
                                </div>
                            </div>

                            <!-- Set Tracking -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between px-2">
                                    <h3 class="text-xs font-black uppercase tracking-widest text-[var(--theme-color)] italic">Session Log</h3>
                                    <button @click.stop="addNewSet" class="text-[10px] font-black text-[var(--theme-color)] border border-[var(--theme-color)]/20 px-3 py-1 rounded-full bg-white shadow-sm">+ ADD SET</button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(set, index) in workoutSets" :key="index" 
                                        @click.stop="toggleSetCompletion(index)"
                                        class="grid grid-cols-[0.8fr_2.2fr_2fr_1fr] gap-3 items-center bg-white p-3 rounded-[24px] border border-gray-100 cursor-pointer hover:border-[var(--theme-color)]/30 transition-all shadow-sm">
                                        
                                        <!-- Number -->
                                        <div @click.stop="removeSet(index)" class="group/num relative flex items-center justify-center size-10 rounded-2xl hover:bg-red-50 transition-colors">
                                            <span class="text-lg font-black italic text-gray-300 group-hover/num:opacity-0 transition-opacity">{{ index + 1 }}</span>
                                            <span class="material-symbols-outlined absolute opacity-0 group-hover/num:opacity-100 text-red-500 text-lg transition-opacity">delete</span>
                                        </div>

                                        <!-- Weight -->
                                        <div class="relative" @click.stop>
                                            <select v-model="set.weight" class="w-full bg-gray-50 border-none rounded-xl text-xs font-black p-2.5 pr-8 appearance-none focus:ring-1 focus:ring-[var(--theme-color)] text-gray-900">
                                                <option v-for="w in weightOptions" :key="w" :value="w">{{ w }}kg</option>
                                            </select>
                                            <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-lg pointer-events-none">expand_more</span>
                                        </div>

                                        <!-- Reps -->
                                        <div class="relative" @click.stop>
                                            <input type="number" v-model="set.reps" class="w-full bg-gray-50 border-none rounded-xl text-xs font-black p-2.5 pr-6 text-center focus:ring-1 focus:ring-[var(--theme-color)] text-gray-900" />
                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[9px] font-black text-gray-400 uppercase pointer-events-none">R</span>
                                        </div>

                                        <!-- Done -->
                                        <div class="size-10 mx-auto rounded-full flex items-center justify-center transition-all border shadow-sm"
                                            :class="set.isCompleted ? 'bg-[var(--theme-color)] text-white border-[var(--theme-color)]' : 'bg-white border-gray-200 text-gray-100 pointer-events-none'">
                                            <span class="material-symbols-outlined text-lg" :class="{ 'fill-icon': set.isCompleted }">check</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Done Button -->
                            <button @click.stop="expandedMachineName = null" class="w-full bg-[var(--theme-color)] text-white py-4 rounded-[20px] font-black text-sm uppercase tracking-widest shadow-lg shadow-[var(--theme-color)]/20 active:scale-95 transition-all">
                                LOG PERFORMANCE
                            </button>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<style scoped>
.fill-icon {
    font-variation-settings: 'FILL' 1;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.2; }
    50% { transform: scale(1.1); opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
