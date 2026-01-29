<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, computed, nextTick } from 'vue';

const props = defineProps({
    gym: Object,
});

const svgContent = ref('');
const selectedMuscle = ref(null);
const workoutPlan = ref([]);

// Mapping Muscle ID (from SVG) -> Equipment Types
const muscleMapping = {
    'Pectoralis_Major': ['Bench Press', 'Smith Machine', 'Chest Press'],
    'Rectus_Abdominis': ['Crunch Machine', 'Yoga Mat'],
    'Obliques': ['Cable Machine'],
    'Deltoid': ['Shoulder Press', 'Dumbbells'],
    'Biceps': ['Dumbbells', 'Barbell'],
    'Quadriceps': ['Leg Press', 'Squat Rack', 'Leg Extension'],
    'Gastrocnemius': ['Calf Raise'], 
    // Add fuzzy matching for complex IDs
};

const getEquipmentForMuscle = (muscleId) => {
    // Simple fuzzy match for demo
    if (!muscleId) return [];
    const id = muscleId.toLowerCase();
    
    // Core Logic: Filter the GYM's actual items based on the muscle
    // But first we need to know what TYPE of equipment targets this muscle.
    // In a real app, EquipmentType would have a 'muscles_targeted' field.
    // Here we assume item.name or similar exists, but GymLayout.items only has { id, name, type, x, y ... }
    
    // Let's assume we map generic names
    let targetTypes = [];
    if (id.includes('pectoralis')) targetTypes = ['Bench Press', 'Smith Machine', 'Pec Fly'];
    else if (id.includes('abdominis') || id.includes('abdominal')) targetTypes = ['Mat', 'Crunch'];
    else if (id.includes('deltoid')) targetTypes = ['Shoulder Press', 'Dumbbell'];
    else if (id.includes('bicep')) targetTypes = ['Dumbbell', 'Barbell'];
    else if (id.includes('quadriceps') || id.includes('femoris') || id.includes('sartorius')) targetTypes = ['Leg Press', 'Squat Rack', 'Leg Extension'];
    else if (id.includes('gastrocnemius') || id.includes('soleus') || id.includes('tibialis')) targetTypes = ['Calf Raise', 'Leg Press'];
    else if (id.includes('gluteus')) targetTypes = ['Squat Rack', 'Leg Press'];
    
    // Now find matching items in the gym
    return props.gym.items.filter(item => {
        return targetTypes.some(type => item.name.toLowerCase().includes(type.toLowerCase()));
    });
};

const availableEquipment = computed(() => {
    if (!selectedMuscle.value) return [];
    return getEquipmentForMuscle(selectedMuscle.value);
});

// Interactive Map Logic
onMounted(async () => {
    try {
        const response = await fetch('/images/Front_body_interactive.svg');
        let text = await response.text();
        
        // Remove scripts/styles if they conflict, but we accept them for now
        // We need to inject click handlers. Since we can't easily modify string binding events,
        // we'll rely on global delegation or finding elements after mount.
        svgContent.value = text;
        
        nextTick(() => {
            const container = document.getElementById('muscle-map-container');
            if (!container) return;
            
            // Attach listeners to all elements with class 'interactive-muscle'
            // or just all groups if class is missing in some
            const muscles = container.querySelectorAll('.interactive-muscle, [id*="_"]'); // broad selector
            
            muscles.forEach(el => {
                el.style.cursor = 'pointer';
                el.classList.add('hover:opacity-80', 'transition-opacity');
                
                el.addEventListener('click', (e) => {
                    // Reset previous selection style
                    muscles.forEach(m => m.style.fill = ''); 
                    
                    const target = e.currentTarget;
                    const id = target.id;
                    const name = target.getAttribute('data-name') || id;
                    
                    console.log('Clicked muscle:', name);
                    selectedMuscle.value = id; // Store ID for logic
                    
                    // Highlight
                    target.style.fill = '#ec4899'; // Pink/Primary color
                    e.stopPropagation();
                });
            });
        });
    } catch (e) {
        console.error("Failed to load muscle map", e);
    }
});

// Helper to clean muscle ID
const cleanMuscleName = (id) => {
    if (!id) return '';
    // Remove numbers and file artifacts, replace underscores
    return id.replace(/_\d+.*$/, '') // Remove trailing numbers
             .replace(/_/g, ' ')     // Replace underscores
             .replace(/Left|Right/g, '') // Remove side if generic
             .trim();
};

const isItemHighlight = (item) => {
    if (!selectedMuscle.value) return false;
    // Check if this item is in the Available Equipment list for the current muscle
    // This is a simple O(N) lookup, for large lists use a Set
    return availableEquipment.value.some(eq => eq.id === item.id);
};

const addToPlan = (item) => {
    workoutPlan.value.push({
        id: Date.now(),
        item: item,
        loading: false,
        sets: 3,
        reps: 12,
        muscle: cleanMuscleName(selectedMuscle.value)
    });
};

const removeFromPlan = (id) => {
    workoutPlan.value = workoutPlan.value.filter(x => x.id !== id);
};

// SVG Preview helper
const getViewBox = (pointsStr) => {
    if (!pointsStr) return '0 0 1000 800';
    const points = pointsStr.split(' ').map(p => { const [x, y] = p.split(',').map(Number); return { x, y }; });
    const minX = Math.min(...points.map(p => p.x));
    const maxX = Math.max(...points.map(p => p.x));
    const minY = Math.min(...points.map(p => p.y));
    const maxY = Math.max(...points.map(p => p.y));
    const padding = 50; 
    return `${minX - padding} ${minY - padding} ${maxX - minX + padding*2} ${maxY - minY + padding*2}`;
};
</script>

<template>
    <Head :title="gym.name + ' - FitPung'" />

    <AppLayout>


        <div class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-8">
                
                <!-- LEFT: Sidebar Info & Gym Map (4 cols) -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Gym Info Card -->
                    <div class="card bg-base-100 shadow-xl border border-base-content/5">
                        <div class="card-body">
                            <h3 class="font-bold text-lg uppercase tracking-wider mb-2 opacity-50">About this Gym</h3>
                            <p class="opacity-80">{{ gym.description || 'Verified fitness facility equipped with professional standard equipment.' }}</p>
                        </div>
                    </div>

                    <!-- Gym Floor Preview -->
                    <div class="card bg-neutral text-neutral-content shadow-xl overflow-hidden relative group">
                         <div class="absolute top-4 left-4 z-10 badge badge-primary font-bold shadow-lg">Gym Layout</div>
                        <div class="h-64 relative flex items-center justify-center p-8 bg-gradient-to-br from-neutral to-base-300">
                             <!-- Background Pattern -->
                             <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
                            
                            <svg 
                                :viewBox="getViewBox(gym.room_config.points)" 
                                class="w-full h-full drop-shadow-2xl transition-all duration-500"
                                preserveAspectRatio="xMidYMid meet"
                            >
                                <!-- Room Shape -->
                                <polygon 
                                    :points="gym.room_config.points" 
                                    fill="#1f2937" 
                                    stroke="currentColor" 
                                    stroke-width="5"
                                    class="text-base-content/20"
                                />
                                
                                <!-- Items -->
                                <g 
                                    v-for="item in gym.items" 
                                    :key="item.id" 
                                    :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                                    class="transition-all duration-300"
                                    :class="isItemHighlight(item) ? 'scale-125' : 'opacity-90'"
                                >
                                    <!-- Highlight Ring -->
                                    <circle 
                                        v-if="isItemHighlight(item)"
                                        r="30" 
                                        fill="none" 
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
                                        class="drop-shadow-sm filter"
                                        :class="isItemHighlight(item) ? 'brightness-125 drop-shadow-[0_0_10px_rgba(0,255,0,0.5)]' : 'grayscale contrast-125'"
                                    />
                                </g>
                            </svg>
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

                <!-- MIDDLE: Interactive Map (5 cols) -->
                <div class="lg:col-span-4 flex flex-col items-center">
                    <div class="text-center mb-6">
                        <div class="badge badge-accent badge-lg font-bold mb-2 shadow-lg shadow-accent/20">Interactive Selector</div>
                        <h2 class="text-2xl font-black italic uppercase">TARGET YOUR MUSCLES</h2>
                        <p class="text-sm opacity-60">Click on a muscle group below.</p>
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
                                    <div class="card-actions mt-2">
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
    </AppLayout>
</template>
