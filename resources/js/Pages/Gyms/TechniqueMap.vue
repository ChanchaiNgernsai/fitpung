<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    gym: Object,
    equipments: Array,
});

const selectedItem = ref(null);
const isModalOpen = ref(false);
const isZoomEnabled = ref(false);
const activeMuscle = ref(null);
const modalLeftPanel = ref(null);
const isPlaying = ref(false);

const startExecution = () => {
    isPlaying.value = true;
    setTimeout(() => {
        if (modalLeftPanel.value) {
            modalLeftPanel.value.scrollTo({ 
                top: modalLeftPanel.value.scrollHeight, 
                behavior: 'smooth' 
            });
        }
    }, 100);
};

const scrollToVideo = () => {
    isPlaying.value = true;
    
    // Check if we are on mobile (where the outer container scrolls)
    const isMobile = typeof window !== 'undefined' && window.innerWidth < 768;
    
    if (isMobile) {
        // Find the outer scrollable modal container
        const modalContainer = document.querySelector('.fixed.inset-0.overflow-y-auto');
        if (modalContainer) {
            modalContainer.scrollTo({ top: 0, behavior: 'smooth' });
        }
    } else if (modalLeftPanel.value) {
        modalLeftPanel.value.scrollTo({ 
            top: 0, 
            behavior: 'smooth' 
        });
    }
};

const handleMuscleClick = (muscleKey) => {
    activeMuscle.value = muscleKey;
    startExecution();
};

// Tactical Workout Tracking (Multi-Set System)
const sessionLog = ref({}); // { itemId: { name, image, sets: [] } }
const workoutSets = ref([
    { weight: 10, reps: 12, isCompleted: false }
]);

const updateSessionLog = () => {
    if (selectedItem.value) {
        const hasProgress = workoutSets.value.some(s => s.isCompleted);
        const info = getEquipmentInfo(selectedItem.value);
        
        if (hasProgress || sessionLog.value[selectedItem.value.id]) {
            sessionLog.value[selectedItem.value.id] = {
                id: selectedItem.value.id,
                name: info?.name || selectedItem.value.name,
                image: info?.image || selectedItem.value.src,
                sets: JSON.parse(JSON.stringify(workoutSets.value))
            };
        }
    }
};

const addNewSet = () => {
    const lastSet = workoutSets.value[workoutSets.value.length - 1];
    workoutSets.value.push({
        weight: lastSet ? lastSet.weight : 10,
        reps: lastSet ? lastSet.reps : 12,
        isCompleted: false
    });
    updateSessionLog();
};

const removeSet = (index) => {
    if (workoutSets.value.length > 1) {
        workoutSets.value.splice(index, 1);
        updateSessionLog();
    }
};

const toggleSetCompletion = (index) => {
    workoutSets.value[index].isCompleted = !workoutSets.value[index].isCompleted;
    updateSessionLog();
};

const muscleImage = computed(() => {
    if (!activeMuscle.value) return null;
    const key = String(activeMuscle.value).toLowerCase();
    
    // Exact mapping based on exercise names
    if (key.includes('bicep') || key.includes('หน้าแขน')) {
        return '/images/gorila/ConcentrationCurl.png';
    }
    if (key.includes('shoulder') || key.includes('ไหล่') || key.includes('deltoid')) {
        return '/images/gorila/DumbbellLateralRaise.png';
    }
    
    // Triceps / หลังแขน
    if (key.includes('tricep') || key.includes('หลังแขน')) {
        return '/images/gorila/DumbbellSkullCrusher.png';
    }

    // Calves / หน่อง
    if (key.includes('calf') || key.includes('หน่อง') || key.includes('calve')) {
        return '/images/gorila/DumbbellCalfRaises.png';
    }

    // Quads & Glutes / ต้นขา, ก้น
    if (key.includes('quad') || key.includes('thigh') || key.includes('glute') || key.includes('ขา') || key.includes('ก้น')) {
        return '/images/gorila/GobletSquat.png';
    }
    
    return null;
});

const muscleVideoInfo = computed(() => {
    if (!activeMuscle.value) return null;
    const key = String(activeMuscle.value).toLowerCase();
    
    // Mapping keys to specific YouTube tutorial IDs
    // Concentration Curl (Biceps) - Use requested video
    if (key.includes('bicep') || key.includes('หน้าแขน')) {
        return { 
            id: 'EjUnEEfTSEY', 
            title: 'Concentration Curl' 
        };
    }
    
    // Dumbbell Lateral Raise (Shoulders/Deltoids) - Use requested video
    if (key.includes('shoulder') || key.includes('ไหล่') || key.includes('deltoid')) {
        return { 
            id: 'Kl3LEzQ5Zqs', 
            title: 'Dumbbell Lateral Raise' 
        };
    }

    // Dumbbell Skull Crusher (Triceps) - Use requested video
    if (key.includes('tricep') || key.includes('หลังแขน')) {
        return { 
            id: 'iuYB_fLp26Q', 
            title: 'Dumbbell Skull Crusher' 
        };
    }

    // Dumbbell Calf Raises (Calves)
    if (key.includes('calf') || key.includes('หน่อง') || key.includes('calve')) {
        return { 
            id: 'SRUtMJ0tE2A', 
            title: 'Dumbbell Calf Raises' 
        };
    }

    // Goblet Squat (Quads/Glutes)
    if (key.includes('quad') || key.includes('thigh') || key.includes('glute') || key.includes('ขา') || key.includes('ก้น')) {
        return { 
            id: 'meJSJEG_sT0', 
            title: 'Goblet Squat' 
        };
    }
    
    return null;
});

// ViewPort & Pan/Zoom Logic
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
    
    // Balanced padding for perfect centering
    const isMobile = typeof window !== 'undefined' ? window.innerWidth < 768 : false;
    
    if (isMobile) {
        // Very tight padding for mobile to maximize floor visibility
        const padX = Math.max(contentW * 0.08, 30);
        const padY = Math.max(contentH * 0.08, 30);
        
        const targetW = contentW + (padX * 2);
        return { 
            x: minX - padX,
            y: minY - padY, 
            w: targetW,
            h: targetW * 1.6 // Optimized vertical ratio for phone screens
        };
    }

    // Desktop logic
    const padFactor = 0.6;
    const paddingX = Math.max(contentW * padFactor, padding); 
    const paddingY = Math.max(contentH * padFactor, padding);

    return { 
        x: minX - (paddingX * 1.1),
        y: minY - (paddingY * 0.3), 
        w: contentW + (paddingX * 2), 
        h: contentH + (paddingY * 3)
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
    event.preventDefault();
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

const zoomIn = () => {
    const factor = 0.8;
    const newW = viewBox.value.w * factor;
    const newH = viewBox.value.h * factor;
    if (newW < 200) return;
    
    // Zoom towards center
    const dx = viewBox.value.w - newW;
    const dy = viewBox.value.h - newH;
    viewBox.value.x += dx / 2;
    viewBox.value.y += dy / 2;
    viewBox.value.w = newW;
    viewBox.value.h = newH;
};

const zoomOut = () => {
    const factor = 1.25;
    const newW = viewBox.value.w * factor;
    const newH = viewBox.value.h * factor;
    if (newW > 10000) return;
    
    const dx = viewBox.value.w - newW;
    const dy = viewBox.value.h - newH;
    viewBox.value.x += dx / 2;
    viewBox.value.y += dy / 2;
    viewBox.value.w = newW;
    viewBox.value.h = newH;
};

const resetZoom = () => {
    viewBox.value = getInitialBounds(props.gym.room_config.points, props.gym.items, 100);
};

const selectItem = (item) => {
    selectedItem.value = item;
    activeMuscle.value = null; // Reset muscle selection when opening new item
    isPlaying.value = false; // Reset play state
    
    if (sessionLog.value[item.id]) {
        workoutSets.value = JSON.parse(JSON.stringify(sessionLog.value[item.id].sets));
    } else {
        const info = getEquipmentInfo(item);
        workoutSets.value = [{ 
            weight: info?.target_weight || 10, 
            reps: info?.reps || 12, 
            isCompleted: false 
        }];
    }
    isModalOpen.value = true;
};

const confirmFinishWorkout = async () => {
    const exercises = Object.values(sessionLog.value)
        .filter(entry => entry.sets.some(s => s.isCompleted))
        .map(entry => ({
            name: entry.name,
            image: entry.image,
            sets: entry.sets.filter(s => s.isCompleted).map(s => ({
                weight: String(s.weight).toLowerCase().includes('kg') ? s.weight : s.weight + 'kg',
                reps: s.reps
            }))
        }));

    if (exercises.length === 0) {
        alert("Please log at least one completed set before finishing.");
        return;
    }

    const totalSets = exercises.reduce((sum, ex) => sum + ex.sets.length, 0);

    const sessionData = {
        workout_date: new Date().toISOString().split('T')[0],
        data: {
            title: `${props.gym.name} Tactical Workout`,
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
        const newSession = response.data;
        
        // Sync local storage history immediately
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

        isModalOpen.value = false;
        alert("บันทึกข้อมูลสำเร็จ!");
        router.get(route('mobile.workout'));
    } catch (e) {
        console.error("Failed to save workout session from Tactical Map", e);
        alert("บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง");
    }
};

const getEquipmentInfo = (item) => {
    if (!item) return null;
    const filename = item.src.split('/').pop().toLowerCase();
    const info = props.equipments.find(e => e.filename.toLowerCase() === filename);
    
    // Inject requested muscles for Dumbbells specifically for UI demonstration
    if (info && filename.includes('dumbbell')) {
        const extraMuscles = [
            { key: 'หน่อง', name_th: 'หน่อง (CALVES)', name: 'Calves' },
            { key: 'ก้น', name_th: 'ก้น/ต้นขา (GOBLET SQUAT)', name: 'Glutes/Quads' }
        ];
        
        // Clone to avoid mutating props and ensure uniqueness
        const muscles = [...(info.target_muscles || [])];
        extraMuscles.forEach(extra => {
            if (!muscles.some(m => m.key === extra.key)) {
                muscles.push(extra);
            }
        });
        return { ...info, target_muscles: muscles };
    }
    
    return info;
};

const isThemeDark = ref(false);

onMounted(() => {
    const checkTheme = () => {
        isThemeDark.value = document.documentElement.classList.contains('dark');
    };
    
    const observer = new MutationObserver(checkTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    checkTheme();
    
    viewBox.value = getInitialBounds(props.gym.room_config.points, props.gym.items, 100);
    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
});
</script>

<template>
    <Head :title="'Tactical Map - ' + gym.name" />

    <AppLayout>
        <template #navbar-left>
            <div class="flex items-center gap-3 ml-2 md:ml-6 pl-2 md:pl-6 border-l border-base-content/10">
                <Link :href="route('gyms.show', gym.id)" 
                    class="btn btn-circle btn-ghost btn-sm hover:text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="hidden sm:flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        <h1 class="text-sm md:text-lg font-black uppercase tracking-tight italic leading-none">
                            {{ gym.name }}
                        </h1>
                    </div>
                    <p class="text-[8px] md:text-[9px] font-bold uppercase tracking-[0.2em] opacity-50 pl-3">
                        Tactical Explorer <span class="text-indigo-600/60">v2.0</span>
                    </p>
                </div>
            </div>
        </template>

        <template #header-actions>
            <div class="flex items-center gap-2 mr-2">
                <!-- Tactical Info (Desktop) -->
                <div class="hidden md:flex flex-col items-end mr-2 leading-none">
                    <span class="text-[8px] font-black uppercase tracking-widest opacity-40">Sensors</span>
                    <span class="text-[10px] font-bold text-indigo-500">{{ gym.items.length }} Units</span>
                </div>

                <!-- Zoom Toggle -->
                <button @click="isZoomEnabled = !isZoomEnabled" 
                    class="btn btn-xs h-8 px-4 gap-2 rounded-lg transition-all duration-300 border-none uppercase tracking-[0.15em] font-black text-[9px]"
                    :class="[
                        isZoomEnabled 
                            ? 'bg-indigo-600 text-white shadow-[0_4px_12px_rgba(79,70,229,0.3)]' 
                            : isThemeDark 
                                ? 'bg-white/5 text-slate-400 hover:bg-white/10' 
                                : 'bg-slate-200 text-slate-600 hover:bg-slate-300'
                    ]">
                    <span class="hidden md:inline">{{ isZoomEnabled ? 'Unlocked' : 'Locked' }}</span>
                    <svg v-if="!isZoomEnabled" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 11V7a4 4 0 118 0m-4 8v2M6 21h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
        </template>

        <div class="h-[calc(100dvh-64px)] flex flex-col overflow-hidden relative font-sans transition-colors duration-500"
            :class="isThemeDark ? 'bg-[#020617] text-slate-200' : 'bg-white text-slate-900'">
            <!-- Atmospheric Background Elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-1/4 -right-1/4 w-1/2 h-1/2 blur-[120px] rounded-full transition-colors duration-700"
                    :class="isThemeDark ? 'bg-blue-950/30' : 'bg-blue-100/40'"></div>
                <div class="absolute -bottom-1/4 -left-1/4 w-1/2 h-1/2 blur-[120px] rounded-full transition-colors duration-700"
                    :class="isThemeDark ? 'bg-indigo-950/20' : 'bg-indigo-50/30'"></div>
                <div class="absolute inset-0 opacity-[0.03]  mix-blend-overlay bg-[url('https://grainy-gradients.vercel.app/noise.svg')]"></div>
            </div>

            <!-- Main Interactive Canvas -->
            <main class="flex-1 relative cursor-crosshair pt-12 md:pt-0">
                <svg 
                    id="technique-canvas" 
                    :viewBox="viewBoxString" 
                    class="w-full h-full drop-shadow-[0_0_50px_rgba(0,0,0,0.5)]" 
                    @wheel="handleWheel" 
                    @mousedown="handleMouseDown"
                    preserveAspectRatio="xMidYMin meet"
                >
                    <defs>
                        <!-- Premium Filter for Items -->
                        <filter id="item-glow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur in="SourceAlpha" stdDeviation="5" result="blur" />
                            <feOffset in="blur" dx="0" dy="0" result="offsetBlur" />
                            <feFlood flood-color="#3b82f6" flood-opacity="0.3" result="offsetColor" />
                            <feComposite in="offsetColor" in2="offsetBlur" operator="in" result="glow" />
                            <feMerge>
                                <feMergeNode in="glow" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>

                        <!-- Floor Gradients -->
                        <radialGradient id="floorGradDark" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                            <stop offset="0%" stop-color="#1e293b" />
                            <stop offset="100%" stop-color="#020617" />
                        </radialGradient>
                        <radialGradient id="floorGradLight" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                            <stop offset="0%" stop-color="#ffffff" />
                            <stop offset="100%" stop-color="#eef2ff" />
                        </radialGradient>

                        <!-- Grid Pattern -->
                        <pattern id="tactical-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                            <path d="M 60 0 L 0 0 0 60" fill="none" stroke="currentColor" class="text-blue-500/10 " stroke-width="0.5"/>
                            <circle cx="0" cy="0" r="1" fill="currentColor" class="text-blue-500/20 " />
                        </pattern>
                    </defs>

                    <!-- Infinite Grid Background -->
                    <rect :x="viewBox.x - 5000" :y="viewBox.y - 5000" width="10000" height="10000" fill="url(#tactical-grid)" />

                    <!-- Room Floor with Depth -->
                    <polygon :points="gym.room_config.points" 
                        :fill="isThemeDark ? 'url(#floorGradDark)' : 'url(#floorGradLight)'" 
                        stroke="rgba(59, 130, 246, 0.4)" 
                        stroke-width="3" 
                        class="transition-all duration-700 hover:stroke-blue-500/60"
                        style="filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.1))" />
                    
                    <!-- Decorative Inner Border -->
                    <polygon :points="gym.room_config.points" 
                        fill="none" 
                        stroke="rgba(255, 255, 255, 0.03)" 
                        stroke-width="15" 
                        stroke-linejoin="round" />

                    <!-- Equipment Unit Layer -->
                    <g v-for="item in gym.items" :key="'item-'+item.id" 
                       class="unit-container cursor-pointer group"
                       @click="selectItem(item)"
                       :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                    >
                        <!-- Anchor Point -->
                        <circle r="4" fill="#3b82f6" class="opacity-40 group-hover:opacity-100 group-hover:scale-150 transition-all duration-500" />
                        
                        <!-- Unit Icon -->
                        <g class="transition-all duration-500 group-hover:-translate-y-2">
                             <image 
                                :href="item.src" 
                                :x="-item.width/2" 
                                :y="-item.height/2" 
                                :width="item.width" 
                                :height="item.height" 
                                class="unit-image transition-all duration-500"
                                :style="[
                                    selectedItem?.id === item.id 
                                        ? { filter: `drop-shadow(0 0 15px #3b82f6) ${isThemeDark ? 'invert(1) brightness(2)' : 'brightness(1.2)'}` } 
                                        : { filter: isThemeDark ? 'invert(1) brightness(1.5) opacity(0.8)' : 'brightness(0.8) grayscale(1) opacity(0.3)' }
                                ]" 
                            />
                        </g>

                        <!-- Label Overlay (Visible on Hover/Select) -->
                        <g class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 transform translate-y-12 pointer-events-none">
                            <rect x="-60" y="0" width="120" height="24" rx="6" fill="rgba(15, 23, 42, 0.9)" stroke="rgba(255,255,255,0.1)" />
                            <text y="16" text-anchor="middle" fill="white" font-size="9" font-weight="900" class="uppercase tracking-widest italic">
                                {{ item.name }}
                            </text>
                        </g>
                    </g>
                </svg>

                <!-- On-Canvas HUD (Bottom Centre) -->
                <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-30 pointer-events-none">
                    <div class="px-8 py-4 bg-white/90  backdrop-blur-2xl border border-slate-200  rounded-full flex items-center gap-8 shadow-[0_30px_60px_-12px_rgba(0,0,0,0.2)]  pointer-events-auto scale-90 md:scale-100 ring-1 ring-black/5 ">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-100  border border-slate-200  flex items-center justify-center text-[10px] font-black text-slate-800  italic">SCR</div>
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 ">Zoom Focus</span>
                        </div>
                        <div class="w-px h-4 bg-slate-200 "></div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-100  border border-slate-200  flex items-center justify-center text-[10px] font-black text-slate-800  italic">DRG</div>
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 ">Pan View</span>
                        </div>
                        <div class="w-px h-4 bg-slate-200 "></div>
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse shadow-[0_0_8px_#3b82f6]"></div>
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-blue-600 ">Lnk Active</span>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Premium Technique Detail Overly (Mission Briefing Style) -->
            <transition name="briefing">
                <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-2 md:p-8 lg:p-12 overflow-y-auto md:overflow-hidden bg-slate-900/60 backdrop-blur-xl">
                    <!-- High-tech backdrop -->
                    <div class="absolute inset-0 transition-opacity" @click="isModalOpen = false"></div>
                    
                    <div class="w-full max-w-6xl h-fit md:h-full max-h-none md:max-h-[850px] rounded-3xl md:rounded-[2.5rem] relative overflow-hidden border flex flex-col md:flex-row animate-briefing-in transition-colors duration-500 shadow-2xl"
                        :class="isThemeDark 
                            ? 'bg-[#020617] border-white/10 shadow-[0_0_100px_rgba(59,130,246,0.15)]' 
                            : 'bg-white border-slate-200 shadow-[0_0_100px_rgba(37,99,235,0.1)]'">
                        <!-- Left Panel: Tactical Footage -->
                        <div class="w-full md:w-[65%] relative flex flex-col border-b md:border-b-0 md:border-r transition-colors duration-500 h-full md:overflow-hidden"
                            :class="isThemeDark ? 'border-white/5 bg-slate-900/40' : 'border-slate-100 bg-slate-50/50'">
                            <!-- Header Info -->
                            <div class="p-6 md:p-8 pb-4">
                                <div class="flex flex-wrap items-center gap-2 md:gap-4 mb-2 md:mb-3">
                                    <div class="px-2 py-0.5 md:px-3 md:py-1 bg-blue-500 text-white text-[8px] md:text-[9px] font-black uppercase tracking-widest rounded-md skew-x-[-12deg]">
                                        Unit {{ selectedItem?.id }}
                                    </div>
                                    <div class="text-[8px] md:text-[9px] font-black uppercase tracking-[0.2em] md:tracking-[0.4em] text-slate-400  italic">Technical Specification</div>
                                </div>
                                <h2 class="text-2xl md:text-5xl font-black italic uppercase tracking-tighter text-slate-900  leading-tight md:leading-none">
                                    {{ getEquipmentInfo(selectedItem)?.name || selectedItem.name }}
                                </h2>
                            </div>

                            <!-- Footage Display -->
                            <div ref="modalLeftPanel" class="flex-1 p-8 pt-4 flex flex-col gap-6 overflow-y-auto custom-scrollbar scroll-smooth">
                                <div class="w-full aspect-video bg-slate-200  rounded-3xl overflow-hidden group relative border border-slate-200  shadow-2xl flex-shrink-0">
                                    <!-- Splash Screen (Visible before Play) -->
                                    <div v-if="!isPlaying" 
                                        class="absolute inset-0 z-30 flex flex-col items-center justify-center p-8 text-center overflow-hidden transition-colors duration-500"
                                        :class="isThemeDark ? 'bg-[#020617]' : 'bg-slate-50'">
                                        <div class="absolute inset-0 bg-gradient-to-b from-blue-600/10 to-transparent opacity-50 "></div>
                                        <!-- Decorative Background Triangle -->
                                        <div class="absolute inset-x-0 top-0 h-full translate-y-[-20%] skew-y-[-10deg]"
                                            :class="isThemeDark ? 'bg-slate-800/20' : 'bg-slate-200/50'"></div>
                                        
                                        <div class="relative z-10 space-y-4">
                                            <div class="text-4xl md:text-6xl font-black italic tracking-tighter">
                                                <span :class="isThemeDark ? 'text-indigo-500' : 'text-indigo-600'">FIT</span>
                                                <span :class="isThemeDark ? 'text-white' : 'text-slate-900'">PUNG</span>
                                            </div>
                                            <div class="space-y-2">
                                                <h3 class="text-xl md:text-2xl font-bold uppercase tracking-tight"
                                                    :class="isThemeDark ? 'text-white' : 'text-slate-800'">Technical Ready</h3>
                                                <p class="text-[10px] md:text-xs font-medium max-w-[280px] leading-relaxed"
                                                    :class="isThemeDark ? 'text-slate-400' : 'text-slate-500'">
                                                    System calibrated. Connect to technical feed to begin execution demo.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Active Video Stream -->
                                    <div v-if="isPlaying" 
                                        class="absolute inset-0 z-10 transition-colors duration-500"
                                        :class="isThemeDark ? 'bg-black' : 'bg-slate-100'">
                                        
                                        <div v-if="muscleVideoInfo" class="w-full h-full">
                                            <iframe 
                                                class="w-full h-full"
                                                :src="`https://www.youtube-nocookie.com/embed/${muscleVideoInfo.id}?autoplay=1&mute=1&rel=0`" 
                                                :title="muscleVideoInfo.title" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                                allowfullscreen
                                            ></iframe>
                                        </div>
                                        <video v-else-if="getEquipmentInfo(selectedItem)?.technique?.video" 
                                            :src="getEquipmentInfo(selectedItem).technique.video" 
                                            class="w-full h-full object-cover" 
                                            controls autoplay muted loop></video>
                                        
                                        <!-- No Video State (Centered) -->
                                        <div v-else class="w-full h-full flex flex-col items-center justify-center p-8">
                                            <div class="w-16 h-16 rounded-full border-2 border-dashed border-indigo-500/30 flex items-center justify-center mb-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-indigo-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div class="text-center space-y-1">
                                                <div class="text-[12px] font-black italic text-indigo-500 uppercase tracking-widest">Technique Unavailable</div>
                                                <div class="text-xs font-bold text-slate-400 ">ยังไม่ครอบคลุมวิดีโอส่วนนี้</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Video HUD Overlay -->
                                    <div v-if="isPlaying" 
                                        class="absolute top-4 left-4 p-3 rounded-xl backdrop-blur-md border flex items-center gap-3 z-20 transition-all duration-500"
                                        :class="isThemeDark ? 'bg-black/40 border-white/5' : 'bg-white/60 border-slate-200 shadow-sm'">
                                        <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                        <span class="text-[8px] font-black uppercase tracking-widest"
                                            :class="isThemeDark ? 'text-white/80' : 'text-slate-600'">Live Execution Demo</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div class="bg-slate-50  rounded-2xl p-6 border border-slate-200  group hover:bg-slate-100  transition-colors">
                                        <h4 class="text-[9px] font-black uppercase tracking-widest text-slate-400  mb-4">Static Reference</h4>
                                        <div class="aspect-square bg-white  rounded-xl overflow-hidden border border-slate-200 ">
                                            <img v-if="isPlaying && muscleImage" 
                                                :src="muscleImage" 
                                                class="w-full h-full object-contain brightness-110 animate-in fade-in duration-500" 
                                            />
                                            <img v-else-if="selectedItem && getEquipmentInfo(selectedItem)?.technique?.image" :src="getEquipmentInfo(selectedItem).technique.image" class="w-full h-full object-cover grayscale brightness-75 hover:grayscale-0 hover:brightness-100 transition-all duration-700" />
                                            <div v-else class="w-full h-full flex flex-col items-center justify-center bg-slate-100  p-4 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-300  mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-[9px] font-black text-slate-400  uppercase tracking-widest leading-tight">No Static Reference<br/><span class="text-[10px] lowercase font-bold">ยังไม่มีรูปภาพ</span></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50  rounded-2xl p-6 border border-slate-200  flex flex-col gap-6 relative group/card">
                                        <div class="flex items-start justify-between">
                                            <div class="space-y-1">
                                                <div class="text-[14px] font-black text-slate-800  italic uppercase tracking-[0.2em]">HOW TO PLAY</div>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                                                    <div class="text-[10px] font-bold text-blue-600  uppercase tracking-widest leading-none">System Active</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Play/Scroll back up to Video -->
                                            <button @click="scrollToVideo" 
                                                class="w-12 h-12 rounded-xl bg-blue-600 hover:bg-white text-white hover:text-blue-600 transition-all duration-500 flex items-center justify-center shadow-[0_10px_20px_rgba(37,99,235,0.2)] group/play overflow-hidden border border-blue-400/20">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-current relative z-10" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Multi-Set Tactical Input List -->
                                        <div class="space-y-3 max-h-[320px] overflow-y-auto custom-scrollbar pr-2">
                                            <!-- List Header -->
                                            <div class="grid grid-cols-4 gap-1 md:gap-2 px-1 mb-1">
                                                <span class="text-[6px] md:text-[7px] font-black text-slate-500 uppercase tracking-widest text-center">Set</span>
                                                <span class="text-[6px] md:text-[7px] font-black text-slate-500 uppercase tracking-widest text-center">Weight</span>
                                                <span class="text-[6px] md:text-[7px] font-black text-slate-500 uppercase tracking-widest text-center">Reps</span>
                                                <span class="text-[6px] md:text-[7px] font-black text-slate-500 uppercase tracking-widest text-center">Status</span>
                                            </div>

                                             <!-- Dynamic Set Rows -->
                                            <div v-for="(set, index) in workoutSets" :key="index" 
                                                class="grid grid-cols-4 gap-1 md:gap-2 animate-in fade-in slide-in-from-top-4 duration-300">
                                                <!-- Set Number -->
                                                <div class="bg-white  border border-slate-200  rounded-xl p-2.5 flex items-center justify-center relative group/delete">
                                                    <span class="text-base font-black italic text-slate-300  group-hover/delete:opacity-0 transition-opacity">{{ index + 1 }}</span>
                                                    <button v-if="workoutSets.length > 1" @click="removeSet(index)" 
                                                        class="absolute inset-0 flex items-center justify-center text-red-500 opacity-0 group-hover/delete:opacity-100 transition-opacity">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </div>
                                                
                                                <!-- Weight Dropdown -->
                                                <div class="bg-white  border border-slate-200  rounded-xl p-2.5 flex items-center justify-center">
                                                    <select v-model="set.weight" class="bg-transparent text-slate-800  text-sm font-black italic text-center appearance-none focus:outline-none cursor-pointer w-full">
                                                        <option v-for="n in [5,7.5,10,12.5,15,17.5,20,22.5,25,30]" :key="n" :value="n" class="bg-white ">{{ n }}kg</option>
                                                    </select>
                                                </div>

                                                <!-- Reps Dropdown -->
                                                <div class="bg-white  border border-slate-200  rounded-xl p-2.5 flex items-center justify-center">
                                                    <select v-model="set.reps" class="bg-transparent text-slate-800  text-sm font-black italic text-center appearance-none focus:outline-none cursor-pointer w-full">
                                                        <option v-for="n in [6,8,10,12,15,20,30]" :key="n" :value="n" class="bg-white ">{{ n }}</option>
                                                    </select>
                                                </div>

                                                <!-- Status Toggle -->
                                                <div @click="toggleSetCompletion(index)" 
                                                    class="cursor-pointer border-2 rounded-xl flex items-center justify-center transition-all duration-300"
                                                    :class="set.isCompleted 
                                                        ? 'bg-emerald-500/10  border-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.2)]' 
                                                        : 'bg-white  border-slate-200  hover:border-blue-500/30'">
                                                    <svg v-if="set.isCompleted" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500 animate-in zoom-in" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <div v-else class="w-1.5 h-1.5 bg-slate-300  rounded-full"></div>
                                                </div>
                                            </div>

                                            <!-- Add Next Set Button -->
                                            <button @click="addNewSet" 
                                                class="w-full h-12 rounded-xl border-2 border-dashed border-white/10 hover:border-blue-500/50 hover:bg-blue-500/5 text-slate-500 hover:text-blue-400 transition-all flex items-center justify-center gap-3 group mt-2">
                                                <svg class="w-5 h-5 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                <span class="text-[10px] font-black uppercase tracking-[0.2em]">Add Next Set</span>
                                            </button>
                                        </div>

                                        <!-- Log Pulse (Total Completed Stats) -->
                                        <div class="flex items-center justify-between px-2">
                                            <div class="flex gap-1">
                                                <div v-for="i in Math.max(5, workoutSets.length)" :key="i" 
                                                    class="w-1 h-3 rounded-full transition-all duration-500" 
                                                    :class="i <= workoutSets.filter(s => s.isCompleted).length ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : (i <= workoutSets.length ? 'bg-blue-500/30' : 'bg-white/5')"></div>
                                            </div>
                                            <div class="flex flex-col items-end">
                                                <span class="text-[7px] font-black uppercase text-slate-500 tracking-[0.3em]">Operational Log Active</span>
                                                <span class="text-[9px] font-black text-blue-400 italic">{{ workoutSets.filter(s => s.isCompleted).length }}/{{ workoutSets.length }} Completed</span>
                                            </div>
                                        </div>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel: Mission Data -->
                        <div class="w-full md:w-[35%] flex flex-col overflow-hidden border-t md:border-t-0 md:border-l transition-colors duration-500"
                            :class="isThemeDark 
                                ? 'bg-[#06060a] border-white/5' 
                                : 'bg-slate-50 border-slate-100'">
                            <div class="flex-1 p-6 md:p-8 overflow-y-auto custom-scrollbar">
                                <div class="space-y-12">
                                    <!-- Target Muscles -->
                                    <section class="space-y-6">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 ">Engaged Modules</h4>
                                            <div class="h-px flex-1 bg-slate-200  mx-4"></div>
                                            <div class="w-2 h-2 rounded-full border border-blue-500/30 "></div>
                                        </div>
                                        <div class="flex flex-wrap gap-3">
                                            <div v-for="m in getEquipmentInfo(selectedItem)?.target_muscles || []" :key="m.key" 
                                                @click="handleMuscleClick(m.key)"
                                                class="flex items-center gap-3 px-5 py-3 border rounded-xl transition-all cursor-pointer"
                                                :class="activeMuscle === m.key 
                                                    ? 'bg-blue-600/10  border-blue-400  shadow-[0_0_15px_rgba(59,130,246,0.15)] ' 
                                                    : 'bg-white  border-slate-200  hover:border-slate-300  hover:bg-slate-50 '"
                                            >
                                                <div class="w-2 h-2 rounded-full transition-shadow duration-300"
                                                    :class="activeMuscle === m.key ? 'bg-blue-500  shadow-[0_0_10px_#3b82f6]' : 'bg-slate-300 '"></div>
                                                <span class="text-[11px] font-black uppercase italic tracking-widest transition-colors"
                                                    :class="activeMuscle === m.key ? 'text-slate-900 ' : 'text-slate-400 '">
                                                    {{ m.name_th }}
                                                </span>
                                            </div>
                                        </div>
                                    </section>

                                     <!-- Pro Tips / Instruction -->
                                    <section class="space-y-6">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 ">Operation Protocol</h4>
                                            <div class="h-px flex-1 bg-slate-200  mx-4"></div>
                                        </div>
                                        <div class="space-y-4">
                                            <div class="p-6 bg-blue-500/[0.03]  rounded-2xl border border-blue-500/10  relative overflow-hidden group">
                                                <div class="absolute top-0 right-0 p-2 opacity-10">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                </div>
                                                <p class="text-xs font-bold text-blue-600  leading-relaxed italic pr-8">
                                                    Focus on controlled movement. Inhale during release, exhale during contraction. Maintain posture alignment.
                                                </p>
                                            </div>
                                            <div class="flex items-start gap-4 p-4 rounded-xl hover:bg-white/5 transition-colors">
                                                <div class="w-1.5 h-1.5 bg-slate-600 rounded-full mt-1.5"></div>
                                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-relaxed">Ensure security locks are engaged before initiation.</p>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>

                            <!-- Action Button (Pinned to bottom) -->
                            <div class="p-8 md:p-8 pt-0">
                                <button @click="confirmFinishWorkout" 
                                    class="group relative w-full h-16 md:h-20 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl overflow-hidden transition-all duration-300 active:scale-[0.98] shadow-[0_20px_40px_-15px_rgba(59,130,246,0.4)]">
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                    <span class="relative z-10 text-sm font-black italic uppercase tracking-[0.3em]">FINISH MISSIONS</span>
                                </button>
                            </div>
                        </div>

                        <!-- Close System Button -->
                        <button @click="isModalOpen = false" 
                            class="absolute top-4 right-4 md:top-10 md:right-10 w-10 h-10 md:w-12 md:h-12 rounded-full border flex items-center justify-center transition-all backdrop-blur-xl z-[60]"
                            :class="isThemeDark 
                                ? 'border-white/10 text-slate-400 hover:text-white hover:border-white/30 bg-slate-900/50' 
                                : 'border-slate-200 text-slate-500 hover:text-slate-900 hover:border-slate-400 bg-white/80'">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');

.font-sans {
    font-family: 'Outfit', sans-serif;
}

/* Animations */
.scanner-ring {
    animation: scan 2s cubic-bezier(0.16, 1, 0.3, 1) infinite;
}

@keyframes scan {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(1.8); opacity: 0; }
}

.animate-spin-slow {
    animation: spin 8s linear infinite;
    transform-origin: center;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

:root {
    --floor-stop-1: #ffffff;
    --floor-stop-2: #f1f5f9;
}

.dark {
    --floor-stop-1: #1e293b;
    --floor-stop-2: #020617;
}

.unit-image {
    transition: filter 0.5s ease, transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.unit-container:hover .unit-image {
    transform: scale(1.15);
    filter: drop-shadow(0 0 15px rgba(59, 130, 246, 0.4)) brightness(1.2) !important;
}

/* Modal Transitions */
.briefing-enter-active, .briefing-leave-active {
    transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.briefing-enter-from, .briefing-leave-to {
    opacity: 0;
    backdrop-filter: blur(0px);
}

.animate-briefing-in {
    animation: briefingSlideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes briefingSlideIn {
    from { 
        opacity: 0; 
        transform: scale(0.8) translateY(100px) skewY(2deg); 
        clip-path: inset(100% 0 0 0);
    }
    to { 
        opacity: 1; 
        transform: scale(1) translateY(0) skewY(0deg);
        clip-path: inset(0 0 0 0);
    }
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { 
    background: rgba(59, 130, 246, 0.15); 
    border-radius: 10px; 
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover { 
    background: rgba(59, 130, 246, 0.3); 
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.2); }
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.4); }

/* Tactical Glitch Effect on Hover (Optional) */
.group:hover h1 {
    text-shadow: 2px 0 #3b82f6, -2px 0 #ef4444;
    animation: glitch 0.3s infinite;
}

@keyframes glitch {
    0% { transform: translate(0); }
    20% { transform: translate(-1px, 1px); }
    40% { transform: translate(-1px, -1px); }
    60% { transform: translate(1px, 1px); }
    80% { transform: translate(1px, -1px); }
    100% { transform: translate(0); }
}
</style>
