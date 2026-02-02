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

// Interactive Helpers
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
    <Head :title="gym.name + ' - FitPung Member View'" />

    <AppLayout>
        <!-- Gym Hero Section (Clean Design) -->
        <div class="relative bg-white pt-32 pb-20 overflow-hidden border-b border-gray-100">
             <!-- Background Subtle Elements -->
             <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-indigo-50 to-transparent"></div>
             <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-50 rounded-full blur-3xl opacity-30"></div>

             <div class="max-w-7xl mx-auto px-6 relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-center gap-12">
                    <div class="space-y-8 max-w-3xl text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-3">
                            <span class="badge bg-indigo-50 border-none text-[#4338ca] font-black italic uppercase tracking-widest px-6 py-4 shadow-sm">Member Hub</span>
                            <span class="text-[10px] font-black uppercase tracking-[0.4em] text-gray-300">Official Facility View</span>
                        </div>
                        
                        <div class="space-y-4">
                            <h1 class="text-7xl md:text-9xl font-black italic uppercase text-gray-900 tracking-tighter leading-none">{{ gym.name }}</h1>
                            <div class="flex items-center justify-center md:justify-start gap-4">
                                <div class="h-px w-20 bg-[#4338ca]"></div>
                                <span class="text-sm font-bold italic uppercase tracking-widest text-[#4338ca]">{{ gym.location || 'Professional Workout Space' }}</span>
                            </div>
                        </div>

                        <p class="text-xl text-gray-500 font-medium leading-relaxed max-w-2xl mx-auto md:mx-0">
                            Unlock your physical potential with our state-of-the-art facility. Experience precision engineering in every piece of equipment and every square foot of our space.
                        </p>

                        <div v-if="gym.google_map_url" class="flex justify-center md:justify-start gap-4">
                            <a :href="gym.google_map_url" target="_blank" class="btn btn-lg bg-gray-900 hover:bg-black text-white rounded-2xl font-black italic uppercase tracking-widest px-10 border-none shadow-xl">
                                Open Route
                            </a>
                        </div>
                    </div>

                    <!-- Right Side: Actual Gym Photo in Stylized Frame -->
                    <div class="hidden lg:block relative p-12">
                         <div class="w-96 h-96 bg-gray-100 rounded-[4rem] rotate-3 relative overflow-hidden shadow-2xl border-8 border-white">
                             <template v-if="gym.image_path">
                                 <img :src="`/storage/${gym.image_path}`" class="w-full h-full object-cover" />
                             </template>
                             <div v-else class="flex items-center justify-center h-full bg-indigo-50/50">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32 text-[#4338ca] opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                             </div>
                             <div class="absolute inset-0 bg-gradient-to-br from-[#4338ca] to-transparent opacity-10"></div>
                         </div>
                         <!-- Floating badges -->
                         <div class="absolute top-0 right-0 bg-white p-6 rounded-3xl shadow-xl -rotate-6 border border-gray-50 z-20">
                             <div class="text-[10px] font-black uppercase text-[#4338ca]">Status</div>
                             <div class="text-lg font-black italic uppercase">Verified</div>
                         </div>
                         <div class="absolute bottom-10 -left-10 bg-white p-6 rounded-3xl shadow-xl rotate-12 border border-gray-50 z-20">
                             <div class="text-[10px] font-black uppercase text-gray-400">Layout</div>
                             <div class="text-lg font-black italic uppercase">Certified</div>
                         </div>
                    </div>
                </div>
             </div>
        </div>


                    <div class="max-w-7xl mx-auto px-4 py-20 space-y-32">

            <div class="grid lg:grid-cols-2 gap-24 items-start pb-20 border-b border-gray-100">
                <!-- Left: Story & Hours -->
                <section class="space-y-12">
                    <div class="space-y-6">
                        <div class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.4em] text-[#4338ca]">
                            <div class="w-8 h-[2px] bg-[#4338ca]"></div>
                            The Experience
                        </div>
                        <h2 class="text-6xl font-black italic uppercase tracking-tighter leading-[0.9] text-gray-900">
                            Beyond a Gym, <br/> It's a Lifestyle
                        </h2>
                        <p class="text-xl text-gray-500 font-medium leading-relaxed max-w-xl">
                            {{ gym.description || 'Verified fitness facility equipped with professional standard equipment and personalized workout environments.' }}
                        </p>
                    </div>
                    
                    <div class="grid sm:grid-cols-2 gap-12">
                        <div v-if="gym.operating_hours" class="space-y-6">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400">Opening Schedule</h4>
                            <div class="space-y-3">
                                <div v-for="h in gym.operating_hours.slice(0, 7)" :key="h.day" class="flex justify-between items-center text-sm">
                                    <span class="font-bold text-gray-400 w-12">{{ h.day.substring(0, 3).toUpperCase() }}</span>
                                    <span :class="h.status === 'Open' ? 'text-gray-900' : 'text-red-400'" class="font-black italic">
                                        {{ h.status === 'Open' ? `${h.start} - ${h.end}` : 'OFFLINE' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="gym.holidays && gym.holidays.length > 0" class="space-y-6">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400">Public Holidays</h4>
                            <div class="space-y-3">
                                <div v-for="(holiday, idx) in gym.holidays.slice(0, 3)" :key="idx" class="flex justify-between items-center text-sm">
                                    <span class="font-bold text-gray-900">{{ holiday.name }}</span>
                                    <span class="font-bold text-gray-400 italic text-[11px]">{{ holiday.date }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Right Side: Plan Your Session Call to Action -->
                <section class="relative">
                    <div class="absolute -inset-10 bg-indigo-50/50 rounded-[4rem] blur-3xl opacity-50"></div>
                    <div class="relative bg-white rounded-[3.5rem] p-12 md:p-16 border border-gray-100 shadow-[0_30px_100px_rgba(0,0,0,0.08)] text-center space-y-10 group overflow-hidden">
                        <!-- Subtle Background Pattern -->
                        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-50 rounded-full translate-x-32 -translate-y-32"></div>

                        <div class="space-y-4 relative z-10">
                            <h3 class="text-xs font-black uppercase tracking-[0.4em] text-[#4338ca] italic">Interactive Hub</h3>
                            <h2 class="text-5xl font-black italic uppercase tracking-tighter leading-tight text-gray-900">
                                Ready to Plan <br/> Your Session?
                            </h2>
                            <p class="text-lg text-gray-500 font-medium leading-relaxed max-w-xs mx-auto">
                                Build your personalized workout plan with our interactive 3D facility map.
                            </p>
                        </div>

                        <div class="relative z-10">
                            <Link 
                                :href="route('gyms.map', gym.id)" 
                                class="btn bg-[#4338ca] hover:bg-[#3730a3] text-white btn-block h-20 rounded-[1.5rem] font-black italic uppercase tracking-widest shadow-2xl transition-all hover:scale-[1.02]"
                            >
                                Open Session Planner
                            </Link>
                        </div>

                        <!-- Mini Icons -->
                        <div class="grid grid-cols-3 gap-6 pt-6 relative z-10 opacity-30 group-hover:opacity-60 transition-opacity">
                            <div class="flex flex-col items-center gap-2">
                                 <div class="p-3 bg-gray-50 rounded-2xl"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></div>
                                 <span class="text-[8px] font-black uppercase tracking-widest">3D Map</span>
                            </div>
                            <div class="flex flex-col items-center gap-2">
                                 <div class="p-3 bg-gray-50 rounded-2xl"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg></div>
                                 <span class="text-[8px] font-black uppercase tracking-widest">Plan Builder</span>
                            </div>
                            <div class="flex flex-col items-center gap-2">
                                 <div class="p-3 bg-gray-50 rounded-2xl"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg></div>
                                 <span class="text-[8px] font-black uppercase tracking-widest">Technique</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- SECTION: Membership Plans (Gemini Style) -->
            <section v-if="gym.price_list && gym.price_list.length > 0" class="space-y-16">
                <div class="text-center space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-[0.4em] text-[#4338ca] italic">Membership</h3>
                    <h2 class="text-5xl font-black italic uppercase tracking-tighter">Choose Your Strength</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="p in gym.price_list" :key="p.label" class="bg-white p-12 rounded-[2.5rem] border border-gray-100 flex flex-col items-center text-center shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_20px_60px_rgba(0,0,0,0.08)] transition-all duration-500 group">
                        <span class="text-2xl font-black text-gray-900 mb-8">{{ p.label }}</span>
                        
                        <div class="flex items-baseline gap-2 mb-10">
                            <span class="text-2xl font-bold opacity-30 italic">฿</span>
                            <span class="text-6xl font-black italic tracking-tighter">{{ p.amount }}</span>
                            <span class="text-sm font-bold opacity-30 uppercase italic tracking-widest">/ {{ p.period }}</span>
                        </div>

                        <ul class="space-y-4 mb-12 text-sm font-medium text-gray-600 list-none p-0 text-left w-full">
                            <li class="flex items-center gap-3">
                                <div class="bg-indigo-50 p-1 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#4338ca]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></div>
                                Full Equipment Access
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="bg-indigo-50 p-1 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#4338ca]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></div>
                                Personal Map Access
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="bg-indigo-50 p-1 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#4338ca]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></div>
                                Verified Plan Builder
                            </li>
                        </ul>

                        <button class="mt-auto btn bg-[#4338ca] hover:bg-[#3730a3] text-white btn-block h-16 rounded-2xl font-bold uppercase tracking-widest border-none shadow-lg shadow-indigo-500/20 transition-all text-sm">
                            Get Started
                        </button>
                    </div>
                </div>
            </section>


        </div>
    </AppLayout>
</template>

<style scoped>
</style>
