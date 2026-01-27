<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';

// --- Assets ---
const equipmentTypes = [
    { id: 'treadmill', name: 'Treadmill', src: '/images/equipment/Treadmill.svg', width: 60, height: 120 },
    { id: 'elliptical', name: 'Elliptical', src: '/images/equipment/Elliptical.svg', width: 50, height: 110 },
    { id: 'bench', name: 'Bench Press', src: '/images/equipment/BenchPress.svg', width: 80, height: 100 },
    { id: 'smith', name: 'Smith Machine', src: '/images/equipment/SmithMachine.svg', width: 100, height: 80 },
    { id: 'cycle', name: 'Bike', src: '/images/equipment/RecumbentCycle.svg', width: 50, height: 90 },
    { id: 'lat', name: 'Lat Pulldown', src: '/images/equipment/LatPulldown.svg', width: 60, height: 90 },
    { id: 'leg', name: 'Leg Press', src: '/images/equipment/LegPress.svg', width: 80, height: 110 },
];

// --- Room Shapes ---
// Points are roughly in a 1000x1000 coordinate space
const roomShapes = [
    { id: 'rect', name: 'Rectangle', points: '100,100 900,100 900,700 100,700' },
    { id: 'l-shape', name: 'L-Shape', points: '100,100 900,100 900,400 500,400 500,700 100,700' },
    { id: 'trapezoid', name: 'Trapezoid', points: '300,100 900,100 900,700 100,700' },
    { id: 'triangle', name: 'Triangle', points: '500,100 900,700 100,700' },
];

// --- State ---
const step = ref(1); // 1: Shape Select, 2: Builder
const selectedRoom = ref(null);
const placedItems = ref([]);
const selectedItemId = ref(null);
const draggingNewItem = ref(null);

// --- Camera / ViewBox State ---
const svgViewBox = ref({ x: 0, y: 0, w: 1000, h: 800 });
const isPanning = ref(false);
const lastMousePos = ref({ x: 0, y: 0 });

// --- Interaction State ---
const isDraggingItem = ref(false);
const isRotatingItem = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const initialRotation = ref(0);
const initialMouseAngle = ref(0);

// --- Actions ---

const getBoundingBox = (pointsStr) => {
    const points = pointsStr.split(' ').map(p => {
        const [x, y] = p.split(',').map(Number);
        return { x, y };
    });
    
    const minX = Math.min(...points.map(p => p.x));
    const maxX = Math.max(...points.map(p => p.x));
    const minY = Math.min(...points.map(p => p.y));
    const maxY = Math.max(...points.map(p => p.y));
    
    return { x: minX, y: minY, w: maxX - minX, h: maxY - minY };
};

const selectRoom = (room) => {
    selectedRoom.value = room;
    step.value = 2;
    
    // Fit to screen logic
    // We calculate the bbox of the room + some padding
    const bbox = getBoundingBox(room.points);
    const padding = 100;
    
    svgViewBox.value = {
        x: bbox.x - padding,
        y: bbox.y - padding,
        w: bbox.w + (padding * 2),
        h: bbox.h + (padding * 2)
    };
};

const handleDragStartFromSidebar = (item, event) => {
    draggingNewItem.value = item;
    event.dataTransfer.effectAllowed = 'copy';
};

const getSvgPoint = (clientX, clientY) => {
    const svg = document.getElementById('gym-canvas');
    if (!svg) return { x: 0, y: 0 };
    const pt = svg.createSVGPoint();
    pt.x = clientX;
    pt.y = clientY;
    return pt.matrixTransform(svg.getScreenCTM().inverse());
};

const handleDropOnCanvas = (event) => {
    event.preventDefault();
    if (draggingNewItem.value) {
        const svgP = getSvgPoint(event.clientX, event.clientY);

        const newItem = {
            id: Date.now(),
            type: draggingNewItem.value.id,
            name: draggingNewItem.value.name,
            src: draggingNewItem.value.src,
            x: svgP.x,
            y: svgP.y,
            width: draggingNewItem.value.width,
            height: draggingNewItem.value.height,
            rotation: 0
        };
        
        placedItems.value.push(newItem);
        selectedItemId.value = newItem.id;
        draggingNewItem.value = null;
    }
};

const handleWheel = (event) => {
    event.preventDefault();
    const zoomIntensity = 0.1;
    const svg = document.getElementById('gym-canvas');
    if (!svg) return;

    // Get mouse position in SVG coordinates BEFORE zoom
    const svgP = getSvgPoint(event.clientX, event.clientY);

    // Calculate new width/height
    const direction = event.deltaY > 0 ? 1 : -1;
    const newW = svgViewBox.value.w * (1 + direction * zoomIntensity);
    const newH = svgViewBox.value.h * (1 + direction * zoomIntensity);

    // Limit zoom
    if (newW < 100 || newW > 5000) return;

    // We want to zoom towards the mouse pointer.
    // The relative position of the mouse inside the viewbox should stay the same.
    // (mouse_x - viewBox_x) / viewBox_w  ==  (mouse_x - new_viewBox_x) / new_w
    
    const mouseRelX = (svgP.x - svgViewBox.value.x) / svgViewBox.value.w;
    const mouseRelY = (svgP.y - svgViewBox.value.y) / svgViewBox.value.h;

    const newX = svgP.x - (mouseRelX * newW);
    const newY = svgP.y - (mouseRelY * newH);

    svgViewBox.value = { x: newX, y: newY, w: newW, h: newH };
};

// --- Mouse Interaction Handler ---

const handleMouseDown = (event) => {
    // If middle click or space bar held (future), we pan
    // For now, let's say dragging on empty space Pans, dragging item Moves.
    
    if (event.target.tagName !== 'image' && event.target.tagName !== 'rect' && event.target.tagName !== 'circle' && event.target.tagName !== 'line') {
        isPanning.value = true;
        lastMousePos.value = { x: event.clientX, y: event.clientY };
    }
};

const startDragItem = (event, item) => {
    if (event.button !== 0) return; 
    event.stopPropagation();
    selectedItemId.value = item.id;
    isDraggingItem.value = true;
    
    const svgP = getSvgPoint(event.clientX, event.clientY);
    dragOffset.value = {
        x: svgP.x - item.x,
        y: svgP.y - item.y
    };
};

const startRotateItem = (event, item) => {
    event.stopPropagation();
    isRotatingItem.value = true;
    
    const svgP = getSvgPoint(event.clientX, event.clientY);
    const cx = item.x;
    const cy = item.y;
    
    initialMouseAngle.value = Math.atan2(svgP.y - cy, svgP.x - cx) * (180 / Math.PI);
    initialRotation.value = item.rotation;
};

const handleMouseMove = (event) => {
    if (isPanning.value) {
        const dx = event.clientX - lastMousePos.value.x;
        const dy = event.clientY - lastMousePos.value.y;
        
        // Convert screen pixels to SVG units
        const svg = document.getElementById('gym-canvas');
        const ctm = svg.getScreenCTM();
        // Scale factor: how many SVG units per screen pixel
        // If current w is 1000 and screen w is 500, scale is 2
        // ctm.a is roughly (screen_w / svg_w)
        
        const scaleX = svgViewBox.value.w / svg.clientWidth;
        const scaleY = svgViewBox.value.h / svg.clientHeight;

        svgViewBox.value.x -= dx * scaleX;
        svgViewBox.value.y -= dy * scaleY;

        lastMousePos.value = { x: event.clientX, y: event.clientY };
    } 
    else if (isDraggingItem.value && selectedItemId.value) {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        const item = placedItems.value.find(i => i.id === selectedItemId.value);
        if (item) {
            item.x = svgP.x - dragOffset.value.x;
            item.y = svgP.y - dragOffset.value.y;
        }
    } 
    else if (isRotatingItem.value && selectedItemId.value) {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        const item = placedItems.value.find(i => i.id === selectedItemId.value);
        if (item) {
            const cx = item.x;
            const cy = item.y;
            const currentAngle = Math.atan2(svgP.y - cy, svgP.x - cx) * (180 / Math.PI);
            const delta = currentAngle - initialMouseAngle.value;
            item.rotation = initialRotation.value + delta;
        }
    }
};

const handleMouseUp = () => {
    isPanning.value = false;
    isDraggingItem.value = false;
    isRotatingItem.value = false;
};

const deleteSelected = () => {
    if (selectedItemId.value) {
        placedItems.value = placedItems.value.filter(i => i.id !== selectedItemId.value);
        selectedItemId.value = null;
    }
};

const clearCanvas = () => {
    if(confirm("Clear all items?")) {
        placedItems.value = [];
        selectedItemId.value = null;
    }
};

onMounted(() => {
    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
    });
});
onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('mouseup', handleMouseUp);
});
</script>

<template>
    <Head title="Gym Builder Studio" />

    <div class="h-screen w-full bg-base-300 text-base-content font-sans flex flex-col overflow-hidden select-none">
        
        <!-- Header -->
        <header class="navbar bg-base-100/90 backdrop-blur border-b border-base-content/10 z-20 shadow-sm shrink-0">
            <div class="flex-1">
                <a class="btn btn-ghost text-2xl font-black italic tracking-tighter text-primary">FitPung<span class="text-base-content">Studio</span></a>
            </div>
            <div class="flex-none gap-2">
                <div v-if="step === 2" class="flex gap-2">
                    <button class="btn btn-sm btn-ghost" @click="step = 1">Change Layout</button>
                    <button class="btn btn-sm btn-error btn-outline" @click="clearCanvas">Clear All</button>
                    <button class="btn btn-primary btn-sm shadow-lg shadow-primary/30">Save Design</button>
                </div>
            </div>
        </header>

        <!-- Phase 1: Room Selection -->
        <div v-if="step === 1" class="flex-1 flex flex-col items-center justify-center p-8 animate-fade-in bg-neutral">
            <div class="text-center mb-10">
                <h1 class="text-4xl font-bold mb-2 text-neutral-content">Start Your Gym Design</h1>
                <p class="opacity-60 text-neutral-content">Select a room layout to begin placing your equipment</p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <!-- Using fixed viewBox for previews -->
                <button 
                    v-for="shape in roomShapes" 
                    :key="shape.id"
                    @click="selectRoom(shape)"
                    class="card bg-base-100 shadow-xl hover:shadow-2xl hover:scale-105 transition-all border-2 border-transparent hover:border-primary group cursor-pointer w-64 h-64 flex items-center justify-center relative overflow-hidden"
                >
                    <svg viewBox="0 0 1000 800" class="w-full h-full p-4 opacity-50 group-hover:opacity-100 transition-opacity">
                        <polygon :points="shape.points" fill="currentColor" class="text-base-300 group-hover:text-primary transition-colors" stroke="currentColor" stroke-width="10" />
                    </svg>
                    <div class="absolute bottom-4 font-bold text-lg tracking-wider uppercase">{{ shape.name }}</div>
                </button>
            </div>
        </div>

        <!-- Phase 2: Builder Interface -->
        <div v-if="step === 2" class="flex-1 flex overflow-hidden">
            
            <!-- Sidebar: Equipment -->
            <aside class="w-72 bg-base-100 border-r border-base-content/10 flex flex-col z-10 shadow-xl">
                <div class="p-4 border-b border-base-content/10">
                    <h2 class="font-bold text-lg">Equipment</h2>
                    <p class="text-xs opacity-50">Drag items to the floor</p>
                </div>
                <div class="flex-1 overflow-y-auto p-4 grid grid-cols-2 gap-3 content-start">
                    <div 
                        v-for="item in equipmentTypes" 
                        :key="item.id"
                        draggable="true"
                        @dragstart="handleDragStartFromSidebar(item, $event)"
                        class="card bg-base-200 hover:bg-neutral cursor-grab active:cursor-grabbing p-2 flex flex-col items-center transition-all hover:shadow-md border border-transparent hover:border-primary/20"
                    >
                        <img :src="item.src" class="w-16 h-16 object-contain mb-2 pointer-events-none select-none" />
                        <span class="text-[10px] font-bold text-center uppercase">{{ item.name }}</span>
                    </div>
                </div>
                <!-- Mini controls info -->
                <div class="p-4 bg-base-200 text-xs opacity-60">
                    <p>🖱️ Scroll to Zoom</p>
                    <p>✋ Click & Drag BG to Pan</p>
                </div>
            </aside>

            <!-- Main Canvas Area -->
            <main class="flex-1 bg-neutral relative overflow-hidden bg-[radial-gradient(#ffffff05_1px,transparent_1px)] [background-size:20px_20px]">
                
                <!-- SVG Canvas taking Full Width/Height -->
                <svg 
                    id="gym-canvas"
                    :viewBox="`${svgViewBox.x} ${svgViewBox.y} ${svgViewBox.w} ${svgViewBox.h}`"
                    class="w-full h-full cursor-crosshair touch-none"
                    @dragover.prevent
                    @drop="handleDropOnCanvas"
                    @wheel="handleWheel"
                    @mousedown="handleMouseDown"
                    @click="selectedItemId = null" 
                >
                    <defs>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="gray" stroke-width="0.5" opacity="0.2"/>
                        </pattern>
                    </defs>

                    <!-- Room Floor -->
                    <polygon 
                        :points="selectedRoom.points" 
                        fill="#f8fafc" 
                        stroke="#333" 
                        stroke-width="5"
                        class="drop-shadow-2xl"
                    />
                    <!-- Grid overlay inside room -->
                    <polygon :points="selectedRoom.points" fill="url(#grid)" pointer-events="none" opacity="0.6" />

                    <!-- Placed Items -->
                    <g 
                        v-for="item in placedItems" 
                        :key="item.id"
                        :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                        @mousedown="startDragItem($event, item)"
                        class="cursor-move group"
                    >
                        <!-- Selection Halo -->
                        <rect 
                            v-if="selectedItemId === item.id"
                            :x="-item.width/2 - 10" 
                            :y="-item.height/2 - 10" 
                            :width="item.width + 20" 
                            :height="item.height + 20" 
                            fill="none" 
                            stroke="#570df8" 
                            stroke-width="3" 
                            stroke-dasharray="8,5" 
                            class="animate-pulse"
                            vector-effect="non-scaling-stroke"
                        />

                        <!-- The Equipment Image -->
                        <image 
                            :href="item.src" 
                            :x="-item.width/2" 
                            :y="-item.height/2" 
                            :width="item.width" 
                            :height="item.height"
                            class="drop-shadow-md filter hover:brightness-110 transition-all"
                        />

                        <!-- Rotation Handle -->
                        <g 
                            v-if="selectedItemId === item.id"
                            :transform="`translate(0, ${-item.height/2 - 40})`"
                            class="cursor-ew-resize hover:scale-125 transition-transform origin-bottom"
                            @mousedown.stop="startRotateItem($event, item)"
                        >
                            <line x1="0" y1="0" x2="0" y2="35" stroke="#570df8" stroke-width="2" />
                            <circle r="8" fill="#570df8" stroke="white" stroke-width="2" />
                        </g>
                    </g>
                </svg>

                <!-- Zoom Controls Overlay -->
                <!-- Optional floating UI buttons if user prefers clicking over scrolling -->
                <div class="absolute bottom-6 right-6 flex flex-col gap-2">
                    <button class="btn btn-circle btn-neutral shadow-xl" @click="svgViewBox.w *= 0.9; svgViewBox.h *= 0.9">➕</button>
                    <button class="btn btn-circle btn-neutral shadow-xl" @click="svgViewBox.w *= 1.1; svgViewBox.h *= 1.1">➖</button>
                    <button class="btn btn-neutral shadow-xl" @click="selectRoom(selectedRoom)">Fit</button>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
