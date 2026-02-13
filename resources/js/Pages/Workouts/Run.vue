<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    plan: String // JSON string of the workout plan
});

const workoutData = ref([]);
const sessionStarted = ref(false); // New state for summary vs active workout
const currentIndex = ref(0);
const sessionSets = ref({}); // Store sets for each exercise: { [exerciseId]: sets[] }

onMounted(() => {
    if (props.plan) {
        try {
            workoutData.value = JSON.parse(props.plan);
            // Initialize sets for each exercise
            workoutData.value.forEach(ex => {
                sessionSets.value[ex.id] = [];
            });
        } catch (e) {
            console.error("Failed to parse plan", e);
        }
    }
});

const currentExercise = computed(() => workoutData.value[currentIndex.value] || null);
const currentSets = computed(() => currentExercise.value ? sessionSets.value[currentExercise.value.id] : []);

const activeSetInput = ref({
    weight: 40,
    reps: 12
});

const addSet = () => {
    if (!currentExercise.value) return;
    
    sessionSets.value[currentExercise.value.id].push({
        id: sessionSets.value[currentExercise.value.id].length + 1,
        weight: activeSetInput.value.weight,
        reps: activeSetInput.value.reps,
        completed: true
    });
};

const adjustWeight = (amount) => {
    activeSetInput.value.weight = Math.max(0, activeSetInput.value.weight + amount);
};

const adjustReps = (amount) => {
    activeSetInput.value.reps = Math.max(0, activeSetInput.value.reps + amount);
};

const nextExercise = () => {
    if (currentIndex.value < workoutData.value.length - 1) {
        currentIndex.value++;
    } else {
        finishWorkout();
    }
};

const prevExercise = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
};

const startWorkout = () => {
    sessionStarted.value = true;
};

const finishWorkout = () => {
    alert('Workout Session Finished! Well done.');
    window.location.href = '/dashboard';
};
</script>

<template>
    <Head title="Workout Session" />

    <AppLayout>
        <div class="min-h-screen bg-gray-50/30 text-gray-900 p-4 md:p-8">
            <div class="max-w-xl mx-auto space-y-8">
                
                <div v-if="workoutData.length > 0">
                    
                    <!-- 1. PRE-WORKOUT SUMMARY VIEW -->
                    <div v-if="!sessionStarted" class="animate-fade-in bg-white p-6 md:p-12 rounded-[2.5rem] md:rounded-[3.5rem] border border-gray-100 shadow-[0_30px_80px_rgba(0,0,0,0.03)] space-y-8 md:space-y-10">
                        <div class="text-center space-y-2 md:space-y-3">
                            <h1 class="text-3xl md:text-4xl font-black italic uppercase tracking-tighter">Your Session Plan</h1>
                            <p class="text-indigo-600 font-black uppercase tracking-[0.2em] text-[9px] md:text-[10px] opacity-60 italic">Review your exercises before starting</p>
                        </div>

                        <div class="space-y-4 md:space-y-6">
                            <div v-for="(ex, idx) in workoutData" :key="idx" 
                                 class="bg-gray-50/50 p-4 md:p-8 rounded-[2rem] md:rounded-[2.5rem] border border-gray-100/50 flex items-center gap-4 md:gap-8 hover:bg-white hover:shadow-xl hover:shadow-gray-200/20 transition-all duration-500 group">
                                <div class="w-20 h-20 md:w-28 md:h-28 bg-white rounded-[1.5rem] md:rounded-[2rem] p-3 md:p-5 flex items-center justify-center border border-gray-100 shadow-sm group-hover:scale-105 transition-transform duration-500 shrink-0">
                                    <img :src="ex.item?.src || '/images/equipment/BenchPress.svg'" class="w-full h-full object-contain" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-black italic uppercase text-base md:text-xl leading-tight text-gray-800 truncate">{{ ex.name }}</h3>
                                    <p class="text-[10px] md:text-[11px] font-black text-indigo-600 uppercase tracking-widest mt-1 opacity-80 truncate">{{ ex.muscle }}</p>
                                    <div class="flex gap-4 md:gap-6 mt-3 md:mt-4">
                                        <div class="flex flex-col">
                                            <span class="text-[8px] md:text-[9px] font-black uppercase text-gray-300 tracking-widest leading-none">Sets</span>
                                            <span class="text-base md:text-lg font-black italic text-gray-700">{{ ex.sets }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[8px] md:text-[9px] font-black uppercase text-gray-300 tracking-widest leading-none">Reps</span>
                                            <span class="text-base md:text-lg font-black italic text-gray-700">{{ ex.reps }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button @click="startWorkout" class="w-full py-6 md:py-8 bg-indigo-600 hover:bg-indigo-700 text-white rounded-[2rem] md:rounded-[2.5rem] font-black italic uppercase tracking-[0.3em] md:tracking-[0.4em] shadow-2xl shadow-indigo-600/40 transform active:scale-95 transition-all text-lg md:text-xl flex items-center justify-center gap-3 md:gap-4 group">
                            <span>Start Workout</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <!-- 2. ACTIVE WORKOUT VIEW -->
                    <div v-else class="animate-fade-in space-y-6 md:space-y-8">
                        <!-- Progress Header -->
                        <div class="flex items-center justify-between mb-6 md:mb-8">
                            <div class="flex flex-col gap-0.5 md:gap-1">
                                <span class="text-[9px] md:text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 italic">Current Session</span>
                                <h1 class="text-2xl md:text-3xl font-black italic uppercase italic tracking-tighter leading-none">Running Plan</h1>
                            </div>
                            <div class="text-right">
                                <div class="text-xl md:text-2xl font-mono font-black text-indigo-600">Ex {{ currentIndex + 1 }}/{{ workoutData.length }}</div>
                                <div class="w-24 md:w-32 h-1.5 bg-gray-200 rounded-full mt-1.5 md:mt-2 overflow-hidden ml-auto">
                                    <div class="h-full bg-indigo-600 transition-all duration-500" :style="{ width: ((currentIndex + 1) / workoutData.length * 100) + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Exercise Card -->
                        <div class="bg-white rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-8 border border-gray-100 shadow-xl shadow-gray-200/20 relative overflow-hidden group mb-6 md:mb-8">
                            <div class="flex items-center gap-5 md:gap-6 relative z-10">
                                <div class="w-16 h-16 md:w-24 md:h-24 bg-gray-50 rounded-2xl md:rounded-[1.5rem] p-3 md:p-4 flex items-center justify-center border border-gray-100 shrink-0">
                                    <img :src="currentExercise?.item?.src || '/images/equipment/BenchPress.svg'" class="w-full h-full object-contain" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-lg md:text-xl font-black italic uppercase tracking-tight leading-tight truncate">{{ currentExercise?.name }}</h2>
                                    <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest mt-1 truncate">{{ currentExercise?.muscle }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Recording Section -->
                        <div class="space-y-4 md:space-y-6">
                            <!-- History -->
                            <div v-if="currentSets.length > 0" class="space-y-2.5 md:space-y-3">
                                <div v-for="set in currentSets" :key="set.id" 
                                     class="flex items-center justify-between p-4 md:p-5 bg-white rounded-xl md:rounded-2xl border border-gray-100 shadow-sm animate-fade-in">
                                    <div class="flex items-center gap-4 md:gap-6">
                                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-lg md:rounded-xl bg-gray-50 flex items-center justify-center text-[9px] md:text-[10px] font-black italic text-gray-400 shrink-0">
                                            {{ set.id }}
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg md:text-xl font-black italic text-gray-900">{{ set.weight }} KG</span>
                                            <span class="text-gray-200 font-bold">×</span>
                                            <span class="text-lg md:text-xl font-black italic text-gray-900">{{ set.reps }} REPS</span>
                                        </div>
                                    </div>
                                    <div class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Recording Card -->
                            <div class="bg-indigo-600 rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-8 shadow-2xl shadow-indigo-600/30 text-white relative">
                                <div class="flex items-center justify-between mb-6 md:mb-8">
                                    <span class="text-base md:text-lg font-black italic uppercase tracking-tight">Active Set {{ currentSets.length + 1 }}</span>
                                    <div class="px-3 md:px-4 py-1 md:py-1.5 bg-white/20 rounded-full text-[8px] md:text-[9px] font-black uppercase tracking-widest border border-white/20">
                                        Target: {{ currentExercise?.sets }} Sets
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 md:gap-8">
                                    <div class="space-y-2 md:space-y-3">
                                        <label class="text-[8px] md:text-[9px] font-black uppercase tracking-widest text-white/60 italic ml-2">Weight</label>
                                        <div class="flex items-center justify-between bg-white rounded-xl md:rounded-2xl p-1.5 md:p-2">
                                            <button @click="adjustWeight(-2.5)" class="btn btn-circle btn-xs md:btn-sm btn-ghost text-indigo-600 border-none font-black">-</button>
                                            <div class="text-2xl md:text-3xl font-black italic text-indigo-600">{{ activeSetInput.weight }}</div>
                                            <button @click="adjustWeight(2.5)" class="btn btn-circle btn-xs md:btn-sm btn-ghost text-indigo-600 border-none font-black">+</button>
                                        </div>
                                    </div>
                                    <div class="space-y-2 md:space-y-3">
                                        <label class="text-[8px] md:text-[9px] font-black uppercase tracking-widest text-white/60 italic ml-2">Reps</label>
                                        <div class="flex items-center justify-between bg-white rounded-xl md:rounded-2xl p-1.5 md:p-2">
                                            <button @click="adjustReps(-1)" class="btn btn-circle btn-xs md:btn-sm btn-ghost text-indigo-600 border-none font-black">-</button>
                                            <div class="text-2xl md:text-3xl font-black italic text-indigo-600">{{ activeSetInput.reps }}</div>
                                            <button @click="adjustReps(1)" class="btn btn-circle btn-xs md:btn-sm btn-ghost text-indigo-600 border-none font-black">+</button>
                                        </div>
                                    </div>
                                </div>

                                <button @click="addSet" class="w-full mt-6 md:mt-10 py-4 md:py-5 bg-white text-indigo-600 hover:bg-gray-100 rounded-xl md:rounded-2xl font-black italic uppercase tracking-[0.2em] md:tracking-[0.3em] shadow-xl transform active:scale-95 transition-all text-xs md:text-sm flex items-center justify-center gap-2 md:gap-3">
                                    <span>Record Set</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Navigation Actions -->
                        <div class="flex gap-3 md:gap-4 pt-6 md:pt-8">
                            <button v-if="currentIndex > 0" @click="prevExercise" class="flex-1 py-4 md:py-5 bg-gray-100 hover:bg-gray-200 rounded-xl md:rounded-2xl text-[9px] md:text-[10px] font-black uppercase tracking-widest text-gray-500 transition-all">
                                Prev
                            </button>
                            <button @click="nextExercise" class="flex-[2] py-4 md:py-5 bg-gray-900 hover:bg-black text-white rounded-xl md:rounded-2xl text-[9px] md:text-[10px] font-black uppercase tracking-widest transition-all">
                                {{ currentIndex < workoutData.length - 1 ? 'Next Exercise' : 'Finish Session' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-20 bg-white rounded-[3rem] border border-gray-100 shadow-sm">
                    <p class="text-gray-400 font-bold italic uppercase tracking-widest">No plan found</p>
                    <Link :href="route('dashboard')" class="btn btn-primary btn-sm rounded-xl mt-6 px-10">Back to Home</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
