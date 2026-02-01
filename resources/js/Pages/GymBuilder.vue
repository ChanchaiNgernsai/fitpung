<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    layout: Object
});

// --- Form for Saving ---
const form = useForm({
    name: props.layout?.name || '',
    room_config: props.layout?.room_config || null,
    items: props.layout?.items || [],
});


// --- Assets ---
const pixelsPerMeter = ref(50); // 50px = 1m

const equipmentTypes = [
    { id: 'treadmill', name: 'Treadmill', src: '/images/equipment/Treadmill.svg', w_m: 1, h_m: 2.2 },
    { id: 'elliptical', name: 'Elliptical', src: '/images/equipment/Elliptical.svg', w_m: 0.8, h_m: 2 },
    { id: 'bench', name: 'Bench Press', src: '/images/equipment/BenchPress.svg', w_m: 1.2, h_m: 1.5 },
    { id: 'smith', name: 'Smith Machine', src: '/images/equipment/SmithMachine.svg', w_m: 2.2, h_m: 1.4 },
    { id: 'cycle', name: 'Bike', src: '/images/equipment/RecumbentCycle.svg', w_m: 0.6, h_m: 1.2 },
    { id: 'lat', name: 'Lat Pulldown', src: '/images/equipment/LatPulldown.svg', w_m: 1.2, h_m: 1.2 },
    { id: 'dumbbells', name: 'Dumbbells', src: '/images/equipment/Dumbbells.svg', w_m: 2.5, h_m: 0.6 },
];

// Combine equipment types with scale
const equipmentList = computed(() => {
    return equipmentTypes.map(item => ({
        ...item,
        width: item.w_m * pixelsPerMeter.value,
        height: item.h_m * pixelsPerMeter.value
    }));
});

// --- Room Shapes ---
// Points are roughly in a 1000x1000 coordinate space
const roomShapes = [
    { id: 'rect', name: 'Rectangle', points: '100,100 900,100 900,700 100,700' },
    { id: 'l-shape', name: 'L-Shape', points: '100,100 900,100 900,400 500,400 500,700 100,700' },
    { id: 'l-shape-alt', name: 'L-Shape 2', points: '100,100 500,100 500,400 900,400 900,700 100,700' },
    { id: 't-shape', name: 'T-Shape', points: '100,200 900,200 900,400 600,400 600,700 400,700 400,400 100,400' },
    { id: 'u-shape', name: 'U-Shape', points: '100,100 300,100 300,500 700,500 700,100 900,100 900,700 100,700' },
    { id: 'trapezoid', name: 'Trapezoid', points: '300,100 900,100 900,700 100,700' },
    { id: 'triangle', name: 'Triangle', points: '500,100 900,700 100,700' },
    { id: 'custom', name: 'Custom Draw', points: '', icon: 'plus' },
];

// --- State ---
const step = ref(1); // 1: Shape Select, 1.5: Drawing, 2: Builder
const selectedRoom = ref(null);
const placedItems = ref([]);
const selectedItemId = ref(null);
const selectedItem = computed(() => placedItems.value.find(i => i.id === selectedItemId.value));
// Custom Drawing / Editor State
const drawingPoints = ref([]); 
const floorWalls = ref([]);   // New Wall-based system
const selectedWallId = ref(null);
const draggingWallId = ref(null);
const draggingEndPoint = ref(null); // 'start', 'end', or 'both'
const initialWallPos = ref(null); // Store stating coords to prevent jumping
const mousePos = ref({ x: 0, y: 0 });
const snapGrid = 1; // 1px grid = Free movement
const draggingVertexIndex = ref(null);
const myLayouts = ref(JSON.parse(localStorage.getItem('my_gym_layouts') || '[]'));

const allRoomShapes = computed(() => {
    const base = [
        { id: 'rect', name: 'Rectangle', points: '100,100 900,100 900,700 100,700' },
        { id: 'l-shape', name: 'L-Shape', points: '100,100 900,100 900,400 500,400 500,700 100,700' },
        { id: 'l-shape-alt', name: 'L-Shape 2', points: '100,100 500,100 500,400 900,400 900,700 100,700' },
        { id: 't-shape', name: 'T-Shape', points: '100,200 900,200 900,400 600,400 600,700 400,700 400,400 100,400' },
        { id: 'u-shape', name: 'U-Shape', points: '100,100 300,100 300,500 700,500 700,100 900,100 900,700 100,700' },
        { id: 'trapezoid', name: 'Trapezoid', points: '300,100 900,100 900,700 100,700' },
        { id: 'triangle', name: 'Triangle', points: '500,100 900,700 100,700' },
    ];
    
    const customs = myLayouts.value.map(l => ({ ...l, isCustom: true }));
    return [...base, ...customs, { id: 'custom', name: 'Custom Draw', points: '', icon: 'plus' }];
});

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
    if (room.id === 'custom') {
        step.value = 1.5;
        // Start with 4 walls forming a default room
        floorWalls.value = [
            { id: 1, x1: 200, y1: 200, x2: 800, y2: 200 },
            { id: 2, x1: 800, y1: 200, x2: 800, y2: 600 },
            { id: 3, x1: 800, y1: 600, x2: 200, y2: 600 },
            { id: 4, x1: 200, y1: 600, x2: 200, y2: 200 }
        ];
        selectedWallId.value = 1;
        svgViewBox.value = { x: 0, y: 0, w: 1000, h: 800 };
        return;
    }

    selectedRoom.value = room;
    if (room.points) {
        floorWalls.value = []; // Clear custom walls if picking standard room
    } else if (room.walls) {
        floorWalls.value = JSON.parse(JSON.stringify(room.walls));
    }
    
    step.value = 2;
    
    // Fit to screen logic
    let bbox;
    if (room.points) {
        bbox = getBoundingBox(room.points);
    } else if (room.walls) {
        const xs = room.walls.flatMap(w => [w.x1, w.x2]);
        const ys = room.walls.flatMap(w => [w.y1, w.y2]);
        bbox = { 
            x: Math.min(...xs), y: Math.min(...ys), 
            w: Math.max(...xs) - Math.min(...xs), 
            h: Math.max(...ys) - Math.min(...ys) 
        };
    } else {
        bbox = { x: 0, y: 0, w: 1000, h: 800 };
    }

    const padding = 150;
    svgViewBox.value = {
        x: bbox.x - padding,
        y: bbox.y - padding,
        w: bbox.w + (padding * 2),
        h: bbox.h + (padding * 2)
    };
};

const handleCanvasMouseDown = (event) => {
    const svgP = getSvgPoint(event.clientX, event.clientY);
    
    if (step.value === 1.5) {
        // 1. Check for End-point Handles
        for (const wall of floorWalls.value) {
            const hDistS = Math.sqrt(Math.pow(wall.x1 - svgP.x, 2) + Math.pow(wall.y1 - svgP.y, 2));
            const hDistE = Math.sqrt(Math.pow(wall.x2 - svgP.x, 2) + Math.pow(wall.y2 - svgP.y, 2));
            
            if (hDistS < 20) {
                draggingWallId.value = wall.id;
                draggingEndPoint.value = 'start';
                selectedWallId.value = wall.id;
                initialWallPos.value = { x1: wall.x1, y1: wall.y1, mx: svgP.x, my: svgP.y };
                return;
            }
            if (hDistE < 20) {
                draggingWallId.value = wall.id;
                draggingEndPoint.value = 'end';
                selectedWallId.value = wall.id;
                initialWallPos.value = { x2: wall.x2, y2: wall.y2, mx: svgP.x, my: svgP.y };
                return;
            }
        }

        // 2. Check for Wall Line Click (Drag entire wall)
        const clickedWall = floorWalls.value.find(wall => {
            const d = distToSegment(svgP, { x: wall.x1, y: wall.y1 }, { x: wall.x2, y: wall.y2 });
            return d < 15;
        });

        if (clickedWall) {
            handleWallMouseDown(clickedWall, event);
            return;
        }

        selectedWallId.value = null;
    }
    handleMouseDown(event);
};

const handleWallMouseDown = (wall, event) => {
    const svgP = getSvgPoint(event.clientX, event.clientY);
    selectedWallId.value = wall.id;
    draggingWallId.value = wall.id;
    draggingEndPoint.value = 'both';
    initialWallPos.value = { 
        x1: wall.x1, y1: wall.y1, 
        x2: wall.x2, y2: wall.y2,
        mx: svgP.x, my: svgP.y 
    };
};

const handleEndPointMouseDown = (wall, point, event) => {
    const svgP = getSvgPoint(event.clientX, event.clientY);
    draggingWallId.value = wall.id;
    draggingEndPoint.value = point; // 'start' or 'end'
    selectedWallId.value = wall.id;
    initialWallPos.value = { 
        x1: wall.x1, y1: wall.y1, 
        x2: wall.x2, y2: wall.y2,
        mx: svgP.x, my: svgP.y 
    };
};

// Math helpers for walls
const distToSegment = (p, v, w) => {
  const l2 = Math.pow(v.x - w.x, 2) + Math.pow(v.y - w.y, 2);
  if (l2 === 0) return Math.sqrt(Math.pow(p.x - v.x, 2) + Math.pow(p.y - v.y, 2));
  let t = ((p.x - v.x) * (w.x - v.x) + (p.y - v.y) * (w.y - v.y)) / l2;
  t = Math.max(0, Math.min(1, t));
  return Math.sqrt(Math.pow(p.x - (v.x + t * (w.x - v.x)), 2) + Math.pow(p.y - (v.y + t * (w.y - v.y)), 2));
};

const addWall = () => {
    const newWall = { id: Date.now(), x1: 400, y1: 300, x2: 600, y2: 300 };
    floorWalls.value.push(newWall);
    selectedWallId.value = newWall.id;
};

const deleteWall = () => {
    if (selectedWallId.value) {
        floorWalls.value = floorWalls.value.filter(w => w.id !== selectedWallId.value);
        selectedWallId.value = null;
    }
};

const finishWalls = () => {
    if (floorWalls.value.length === 0) return;
    selectedRoom.value = { 
        id: 'custom-' + Date.now(), 
        name: 'My Custom View', 
        walls: JSON.parse(JSON.stringify(floorWalls.value)) 
    };
    step.value = 2;
    selectRoom(selectedRoom.value); // Recalculate bbox
};

const saveTemplate = () => {
    const name = prompt("ชื่อแบบแปลนของคุณ:", "แบบแปลนใหม่ " + (myLayouts.value.length + 1));
    if (name) {
        myLayouts.value.push({ 
            id: 'user-' + Date.now(), 
            name, 
            walls: JSON.parse(JSON.stringify(floorWalls.value)) 
        });
        localStorage.setItem('my_gym_layouts', JSON.stringify(myLayouts.value));
        alert("บันทึกเรียบร้อย!");
    }
};

const handleDragStartFromSidebar = (item, event) => {
    draggingNewItem.value = item;
    event.dataTransfer.effectAllowed = 'copy';
};

const getSvgPoint = (clientX, clientY) => {
    const svg = document.getElementById('gym-canvas');
    if (!svg) return { x: clientX, y: clientY };
    const pt = svg.createSVGPoint();
    pt.x = clientX;
    pt.y = clientY;
    const transformed = pt.matrixTransform(svg.getScreenCTM().inverse());
    return { x: transformed.x, y: transformed.y };
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
    // If we clicked an item, we handle select in startDragItem (mousedown on group)
    // But if we clicked empty space, we pan and deselect
    
    // Check if we clicked on an item or the background
    const isItemClick = event.target.closest('.cursor-move');
    
    if (!isItemClick) {
        selectedItemId.value = null; // Deselect if clicking background
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
    
    if (step.value === 1.5) {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        mousePos.value = {
            x: Math.round(svgP.x / snapGrid) * snapGrid,
            y: Math.round(svgP.y / snapGrid) * snapGrid
        };

        if (draggingWallId.value !== null && initialWallPos.value) {
            const wall = floorWalls.value.find(w => w.id === draggingWallId.value);
            
            // Calculate absolute distance moved by mouse since click
            const dx_raw = svgP.x - initialWallPos.value.mx;
            const dy_raw = svgP.y - initialWallPos.value.my;

            // Apply movement based on drag mode
            if (draggingEndPoint.value === 'start') {
                wall.x1 = initialWallPos.value.x1 + dx_raw;
                wall.y1 = initialWallPos.value.y1 + dy_raw;
                for (const other of floorWalls.value) {
                    if (other.id === wall.id) continue;
                    const targets = [{x: other.x1, y: other.y1}, {x: other.x2, y: other.y2}];
                    for (const t of targets) {
                         if (Math.sqrt(Math.pow(t.x - wall.x1, 2) + Math.pow(t.y - wall.y1, 2)) < 20) {
                             wall.x1 = t.x; wall.y1 = t.y; break;
                         }
                    }
                }
            } else if (draggingEndPoint.value === 'end') {
                wall.x2 = initialWallPos.value.x2 + dx_raw;
                wall.y2 = initialWallPos.value.y2 + dy_raw;
                for (const other of floorWalls.value) {
                    if (other.id === wall.id) continue;
                    const targets = [{x: other.x1, y: other.y1}, {x: other.x2, y: other.y2}];
                    for (const t of targets) {
                         if (Math.sqrt(Math.pow(t.x - wall.x2, 2) + Math.pow(t.y - wall.y2, 2)) < 20) {
                             wall.x2 = t.x; wall.y2 = t.y; break;
                         }
                    }
                }
            } else if (draggingEndPoint.value === 'both') {
                wall.x1 = initialWallPos.value.x1 + dx_raw;
                wall.y1 = initialWallPos.value.y1 + dy_raw;
                wall.x2 = initialWallPos.value.x2 + dx_raw;
                wall.y2 = initialWallPos.value.y2 + dy_raw;
            }
        }
    }
};

const handleMouseUp = () => {
    isPanning.value = false;
    isDraggingItem.value = false;
    isRotatingItem.value = false;
    draggingVertexIndex.value = null;
    draggingWallId.value = null;
    draggingEndPoint.value = null;
};

const rotateSelected = () => {
    if (selectedItemId.value) {
        const item = placedItems.value.find(i => i.id === selectedItemId.value);
        if (item) {
            item.rotation = (item.rotation || 0) + 90;
        }
    }
};

const deleteSelected = () => {
    if (selectedItemId.value) {
        const index = placedItems.value.findIndex(i => i.id === selectedItemId.value);
        if (index !== -1) {
            placedItems.value.splice(index, 1);
            selectedItemId.value = null;
        }
    }
};

const clearCanvas = () => {
    if(confirm("Clear all items?")) {
        placedItems.value = [];
        selectedItemId.value = null;
    }
};

const showSuccessModal = ref(false);

const saveLayout = () => {
    if (!selectedRoom.value) return;

    form.room_config = selectedRoom.value;
    form.items = placedItems.value;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessModal.value = true;
            setTimeout(() => {
                router.visit(route('dashboard'));
            }, 1000);
        },
        onError: () => {
            alert('Failed to save layout. Please check your connection.');
        }
    };

    if (props.layout) {
        form.put(route('gym-builder.update', props.layout.id), options);
    } else {
        const name = prompt("Name your design:", "My New Gym");
        if (!name) return;
        form.name = name;
        form.post(route('gym-builder.store'), options);
    }
};

onMounted(() => {
    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
    });

    // Initialize from props if editing
    if (props.layout) {
        // Deep clone to avoid mutating props and allow distinct reactivity
        selectedRoom.value = JSON.parse(JSON.stringify(props.layout.room_config));
        placedItems.value = JSON.parse(JSON.stringify(props.layout.items));
        step.value = 2;
        
        // Fit view
        nextTick(() => {
             const bbox = getBoundingBox(selectedRoom.value.points);
             const padding = 100;
             svgViewBox.value = {
                x: bbox.x - padding,
                y: bbox.y - padding,
                w: bbox.w + (padding * 2),
                h: bbox.h + (padding * 2)
            };
        });
    }
});
onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('mouseup', handleMouseUp);
});
</script>

<template>
    <Head title="Gym Builder Studio" />

    <AppLayout>
        <template #header-actions>
            <div class="flex gap-2 items-center">
                <Link :href="route('dashboard')" class="btn btn-sm btn-neutral gap-1 mr-2 hidden md:flex">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                     Dashboard
                </Link>
                <div v-if="step === 2" class="flex gap-2">
                    <button class="btn btn-sm btn-ghost" @click="step = 1">Change Layout</button>
                    <button class="btn btn-sm btn-error btn-outline" @click="clearCanvas">Clear All</button>
                    <button 
                        class="btn btn-primary btn-sm shadow-lg shadow-primary/30 min-w-[100px]" 
                        @click="saveLayout" 
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                        <span v-else>Save Design</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="flex-1 flex flex-col overflow-hidden select-none bg-base-300 w-full h-full">

        <!-- Phase 1: Room Selection -->
        <div v-if="step === 1" class="flex-1 flex flex-col items-center justify-center p-8 animate-fade-in bg-neutral relative overflow-hidden text-neutral-content">
            <!-- Background Image/Gradient -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-accent/20 mix-blend-overlay"></div>
                <!-- Abstract Gym shapes -->
                <svg class="absolute top-0 left-0 w-full h-full opacity-10" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 0 L50 100 L100 0 Z" fill="currentColor" />
                </svg>
            </div>

            <div class="text-center mb-10 relative z-10">
                <h1 class="text-4xl font-bold mb-2">Start Your Gym Design</h1>
                <p class="opacity-60">Select a room layout to begin placing your equipment</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full max-w-6xl px-4 relative z-10">
                <button 
                    v-for="shape in allRoomShapes" 
                    :key="shape.id"
                    @click="selectRoom(shape)"
                    class="card bg-base-100 shadow-xl hover:shadow-2xl hover:scale-105 transition-all border-2 border-transparent hover:border-primary group cursor-pointer w-full aspect-square flex items-center justify-center relative overflow-hidden"
                >
                    <button 
                        v-if="shape.isCustom" 
                        @click="deleteTemplate(shape.id, $event)"
                        class="absolute top-2 right-2 btn btn-xs btn-circle btn-error opacity-0 group-hover:opacity-100 transition-opacity z-20"
                    >
                        ✕
                    </button>
                    <div v-if="shape.icon === 'plus'" class="flex flex-col items-center justify-center p-8">
                         <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32 text-base-content/30 group-hover:text-primary transition-colors mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                         </svg>
                    </div>
                    <svg v-else viewBox="0 0 1000 800" class="w-full h-full p-8 transition-all duration-300">
                        <polygon 
                            :points="shape.points" 
                            fill="currentColor" 
                            class="text-base-content/30 group-hover:text-primary transition-colors" 
                            stroke="currentColor" 
                            stroke-width="20"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <div class="absolute bottom-4 font-bold text-lg tracking-wider uppercase px-4 truncate w-full text-center">{{ shape.name }}</div>
                </button>
            </div>
        </div>

        <!-- Phase 1.5: Wall-Based Floor Designer -->
        <div v-if="step === 1.5" class="flex-1 flex flex-col md:flex-row overflow-hidden p-0 relative">
            
            <aside class="w-full md:w-80 bg-base-100 border-r border-base-content/10 flex flex-col z-10 p-6 shadow-2xl">
                <div class="mb-6">
                    <h2 class="text-2xl font-black text-primary">Wall Designer</h2>
                    <p class="text-xs opacity-50 uppercase tracking-widest mt-1">ประกอบโครงสร้างยิมของคุณ</p>
                </div>
                
                <div class="flex flex-col gap-4">
                    <button class="btn btn-primary gap-3 shadow-lg h-14" @click="addWall">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                        <span class="text-lg">เพิ่มกำแพงใหม่</span>
                    </button>
                    
                    <button v-if="selectedWallId" class="btn btn-error btn-outline btn-sm animate-in fade-in zoom-in duration-200" @click="deleteWall">
                        ลบกำแพงที่เลือก
                    </button>

                    <div class="divider"></div>
                    
                    <div class="space-y-3">
                        <button class="btn btn-outline btn-block" @click="saveTemplate">บันทึกเป็นแบบของฉัน</button>
                        <button class="btn btn-primary btn-block shadow-xl text-lg h-14" @click="finishWalls">เสร็จสิ้น และวางเครื่อง</button>
                        <button class="btn btn-ghost btn-block btn-sm opacity-50" @click="step = 1">ยกเลิก</button>
                    </div>
                </div>

                <div class="mt-auto p-4 bg-primary/5 rounded-2xl border border-primary/10 text-[11px] space-y-2">
                    <p class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary"></span> ลากจุดปลายเพื่อยืด/หด หรือหมุนกำแพง</p>
                    <p class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary"></span> ลากกลางเส้นเพื่อย้ายตำแหน่ง</p>
                    <p class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary"></span> จุดปลายจะ "ดูด" ติดกันเมื่อเข้าใกล้</p>
                </div>
            </aside>

            <main class="flex-1 bg-base-300 relative overflow-hidden bg-[radial-gradient(currentColor_1px,transparent_1px)] [background-size:24px_24px] text-base-content/5">
                <svg 
                    id="gym-canvas"
                    class="w-full h-full cursor-crosshair select-none touch-none"
                    :viewBox="`${svgViewBox.x} ${svgViewBox.y} ${svgViewBox.w} ${svgViewBox.h}`"
                    @mousedown="handleCanvasMouseDown"
                    @mousemove="handleMouseMove"
                >
                     <!-- Render Walls -->
                     <g v-for="w in floorWalls" :key="w.id">
                         <!-- The Wall Line -->
                         <line 
                            :x1="w.x1" :y1="w.y1" :x2="w.x2" :y2="w.y2"
                            :stroke="selectedWallId === w.id ? '#f59e0b' : '#570df8'"
                            :stroke-width="selectedWallId === w.id ? 14 : 8"
                            stroke-linecap="round"
                            class="cursor-move drop-shadow-md transition-colors duration-200"
                         />
                         <!-- Wall Glow (Selected) - Invisible but provides better hit area -->
                         <line 
                            :x1="w.x1" :y1="w.y1" :x2="w.x2" :y2="w.y2"
                            stroke="transparent" stroke-width="30" stroke-linecap="round"
                            class="cursor-move"
                         />
                         
                         <!-- Length Label -->
                         <text 
                            :x="(w.x1 + w.x2)/2" 
                            :y="(w.y1 + w.y2)/2 - 15"
                            :transform="`rotate(${Math.atan2(w.y2-w.y1, w.x2-w.x1) * 180 / Math.PI}, ${(w.x1 + w.x2)/2}, ${(w.y1 + w.y2)/2})`"
                            text-anchor="middle"
                            font-weight="900"
                            fill="#1e293b"
                            style="font-size: 16px; paint-order: stroke; stroke: white; stroke-width: 4px;"
                         >
                            {{ (Math.sqrt(Math.pow(w.x2-w.x1, 2) + Math.pow(w.y2-w.y1, 2)) / pixelsPerMeter).toFixed(1) }}m
                         </text>

                         <!-- End-point Handles -->
                         <circle 
                            :cx="w.x1" :cy="w.y1" r="10" 
                            fill="white" :stroke="selectedWallId === w.id ? '#f59e0b' : '#570df8'" stroke-width="4"
                            class="cursor-pointer shadow-sm"
                            @mousedown.stop="handleEndPointMouseDown(w, 'start', $event)"
                         />
                         <circle 
                            :cx="w.x2" :cy="w.y2" r="10" 
                            fill="white" :stroke="selectedWallId === w.id ? '#f59e0b' : '#570df8'" stroke-width="4"
                            class="cursor-pointer shadow-sm"
                            @mousedown.stop="handleEndPointMouseDown(w, 'end', $event)"
                         />
                     </g>
                </svg>
            </main>
        </div>

        <!-- Phase 2: Builder Interface -->
        <div v-if="step === 2" class="flex-1 flex flex-col md:flex-row overflow-hidden p-0 relative">
            
            <!-- Sidebar: Equipment -->
            <aside class="w-full md:w-72 h-48 md:h-auto bg-base-100 border-b md:border-b-0 md:border-r border-base-content/10 flex flex-col z-10 shadow-xl shrink-0">
                <div class="p-4 border-b border-base-content/10 flex justify-between items-center">
                    <div>
                        <h2 class="font-bold text-lg">Equipment</h2>
                        <p class="text-xs opacity-50">Drag items to the floor</p>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-4 grid grid-cols-2 gap-3 content-start">
                    <div 
                        v-for="item in equipmentList" 
                        :key="item.id"
                        draggable="true"
                        @dragstart="handleDragStartFromSidebar(item, $event)"
                        class="card bg-base-200 hover:bg-neutral cursor-grab active:cursor-grabbing p-2 flex flex-col items-center transition-all hover:shadow-md border border-transparent hover:border-primary/20"
                    >
                        <img :src="item.src" class="w-16 h-16 object-contain mb-2 pointer-events-none select-none" />
                        <span class="text-[10px] font-bold text-center uppercase">{{ item.name }}</span>
                        <span class="text-[8px] opacity-40">{{ item.w_m }}m x {{ item.h_m }}m</span>
                    </div>
                </div>
                <!-- Mini controls info -->
                <div class="p-4 bg-base-200 text-xs opacity-60">
                    <p>🖱️ Scroll to Zoom</p>
                    <p>✋ Click & Drag BG to Pan</p>
                </div>
            </aside>

            <!-- Main Canvas Area -->
            <main class="flex-1 bg-base-200 relative overflow-hidden text-base-content/20 bg-[radial-gradient(currentColor_1px,transparent_1px)] [background-size:20px_20px]">
                
                <!-- SVG Canvas taking Full Width/Height -->
                <svg 
                    id="gym-canvas"
                    :viewBox="`${svgViewBox.x} ${svgViewBox.y} ${svgViewBox.w} ${svgViewBox.h}`"
                    class="w-full h-full cursor-crosshair touch-none"
                    @dragover.prevent
                    @drop="handleDropOnCanvas"
                    @wheel="handleWheel"
                    @mousedown="handleMouseDown"
                >
                    <defs>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="gray" stroke-width="0.5" opacity="0.2"/>
                        </pattern>
                    </defs>

                    <!-- Room Floor & Walls -->
                    <g v-if="selectedRoom?.walls">
                        <line 
                           v-for="w in selectedRoom.walls" 
                           :key="w.id"
                           :x1="w.x1" :y1="w.y1" :x2="w.x2" :y2="w.y2"
                           stroke="#1e293b" 
                           stroke-width="12"
                           stroke-linecap="round"
                        />
                    </g>
                    <g v-else-if="selectedRoom?.blocks">
                        <rect 
                           v-for="b in selectedRoom.blocks" 
                           :key="b.id"
                           :x="b.x" :y="b.y" :width="b.w" :height="b.h"
                           fill="#f8fafc" stroke="#333" stroke-width="5"
                           class="drop-shadow-2xl"
                        />
                        <rect v-for="b in selectedRoom.blocks" :key="'grid-'+b.id" :x="b.x" :y="b.y" :width="b.w" :height="b.h" fill="url(#grid)" pointer-events="none" opacity="0.6" />
                    </g>
                    <g v-else-if="selectedRoom?.points">
                        <polygon 
                            :points="selectedRoom.points" 
                            fill="#f8fafc" 
                            stroke="#333" 
                            stroke-width="5"
                            class="drop-shadow-2xl"
                        />
                        <polygon :points="selectedRoom.points" fill="url(#grid)" pointer-events="none" opacity="0.6" />
                    </g>

                    <!-- Placed Items -->
                    <g 
                        v-for="item in placedItems" 
                        :key="item.id"
                        :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                        @mousedown="startDragItem($event, item)"
                        class="cursor-move group"
                    >
                        <!-- The Equipment Image -->
                        <image 
                            :href="item.src" 
                            :x="-item.width/2" 
                            :y="-item.height/2" 
                            :width="item.width" 
                            :height="item.height"
                            class="drop-shadow-md filter hover:brightness-110 transition-all"
                        />
                    </g>
                    
                    <!-- Selection Overlay Layer (Rendered on top) -->
                    <g v-if="selectedItem">
                       <!-- Primary container: follows item X/Y -->
                       <g :transform="`translate(${selectedItem.x}, ${selectedItem.y})`">
                            
                            <!-- Rotated Controls (Halo & Handle) -->
                            <!-- These MATCH item rotation to surround it correctly -->
                            <g :transform="`rotate(${selectedItem.rotation})`">
                                <rect 
                                    :x="-selectedItem.width/2 - 10" 
                                    :y="-selectedItem.height/2 - 10" 
                                    :width="selectedItem.width + 20" 
                                    :height="selectedItem.height + 20" 
                                    fill="none" 
                                    stroke="#570df8" 
                                    stroke-width="2" 
                                    stroke-dasharray="6,4" 
                                    class="animate-pulse"
                                    vector-effect="non-scaling-stroke"
                                />
                                <!-- Rotation Handle -->
                                <g 
                                    :transform="`translate(0, ${-selectedItem.height/2 - 40})`"
                                    class="cursor-ew-resize"
                                    @mousedown.stop="startRotateItem($event, selectedItem)"
                                >
                                    <line x1="0" y1="0" x2="0" y2="35" stroke="#570df8" stroke-width="2" />
                                    <circle r="6" fill="#570df8" stroke="white" stroke-width="2" />
                                </g>
                            </g>

                            <!-- Non-Rotated Controls (Floating Menu) -->
                            <!-- Positioned to the right of the item -->
                            <foreignObject 
                                :x="Math.max(selectedItem.width, selectedItem.height)/2 + 10" 
                                :y="-50" 
                                width="60" 
                                height="100"
                                class="overflow-visible"
                            >
                                <div class="flex flex-col gap-2" xmlns="http://www.w3.org/1999/xhtml" @mousedown.stop>
                                    <button 
                                        class="btn btn-circle btn-sm btn-info text-white shadow-xl hover:scale-110 transition-transform" 
                                        @click.stop="rotateSelected"
                                        title="Rotate 90°"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    </button>
                                    <button 
                                        class="btn btn-circle btn-sm btn-error text-white shadow-xl hover:scale-110 transition-transform" 
                                        @click.stop="deleteSelected"
                                        title="Delete"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </foreignObject>
                       </g>
                    </g>
                    <!-- Scale Indicator -->
                    <g :transform="`translate(${svgViewBox.x + 20}, ${svgViewBox.y + svgViewBox.h - 40})`" class="pointer-events-none opacity-50">
                        <line x1="0" y1="0" :x2="pixelsPerMeter" y2="0" stroke="currentColor" stroke-width="2" />
                        <line x1="0" y1="-5" x2="0" y2="5" stroke="currentColor" stroke-width="2" />
                        <line :x1="pixelsPerMeter" y1="-5" :x2="pixelsPerMeter" y2="5" stroke="currentColor" stroke-width="2" />
                        <text x="0" y="20" font-size="12" fill="currentColor" font-weight="bold">1.0m</text>
                    </g>
                </svg>

                <!-- Zoom & Edit Controls Overlay -->
                <div class="fixed bottom-8 right-8 z-50 flex flex-col gap-2">
                    <button class="btn btn-circle btn-success text-white shadow-xl" @click="svgViewBox.w *= 0.9; svgViewBox.h *= 0.9" title="Zoom In">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    </button>
                    <button class="btn btn-circle btn-warning text-white shadow-xl" @click="svgViewBox.w *= 1.1; svgViewBox.h *= 1.1" title="Zoom Out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                    </button>
                    <button class="btn btn-circle btn-neutral shadow-xl" @click="selectRoom(selectedRoom)" title="Fit to Screen">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>
                </div>
            </main>
        </div>
        </div>
    </AppLayout>

    <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm animate-fade-in">
        <div class="bg-base-100 p-8 rounded-2xl shadow-2xl text-center max-w-sm mx-4 transform transition-all scale-100 border border-base-content/10">
            <div class="mb-4 text-success flex justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold mb-2">บันทึกสำเร็จ!</h3>
            <p class="text-base-content/60 mb-6 py-2">กำลังกลับไปหน้า Dashboard...</p>
            <div class="loading loading-dots loading-lg text-primary"></div>
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
