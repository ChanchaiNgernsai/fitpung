<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    gyms: { type: Array, default: () => [] }
});

const activeTab = ref('recommend'); // 'recommend' | 'history'
const isWorkoutSessionActive = ref(false);
const activeWorkout = ref(null);
const setsDone = ref(0);
const totalMinutes = ref(48);
const selectedWorkoutIds = ref([]);
const history = ref([]);
const viewMode = ref('list'); // 'list' | 'grid'

// Load stats and history from localStorage
onMounted(() => {
    const savedSets = localStorage.getItem('fitpung_sets_done');
    if (savedSets) setsDone.value = parseInt(savedSets);

    const savedHistory = localStorage.getItem('fitpung_workout_history');
    if (savedHistory) history.value = JSON.parse(savedHistory);
});

const logSet = () => {
    setsDone.value++;
    localStorage.setItem('fitpung_sets_done', setsDone.value.toString());
};

const formatImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/images/')) return path;
    return `/storage/${path}`;
};

const workoutRecommendations = computed(() => {
    // Flatten recommendations from all gyms and add gym name for context
    return props.gyms.flatMap(gym => (gym.recommendations || []).map(rec => ({
        ...rec,
        // Stabilize ID using title if missing
        id: `${gym.id}-${rec.id || rec.title.replace(/\s+/g, '-').toLowerCase()}`, 
        gymName: gym.name,
        // Use recommendation image, or fallback to gym image if missing
        image: formatImageUrl(rec.image) || formatImageUrl(gym.image_path)
    })));
});
const toggleSelection = (id) => {
    const index = selectedWorkoutIds.value.indexOf(id);
    if (index === -1) {
        selectedWorkoutIds.value.push(id);
    } else {
        selectedWorkoutIds.value.splice(index, 1);
    }
};

const startCombinedWorkout = () => {
    const selectedPlans = workoutRecommendations.value.filter(r => selectedWorkoutIds.value.includes(r.id));
    
    // Deduplication logic: Flatten exercises and merge by name
    const exerciseMap = new Map();
    
    selectedPlans.forEach(plan => {
        if (!plan.exercises) return;
        plan.exercises.forEach(ex => {
                if (!exerciseMap.has(ex.name)) {
                    // Try to get exercise image, fallback to equipment SVG if missing
                    let exerciseImage = formatImageUrl(ex.image);
                    
                    if (!exerciseImage) {
                        const nameLower = ex.name.toLowerCase();
                        if (nameLower.includes('dumbbell')) exerciseImage = '/images/equipment/Dumbbells.svg';
                        else if (nameLower.includes('treadmill')) exerciseImage = '/images/equipment/Treadmill.svg';
                        else if (nameLower.includes('bench press')) exerciseImage = '/images/equipment/BenchPress.svg';
                        else if (nameLower.includes('leg press')) exerciseImage = '/images/equipment/LegPress.svg';
                        else if (nameLower.includes('smith')) exerciseImage = '/images/equipment/SmithMachine.svg';
                        else if (nameLower.includes('elliptical')) exerciseImage = '/images/equipment/Elliptical.svg';
                    }

                    exerciseMap.set(ex.name, { 
                        ...ex, 
                        // Target sets from the seeder (summed if merged)
                        targetSets: parseInt(ex.sets) || 0,
                        // Use plain array for logs (reactive via parent ref)
                        workoutLogs: [],
                        image: exerciseImage,
                        isMerged: false
                    });
                } else {
                    const existing = exerciseMap.get(ex.name);
                    // Sum up sets if duplicate
                    existing.targetSets += (parseInt(ex.sets) || 0);
                    existing.isMerged = true;
                    
                    if (!existing.targetWeight && ex.targetWeight) {
                        existing.targetWeight = ex.targetWeight;
                    }

                    if (!existing.image && ex.image) {
                        existing.image = formatImageUrl(ex.image);
                    }
                }
        });
    });
    
    activeWorkout.value = {
        title: selectedPlans.map(p => p.title).join(' + '),
        exercises: Array.from(exerciseMap.values())
    };
    isWorkoutSessionActive.value = true;
};

const addSet = (exercise) => {
    exercise.workoutLogs.push({
        id: Date.now(),
        weight: exercise.targetWeight || '10kg',
        reps: exercise.reps || '12',
        completed: false
    });
};

const removeSet = (exercise, logIndex) => {
    exercise.workoutLogs.splice(logIndex, 1);
};

const toggleSet = (set) => {
    set.completed = !set.completed;
    if (set.completed) {
        logSet();
    } else {
        if (setsDone.value > 0) {
            setsDone.value--;
            localStorage.setItem('fitpung_sets_done', setsDone.value.toString());
        }
    }
};
const finishWorkout = () => {
    // Record to history
    const session = {
        id: Date.now(),
        date: new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }),
        title: activeWorkout.value.title,
        exercises: activeWorkout.value.exercises.map(ex => ({
            name: ex.name,
            image: ex.image,
            sets: ex.workoutLogs.filter(s => s.completed).map(s => ({
                weight: s.weight,
                reps: s.reps
            }))
        })).filter(ex => ex.sets.length > 0),
        sets: activeWorkout.value.exercises.reduce((sum, ex) => sum + ex.workoutLogs.filter(s => s.completed).length, 0)
    };

    history.value.unshift(session);
    localStorage.setItem('fitpung_workout_history', JSON.stringify(history.value));

    isWorkoutSessionActive.value = false;
    activeWorkout.value = null;
    selectedWorkoutIds.value = [];
};
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Workouts" />

        <!-- Workout Session View (Guided Plan) -->
        <div v-if="isWorkoutSessionActive && activeWorkout" class="min-h-full bg-white">
            <header class="p-6 pb-2 flex items-center justify-between sticky top-0 bg-white/80 backdrop-blur-md z-30">
                <div>
                    <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-[#00a18c] mb-1">Guided Plan</h2>
                    <h3 class="text-xl font-black uppercase italic text-gray-900 leading-tight">{{ activeWorkout.title }}</h3>
                </div>
                <button @click="isWorkoutSessionActive = false" class="size-10 rounded-full bg-gray-50 flex items-center justify-center">
                    <span class="material-symbols-outlined text-gray-400">close</span>
                </button>
            </header>

            <div class="px-6 py-4 space-y-8">
                <div v-for="exercise in activeWorkout.exercises" :key="exercise.name" class="space-y-6">
                    <!-- Premium Exercise Header -->
                    <div class="flex gap-5 items-center">
                        <div class="size-24 rounded-[32px] bg-gray-50 overflow-hidden border border-gray-100 flex-shrink-0 flex items-center justify-center p-2">
                            <img v-if="exercise.image" :src="exercise.image" class="w-full h-full object-contain">
                            <div v-else class="flex flex-col items-center justify-center opacity-20">
                                <span class="material-symbols-outlined text-4xl">fitness_center</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-[28px] font-black uppercase italic text-gray-900 leading-none tracking-tighter">{{ exercise.name }}</h4>
                                <span v-if="exercise.isMerged" class="px-2 py-0.5 rounded-md bg-[#00a18c]/10 text-[#00a18c] text-[8px] font-black uppercase italic tracking-wider">Merged</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-base font-black text-[#00a18c] uppercase italic leading-none">
                                    {{ exercise.targetWeight || (exercise.workoutLogs.length > 0 ? exercise.workoutLogs[0].weight : '0KG') }}
                                </span>
                                <span class="text-base font-black text-gray-400 uppercase italic tracking-tight leading-none">
                                    {{ exercise.workoutLogs.filter(s => s.completed).length }} / {{ exercise.targetSets }} Sets × {{ exercise.reps }} Reps
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-6 mb-4">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Workout Log</h4>
                        <button @click="addSet(exercise)" class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-[var(--theme-color)]/20 text-[var(--theme-color)] text-[9px] font-black uppercase tracking-widest active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-xs">add</span>
                            Add Set
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(set, setIdx) in exercise.workoutLogs" :key="set.id" 
                            class="flex items-end gap-2 px-2 py-4 transition-all"
                            :class="{ 'opacity-50': set.completed }">
                            
                            <!-- Delete Button -->
                            <button @click="removeSet(exercise, setIdx)" class="size-10 rounded-full flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors mb-0.5">
                                <span class="material-symbols-outlined text-lg">close</span>
                            </button>

                            <!-- Weight Input Group -->
                            <div class="flex-1 space-y-1.5 flex flex-col">
                                <span class="text-[8px] font-black text-gray-300 uppercase tracking-wider pl-1 whitespace-nowrap">Weight (KG)</span>
                                <div class="bg-white rounded-[14px] border border-gray-200 px-3 py-2.5 flex items-center">
                                    <input v-model="set.weight" class="w-full text-base font-black text-gray-900 border-none p-0 focus:ring-0 uppercase bg-transparent" placeholder="0">
                                </div>
                            </div>

                            <!-- Sets Info Group -->
                            <div class="w-16 space-y-1.5 flex flex-col items-center">
                                <span class="text-[8px] font-black text-gray-300 uppercase tracking-wider">Sets</span>
                                <div class="w-full bg-white rounded-[14px] border border-gray-200 py-2.5 flex items-center justify-center">
                                    <span class="text-base font-black text-gray-900">{{ setIdx + 1 }}</span>
                                </div>
                            </div>

                            <!-- Reps Input Group -->
                            <div class="flex-1 space-y-1.5 flex flex-col">
                                <span class="text-[8px] font-black text-gray-300 uppercase tracking-wider pl-1">Reps</span>
                                <div class="bg-white rounded-[14px] border border-gray-200 px-3 py-2.5 flex items-center">
                                    <input v-model="set.reps" class="w-full text-base font-black text-gray-900 border-none p-0 focus:ring-0 text-center bg-transparent" placeholder="0">
                                </div>
                            </div>

                            <!-- Done Button -->
                            <button @click="toggleSet(set)" 
                                class="size-11 rounded-full flex items-center justify-center transition-all border shadow-sm active:scale-95 mb-0.5"
                                :class="set.completed ? 'bg-[var(--theme-color)] border-[var(--theme-color)] text-white' : 'bg-white border-gray-100 text-gray-200'">
                                <span class="material-symbols-outlined text-xl font-bold">{{ set.completed ? 'check_circle' : 'check' }}</span>
                            </button>
                        </div>
                        
                        <!-- Empty Sets State -->
                        <div v-if="exercise.workoutLogs.length === 0" class="py-8 border-2 border-dashed border-gray-100 rounded-[32px] flex flex-col items-center justify-center opacity-40">
                             <span class="material-symbols-outlined text-3xl mb-2">add_task</span>
                             <p class="text-[9px] font-black uppercase tracking-widest">No sets added yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 mt-4">
                <button @click="finishWorkout" class="w-full py-5 rounded-[24px] bg-gray-900 text-white font-black uppercase tracking-[0.2em] shadow-xl active:scale-95 transition-all">
                    Finish & Log Session
                </button>
            </div>
        </div>

        <!-- Home View -->
        <div v-else class="bg-[#f8f9fa] min-h-screen pb-32">
            <!-- Header Tabs -->
            <header class="p-6 pb-2">
                <div class="flex items-center gap-4 mb-6">
                    <div class="size-16 rounded-2xl bg-white shadow-xl border border-gray-100 flex items-center justify-center p-1">
                        <img src="/images/gorila/GorillaLogo.png" class="size-12 object-contain">
                    </div>
                    <div>
                        <h1 class="text-2xl font-black uppercase italic tracking-tighter text-gray-900 leading-none mt-1">Workouts</h1>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-4 border-b border-gray-100">
                    <button @click="activeTab = 'recommend'" 
                        :class="['pb-4 text-[10px] font-black uppercase tracking-widest transition-all', activeTab === 'recommend' ? 'text-gray-900 border-b-2 border-gray-900' : 'text-gray-400']">
                        Recommended
                    </button>
                    <button @click="activeTab = 'history'" 
                        :class="['pb-4 text-[10px] font-black uppercase tracking-widest transition-all', activeTab === 'history' ? 'text-gray-900 border-b-2 border-gray-900' : 'text-gray-400']">
                        History
                    </button>
                </div>
            </header>

            <!-- Recommendation View -->
            <div v-if="activeTab === 'recommend'">
                <!-- Top Stats -->
                <div class="px-6 pt-4 pb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-50 flex flex-col items-center text-center">
                            <div class="flex items-center justify-center mb-4">
                                <span class="material-symbols-outlined text-[#00a18c] text-3xl font-bold">fitness_center</span>
                            </div>
                            <span class="text-4xl font-black text-gray-900 leading-none">{{ setsDone }}</span>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mt-3">Sets Done</span>
                        </div>
                        <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-100 flex flex-col items-center text-center">
                            <div class="flex items-center justify-center mb-4">
                                <span class="material-symbols-outlined text-[#3b82f6] text-3xl font-bold">schedule</span>
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-black text-gray-900 leading-none">{{ totalMinutes }}</span>
                                <span class="text-sm font-black text-gray-400 uppercase">min</span>
                            </div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mt-3">Total Time</span>
                        </div>
                    </div>
                </div>

                <div class="py-6">
                    <div class="px-8 flex items-center justify-between mb-6">
                        <div class="flex flex-col">
                            <h3 class="text-xl font-black uppercase italic text-gray-900 tracking-tight">Today's Picks</h3>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">Select 1 or more</p>
                        </div>
                        
                        <!-- View Toggle -->
                        <div class="flex bg-gray-100 p-1 rounded-xl border border-gray-200">
                            <button @click="viewMode = 'list'" 
                                :class="['size-8 rounded-lg flex items-center justify-center transition-all', viewMode === 'list' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400']">
                                <span class="material-symbols-outlined text-lg">format_list_bulleted</span>
                            </button>
                            <button @click="viewMode = 'grid'" 
                                :class="['size-8 rounded-lg flex items-center justify-center transition-all', viewMode === 'grid' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400']">
                                <span class="material-symbols-outlined text-lg">view_carousel</span>
                            </button>
                        </div>
                    </div>

                    <!-- List View (Default) -->
                    <div v-if="viewMode === 'list'" class="px-6 space-y-4 pb-8">
                        <div v-for="workout in workoutRecommendations" :key="workout.id" 
                            @click="toggleSelection(workout.id)"
                            class="bg-white p-4 rounded-[32px] border-2 transition-all duration-300 flex items-center gap-4 group"
                            :class="selectedWorkoutIds.includes(workout.id) ? 'border-[#00a18c] bg-[#00a18c]/5 shadow-lg shadow-[#00a18c]/10' : 'border-white shadow-sm shadow-gray-200/50'">
                            
                            <div class="size-20 rounded-2xl overflow-hidden relative border border-gray-100 flex-shrink-0">
                                <img v-if="workout.image" :src="workout.image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-50 opacity-10">
                                    <span class="material-symbols-outlined">fitness_center</span>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <span class="text-[8px] font-black text-[#00a18c] uppercase tracking-widest mb-1 block">{{ workout.gymName }}</span>
                                <h4 class="text-base font-black uppercase italic text-gray-900 truncate leading-tight">{{ workout.title }}</h4>
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">{{ workout.duration }} • {{ workout.calories }}</p>
                            </div>

                            <div class="size-8 rounded-full flex items-center justify-center border-2 transition-all"
                                :class="selectedWorkoutIds.includes(workout.id) ? 'bg-[#00a18c] border-[#00a18c]' : 'border-gray-100 text-gray-200'">
                                <span v-if="selectedWorkoutIds.includes(workout.id)" class="material-symbols-outlined text-white text-base">check</span>
                            </div>
                        </div>
                    </div>

                    <!-- Grid View (Carousel) -->
                    <div v-else class="flex gap-6 overflow-x-auto no-scrollbar px-6 pb-8 snap-x">
                        <div v-for="workout in workoutRecommendations" :key="workout.id" 
                            @click="toggleSelection(workout.id)"
                            class="min-w-[80%] snap-center rounded-[48px] overflow-hidden bg-white shadow-xl border-4 transition-all duration-300"
                            :class="selectedWorkoutIds.includes(workout.id) ? 'border-[#00a18c] shadow-[#00a18c]/20' : 'border-white shadow-gray-200/50'">
                            
                            <div class="relative h-64 overflow-hidden bg-gray-100">
                                <img v-if="workout.image" :src="workout.image" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center opacity-10">
                                    <span class="material-symbols-outlined text-6xl">fitness_center</span>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"></div>
                                <div class="absolute top-8 left-8">
                                    <span class="px-4 py-2 bg-[#00a18c] text-white text-[10px] font-black uppercase tracking-widest rounded-xl">
                                        {{ workout.badge }}
                                    </span>
                                </div>
                                
                                <!-- Selection Indicator -->
                                <div class="absolute top-8 right-8">
                                    <div class="size-8 rounded-full flex items-center justify-center border-2 transition-all"
                                        :class="selectedWorkoutIds.includes(workout.id) ? 'bg-[#00a18c] border-[#00a18c]' : 'bg-white/20 border-white'">
                                        <span v-if="selectedWorkoutIds.includes(workout.id)" class="material-symbols-outlined text-white text-xl">check</span>
                                    </div>
                                </div>

                                <div class="absolute bottom-6 left-8">
                                    <p class="text-[10px] font-black text-white/70 uppercase tracking-widest mb-1">{{ workout.gymName }}</p>
                                    <h4 class="text-white text-3xl font-black uppercase italic tracking-tighter">{{ workout.title }}</h4>
                                </div>
                            </div>

                            <div class="p-8 space-y-4">
                                <div class="flex items-center justify-between text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    <span>{{ workout.duration }} • {{ workout.calories }}</span>
                                    <span class="text-[#00a18c]">Tap to Select</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History View (Report List Style) -->
            <div v-else class="px-6 py-4 space-y-10">
                <div v-if="history.length === 0" class="py-20 text-center opacity-30">
                    <span class="material-symbols-outlined text-6xl">history</span>
                    <p class="text-xs font-black uppercase tracking-widest mt-4">No workout history yet</p>
                </div>
                
                <div v-for="entry in history" :key="entry.id" class="space-y-6">
                    <!-- Session Header (Date & Title) -->
                    <div class="px-2">
                        <span class="text-[10px] font-black text-[#00a18c] uppercase tracking-[0.2em] mb-1 block">{{ entry.date }}</span>
                        <h4 class="text-2xl font-black uppercase italic text-gray-900 leading-none tracking-tighter">{{ entry.title }}</h4>
                    </div>
                    
                    <!-- Detailed Exercise List -->
                    <div v-if="entry.exercises && entry.exercises.length > 0" class="space-y-4">
                        <div v-for="ex in entry.exercises" :key="ex.name" class="flex gap-4 items-start bg-white rounded-[32px] p-5 border border-gray-100 shadow-sm shadow-gray-200/50">
                            <!-- Machine Image -->
                            <div class="size-20 rounded-2xl bg-gray-50 overflow-hidden border border-gray-100 flex-shrink-0 p-2 shadow-inner">
                                <img v-if="ex.image" :src="ex.image" class="w-full h-full object-contain">
                                <div v-else class="w-full h-full flex items-center justify-center opacity-10">
                                    <span class="material-symbols-outlined text-2xl">fitness_center</span>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0 pt-1">
                                <h5 class="text-xs font-black uppercase italic text-gray-900 mb-3 truncate tracking-tight">{{ ex.name }}</h5>
                                <!-- History Set Logs (New Labeled Box Style) -->
                                <div class="flex flex-col gap-4">
                                    <div v-for="(set, idx) in ex.sets" :key="idx" class="flex gap-2 items-end">
                                        <!-- Weight Group -->
                                        <div class="flex-1 flex flex-col gap-1">
                                            <span class="text-[7px] font-black text-gray-300 uppercase tracking-wider pl-1">Weight</span>
                                            <div class="bg-white rounded-xl border border-gray-100 py-1.5 flex items-center justify-center">
                                                <span class="text-[10px] font-black text-gray-700">{{ set.weight }}</span>
                                            </div>
                                        </div>

                                        <!-- Sets Group -->
                                        <div class="w-10 flex flex-col gap-1 items-center">
                                            <span class="text-[7px] font-black text-gray-300 uppercase tracking-wider">Sets</span>
                                            <div class="w-full bg-white rounded-xl border border-gray-100 py-1.5 flex items-center justify-center">
                                                <span class="text-[10px] font-black text-gray-400 font-mono">{{ idx + 1 }}</span>
                                            </div>
                                        </div>

                                        <!-- Reps Group -->
                                        <div class="flex-1 flex flex-col gap-1">
                                            <span class="text-[7px] font-black text-gray-300 uppercase tracking-wider pl-1">Reps</span>
                                            <div class="bg-white rounded-xl border border-gray-100 py-1.5 flex items-center justify-center">
                                                <span class="text-[10px] font-black text-gray-700">{{ set.reps }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Combined Start Button (Floating) -->
            <transition name="up">
                <div v-if="selectedWorkoutIds.length > 0" class="fixed bottom-24 left-1/2 -translate-x-1/2 w-full max-w-[340px] px-6 z-40">
                    <button @click="startCombinedWorkout" 
                        class="w-full py-5 rounded-full bg-[#00a18c] text-white font-black uppercase tracking-[0.2em] shadow-[0_20px_40px_rgba(0,161,140,0.3)] flex items-center justify-center gap-4 active:scale-95 hover:brightness-110 transition-all text-[10px] italic border border-white/10">
                        <span class="material-symbols-outlined fill-icon text-xl">play_arrow</span>
                        Start Workout ({{ selectedWorkoutIds.length }})
                    </button>
                </div>
            </transition>
        </div>
    </MobileLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.fill-icon { font-variation-settings: 'FILL' 1; }
.up-enter-active, .up-leave-active { transition: all 0.3s ease; }
.up-enter-from, .up-leave-to { transform: translateY(20px); opacity: 0; }
</style>
