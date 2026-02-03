<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, computed, nextTick } from 'vue';

const props = defineProps({
    gym: Object,
    equipments: Array,
});

const svgContent = ref('');
const selectedMuscle = ref(null);
const workoutPlan = ref([]);
const isMapExpanded = ref(false);

// Map SVG ID prefixes to DB muscle keys
const svgToDbMuscleMap = {
    'Pecs': 'pectorals',
    'Triceps': 'triceps',
    'Biceps': 'biceps',
    'Brachialis': 'biceps',
    'Brachioradialis': 'forearms',
    'Deltoid': 'anterior_deltoids',
    'Deltoids': 'anterior_deltoids',
    'Front_traps': 'traps',
    'Traps': 'traps',
    'Latissimus': 'latissimus_dorsi',
    'Gluteus': 'glutes',
    'Lower_abs': 'abs',
    'Upper_abs': 'abs',
    'Mid_quad': 'quadriceps',
    'Inner_quads': 'quadriceps',
    'Outer_quads': 'quadriceps',
    'Soleus': 'calves',
    'Calves': 'calves',
    'Scm': 'neck',
    'Obliques': 'obliques'
};

const getDbMuscleKey = (id) => {
    if (!id) return null;
    const lowerId = id.toLowerCase();
    
    // Check for explicit substrings first
    if (lowerId.includes('pecs')) return 'pectorals';
    if (lowerId.includes('triceps')) return 'triceps';
    if (lowerId.includes('biceps')) return 'biceps';
    if (lowerId.includes('deltoid')) return 'anterior_deltoids';
    if (lowerId.includes('trap')) return 'traps';
    if (lowerId.includes('latissimus')) return 'latissimus_dorsi';
    if (lowerId.includes('gluteus')) return 'glutes';
    if (lowerId.includes('abs')) return 'abs';
    if (lowerId.includes('quad')) return 'quadriceps';
    if (lowerId.includes('soleus')) return 'calves';
    if (lowerId.includes('calves')) return 'calves';
    if (lowerId.includes('flexor') || lowerId.includes('extensor') || lowerId.includes('brachioradialis')) return 'forearms';
    if (lowerId.includes('scm')) return 'neck';
    if (lowerId.includes('obliques')) return 'obliques';

    // Fallback to prefix mapping
    const prefix = id.split('_')[0];
    return svgToDbMuscleMap[prefix] || id.toLowerCase();
};

const availableEquipment = computed(() => {
    if (!selectedMuscle.value) return [];
    const muscleKey = getDbMuscleKey(selectedMuscle.value);
    
    const matchingItems = props.gym.items.filter(item => {
        const itemFilename = item.src.split('/').pop().toLowerCase();
        return props.equipments.some(eq => 
            eq.filename.toLowerCase() === itemFilename &&
            eq.target_muscles.some(m => {
                if (muscleKey === 'pectorals') {
                    return m.key === 'pectorals' || m.key === 'upper_pectorals' || m.key === 'lower_pectorals';
                }
                if (muscleKey === 'calves') {
                   return m.key === 'calves' || m.key === 'soleus';
                }
                return m.key === muscleKey;
            })
        );
    });

    const uniqueMap = new Map();
    matchingItems.forEach(item => {
        const itemFilename = item.src.split('/').pop().toLowerCase();
        if (!uniqueMap.has(itemFilename)) {
            const dbInfo = props.equipments.find(e => e.filename.toLowerCase() === itemFilename);
            uniqueMap.set(itemFilename, { ...item, dbInfo });
        }
    });

    return Array.from(uniqueMap.values());
});

onMounted(async () => {
    try {
        const response = await fetch('/images/Front_body_interactive.svg?v=' + Date.now());
        let text = await response.text();
        svgContent.value = text;
        
        nextTick(() => {
            const container = document.getElementById('muscle-map-container');
            if (!container) return;
            const muscles = container.querySelectorAll('.interactive-muscle'); 
            muscles.forEach(el => {
                el.addEventListener('click', (e) => {
                    const target = e.currentTarget;
                    const isAlreadySelected = target.classList.contains('muscle-highlight');
                    container.querySelectorAll('.muscle-highlight').forEach(m => m.classList.remove('muscle-highlight'));
                    if (isAlreadySelected) {
                        selectedMuscle.value = null;
                    } else {
                        selectedMuscle.value = target.id; 
                        target.classList.add('muscle-highlight');
                    }
                    e.stopPropagation();
                });
            });
        });
    } catch (e) {
        console.error("Failed to load muscle map", e);
    }
});

const isItemHighlight = (item) => {
    if (!selectedMuscle.value) return false;
    const muscleKey = getDbMuscleKey(selectedMuscle.value);
    const itemFilename = item.src.split('/').pop().toLowerCase();
    return props.equipments.some(eq => 
        eq.filename.toLowerCase() === itemFilename &&
        eq.target_muscles.some(m => m.key === muscleKey)
    );
};

const cleanMuscleName = (id) => {
    if (!id) return '';
    const key = getDbMuscleKey(id);
    for (const eq of props.equipments) {
        const muscle = eq.target_muscles.find(m => m.key === key);
        if (muscle) return muscle.name_th;
    }
    return id.replace(/_\d+.*$/, '').replace(/_/g, ' ').replace(/Left|Right/g, '').trim();
};

const addToPlan = (item) => {
    workoutPlan.value.push({
        id: Date.now(),
        item: item,
        muscle: cleanMuscleName(selectedMuscle.value),
        sets: 3,
        reps: 12
    });
};

const removeFromPlan = (id) => {
    workoutPlan.value = workoutPlan.value.filter(x => x.id !== id);
};

// --- Fullscreen Map & Pan/Zoom Logic ---
const modalViewBox = ref({ x: 0, y: 0, w: 1000, h: 800 });
const isPanning = ref(false);
const lastMousePos = ref({ x: 0, y: 0 });

const getInitialBounds = (pointsStr, items = [], padding = 200) => {
    // Default fallback
    const defaultBounds = { x: 0, y: 0, w: 1000, h: 800 };
    if (!pointsStr) return defaultBounds;

    // Parse floor points
    const points = pointsStr.trim().split(/\s+/).map(p => { 
        const parts = p.split(',').map(n => parseFloat(n));
        if (parts.length < 2 || isNaN(parts[0]) || isNaN(parts[1])) return null;
        return { x: parts[0], y: parts[1] }; 
    }).filter(p => p !== null);

    if (points.length === 0) return defaultBounds;

    let minX = Math.min(...points.map(p => p.x));
    let maxX = Math.max(...points.map(p => p.x));
    let minY = Math.min(...points.map(p => p.y));
    let maxY = Math.max(...points.map(p => p.y));

    // Expand bounds to include all items
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

    const w = maxX - minX;
    const h = maxY - minY;

    // Safety check for collapsed bounds
    if (w <= 1 || h <= 1) return defaultBounds;

    return { 
        x: minX - padding, 
        y: minY - padding, 
        w: w + (padding * 2), 
        h: h + (padding * 2) 
    };
};

const getViewBoxString = (bounds) => `${bounds.x} ${bounds.y} ${bounds.w} ${bounds.h}`;

const getSvgPoint = (clientX, clientY, svgId) => {
    const svg = document.getElementById(svgId);
    if (!svg) return { x: 0, y: 0 };
    const pt = svg.createSVGPoint();
    pt.x = clientX; pt.y = clientY;
    const ctm = svg.getScreenCTM();
    if (!ctm) return { x: 0, y: 0 };
    return pt.matrixTransform(ctm.inverse());
};

const handleModalWheel = (event) => {
    if (!isMapExpanded.value) return;
    event.preventDefault();
    const svgP = getSvgPoint(event.clientX, event.clientY, 'fullscreen-gym-canvas');
    const direction = event.deltaY > 0 ? 1 : -1;
    const newW = modalViewBox.value.w * (1 + direction * 0.1);
    const newH = modalViewBox.value.h * (1 + direction * 0.1);
    if (newW < 100 || newW > 8000) return;
    const mouseRelX = (svgP.x - modalViewBox.value.x) / modalViewBox.value.w;
    const mouseRelY = (svgP.y - modalViewBox.value.y) / modalViewBox.value.h;
    modalViewBox.value.x = svgP.x - (mouseRelX * newW);
    modalViewBox.value.y = svgP.y - (mouseRelY * newH);
    modalViewBox.value.w = newW;
    modalViewBox.value.h = newH;
};

const handleModalMouseDown = (event) => {
    isPanning.value = true;
    lastMousePos.value = { x: event.clientX, y: event.clientY };
};

const handleModalMouseMove = (event) => {
    if (!isPanning.value || !isMapExpanded.value) return;
    const dx = event.clientX - lastMousePos.value.x;
    const dy = event.clientY - lastMousePos.value.y;
    const svg = document.getElementById('fullscreen-gym-canvas');
    if (!svg) return;
    const scaleX = modalViewBox.value.w / svg.clientWidth;
    const scaleY = modalViewBox.value.h / svg.clientHeight;
    modalViewBox.value.x -= dx * scaleX;
    modalViewBox.value.y -= dy * scaleY;
    lastMousePos.value = { x: event.clientX, y: event.clientY };
};

onMounted(() => {
    window.addEventListener('mousemove', handleModalMouseMove);
    window.addEventListener('mouseup', () => isPanning.value = false);
});

const openMap = () => {
    modalViewBox.value = getInitialBounds(props.gym.room_config.points, props.gym.items, 60);
    isMapExpanded.value = true;
};

</script>

<template>
    <Head :title="'Session Planner - ' + gym.name" />

    <AppLayout>
        <div class="bg-base-100 min-h-screen">
            <!-- Header Bar -->
            <div class="border-b border-base-content/5 py-8 px-6 md:px-12">
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="space-y-2">
                        <Link :href="route('gyms.show', gym.id)" class="text-xs font-black uppercase tracking-[0.3em] text-primary flex items-center gap-2 hover:opacity-70 transition-opacity">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                            Back to {{ gym.name }}
                        </Link>
                        <h1 class="text-4xl md:text-5xl font-black italic uppercase tracking-tighter">Interactive Map Selector</h1>
                        <p class="opacity-50 text-sm max-w-xl">Select a muscle group to discover matching equipment and build your plan.</p>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-6 py-12">
                <div class="grid lg:grid-cols-12 gap-12">
                    
                    <!-- LEFT: Floor & Plan -->
                    <div class="lg:col-span-4 space-y-8">
                        <div class="card bg-white border border-base-content/5 rounded-[2.5rem] overflow-hidden group">
                            <div class="h-96 relative flex items-center justify-center bg-white">
                                <svg 
                                    :viewBox="getViewBoxString(getInitialBounds(gym.room_config.points, gym.items, 50))" 
                                    class="w-full h-full transition-all duration-700"
                                    preserveAspectRatio="xMidYMid meet"
                                >
                                    <polygon :points="gym.room_config.points" fill="#0f172a" stroke="#0f172a" stroke-width="8" />
                                    <g v-for="item in gym.items" :key="item.id" :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`">
                                        <circle v-if="isItemHighlight(item)" r="35" fill="rgba(0,255,0,0.2)" stroke="#00ff00" stroke-width="5" class="animate-pulse" />
                                        <image :href="item.src" :x="-item.width/2" :y="-item.height/2" :width="item.width" :height="item.height" :style="isItemHighlight(item) ? { filter: 'drop-shadow(0 0 10px #00ff00) brightness(1.5)' } : { filter: 'grayscale(1) brightness(0.5)' }" />
                                    </g>
                                </svg>
                                <button @click="openMap" class="absolute bottom-6 right-6 btn btn-circle btn-neutral shadow-2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                                </button>
                            </div>
                        </div>

                        <div class="card bg-base-100 border border-base-content/5 rounded-[2.5rem] p-8">
                            <h3 class="font-black italic text-xl uppercase mb-6">Your Plan</h3>
                            <div v-if="workoutPlan.length === 0" class="text-sm opacity-40 italic py-10 text-center">Empty plan. Select muscles to start.</div>
                            <ul class="space-y-4">
                                <li v-for="plan in workoutPlan" :key="plan.id" class="bg-base-200/50 p-5 rounded-2xl relative group border-l-4 border-primary">
                                    <button @click="removeFromPlan(plan.id)" class="btn btn-circle btn-xs btn-error absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity">x</button>
                                    <div class="font-black italic uppercase text-sm">{{ plan.item.dbInfo?.name || plan.item.name }}</div>
                                    <div class="text-[10px] font-black uppercase text-primary mb-3">{{ plan.muscle }}</div>
                                    <div class="flex gap-4">
                                        <div class="flex flex-col gap-1 w-1/2">
                                            <span class="text-[9px] uppercase font-black opacity-30">Sets</span>
                                            <input type="number" v-model="plan.sets" class="input input-sm input-bordered w-full bg-base-100" />
                                        </div>
                                        <div class="flex flex-col gap-1 w-1/2">
                                            <span class="text-[9px] uppercase font-black opacity-30">Reps</span>
                                            <input type="number" v-model="plan.reps" class="input input-sm input-bordered w-full bg-base-100" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- CENTER: Muscle Map -->
                    <div class="lg:col-span-4">
                        <div id="muscle-map-container" class="w-full h-[700px] bg-base-100 rounded-[3rem] border border-base-content/5 relative overflow-hidden group flex items-center justify-center p-12">
                            <div v-if="svgContent" v-html="svgContent" class="h-full w-full flex items-center justify-center"></div>
                            <div v-else class="flex items-center justify-center h-full"><span class="loading loading-spinner loading-lg"></span></div>
                        </div>
                    </div>

                    <!-- RIGHT: Equipment -->
                    <div class="lg:col-span-4 space-y-8">
                        <div v-if="selectedMuscle" class="animate-fade-in-up space-y-8">
                             <div class="bg-primary text-white p-8 rounded-[2.5rem] relative overflow-hidden">
                                <div class="text-[10px] font-black uppercase tracking-[0.4em] mb-2 opacity-60">Target Area</div>
                                <div class="text-4xl font-black italic uppercase">{{ cleanMuscleName(selectedMuscle) }}</div>
                             </div>
                             <div v-if="availableEquipment.length > 0" class="space-y-6">
                                 <div v-for="item in availableEquipment" :key="item.id" class="bg-white rounded-[2.5rem] border border-base-content/5 overflow-hidden hover:-translate-y-2 transition-all p-8 space-y-6">
                                    <img :src="item.src" class="h-32 mx-auto object-contain" />
                                     <h4 class="font-black italic uppercase text-lg text-black">{{ item.dbInfo?.name || item.name }}</h4>
                                    <button @click="addToPlan(item)" class="btn btn-primary btn-block rounded-xl font-black italic uppercase tracking-widest">Add to Plan</button>
                                 </div>
                             </div>
                             <div v-else class="text-center py-20 opacity-30 uppercase font-black italic">No Matching Equipment</div>
                        </div>
                        <div v-else class="bg-base-200/30 rounded-[3rem] p-16 text-center border-2 border-dashed border-base-content/10 h-full flex flex-col justify-center opacity-30">
                            <h4 class="font-black text-2xl uppercase italic mb-4">Select Muscle</h4>
                            <p class="text-xs font-bold px-10">Use the body map to explore available machines.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fullscreen Modal -->
        <div v-if="isMapExpanded" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-[#0f172a]/95 backdrop-blur-2xl" @click="isMapExpanded = false"></div>
            <div class="w-full max-w-[95vw] h-full max-h-[90vh] bg-white rounded-[4rem] relative overflow-hidden shadow-6xl p-20">
                <button @click="isMapExpanded = false" class="absolute top-10 right-10 z-50 btn btn-circle btn-neutral btn-lg">×</button>
                <svg id="fullscreen-gym-canvas" :viewBox="getViewBoxString(modalViewBox)" class="w-full h-full cursor-move" preserveAspectRatio="xMidYMid meet" @wheel="handleModalWheel" @mousedown="handleModalMouseDown">
                     <polygon :points="gym.room_config.points" fill="#0f172a" />
                     <g v-for="item in gym.items" :key="'fs-'+item.id" :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`">
                        <circle v-if="isItemHighlight(item)" r="40" fill="rgba(0,255,0,0.15)" stroke="#00ff00" stroke-width="4" />
                        <image :href="item.src" :x="-item.width/2" :y="-item.height/2" :width="item.width" :height="item.height" :style="isItemHighlight(item) ? { filter: 'drop-shadow(0 0 15px #00ff00) brightness(1.5)' } : { filter: 'grayscale(1) opacity(0.5)' }" />
                     </g>
                </svg>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
#muscle-map-container :deep(svg) {
    height: 100%;
    width: auto;
    margin: 0 auto;
    filter: none !important;
}
#muscle-map-container :deep(svg *) {
    filter: none !important;
    box-shadow: none !important;
}
.animate-fade-in-up { animation: fadeInUp 0.5s ease-out; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
