<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, onMounted, computed, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    gym: Object,
    equipments: Array,
});

// --- State Management ---
const selectedItem = ref(null);
const isModalOpen = ref(false);
const isZoomEnabled = ref(false);
const activeMuscle = ref(null);
const isPlaying = ref(false);
const isThemeDark = ref(false);
const isProgramModalOpen = ref(false);
const weightOptions = Array.from({ length: 80 }, (_, i) => ((i + 1) * 2.5).toFixed(1).replace(/\.0$/, '') + 'kg');

const selectedPlanInfo = ref(null); // { category: string, targetSets: number, targetReps: number }
const workoutHistory = ref([]);
const activeMuscleFilter = ref(null); // Muscle group selected for highlighting (Back, Arms, etc.)
const isShowFinishConfirm = ref(false);
const isShowSuccessMessage = ref(false);

// --- Workout Tracking ---
// sessionLog stores the history of this session: { itemId: { name, image, sets: [] } }
const sessionLog = ref({});
const workoutSets = ref([]);
const markedItemIds = ref([]); // Multiple machine pins

const formatImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/images/') || path.startsWith('data:')) return path;
    return `/storage/${path}`;
};

const addNewSet = () => {
    const lastSet = workoutSets.value[workoutSets.value.length - 1];
    workoutSets.value.push({
        weight: lastSet ? lastSet.weight : '10kg',
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

const saveCurrentSetsToLog = () => {
    if (selectedItem.value) {
        const hasProgress = workoutSets.value.some(s => s.isCompleted);
        const alreadyInLog = !!sessionLog.value[selectedItem.value.id];

        // Only save if there's actual progress OR it was already started (e.g. from Program list)
        if (hasProgress || alreadyInLog) {
            sessionLog.value[selectedItem.value.id] = {
                id: selectedItem.value.id,
                name: getEquipmentInfo(selectedItem.value)?.name || selectedItem.value.name,
                image: getEquipmentInfo(selectedItem.value)?.image || selectedItem.value.src,
                sets: JSON.parse(JSON.stringify(workoutSets.value)),
                targetSets: selectedPlanInfo.value?.targetSets || 3,
                targetWeight: selectedPlanInfo.value?.targetWeight || 10,
                reps: selectedPlanInfo.value?.targetReps || 12
            };
        }
    }
    isModalOpen.value = false;
    selectedPlanInfo.value = null;
};

// --- Recommendation Accordion Logic ---
const normalizeWeight = (val) => {
    if (typeof val === 'string' && val.toLowerCase().endsWith('kg')) return val;
    let num = parseFloat(val) || 10;
    return num.toFixed(1).replace(/\.0$/, '') + 'kg';
};

const getMapItemForEx = (exercise) => {
    if (selectedItem.value) {
        const info = getEquipmentInfo(selectedItem.value);
        const nameLower = exercise.name.toLowerCase();
        const matches = (selectedItem.value.name && selectedItem.value.name.toLowerCase() === nameLower) || 
                       (info && info.name && info.name.toLowerCase() === nameLower) ||
                       (info && info.name_th && info.name_th === exercise.name);
        if (matches) return selectedItem.value;
    }

    const inProgressMatch = props.gym.items.find(i => {
        if (!sessionLog.value[i.id]) return false;
        const info = getEquipmentInfo(i);
        const nameLower = exercise.name.toLowerCase();
        return (i.name && i.name.toLowerCase() === nameLower) || 
               (info && info.name && info.name.toLowerCase() === nameLower) ||
               (info && info.name_th && info.name_th === exercise.name);
    });
    if (inProgressMatch) return inProgressMatch;

    return props.gym.items.find(i => {
         const info = getEquipmentInfo(i);
         const nameLower = exercise.name.toLowerCase();
         return (i.name && i.name.toLowerCase() === nameLower) || 
                (info && info.name && info.name.toLowerCase() === nameLower) ||
                (info && info.name_th && info.name_th === exercise.name);
    });
};

const initSessionForProgramEx = (ex) => {
    const item = getMapItemForEx(ex);

    if (item && !sessionLog.value[item.id]) {
        sessionLog.value[item.id] = {
            id: item.id,
            name: ex.name,
            image: getEquipmentInfo(item)?.image || item.src,
            sets: Array.from({ length: parseInt(ex.sets) || 3 }, () => ({
                weight: normalizeWeight(ex.targetWeight || 10),
                reps: parseInt(ex.reps) || 12,
                isCompleted: false
            }))
        };
    }
    return item;
};

const logSessionCount = computed(() => {
    return Object.values(sessionLog.value).filter(entry => entry.sets.some(s => s.isCompleted)).length;
});

const isExerciseCompleted = (itemId) => {
    const log = sessionLog.value[itemId];
    return log && log.sets.length > 0 && log.sets.every(s => s.isCompleted);
};

const getSessionLog = (ex) => {
    const item = getMapItemForEx(ex);
    return item ? sessionLog.value[item.id] : null;
};

const addSetToSession = (itemId) => {
    const sets = sessionLog.value[itemId].sets;
    const lastSet = sets[sets.length - 1];
    sets.push({
        weight: lastSet ? lastSet.weight : '10kg',
        reps: lastSet ? lastSet.reps : 12,
        isCompleted: false
    });
};

const removeSetFromSession = (itemId, index) => {
    if (sessionLog.value[itemId].sets.length > 1) {
        sessionLog.value[itemId].sets.splice(index, 1);
    }
};

const toggleSetInSession = (itemId, index) => {
    sessionLog.value[itemId].sets[index].isCompleted = !sessionLog.value[itemId].sets[index].isCompleted;
};

const finishWorkout = () => {
    const exercises = Object.values(sessionLog.value)
        .filter(entry => entry.sets.some(s => s.isCompleted));
    
    if (exercises.length === 0) return;
    isShowFinishConfirm.value = true;
};

const confirmFinishWorkout = async () => {
    const exercises = Object.values(sessionLog.value)
        .filter(entry => entry.sets.some(s => s.isCompleted))
        .map(entry => ({
            name: entry.name,
            image: entry.image,
            sets: entry.sets.filter(s => s.isCompleted).map(s => ({
                weight: s.weight.toLowerCase().includes('kg') ? s.weight : s.weight + 'kg',
                reps: s.reps
            }))
        }));

    const totalSets = exercises.reduce((sum, ex) => sum + ex.sets.length, 0);

    const sessionData = {
        workout_date: new Date().toISOString().split('T')[0],
        data: {
            title: `${props.gym.name} Workout`,
            exercises: exercises,
            sets: totalSets
        }
    };

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await axios.post('/api/workout-sessions', sessionData);
        const newSession = response.data; // Server usually returns the created session
        
        // Sync local storage history immediately if server returns it
        const savedHistory = localStorage.getItem('fitpung_workout_history');
        const history = savedHistory ? JSON.parse(savedHistory) : [];
        
        const localEntry = {
            id: newSession.id || Date.now(),
            apiId: newSession.id,
            date: new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }),
            title: sessionData.data.title,
            exercises: exercises,
            sets: totalSets
        };
        
        history.unshift(localEntry);
        localStorage.setItem('fitpung_workout_history', JSON.stringify(history));

        const savedSets = localStorage.getItem('fitpung_sets_done');
        const currentTotal = savedSets ? parseInt(savedSets) : 0;
        localStorage.setItem('fitpung_sets_done', (currentTotal + totalSets).toString());

        isShowFinishConfirm.value = false;
        isShowSuccessMessage.value = true;

        setTimeout(() => {
            router.get(route('mobile.workout'));
        }, 2000);
    } catch (e) {
        console.error("Failed to save workout session from Map", e);
        alert("บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง");
    }
};


// --- Map Logic (Panning/Zooming) ---
const viewBox = ref({ x: 0, y: 0, w: 1000, h: 800 });
const isPanning = ref(false);
const lastMousePos = ref({ x: 0, y: 0 });

const getInitialBounds = (pointsStr, items = [], leftPadding = 40, rightPadding = 40, topPadding = 40, bottomPadding = 40) => {
    const defaultBounds = { x: 0, y: 0, w: 1000, h: 800 };
    if (!pointsStr) return defaultBounds;

    const points = pointsStr.trim().split(/\s+/).map(p => { 
        const parts = p.split(',').map(n => parseFloat(n));
        return parts.length < 2 ? null : { x: parts[0], y: parts[1] }; 
    }).filter(p => p !== null);

    if (points.length === 0) return defaultBounds;

    let minX = Math.min(...points.map(p => p.x));
    let maxX = Math.max(...points.map(p => p.x));
    let minY = Math.min(...points.map(p => p.y));
    let maxY = Math.max(...points.map(p => p.y));

    if (items && items.length > 0) {
        items.forEach(item => {
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

    const targetW = (maxX - minX) + leftPadding + rightPadding;
    const targetH = (maxY - minY) + topPadding + bottomPadding;
    
    return { 
        x: minX - leftPadding,
        y: minY - topPadding, 
        w: targetW,
        h: targetH 
    };
};

const viewBoxString = computed(() => `${viewBox.value.x} ${viewBox.value.y} ${viewBox.value.w} ${viewBox.value.h}`);

const getSvgPoint = (clientX, clientY) => {
    const svg = document.getElementById('technique-canvas');
    if (!svg) return { x: 0, y: 0 };
    const pt = svg.createSVGPoint();
    pt.x = clientX; pt.y = clientY;
    const ctm = svg.getScreenCTM();
    if (!ctm) return { x: 0, y: 0 };
    return pt.matrixTransform(ctm.inverse());
};

const handleWheel = (event) => {
    if (!isZoomEnabled.value) return; 
    const svgP = getSvgPoint(event.clientX, event.clientY);
    const direction = event.deltaY > 0 ? 1 : -1;
    const newW = viewBox.value.w * (1 + direction * 0.1);
    const newH = viewBox.value.h * (1 + direction * 0.1);
    if (newW < 200 || newW > 10000) return;

    const mouseRelX = (svgP.x - viewBox.value.x) / viewBox.value.w;
    const mouseRelY = (svgP.y - viewBox.value.y) / viewBox.value.h;

    viewBox.value.x = svgP.x - (mouseRelX * newW);
    viewBox.value.y = svgP.y - (mouseRelY * newH);
    viewBox.value.w = newW;
    viewBox.value.h = newH;
};

const handleMouseDown = (event) => {
    if (!isZoomEnabled.value) return;
    if (event.target.closest('.interactive-item')) return;
    isPanning.value = true;
    lastMousePos.value = { x: event.clientX, y: event.clientY };
};

const handleMouseMove = (event) => {
    if (!isPanning.value) return;
    const dx = event.clientX - lastMousePos.value.x;
    const dy = event.clientY - lastMousePos.value.y;
    const svg = document.getElementById('technique-canvas');
    if (!svg) return;
    const scaleX = viewBox.value.w / svg.clientWidth;
    const scaleY = viewBox.value.h / svg.clientHeight;
    viewBox.value.x -= dx * scaleX;
    viewBox.value.y -= dy * scaleY;
    lastMousePos.value = { x: event.clientX, y: event.clientY };
};

const handleMouseUp = () => {
    isPanning.value = false;
};

const resetZoom = () => {
    viewBox.value = getInitialBounds(props.gym.room_config.points, props.gym.items, 120, 20, 60, 810);
};

// --- Interaction Helpers ---
const selectItem = (item, customSets = null, planInfo = null) => {
    selectedItem.value = item;
    activeMuscle.value = null;
    isPlaying.value = false;
    selectedPlanInfo.value = planInfo;
    
    if (sessionLog.value[item.id]) {
        workoutSets.value = JSON.parse(JSON.stringify(sessionLog.value[item.id].sets));
    } else if (customSets && Array.isArray(customSets) && customSets.length > 0) {
        workoutSets.value = customSets.map(s => ({
            weight: normalizeWeight(s.weight || 10),
            reps: s.reps || 12,
            isCompleted: false
        }));
    } else if (item.preset_sets && Array.isArray(item.preset_sets) && item.preset_sets.length > 0) {
        workoutSets.value = item.preset_sets.map(s => ({
            weight: normalizeWeight(s.weight || 10),
            reps: s.reps || 12,
            isCompleted: false
        }));
    } else {
        const info = getEquipmentInfo(item);
        workoutSets.value = [{ 
            weight: normalizeWeight(info?.target_weight || 10), 
            reps: info?.reps || 12, 
            isCompleted: false 
        }];
    }
    isModalOpen.value = true;
};

const toggleProgramEx = (ex) => {
    if (expandedProgramExName.value === ex.name) {
        expandedProgramExName.value = null;
    } else {
        initSessionForProgramEx(ex);
        expandedProgramExName.value = ex.name;
    }
};

// Reordered to top

const getExerciseImage = (ex) => {
    if (!ex) return null;
    
    // 1. Try to get image from map item first
    const item = getMapItemForEx(ex);
    const infoFromMap = item ? getEquipmentInfo(item) : null;
    if (infoFromMap && infoFromMap.image) return infoFromMap.image;
    
    // 2. Try to get image from equipments list by name match
    const nameLower = ex.name.toLowerCase();
    const directMatch = props.equipments.find(e => 
        (e.name && e.name.toLowerCase() === nameLower) || 
        (e.name_th && e.name_th === ex.name)
    );
    if (directMatch && directMatch.image) return directMatch.image;

    // 3. Fallbacks
    return ex.image || (item ? item.src : null);
};

const locateProgramExercise = (exercise) => {
    const item = getMapItemForEx(exercise);

    if (item) {
        const id = item.id;
        const index = markedItemIds.value.indexOf(id);
        if (index === -1) {
            markedItemIds.value.push(id);
        } else {
            markedItemIds.value.splice(index, 1);
        }
        // Do not close modal - allow multiple marking
    } else {
        alert('Equipment not found on the map for: ' + exercise.name);
    }
};

const selectProgramExercise = (exercise) => {
    // 1. Find the map item matching the exercise name
    const item = getMapItemForEx(exercise);

    if (item) {
        // 2. Initialize session for this exercise if not exists
        initSessionForProgramEx(exercise);
        
        // 3. Prepare plan info for the detail modal
        const planInfo = {
            category: exercise.category || 'Owner Recommended',
            targetSets: parseInt(exercise.sets) || 3,
            targetWeight: parseFloat(exercise.targetWeight) || 10,
            targetReps: parseInt(exercise.reps) || 12,
            isRecommended: true
        };

        // 4. Open the main detail modal and close the list
        selectItem(item, null, planInfo);
        isProgramModalOpen.value = false;
    } else {
        alert('Equipment not found on the map for: ' + exercise.name);
    }
};

const getEquipmentInfo = (item) => {
    if (!item) return null;
    
    // 1. Try matching by filename in src
    let match = null;
    if (item.src) {
        const filename = item.src.split('/').pop().toLowerCase();
        match = props.equipments.find(e => e.filename && e.filename.toLowerCase() === filename);
    }
    
    // 2. If not found, try matching by name or name_th
    if (!match && item.name) {
        const nameLower = item.name.toLowerCase();
        match = props.equipments.find(e => 
            (e.name && e.name.toLowerCase() === nameLower) || 
            (e.name_th && e.name_th === item.name)
        );
    }
    
    return match;
};

const handleMuscleClick = (muscleKey) => {
    activeMuscle.value = muscleKey;
    isPlaying.value = true;
};

const muscleVideoInfo = computed(() => {
    if (!activeMuscle.value) return null;
    const key = String(activeMuscle.value).toLowerCase();
    if (key.includes('bicep') || key.includes('หน้าแขน')) return { id: 'EjUnEEfTSEY', title: 'Concentration Curl' };
    if (key.includes('shoulder') || key.includes('ไหล่') || key.includes('deltoid')) return { id: 'Kl3LEzQ5Zqs', title: 'Dumbbell Lateral Raise' };
    if (key.includes('tricep') || key.includes('หลังแขน')) return { id: 'iuYB_fLp26Q', title: 'Dumbbell Skull Crusher' };
    if (key.includes('calf') || key.includes('หน่อง') || key.includes('calve')) return { id: 'SRUtMJ0tE2A', title: 'Dumbbell Calf Raises' };
    if (key.includes('quad') || key.includes('thigh') || key.includes('glute') || key.includes('ขา') || key.includes('ก้น')) return { id: 'meJSJEG_sT0', title: 'Goblet Squat' };
    return null;
});

// Removed redundant logSessionCount

const ownerPlans = computed(() => {
    if (!props.gym.recommendations || !Array.isArray(props.gym.recommendations)) return [];
    return props.gym.recommendations;
});

const groupedOwnerPlans = computed(() => {
    const plans = ownerPlans.value;
    const groups = {};
    
    plans.forEach(plan => {
        // Use the plan title as the group key
        const groupName = plan.title || 'Other';
        
        if (!groups[groupName]) {
            groups[groupName] = {
                name: groupName,
                badge: 'OWNER RECOMMENDED',
                exercises: []
            };
        }
        
        if (plan.exercises) {
            plan.exercises.forEach(ex => {
                const normalizedEx = typeof ex === 'string' ? { name: ex } : ex;
                groups[groupName].exercises.push({
                    ...normalizedEx,
                    category: groupName
                });
            });
        }
    });
    
    return groups;
});

// --- Muscle Groups & Recommendations Logic ---
const categorizeMuscle = (name) => {
    const n = (name || '').toLowerCase();
    // Cardio: วิ่ง, เดิน, จักรยาน, คาอิโอ, cardio, run, walk, elliptical, stair
    if (n.includes('cardio') || n.includes('run') || n.includes('walk') || n.includes('elliptical') || n.includes('stair') || n.includes('cycle') || n.includes('bike') || n.includes('วิ่ง') || n.includes('เดิน') || n.includes('จักรยาน') || n.includes('คาดิโอ') || n.includes('threadmill')) return 'Cardio';
    // Legs: ขา, ก้น, น่อง
    if (n.includes('squat') || n.includes('leg') || n.includes('calf') || n.includes('lunge') || n.includes('glute') || n.includes('hip') || n.includes('thigh') || n.includes('quad') || n.includes('ขา') || n.includes('ก้น') || n.includes('น่อง') || n.includes('hamstring')) return 'Legs';
    // Back: หลัง, ปีก (Still categorize for logic, but UI will show Cardio)
    if (n.includes('row') || n.includes('pull') || n.includes('deadlift') || n.includes('lat') || n.includes('back') || n.includes('หลัง') || n.includes('ปีก')) return 'Back';
    // Chest: อก
    if (n.includes('bench') || n.includes('press') || n.includes('push') || n.includes('chest') || n.includes('อก')) return 'Chest';
    // Arms: แขน, หน้าแขน, หลังแขน
    if (n.includes('bicep') || n.includes('tricep') || n.includes('arm') || n.includes('curl') || n.includes('แขน') || n.includes('หน้าแขน') || n.includes('หลังแขน') || n.includes('dip')) return 'Arms';
    
    return 'Other';
};

const lastWorkout = computed(() => workoutHistory.value[0] || null);

const recentMuscles = computed(() => {
    if (!lastWorkout.value) return [];
    const muscles = new Set();
    lastWorkout.value.exercises?.forEach(ex => {
        const cat = categorizeMuscle(ex.name);
        if (cat !== 'Other') muscles.add(cat);
    });
    return Array.from(muscles);
});

const recommendedMuscles = computed(() => {
    const muscles = new Set();
    ownerPlans.value.forEach(plan => {
        plan.exercises?.forEach(ex => {
            const name = typeof ex === 'string' ? ex : ex.name;
            const cat = categorizeMuscle(name);
            if (cat !== 'Other') muscles.add(cat);
        });
    });
    // Ensure all 4 major groups are accounted for in the UI if we want to show badges for all
    return ['Cardio', 'Arms', 'Legs', 'Chest'].filter(m => muscles.has(m));
});

const muscleMapping = {
    'Cardio': { th: 'คาดิโอ', icon: '❤️', isEmoji: true },
    'Arms': { th: 'แขน', icon: '💪🏻', isEmoji: true },
    'Legs': { th: 'ขา', icon: '🦵🏻', isEmoji: true },
    'Chest': { th: 'อก', icon: '🏋🏻‍♂️', isEmoji: true }
};

const toggleMuscleFilter = (muscle) => {
    if (activeMuscleFilter.value === muscle) {
        activeMuscleFilter.value = null;
    } else {
        activeMuscleFilter.value = muscle;
    }
};

const isMachineTargetingFilteredMuscle = (item) => {
    if (!activeMuscleFilter.value) return false;
    
    const info = getEquipmentInfo(item);
    
    // 1. Check the primary name of the equipment
    const targetName = info?.name || info?.name_th || item.name;
    if (categorizeMuscle(targetName) === activeMuscleFilter.value) return true;
    
    // 2. Check all target muscles defined in the equipment info
    if (info && info.target_muscles && Array.isArray(info.target_muscles)) {
        return info.target_muscles.some(m => {
            const muscleName = m.name || m.name_th || m.key;
            return categorizeMuscle(muscleName) === activeMuscleFilter.value;
        });
    }
    
    return false;
};

// --- Lifecycle ---
onMounted(() => {
    const checkTheme = () => {
        isThemeDark.value = document.documentElement.classList.contains('dark');
    };
    const observer = new MutationObserver(checkTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    checkTheme();
    
    // Load history
    const savedHistory = localStorage.getItem('fitpung_workout_history');
    if (savedHistory) {
        workoutHistory.value = JSON.parse(savedHistory);
    }
    
    viewBox.value = getInitialBounds(props.gym.room_config.points, props.gym.items, 120, 20, 60, 810);
    // Note: wheel event is handled directly on svg element
    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('mouseup', handleMouseUp);
});
</script>

<template>
    <MobileLayout>
        <Head :title="'Explore Map - ' + gym.name" />
        
        <div class="flex flex-col h-full relative bg-[var(--page-bg)] transition-colors">
            <!-- Header -->
            <header class="flex items-center justify-between p-6 pb-4 flex-shrink-0 z-30 relative bg-[var(--nav-bg)] backdrop-blur-sm transition-colors">
                <div class="flex items-center gap-3">
                    <Link :href="route('mobile.maps')" class="size-10 rounded-full bg-[var(--card-bg)] shadow-sm border border-[var(--border-color)] flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-[var(--text-main)] font-bold transition-colors">arrow_back</span>
                    </Link>
                    <div>
                        <p class="text-[10px] text-[var(--text-muted)] font-bold uppercase tracking-widest leading-none mb-1 transition-colors">Interactive Map</p>
                        <h2 class="text-xl font-black leading-tight text-[var(--text-main)] transition-colors">{{ gym.name }}</h2>
                    </div>
                </div>
                <button @click="isZoomEnabled = !isZoomEnabled" 
                    class="p-2.5 rounded-2xl transition-all shadow-sm border"
                    :class="isZoomEnabled ? 'bg-[var(--theme-color)] text-white border-[var(--theme-color)]' : 'bg-[var(--card-bg)] text-[var(--text-main)] border-[var(--border-color)]'">
                    <span class="material-symbols-outlined">{{ isZoomEnabled ? 'zoom_in' : 'lock' }}</span>
                </button>
            </header>

            <!-- Map Viewport -->
            <div class="relative flex-1 bg-[var(--page-bg)] w-full overflow-hidden transition-colors">
                <svg id="technique-canvas" 
                    :viewBox="viewBoxString" 
                    class="w-full h-full cursor-crosshair"
                    @wheel="handleWheel" 
                    @mousedown="handleMouseDown"
                    preserveAspectRatio="xMidYMin meet"
                >
                    <defs>
                        <pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse">
                            <path d="M 50 0 L 0 0 0 50" fill="none" stroke="currentColor" class="text-[var(--text-muted)] opacity-5" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect :x="viewBox.x - 5000" :y="viewBox.y - 5000" width="10000" height="10000" fill="url(#grid)" />
                    
                    <polygon :points="gym.room_config.points" 
                        class="fill-[var(--card-bg)] stroke-[var(--theme-color)]/20 transition-colors" 
                        stroke-width="4" 
                        stroke-linejoin="round" />
                    
                    <g v-for="item in gym.items" :key="'item-'+item.id" 
                       class="cursor-pointer group"
                       @click="selectItem(item)"
                       :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                    >
                        <image 
                            :href="item.src" 
                            :x="-item.width/2" 
                            :y="-item.height/2" 
                            :width="item.width" 
                            :height="item.height" 
                            class="transition-all duration-300 group-hover:scale-110"
                            :style="[
                                selectedItem?.id === item.id || markedItemIds.includes(item.id) || sessionLog[item.id] || isMachineTargetingFilteredMuscle(item)
                                    ? { filter: `drop-shadow(0 0 10px var(--theme-color)) ${isThemeDark ? 'invert(1) brightness(2)' : 'brightness(1.1)'}` } 
                                    : { filter: isThemeDark ? 'invert(1) opacity(0.8)' : 'opacity(0.4) grayscale(1)' }
                            ]" 
                        />
                        <!-- In-progress indicator (Dot) - Positioned at machine center -->
                        <circle v-if="sessionLog[item.id] && sessionLog[item.id].sets.some(s => s.isCompleted) && !isExerciseCompleted(item.id)" 
                            r="8" 
                            cx="0" 
                            cy="0" 
                            fill="var(--theme-color)" 
                            class="stroke-[var(--page-bg)] transition-colors" 
                            stroke-width="2" />
                        
                        <!-- Completed indicator (Checkmark) - Positioned at machine center -->
                        <g v-if="isExerciseCompleted(item.id)">
                            <circle r="12" cx="0" cy="0" fill="var(--theme-color)" class="stroke-[var(--page-bg)] transition-colors" stroke-width="2" />
                            <text class="material-symbols-outlined" 
                                x="0" y="0" 
                                text-anchor="middle" 
                                dominant-baseline="central" 
                                fill="white" 
                                font-size="14px"
                                style="font-family: 'Material Symbols Outlined'; font-weight: bold;">
                                check
                            </text>
                        </g>
                    </g>
                </svg>

                <!-- Floating Bottom UI Layer (Unified to prevent overlap) -->
                <div class="absolute bottom-6 left-6 right-6 z-20 flex flex-col gap-3">
                    <!-- Recommended Muscles -->
                    <div class="flex justify-between items-center gap-2">
                        <div v-for="muscle in ['Cardio', 'Arms', 'Legs', 'Chest']" :key="muscle" 
                            @click="toggleMuscleFilter(muscle)"
                            class="flex-1 h-[58px] rounded-[10px] flex flex-col items-center justify-center gap-0.5 transition-all duration-500 cursor-pointer active:scale-95 text-center relative shadow-[0_4px_12px_rgba(0,0,0,0.05)] py-1.5"
                            :class="[
                                activeMuscleFilter === muscle
                                    ? 'bg-[var(--theme-color)] scale-105 z-10 shadow-[0_0_20px_rgba(var(--theme-color-rgb),0.3)]'
                                    : 'bg-[var(--card-bg)]'
                            ]"
                        >
                            <!-- (Pulse effect removed as per user request) -->

                            <span v-if="recentMuscles.includes(muscle)" 
                                class="text-[8px] font-bold uppercase leading-none"
                                :class="activeMuscleFilter === muscle ? 'text-white/80' : 'text-[var(--theme-color)]'"
                            >พึ่งเล่น</span>
                            
                            <span :class="[
                                    muscleMapping[muscle]?.isEmoji ? '' : 'material-symbols-outlined',
                                    'transition-all duration-300'
                                ]"
                                :style="{ 
                                    fontSize: muscleMapping[muscle]?.isEmoji ? '20px' : (muscle === 'Arms' ? '24px' : '20px'),
                                    filter: muscleMapping[muscle]?.isEmoji ? (activeMuscleFilter === muscle ? 'grayscale(1) brightness(2)' : 'grayscale(1) brightness(0.5)') : 'none',
                                    color: !muscleMapping[muscle]?.isEmoji ? (activeMuscleFilter === muscle ? 'white' : 'var(--text-main)') : 'inherit'
                                }"
                            >
                                {{ muscleMapping[muscle]?.icon }}
                            </span>

                            <span class="text-[10px] font-bold transition-colors"
                                :class="[
                                    activeMuscleFilter === muscle
                                        ? 'text-white/70' 
                                        : 'text-[var(--text-muted)]'
                                ]"
                            >{{ muscleMapping[muscle]?.th }}</span>
                            
                            <!-- Filter Active Dot (Bottom) -->
                            <div v-if="activeMuscleFilter === muscle" 
                                class="absolute -bottom-1 size-1.5 rounded-full bg-white shadow-sm animate-pulse"></div>
                        </div>
                    </div>

                    <!-- Rectangle: Recently Played -->
                    <transition name="up-simple">
                        <div v-if="recentMuscles.length > 0" 
                            class="bg-[var(--card-bg)]/90 backdrop-blur-md p-3.5 rounded-[22px] flex items-center justify-between transition-all shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="size-10 rounded-2xl bg-[#ec5b13]/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#ec5b13] text-lg">history</span>
                                </div>
                                <div class="transition-colors">
                                    <p class="text-[8px] font-bold text-[var(--text-muted)] uppercase leading-none mb-1 transition-colors">เล่นล่าสุด</p>
                                    <div class="flex gap-2 transition-colors">
                                        <div v-for="m in recentMuscles" :key="m" 
                                            class="px-2 py-0.5 bg-[var(--page-bg)] rounded-lg transition-colors">
                                            <span class="text-[9px] font-bold text-[var(--text-main)] uppercase transition-colors">{{ muscleMapping[m]?.th }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 opacity-60">
                                <span class="text-[7px] font-bold uppercase text-[var(--text-muted)] transition-colors">Finished</span>
                                <span class="material-symbols-outlined text-green-500 text-base transition-colors">check_circle</span>
                            </div>
                        </div>
                    </transition>

                    <!-- Bottom Controls: Program & Finish Workout -->
                    <div class="flex items-center gap-3">
                         <!-- Gym Program Button -->
                         <button @click="isProgramModalOpen = true" 
                            class="flex-1 bg-[var(--card-bg)] backdrop-blur-md p-3.5 rounded-[24px] shadow-[0_12px_30px_rgba(0,0,0,0.06)] flex items-center gap-3 active:scale-95 transition-all hover:brightness-110 group">
                            <div class="size-10 rounded-full bg-[var(--theme-color)]/10 flex items-center justify-center group-hover:bg-[var(--theme-color)] transition-colors">
                                <span class="material-symbols-outlined text-[var(--theme-color)] text-lg group-hover:text-white transition-colors">fitness_center</span>
                            </div>
                            <div class="text-left">
                                <h3 class="text-[9px] font-black uppercase italic text-[var(--text-main)] leading-none transition-colors">Program</h3>
                                <p class="text-[7px] text-[var(--text-muted)] font-bold uppercase tracking-widest mt-0.5 transition-colors">
                                    {{ ownerPlans.length }} Plans
                                </p>
                            </div>
                         </button>

                         <!-- Finish Workout Button -->
                         <transition name="up-simple">
                            <button v-if="logSessionCount > 0" @click="finishWorkout" 
                                class="flex-1 bg-[var(--theme-color)] p-3.5 rounded-[24px] shadow-[0_12px_30px_rgba(var(--theme-color-rgb),0.15)] flex items-center gap-3 active:scale-95 transition-all text-white">
                                <div class="size-10 rounded-full bg-white/20 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-lg fill-icon">check_circle</span>
                                </div>
                                <div class="text-left">
                                    <h3 class="text-[9px] font-black uppercase italic leading-none">Finish</h3>
                                    <p class="text-[7px] text-white/80 font-bold uppercase tracking-widest mt-0.5">
                                        {{ logSessionCount }} Done
                                    </p>
                                </div>
                            </button>
                        </transition>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remove the old Floating Finish Button (Lines 540-549 removed) -->

        <!-- Program List Modal -->
        <transition name="modal">
            <div v-if="isProgramModalOpen" class="fixed inset-0 z-[100] flex items-end md:items-center justify-center p-0 md:p-8 bg-black/60 backdrop-blur-sm">
                <div class="absolute inset-0" @click="isProgramModalOpen = false"></div>
                
                <div class="bg-[var(--card-bg)] w-full max-w-md md:rounded-[24px] rounded-t-[24px] overflow-hidden shadow-2xl relative animate-in slide-in-from-bottom-full duration-500 h-[80vh] flex flex-col transition-colors border-t border-[var(--border-color)]">
                     <div class="p-6 border-b border-[var(--border-color)] flex items-center justify-between transition-colors">
                         <h3 class="text-xl font-black uppercase italic text-[var(--text-main)] transition-colors">Owner's Recommendations</h3>
                         <button @click="isProgramModalOpen = false" class="size-8 rounded-full bg-[var(--page-bg)] flex items-center justify-center transition-colors">
                             <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">close</span>
                         </button>
                     </div>

                     <div class="flex-1 overflow-y-auto p-6 space-y-8 no-scrollbar">
                         <div v-for="group in groupedOwnerPlans" :key="group.name" class="space-y-4">
                             <div class="flex items-center justify-between">
                                 <h4 class="text-sm font-black uppercase tracking-widest text-[var(--theme-color)]">{{ group.name }}</h4>
                                 <span class="text-[8px] font-black px-2 py-0.5 rounded-full border border-[var(--theme-color)]/20 text-[var(--theme-color)] bg-[var(--theme-color)]/5">
                                     {{ group.badge }}
                                 </span>
                             </div>
                             <div class="space-y-3">
                                 <div v-for="ex in group.exercises" :key="ex.name" 
                                      class="flex flex-col gap-0 overflow-hidden rounded-[28px] border border-[var(--border-color)] transition-all bg-[var(--page-bg)] shadow-sm group"
                                      :class="{ 'border-[var(--theme-color)] bg-[var(--theme-color)]/5 ring-1 ring-[var(--theme-color)]/10': expandedProgramExName === ex.name }">
                                     
                                     <!-- Collapsed State / Card Header -->
                                     <div @click="toggleProgramEx(ex)" class="flex items-center gap-4 p-5 cursor-pointer">
                                         <div class="size-14 rounded-2xl bg-[var(--card-bg)] border border-[var(--border-color)] overflow-hidden flex items-center justify-center p-2 shadow-inner transition-colors">
                                              <img v-if="getExerciseImage(ex)" 
                                                   :src="formatImageUrl(getExerciseImage(ex))" 
                                                   class="w-full h-full object-contain">
                                              <span v-else class="material-symbols-outlined text-[var(--text-muted)] opacity-20 text-3xl transition-colors">fitness_center</span>
                                         </div>
                                         <div class="flex-1 transition-colors">
                                             <h5 class="text-sm font-black uppercase italic text-[var(--text-main)] leading-tight transition-colors">{{ ex.name }}</h5>
                                             <p v-if="!sessionLog[getMapItemForEx(ex)?.id]" class="text-[9px] font-bold text-[var(--text-muted)] uppercase tracking-widest mt-0.5 transition-colors">
                                                 {{ ex.sets }} Sets × {{ ex.reps }} Reps
                                             </p>
                                             <div v-else class="flex items-center gap-1.5 mt-0.5">
                                                  <span class="size-1.5 rounded-full" :class="isExerciseCompleted(getMapItemForEx(ex)?.id) ? 'bg-[var(--theme-color)]' : 'bg-orange-400'"></span>
                                                  <span class="text-[9px] font-black uppercase tracking-widest" :class="isExerciseCompleted(getMapItemForEx(ex)?.id) ? 'text-[var(--theme-color)]' : 'text-orange-400'">
                                                      {{ isExerciseCompleted(getMapItemForEx(ex)?.id) ? 'COMPLETED' : 'In Progress' }}
                                                  </span>
                                             </div>
                                         </div>
                                         <div class="flex items-center gap-2">
                                             <button @click.stop="locateProgramExercise(ex)" 
                                                 class="size-10 rounded-full flex items-center justify-center transition-all bg-[var(--card-bg)] border transition-colors"
                                                 :class="markedItemIds.includes(getMapItemForEx(ex)?.id) 
                                                     ? 'text-[var(--theme-color)] border-[var(--theme-color)]/30 bg-[var(--theme-color)]/5 shadow-[0_0_15px_rgba(var(--theme-color-rgb),0.15)] shadow-inner' 
                                                     : 'text-[var(--text-muted)] border-[var(--border-color)] opacity-60'">
                                                 <span class="material-symbols-outlined text-xl" :class="{ 'fill-icon': markedItemIds.includes(getMapItemForEx(ex)?.id) }">location_on</span>
                                             </button>
                                             
                                             <!-- If not started: Show START button -->
                                             <button v-if="!sessionLog[getMapItemForEx(ex)?.id]" 
                                                 @click.stop="selectProgramExercise(ex)"
                                                 class="px-4 py-2 rounded-full bg-[var(--theme-color)] text-white text-[10px] font-black uppercase tracking-widest active:scale-95 transition-all shadow-sm">
                                                 START
                                             </button>
                                             
                                             <!-- If started: Show normal expand arrow -->
                                             <span v-else class="material-symbols-outlined text-gray-300 transition-transform duration-300"
                                                 :class="{ 'rotate-180 text-[var(--theme-color)]': expandedProgramExName === ex.name }">
                                                 expand_more
                                             </span>
                                         </div>
                                     </div>

                                     <!-- Expanded Content (Accordion) -->
                                     <transition 
                                         enter-active-class="transition-all duration-300 ease-out"
                                         enter-from-class="max-h-0 opacity-0"
                                         enter-to-class="max-h-[1000px] opacity-100"
                                         leave-active-class="transition-all duration-200 ease-in"
                                         leave-from-class="max-h-[1000px] opacity-100"
                                         leave-to-class="max-h-0 opacity-0"
                                     >
                                         <div v-if="expandedProgramExName === ex.name && getSessionLog(ex)" class="px-5 pb-6 pt-0 overflow-hidden">
                                             <div class="flex flex-col gap-6">
                                                 <!-- Header info -->
                                                 <div class="flex items-center gap-6 pt-4 border-t border-[var(--border-color)] transition-colors">
                                                     <!-- Progress Box -->
                                                     <div class="flex flex-col items-center">
                                                         <span class="text-[7px] font-black text-[var(--theme-color)] uppercase tracking-widest mb-1 px-1">Progress</span>
                                                         <div class="px-3 py-1.5 bg-[var(--theme-color)]/5 rounded-xl border border-[var(--theme-color)]/20 flex items-center justify-center min-w-[48px]">
                                                             <span class="text-[11px] font-black text-[var(--theme-color)] uppercase italic">
                                                                 {{ getSessionLog(ex).sets.filter(s => s.isCompleted).length }}/{{ ex.sets }}
                                                             </span>
                                                         </div>
                                                     </div>
                                                 </div>
  
                                                 <!-- Workout Log UI -->
                                                 <div class="space-y-4 transition-colors">
                                                     <div class="flex items-center justify-between transition-colors">
                                                         <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)] transition-colors">Workout Log</h3>
                                                         <button @click="addSetToSession(getSessionLog(ex).id)" class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-[#ec5b13]/20 text-[#ec5b13] text-[9px] font-black uppercase tracking-widest active:scale-95 transition-all bg-[#ec5b13]/5">
                                                             <span class="material-symbols-outlined text-[10px] font-bold">add</span>
                                                             ADD SET
                                                         </button>
                                                     </div>
  
                                                     <div class="space-y-3">
                                                         <!-- Table Header -->
                                                         <div class="flex items-center gap-3 px-1 text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest transition-colors">
                                                             <div class="size-10"></div>
                                                             <div class="flex-1 text-center">Weight (KG)</div>
                                                             <div class="w-16 text-center">Sets</div>
                                                             <div class="flex-1 text-center">Reps</div>
                                                             <div class="size-11"></div>
                                                         </div>

                                                         <div v-for="(set, idx) in getSessionLog(ex).sets" :key="idx" 
                                                              @click="toggleSetInSession(getSessionLog(ex).id, idx)"
                                                              class="flex items-center gap-3 transition-all duration-300"
                                                              :class="{ 'opacity-40': set.isCompleted }">
                                                             
                                                             <!-- Remove Icon -->
                                                             <button @click.stop="removeSetFromSession(getSessionLog(ex).id, idx)" class="size-10 flex items-center justify-center text-[var(--text-muted)] opacity-30 hover:text-red-500 hover:opacity-100 transition-all">
                                                                 <span class="material-symbols-outlined text-[18px]">close</span>
                                                             </button>
 
                                                             <!-- Weight Box -->
                                                             <div class="flex-1 h-14 bg-[var(--card-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center px-1 overflow-hidden transition-colors" @click.stop>
                                                                 <select v-model="set.weight" class="w-full border-none p-0 focus:ring-0 text-sm font-black text-[var(--text-main)] text-center bg-transparent italic appearance-none text-center-last transition-colors">
                                                                     <option v-for="opt in weightOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                                 </select>
                                                             </div>
 
                                                             <!-- Set Box -->
                                                             <div class="w-16 h-14 bg-[var(--card-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center transition-colors">
                                                                 <span class="text-base font-black text-[var(--text-main)] italic transition-colors">{{ idx + 1 }}</span>
                                                             </div>
 
                                                             <!-- Reps Box -->
                                                             <div class="flex-1 h-14 bg-[var(--card-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center px-2 transition-colors" @click.stop>
                                                                 <input type="number" v-model="set.reps" 
                                                                     class="w-full border-none p-0 focus:ring-0 text-base font-black text-[var(--text-main)] text-center bg-transparent italic transition-colors"
                                                                 />
                                                             </div>
 
                                                             <!-- Completion Button -->
                                                             <div class="size-11 rounded-full flex items-center justify-center transition-all border shadow-sm transition-colors"
                                                                 :class="set.isCompleted 
                                                                     ? 'bg-[var(--theme-color)] border-[var(--theme-color)] text-white' 
                                                                     : 'bg-[var(--card-bg)] border-[var(--border-color)] text-[var(--text-muted)]/20'">
                                                                 <span class="material-symbols-outlined text-xl font-bold">{{ set.isCompleted ? 'check_circle' : 'check' }}</span>
                                                             </div>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </transition>
                                 </div>
                             </div>
                         </div>
                         
                         <div v-if="Object.keys(groupedOwnerPlans).length === 0" class="flex flex-col items-center justify-center py-20 opacity-30">
                             <span class="material-symbols-outlined text-5xl mb-3">fitness_center</span>
                             <p class="text-xs font-black uppercase tracking-widest">No plans found</p>
                         </div>
                     </div>

                     <!-- Finish Workout Action (Sticky Bottom) -->
                     <div v-if="logSessionCount > 0" class="p-6 border-t border-[var(--border-color)] bg-[var(--card-bg)] transition-colors">
                         <button @click="finishWorkout" 
                             class="w-full py-5 bg-[var(--theme-color)] text-white font-black italic uppercase tracking-[0.2em] rounded-[28px] shadow-2xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all text-sm leading-none">
                             Finish Workout
                         </button>
                     </div>
                </div>
            </div>
        </transition>

        <!-- Detail Modal -->
        <transition name="modal">
            <div v-if="isModalOpen" class="fixed inset-0 z-[110] flex items-end md:items-center justify-center p-0 md:p-8 bg-black/60 backdrop-blur-sm">
                <div class="absolute inset-0" @click="saveCurrentSetsToLog"></div>
                
                <div class="bg-[var(--card-bg)] w-full max-w-md md:rounded-[40px] rounded-t-[40px] overflow-hidden shadow-2xl relative animate-in slide-in-from-bottom-full duration-500 flex flex-col max-h-[92vh] border-t border-[var(--border-color)] transition-colors">
                    <div class="px-8 pt-10 pb-8 space-y-8 overflow-y-auto no-scrollbar">
                        <!-- Header Section -->
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <!-- Badges -->
                                    <div class="flex items-center gap-2 mb-2">
                                        <span v-if="selectedPlanInfo" class="bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-widest leading-none">GUIDED</span>
                                        <span v-else class="bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[9px] font-black px-2 py-0.5 rounded uppercase">Unit {{ selectedItem?.id }}</span>
                                        
                                        <span v-if="selectedItem?.preset_sets && !selectedPlanInfo" class="text-[9px] text-[var(--theme-color)] font-bold uppercase tracking-widest border border-[var(--theme-color)]/20 px-2 py-0.5 rounded leading-none">Owner Preset</span>
                                    </div>

                                    <!-- Unified Title (Equipment Name) -->
                                    <h2 class="text-[32px] font-black uppercase italic text-[var(--text-main)] leading-[0.9] tracking-tighter transition-colors">
                                        {{ getEquipmentInfo(selectedItem)?.name || selectedItem.name }}
                                    </h2>
                                    <p v-if="selectedPlanInfo" class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest mt-2 ml-0.5 transition-colors">
                                        {{ selectedPlanInfo.category }}
                                    </p>
                                </div>
                                <button @click="saveCurrentSetsToLog" class="size-10 rounded-full bg-[var(--page-bg)] flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Video Section (Unified: Always available) -->
                        <div class="bg-[var(--page-bg)] rounded-[32px] overflow-hidden aspect-video relative group border border-[var(--border-color)] transition-colors">
                            <div v-if="!isPlaying" class="absolute inset-0 flex flex-col items-center justify-center p-8 bg-gradient-to-br from-[#1a1f2c] to-[#2a3142] text-white">
                                <span class="material-symbols-outlined text-5xl mb-4 opacity-50">fitness_center</span>
                                <button @click="isPlaying = true" class="bg-[var(--theme-color)] hover:scale-105 transition-transform text-white px-8 py-3 rounded-full font-black text-sm shadow-xl shadow-[var(--theme-color)]/40">
                                    WATCH TECHNIQUE
                                </button>
                            </div>
                            <div v-if="isPlaying" class="w-full h-full">
                                <iframe v-if="muscleVideoInfo"
                                    class="w-full h-full"
                                    :src="`https://www.youtube-nocookie.com/embed/${muscleVideoInfo.id}?autoplay=1&mute=1&rel=0`" 
                                    frameborder="0" allowfullscreen></iframe>
                                <video v-else-if="getEquipmentInfo(selectedItem)?.technique?.video" 
                                    :src="getEquipmentInfo(selectedItem).technique.video" 
                                    class="w-full h-full object-cover" controls autoplay muted loop></video>
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-900 text-white/50 text-[10px] font-bold uppercase tracking-widest">
                                    No technique video available
                                </div>
                            </div>
                        </div>

                        <!-- Target Muscles Section (Unified) -->
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-[#ec5b13]">Target Muscles</h3>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="m in getEquipmentInfo(selectedItem)?.target_muscles || []" :key="m.key" 
                                    @click="handleMuscleClick(m.key)"
                                    class="px-5 py-3 rounded-2xl border transition-all text-[10px] font-bold uppercase tracking-tight"
                                    :class="activeMuscle === m.key 
                                        ? 'bg-[var(--theme-color)] text-white border-[var(--theme-color)]' 
                                        : 'bg-[var(--card-bg)] border-[var(--border-color)] text-[var(--text-main)] font-black'">
                                    {{ m.name_th }}
                                </button>
                                <div v-if="!getEquipmentInfo(selectedItem)?.target_muscles?.length" class="text-[10px] text-[var(--text-muted)] italic transition-colors">No muscle data available</div>
                            </div>
                        </div>

                        <!-- Workout Log Section (Unified) -->
                        <div class="space-y-4 pb-4">
                            <div class="flex items-center justify-between">
                                <div class="flex flex-col">
                                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)] transition-colors">Workout Log</h3>
                                    <p v-if="selectedPlanInfo" class="text-[8px] font-black text-[var(--theme-color)] uppercase tracking-widest mt-1">
                                        Goal: {{ selectedPlanInfo.targetSets }} Sets × {{ selectedPlanInfo.targetReps }} Reps @ {{ selectedPlanInfo.targetWeight }}KG
                                    </p>
                                </div>
                                <button @click="addNewSet" class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-[#ec5b13]/20 text-[#ec5b13] text-[9px] font-black uppercase tracking-widest active:scale-95 transition-all bg-[#ec5b13]/5">
                                    <span class="material-symbols-outlined text-[10px] font-bold">add</span>
                                    ADD SET
                                </button>
                            </div>

                            <div class="space-y-3">
                                <!-- Table Header -->
                                <div class="flex items-center gap-3 px-1 text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest transition-colors">
                                    <div class="size-10"></div>
                                    <div class="flex-1 text-center">Weight (KG)</div>
                                    <div class="w-16 text-center">Sets</div>
                                    <div class="flex-1 text-center">Reps</div>
                                    <div class="size-11"></div>
                                </div>

                                <div v-for="(set, index) in workoutSets" :key="index" 
                                    @click="toggleSetCompletion(index)"
                                    class="flex items-center gap-3 transition-all duration-300"
                                    :class="{ 'opacity-40': set.isCompleted }">
                                    
                                    <!-- Remove Icon -->
                                    <button @click.stop="removeSet(index)" class="size-10 flex items-center justify-center text-[var(--text-muted)] opacity-30 hover:text-red-500 hover:opacity-100 transition-all">
                                        <span class="material-symbols-outlined text-[18px]">close</span>
                                    </button>

                                    <!-- Weight Box -->
                                    <div class="flex-1 h-14 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center px-1 overflow-hidden transition-colors" @click.stop>
                                        <select v-model="set.weight" class="w-full border-none p-0 focus:ring-0 text-sm font-black text-[var(--text-main)] text-center bg-transparent italic appearance-none text-center-last transition-colors">
                                            <option v-for="opt in weightOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </div>

                                    <!-- Set Box -->
                                    <div class="w-16 h-14 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center transition-colors">
                                        <span class="text-base font-black text-[var(--text-main)] italic transition-colors">{{ index + 1 }}</span>
                                    </div>

                                    <!-- Reps Box -->
                                    <div class="flex-1 h-14 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)] shadow-sm flex items-center justify-center px-2 transition-colors" @click.stop>
                                        <input type="number" v-model="set.reps" 
                                            class="w-full border-none p-0 focus:ring-0 text-base font-black text-[var(--text-main)] text-center bg-transparent italic transition-colors"
                                        />
                                    </div>

                                    <!-- Done Button -->
                                    <div class="size-11 rounded-full flex items-center justify-center transition-all border shadow-sm transition-colors"
                                        :class="set.isCompleted 
                                            ? 'bg-[var(--theme-color)] border-[var(--theme-color)] text-white' 
                                            : 'bg-[var(--card-bg)] border-[var(--border-color)] text-[var(--text-muted)]/20'">
                                        <span class="material-symbols-outlined text-xl font-bold">{{ set.isCompleted ? 'check_circle' : 'check' }}</span>
                                    </div>
                                </div>
                            </div>

                            <button @click="saveCurrentSetsToLog" 
                                class="w-full bg-[var(--page-bg)] text-[var(--text-main)] py-5 rounded-[28px] font-black text-sm tracking-[0.2em] border border-[var(--border-color)] active:scale-95 transition-all uppercase mt-6 italic transition-colors">
                                SAVE PROGRESS & CLOSE
                            </button>

                            <!-- Finish Workout Action (Only if progress) -->
                            <button v-if="logSessionCount > 0" @click="finishWorkout" 
                                class="w-full bg-[var(--theme-color)] text-white py-5 rounded-[28px] font-black text-sm tracking-[0.2em] shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all uppercase mt-4 italic transition-colors">
                                Finish Workout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

            <!-- Confirmation Modal (Finish Workout) -->
            <transition name="fade">
                <div v-if="isShowFinishConfirm" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm">
                    <div class="bg-[var(--card-bg)] w-full max-w-xs rounded-[32px] overflow-hidden shadow-2xl animate-in zoom-in-95 duration-300">
                        <div class="p-8 text-center transition-colors">
                            <div class="size-16 rounded-full bg-[var(--theme-color)]/10 flex items-center justify-center mx-auto mb-4">
                                <span class="material-symbols-outlined text-[var(--theme-color)] text-3xl">logout</span>
                            </div>
                            <h2 class="text-lg font-black text-[var(--text-main)] leading-tight mb-2 transition-colors">จบการออกกำลังกาย?</h2>
                            <p class="text-xs text-[var(--text-muted)] font-medium transition-colors">ต้องการหยุดการออกกำลังกายแล้วใช่มั้ย?</p>
                        </div>
                        <div class="flex border-t border-[var(--border-color)] transition-colors">
                            <button @click="isShowFinishConfirm = false" class="flex-1 py-4 text-sm font-bold text-[var(--text-muted)] border-r border-[var(--border-color)] active:bg-black/5 transition-colors">ยกเลิก</button>
                            <button @click="confirmFinishWorkout" class="flex-1 py-4 text-sm font-black text-[var(--theme-color)] active:bg-[var(--theme-color)]/5 transition-colors">หยุดเลย</button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Success Toast (Finish Workout) -->
            <transition name="fade">
                <div v-if="isShowSuccessMessage" class="fixed top-6 left-1/2 -translate-x-1/2 z-[200] pointer-events-none w-max">
                    <div class="bg-[#111827] text-white px-5 py-3 rounded-full shadow-2xl flex items-center gap-3 border border-white/10 backdrop-blur-md">
                        <div class="size-6 rounded-full bg-[var(--theme-color)] flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-[14px] font-black">check</span>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-widest whitespace-nowrap">บันทึกข้อมูลสำเร็จ</span>
                    </div>
                </div>
            </transition>
        </MobileLayout>
</template>

<style scoped>
.fill-icon { font-variation-settings: 'FILL' 1; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.up-enter-active, .up-leave-active { transition: all 0.3s ease; }
.up-enter-from, .up-leave-to { transform: translateY(20px) translateX(-50%); opacity: 0; }
.up-simple-enter-active, .up-simple-leave-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.up-simple-enter-from, .up-simple-leave-to { transform: translateY(40px) scale(0.9); opacity: 0; }
</style>
