<script setup>
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import RoomSelector from '@/Components/GymBuilder/RoomSelector.vue';
import WallDesigner from '@/Components/GymBuilder/WallDesigner.vue';
import EquipmentPlacer from '@/Components/GymBuilder/EquipmentPlacer.vue';
import NavigationTools from '@/Components/GymBuilder/NavigationTools.vue';

const props = defineProps({
    layout: Object
});

// --- Form for Saving ---
const form = useForm({
    name: props.layout?.name || '',
    location: props.layout?.location || '',
    google_map_url: props.layout?.google_map_url || '',
    image: null, // For file upload
    image_url: props.layout?.image_url || null, // For preview of existing image
    room_config: props.layout?.room_config || null,
    items: props.layout?.items || [],
});


// --- Assets ---
const pixelsPerMeter = ref(100); // 100px = 1m

const equipmentTypes = [
    { id: 'treadmill', name: 'Treadmill', src: '/images/equipment/Treadmill.svg', w_m: 1.0, h_m: 2.0 },
    { id: 'incline_bench', name: 'Incline Bench Press', src: '/images/equipment/DeclineBenchPress.svg', w_m: 1.2, h_m: 1.5 },
    { id: 'bench', name: 'Bench Press', src: '/images/equipment/BenchPress.svg', w_m: 1.2, h_m: 1.5 },
    { id: 'smith', name: 'Smith Machine', src: '/images/equipment/SmithMachine.svg', w_m: 1.5, h_m: 1.2 },
    { id: 'leg_press', name: 'Leg Press', src: '/images/equipment/LegPress.svg', w_m: 1.2, h_m: 1.8 },
    { id: 'elliptical', name: 'Elliptical', src: '/images/equipment/Elliptical.svg', w_m: 0.8, h_m: 2.0 },
    { id: 'dumbbells', name: 'Dumbbells', src: '/images/equipment/Dumbbells.svg', w_m: 2.5, h_m: 0.8 },
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
// --- State ---
const step = ref(1); // 1: Shape Select, 1.5: Drawing, 2: Builder
const selectedRoom = ref(null);
const placedItems = ref([]);

// Multi-selection state
const selectedItemIds = ref(new Set());
const selectedItems = computed(() => placedItems.value.filter(i => selectedItemIds.value.has(i.id)));
// Primary item for resizing/rotating context (usually the last selected or the one clicked)
const primarySelectedItem = computed(() => selectedItems.value.length > 0 ? selectedItems.value[selectedItems.value.length - 1] : null);

const draggingNewItem = ref(null);
const drawingPoints = ref([]); 
const floorWalls = ref([]);   // New Wall-based system
const selectedWallId = ref(null);
const draggingWallId = ref(null);
const draggingEndPoint = ref(null); // 'start', 'end', or 'both'
const initialWallPos = ref(null); // Store stating coords to prevent jumping
const mousePos = ref({ x: 0, y: 0 });
const snapGrid = 1; // 1px grid = Free movement
const draggingVertexIndex = ref(null);

// Construct a list of points from discrete walls for the 'Floor' effect
const computedRoomPoints = computed(() => {
    const room = selectedRoom.value;
    if (!room || !room.walls || room.walls.length === 0) return "";
    return room.walls.map(w => `${w.x1},${w.y1} ${w.x2},${w.y2}`).join(' ');
});

const getCustomPoints = (walls) => {
    if (!walls || walls.length === 0) return "";
    return walls.map(w => `${w.x1},${w.y1} ${w.x2},${w.y2}`).join(' ');
};

// --- User-Specific Persistence ---
const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const storageKey = computed(() => currentUser.value ? `my_gym_layouts_${currentUser.value.id}` : null);
const myLayouts = ref([]);

const loadMyLayouts = () => {
    if (storageKey.value) {
        const saved = localStorage.getItem(storageKey.value);
        myLayouts.value = saved ? JSON.parse(saved) : [];
    } else {
        myLayouts.value = [];
    }
};

// Auto-reload when switching accounts
watch(storageKey, () => {
    loadMyLayouts();
}, { immediate: true });

const deleteTemplate = (id, event) => {
    event.stopPropagation();
    if (confirm("ลบแบบแปลนนี้ใช่หรือไม่?")) {
        myLayouts.value = myLayouts.value.filter(l => l.id !== id);
        if (storageKey.value) {
            localStorage.setItem(storageKey.value, JSON.stringify(myLayouts.value));
        }
    }
};

// --- Camera / ViewBox State ---
const svgViewBox = ref({ x: 0, y: 0, w: 1000, h: 800 });
const isPanning = ref(false);
const lastMousePos = ref({ x: 0, y: 0 });

// --- Interaction State ---
const activeTool = ref('select'); // 'select' or 'hand'
const isDraggingItem = ref(false);
const isRotatingItem = ref(false);
const isResizingItem = ref(false);
const isBoxSelecting = ref(false);

const dragOffsets = ref(new Map()); // Map<itemId, {dx, dy}>
const initialRotation = ref(0);
const initialMouseAngle = ref(0);
const initialSizes = ref(new Map()); // Map<itemId, {w, h}>
const initialPositions = ref(new Map()); // Map<itemId, {x, y}> (For group scaling pivot)
const clipboard = ref([]); // Store copied item data

// --- Copy/Paste Logic ---

const copySelected = () => {
    if (selectedItemIds.value.size === 0) return;
    clipboard.value = selectedItems.value.map(item => ({
        ...item,
        id: null // Will generate new ID on paste
    }));
};

const pasteSelected = () => {
    if (clipboard.value.length === 0) return;
    
    const newItems = clipboard.value.map((item, index) => ({
        ...item,
        id: Date.now() + index,
        x: item.x + 30, // Offset to show it was pasted
        y: item.y + 30
    }));
    
    placedItems.value = [...placedItems.value, ...newItems];
    
    // Select the newly pasted items
    selectedItemIds.value.clear();
    newItems.forEach(item => selectedItemIds.value.add(item.id));
    
    // Update clipboard to the new items (so subsequent pastes offset from the last one)
    clipboard.value = newItems;
};

const handleKeyDown = (event) => {
    // Prevent shortcuts if user is typing in an input or textarea
    if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;

    // Shortcuts for step 2 (Builder)
    if (step.value === 2) {
        const isMod = event.ctrlKey || event.metaKey;
        
        if (isMod && event.key === 'c') {
            event.preventDefault();
            copySelected();
        }
        if (isMod && event.key === 'v') {
            event.preventDefault();
            pasteSelected();
        }
        if (event.key === 'Delete' || event.key === 'Backspace') {
            deleteSelected(); 
        }
    }
};

onMounted(() => {
    loadMyLayouts();
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
// Box Selection logic
const selectionBoxStart = ref({ x: 0, y: 0 });
const selectionBoxCurrent = ref({ x: 0, y: 0 });
const selectionBox = computed(() => {
    if (!isBoxSelecting.value) return null;
    const x = Math.min(selectionBoxStart.value.x, selectionBoxCurrent.value.x);
    const y = Math.min(selectionBoxStart.value.y, selectionBoxCurrent.value.y);
    const w = Math.abs(selectionBoxCurrent.value.x - selectionBoxStart.value.x);
    const h = Math.abs(selectionBoxCurrent.value.y - selectionBoxStart.value.y);
    return { x, y, w, h };
});

// --- Actions ---

const getBoundingBox = (pointsStr) => {
    if (!pointsStr) return { x: 0, y: 0, w: 1000, h: 800 };
    const points = pointsStr.trim().split(/\s+/).map(p => {
        const [x, y] = p.split(',').map(Number);
        return { x, y };
    }).filter(p => !isNaN(p.x) && !isNaN(p.y));
    
    if (points.length === 0) return { x: 0, y: 0, w: 1000, h: 800 };
    
    const minX = Math.min(...points.map(p => p.x));
    const maxX = Math.max(...points.map(p => p.x));
    const minY = Math.min(...points.map(p => p.y));
    const maxY = Math.max(...points.map(p => p.y));
    
    return { x: minX, y: minY, w: maxX - minX, h: maxY - minY };
};

const wallsToPoints = (walls) => {
    if (!walls || walls.length === 0) return "";
    // Collect all unique points. Since walls are connected, 
    // we can try to follow the sequence. For simplicity in a closed loop:
    return walls.map(w => `${w.x1},${w.y1}`).join(' ');
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

    // Deep clone room and ensure it has points for rendering
    const roomClone = JSON.parse(JSON.stringify(room));
    if (roomClone.walls && !roomClone.points) {
        roomClone.points = wallsToPoints(roomClone.walls);
    }

    selectedRoom.value = roomClone;
    if (roomClone.points) {
        floorWalls.value = []; 
    } else if (roomClone.walls) {
        floorWalls.value = JSON.parse(JSON.stringify(roomClone.walls));
    }
    
    step.value = 2;
    
    // Fit to screen logic
    let bbox;
    if (roomClone.points) {
        bbox = getBoundingBox(roomClone.points);
    } else if (roomClone.walls) {
        const xs = roomClone.walls.flatMap(w => [w.x1, w.x2]);
        const ys = roomClone.walls.flatMap(w => [w.y1, w.y2]);
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
    const pointsStr = wallsToPoints(floorWalls.value);
    const customRoom = { 
        id: 'custom-' + Date.now(), 
        name: 'My Custom View', 
        walls: JSON.parse(JSON.stringify(floorWalls.value)),
        points: pointsStr
    };
    selectRoom(customRoom);
};

const saveTemplate = () => {
    const name = prompt("ชื่อแบบแปลนของคุณ:", "แบบแปลนใหม่ " + (myLayouts.value.length + 1));
    if (name) {
        myLayouts.value.push({ 
            id: 'user-' + Date.now(), 
            name, 
            creatorName: currentUser.value?.name || 'Unknown',
            creatorEmail: currentUser.value?.email || '',
            creatorId: currentUser.value?.id,
            walls: JSON.parse(JSON.stringify(floorWalls.value)) 
        });
        if (storageKey.value) {
            localStorage.setItem(storageKey.value, JSON.stringify(myLayouts.value));
            alert("บันทึกเรียบร้อย!");
        } else {
            alert("กรุณาเข้าสู่ระบบเพื่อบันทึกแบบแปลน");
        }
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
            width: draggingNewItem.value.width || 100,
            height: draggingNewItem.value.height || 100,
            rotation: 0
        };
        
        placedItems.value = [...placedItems.value, newItem];
        
        // Select the new item
        selectedItemIds.value.clear();
        selectedItemIds.value.add(newItem.id);
        
        draggingNewItem.value = null;
    }
};

const handleWheel = (event) => {
    event.preventDefault();
    const zoomIntensity = 0.1;
    const svg = document.getElementById('gym-canvas');
    if (!svg) return;

    const svgP = getSvgPoint(event.clientX, event.clientY);
    const direction = event.deltaY > 0 ? 1 : -1;
    const newW = svgViewBox.value.w * (1 + direction * zoomIntensity);
    const newH = svgViewBox.value.h * (1 + direction * zoomIntensity);

    if (newW < 100 || newW > 10000) return; 

    const mouseRelX = (svgP.x - svgViewBox.value.x) / svgViewBox.value.w;
    const mouseRelY = (svgP.y - svgViewBox.value.y) / svgViewBox.value.h;

    const newX = svgP.x - (mouseRelX * newW);
    const newY = svgP.y - (mouseRelY * newH);

    svgViewBox.value = { x: newX, y: newY, w: newW, h: newH };
};

// --- Mouse Interaction Handler ---

const handleMouseDown = (event) => {
    // Check handles first
    const isResizeHandle = event.target.closest('.cursor-se-resize');
    const isRotateHandle = event.target.closest('.cursor-ew-resize');
    if (isResizeHandle || isRotateHandle) return;

    const isItemClick = event.target.closest('.cursor-move');
    
    // 1. Pan: Hand Tool Active OR Right Click OR Middle Click OR Space+Left
    if (activeTool.value === 'hand' || event.button === 2 || event.button === 1 || (event.code === 'Space' && event.button === 0)) {
        isPanning.value = true;
        lastMousePos.value = { x: event.clientX, y: event.clientY };
        return;
    }

    // 2. Box Selection: Left Click on BG
    if (!isItemClick && event.button === 0) {
        isBoxSelecting.value = true;
        const svgP = getSvgPoint(event.clientX, event.clientY);
        selectionBoxStart.value = { x: svgP.x, y: svgP.y };
        selectionBoxCurrent.value = { x: svgP.x, y: svgP.y };
        
        // Clear previous selection if this is a new box select (unless holding Shift/Ctrl)
        if (!event.shiftKey && !event.ctrlKey && !event.metaKey) {
            selectedItemIds.value.clear();
        }
        return;
    }
};

const startDragItem = (event, item) => {
    // Prevent dragging items if Hand tool is active
    if (activeTool.value === 'hand') return;

    if (event.button !== 0) return; 
    event.stopPropagation();
    
    // Multi-Select Logic
    if (event.shiftKey || event.ctrlKey || event.metaKey) {
        // Toggle selection
        if (selectedItemIds.value.has(item.id)) {
            selectedItemIds.value.delete(item.id);
        } else {
            selectedItemIds.value.add(item.id);
        }
    } else {
        // Single select logic:
        // If the item is NOT part of the current selection, select ONLY it.
        // If it IS part of the selection, keep the selection (so we can drag the whole group).
        if (!selectedItemIds.value.has(item.id)) {
            selectedItemIds.value.clear();
            selectedItemIds.value.add(item.id);
        }
    }

    isDraggingItem.value = true;
    
    // Calculate offsets for ALL selected items
    const svgP = getSvgPoint(event.clientX, event.clientY);
    dragOffsets.value.clear();
    
    selectedItems.value.forEach(selItem => {
        dragOffsets.value.set(selItem.id, {
            dx: svgP.x - selItem.x,
            dy: svgP.y - selItem.y
        });
    });
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

const startResizeItem = (event, item) => {
    event.stopPropagation();
    isResizingItem.value = true;
    const svgP = getSvgPoint(event.clientX, event.clientY);
    
    // Capture initial sizes for all selected items
    initialSizes.value.clear();
    initialPositions.value.clear();
    selectedItems.value.forEach(it => {
        initialSizes.value.set(it.id, { w: it.width, h: it.height });
        initialPositions.value.set(it.id, { x: it.x, y: it.y });
    });

    lastMousePos.value = { x: svgP.x, y: svgP.y }; // This is our anchor point
};

const handleMouseMove = (event) => {
    // 1. Pan
    if (isPanning.value) {
        const dx = event.clientX - lastMousePos.value.x;
        const dy = event.clientY - lastMousePos.value.y;
        
        const svg = document.getElementById('gym-canvas');
        if (!svg) return;
        
        const scaleX = svgViewBox.value.w / svg.clientWidth;
        const scaleY = svgViewBox.value.h / svg.clientHeight;

        svgViewBox.value.x -= dx * scaleX;
        svgViewBox.value.y -= dy * scaleY;

        lastMousePos.value = { x: event.clientX, y: event.clientY };
    } 
    // 2. Box Select
    else if (isBoxSelecting.value) {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        selectionBoxCurrent.value = { x: svgP.x, y: svgP.y };
        
        const box = selectionBox.value;
        if (box) {
            const bx = box.x, by = box.y, bw = box.w, bh = box.h;
            const newSelection = new Set();
            
            placedItems.value.forEach(item => {
                const ix = item.x - item.width/2;
                const iy = item.y - item.height/2;
                const iw = item.width;
                const ih = item.height;
                
                if (ix < bx + bw && ix + iw > bx && iy < by + bh && iy + ih > by) {
                    newSelection.add(item.id);
                }
            });
            
            if (event.shiftKey || event.ctrlKey || event.metaKey) {
                 newSelection.forEach(id => selectedItemIds.value.add(id));
            } else {
                 selectedItemIds.value = newSelection;
            }
        }
    }
    // 3. Drag Items (Multi)
    else if (isDraggingItem.value && selectedItemIds.value.size > 0) {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        
        selectedItems.value.forEach(item => {
            const offset = dragOffsets.value.get(item.id);
            if (offset) {
                item.x = svgP.x - offset.dx;
                item.y = svgP.y - offset.dy;
            }
        });
    } 
    // 4. Rotate Items (Single - on the handle clicked)
    else if (isRotatingItem.value && primarySelectedItem.value) {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        const item = primarySelectedItem.value; 
        if (item) {
             const cx = item.x;
             const cy = item.y;
             const currentAngle = Math.atan2(svgP.y - cy, svgP.x - cx) * (180 / Math.PI);
             const delta = currentAngle - initialMouseAngle.value;
             item.rotation = initialRotation.value + delta;
        }
    }
    // 5. Resize Items (Multi)
    else if (isResizingItem.value && selectedItemIds.value.size > 0) {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        const dx = svgP.x - lastMousePos.value.x; // Delta from start of resize
        const dy = svgP.y - lastMousePos.value.y;
        
        // Apply delta to ALL selected items based on their individual starting sizes
        selectedItems.value.forEach(item => {
            const initial = initialSizes.value.get(item.id);
            if (initial) {
                let newW = initial.w + dx;
                let newH = initial.h + dy;
                
                // Constraints
                if (newW < 20) newW = 20;
                if (newH < 20) newH = 20;
                
                item.width = newW;
                item.height = newH;
            }
        });
    }
    
    // Wall Building Logic
    if (step.value === 1.5) {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        mousePos.value = {
            x: Math.round(svgP.x / snapGrid) * snapGrid,
            y: Math.round(svgP.y / snapGrid) * snapGrid
        };

        if (draggingWallId.value !== null && initialWallPos.value) {
            const wall = floorWalls.value.find(w => w.id === draggingWallId.value);
            const dx_raw = svgP.x - initialWallPos.value.mx;
            const dy_raw = svgP.y - initialWallPos.value.my;

            if (draggingEndPoint.value === 'start') {
                wall.x1 = initialWallPos.value.x1 + dx_raw;
                wall.y1 = initialWallPos.value.y1 + dy_raw;
            } else if (draggingEndPoint.value === 'end') {
                wall.x2 = initialWallPos.value.x2 + dx_raw;
                wall.y2 = initialWallPos.value.y2 + dy_raw;
            } else if (draggingEndPoint.value === 'both') {
                wall.x1 = initialWallPos.value.x1 + dx_raw;
                wall.y1 = initialWallPos.value.y1 + dy_raw;
                wall.x2 = initialWallPos.value.x2 + dx_raw;
                wall.y2 = initialWallPos.value.y2 + dy_raw;
            }

            // --- Magnet / Snapping Logic ---
            if (draggingEndPoint.value !== 'both' && draggingEndPoint.value !== null) {
                const snapDist = 20;
                for (const other of floorWalls.value) {
                    if (other.id === wall.id) continue;
                    const targets = [
                        { x: other.x1, y: other.y1 },
                        { x: other.x2, y: other.y2 }
                    ];
                    for (const p of targets) {
                         const curX = draggingEndPoint.value === 'start' ? wall.x1 : wall.x2;
                         const curY = draggingEndPoint.value === 'start' ? wall.y1 : wall.y2;
                         const dist = Math.sqrt(Math.pow(curX - p.x, 2) + Math.pow(curY - p.y, 2));
                         if (dist < snapDist) {
                             if (draggingEndPoint.value === 'start') { wall.x1 = p.x; wall.y1 = p.y; }
                             else { wall.x2 = p.x; wall.y2 = p.y; }
                             break;
                         }
                    }
                }
            }
        }
    }
};

const handleMouseUp = () => {
    isPanning.value = false;
    isBoxSelecting.value = false;
    isDraggingItem.value = false;
    isRotatingItem.value = false;
    isResizingItem.value = false;
    draggingVertexIndex.value = null;
    draggingWallId.value = null;
    draggingEndPoint.value = null;
};

const rotateSelected = () => {
    selectedItems.value.forEach(item => {
        item.rotation = (item.rotation || 0) + 90;
    });
};

const deleteSelected = () => {
    if (selectedItemIds.value.size > 0) {
        placedItems.value = placedItems.value.filter(i => !selectedItemIds.value.has(i.id));
        selectedItemIds.value.clear();
    }
};

const clearCanvas = () => {
    if(confirm("Clear all items?")) {
        placedItems.value = [];
        selectedItemIds.value.clear();
    }
};

const showSuccessModal = ref(false);
const showSaveSettingsModal = ref(false);
const imagePreview = ref(null);

const compressImage = (file, maxWidth = 1200, quality = 0.8) => {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                if (width > maxWidth) {
                    height = (height * maxWidth) / width;
                    width = maxWidth;
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    resolve(new File([blob], file.name, {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    }));
                }, 'image/jpeg', quality);
            };
        };
    });
};

const handleImageChange = async (e) => {
    const file = e.target.files[0];
    if (file) {
        // Show loading preview immediately
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);

        // Compress in background
        const compressedFile = await compressImage(file);
        form.image = compressedFile;
    }
};

const saveLayout = () => {
    if (!selectedRoom.value) return;
    
    // Direct save if editing an existing layout that already has a name
    if (props.layout && form.name) {
        submitSave();
        return;
    }
    
    showSaveSettingsModal.value = true;
};

const submitSave = () => {
    form.room_config = selectedRoom.value;
    form.items = placedItems.value;

    const options = {
        forceFormData: true, // Required for file uploads
        preserveScroll: true,
        onSuccess: () => {
            showSaveSettingsModal.value = false;
        },
        onError: (errors) => {
            console.error('Save failed:', errors);
            const errorMsg = Object.values(errors).flat().join('\n');
            alert('บันทึกไม่สำเร็จ:\n' + (errorMsg || 'เกิดข้อผิดพลาดในการเชื่อมต่อ'));
        }
    };

    if (props.layout) {
        form.post(route('gym-builder.post_update', props.layout.id), options);
    } else {
        form.post(route('gym-builder.store'), options);
    }
};

onMounted(() => {
    loadMyLayouts();
    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
    });

    // Initialize from props if editing
    if (props.layout) {
        // Deep clone to avoid mutating props and allow distinct reactivity
        selectedRoom.value = JSON.parse(JSON.stringify(props.layout.room_config));
        
        // Load and Repair items: Ensure dimensions match current equipment types
        const loadedItems = JSON.parse(JSON.stringify(props.layout.items));
        loadedItems.forEach(item => {
             // Find definition by type (fallback to name if type missing for legacy support)
             const def = equipmentTypes.find(e => e.id === item.type);
             if (def) {
                 item.width = def.w_m * pixelsPerMeter.value;
                 item.height = def.h_m * pixelsPerMeter.value;
             }
        });
        placedItems.value = loadedItems;
        
        // Populate form with existing data
        form.name = props.layout.name || '';
        form.location = props.layout.location || '';
        form.google_map_url = props.layout.google_map_url || '';
        form.image_url = props.layout.image_url || null;

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
                <button v-if="step !== 1" @click="step = 1" class="btn btn-sm btn-neutral gap-1 mr-2 hidden md:flex">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                     ย้อนกลับ
                </button>
                <Link v-else :href="route('dashboard')" class="btn btn-sm btn-neutral gap-1 mr-2 hidden md:flex">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                     Dashboard
                </Link>
                <div v-if="step === 2" class="flex gap-2">
                    <button class="btn btn-sm btn-ghost" @click="step = 1">Change Layout</button>
                    <button class="btn btn-sm btn-error btn-outline" @click="clearCanvas">Clear All</button>
                    <!-- Settings Button for Edit Mode -->
                    <button v-if="props.layout" class="btn btn-sm btn-square btn-ghost" @click="showSaveSettingsModal = true" title="Gym Settings">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </button>
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

        <div :class="['flex-1 flex flex-col select-none bg-base-300 w-full', step === 1 ? '' : 'h-[calc(100vh-64px)] overflow-hidden']">

        <!-- Phase 1: Room Selection -->
        <RoomSelector 
            v-if="step === 1"
            :roomShapes="roomShapes"
            :myLayouts="myLayouts"
            :currentUser="currentUser"
            @select="selectRoom"
            @delete="deleteTemplate"
        />

        <!-- Phase 1.5: Wall-Based Floor Designer -->
        <WallDesigner 
            v-if="step === 1.5"
            :floorWalls="floorWalls"
            :selectedWallId="selectedWallId"
            :svgViewBox="svgViewBox"
            :pixelsPerMeter="pixelsPerMeter"
            :activeTool="activeTool"
            @addWall="addWall"
            @deleteWall="deleteWall"
            @saveTemplate="saveTemplate"
            @finishWalls="finishWalls"
            @cancel="step = 1"
            @onMouseDown="handleCanvasMouseDown"
            @onMouseMove="handleMouseMove"
            @onWheel="handleWheel"
            @onEndPointMouseDown="handleEndPointMouseDown"
        />

        <!-- Phase 2: Builder Interface -->
        <EquipmentPlacer 
            v-if="step === 2"
            :equipmentList="equipmentList"
            :placedItems="placedItems"
            :selectedItemIds="selectedItemIds"
            :primarySelectedItem="primarySelectedItem"
            :svgViewBox="svgViewBox"
            :activeTool="activeTool"
            :isPanning="isPanning"
            :selectionBox="selectionBox"
            :pixelsPerMeter="pixelsPerMeter"
            :selectedRoom="selectedRoom"
            :computedRoomPoints="computedRoomPoints"
            @dragstart="handleDragStartFromSidebar"
            @drop="handleDropOnCanvas"
            @wheel="handleWheel"
            @mousedown="handleMouseDown"
            @mousemove="handleMouseMove"
            @mouseup="handleMouseUp"
            @startDragItem="startDragItem"
            @startResizeItem="startResizeItem"
            @startRotateItem="startRotateItem"
            @rotateSelected="rotateSelected"
            @copySelected="copySelected"
            @pasteSelected="pasteSelected"
            @deleteSelected="deleteSelected"
        />
        </div>

        <!-- Floating Navigation Tools -->
        <NavigationTools 
            v-if="step === 1.5 || step === 2"
            v-model:activeTool="activeTool"
            @zoomIn="handleWheel({ deltaY: -100, preventDefault: () => {}, clientX: window?.innerWidth/2 || 0, clientY: window?.innerHeight/2 || 0 })"
            @zoomOut="handleWheel({ deltaY: 100, preventDefault: () => {}, clientX: window?.innerWidth/2 || 0, clientY: window?.innerHeight/2 || 0 })"
            @resetView="svgViewBox = { x: 0, y: 0, w: 1000, h: 800 }"
        />
    </AppLayout>

    <!-- Save Settings Modal -->
    <div v-if="showSaveSettingsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md animate-fade-in p-4">
        <div class="bg-base-100 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-base-content/10 flex flex-col md:flex-row">
            <!-- Left Side: Image Preview/Upload -->
            <div class="w-full md:w-5/12 bg-base-200 p-6 flex flex-col items-center justify-center border-b md:border-b-0 md:border-r border-base-content/5">
                <div class="text-center mb-6">
                    <h3 class="text-xl font-bold">รูปยิมของคุณ</h3>
                    <p class="text-xs opacity-50 mt-1">อัพโหลดรูปภาพจริงเพื่อให้ลูกค้าจดจำได้ง่าย</p>
                </div>

                <div 
                    class="relative w-full aspect-square rounded-2xl border-2 border-dashed border-primary/30 flex flex-col items-center justify-center overflow-hidden bg-base-100 group cursor-pointer hover:border-primary transition-colors"
                    @click="$refs.fileInput.click()"
                >
                    <img v-if="imagePreview || form.image_url" :src="imagePreview || form.image_url" class="w-full h-full object-cover" />
                    <div v-else class="flex flex-col items-center text-primary/40 group-hover:text-primary transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider">Click to Upload</span>
                    </div>
                    <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleImageChange" />
                    
                    <!-- Overlay on hover -->
                    <div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity" v-if="imagePreview || form.image_url">
                         <span class="bg-base-100 px-3 py-1 rounded-full text-xs font-bold shadow-lg">Change Photo</span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Form Fields -->
            <div class="flex-1 p-8">
                <div class="flex justify-between items-start mb-6">
                    <h2 class="text-2xl font-black text-primary uppercase tracking-tight">บันทึกแบบแปลน</h2>
                    <button class="btn btn-ghost btn-circle btn-sm" @click="showSaveSettingsModal = false">✕</button>
                </div>

                <div class="space-y-4">
                    <div class="form-control w-full">
                        <label class="label pb-1"><span class="label-text font-bold text-xs uppercase opacity-60">ชื่อยิม / ชื่อแปลน</span></label>
                        <input v-model="form.name" type="text" placeholder="เช่น Fit Pung Studio" class="input input-bordered w-full focus:input-primary bg-base-200 border-none shadow-inner" :class="{'input-error': form.errors.name}" />
                        <label v-if="form.errors.name" class="label pb-0"><span class="label-text-alt text-error font-bold">{{ form.errors.name }}</span></label>
                    </div>

                    <div class="form-control w-full">
                        <label class="label pb-1"><span class="label-text font-bold text-xs uppercase opacity-60">สถานที่ตั้ง</span></label>
                        <textarea v-model="form.location" rows="2" placeholder="กรอกที่อยู่หรือชื่อตึก..." class="textarea textarea-bordered w-full focus:textarea-primary bg-base-200 border-none shadow-inner leading-tight" :class="{'textarea-error': form.errors.location}"></textarea>
                        <label v-if="form.errors.location" class="label pb-0"><span class="label-text-alt text-error font-bold">{{ form.errors.location }}</span></label>
                    </div>

                    <div class="form-control w-full">
                        <label class="label pb-1"><span class="label-text font-bold text-xs uppercase opacity-60">Google Map URL</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-primary/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </span>
                            <input v-model="form.google_map_url" type="text" placeholder="https://maps.google.com/..." class="input input-bordered w-full pl-10 focus:input-primary bg-base-200 border-none shadow-inner text-sm" :class="{'input-error': form.errors.google_map_url}" />
                        </div>
                        <label v-if="form.errors.google_map_url" class="label pb-0"><span class="label-text-alt text-error font-bold">{{ form.errors.google_map_url }}</span></label>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button class="btn btn-ghost flex-1 text-neutral" @click="showSaveSettingsModal = false">ยกเลิก</button>
                    <button class="btn btn-primary flex-[2] shadow-lg shadow-primary/30" @click="submitSave" :disabled="form.processing || !form.name">
                        <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                        <span v-else>ยืนยันการบันทึก</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm animate-fade-in">
        <div class="bg-base-100 p-8 rounded-2xl shadow-2xl text-center max-w-sm mx-4 transform transition-all scale-100 border border-base-content/10">
            <div class="mb-4 text-success flex justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold mb-2">บันทึกสำเร็จ!</h3>
            <p class="text-base-content/60 mb-6 py-2">ข้อมูลยิมและแบบแปลนของคุณถูกบันทึกแล้ว</p>
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
.cursor-crosshair { cursor: crosshair; }
.cursor-grab { cursor: grab; }
.cursor-grabbing { cursor: grabbing; }
</style>
