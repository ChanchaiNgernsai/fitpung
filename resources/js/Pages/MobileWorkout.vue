<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '@/language';
import axios from 'axios';
import TrainerTab from '@/Components/TrainerTab.vue';

const { t } = useI18n();

const props = defineProps({
    gyms: { type: Array, default: () => [] },
    trainers: { type: Array, default: () => [] },
    activePackage: { type: Object, default: null },
    todaySchedule: { type: Object, default: null },
    bookings: { type: Array, default: () => [] }
});

const mode = ref(null); // null (selection) | 'free'
const activeTab = ref('equipments'); // 'equipments' | 'plans' | 'history'
const isWorkoutSessionActive = ref(false);
const weightOptions = Array.from({ length: 80 }, (_, i) => ((i + 1) * 2.5).toFixed(1).replace(/\.0$/, '') + 'kg');

// Helper for timezone-robust date display (e.g., Friday, Feb 27)
const formatLocalDate = (dateStr) => {
    const d = new Date(dateStr);
    // Use Intl.DateTimeFormat with a fixed locale to ensure consistency
    return new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'short', day: 'numeric' }).format(d);
};

const activeWorkout = ref(null);
const setsDone = ref(0);
const totalMinutes = ref(48);
const searchQuery = ref('');
const selectedWorkoutIds = ref([]);
const history = ref([]);
const customPlans = ref([]);
const viewMode = ref('list'); // 'list' | 'grid'
const isShowFinishConfirm = ref(false);
const isShowSuccessMessage = ref(false);
const initialTrainerId = ref(null);

const openTrainerWithId = (id) => {
    initialTrainerId.value = id;
    mode.value = 'trainer';
};

// Load stats, history, and custom plans
onMounted(() => {
    const savedSets = localStorage.getItem('fitpung_sets_done');
    if (savedSets) setsDone.value = parseInt(savedSets);

    const savedHistory = localStorage.getItem('fitpung_workout_history');
    if (savedHistory) history.value = JSON.parse(savedHistory);

    // Initial load from localStorage for speed
    const savedPlans = localStorage.getItem('fitpung_custom_plans');
    if (savedPlans) customPlans.value = JSON.parse(savedPlans);
    
    // Sync from database
    refreshPlans();
    fetchWorkoutHistory();
});

const fetchWorkoutHistory = async () => {
    try {
        const response = await axios.get('/api/workout-sessions');
        // Handle both paginated and non-paginated responses just in case
        const sessionsData = response.data.data || response.data;
        
        if (!Array.isArray(sessionsData)) {
            console.error("Invalid history data format", response.data);
            return;
        }

        const dbSessions = sessionsData.map(s => ({
            id: s.id,
            apiId: s.id,
            date: formatLocalDate(s.workout_date),
            title: s.data?.title || 'Workout Session',
            exercises: s.data?.exercises || [],
            sets: s.data?.sets || 0
        }));
        history.value = dbSessions;
        localStorage.setItem('fitpung_workout_history', JSON.stringify(dbSessions));
    } catch (e) {
        console.error("Failed to fetch workout history from DB", e);
    }
};

const refreshPlans = async () => {
    try {
        const response = await axios.get('/api/workout-plans');
        // Map DB structure to our local structure
        const dbPlans = response.data.map(p => ({
            id: p.id,
            dbId: p.id,
            title: p.name,
            ...p.data,
            isCustom: true
        }));
        customPlans.value = dbPlans;
        localStorage.setItem('fitpung_custom_plans', JSON.stringify(dbPlans));
    } catch (e) {
        console.error("Failed to fetch plans from DB", e);
    }
};

const selectMode = (selectedMode) => {
    if (selectedMode === 'gym') {
        window.location.href = route('mobile.maps');
    } else {
        mode.value = selectedMode;
    }
};

const logSet = () => {
    setsDone.value++;
    localStorage.setItem('fitpung_sets_done', setsDone.value.toString());
};

const formatImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/images/')) return path;
    return `/storage/${path}`;
};

const allEquipments = computed(() => {
    const equipMap = new Map();
    props.gyms.forEach(gym => {
        if (!gym.items) return;
        gym.items.forEach(item => {
            const name = item.name || 'Unknown Machine';
            if (!equipMap.has(name)) {
                equipMap.set(name, {
                    id: `equip-${name.replace(/\s+/g, '-').toLowerCase()}`,
                    title: name,
                    name: name,
                    image: formatImageUrl(item.src),
                    gymName: gym.name,
                    isEquipment: true,
                    exercises: [{ 
                        name: name, 
                        sets: 3, 
                        reps: '12', 
                        targetWeight: '10kg',
                        image: formatImageUrl(item.src)
                    }]
                });
            }
        });
    });
    
    let list = Array.from(equipMap.values());
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(e => e.title.toLowerCase().includes(q));
    }
    return list;
});

const userSavedPlans = computed(() => {
    let list = customPlans.value.map(p => ({ ...p, isCustom: true }));
    
    if (searchQuery.value && activeTab.value === 'plans') {
         const q = searchQuery.value.toLowerCase();
         return list.filter(r => r.title.toLowerCase().includes(q));
    }
    return list;
});

const saveAsCustomPlan = (workoutObj = null) => {
    let exercisesToSave = [];
    let defaultTitle = "";

    if (workoutObj && workoutObj.exercises) {
        // Saving from active session
        exercisesToSave = workoutObj.exercises.map(ex => {
            const firstLog = ex.workoutLogs[0] || {};
            return {
                name: ex.name,
                sets: ex.workoutLogs.length || 3,
                targetWeight: firstLog.weight || ex.targetWeight || '10kg',
                reps: firstLog.reps || ex.reps || '12',
                image: ex.image
            };
        });
        defaultTitle = workoutObj.title;
    } else {
        // Saving from selection in pool
        const selectedItems = allEquipments.value.filter(e => selectedWorkoutIds.value.includes(e.id));
        if (selectedItems.length === 0) return;
        exercisesToSave = selectedItems.map(item => ({
            name: item.name,
            sets: 3,
            reps: item.exercises[0]?.reps || '12',
            targetWeight: item.exercises[0]?.targetWeight || '10kg',
            image: item.image
        }));
        defaultTitle = `My Workout ${customPlans.value.length + 1}`;
    }

    const planName = prompt("Name your custom workout plan:", defaultTitle);
    if (!planName) return;

    const planData = {
        gymName: 'Saved Plan',
        badge: 'USER SAVED',
        duration: 'Custom',
        calories: '---',
        isCustom: true,
        exercises: exercisesToSave
    };

    // Save to DB
    axios.post('/api/workout-plans', {
        name: planName,
        data: planData
    }).then(res => {
        refreshPlans();
    }).catch(e => {
        // Fallback to local
        const newPlan = {
            id: `custom-${Date.now()}`,
            title: planName,
            ...planData
        };
        customPlans.value.unshift(newPlan);
        localStorage.setItem('fitpung_custom_plans', JSON.stringify(customPlans.value));
    });
    
    if (!workoutObj) {
        selectedWorkoutIds.value = [];
        activeTab.value = 'plans';
    }
    alert("Plan saved successfully!");
};

const deletePlan = async (planId) => {
    if (!confirm("Are you sure you want to delete this plan?")) return;
    
    try {
        // Find if it has a dbId
        const plan = customPlans.value.find(p => p.id === planId);
        if (plan && plan.dbId) {
            await axios.delete(`/api/workout-plans/${plan.dbId}`);
        }
        
        customPlans.value = customPlans.value.filter(p => p.id !== planId);
        localStorage.setItem('fitpung_custom_plans', JSON.stringify(customPlans.value));
    } catch (e) {
        alert("Failed to delete plan");
    }
};

const toggleSelection = (id) => {
    const index = selectedWorkoutIds.value.indexOf(id);
    if (index === -1) {
        selectedWorkoutIds.value.push(id);
    } else {
        selectedWorkoutIds.value.splice(index, 1);
    }
};

const startCombinedWorkout = () => {
    // Starting from Equipment Pool or Saved Plans
    const pool = activeTab.value === 'equipments' ? allEquipments.value : userSavedPlans.value;
    const selectedPlans = pool.filter(r => selectedWorkoutIds.value.includes(r.id));
    
    // Deduplication logic: Flatten exercises and merge by name
    const exerciseMap = new Map();
    
    selectedPlans.forEach(plan => {
        if (!plan.exercises) return;
        plan.exercises.forEach(ex => {
                if (!exerciseMap.has(ex.name)) {
                    // Try to get exercise image
                    let exerciseImage = formatImageUrl(ex.image);
                    
                    
                    if (!exerciseImage) {
                        exerciseImage = getExerciseImage(ex);
                    }

                    const targetSets = parseInt(ex.sets) || 3;
                    const initialLogs = Array.from({ length: targetSets }, (_, idx) => ({
                        id: Date.now() + idx,
                        weight: ex.targetWeight || '10kg',
                        reps: ex.reps || '12',
                        completed: false
                    }));

                    exerciseMap.set(ex.name, { 
                        ...ex, 
                        targetSets: targetSets,
                        targetWeight: ex.targetWeight || '10kg',
                        reps: ex.reps || '12',
                        workoutLogs: initialLogs, // Pre-populate sets as requested
                        image: exerciseImage,
                        isMerged: false
                    });
                } else {
                    const existing = exerciseMap.get(ex.name);
                    const additionalSets = (parseInt(ex.sets) || 0);
                    existing.targetSets += additionalSets;
                    existing.isMerged = true;
                }
        });
    });
    
    if (exerciseMap.size === 0) return;

    const isManual = activeTab.value === 'equipments';
    
    activeWorkout.value = {
        title: selectedPlans.length === 1 ? selectedPlans[0].title : (isManual ? `New Session (${selectedPlans.length} items)` : `My Workout (${selectedPlans.length} items)`),
        sessionType: isManual ? 'manual' : 'plan',
        exercises: Array.from(exerciseMap.values()).map(ex => {
            const targetSets = parseInt(ex.sets) || 3;
            // DIFFERENTIATION: 
            // If manual (Builder), start with just 1 set to be configured.
            // If plan, pre-populate all sets as saved.
            const initialCount = isManual ? 1 : targetSets;
            
            return {
                ...ex,
                targetSets: targetSets,
                workoutLogs: Array.from({ length: initialCount }, (_, idx) => ({
                    id: Date.now() + idx + Math.random(),
                    weight: ex.targetWeight || '10kg',
                    reps: ex.reps || '12',
                    completed: false
                }))
            };
        })
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
    isShowFinishConfirm.value = true;
};

const confirmFinishWorkout = async () => {
    // Collect all exercises where at least one set has reps/weight OR is completed
    const sessionExercises = activeWorkout.value.exercises.map(ex => {
        // Find sets with interaction (either completed OR have numerical data)
        const validSets = ex.workoutLogs.filter(s => {
            if (s.completed) return true;
            const repsNum = parseInt(s.reps);
            return !isNaN(repsNum) && repsNum > 0;
        }).map(s => ({
            weight: s.weight,
            reps: s.reps
        }));

        return {
            name: ex.name,
            image: ex.image,
            sets: validSets
        };
    }).filter(ex => ex.sets.length > 0);

    const totalSets = sessionExercises.reduce((sum, ex) => sum + ex.sets.length, 0);

    if (sessionExercises.length === 0) {
        alert("Please log at least one exercise before finishing.");
        isShowFinishConfirm.value = false;
        return;
    }

    const sessionData = {
        workout_date: new Date().toISOString().split('T')[0],
        data: {
            title: activeWorkout.value.title,
            exercises: sessionExercises,
            sets: totalSets
        }
    };

    try {
        // Ensure CSRF token if meta tag exists (standard Laravel)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await axios.post('/api/workout-sessions', sessionData);
        
        // Refresh full history from server after successful save
        await fetchWorkoutHistory();
        
        isWorkoutSessionActive.value = false;
        activeWorkout.value = null;
        selectedWorkoutIds.value = [];
        isShowFinishConfirm.value = false;
        isShowSuccessMessage.value = true;
        
        setTimeout(() => {
            isShowSuccessMessage.value = false;
            mode.value = null;
        }, 3000);
    } catch (e) {
        console.error("Failed to save workout session", e);
        alert("บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง");
    }
};

const deleteHistoryEntry = async (id) => {
    if (!confirm("Delete this workout from history?")) return;
    const entryToDelete = history.value.find(h => h.id === id);
    if (entryToDelete) {
        // Delete from API if it has an apiId
        if (entryToDelete.apiId) {
            try {
                await axios.delete(`/api/workout-sessions/${entryToDelete.apiId}`);
            } catch (e) {
                console.error("Failed to delete session from API", e);
                alert("Failed to delete from server. Try again.");
                return;
            }
        }

        // Subtract sets from total
        const newSets = Math.max(0, setsDone.value - (entryToDelete.sets || 0));
        setsDone.value = newSets;
        localStorage.setItem('fitpung_sets_done', newSets.toString());
        
        // Remove from history
        history.value = history.value.filter(h => h.id !== id);
        localStorage.setItem('fitpung_workout_history', JSON.stringify(history.value));
    }
};

// Computed property to check if user has gym workout history from localstorage 
// (Note: This is a hacky way to merge histories, ideally should be unified)
const mergedHistory = computed(() => {
    return history.value;
});

const groupedHistory = computed(() => {
    const groups = [];
    mergedHistory.value.forEach(entry => {
        // Find if we already have a group for this date
        let group = groups.find(g => g.date === entry.date);
        if (!group) {
            group = {
                date: entry.date,
                entries: []
            };
            groups.push(group);
        }
        group.entries.push(entry);
    });
    return groups;
});
const getExerciseImage = (ex) => {
    if (ex.image) return ex.image;
    
    const nameLower = (ex.name || '').toLowerCase();
    // Use available SVGs for the most common exercises
    if (nameLower.includes('dumbbell') || nameLower.includes('curl') || nameLower.includes('lateral raise') || nameLower.includes('fly')) 
        return '/images/equipment/Dumbbells.svg';
    if (nameLower.includes('treadmill') || nameLower.includes('run') || nameLower.includes('walk')) 
        return '/images/equipment/Treadmill.svg';
    if (nameLower.includes('elliptical') || nameLower.includes('cycle') || nameLower.includes('bike') || nameLower.includes('stair')) 
        return '/images/equipment/Elliptical.svg';
    if (nameLower.includes('bench press') || nameLower.includes('chest press') || nameLower.includes('shoulder press') || nameLower.includes('press') || nameLower.includes('push up') || nameLower.includes('dip')) 
        return '/images/equipment/BenchPress.svg';
    if (nameLower.includes('decline'))
        return '/images/equipment/DeclineBenchPress.svg';
    if (nameLower.includes('leg press') || nameLower.includes('leg extension') || nameLower.includes('leg curl') || nameLower.includes('calf')) 
        return '/images/equipment/LegPress.svg';
    if (nameLower.includes('smith') || nameLower.includes('squat') || nameLower.includes('deadlift') || nameLower.includes('row') || nameLower.includes('lat pulldown') || nameLower.includes('pull up') || nameLower.includes('pushdown') || nameLower.includes('back extension')) 
        return '/images/equipment/SmithMachine.svg';
    
    return null;
};
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Workouts" />

        <!-- Mode Selection View -->
        <div v-if="!mode && !isWorkoutSessionActive" class="min-h-full bg-[var(--page-bg)] p-6 flex flex-col items-center justify-center space-y-6 transition-colors">
            <div class="text-center mb-8">
                  <h1 class="text-3xl font-black uppercase italic tracking-tighter text-[var(--text-main)] leading-[0.85] mt-1 transition-colors">{{ t('workout.choose_mode') }}</h1>
                  <p class="text-[11px] font-bold text-[var(--text-muted)] uppercase tracking-wider mt-2 transition-colors">{{ t('workout.select_train') }}</p>
            </div>

            <!-- Today's Coaching Plan (For Active Trainees) -->
            <div v-if="activePackage" 
                class="w-full p-6 rounded-[40px] shadow-2xl mb-6 overflow-hidden relative group transition-all border-t-2 border-white/20" 
                :style="{ background: 'linear-gradient(135deg, var(--theme-color), #f97316)', boxShadow: '0 20px 40px rgba(var(--theme-color-rgb), 0.2)' }"
            >
                <div class="flex items-center gap-5 relative z-10">
                    <img :src="activePackage.trainer.user.profile_photo_url" class="size-16 rounded-[24px] border-2 border-white/30 object-cover shadow-xl group-hover:scale-105 transition-transform">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <span class="size-1.5 rounded-full bg-green-400 animate-pulse"></span>
                            <h3 class="text-white text-[10px] font-black uppercase tracking-[0.2em] opacity-80 leading-none">Today's Coaching Guide</h3>
                        </div>
                        <h2 class="text-white text-3xl font-black uppercase italic leading-[0.85] tracking-tighter truncate">
                            {{ todaySchedule?.focus_area || 'Daily Plan' }}
                        </h2>
                    </div>
                </div>
                
                <div class="mt-5 p-5 bg-white/10 rounded-[28px] backdrop-blur-md border border-white/20 relative z-10">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-white/40 text-sm mt-0.5">format_quote</span>
                        <p class="text-white text-[11px] font-bold leading-relaxed italic opacity-95">
                            {{ todaySchedule?.description || "Pick an exercise and let's crush it today! I'm here to support you." }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between relative z-10 px-1">
                    <span class="text-white/60 text-[9px] font-black uppercase tracking-widest italic flex items-center gap-1">
                        <span class="material-symbols-outlined text-[10px]">person</span>
                        {{ activePackage.trainer.user.name }}
                    </span>
                    <div class="flex items-center gap-2">
                    </div>
                </div>

                <!-- Abstract Decorations -->
                <div class="absolute -right-6 -bottom-6 opacity-10 rotate-12 group-hover:rotate-0 transition-transform duration-700">
                    <span class="material-symbols-outlined text-[120px] text-white">sports_martial_arts</span>
                </div>
                <div class="absolute -left-4 top-1/2 -translate-y-1/2 opacity-5 blur-sm">
                    <div class="size-40 rounded-full border-[20px] border-white"></div>
                </div>
            </div>

            <!-- Gym Mode Card -->
            <button @click="selectMode('gym')" class="w-full bg-[var(--card-bg)] p-8 rounded-[40px] shadow-xl border-4 border-transparent hover:border-[#00a18c] group transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="material-symbols-outlined text-9xl text-[#00a18c]">map</span>
                </div>
                <div class="relative z-10 flex flex-col items-start text-left">
                    <span class="bg-[#00a18c]/10 text-[#00a18c] px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider mb-4">{{ t('workout.interactive') }}</span>
                    <h2 class="text-3xl font-black uppercase italic text-[var(--text-main)] leading-none mb-2 transition-colors">{{ t('workout.gym_workout') }}</h2>
                    <p class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider max-w-[200px] transition-colors">
                        {{ t('workout.gym_subtitle') }}
                    </p>
                </div>
            </button>

            <!-- Free Mode Card -->
            <button @click="selectMode('free')" class="w-full bg-[var(--card-bg)] p-8 rounded-[40px] shadow-xl border-4 border-transparent hover:border-blue-500 group transition-all duration-300 relative overflow-hidden">
                 <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="material-symbols-outlined text-9xl text-blue-500">fitness_center</span>
                </div>
                <div class="relative z-10 flex flex-col items-start text-left">
                    <span class="bg-blue-500/10 text-blue-500 px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider mb-4">{{ t('workout.manual') }}</span>
                    <h2 class="text-3xl font-black uppercase italic text-[var(--text-main)] leading-none mb-2 transition-colors">{{ t('workout.free_workout') }}</h2>
                    <p class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider max-w-[200px] transition-colors">
                        {{ t('workout.free_subtitle') }}
                    </p>
                </div>
            </button>

            <!-- Trainer Mode Card -->
            <button @click="selectMode('trainer')" class="w-full bg-[var(--card-bg)] p-8 rounded-[40px] shadow-xl border-4 border-transparent hover:border-orange-500 group transition-all duration-300 relative overflow-hidden">
                 <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="material-symbols-outlined text-9xl text-orange-500">sports_martial_arts</span>
                </div>
                <div class="relative z-10 flex flex-col items-start text-left">
                    <span class="bg-orange-500/10 text-orange-500 px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider mb-4">{{ t('trainer.title') }}</span>
                    <h2 class="text-3xl font-black uppercase italic text-[var(--text-main)] leading-none mb-2 transition-colors">Personal Trainer</h2>
                    <p class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider max-w-[200px] transition-colors">
                        {{ t('trainer.motivation') }}
                    </p>
                </div>
            </button>
        </div>

        <!-- Trainer View -->
        <div v-else-if="mode === 'trainer' && !isWorkoutSessionActive" class="min-h-full bg-[var(--page-bg)] transition-colors">
            <TrainerTab 
                :trainers="trainers" 
                :bookings="bookings"
                :initial-trainer-id="initialTrainerId" 
                @back="mode = null; initialTrainerId = null" 
            />
        </div>

        <!-- Workout Session View (Guided Plan) -->
        <div v-else-if="isWorkoutSessionActive && activeWorkout" class="min-h-full bg-[var(--app-bg)] transition-colors">
            <header class="p-6 pb-2 flex items-center justify-between sticky top-0 bg-[var(--nav-bg)] backdrop-blur-md z-30 border-b border-[var(--border-color)] transition-colors">
                <div>
                    <h2 class="text-[10px] font-black uppercase tracking-wider mb-1"
                        :class="activeWorkout.sessionType === 'manual' ? 'text-blue-500' : 'text-[var(--theme-color)]'">
                        {{ activeWorkout.sessionType === 'manual' ? 'BUILD SESSION' : 'GUIDED PLAN' }}
                    </h2>
                    <h3 class="text-xl font-black uppercase italic text-[var(--text-main)] leading-tight transition-colors">{{ activeWorkout.title }}</h3>
                </div>
                <button @click="isWorkoutSessionActive = false" class="size-10 rounded-full bg-[var(--page-bg)] flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">close</span>
                </button>
            </header>

            <div class="px-6 py-4">
                <div v-for="exercise in activeWorkout.exercises" :key="exercise.name" 
                    class="bg-[var(--card-bg)] rounded-[40px] border border-[var(--border-color)] p-8 shadow-[0_8px_30px_rgba(0,0,0,0.04)] mb-10 space-y-8 last:mb-20 transition-colors">
                    <!-- Premium Exercise Header -->
                    <div class="flex gap-6 items-center">
                        <div class="size-28 rounded-[36px] bg-[var(--page-bg)] border border-[var(--border-color)] flex-shrink-0 flex items-center justify-center p-4 shadow-sm transition-colors">
                            <img v-if="exercise.image" :src="exercise.image" class="w-full h-full object-contain">
                            <div v-else class="flex flex-col items-center justify-center opacity-20 transition-colors">
                                <span class="material-symbols-outlined text-4xl text-[var(--text-main)]">fitness_center</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="mb-4">
                                <h4 class="text-[28px] font-black uppercase italic text-[var(--text-main)] leading-[0.9] tracking-tighter transition-colors">{{ exercise.name }}</h4>
                                <span v-if="exercise.isMerged" class="inline-block mt-2 px-2 py-0.5 rounded-md bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[8px] font-black uppercase italic tracking-normal">Merged</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <!-- Progress Box -->
                                <div class="flex flex-col items-center">
                                    <span class="text-[7px] font-black uppercase tracking-widest mb-1 px-1"
                                        :class="activeWorkout.sessionType === 'manual' ? 'text-blue-500' : 'text-[var(--theme-color)]'">
                                        {{ activeWorkout.sessionType === 'manual' ? 'SETS TOTAL' : 'SETS DONE' }}
                                    </span>
                                    <div class="px-3 py-1.5 rounded-xl border flex items-center justify-center min-w-[48px]"
                                        :class="activeWorkout.sessionType === 'manual' 
                                            ? 'bg-blue-50/50 border-blue-100 text-blue-500' 
                                            : 'bg-[var(--theme-color)]/10 border-[var(--theme-color)]/20 text-[var(--theme-color)]'">
                                        <span class="text-[11px] font-black uppercase italic">
                                            {{ activeWorkout.sessionType === 'manual' 
                                                ? exercise.workoutLogs.length 
                                                : exercise.workoutLogs.filter(s => s.completed).length + '/' + exercise.targetSets 
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <!-- Weight Box -->
                                <div class="flex flex-col items-center">
                                    <span class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider mb-1 px-1 transition-colors">Target</span>
                                    <div class="px-3 py-1.5 bg-[var(--page-bg)] rounded-xl border border-[var(--border-color)] flex items-center justify-center min-w-[48px] transition-colors">
                                        <span class="text-[11px] font-black text-[var(--text-main)] uppercase italic transition-colors">
                                            {{ exercise.targetWeight || (exercise.workoutLogs.length > 0 ? exercise.workoutLogs[0].weight : '0kg') }}
                                        </span>
                                    </div>
                                </div>
                                <!-- Reps Box -->
                                <div class="flex flex-col items-center">
                                    <span class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider mb-1 px-1 transition-colors">Reps</span>
                                    <div class="px-3 py-1.5 bg-[var(--page-bg)] rounded-xl border border-[var(--border-color)] flex items-center justify-center min-w-[48px] transition-colors">
                                        <span class="text-[11px] font-black text-[var(--text-main)] uppercase italic transition-colors">{{ exercise.reps }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-8 mb-4">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[var(--text-muted)] transition-colors">Workout Log</h4>
                        <button @click="addSet(exercise)" 
                            class="flex items-center gap-1.5 px-4 py-2 rounded-full border text-[9px] font-black uppercase tracking-wider active:scale-95 transition-all shadow-sm"
                            :style="{ borderColor: 'var(--theme-color)', color: 'var(--theme-color)', backgroundColor: 'rgba(var(--theme-color-rgb), 0.05)' }"
                        >
                            <span class="material-symbols-outlined text-[10px] font-bold">add</span>
                            ADD SET
                        </button>
                    </div>

                    <div class="space-y-3">
                        <!-- Table Header -->
                        <div class="flex items-center gap-3 px-1 text-[8px] font-black text-[var(--text-muted)] uppercase tracking-wider transition-colors">
                            <div class="size-10"></div>
                            <div class="flex-1 text-center">Weight (KG)</div>
                            <div class="w-16 text-center">Sets</div>
                            <div class="flex-1 text-center">Reps</div>
                            <div class="size-11"></div>
                        </div>

                        <div v-for="(set, setIdx) in exercise.workoutLogs" :key="set.id" 
                            class="flex items-center gap-3 transition-all duration-300"
                            :class="{ 'opacity-40': set.completed }">
                            
                            <!-- Delete Button -->
                            <button @click="removeSet(exercise, setIdx)" class="size-10 flex items-center justify-center text-gray-200 hover:text-red-500 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>

                            <!-- Weight Box -->
                            <div class="flex-1 h-14 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center px-1 overflow-hidden transition-colors">
                                <select v-model="set.weight" class="w-full text-sm font-black text-[var(--text-main)] border-none p-0 focus:ring-0 text-center bg-transparent italic uppercase appearance-none text-center-last transition-colors">
                                    <option v-for="opt in weightOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </div>

                            <!-- Set Box -->
                            <div class="w-16 h-14 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center transition-colors">
                                <span class="text-base font-black text-[var(--text-main)] italic transition-colors">{{ setIdx + 1 }}</span>
                            </div>

                            <!-- Reps Box -->
                            <div class="flex-1 h-14 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center px-2 transition-colors">
                                <input v-model="set.reps" class="w-full text-base font-black text-[var(--text-main)] border-none p-0 focus:ring-0 text-center bg-transparent italic transition-colors" placeholder="0">
                            </div>

                            <!-- Done Button -->
                            <button @click="toggleSet(set)" 
                                class="size-11 rounded-full flex items-center justify-center transition-all border shadow-sm active:scale-95"
                                :style="set.completed ? { backgroundColor: 'var(--theme-color)', borderColor: 'var(--theme-color)' } : {}"
                                :class="set.completed ? 'text-white' : 'bg-[var(--card-bg)] border-[var(--border-color)] text-[var(--text-muted)]/30'">
                                <span class="material-symbols-outlined text-xl font-bold">{{ set.completed ? 'check_circle' : 'check' }}</span>
                            </button>
                        </div>
                        
                        <!-- Empty Sets State -->
                        <div v-if="exercise.workoutLogs.length === 0" class="py-12 border-2 border-dashed border-[var(--border-color)] rounded-[32px] flex flex-col items-center justify-center opacity-30 transition-colors">
                             <span class="material-symbols-outlined text-4xl mb-3 text-[var(--text-muted)]">add_task</span>
                             <p class="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)] text-center">No sets added yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 flex flex-col gap-4 mt-4 transition-colors">
                <button @click="saveAsCustomPlan(activeWorkout)" 
                    class="w-full py-4 rounded-[24px] bg-[var(--card-bg)] border-2 border-blue-600 text-blue-600 font-black uppercase tracking-wider shadow-sm active:scale-95 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">bookmark</span>
                    Save as My Plan
                </button>
                <button @click="finishWorkout" 
                    class="w-full py-5 rounded-[24px] bg-[var(--text-main)] text-[var(--card-bg)] font-black uppercase tracking-wider shadow-xl active:scale-95 transition-all">
                    Finish & Log Session
                </button>
            </div>
        </div>

        <!-- Selection View -->
        <div v-else class="bg-[var(--page-bg)] min-h-screen pb-32 transition-colors">
            <!-- Header Tabs -->
            <header class="p-6 pb-2 transition-colors">
                <div class="flex flex-col items-center mb-4 transition-colors">
                    <p class="text-xs font-black uppercase tracking-wider text-blue-500 mb-1 transition-colors">{{ t('workout.manual') }}</p>
                    <h1 class="text-[26px] font-black uppercase italic tracking-tighter text-[var(--text-main)] leading-none transition-colors">{{ t('workout.search_exercises') }}</h1>
                </div>

                <!-- Simple Back Button -->
                <button @click="mode = null" class="absolute left-6 top-8 size-9 rounded-full flex items-center justify-center border border-[var(--border-color)] bg-[var(--card-bg)] shadow-sm transition-all">
                    <span class="material-symbols-outlined text-[var(--text-muted)] text-xl">arrow_back</span>
                </button>

                <!-- Tabs -->
                <div class="flex gap-8 border-b border-[var(--border-color)] px-2 transition-colors">
                    <button @click="activeTab = 'equipments'; selectedWorkoutIds = []" 
                        :class="['pb-4 text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap', activeTab === 'equipments' ? 'text-[var(--text-main)] border-b-2 border-[var(--text-main)]' : 'text-[var(--text-muted)]']">
                        {{ t('workout.equipment_pool') }}
                    </button>
                    <button @click="activeTab = 'plans'; selectedWorkoutIds = []" 
                        :class="['pb-4 text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap', activeTab === 'plans' ? 'text-[var(--text-main)] border-b-2 border-[var(--text-main)]' : 'text-[var(--text-muted)]']">
                        {{ t('workout.my_plans') }}
                    </button>
                    <button @click="activeTab = 'history'" 
                        :class="['pb-4 text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap', activeTab === 'history' ? 'text-[var(--text-main)] border-b-2 border-[var(--text-main)]' : 'text-[var(--text-muted)]']">
                        {{ t('workout.history') }}
                    </button>
                </div>
            </header>

            <div v-if="activeTab !== 'history'" class="px-6 mt-4 transition-colors">
                 <div class="relative transition-colors">
                    <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] text-xl transition-colors">search</span>
                    <input v-model="searchQuery" 
                        type="text" 
                        :placeholder="t('workout.search_exercises') + '...'" 
                        class="w-full pl-12 pr-6 py-4 bg-[var(--card-bg)] border border-[var(--border-color)] rounded-full text-[13px] text-[var(--text-main)] focus:ring-0 transition-all shadow-[0_2px_10px_rgba(0,0,0,0.02)] outline-none placeholder:text-[var(--text-muted)]/40"
                    >
                 </div>
            </div>

            <!-- Main View Content -->
            <div v-if="activeTab === 'equipments' || activeTab === 'plans'" class="transition-colors">
                <div class="py-6 transition-colors">
                    <div class="px-8 flex items-center justify-between mb-2 transition-colors">
                        <div class="flex flex-col transition-colors">
                            <h3 class="text-lg font-black uppercase italic text-[var(--text-main)] tracking-tight leading-none transition-colors">{{ t('workout.build_session') }}</h3>
                            <p class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-wider mt-1 transition-colors">
                                {{ selectedWorkoutIds.length }} {{ t('workout.items_selected') }}
                            </p>
                        </div>
                    </div>

                    <!-- List View -->
                    <div class="px-6 space-y-4 pb-8 transition-colors">
                        <div v-for="workout in (activeTab === 'equipments' ? allEquipments : userSavedPlans)" :key="workout.id" 
                            @click="toggleSelection(workout.id)"
                            class="bg-[var(--card-bg)] p-4 rounded-[32px] border transition-all duration-300 flex items-center gap-4 group"
                            :class="selectedWorkoutIds.includes(workout.id) ? 'border-[var(--theme-color)] ring-1 ring-[var(--theme-color)]/5' : 'border-transparent shadow-[0_2px_8px_rgba(0,0,0,0.015)]'">
                            
                            <div class="size-16 rounded-[24px] overflow-hidden relative border border-[var(--border-color)] flex-shrink-0 flex items-center justify-center bg-[var(--page-bg)] transition-colors">
                                <img v-if="workout.image && !workout.isCustom" :src="workout.image" class="w-full h-full object-contain">
                                <div v-else class="flex flex-col items-center justify-center text-[var(--theme-color)] transition-opacity opacity-60">
                                    <span class="material-symbols-outlined text-3xl">fitness_center</span>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0 transition-colors">
                                <span class="text-[6px] font-black uppercase tracking-[0.1em] text-[var(--theme-color)] opacity-60 transition-colors">
                                    {{ workout.gymName }}
                                </span>
                                <h4 class="text-[17px] font-black uppercase italic text-[var(--text-main)] truncate leading-none tracking-tight my-0.5 transition-colors">{{ workout.title }}</h4>
                                <p class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider transition-colors">
                                    {{ workout.exercises?.length || 1 }} EXERCISES
                                </p>
                            </div>

                            <div class="flex items-center transition-colors">
                                <!-- Selection Circle -->
                                <div class="size-8 rounded-full flex items-center justify-center border transition-all"
                                    :class="selectedWorkoutIds.includes(workout.id) ? 'bg-[var(--theme-color)] border-[var(--theme-color)] text-white shadow-lg' : 'border-[var(--border-color)] text-transparent'">
                                    <span v-if="selectedWorkoutIds.includes(workout.id)" class="material-symbols-outlined text-white text-lg font-black">check</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Results -->
                    <div v-if="(activeTab === 'equipments' ? allEquipments : userSavedPlans).length === 0" class="py-20 text-center opacity-20 transition-colors">
                         <span class="material-symbols-outlined text-6xl transition-colors">bookmark_border</span>
                         <p class="text-xs font-black uppercase tracking-wider mt-4">
                             {{ activeTab === 'plans' ? t('workout.no_saved_plans') : t('workout.no_results') }}
                         </p>
                    </div>
                </div>
            </div>

            <!-- History View (Unified) -->
            <div v-else-if="activeTab === 'history'" class="px-6 py-4 space-y-12 transition-colors">
                <div v-if="mergedHistory.length === 0" class="py-20 text-center opacity-30 transition-colors">
                    <span class="material-symbols-outlined text-6xl text-[var(--text-muted)]">history</span>
                    <p class="text-xs font-black uppercase tracking-wider mt-4 text-[var(--text-muted)]">No workout history yet</p>
                </div>
                
                <div v-for="group in groupedHistory" :key="group.date" class="space-y-8 transition-colors">
                    <!-- Day Header -->
                    <div class="px-2 transition-colors">
                        <span class="text-[10px] font-black text-[var(--theme-color)] uppercase tracking-wider block transition-colors">{{ group.date }}</span>
                    </div>

                    <div v-for="entry in group.entries" :key="entry.id" class="space-y-6 transition-colors">
                        <!-- Session Sub-header -->
                        <div class="px-2 flex items-center justify-between transition-colors">
                             <h4 class="text-2xl font-black uppercase italic text-[var(--text-main)] leading-none tracking-tight transition-colors">{{ entry.title || 'Workout Session' }}</h4>
                             <button @click="deleteHistoryEntry(entry.id)" class="size-10 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center active:scale-95 transition-all">
                                 <span class="material-symbols-outlined text-xl">delete</span>
                             </button>
                        </div>
                        
                        <!-- Detailed Exercise List -->
                        <div v-if="entry.exercises && entry.exercises.length > 0" class="space-y-4 transition-colors">
                            <div v-for="ex in entry.exercises" :key="ex.name" class="flex gap-4 items-start bg-[var(--card-bg)] rounded-[32px] p-5 border border-[var(--border-color)] shadow-sm transition-colors">
                                <!-- Machine Image -->
                                <div class="size-20 rounded-2xl bg-[var(--page-bg)] overflow-hidden border border-[var(--border-color)] flex-shrink-0 p-2 shadow-inner transition-colors">
                                    <img v-if="getExerciseImage(ex)" :src="getExerciseImage(ex)" class="w-full h-full object-contain">
                                    <div v-else class="w-full h-full flex items-center justify-center opacity-10 transition-colors">
                                        <span class="material-symbols-outlined text-2xl text-[var(--text-main)] transition-colors">fitness_center</span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0 pt-1 transition-colors">
                                    <h5 class="text-xs font-black uppercase italic text-[var(--text-main)] mb-3 truncate tracking-tight transition-colors">{{ ex.name }}</h5>
                                    <!-- History Set Logs (New Labeled Box Style) -->
                                    <div class="flex flex-col gap-4 transition-colors">
                                        <div v-for="(set, idx) in ex.sets" :key="idx" class="flex gap-2 items-end transition-colors">
                                            <!-- Weight Group -->
                                            <div class="flex-1 flex flex-col gap-1 transition-colors">
                                                <span class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider pl-1 transition-colors">Weight</span>
                                                <div class="bg-[var(--card-bg)] rounded-xl border border-[var(--border-color)] py-1.5 flex items-center justify-center transition-colors">
                                                    <span class="text-[10px] font-black text-[var(--text-muted)] tracking-wider transition-colors">{{ set.weight }}</span>
                                                </div>
                                            </div>

                                            <!-- Sets Group -->
                                            <div class="w-10 flex flex-col gap-1 items-center transition-colors">
                                                <span class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider transition-colors">Sets</span>
                                                <div class="w-full bg-[var(--card-bg)] rounded-xl border border-[var(--border-color)] py-1.5 flex items-center justify-center transition-colors">
                                                    <span class="text-[10px] font-black text-[var(--text-muted)]/40 font-mono transition-colors">{{ idx + 1 }}</span>
                                                </div>
                                            </div>

                                            <!-- Reps Group -->
                                            <div class="flex-1 flex flex-col gap-1 transition-colors">
                                                <span class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider pl-1 transition-colors">Reps</span>
                                                <div class="bg-[var(--card-bg)] rounded-xl border border-[var(--border-color)] py-1.5 flex items-center justify-center transition-colors">
                                                    <span class="text-[10px] font-black text-[var(--text-muted)] tracking-wider transition-colors">{{ set.reps }}</span>
                                                </div>
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
                <div v-if="selectedWorkoutIds.length > 0" class="fixed bottom-24 left-10 right-10 z-40 flex justify-center transition-colors">
                    <button @click="startCombinedWorkout" 
                    class="w-full max-w-[280px] py-4 rounded-full text-white font-black uppercase tracking-[0.2em] flex items-center justify-center gap-3 active:scale-95 hover:brightness-110 transition-all text-xs italic"
                    :style="{ backgroundColor: 'var(--theme-color)', boxShadow: '0 15px 30px rgba(var(--theme-color-rgb), 0.3)' }"
                >
                    <span class="material-symbols-outlined fill-icon text-lg">play_arrow</span>
                    {{ t('workout.start') }} ({{ selectedWorkoutIds.length }})
                </button>
                </div>
            </transition>
        </div>

        <!-- Success Toast (Finish Workout) -->
        <transition 
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="translate-y-[-100%] opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-[-100%] opacity-0"
        >
            <div v-if="isShowSuccessMessage" class="fixed top-6 left-1/2 -translate-x-1/2 z-[200] pointer-events-none w-max">
                <div class="bg-[#111827] text-white px-5 py-3 rounded-full shadow-2xl flex items-center gap-3 border border-white/10 backdrop-blur-md">
                    <div class="size-6 rounded-full bg-[#ec5b13] flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-[14px] font-black">check</span>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider whitespace-nowrap">บันทึกข้อมูลสำเร็จ</span>
                </div>
            </div>
        </transition>

        <!-- Finish Workout Confirmation Modal -->
        <transition name="modal">
            <div v-if="isShowFinishConfirm" class="fixed inset-0 z-[150] flex items-center justify-center p-8 bg-black/60 backdrop-blur-sm">
                <div class="bg-[var(--card-bg)] w-full max-w-[320px] rounded-[40px] overflow-hidden shadow-2xl relative animate-in zoom-in-95 duration-300 border border-[var(--border-color)]">
                    <div class="p-8 text-center space-y-6 transition-colors">
                        <div class="size-20 rounded-full bg-orange-500/10 flex items-center justify-center mx-auto transition-colors">
                            <span class="material-symbols-outlined text-4xl text-orange-500">logout</span>
                        </div>
                        <div class="space-y-2">
                            <h4 class="text-xl font-black uppercase italic text-[var(--text-main)] transition-colors">Finish Workout?</h4>
                            <p class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-wider leading-relaxed transition-colors">
                                ต้องการหยุดการออกกำลังกาย<br/>แล้วใช่มั้ย?
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button @click="isShowFinishConfirm = false" 
                                class="py-4 rounded-3xl bg-[var(--page-bg)] border border-[var(--border-color)] text-[var(--text-main)] text-[10px] font-black uppercase tracking-wider active:scale-95 transition-all">
                                CANCEL
                            </button>
                            <button @click="confirmFinishWorkout" 
                                class="py-4 rounded-3xl bg-orange-500 text-white text-[10px] font-black uppercase tracking-wider active:scale-95 transition-all shadow-lg shadow-orange-500/20">
                                FINISH
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </MobileLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.fill-icon { font-variation-settings: 'FILL' 1; }
.up-enter-active, .up-leave-active { transition: all 0.3s ease; }
.up-enter-from, .up-leave-to { transform: translateY(20px); opacity: 0; }
</style>
