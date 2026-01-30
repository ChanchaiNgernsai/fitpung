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
    'Deltoides': 'anterior_deltoids',
    'Deltoid': 'anterior_deltoids',
    'Deltoids_front': 'anterior_deltoids',
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
    'Traps': 'traps',
    'Obliques': 'obliques'
};

const getDbMuscleKey = (id) => {
    if (!id) return null;
    const prefix = id.split('_')[0];
    return svgToDbMuscleMap[prefix] || id.toLowerCase();
};

const availableEquipment = computed(() => {
    if (!selectedMuscle.value) return [];
    const muscleKey = getDbMuscleKey(selectedMuscle.value);
    
    // 1. Filter all items that match the muscle
    const matchingItems = props.gym.items.filter(item => {
        const itemFilename = item.src.split('/').pop().toLowerCase();
        return props.equipments.some(eq => 
            eq.filename.toLowerCase() === itemFilename &&
            eq.target_muscles.some(m => m.key === muscleKey)
        );
    });

    // 2. Deduplicate by filename so each equipment type appears once in the list
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

// Interactive Map Logic
onMounted(async () => {
    try {
        const response = await fetch('/images/Front_body_interactive.svg');
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

                    container.querySelectorAll('.muscle-highlight').forEach(m => {
                        m.classList.remove('muscle-highlight');
                    });
                    
                    if (isAlreadySelected) {
                        selectedMuscle.value = null;
                    } else {
                        const id = target.id;
                        selectedMuscle.value = id; 
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

// --- ViewBox & Interactive State for Fullscreen Map ---
const modalViewBox = ref({ x: 0, y: 0, w: 1000, h: 800 });
const isPanning = ref(false);
const lastMousePos = ref({ x: 0, y: 0 });

const getInitialBounds = (pointsStr, items = [], padding = 150) => {
    if (!pointsStr) return { x: 0, y: 0, w: 1000, h: 800 };
    
    const points = pointsStr.split(' ').map(p => { 
        const [x, y] = p.split(',').map(Number); 
        return { x, y }; 
    });

    let minX = Math.min(...points.map(p => p.x));
    let maxX = Math.max(...points.map(p => p.x));
    let minY = Math.min(...points.map(p => p.y));
    let maxY = Math.max(...points.map(p => p.y));

    if (items.length > 0) {
        items.forEach(item => {
            const hw = item.width / 2;
            const hh = item.height / 2;
            minX = Math.min(minX, item.x - hw);
            maxX = Math.max(maxX, item.x + hw);
            minY = Math.min(minY, item.y - hh);
            maxY = Math.max(maxY, item.y + hh);
        });
    }

    return {
        x: minX - padding,
        y: minY - padding,
        w: (maxX - minX) + (padding * 2),
        h: (maxY - minY) + (padding * 2)
    };
};

const getViewBoxString = (bounds) => `${bounds.x} ${bounds.y} ${bounds.w} ${bounds.h}`;

// Interactive Helpers (Simplified port from Builder)
const getSvgPoint = (clientX, clientY, svgId) => {
    const svg = document.getElementById(svgId);
    if (!svg) return { x: 0, y: 0 };
    const pt = svg.createSVGPoint();
    pt.x = clientX;
    pt.y = clientY;
    const ctm = svg.getScreenCTM();
    if (!ctm) return { x: 0, y: 0 };
    return pt.matrixTransform(ctm.inverse());
};

const handleModalWheel = (event) => {
    if (!isMapExpanded.value) return;
    event.preventDefault();
    const zoomIntensity = 0.1;
    const svgId = 'fullscreen-gym-canvas';
    const svgP = getSvgPoint(event.clientX, event.clientY, svgId);

    const direction = event.deltaY > 0 ? 1 : -1;
    const newW = modalViewBox.value.w * (1 + direction * zoomIntensity);
    const newH = modalViewBox.value.h * (1 + direction * zoomIntensity);

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

const handleModalMouseUp = () => {
    isPanning.value = false;
};

const openMap = () => {
    // Maximum possible expansion with minimal safety margin
    const bounds = getInitialBounds(props.gym.room_config.points, props.gym.items, 5);
    modalViewBox.value = bounds;
    isMapExpanded.value = true;
};

onMounted(() => {
    window.addEventListener('mousemove', handleModalMouseMove);
    window.addEventListener('mouseup', handleModalMouseUp);
});

</script>

<template>
    <Head :title="gym.name + ' - FitPung'" />

    <AppLayout>


        <div class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-8">
                
                <!-- BOTTOM (SIDEBAR ON LG): Info & Gym Map -->
                <div class="lg:col-span-4 space-y-6 order-2 lg:order-none">
                    <!-- Gym Info Card -->
                    <div class="card bg-base-100 shadow-xl border border-base-content/5">
                        <div class="card-body">
                            <h3 class="font-bold text-lg uppercase tracking-wider mb-2 opacity-50">About this Gym</h3>
                            <p class="opacity-80">{{ gym.description || 'Verified fitness facility equipped with professional standard equipment.' }}</p>
                        </div>
                    </div>

                    <!-- Gym Floor Layout -->
                    <div class="card bg-neutral text-neutral-content shadow-xl overflow-hidden relative group">
                        <div class="absolute top-4 left-4 z-10 badge badge-primary font-bold shadow-lg">Gym Layout</div>

                        <div class="h-80 relative flex items-center justify-center pt-8 pb-2 px-4 bg-white border-t border-base-200">
                            <svg 
                                :viewBox="getViewBoxString(getInitialBounds(gym.room_config.points, gym.items, 40))" 
                                class="w-full h-full transition-all duration-500 transform translate-y-6"
                                style="filter: drop-shadow(0 20px 40px rgba(0,0,0,0.15));"
                                preserveAspectRatio="xMidYMid meet"
                            >
                                <!-- Room Shape -->
                                <polygon 
                                    :points="gym.room_config.points" 
                                    fill="#111827" 
                                    stroke="currentColor" 
                                    stroke-width="8"
                                    class="text-base-content/10"
                                />
                                
                                <!-- Items -->
                                <g 
                                    v-for="item in gym.items" 
                                    :key="item.id" 
                                    :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                                    class="transition-all duration-300"
                                    :class="isItemHighlight(item) ? 'opacity-100' : 'opacity-60'"
                                >
                                    <!-- Green Glow Ring -->
                                    <circle 
                                        v-if="isItemHighlight(item)"
                                        r="35" 
                                        fill="rgba(0,255,0,0.2)" 
                                        stroke="#00ff00" 
                                        stroke-width="5" 
                                        class="animate-pulse"
                                    />
                                    
                                     <image 
                                        :href="item.src" 
                                        :x="-item.width/2" 
                                        :y="-item.height/2" 
                                        :width="item.width" 
                                        :height="item.height"
                                        class="transition-all duration-500"
                                        :style="isItemHighlight(item) ? { filter: 'drop-shadow(0 0 8px #00ff00) brightness(1.5)', transform: 'scale(1.1)' } : { filter: 'grayscale(1) brightness(0.7)' }"
                                    />
                                </g>
                            </svg>

                            <!-- Expand Button -->
                            <button @click="openMap" class="absolute bottom-4 right-4 btn btn-circle btn-sm btn-ghost bg-base-100/20 hover:bg-base-100/40 backdrop-blur-sm border-none transition-all hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Workout Plan Summary -->
                     <div class="card bg-base-100 shadow-xl border border-base-content/5">
                        <div class="card-body">
                            <h3 class="font-bold text-lg uppercase tracking-wider mb-2 opacity-50">Your Plan</h3>
                            <div v-if="workoutPlan.length === 0" class="text-sm opacity-50 italic">
                                Select muscles and add equipment to build your plan.
                            </div>
                            <ul class="space-y-4">
                                <li v-for="plan in workoutPlan" :key="plan.id" class="bg-base-200 p-3 rounded-lg relative group border-l-4 border-primary">
                                    <button @click="removeFromPlan(plan.id)" class="btn btn-circle btn-xs btn-error absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity shadow-sm">x</button>
                                    <div class="font-bold text-base-content">{{ plan.item.name }}</div>
                                    <div class="text-xs opacity-50 mb-2">{{ plan.muscle }}</div>
                                    <div class="flex gap-2">
                                        <div class="flex flex-col gap-1 w-1/2">
                                            <span class="text-[10px] uppercase font-bold opacity-50">Sets</span>
                                            <input type="number" v-model="plan.sets" class="input input-xs input-bordered w-full bg-base-100" />
                                        </div>
                                        <div class="flex flex-col gap-1 w-1/2">
                                            <span class="text-[10px] uppercase font-bold opacity-50">Reps</span>
                                            <input type="number" v-model="plan.reps" class="input input-xs input-bordered w-full bg-base-100" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <button v-if="workoutPlan.length > 0" class="btn btn-primary btn-sm mt-4 w-full shadow-lg shadow-primary/20 animate-pulse">Start Workout</button>
                        </div>
                    </div>
                </div>

                <!-- TOP (MIDDLE ON LG): Interactive Map -->
                <div class="lg:col-span-4 flex flex-col items-center order-1 lg:order-none">
                    <div class="text-center mb-6">
                        <div class="badge badge-accent badge-lg font-bold mb-2 shadow-lg shadow-accent/20">Interactive Selector</div>
                        <h2 class="text-2xl font-black italic uppercase">TARGET YOUR MUSCLES</h2>
                        <p class="text-sm opacity-60">คลิกที่ส่วนกล้ามเนื้อที่ต้องการฝึก</p>
                    </div>

                    <div 
                        id="muscle-map-container"
                        class="w-full h-[600px] bg-base-100 rounded-3xl shadow-2xl border border-base-content/5 p-4 overflow-hidden relative"
                    >
                        <!-- SVG Injected Here -->
                        <div v-if="svgContent" v-html="svgContent" class="w-full h-full"></div>
                        
                        <div v-else class="flex items-center justify-center h-full">
                            <span class="loading loading-spinner loading-lg text-primary"></span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Equipment & Videos (3 cols) -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <div v-if="selectedMuscle" class="animate-fade-in-up">
                         <div class="alert bg-base-100 shadow-lg border-l-4 border-primary p-4 mb-6">
                            <div>
                                <div class="text-xs opacity-50 uppercase font-bold">Selected Muscle Group</div>
                                <div class="text-xl font-black italic text-primary">{{ cleanMuscleName(selectedMuscle) }}</div>
                            </div>
                         </div>
                         
                         <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                            <span>Available Equipment</span>
                            <span class="badge badge-sm badge-ghost">{{ availableEquipment.length }}</span>
                         </h3>
                         
                         <div v-if="availableEquipment.length > 0" class="space-y-4">
                             <div 
                                v-for="item in availableEquipment" 
                                :key="item.id" 
                                class="card bg-base-100 shadow-md hover:shadow-xl transition-all border border-base-content/5 overflow-hidden group hover:-translate-y-1 duration-300"
                            >
                                <figure class="h-32 bg-base-200 relative overflow-hidden">
                                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white/10 to-transparent z-0"></div>
                                    <img :src="item.src" class="h-full object-contain p-4 relative z-10 transition-transform duration-500 group-hover:scale-110" />
                                </figure>
                                <div class="card-body p-4">
                                    <h4 class="font-bold text-sm">{{ item.name }}</h4>
                                    
                                    <!-- Technique Details -->
                                    <div v-if="item.dbInfo && item.dbInfo.technique[getDbMuscleKey(selectedMuscle)]" class="mt-4 space-y-3 bg-base-200/50 p-3 rounded-xl text-xs">
                                        <div class="badge badge-outline gap-2">{{ item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].variation_name }}</div>
                                        
                                        <div v-if="item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].setup">
                                            <span class="font-bold text-primary mr-1">{{ item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].setup.title }}:</span>
                                            <span class="opacity-70">{{ item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].setup.description }}</span>
                                        </div>
                                        
                                        <div v-if="item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].elbow_angle">
                                            <span class="font-bold text-primary mr-1">{{ item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].elbow_angle.title }}:</span>
                                            <span class="opacity-70">{{ item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].elbow_angle.description }}</span>
                                            <div v-if="item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].elbow_angle.warning" class="text-[10px] text-error mt-1 italic font-bold">
                                                ⚠ {{ item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].elbow_angle.warning }}
                                            </div>
                                        </div>
                                        
                                        <div v-if="item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].breathing">
                                            <span class="font-bold text-primary mr-1">{{ item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].breathing.title }}:</span>
                                            <span class="opacity-70">{{ item.dbInfo.technique[getDbMuscleKey(selectedMuscle)].breathing.description }}</span>
                                        </div>
                                    </div>

                                    <div class="card-actions mt-4 border-t border-base-content/5 pt-3">
                                        <button @click="addToPlan(item)" class="btn btn-sm btn-primary w-full shadow-lg shadow-primary/20">
                                            + Add to Plan
                                        </button>
                                    </div>
                                </div>
                             </div>
                         </div>
                         
                         <div v-else class="text-center py-12 px-6 bg-base-100 rounded-2xl border-2 border-dashed border-base-content/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="font-bold opacity-60">No matching equipment found</p>
                            <p class="text-xs opacity-40 mt-1">This gym doesn't have specific machines for {{ cleanMuscleName(selectedMuscle) }} yet.</p>
                         </div>

                    </div>
                    
                    <div v-else class="bg-base-200/50 rounded-3xl p-10 text-center border-2 border-dashed border-base-content/10 h-full flex flex-col justify-center items-center opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" /></svg>
                        <p class="font-bold text-lg">Select a Muscle</p>
                        <p class="text-sm">Explore the interactive body map to see what you can train.</p>
                    </div>

                </div>
            </div>
        </div>

        <!-- Fullscreen Map Modal -->
        <div v-if="isMapExpanded" class="fixed inset-0 z-[100] flex items-center justify-center p-2 md:p-4">
            <div class="absolute inset-0 bg-white/80 backdrop-blur-3xl animate-fade-in" @click="isMapExpanded = false"></div>
            
            <div class="card w-full max-w-[96vw] h-full max-h-[96vh] bg-white shadow-2xl relative animate-zoom-in overflow-hidden border border-black/5">
                <div class="flex-1 relative flex items-center justify-center overflow-hidden bg-white">
                    <!-- Floating Close Button -->
                    <button @click="isMapExpanded = false" class="absolute top-6 right-6 z-50 btn btn-circle btn-sm btn-neutral shadow-lg hover:scale-110 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>

                    <svg 
                        id="fullscreen-gym-canvas"
                        :viewBox="getViewBoxString(modalViewBox)" 
                        class="w-full h-full cursor-move touch-none transform translate-y-10"
                        preserveAspectRatio="xMidYMid meet"
                        @wheel="handleModalWheel"
                        @mousedown="handleModalMouseDown"
                    >
                         <!-- Room Shape -->
                         <polygon 
                            :points="gym.room_config.points" 
                            fill="#111827" 
                            stroke="currentColor" 
                            stroke-width="5"
                            class="text-base-content/10"
                        />
                        
                        <!-- Items -->
                        <g 
                            v-for="item in gym.items" 
                            :key="'fs-'+item.id" 
                            :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                        >
                            <circle 
                                v-if="isItemHighlight(item)"
                                r="40" 
                                fill="rgba(0,255,0,0.15)" 
                                stroke="#00ff00" 
                                stroke-width="4" 
                                class="animate-pulse"
                            />
                             <image 
                                :href="item.src" 
                                :x="-item.width/2" 
                                :y="-item.height/2" 
                                :width="item.width" 
                                :height="item.height"
                                :style="isItemHighlight(item) ? { filter: 'drop-shadow(0 0 15px #00ff00) brightness(1.5)', transform: 'scale(1.15)' } : { filter: 'grayscale(1) opacity(0.5)' }"
                                class="transition-all duration-300"
                            />
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-zoom-in {
    animation: zoom-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes zoom-in {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>
