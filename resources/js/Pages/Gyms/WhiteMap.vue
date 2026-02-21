<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, onMounted, computed, onUnmounted } from 'vue';
// Reverting axios as My Plans are not intended for Map view per user feedback

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

const expandedProgramExName = ref(null); // Track which exercise is expanded in the program modal
const selectedPlanInfo = ref(null); // { category: string, targetSets: number, targetReps: number }

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
        sessionLog.value[selectedItem.value.id] = {
            id: selectedItem.value.id,
            name: getEquipmentInfo(selectedItem.value)?.name || selectedItem.value.name,
            // Find equipment image or fallback to item src
            image: getEquipmentInfo(selectedItem.value)?.image || selectedItem.value.src,
            sets: JSON.parse(JSON.stringify(workoutSets.value)), // Deep copy
            targetSets: selectedPlanInfo.value?.targetSets || 3,
            targetWeight: selectedPlanInfo.value?.targetWeight || 10,
            reps: selectedPlanInfo.value?.targetReps || 12
        };
    }
    isModalOpen.value = false;
    selectedPlanInfo.value = null; // Reset plan info after saving
};

// --- Recommendation Accordion Logic ---
const initSessionForProgramEx = (ex) => {
    const item = props.gym.items.find(i => {
         const info = getEquipmentInfo(i);
         const nameLower = ex.name.toLowerCase();
         return (i.name && i.name.toLowerCase() === nameLower) || 
                (info && info.name && info.name.toLowerCase() === nameLower) ||
                (info && info.name_th && info.name_th === ex.name);
    });

    if (item && !sessionLog.value[item.id]) {
        sessionLog.value[item.id] = {
            id: item.id,
            name: ex.name,
            image: getEquipmentInfo(item)?.image || item.src,
            sets: Array.from({ length: parseInt(ex.sets) || 3 }, () => ({
                weight: ((parseFloat(ex.targetWeight) || 10).toFixed(1).replace(/\.0$/, '') + 'kg'),
                reps: parseInt(ex.reps) || 12,
                isCompleted: false
            }))
        };
    }
    return item;
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

const getSessionLog = (ex) => {
    const item = getMapItemForEx(ex);
    return item ? sessionLog.value[item.id] : null;
};

const isExerciseCompleted = (itemId) => {
    const log = sessionLog.value[itemId];
    return log && log.sets.length > 0 && log.sets.every(s => s.isCompleted);
};

const finishWorkout = () => {
    // 1. Collect all exercises with at least one completed set
    const exercises = Object.values(sessionLog.value)
        .filter(entry => entry.sets.some(s => s.isCompleted))
        .map(entry => ({
            name: entry.name,
            image: entry.image,
            sets: entry.sets.filter(s => s.isCompleted).map(s => ({
                weight: s.weight.toLowerCase().includes('kg') ? s.weight : s.weight + 'kg', // Ensure consistent formatting
                reps: s.reps
            }))
        }));

    if (exercises.length === 0) return;

    const totalSets = exercises.reduce((sum, ex) => sum + ex.sets.length, 0);

    // 2. Create Session Object
    const session = {
        id: Date.now(),
        date: new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }),
        title: `${props.gym.name} Workout`,
        exercises: exercises,
        sets: totalSets
    };

    // 3. Save to LocalStorage
    const savedHistory = localStorage.getItem('fitpung_workout_history');
    const history = savedHistory ? JSON.parse(savedHistory) : [];
    history.unshift(session);
    localStorage.setItem('fitpung_workout_history', JSON.stringify(history));

    // 4. Update total sets count
    const savedSets = localStorage.getItem('fitpung_sets_done');
    const currentTotal = savedSets ? parseInt(savedSets) : 0;
    localStorage.setItem('fitpung_sets_done', (currentTotal + totalSets).toString());

    // 5. Redirect back to Workout page
    window.location.href = route('mobile.workout');
};


// --- Map Logic (Panning/Zooming) ---
const viewBox = ref({ x: 0, y: 0, w: 1000, h: 800 });
const isPanning = ref(false);
const lastMousePos = ref({ x: 0, y: 0 });

const getInitialBounds = (pointsStr, items = [], padding = 150) => {
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

    const contentW = maxX - minX;
    const contentH = maxY - minY;
    
    // In MobileLayout, the canvas is already constrained.
    const padX = Math.max(contentW * 0.08, 30);
    const padY = Math.max(contentH * 0.08, 30);
    const targetW = contentW + (padX * 2);
    return { 
        x: minX - padX,
        y: minY - padY, 
        w: targetW,
        h: targetW * 1.6 
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
    // Only intercept scroll if zoom is enabled!
    if (!isZoomEnabled.value) return; 
    
    event.preventDefault();
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
    viewBox.value = getInitialBounds(props.gym.room_config.points, props.gym.items, 100);
};

// --- Interaction Helpers ---
const selectItem = (item, customSets = null, planInfo = null) => {
    selectedItem.value = item;
    activeMuscle.value = null;
    isPlaying.value = false;
    selectedPlanInfo.value = planInfo;
    
    // Load Sets Logic
    if (sessionLog.value[item.id]) {
        // 1. Load from current session if exists
        workoutSets.value = JSON.parse(JSON.stringify(sessionLog.value[item.id].sets));
    } else if (customSets && Array.isArray(customSets) && customSets.length > 0) {
        // 2. Load from passed Custom Sets (e.g. from program)
        workoutSets.value = customSets.map(s => ({
            weight: s.weight || 10,
            reps: s.reps || 12,
            isCompleted: false
        }));
    } else if (item.preset_sets && Array.isArray(item.preset_sets) && item.preset_sets.length > 0) {
        // 3. Load from Owner Presets (from Map Item data)
        workoutSets.value = item.preset_sets.map(s => ({
            weight: s.weight || 10,
            reps: s.reps || 12,
            isCompleted: false
        }));
    } else {
        // 4. Default from Equipment DB if possible
        const info = getEquipmentInfo(item);
        workoutSets.value = [{ 
            weight: info?.target_weight || 10, 
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

const getMapItemForEx = (exercise) => {
    return props.gym.items.find(i => {
         const info = getEquipmentInfo(i);
         const nameLower = exercise.name.toLowerCase();
         return (i.name && i.name.toLowerCase() === nameLower) || 
                (info && info.name && info.name.toLowerCase() === nameLower) ||
                (info && info.name_th && info.name_th === exercise.name);
    });
};

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
    const item = props.gym.items.find(i => {
         const info = getEquipmentInfo(i);
         const nameLower = exercise.name.toLowerCase();
         return (i.name && i.name.toLowerCase() === nameLower) || 
                (info && info.name && info.name.toLowerCase() === nameLower) ||
                (info && info.name_th && info.name_th === exercise.name);
    });

    if (item) {
        // 2. Initialize session for this exercise
        initSessionForProgramEx(exercise);
        
        // 3. Just expand the accordion inline
        expandedProgramExName.value = exercise.name;
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

const logSessionCount = computed(() => {
    return Object.values(sessionLog.value).filter(entry => entry.sets.some(s => s.isCompleted)).length;
});

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

// --- Lifecycle ---
onMounted(() => {
    const checkTheme = () => {
        isThemeDark.value = document.documentElement.classList.contains('dark');
    };
    const observer = new MutationObserver(checkTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    checkTheme();
    
    viewBox.value = getInitialBounds(props.gym.room_config.points, props.gym.items, 100);
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
        
        <div class="flex flex-col h-full relative bg-gray-50/50">
            <!-- Header -->
            <header class="flex items-center justify-between p-6 pb-4 flex-shrink-0 z-30 relative bg-white/50 backdrop-blur-sm">
                <div class="flex items-center gap-3">
                    <Link :href="route('mobile.maps')" class="size-10 rounded-full bg-white  shadow-sm border border-gray-100  flex items-center justify-center">
                        <span class="material-symbols-outlined text-gray-600 ">arrow_back</span>
                    </Link>
                    <div>
                        <p class="text-[10px] text-gray-700 font-bold uppercase tracking-widest leading-none mb-1">Interactive Map</p>
                        <h2 class="text-xl font-black leading-tight text-gray-900">{{ gym.name }}</h2>
                    </div>
                </div>
                <button @click="isZoomEnabled = !isZoomEnabled" 
                    class="p-2.5 rounded-2xl transition-all shadow-sm border"
                    :class="isZoomEnabled ? 'bg-[var(--theme-color)] text-white border-[var(--theme-color)]' : 'bg-white  text-gray-600  border-gray-100 '">
                    <span class="material-symbols-outlined">{{ isZoomEnabled ? 'zoom_in' : 'lock' }}</span>
                </button>
            </header>

            <!-- Map Viewport -->
            <div class="relative flex-1 bg-white w-full overflow-hidden">
                <svg id="technique-canvas" 
                    :viewBox="viewBoxString" 
                    class="w-full h-full cursor-crosshair"
                    @wheel="handleWheel" 
                    @mousedown="handleMouseDown"
                    preserveAspectRatio="xMidYMin meet"
                >
                    <defs>
                        <pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse">
                            <path d="M 50 0 L 0 0 0 50" fill="none" stroke="currentColor" class="text-gray-500/5" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect :x="viewBox.x - 5000" :y="viewBox.y - 5000" width="10000" height="10000" fill="url(#grid)" />
                    
                    <polygon :points="gym.room_config.points" 
                        class="fill-[#f8f6f6]  stroke-[var(--theme-color)]/20" 
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
                                selectedItem?.id === item.id || markedItemIds.includes(item.id) || sessionLog[item.id]
                                    ? { filter: `drop-shadow(0 0 10px var(--theme-color)) ${isThemeDark ? 'invert(1) brightness(2)' : 'brightness(1.1)'}` } 
                                    : { filter: isThemeDark ? 'invert(1) opacity(0.8)' : 'opacity(0.4) grayscale(1)' }
                            ]" 
                        />
                        <!-- In-progress indicator (Dot) -->
                        <circle v-if="sessionLog[item.id] && sessionLog[item.id].sets.some(s => s.isCompleted) && !isExerciseCompleted(item.id)" 
                            r="8" 
                            cx="0" 
                            cy="0" 
                            fill="#00a18c" 
                            stroke="white" 
                            stroke-width="2" />
                        
                        <!-- Completed indicator (Checkmark) -->
                        <g v-if="isExerciseCompleted(item.id)">
                            <circle r="12" cx="0" cy="0" fill="#00a18c" stroke="white" stroke-width="2" />
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

                <!-- Floating Bottom Controls: Gym Program & Finish Workout -->
                <div class="absolute bottom-8 left-6 right-6 z-20 flex items-center gap-3">
                     <!-- Gym Program Button -->
                     <button @click="isProgramModalOpen = true" 
                        class="flex-1 bg-white/95 backdrop-blur-md p-4 rounded-[26px] shadow-[0_15px_35px_rgba(0,0,0,0.08)] border border-white/50 flex items-center gap-3 active:scale-95 transition-all hover:bg-white group">
                        <div class="size-11 rounded-full bg-[#00a18c]/10 flex items-center justify-center group-hover:bg-[#00a18c] transition-colors">
                            <span class="material-symbols-outlined text-[#00a18c] text-xl group-hover:text-white">fitness_center</span>
                        </div>
                        <div class="text-left">
                            <h3 class="text-[10px] font-black uppercase italic text-gray-900 leading-none">Program</h3>
                            <p class="text-[8px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">
                                {{ ownerPlans.length }} Plans
                            </p>
                        </div>
                     </button>

                     <!-- Finish Workout Button (Uniform Style) -->
                     <transition name="up-simple">
                        <button v-if="logSessionCount > 0" @click="finishWorkout" 
                            class="flex-1 bg-[#00a18c] p-4 rounded-[26px] shadow-[0_15px_35px_rgba(0,161,140,0.2)] border border-[#00a18c] flex items-center gap-3 active:scale-95 transition-all text-white">
                            <div class="size-11 rounded-full bg-white/20 flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-xl fill-icon">check_circle</span>
                            </div>
                            <div class="text-left">
                                <h3 class="text-[10px] font-black uppercase italic leading-none">Finish</h3>
                                <p class="text-[8px] text-white/80 font-bold uppercase tracking-widest mt-0.5">
                                    {{ logSessionCount }} Done
                                </p>
                            </div>
                        </button>
                    </transition>
                </div>
            </div>
        </div>

        <!-- Remove the old Floating Finish Button (Lines 540-549 removed) -->

        <!-- Program List Modal -->
        <transition name="modal">
            <div v-if="isProgramModalOpen" class="fixed inset-0 z-[100] flex items-end md:items-center justify-center p-0 md:p-8 bg-black/60 backdrop-blur-sm">
                <div class="absolute inset-0" @click="isProgramModalOpen = false"></div>
                
                <div class="bg-white w-full max-w-md md:rounded-[24px] rounded-t-[24px] overflow-hidden shadow-2xl relative animate-in slide-in-from-bottom-full duration-500 h-[80vh] flex flex-col">
                     <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                         <h3 class="text-xl font-black uppercase italic text-gray-900">Owner's Recommendations</h3>
                         <button @click="isProgramModalOpen = false" class="size-8 rounded-full bg-gray-50 flex items-center justify-center">
                             <span class="material-symbols-outlined text-gray-400">close</span>
                         </button>
                     </div>

                     <div class="flex-1 overflow-y-auto p-6 space-y-8 no-scrollbar">
                         <div v-for="group in groupedOwnerPlans" :key="group.name" class="space-y-4">
                             <div class="flex items-center justify-between">
                                 <h4 class="text-sm font-black uppercase tracking-widest text-[#00a18c]">{{ group.name }}</h4>
                                 <span class="text-[8px] font-black px-2 py-0.5 rounded-full border border-[#00a18c]/20 text-[#00a18c] bg-[#00a18c]/5">
                                     {{ group.badge }}
                                 </span>
                             </div>
                             <div class="space-y-3">
                                 <div v-for="ex in group.exercises" :key="ex.name" 
                                      class="flex flex-col gap-0 overflow-hidden rounded-[28px] border border-gray-100/50 transition-all bg-gray-50/50 shadow-sm group"
                                      :class="{ 'border-[#00a18c] bg-[#00a18c]/5 ring-1 ring-[#00a18c]/10': expandedProgramExName === ex.name }">
                                     
                                     <!-- Collapsed State / Card Header -->
                                     <div @click="toggleProgramEx(ex)" class="flex items-center gap-4 p-5 cursor-pointer">
                                         <div class="size-14 rounded-2xl bg-white border border-gray-100 overflow-hidden flex items-center justify-center p-2 shadow-inner">
                                              <img v-if="getExerciseImage(ex)" 
                                                   :src="formatImageUrl(getExerciseImage(ex))" 
                                                   class="w-full h-full object-contain">
                                              <span v-else class="material-symbols-outlined text-gray-200 text-3xl">fitness_center</span>
                                         </div>
                                         <div class="flex-1">
                                             <h5 class="text-sm font-black uppercase italic text-gray-900 leading-tight">{{ ex.name }}</h5>
                                             <p v-if="!sessionLog[getMapItemForEx(ex)?.id]" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                                                 {{ ex.sets }} Sets × {{ ex.reps }} Reps
                                             </p>
                                             <div v-else class="flex items-center gap-1.5 mt-0.5">
                                                  <span class="size-1.5 rounded-full" :class="isExerciseCompleted(getMapItemForEx(ex)?.id) ? 'bg-[#00a18c]' : 'bg-orange-400'"></span>
                                                  <span class="text-[9px] font-black uppercase tracking-widest" :class="isExerciseCompleted(getMapItemForEx(ex)?.id) ? 'text-[#00a18c]' : 'text-orange-400'">
                                                      {{ isExerciseCompleted(getMapItemForEx(ex)?.id) ? 'COMPLETED' : 'In Progress' }}
                                                  </span>
                                             </div>
                                         </div>
                                         <div class="flex items-center gap-2">
                                             <button @click.stop="locateProgramExercise(ex)" 
                                                 class="size-10 rounded-full flex items-center justify-center transition-all bg-white border"
                                                 :class="markedItemIds.includes(getMapItemForEx(ex)?.id) 
                                                     ? 'text-[#00a18c] border-[#00a18c]/30 bg-[#00a18c]/5 shadow-[0_0_15px_rgba(0,161,140,0.15)] shadow-inner' 
                                                     : 'text-gray-300 border-gray-100'">
                                                 <span class="material-symbols-outlined text-xl" :class="{ 'fill-icon': markedItemIds.includes(getMapItemForEx(ex)?.id) }">location_on</span>
                                             </button>
                                             
                                             <!-- If not started: Show START button -->
                                             <button v-if="!sessionLog[getMapItemForEx(ex)?.id]" 
                                                 @click.stop="selectProgramExercise(ex)"
                                                 class="px-4 py-2 rounded-full bg-[#00a18c] text-white text-[10px] font-black uppercase tracking-widest active:scale-95 transition-all shadow-sm">
                                                 START
                                             </button>
                                             
                                             <!-- If started: Show normal expand arrow -->
                                             <span v-else class="material-symbols-outlined text-gray-300 transition-transform duration-300"
                                                 :class="{ 'rotate-180 text-[#00a18c]': expandedProgramExName === ex.name }">
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
                                                 <div class="flex items-center gap-6 pt-4 border-t border-gray-100">
                                                     <!-- Progress Box -->
                                                     <div class="flex flex-col items-center">
                                                         <span class="text-[7px] font-black text-[#00a18c] uppercase tracking-widest mb-1 px-1">Progress</span>
                                                         <div class="px-3 py-1.5 bg-[#00a18c]/5 rounded-xl border border-[#00a18c]/20 flex items-center justify-center min-w-[48px]">
                                                             <span class="text-[11px] font-black text-[#00a18c] uppercase italic">
                                                                 {{ getSessionLog(ex).sets.filter(s => s.isCompleted).length }}/{{ ex.sets }}
                                                             </span>
                                                         </div>
                                                     </div>
                                                 </div>
 
                                                 <!-- Workout Log UI -->
                                                 <div class="space-y-4">
                                                     <div class="flex items-center justify-between">
                                                         <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Workout Log</h3>
                                                         <button @click="addSetToSession(getSessionLog(ex).id)" class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-[#ec5b13]/20 text-[#ec5b13] text-[9px] font-black uppercase tracking-widest active:scale-95 transition-all bg-[#ec5b13]/5">
                                                             <span class="material-symbols-outlined text-[10px] font-bold">add</span>
                                                             ADD SET
                                                         </button>
                                                     </div>
 
                                                     <div class="space-y-3">
                                                         <!-- Table Header -->
                                                         <div class="flex items-center gap-3 px-1 text-[8px] font-black text-gray-300 uppercase tracking-widest">
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
                                                             <button @click.stop="removeSetFromSession(getSessionLog(ex).id, idx)" class="size-10 flex items-center justify-center text-gray-200 hover:text-red-500 transition-colors">
                                                                 <span class="material-symbols-outlined text-[18px]">close</span>
                                                             </button>
 
                                                             <!-- Weight Box -->
                                                             <div class="flex-1 h-14 bg-white rounded-2xl border border-gray-200/80 shadow-sm flex items-center justify-center px-1 overflow-hidden" @click.stop>
                                                                 <select v-model="set.weight" class="w-full border-none p-0 focus:ring-0 text-sm font-black text-gray-900 text-center bg-transparent italic appearance-none text-center-last">
                                                                     <option v-for="opt in weightOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                                 </select>
                                                             </div>
 
                                                             <!-- Set Box -->
                                                             <div class="w-16 h-14 bg-white rounded-2xl border border-gray-200/80 shadow-sm flex items-center justify-center">
                                                                 <span class="text-base font-black text-gray-900 italic font-black">{{ idx + 1 }}</span>
                                                             </div>
 
                                                             <!-- Reps Box -->
                                                             <div class="flex-1 h-14 bg-white rounded-2xl border border-gray-200/80 shadow-sm flex items-center justify-center px-2" @click.stop>
                                                                 <input type="number" v-model="set.reps" 
                                                                     class="w-full border-none p-0 focus:ring-0 text-base font-black text-gray-900 text-center bg-transparent italic"
                                                                 />
                                                             </div>
 
                                                             <!-- Completion Button -->
                                                             <div class="size-11 rounded-full flex items-center justify-center transition-all border shadow-sm"
                                                                 :class="set.isCompleted 
                                                                     ? 'bg-[#00a18c] border-[#00a18c] text-white' 
                                                                     : 'bg-white border-gray-100 text-gray-200'">
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
                </div>
            </div>
        </transition>

        <!-- Detail Modal -->
        <transition name="modal">
            <div v-if="isModalOpen" class="fixed inset-0 z-[110] flex items-end md:items-center justify-center p-0 md:p-8 bg-black/60 backdrop-blur-sm">
                <div class="absolute inset-0" @click="saveCurrentSetsToLog"></div>
                
                <div class="bg-white w-full max-w-md md:rounded-[40px] rounded-t-[40px] overflow-hidden shadow-2xl relative animate-in slide-in-from-bottom-full duration-500 flex flex-col max-h-[92vh]">
                    <div class="px-8 pt-10 pb-8 space-y-8 overflow-y-auto no-scrollbar">
                        <!-- Design Image 2: Guided Plan Header -->
                        <div v-if="selectedPlanInfo" class="space-y-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-[#00a18c] uppercase tracking-[0.2em] mb-1">GUIDED PLAN</p>
                                    <h2 class="text-[32px] font-black uppercase italic text-gray-900 leading-[0.9] tracking-tighter">{{ selectedPlanInfo.category }}</h2>
                                </div>
                                <button @click="saveCurrentSetsToLog" class="size-10 rounded-full bg-gray-50 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-gray-400">close</span>
                                </button>
                            </div>

                            <div class="flex gap-6 items-center">
                                <div class="size-28 rounded-[36px] bg-white border border-gray-100 flex items-center justify-center p-4 shadow-sm">
                                     <img v-if="getEquipmentInfo(selectedItem)?.image || selectedItem.src" 
                                          :src="formatImageUrl(getEquipmentInfo(selectedItem)?.image || selectedItem.src)" 
                                          class="w-full h-full object-contain">
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-[28px] font-black uppercase italic text-gray-900 leading-[0.9] tracking-tighter mb-4">
                                        {{ getEquipmentInfo(selectedItem)?.name || selectedItem.name }}
                                    </h3>
                                    <div class="flex items-center gap-3">
                                        <!-- Progress Box -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[7px] font-black text-[#00a18c] uppercase tracking-widest mb-1 px-1">Progress</span>
                                            <div class="px-3 py-1.5 bg-[#00a18c]/5 rounded-xl border border-[#00a18c]/20 flex items-center justify-center min-w-[48px]">
                                                <span class="text-[11px] font-black text-[#00a18c] uppercase italic">
                                                    {{ sessionLog[selectedItem.id]?.sets.filter(s => s.isCompleted).length || 0 }}/{{ selectedPlanInfo.targetSets }}
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Target Box -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[7px] font-black text-gray-300 uppercase tracking-widest mb-1 px-1">Target</span>
                                            <div class="px-3 py-1.5 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center min-w-[48px]">
                                                <span class="text-[11px] font-black text-gray-900 uppercase italic">{{ selectedPlanInfo.targetWeight }}KG</span>
                                            </div>
                                        </div>
                                        <!-- Reps Box -->
                                        <div class="flex flex-col items-center">
                                            <span class="text-[7px] font-black text-gray-300 uppercase tracking-widest mb-1 px-1">Reps</span>
                                            <div class="px-3 py-1.5 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center min-w-[48px]">
                                                <span class="text-[11px] font-black text-gray-900 uppercase italic">{{ selectedPlanInfo.targetReps }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 pt-2">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Workout Log</h3>
                                    <button @click="addNewSet" class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-[#ec5b13]/20 text-[#ec5b13] text-[9px] font-black uppercase tracking-widest active:scale-95 transition-all bg-[#ec5b13]/5">
                                        <span class="material-symbols-outlined text-[10px] font-bold">add</span>
                                        ADD SET
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <!-- Table Header -->
                                    <div class="flex items-center gap-3 px-1 text-[8px] font-black text-gray-300 uppercase tracking-widest">
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
                                        <button @click.stop="removeSet(index)" class="size-10 flex items-center justify-center text-gray-200 hover:text-red-500 transition-colors">
                                            <span class="material-symbols-outlined text-[18px]">close</span>
                                        </button>

                                        <!-- Weight Box -->
                                        <div class="flex-1 h-14 bg-white rounded-2xl border border-gray-200/80 shadow-sm flex items-center justify-center px-1 overflow-hidden" @click.stop>
                                            <select v-model="set.weight" class="w-full border-none p-0 focus:ring-0 text-sm font-black text-gray-900 text-center bg-transparent italic appearance-none text-center-last">
                                                <option v-for="opt in weightOptions" :key="opt" :value="opt">{{ opt }}</option>
                                            </select>
                                        </div>

                                        <!-- Set Box -->
                                        <div class="w-16 h-14 bg-white rounded-2xl border border-gray-200/80 shadow-sm flex items-center justify-center">
                                            <span class="text-base font-black text-gray-900 italic">{{ index + 1 }}</span>
                                        </div>

                                        <!-- Reps Box -->
                                        <div class="flex-1 h-14 bg-white rounded-2xl border border-gray-200/80 shadow-sm flex items-center justify-center px-2" @click.stop>
                                            <input type="number" v-model="set.reps" 
                                                class="w-full border-none p-0 focus:ring-0 text-base font-black text-gray-900 text-center bg-transparent italic"
                                            />
                                        </div>

                                        <!-- Done Button -->
                                        <div class="size-11 rounded-full flex items-center justify-center transition-all border shadow-sm"
                                            :class="set.isCompleted 
                                                ? 'bg-[#00a18c] border-[#00a18c] text-white' 
                                                : 'bg-white border-gray-100 text-gray-200'">
                                            <span class="material-symbols-outlined text-xl font-bold">{{ set.isCompleted ? 'check_circle' : 'check' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button @click="saveCurrentSetsToLog" 
                                class="w-full bg-gray-900 text-white py-5 rounded-[28px] font-black text-sm tracking-[0.2em] shadow-xl active:scale-95 transition-all uppercase mt-6 italic">
                                SAVE PROGRESS & CLOSE
                            </button>
                        </div>

                        <!-- Original Detail Header (If not from plan) -->
                        <div v-else class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[9px] font-black px-2 py-0.5 rounded uppercase">Unit {{ selectedItem?.id }}</span>
                                        <span v-if="selectedItem?.preset_sets" class="text-[9px] text-[#00a18c] font-bold uppercase tracking-widest border border-[#00a18c]/20 px-2 py-0.5 rounded">Owner Preset</span>
                                    </div>
                                    <h2 class="text-3xl font-black leading-tight uppercase italic text-gray-900">{{ getEquipmentInfo(selectedItem)?.name || selectedItem.name }}</h2>
                                </div>
                                <button @click="saveCurrentSetsToLog" class="size-10 rounded-full bg-gray-50 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-gray-400">close</span>
                                </button>
                            </div>

                            <div class="bg-gray-100 rounded-[32px] overflow-hidden aspect-video relative group border border-gray-200">
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
                                </div>
                            </div>
                        </div>

                        <!-- Target Muscles (Only if video not playing or not from plan) -->
                        <div v-if="!selectedPlanInfo" class="space-y-4">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-[#ec5b13]">Target Muscles</h3>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="m in getEquipmentInfo(selectedItem)?.target_muscles || []" :key="m.key" 
                                    @click="handleMuscleClick(m.key)"
                                    class="px-5 py-3 rounded-2xl border transition-all text-[10px] font-bold uppercase tracking-tight"
                                    :class="activeMuscle === m.key 
                                        ? 'bg-[var(--theme-color)] text-white border-[var(--theme-color)]' 
                                        : 'bg-white  border-gray-100  text-gray-600 font-black'">
                                    {{ m.name_th }}
                                </button>
                            </div>
                        </div>

                        <!-- Workout Log for Direct Selection (Non-Guided) -->
                        <div v-if="!selectedPlanInfo" class="space-y-4 pb-4">
                             <!-- This section remains similar to above but for when owner recommendations aren't being followed -->
                             <div class="flex items-center justify-between">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Workout Log</h3>
                                <button @click="addNewSet" class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-[var(--theme-color)]/20 text-[var(--theme-color)] text-[9px] font-black uppercase tracking-widest active:scale-95 transition-all">
                                    <span class="material-symbols-outlined text-[10px] font-bold">add</span>
                                    ADD SET
                                </button>
                             </div>
                             <!-- ... rest of standard log ... -->
                        </div>
                    </div>
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
