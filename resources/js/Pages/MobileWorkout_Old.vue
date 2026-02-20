<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, computed } from 'vue';

const selectedPath = ref('muscle'); // 'muscle', 'fat', 'strength'
const isCalendarOpen = ref(false);
const isLevelModalOpen = ref(false);
const isWorkoutSessionActive = ref(false);
const selectedLevel = ref('Beginning');
const currentExerciseIndex = ref(0);

// Real-time Date Logic
const today = new Date();
const selectedDate = ref(new Date(today.getFullYear(), today.getMonth(), today.getDate()));
const calendarViewDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));

const levels = [
    { id: 'Beginning', name: 'Beginning' },
    { id: 'Novice', name: 'Novice' },
    { id: 'Intermediate', name: 'Intermediate' },
    { id: 'Advanced', name: 'Advanced' }
];

const paths = [
    { id: 'muscle', name: 'Build Muscle' },
    { id: 'fat', name: 'Fat Loss' },
    { id: 'strength', name: 'Strength' }
];

// Helper to format date as YYYY-MM-DD for key lookup
const formatDateKey = (date) => {
    const d = new Date(date);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().split('T')[0];
};

// Generate 7-day schedule strip (centered on selected date)
const schedule = computed(() => {
    const days = [];
    const start = new Date(selectedDate.value);
    start.setDate(start.getDate() - 2); // Show 2 days before
    
    for (let i = 0; i < 6; i++) {
        const d = new Date(start);
        d.setDate(d.getDate() + i);
        days.push({
            date: d,
            dayNum: d.getDate(),
            label: d.toLocaleDateString('en-US', { weekday: 'short' }),
            fullKey: formatDateKey(d)
        });
    }
    return days;
});

// Calendar Modal Logic
const calendarMonthLabel = computed(() => {
    return calendarViewDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const calendarDays = computed(() => {
    const year = calendarViewDate.value.getFullYear();
    const month = calendarViewDate.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    const days = [];
    // Padding for start of month
    for (let i = 0; i < firstDay; i++) {
        days.push({ day: null });
    }
    // Days of month
    for (let i = 1; i <= daysInMonth; i++) {
        const d = new Date(year, month, i);
        days.push({
            day: i,
            date: d,
            fullKey: formatDateKey(d)
        });
    }
    return days;
});

const changeMonth = (offset) => {
    const newDate = new Date(calendarViewDate.value);
    newDate.setMonth(newDate.getMonth() + offset);
    calendarViewDate.value = newDate;
};

// Workout Data (Mapped by YYYY-MM-DD)
const getWorkoutPlans = () => {
    const t = formatDateKey(today);
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);
    const tm = formatDateKey(tomorrow);
    
    return {
        muscle: {
            [t]: {
                title: 'Power Chest & Triceps',
                subtitle: 'Hypertrophy Session • 65 min',
                exercises: [
                    { 
                        name: 'Incline Barbell Press', 
                        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAQgsXa3tiS7cPy4_fTcSe9oE6KlYFZrrOMNnLBS8CSYXflTymrdYMtwKJLXaw6kaEG3fxrNPPN4kheYHlGcw_otAnA3F3xmcTP7DuJsDQtEOCWp2gtvojJhLw5npsGXKAU1X-026_zyBgDZC1Ww3t86DaJpI8jUWzR7qrG_mvMdbSCPuZ87pMiR38AWvwL9OWZ5cB6vDebYe_-60iJXDAXbylYjCS_oJPooy9dj1Zh6wyCSaStBiN_aKAKfH3IEzK6xlJ6xxrjQrw',
                        mapPos: { x: '82%', y: '16%' },
                        targets: {
                            Beginning: { sets: 3, reps: '12', weight: '20kg' },
                            Novice: { sets: 4, reps: '10', weight: '40kg' },
                            Intermediate: { sets: 4, reps: '8-10', weight: '60kg' },
                            Advanced: { sets: 5, reps: '6-8', weight: '80kg' }
                        }
                    },
                    { 
                        name: 'Cable Chest Flyes', 
                        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD1qG5EQaFvmWIu8PiGMxN02m32Jg4q7tuh3OCqUnVPoc3lh8LaB7hi5GKtirrFNL6MAvKgdGGIAFHfETcAe51e9a4SAk4CqWFsZyEAe4MUa66gckxTELP5gODhniGaoLw83llyTHMrQVj6xbEP0RuUcG2hKk2ZYxMyFc0VRfKSBMrWrmUQEnv0k_7Ymbo3qI6r_nrbg6ig5UlXTBIqoyI2Mcv3PpxFsjL-5zWTf9KsOzsZkBi3bGqZai-ABpcSgNyb-kpxXExCkhY',
                        mapPos: { x: '82%', y: '68%' },
                        targets: {
                            Beginning: { sets: 3, reps: '15', weight: '10kg' },
                            Novice: { sets: 3, reps: '12', weight: '15kg' },
                            Intermediate: { sets: 3, reps: '12-15', weight: '20kg' },
                            Advanced: { sets: 4, reps: '12', weight: '25kg' }
                        }
                    },
                    { 
                        name: 'Dumbbell Skullcrushers', 
                        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCIiPdTcK-zuZm31Wz0V0bZejQWkM7MzIAPtz3K13lqanEb2n1a0rnV6x1qKyuFpfOqQdHqrgEkRY5nW-7_q8zHPYg2wGImqI6VUGkx2MOqQstCPgZY6W8nebEUYkCvJ83SXVoEpWAyjI8Qjgm_KfIYQvOtuCrep2c8IRi8R5uBE612kLY_Ld44oZ_oLVvVWDglp8O1dttPJNfr-nEwj3O0ZjE2GQvzBS23CSyRjbfubZgNAvRRMa0zvL5ZvE0szM-saoUrMVdbcfI',
                        mapPos: { x: '51%', y: '82%' },
                        targets: {
                            Beginning: { sets: 2, reps: '12', weight: '5kg' },
                            Novice: { sets: 3, reps: '12', weight: '10kg' },
                            Intermediate: { sets: 3, reps: '10', weight: '15kg' },
                            Advanced: { sets: 4, reps: '8-10', weight: '17.5kg' }
                        }
                    },
                ]
            },
            [tm]: {
                title: 'High Volume Back',
                subtitle: 'Width & Thickness • 55 min',
                exercises: [
                    { 
                        name: 'Lat Pulldowns', 
                        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCHX-A0K-9-fR0B-Kk8hX-x387gqG2hG0qf1H3R5i8q7q6j5n4m3l2k1j0i9h8g7f6e5d4c3b2a1',
                        mapPos: { x: '25%', y: '30%' },
                        targets: {
                            Beginning: { sets: 3, reps: '12', weight: '25kg' },
                            Novice: { sets: 3, reps: '10', weight: '35kg' },
                            Intermediate: { sets: 4, reps: '10', weight: '45kg' },
                            Advanced: { sets: 4, reps: '8-10', weight: '60kg' }
                        }
                    },
                    { 
                        name: 'Seated Cable Row', 
                        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCHX-A0K-9-fR0B-Kk8hX-x387gqG2hG0qf1H3R5i8q7q6j5n4m3l2k1j0i9h8g7f6e5d4c3b2a1',
                        mapPos: { x: '25%', y: '65%' },
                        targets: {
                            Beginning: { sets: 3, reps: '15', weight: '20kg' },
                            Novice: { sets: 3, reps: '12', weight: '30kg' },
                            Intermediate: { sets: 3, reps: '10-12', weight: '40kg' },
                            Advanced: { sets: 4, reps: '10', weight: '55kg' }
                        }
                    },
                ]
            }
        },
        fat: {
            [t]: {
                title: 'Full Body HIIT',
                subtitle: 'Cardio Core • 45 min',
                exercises: [
                    { 
                        name: 'Burpees', 
                        image: '/images/gorila/GobletSquat.png',
                        mapPos: { x: '50%', y: '50%' },
                        targets: {
                            Beginning: { sets: 3, reps: '30 sec', weight: 'Body' },
                            Novice: { sets: 4, reps: '45 sec', weight: 'Body' },
                            Intermediate: { sets: 5, reps: '60 sec', weight: 'Body' },
                            Advanced: { sets: 6, reps: '60 sec', weight: 'Body' }
                        }
                    },
                    { 
                        name: 'Mountain Climbers', 
                        image: '/images/gorila/DumbbellSkullCrusher.png',
                        mapPos: { x: '60%', y: '60%' },
                        targets: {
                            Beginning: { sets: 3, reps: '30 sec', weight: 'Body' },
                            Novice: { sets: 3, reps: '45 sec', weight: 'Body' },
                            Intermediate: { sets: 4, reps: '45 sec', weight: 'Body' },
                            Advanced: { sets: 5, reps: '60 sec', weight: 'Body' }
                        }
                    },
                ]
            }
        },
        strength: {
            [t]: {
                title: 'Powerlifting Base',
                subtitle: 'Heavy Session • 90 min',
                exercises: [
                    { 
                        name: 'Back Squat', 
                        image: '/images/gorila/GobletSquat.png',
                        mapPos: { x: '30%', y: '80%' },
                        targets: {
                            Beginning: { sets: 3, reps: '8', weight: '40kg' },
                            Novice: { sets: 5, reps: '5', weight: '70kg' },
                            Intermediate: { sets: 5, reps: '3-5', weight: '100kg' },
                            Advanced: { sets: 6, reps: '1-3', weight: '140kg' }
                        }
                    },
                    { 
                        name: 'Bench Press', 
                        image: '/images/gorila/DumbbellLateralRaise.png',
                        mapPos: { x: '82%', y: '16%' },
                        targets: {
                            Beginning: { sets: 3, reps: '10', weight: '30kg' },
                            Novice: { sets: 4, reps: '8', weight: '50kg' },
                            Intermediate: { sets: 5, reps: '5', weight: '80kg' },
                            Advanced: { sets: 5, reps: '3-5', weight: '110kg' }
                        }
                    },
                ]
            }
        }
    };
};

const hasWorkout = (dateKey) => !!getWorkoutPlans()[selectedPath.value]?.[dateKey];

const currentWorkout = computed(() => {
    const key = formatDateKey(selectedDate.value);
    return getWorkoutPlans()[selectedPath.value]?.[key] || {
        title: 'Rest Day',
        subtitle: 'Active Recovery',
        exercises: []
    };
});

const startWorkout = () => {
    isLevelModalOpen.value = true;
};

const beginSession = (level) => {
    selectedLevel.value = level;
    isLevelModalOpen.value = false;
    isWorkoutSessionActive.value = true;
    currentExerciseIndex.value = 0;
};

const finishWorkout = () => {
    isWorkoutSessionActive.value = false;
};

const selectDate = (date) => {
    selectedDate.value = new Date(date);
    isCalendarOpen.value = false;
};

const isToday = (date) => formatDateKey(date) === formatDateKey(today);
const isSelected = (date) => formatDateKey(date) === formatDateKey(selectedDate.value);
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Workouts" />

        <template #footer>
            <div v-if="isWorkoutSessionActive" class="flex gap-3">
                <button v-if="currentExerciseIndex > 0" 
                    @click="currentExerciseIndex--"
                    class="flex-1 h-14 bg-gray-100  text-gray-500 font-black uppercase tracking-widest rounded-2xl active:scale-95 transition-all outline-none">
                    Back
                </button>
                <button v-if="currentExerciseIndex < currentWorkout.exercises.length - 1" 
                    @click="currentExerciseIndex++"
                    class="flex-[2] h-14 bg-[var(--theme-color)] text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all outline-none">
                    Next Exercise
                </button>
                <button v-else 
                    @click="finishWorkout"
                    class="flex-[2] h-14 bg-green-500 text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-green-500/30 active:scale-95 transition-all outline-none">
                    Finish Workout
                </button>
            </div>
            <button v-else-if="currentWorkout.exercises.length > 0" 
                @click="startWorkout"
                class="w-full h-14 bg-[var(--theme-color)] text-white font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-[var(--theme-color)]/30 flex items-center justify-center gap-3 active:scale-95 transition-all italic outline-none">
                <span class="material-symbols-outlined fill-icon">play_arrow</span>
                Start Guided Training
            </button>
        </template>

        <!-- Workout Session View -->
        <div v-if="isWorkoutSessionActive" class="min-h-full">
            <!-- Session Header -->
            <header class="p-6 pb-2 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-[var(--theme-color)] mb-1">Workout Session</h2>
                    <h3 class="text-2xl font-black uppercase italic text-gray-900 leading-tight underline decoration-[var(--theme-color)] decoration-4 underline-offset-4">{{ currentWorkout.title }}</h3>
                </div>
                <button @click="finishWorkout" class="size-10 rounded-full bg-gray-100  flex items-center justify-center">
                    <span class="material-symbols-outlined text-gray-400">close</span>
                </button>
            </header>

            <div class="px-6 py-4">
                <!-- Current Exercise Card -->
                <div class="bg-white  rounded-[32px] p-4 shadow-xl border border-gray-100  mb-6">
                    <div class="flex gap-4 items-start">
                        <div class="size-24 rounded-2xl bg-gray-50  overflow-hidden border border-gray-100  flex-shrink-0">
                            <img :src="currentWorkout.exercises[currentExerciseIndex].image" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1">
                            <span class="inline-block px-2 py-0.5 rounded-lg bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[8px] font-black uppercase tracking-widest mb-1">Exercise {{ currentExerciseIndex + 1 }} of {{ currentWorkout.exercises.length }}</span>
                            <h4 class="text-lg font-black uppercase italic text-gray-900 leading-tight mb-2">{{ currentWorkout.exercises[currentExerciseIndex].name }}</h4>
                            
                            <div class="flex flex-wrap gap-2">
                                <div class="px-3 py-1 bg-gray-50  rounded-xl border border-gray-50 ">
                                    <p class="text-[8px] font-black text-gray-400 uppercase leading-none">Sets</p>
                                    <p class="text-xs font-black  mt-0.5">{{ currentWorkout.exercises[currentExerciseIndex].targets[selectedLevel].sets }}</p>
                                </div>
                                <div class="px-3 py-1 bg-gray-50  rounded-xl border border-gray-50 ">
                                    <p class="text-[8px] font-black text-gray-400 uppercase leading-none">Reps</p>
                                    <p class="text-xs font-black  mt-0.5">{{ currentWorkout.exercises[currentExerciseIndex].targets[selectedLevel].reps }}</p>
                                </div>
                                <div class="px-3 py-1 bg-gray-50  rounded-xl border border-gray-50 ">
                                    <p class="text-[8px] font-black text-gray-400 uppercase leading-none">Weight</p>
                                    <p class="text-xs font-black  mt-0.5">{{ currentWorkout.exercises[currentExerciseIndex].targets[selectedLevel].weight }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gym Map Highlight -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4 px-2">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Gym Navigation</h4>
                        <span class="text-[10px] font-black uppercase tracking-widest text-[var(--theme-color)]">Machine {{ currentExerciseIndex + 1 }} Highlighted</span>
                    </div>
                    
                    <div class="relative w-full aspect-[4/3] bg-gray-50/50  rounded-[40px] overflow-hidden p-6 border border-gray-100  shadow-inner">
                        <!-- Real Gym Map SVG -->
                        <div class="absolute inset-0 p-8">
                            <svg viewBox="0 0 400 300" class="w-full h-full drop-shadow-2xl">
                                <!-- Floor boundary with rounded walls -->
                                <path d="M40,40 Q40,20 60,20 L340,20 Q360,20 360,40 L360,260 Q360,280 340,280 L60,280 Q40,280 40,260 Z" 
                                      fill="white" 
                                      class=" stroke-gray-200 " 
                                      stroke-width="2" />
                                
                                <!-- Decorative Zone Outlines -->
                                <rect x="60" y="40" width="100" height="80" rx="10" 
                                      fill="none" stroke="currentColor" class="text-[var(--theme-color)]/5" stroke-dasharray="4,4" />
                                <rect x="60" y="180" width="100" height="80" rx="10" 
                                      fill="none" stroke="currentColor" class="text-[var(--theme-color)]/5" stroke-dasharray="4,4" />
                                <circle cx="300" cy="150" r="60" 
                                        fill="none" stroke="currentColor" class="text-[var(--theme-color)]/5" stroke-dasharray="4,4" />
                                
                                <!-- Interior Detail Lines -->
                                <path d="M160,20 L160,280" stroke="currentColor" class="text-gray-100 " stroke-width="1" stroke-dasharray="8,8" />
                                <path d="M40,150 L360,150" stroke="currentColor" class="text-gray-100 " stroke-width="1" stroke-dasharray="8,8" />
                            </svg>
                        </div>

                        <!-- Interactive Overlays -->
                        <div class="relative w-full h-full">
                            <div v-for="(ex, index) in currentWorkout.exercises" :key="index"
                                :style="{ left: ex.mapPos.x, top: ex.mapPos.y }"
                                class="absolute transform -translate-x-1/2 -translate-y-1/2 transition-all duration-500">
                                
                                <div :class="[
                                    'size-10 rounded-full flex items-center justify-center font-black text-sm transition-all duration-300 shadow-lg',
                                    index === currentExerciseIndex 
                                        ? 'bg-[var(--theme-color)] text-white scale-125 z-20 ring-4 ring-[var(--theme-color)]/30' 
                                        : 'bg-white  text-gray-400 scale-90 z-10 border border-gray-100 '
                                ]">
                                    {{ index + 1 }}
                                </div>
                                <div v-if="index === currentExerciseIndex" 
                                     class="absolute -inset-2 bg-[var(--theme-color)]/20 animate-ping rounded-full scale-150"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Default View -->
        <div v-else>

        <!-- Header -->
        <header class="flex items-center justify-between p-6 pb-4 sticky top-0 bg-white/80  backdrop-blur-md z-20 border-b border-gray-100 ">
            <div class="flex items-center gap-4">
                <div class="size-16 rounded-2xl bg-white shadow-xl border border-gray-100 flex items-center justify-center p-1">
                    <img src="/images/gorila/GorillaLogo.png" class="size-12 object-contain">
                </div>
                <h1 class="text-2xl font-black uppercase italic tracking-tighter text-gray-900 leading-none mt-1">FitPung</h1>
            </div>
            <div class="flex items-center gap-4">
                <button class="relative p-2 rounded-full hover:bg-gray-100  transition-colors">
                    <span class="material-symbols-outlined text-gray-900">notifications</span>
                    <span class="absolute top-2.5 right-2.5 size-2 bg-[var(--theme-color)] rounded-full border-2 border-white "></span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <div class="px-6 py-4">
            <!-- Goal Selection -->
            <div class="mb-8">
                <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-4">Choose Your Path</h2>
                <div class="flex p-1 bg-gray-100  rounded-2xl">
                    <button v-for="path in paths" :key="path.id"
                        @click="selectedPath = path.id"
                        :class="[
                            'flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all duration-300',
                            selectedPath === path.id 
                                ? 'bg-white  shadow-sm text-[var(--theme-color)]' 
                                : 'text-gray-500'
                        ]">
                        {{ path.name }}
                    </button>
                </div>
            </div>

            <!-- Schedule -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Schedule</h3>
                    <button @click="isCalendarOpen = true; calendarViewDate = new Date(selectedDate)" class="px-3 py-1 rounded-full bg-[var(--theme-color)]/5 text-[var(--theme-color)] flex items-center gap-1">
                        <span class="text-[10px] font-black uppercase tracking-widest">{{ selectedDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) }}</span>
                        <span class="material-symbols-outlined text-[10px]">expand_more</span>
                    </button>
                </div>
                <div class="flex gap-3 overflow-x-auto no-scrollbar pb-2">
                    <div v-for="item in schedule" :key="item.fullKey" 
                         @click="selectedDate = item.date"
                         :class="[
                            'flex flex-col items-center min-w-[56px] py-4 rounded-2xl transition-all duration-300 cursor-pointer',
                            isSelected(item.date) 
                                ? 'bg-[var(--theme-color)] text-white shadow-xl shadow-[var(--theme-color)]/30 scale-105' 
                                : 'bg-white  border border-gray-50  text-gray-400 font-black',
                            isToday(item.date) && !isSelected(item.date) ? 'border-[var(--theme-color)]/30 ring-1 ring-[var(--theme-color)]/20' : ''
                         ]">
                        <span class="text-[9px] font-black uppercase" :class="isSelected(item.date) ? 'opacity-80' : 'opacity-60'">{{ item.label }}</span>
                        <span class="text-lg font-black mt-1">{{ item.dayNum }}</span>
                        <div v-if="hasWorkout(item.fullKey)" class="size-1 mt-1 bg-[var(--theme-color)] rounded-full" :class="isSelected(item.date) ? 'bg-white' : ''"></div>
                    </div>
                </div>
            </div>

            <!-- Daily Workout -->
            <div class="mb-6">
                <h3 class="text-2xl font-black uppercase tracking-tighter italic leading-tight text-gray-900">{{ currentWorkout.title }}</h3>
                <p class="text-gray-400 text-xs mt-1 font-bold uppercase tracking-widest">{{ currentWorkout.subtitle }}</p>
            </div>

            <div class="space-y-4">
                <div v-if="currentWorkout.exercises.length === 0" class="py-12 flex flex-col items-center justify-center text-center opacity-50">
                    <span class="material-symbols-outlined text-4xl mb-2">hotel</span>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">Take a rest. Your muscles need it.</p>
                </div>
                <div v-else v-for="ex in currentWorkout.exercises" :key="ex.name" class="group flex items-center p-4 bg-white  rounded-[24px] border border-gray-100  shadow-sm active:scale-95 transition-all">
                    <div class="size-16 rounded-2xl bg-gray-100  overflow-hidden flex-shrink-0 border border-gray-50 ">
                        <img :src="ex.image" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500">
                    </div>
                    <div class="ml-4 flex-1">
                        <h4 class="font-black uppercase italic text-sm tracking-tight text-gray-900 leading-none">{{ ex.name }}</h4>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-[9px] font-black bg-[var(--theme-color)]/10 text-[var(--theme-color)] px-2 py-0.5 rounded-full uppercase tracking-widest">{{ ex.sets }} Sets</span>
                            <span class="text-[9px] font-black bg-gray-100  text-gray-400 px-2 py-0.5 rounded-full uppercase tracking-widest">{{ ex.reps }} Reps</span>
                        </div>
                    </div>
                    <button class="p-2 text-gray-300 hover:text-[var(--theme-color)] transition-colors">
                        <span class="material-symbols-outlined">more_vert</span>
                    </button>
                </div>
            </div>
        </div>
        </div>

        <!-- Calendar Modal -->
        <transition name="fade">
            <div v-if="isCalendarOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm">
                <div class="absolute inset-0" @click="isCalendarOpen = false"></div>
                
                <div class="bg-white  w-full max-w-sm rounded-[32px] overflow-hidden shadow-2xl relative animate-in zoom-in-95 duration-200">
                    <div class="p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-2">
                                <button @click="changeMonth(-1)" class="p-1 hover:bg-gray-100  rounded-lg">
                                    <span class="material-symbols-outlined text-gray-400">chevron_left</span>
                                </button>
                                <h2 class="text-lg font-black uppercase italic text-gray-900 min-w-[140px] text-center">{{ calendarMonthLabel }}</h2>
                                <button @click="changeMonth(1)" class="p-1 hover:bg-gray-100  rounded-lg">
                                    <span class="material-symbols-outlined text-gray-400">chevron_right</span>
                                </button>
                            </div>
                            <button @click="isCalendarOpen = false" class="size-8 rounded-full bg-gray-100  flex items-center justify-center">
                                <span class="material-symbols-outlined text-gray-900 text-lg font-bold">close</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 gap-2">
                            <div v-for="d in ['S','M','T','W','T','F','S']" :key="d" class="text-[10px] font-black text-gray-400 text-center mb-2">{{ d }}</div>
                            <div v-for="(dayObj, idx) in calendarDays" :key="idx" 
                                @click="dayObj.day ? selectDate(dayObj.date) : null"
                                :class="[
                                    'aspect-square flex flex-col items-center justify-center rounded-2xl transition-all relative group',
                                    dayObj.day ? 'cursor-pointer' : 'pointer-events-none opacity-0',
                                    dayObj.day && isSelected(dayObj.date) 
                                        ? 'bg-[var(--theme-color)] text-white shadow-xl shadow-[var(--theme-color)]/40 scale-110 z-10' 
                                        : 'bg-white  border border-gray-50  text-gray-400 font-black',
                                    dayObj.day && hasWorkout(dayObj.fullKey) && !isSelected(dayObj.date)
                                        ? 'ring-2 ring-[var(--theme-color)]/10 bg-[var(--theme-color)]/[0.03]  shadow-sm shadow-[var(--theme-color)]/5'
                                        : '',
                                    dayObj.day && isToday(dayObj.date) && !isSelected(dayObj.date) ? 'border-[var(--theme-color)]/30' : ''
                                ]">
                                <!-- Day Number -->
                                <span class="text-xs font-black" :class="isSelected(dayObj.date) ? 'text-white' : (hasWorkout(dayObj.fullKey) ? 'text-gray-900' : 'text-gray-400')">{{ dayObj.day }}</span>
                                
                                <!-- Premium Workout Marker (Badge Style) -->
                                <div v-if="dayObj.day && hasWorkout(dayObj.fullKey)" 
                                     class="absolute -top-2 -right-2 size-7 rounded-lg bg-white  shadow-lg flex items-center justify-center border border-gray-100  transform group-hover:scale-110 transition-transform duration-300"
                                     :class="isSelected(dayObj.date) ? 'ring-2 ring-white/50' : ''">
                                     <img src="/images/gorila/GorillaLogo.png" class="size-5 object-contain" :class="isSelected(dayObj.date) ? 'brightness-110' : ''">
                                </div>
                                
                                <!-- Subtle indicator for selected day that is today -->
                                <div v-if="dayObj.day && isToday(dayObj.date) && isSelected(dayObj.date)" class="absolute bottom-1.5 size-1 bg-white rounded-full"></div>
                                <div v-if="dayObj.day && isToday(dayObj.date) && !isSelected(dayObj.date)" class="absolute bottom-1.5 size-1 bg-[var(--theme-color)] rounded-full"></div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 ">
                             <div class="flex items-center gap-4">
                                 <div class="size-12 rounded-xl bg-white  shadow-lg flex items-center justify-center border border-gray-100 ">
                                     <img src="/images/gorila/GorillaLogo.png" class="size-9 object-contain">
                                 </div>
                                  <div>
                                     <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest leading-none">Workout Day</p>
                                     <p class="text-[9px] font-medium text-gray-400 uppercase tracking-widest mt-1">Marked with premium gorilla badge</p>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Level Selection Modal -->
        <transition name="fade">
            <div v-if="isLevelModalOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm">
                <div class="absolute inset-0" @click="isLevelModalOpen = false"></div>
                <div class="bg-white  w-full max-w-sm rounded-[32px] p-8 shadow-2xl relative animate-in zoom-in-95 duration-200">
                    <h2 class="text-xl font-black uppercase italic text-gray-900 mb-6">Select Your Level</h2>
                    <div class="space-y-3">
                        <button v-for="lvl in levels" :key="lvl.id" 
                            @click="beginSession(lvl.id)"
                            class="w-full p-5 rounded-2xl border-2 border-gray-50  flex items-center justify-between hover:border-[var(--theme-color)] active:scale-98 transition-all group bg-white ">
                            <span class="font-black uppercase italic tracking-tight text-gray-900">{{ lvl.name }}</span>
                            <div class="size-8 rounded-full bg-[var(--theme-color)]/10 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-[var(--theme-color)] text-sm font-bold">play_arrow</span>
                            </div>
                        </button>
                    </div>
                    <button @click="isLevelModalOpen = false" class="mt-6 w-full py-3 text-gray-400 font-black uppercase text-[10px] tracking-[0.2em]">Cancel</button>
                </div>
            </div>
        </transition>
    </MobileLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
