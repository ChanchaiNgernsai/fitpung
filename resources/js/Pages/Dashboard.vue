<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/language';

const { t } = useI18n();

defineProps({
    layouts: Array
});

const showSettingsModal = ref(false);
const showPreviewModal = ref(false);
const selectedLayout = ref(null);

const settingsForm = useForm({
    name: '',
    location: '',
    google_map_url: '',
    image: null,
    image_url: null,
    operating_hours: [],
    holidays: [],
    promotions: [],
    price_list: [],
    recommendations: [],
});

const imagePreview = ref(null);

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (readerEvent) => {
            const img = new Image();
            img.onload = () => {
                // Maximum dimensions
                const MAX_WIDTH = 1280;
                const MAX_HEIGHT = 720;
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }
                } else {
                    if (height > MAX_HEIGHT) {
                        width *= MAX_HEIGHT / height;
                        height = MAX_HEIGHT;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                // Export to blob with 0.7 quality
                canvas.toBlob((blob) => {
                    const compressedFile = new File([blob], file.name, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });
                    settingsForm.image = compressedFile;
                    imagePreview.value = canvas.toDataURL('image/jpeg', 0.7);
                }, 'image/jpeg', 0.7);
            };
            img.src = readerEvent.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const defaultHours = [
    { day: 'Sunday', status: 'Closed', start: '09:00', end: '18:00' },
    { day: 'Monday', status: 'Open', start: '09:00', end: '18:00' },
    { day: 'Tuesday', status: 'Open', start: '09:00', end: '18:00' },
    { day: 'Wednesday', status: 'Open', start: '09:00', end: '18:00' },
    { day: 'Thursday', status: 'Open', start: '09:00', end: '18:00' },
    { day: 'Friday', status: 'Open', start: '09:00', end: '18:00' },
    { day: 'Saturday', status: 'Closed', start: '09:00', end: '18:00' },
];

const openSettings = (layout) => {
    selectedLayout.value = layout;
    settingsForm.name = layout.name || '';
    settingsForm.location = layout.location || '';
    settingsForm.google_map_url = layout.google_map_url || '';
    settingsForm.operating_hours = layout.operating_hours || JSON.parse(JSON.stringify(defaultHours));
    settingsForm.holidays = layout.holidays || [];
    settingsForm.promotions = layout.promotions || [];
    settingsForm.price_list = layout.price_list || [];
    settingsForm.recommendations = (layout.recommendations || []).map(r => ({
        ...r,
        id: r.id || Date.now() + Math.random() // Ensure ID exists for stable keys
    }));
    settingsForm.image_url = formatImageUrl(layout.image_path);
    settingsForm.image = null;
    imagePreview.value = null;
    showSettingsModal.value = true;
};

const previewViewBox = ref({ x: 0, y: 0, w: 1000, h: 800 });
const isPanning = ref(false);
const lastMousePos = ref({ x: 0, y: 0 });

const openPreview = (layout) => {
    selectedLayout.value = layout;
    
    // 1. Get room points bounds
    const points = layout.room_config.points.split(' ').map(p => {
        const [x, y] = p.split(',').map(Number);
        return { x, y };
    });
    
    let minX = Math.min(...points.map(p => p.x));
    let maxX = Math.max(...points.map(p => p.x));
    let minY = Math.min(...points.map(p => p.y));
    let maxY = Math.max(...points.map(p => p.y));

    // 2. Expand bounds to include all equipment items
    if (layout.items && layout.items.length > 0) {
        layout.items.forEach(item => {
            const hw = item.width / 2;
            const hh = item.height / 2;
            minX = Math.min(minX, item.x - hw);
            maxX = Math.max(maxX, item.x + hw);
            minY = Math.min(minY, item.y - hh);
            maxY = Math.max(maxY, item.y + hh);
        });
    }

    // 3. Set initial viewbox with comfortable padding to see everything
    const padding = 100; 
    
    previewViewBox.value = {
        x: minX - padding,
        y: minY - padding,
        w: (maxX - minX) + (padding * 2),
        h: (maxY - minY) + (padding * 2)
    };
    
    showPreviewModal.value = true;
};

const handlePreviewWheel = (event) => {
    event.preventDefault();
    const zoomIntensity = 0.1;
    const svg = event.currentTarget;
    const pt = svg.createSVGPoint();
    pt.x = event.clientX;
    pt.y = event.clientY;
    const ctm = svg.getScreenCTM();
    if (!ctm) return;
    const svgP = pt.matrixTransform(ctm.inverse());

    const direction = event.deltaY > 0 ? 1 : -1;
    const newW = previewViewBox.value.w * (1 + direction * zoomIntensity);
    const newH = previewViewBox.value.h * (1 + direction * zoomIntensity);

    if (newW < 100 || newW > 10000) return;

    const mouseRelX = (svgP.x - previewViewBox.value.x) / previewViewBox.value.w;
    const mouseRelY = (svgP.y - previewViewBox.value.y) / previewViewBox.value.h;

    previewViewBox.value.x = svgP.x - (mouseRelX * newW);
    previewViewBox.value.y = svgP.y - (mouseRelY * newH);
    previewViewBox.value.w = newW;
    previewViewBox.value.h = newH;
};

const handlePreviewMouseDown = (event) => {
    if (event.button !== 0) return; // Only left click
    isPanning.value = true;
    lastMousePos.value = { x: event.clientX, y: event.clientY };
};

const handlePreviewMouseMove = (event) => {
    if (!isPanning.value) return;
    
    const dx = event.clientX - lastMousePos.value.x;
    const dy = event.clientY - lastMousePos.value.y;
    
    const svg = document.getElementById('preview-canvas');
    if (!svg) return;
    
    const scaleX = previewViewBox.value.w / svg.clientWidth;
    const scaleY = previewViewBox.value.h / svg.clientHeight;

    previewViewBox.value.x -= dx * scaleX;
    previewViewBox.value.y -= dy * scaleY;

    lastMousePos.value = { x: event.clientX, y: event.clientY };
};

const handlePreviewMouseUp = () => {
    isPanning.value = false;
};

const addHoliday = () => {
    settingsForm.holidays.push({ name: '', date: '', recurring: true });
};

const removeHoliday = (index) => {
    settingsForm.holidays.splice(index, 1);
};

const addPromotion = () => {
    settingsForm.promotions.push({ title: '', description: '', end_date: '' });
};

const removePromotion = (index) => {
    settingsForm.promotions.splice(index, 1);
};

const addPrice = () => {
    settingsForm.price_list.push({ label: '', amount: '', period: 'Month' });
};

const removePrice = (index) => {
    settingsForm.price_list.splice(index, 1);
};

const addRecommendation = () => {
    settingsForm.recommendations.push({ 
        id: Date.now() + Math.random(),
        title: '', 
        subtitle: '', 
        duration: 45, 
        calories: 320, 
        badge: 'Starter',
        exercises: [] 
    });
};

const removeRecommendation = (index) => {
    settingsForm.recommendations.splice(index, 1);
};

const toggleExerciseInRecommendation = (recIndex, machineName) => {
    const exercises = settingsForm.recommendations[recIndex].exercises;
    const existingIndex = exercises.findIndex(e => (typeof e === 'string' ? e : e.name) === machineName);
    
    if (existingIndex === -1) {
        // Add new exercise as an object with default values
        exercises.push({
            name: machineName,
            weight: '10',
            sets: '3',
            reps: '12'
        });
    } else {
        // Remove exercise
        exercises.splice(existingIndex, 1);
    }
};

const getExerciseName = (exercise) => {
    return typeof exercise === 'string' ? exercise : exercise.name;
};

const isExerciseSelected = (rec, name) => {
    return rec.exercises.some(e => getExerciseName(e) === name);
};

const saveSettings = () => {
    // Use the dedicated POST route for updates to handle multipart/form-data correctly
    settingsForm.post(route('gym-builder.post_update', selectedLayout.value.id), {
        forceFormData: true,
        onSuccess: () => {
            showSettingsModal.value = false;
        }
    });
};

const deleteLayout = (id) => {
    if (confirm("Are you sure you want to delete this design?")) {
        router.delete(route('gym-builder.destroy', id));
    }
};

const getViewBox = (pointsStr) => {
    if (!pointsStr) return '0 0 1000 800';
    
    const points = pointsStr.split(' ').map(p => {
        const [x, y] = p.split(',').map(Number);
        return { x, y };
    });
    
    const minX = Math.min(...points.map(p => p.x));
    const maxX = Math.max(...points.map(p => p.x));
    const minY = Math.min(...points.map(p => p.y));
    const maxY = Math.max(...points.map(p => p.y));
    
    const padding = 50; // Minimal padding to fill the card
    const w = maxX - minX + (padding * 2);
    const h = maxY - minY + (padding * 2);
    const x = minX - padding;
    const y = minY - padding;
    
    return `${x} ${y} ${w} ${h}`;
};

const imageLoadError = ref({});

const handleImageError = (id) => {
    imageLoadError.value[id] = true;
};

const getUniqueMachines = (items) => {
    if (!items) return [];
    const unique = [];
    const seen = new Set();
    for (const item of items) {
        if (!seen.has(item.name)) {
            unique.push(item);
            seen.add(item.name);
        }
    }
    return unique;
};

const formatImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
                <template #header>
            <div class="relative flex items-center justify-center py-2 h-10 w-full px-4">
                <!-- Title Centered -->
                <h2 class="text-3xl font-bold leading-tight text-base-content absolute left-1/2 -translate-x-1/2">
                    {{ t('dashboard.title') }}
                </h2>
                <!-- Button Right Aligned -->
                <Link :href="route('gym-builder.create')" class="btn btn-primary btn-sm ml-auto absolute right-0">
                    + {{ t('dashboard.create_new') }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <div v-if="layouts.length === 0" class="text-center py-20 opacity-50">
                    <p class="text-2xl font-bold mb-4">No Designs Yet</p>
                    <Link :href="route('gym-builder.create')" class="btn btn-outline">Start Building</Link>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="layout in layouts" 
                        :key="layout.id" 
                        class="group bg-base-100 rounded-[2.5rem] overflow-hidden border border-base-content/5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_25px_60px_rgba(0,0,0,0.12)] transition-all duration-500 hover:-translate-y-2 flex flex-col"
                    >
                        <!-- Card Preview Section -->
                        <div class="h-60 bg-neutral relative overflow-hidden flex items-center justify-center">
                            <template v-if="layout.image_path && !imageLoadError[layout.id]">
                                <img 
                                    :src="formatImageUrl(layout.image_path)" 
                                    @error="handleImageError(layout.id)"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" 
                                />
                            </template>
                            <div v-else class="w-full h-full bg-gradient-to-br from-[#1a2233] to-[#0f172a] flex items-center justify-center p-6">
                                <svg 
                                    :viewBox="getViewBox(layout.room_config.points)" 
                                    class="w-full h-full opacity-30 group-hover:opacity-50 transition-all duration-700 group-hover:scale-105"
                                    preserveAspectRatio="xMidYMid meet"
                                >
                                    <polygon 
                                        :points="layout.room_config.points" 
                                        fill="none" 
                                        stroke="rgba(255,255,255,0.2)" 
                                        stroke-width="2" 
                                    />
                                    <g v-for="item in layout.items" :key="item.id">
                                        <circle :cx="item.x" :cy="item.y" r="5" fill="rgba(255,255,255,0.4)" stroke="white" stroke-width="1" />
                                    </g>
                                </svg>
                            </div>
                            
                            <!-- Premium Hover State Overlay -->
                            <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                <div class="px-6 py-3 bg-white/20 backdrop-blur-xl rounded-2xl border border-white/20 shadow-xl">
                                    <span class="text-white text-[10px] font-black tracking-[3px] uppercase italic">Open Blueprint</span>
                                </div>
                            </div>

                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                        </div>

                        <!-- Card Info Section -->
                        <div class="p-8 pt-6 flex flex-col flex-1 items-center bg-gradient-to-b from-transparent to-base-200/30">
                            <h2 class="text-2xl font-black italic uppercase text-base-content tracking-tighter mb-1">{{ layout.name }}</h2>
                            <p class="text-xs uppercase font-bold opacity-40 mb-8">Ref: {{ new Date(layout.updated_at).toLocaleDateString() }}</p>
                            
                            <!-- Action Buttons Row -->
                            <div class="flex items-center justify-center gap-4 w-full border-t border-base-content/5 pt-8">
                                <button @click="openPreview(layout)" class="btn btn-circle btn-ghost btn-sm bg-base-200/50 hover:bg-primary hover:text-white border-none transition-all duration-300" title="Quick Preview">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </button>
                                
                                <button @click="openSettings(layout)" class="btn btn-circle btn-ghost btn-sm bg-base-200/50 hover:bg-accent hover:text-white border-none transition-all duration-300" title="General Settings">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </button>
                                
                                <Link :href="route('gym-builder.edit', layout.id)" class="btn btn-primary btn-md px-10 rounded-2xl font-black italic uppercase shadow-xl shadow-primary/30 hover:shadow-primary/50 hover:scale-105 active:scale-95 transition-all duration-300">
                                    {{ t('dashboard.edit_gym') }}
                                </Link>
                                
                                <button @click="deleteLayout(layout.id)" class="btn btn-circle btn-ghost btn-sm bg-error/5 text-error hover:bg-red-500 hover:text-white border-none transition-all duration-300" title="Destroy Design">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Gym Settings Modal -->
        <div v-if="showSettingsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-2 md:p-4 overflow-y-auto">
            <div class="bg-base-100 w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden border border-base-content/10 my-auto">
                <div class="flex justify-between items-center p-4 md:p-6 border-b border-base-content/10 bg-base-200/50">
                    <h2 class="text-xl md:text-2xl font-black text-primary uppercase tracking-tight">Gym Settings</h2>
                    <button class="btn btn-ghost btn-circle btn-sm" @click="showSettingsModal = false">✕</button>
                </div>
                
                <div class="flex flex-col lg:flex-row max-h-[85vh] overflow-y-auto">
                    <!-- Left Sidebar (Mobile: Top): Image Upload -->
                    <div class="w-full lg:w-80 bg-base-200/50 p-6 flex flex-col items-center border-b lg:border-b-0 lg:border-r border-base-content/5">
                        <h3 class="font-bold text-sm uppercase opacity-60 mb-4 self-start">Gym Photo</h3>
                        
                        <div 
                            class="relative w-full aspect-video lg:aspect-square rounded-2xl border-2 border-dashed border-primary/30 flex flex-col items-center justify-center overflow-hidden bg-base-100 group cursor-pointer hover:border-primary transition-colors mb-4"
                            @click="$refs.settingsFileInput.click()"
                        >
                            <img v-if="imagePreview || settingsForm.image_url" :src="imagePreview || settingsForm.image_url" class="w-full h-full object-cover" />
                            <div v-else class="flex flex-col items-center text-primary/40 group-hover:text-primary transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-[10px] font-bold uppercase tracking-wider">Click to Upload</span>
                            </div>
                            <input type="file" ref="settingsFileInput" class="hidden" accept="image/*" @change="handleImageChange" />
                        </div>
                        <p class="text-[10px] opacity-50 text-center px-4 italic">Recommend square or landscape image for the best view on dashboard.</p>
                    </div>

                    <!-- Right Side: Content Sections -->
                    <div class="flex-1 p-4 md:p-8 space-y-10">
                        <!-- Section: General Info -->
                        <section>
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-2 bg-primary/10 rounded-lg text-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                </div>
                                <h3 class="text-lg md:text-xl font-bold">General Information</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div class="form-control">
                                    <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">Gym Name</span></label>
                                    <input v-model="settingsForm.name" type="text" class="input input-bordered bg-base-200 border-none shadow-inner h-10 text-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">Google Map URL</span></label>
                                    <input v-model="settingsForm.google_map_url" type="text" class="input input-bordered bg-base-200 border-none shadow-inner h-10 text-sm" />
                                </div>
                                <div class="form-control md:col-span-2">
                                    <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">Location / Address</span></label>
                                    <textarea v-model="settingsForm.location" rows="2" class="textarea textarea-bordered bg-base-200 border-none shadow-inner leading-tight text-sm"></textarea>
                                </div>
                            </div>
                        </section>

                        <!-- Section: Operating Hours -->
                        <section>
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-2 bg-accent/10 rounded-lg text-accent">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <h3 class="text-lg md:text-xl font-bold">Operating Hours</h3>
                            </div>
                            
                            <div class="bg-base-200/30 rounded-2xl md:p-6 border border-base-content/5 overflow-x-auto">
                                <table class="table table-xs md:table-sm w-full">
                                    <thead>
                                        <tr class="text-[10px] uppercase opacity-40 border-none">
                                            <th class="bg-transparent pl-4">Day</th>
                                            <th class="bg-transparent">Status</th>
                                            <th class="bg-transparent pr-4">Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-base-content/5">
                                        <tr v-for="h in settingsForm.operating_hours" :key="h.day" class="border-none">
                                            <td class="font-bold pl-4 py-3">{{ h.day }}</td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <input type="checkbox" class="toggle toggle-primary toggle-xs" v-model="h.status" true-value="Open" false-value="Closed" />
                                                    <span :class="h.status === 'Open' ? 'text-primary font-bold' : 'opacity-40'" class="text-[10px]">{{ h.status }}</span>
                                                </div>
                                            </td>
                                            <td class="pr-4">
                                                <div class="flex items-center gap-1 md:gap-2 flex-wrap" v-if="h.status === 'Open'">
                                                    <input v-model="h.start" type="time" class="input input-xs input-bordered bg-base-100 p-1 w-20" />
                                                    <span class="opacity-40 text-[10px]">to</span>
                                                    <input v-model="h.end" type="time" class="input input-xs input-bordered bg-base-100 p-1 w-20" />
                                                </div>
                                                <div class="text-error italic text-[10px]" v-else>
                                                    Gym Closed
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- Section: Holidays -->
                        <section>
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-secondary/10 rounded-lg text-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <h3 class="text-lg md:text-xl font-bold">Gym Holidays</h3>
                                </div>
                                <button class="btn btn-secondary btn-sm" @click="addHoliday">+ Add Holiday</button>
                            </div>

                            <div class="bg-base-200/30 rounded-2xl p-4 md:p-6 border border-base-content/5">
                                <p class="text-[10px] md:text-sm opacity-50 mb-6 font-medium">Clients won't be able to book or visit on these dates.</p>
                                
                                <div class="space-y-3">
                                    <div v-for="(holiday, index) in settingsForm.holidays" :key="index" class="flex flex-col md:flex-row items-stretch md:items-center gap-3 bg-base-100 p-3 rounded-xl shadow-sm border border-base-content/5">
                                        <input v-model="holiday.name" type="text" placeholder="Holiday Name" class="input input-sm input-bordered flex-1" />
                                        <input v-model="holiday.date" type="date" class="input input-sm input-bordered" />
                                        <div class="flex items-center justify-between gap-4">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" v-model="holiday.recurring" class="checkbox checkbox-primary checkbox-xs" />
                                                <span class="text-[10px]">Recurring</span>
                                            </label>
                                            <button class="btn btn-ghost btn-xs text-error" @click="removeHoliday(index)">Remove</button>
                                        </div>
                                    </div>
                                    <div v-if="settingsForm.holidays.length === 0" class="text-center py-8 opacity-30 italic text-sm">
                                        No holidays added yet.
                                    </div>
                                </div>
                            </div>
                        </section>



                        <!-- Section: Membership Prices -->
                        <section>
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-success/10 rounded-lg text-success">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <h3 class="text-lg md:text-xl font-bold">Price List</h3>
                                </div>
                                <button class="btn btn-success btn-sm" @click="addPrice">+ Add Price</button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="(p, index) in settingsForm.price_list" :key="index" class="bg-base-200/50 p-4 rounded-2xl border border-base-content/5 relative group">
                                    <button @click="removePrice(index)" class="btn btn-circle btn-xs btn-error absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">✕</button>
                                    <div class="space-y-3">
                                        <input v-model="p.label" type="text" placeholder="Entry Type (e.g. 1 Month)" class="input input-sm input-bordered w-full" />
                                        <div class="flex items-center gap-2">
                                            <input v-model="p.amount" type="number" placeholder="Price" class="input input-sm input-bordered flex-1" />
                                            <select v-model="p.period" class="select select-sm select-bordered">
                                                <option>Day</option>
                                                <option>Week</option>
                                                <option>Month</option>
                                                <option>Year</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="settingsForm.price_list.length === 0" class="text-center py-8 opacity-30 italic text-sm border-2 border-dashed border-base-content/10 rounded-2xl sm:col-span-2 lg:col-span-3">
                                    No prices listed.
                                </div>
                            </div>
                        </section>

                        <!-- Section: Workout Recommendations -->
                        <section>
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary/10 rounded-lg text-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    </div>
                                    <h3 class="text-lg md:text-xl font-bold">Workout Recommendations</h3>
                                </div>
                                <button class="btn btn-primary btn-sm" @click="addRecommendation">+ Add Recommendation</button>
                            </div>

                            <div class="space-y-6">
                                <div v-for="(rec, recIdx) in settingsForm.recommendations" :key="rec.id" class="bg-base-200/50 p-6 rounded-3xl border border-base-content/5 relative group">
                                    <button @click="removeRecommendation(recIdx)" class="btn btn-circle btn-xs btn-error absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                        <div class="form-control">
                                            <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">Title</span></label>
                                            <input v-model="rec.title" type="text" placeholder="e.g. UPPER BODY FOCUS" class="input input-sm input-bordered" />
                                        </div>
                                        <div class="form-control">
                                            <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">Badge (Category)</span></label>
                                            <select v-model="rec.badge" class="select select-sm select-bordered font-bold text-xs">
                                                <option value="Starter">Starter</option>
                                                <option value="Intermediate">Intermediate (กลาง)</option>
                                                <option value="Elite">Elite</option>
                                            </select>
                                        </div>
                                        <div class="form-control md:col-span-2">
                                            <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">Subtitle / Target Description</span></label>
                                            <input v-model="rec.subtitle" type="text" placeholder="Targets Chest, Shoulders, and Triceps..." class="input input-sm input-bordered" />
                                        </div>
                                        <div class="form-control">
                                            <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">Duration (Min)</span></label>
                                            <input v-model="rec.duration" type="number" class="input input-sm input-bordered" />
                                        </div>
                                        <div class="form-control">
                                            <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">Est. Calories</span></label>
                                            <input v-model="rec.calories" type="number" class="input input-sm input-bordered" />
                                        </div>
                                    </div>

                                    <!-- Exercise Selection Grid -->
                                    <div class="form-control">
                                        <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">1. Select Included Machines</span></label>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-4 bg-base-100 rounded-3xl border border-base-content/5">
                                            <div 
                                                v-for="machine in getUniqueMachines(selectedLayout.items)" 
                                                :key="machine.name"
                                                @click="toggleExerciseInRecommendation(recIdx, machine.name)"
                                                class="group relative aspect-square rounded-2xl border-2 transition-all cursor-pointer overflow-hidden flex flex-col items-center justify-center p-2 text-center"
                                                :class="isExerciseSelected(rec, machine.name) ? 'border-primary bg-primary/5' : 'border-transparent bg-base-200/50 hover:bg-base-200'"
                                            >
                                                <img :src="machine.src" class="w-12 h-12 object-contain mb-2 transition-all" :class="isExerciseSelected(rec, machine.name) ? 'brightness-110 drop-shadow-[0_0_8px_rgba(var(--p),0.4)]' : 'grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100'" />
                                                <span class="text-[8px] font-black uppercase text-center leading-tight transition-colors" :class="isExerciseSelected(rec, machine.name) ? 'text-primary' : 'text-base-content/40 group-hover:text-base-content/80'">{{ machine.name }}</span>
                                                
                                                <!-- Selection Marker -->
                                                <div v-if="isExerciseSelected(rec, machine.name)" class="absolute top-2 right-2 size-4 bg-primary text-white rounded-full flex items-center justify-center shadow-lg animate-in zoom-in duration-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>

                                            <div v-if="!selectedLayout.items || selectedLayout.items.length === 0" class="col-span-full text-center py-6 opacity-30 italic text-xs">
                                                No machines found in this layout. Please add machines in the Editor first.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected Machine Details -->
                                    <div v-if="rec.exercises.length > 0" class="form-control mt-4">
                                        <label class="label pt-0"><span class="label-text font-bold text-[10px] uppercase opacity-60">2. Set Target for Each Machine</span></label>
                                        <div class="space-y-3">
                                            <div v-for="(exe, exeIdx) in rec.exercises" :key="getExerciseName(exe)" 
                                                class="flex items-center gap-4 p-4 bg-base-100 rounded-2xl border border-base-content/5 animate-in slide-in-from-left duration-300"
                                                :style="{ animationDelay: `${exeIdx * 50}ms` }"
                                            >
                                                <!-- Mini Preview -->
                                                <div class="size-10 rounded-xl bg-base-200 flex items-center justify-center flex-shrink-0">
                                                    <img :src="selectedLayout.items.find(i => i.name === getExerciseName(exe))?.src" class="size-6 object-contain" />
                                                </div>
                                                
                                                <!-- Name -->
                                                <div class="flex-1">
                                                    <p class="text-[10px] font-black uppercase tracking-tighter">{{ getExerciseName(exe) }}</p>
                                                </div>

                                                <!-- Detail Inputs -->
                                                <div class="flex items-center gap-2">
                                                    <div class="flex flex-col">
                                                        <label class="text-[8px] font-bold opacity-30 uppercase ml-1">Weight (kg)</label>
                                                        <input v-model="rec.exercises[exeIdx].weight" type="text" placeholder="10" class="input input-xs input-bordered w-16 font-bold" />
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <label class="text-[8px] font-bold opacity-30 uppercase ml-1">Sets</label>
                                                        <input v-model="rec.exercises[exeIdx].sets" type="text" placeholder="3" class="input input-xs input-bordered w-12 font-bold" />
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <label class="text-[8px] font-bold opacity-30 uppercase ml-1">Reps</label>
                                                        <input v-model="rec.exercises[exeIdx].reps" type="text" placeholder="12" class="input input-xs input-bordered w-12 font-bold" />
                                                    </div>
                                                </div>

                                                <!-- Remove Button -->
                                                <button @click="toggleExerciseInRecommendation(recIdx, getExerciseName(exe))" class="btn btn-ghost btn-xs btn-circle text-error">
                                                    <span class="material-symbols-outlined text-sm">close</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="settingsForm.recommendations.length === 0" class="text-center py-12 opacity-30 italic text-sm border-2 border-dashed border-base-content/10 rounded-3xl">
                                    No workout recommendations created yet.
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="p-4 md:p-6 bg-base-200 border-t border-base-content/10 flex flex-col md:flex-row gap-3">
                    <button class="btn btn-ghost flex-1 order-2 md:order-1" @click="showSettingsModal = false">Cancel</button>
                    <button class="btn btn-primary flex-[2] shadow-lg shadow-primary/30 order-1 md:order-2" @click="saveSettings" :disabled="settingsForm.processing">
                        <span v-if="settingsForm.processing" class="loading loading-spinner loading-xs"></span>
                        <span v-else>Save Changes</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Preview Modal (Image 1 Style) -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-fade-in" @click.self="showPreviewModal = false">
            <div class="relative w-full max-w-5xl h-[80vh] bg-[#0f172a] rounded-2xl shadow-2xl overflow-hidden border border-white/5 flex flex-col">
                <!-- Close Button Overlay -->
                <button 
                    @click="showPreviewModal = false" 
                    class="absolute top-4 right-4 z-[60] btn btn-circle btn-sm bg-black/50 border-none text-white hover:bg-black"
                >
                    ✕
                </button>

                <div class="flex-1 w-full relative cursor-move" 
                     @mousemove="handlePreviewMouseMove" 
                     @mouseup="handlePreviewMouseUp" 
                     @mouseleave="handlePreviewMouseUp">
                    <svg 
                        id="preview-canvas"
                        :viewBox="`${previewViewBox.x} ${previewViewBox.y} ${previewViewBox.w} ${previewViewBox.h}`"
                        class="w-full h-full select-none"
                        preserveAspectRatio="xMidYMid meet"
                        @wheel="handlePreviewWheel"
                        @mousedown="handlePreviewMouseDown"
                    >
                        <!-- Space-themed deep background is already the container's bg -->
                        
                        <!-- Room Floor -->
                        <polygon 
                            :points="selectedLayout.room_config.points" 
                            fill="#1a2233" 
                            stroke="rgba(255,255,255,0.2)" 
                            stroke-width="2" 
                        />

                        <!-- Equipment Items -->
                        <g 
                            v-for="item in selectedLayout.items" 
                            :key="item.id"
                            :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                        >
                            <image 
                                :href="item.src" 
                                :x="-item.width/2" 
                                :y="-item.height/2" 
                                :width="item.width" 
                                :height="item.height"
                                class="filter brightness-125 drop-shadow-[0_0_20px_rgba(255,255,255,0.4)] contrast-125"
                            />
                        </g>
                    </svg>

                    <!-- Zoom Helper Hint -->
                    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 px-4 py-2 bg-black/40 backdrop-blur-md rounded-full text-[10px] font-bold text-white/50 uppercase tracking-widest pointer-events-none border border-white/5">
                        Scroll to Zoom • Drag to Pan
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>


