<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, onMounted, computed, onUnmounted } from 'vue';

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

// --- Workout Tracking ---
const workoutSets = ref([
    { weight: 10, reps: 12, isCompleted: false }
]);

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

const resetZoom = () => {
    viewBox.value = getInitialBounds(props.gym.room_config.points, props.gym.items, 100);
};

// --- Interaction Helpers ---
const selectItem = (item) => {
    selectedItem.value = item;
    activeMuscle.value = null;
    isPlaying.value = false;
    isModalOpen.value = true;
};

const getEquipmentInfo = (item) => {
    if (!item) return null;
    const filename = item.src.split('/').pop().toLowerCase();
    return props.equipments.find(e => e.filename.toLowerCase() === filename);
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

// --- Lifecycle ---
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

onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('mouseup', handleMouseUp);
});
</script>

<template>
    <MobileLayout>
        <Head :title="'Explore Map - ' + gym.name" />

        <!-- Header (White Template Style) -->
        <header class="flex items-center justify-between p-6 pb-4">
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
        <div class="flex-1 relative bg-white  overflow-hidden min-h-[400px]">
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
                
                <!-- Floor -->
                <polygon :points="gym.room_config.points" 
                    class="fill-[#f8f6f6]  stroke-[var(--theme-color)]/20" 
                    stroke-width="4" 
                    stroke-linejoin="round" />
                
                <!-- Equipment Units -->
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
                            selectedItem?.id === item.id 
                                ? { filter: `drop-shadow(0 0 10px var(--theme-color)) ${isThemeDark ? 'invert(1) brightness(2)' : 'brightness(1.1)'}` } 
                                : { filter: isThemeDark ? 'invert(1) opacity(0.8)' : 'opacity(0.4) grayscale(1)' }
                        ]" 
                    />
                </g>
            </svg>

            <!-- Floating Controls (HUD) -->
            <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex items-center gap-3 px-6 py-3 bg-white/90  backdrop-blur-lg rounded-full border border-gray-100  shadow-xl z-20">
                <button @click="resetZoom" class="flex items-center gap-2 hover:text-[var(--theme-color)] transition-colors ">
                    <span class="material-symbols-outlined text-sm">home</span>
                    <span class="text-[10px] font-black uppercase tracking-tighter">Reset</span>
                </button>
                <div class="w-px h-4 bg-gray-200 "></div>
                <div class="flex items-center gap-2 text-gray-400">
                    <span class="material-symbols-outlined text-sm">mouse</span>
                    <span class="text-[10px] font-black uppercase tracking-tighter">Pan & Zoom</span>
                </div>
            </div>
        </div>

        <!-- Detail Modal (White Template Aesthetic) -->
        <transition name="modal">
            <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-end md:items-center justify-center p-0 md:p-8 bg-black/60 backdrop-blur-sm">
                <div class="absolute inset-0" @click="isModalOpen = false"></div>
                
                <div class="bg-white  w-full max-w-md md:rounded-[24px] rounded-t-[24px] overflow-hidden shadow-2xl relative animate-in slide-in-from-bottom-full duration-500">
                    <!-- Close Handle (Mobile) -->
                    <div class="md:hidden flex justify-center p-4">
                        <div class="w-12 h-1.5 bg-gray-200  rounded-full" @click="isModalOpen = false"></div>
                    </div>

                    <div class="p-8 pt-4 space-y-8 max-h-[85vh] overflow-y-auto">
                        <!-- Header -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[9px] font-black px-2 py-0.5 rounded uppercase">Unit {{ selectedItem?.id }}</span>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Premium Equipment</span>
                            </div>
                            <h2 class="text-3xl font-black leading-tight uppercase italic text-gray-900">{{ getEquipmentInfo(selectedItem)?.name || selectedItem.name }}</h2>
                        </div>

                        <!-- Video/Technique Section -->
                        <div class="bg-gray-100  rounded-[24px] overflow-hidden aspect-video relative group border border-gray-200 ">
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

                        <!-- Target Muscles -->
                        <div class="space-y-4">
                            <h3 class="text-xs font-black uppercase tracking-widest text-[#ec5b13]">Target Muscles</h3>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="m in getEquipmentInfo(selectedItem)?.target_muscles || []" :key="m.key" 
                                    @click="handleMuscleClick(m.key)"
                                    class="px-5 py-3 rounded-2xl border transition-all text-xs font-bold uppercase tracking-tight"
                                    :class="activeMuscle === m.key 
                                        ? 'bg-[var(--theme-color)] text-white border-[var(--theme-color)]' 
                                        : 'bg-white  border-gray-100  text-gray-600  font-black'">
                                    {{ m.name_th }}
                                </button>
                            </div>
                        </div>

                        <!-- Set Tracking -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-black uppercase tracking-widest text-[var(--theme-color)]">Workout Log</h3>
                                <button @click="addNewSet" class="text-[10px] font-black text-[var(--theme-color)] border border-[var(--theme-color)]/20 px-3 py-1 rounded-full">+ ADD SET</button>
                            </div>
                            <div class="space-y-2">
                                <div v-for="(set, index) in workoutSets" :key="index" 
                                    @click="toggleSetCompletion(index)"
                                    class="grid grid-cols-[0.8fr_2.2fr_2fr_1fr] gap-2 items-center bg-gray-50  p-2 rounded-2xl border border-gray-100  cursor-pointer hover:bg-gray-100  transition-all active:scale-[0.98]">
                                    <!-- Delete via Number -->
                                    <div @click.stop="removeSet(index)" 
                                         class="group/num relative flex items-center justify-center size-9 rounded-xl hover:bg-red-50  transition-colors">
                                        <span class="text-base font-black italic text-gray-400 group-hover/num:opacity-0 transition-opacity">{{ index + 1 }}</span>
                                        <span class="material-symbols-outlined absolute opacity-0 group-hover/num:opacity-100 text-red-500 text-base transition-opacity">delete</span>
                                    </div>

                                    <!-- Weight Dropdown -->
                                    <div class="relative" @click.stop>
                                        <select v-model="set.weight" 
                                            class="w-full bg-white  border-none rounded-xl text-[11px] font-black p-2 pr-6 appearance-none focus:ring-1 focus:ring-[var(--theme-color)]  shadow-sm text-gray-900">
                                            <option v-for="w in weightOptions" :key="w" :value="w">{{ w }}kg</option>
                                        </select>
                                        <span class="material-symbols-outlined absolute right-1.5 top-1/2 -translate-y-1/2 text-gray-400 text-[14px] pointer-events-none">expand_more</span>
                                    </div>

                                    <!-- Reps Input -->
                                    <div class="relative" @click.stop>
                                        <input type="number" v-model="set.reps" 
                                            @input="set.reps > 30 ? set.reps = 30 : null"
                                            min="0" max="30"
                                            class="w-full bg-white  border-none rounded-xl text-[11px] font-black p-2 pr-5 text-center focus:ring-1 focus:ring-[var(--theme-color)]  shadow-sm text-gray-900"
                                            placeholder="0"
                                        />
                                        <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[8px] font-black text-gray-400 uppercase tracking-tighter pointer-events-none">R</span>
                                    </div>

                                    <div class="size-8 mx-auto rounded-full flex items-center justify-center transition-all border shadow-sm"
                                        :class="set.isCompleted 
                                            ? 'bg-[var(--theme-color)] text-white border-[var(--theme-color)]' 
                                            : 'bg-white  border-gray-200  text-gray-200  pointer-events-none'">
                                        <span class="material-symbols-outlined text-sm" :class="{ 'fill-icon': set.isCompleted }">check</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Start Action -->
                        <button @click="isModalOpen = false" 
                            class="w-full bg-[var(--theme-color)] text-white py-5 rounded-[24px] font-black text-lg tracking-tight shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all">
                            DONE & CLOSE
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </MobileLayout>
</template>
