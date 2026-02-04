<script setup>
import { ref } from 'vue';

const props = defineProps({
    floorWalls: Array,
    selectedWallIds: Object,
    svgViewBox: Object,
    pixelsPerMeter: Number,
    activeTool: String,
    traceImage: Object,
    selectionBox: Object
});

const emit = defineEmits([
    'addWall', 
    'deleteWall', 
    'saveTemplate', 
    'finishWalls', 
    'cancel',
    'update:selectedWallId',
    'onMouseDown',
    'onMouseMove',
    'onWheel',
    'onEndPointMouseDown',
    'onTraceImageUpload'
]);

const interactionMode = ref(null); // 'move' or 'resize'
const lastMousePos = ref({ x: 0, y: 0 });

const getSvgPoint = (clientX, clientY) => {
    const svg = document.getElementById('gym-canvas');
    if (!svg) return { x: 0, y: 0 };
    const pt = svg.createSVGPoint();
    pt.x = clientX;
    pt.y = clientY;
    const transformed = pt.matrixTransform(svg.getScreenCTM().inverse());
    return { x: transformed.x, y: transformed.y };
};

const handleTraceMouseDown = (type, event) => {
    if (props.traceImage.locked) return;
    event.stopPropagation();
    interactionMode.value = type;
    lastMousePos.value = { x: event.clientX, y: event.clientY };
    
    // Add temporary window listeners for smooth dragging outside canvas
    window.addEventListener('mousemove', handleTraceMouseMove);
    window.addEventListener('mouseup', handleTraceMouseUp);
};

const handleTraceMouseMove = (event) => {
    if (!interactionMode.value || props.traceImage.locked) return;
    
    const dx = (event.clientX - lastMousePos.value.x) * (props.svgViewBox.w / window.innerWidth);
    const dy = (event.clientY - lastMousePos.value.y) * (props.svgViewBox.h / window.innerHeight);
    
    if (interactionMode.value === 'move') {
        props.traceImage.x += dx;
        props.traceImage.y += dy;
    } else if (interactionMode.value === 'resize') {
        const scaleChange = dx / 200;
        props.traceImage.scale = Math.max(0.1, props.traceImage.scale + scaleChange);
    } else if (interactionMode.value === 'rotate') {
        const svgP = getSvgPoint(event.clientX, event.clientY);
        // Calculate center of image in SVG space
        const centerX = props.traceImage.x + (props.traceImage.width * props.traceImage.scale) / 2;
        const centerY = props.traceImage.y + (props.traceImage.height * props.traceImage.scale) / 2;
        const angle = Math.atan2(svgP.y - centerY, svgP.x - centerX) * (180 / Math.PI);
        // Offset so handle at top (270 deg) is considered 0 rotation starting point
        props.traceImage.rotation = angle + 90;
    }
    
    lastMousePos.value = { x: event.clientX, y: event.clientY };
};

const handleTraceMouseUp = () => {
    interactionMode.value = null;
    window.removeEventListener('mousemove', handleTraceMouseMove);
    window.removeEventListener('mouseup', handleTraceMouseUp);
};

const toggleLock = () => {
    props.traceImage.locked = !props.traceImage.locked;
};

const handleEndPointMouseDown = (wall, point, event) => {
    emit('onEndPointMouseDown', wall, point, event);
};

const handleCanvasMouseDown = (event) => {
    emit('onMouseDown', event);
};

const handleMouseMove = (event) => {
    emit('onMouseMove', event);
};

const handleWheel = (event) => {
    emit('onWheel', event);
};

const handleFileUpload = (event) => {
    emit('onTraceImageUpload', event);
};
</script>

<template>
    <div class="flex-1 flex flex-col md:flex-row overflow-hidden p-0 relative h-full">
        <aside class="w-full md:w-80 bg-base-100 border-r border-base-content/10 flex flex-col z-20 p-6 shadow-2xl shrink-0 overflow-y-auto h-full">
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary text-[10px] font-black tracking-wider uppercase">Structural Phase</span>
                    <div class="h-px flex-1 bg-base-content/5"></div>
                </div>
                <h2 class="text-3xl font-black text-base-content uppercase tracking-tighter">
                    Wall <span class="text-primary">Designer</span>
                </h2>
                <p class="text-[11px] text-base-content/50 font-medium leading-relaxed mt-1">
                    วางโครงสร้างและกำหนดพื้นที่การใช้งาน <br/>
                    เพื่อให้การจัดวางอุปกรณ์ของคุณออกมาสมบูรณ์แบบที่สุด
                </p>
            </div>
            
            <div class="flex flex-col gap-4">
                <button class="btn btn-primary gap-3 shadow-lg h-14" @click="emit('addWall')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    <span class="text-lg">เพิ่มกำแพงใหม่</span>
                </button>
                
                <button v-if="selectedWallIds.size > 0" class="btn btn-error btn-outline btn-sm animate-in fade-in zoom-in duration-200" @click="emit('deleteWall')">
                    ลบกำแพงที่เลือก
                </button>

                <!-- Background Image Blueprint -->
                <div class="card bg-base-200 p-4 border border-base-content/5 mt-2">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-xs font-bold uppercase opacity-60 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            รูปภาพต้นแบบ
                        </h3>
                        <button 
                            v-if="traceImage.url" 
                            @click="toggleLock"
                            :class="['btn btn-xs gap-1', traceImage.locked ? 'btn-error' : 'btn-success']"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path v-if="traceImage.locked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            {{ traceImage.locked ? 'ปลดล็อก' : 'ล็อกตำแหน่ง' }}
                        </button>
                    </div>
                    
                    <input type="file" ref="traceInput" class="hidden" accept="image/*" @change="handleFileUpload" />
                    <button v-if="!traceImage.url" class="btn btn-sm btn-outline btn-block" @click="$refs.traceInput.click()">
                        อัพโหลดรูปภาพ
                    </button>

                    <div v-if="traceImage.url" class="space-y-4">
                        <div class="flex gap-2">
                            <button class="btn btn-xs btn-outline flex-1" @click="$refs.traceInput.click()">เปลี่ยนรูป</button>
                            <button class="btn btn-xs btn-error btn-ghost" @click="traceImage.url = null">ลบรูป</button>
                        </div>
                        
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold opacity-50 uppercase flex justify-between">
                                Opacity <span>{{ Math.round(traceImage.opacity * 100) }}%</span>
                            </label>
                            <input type="range" min="0" max="1" step="0.01" v-model="traceImage.opacity" class="range range-xs range-primary" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold opacity-50 uppercase flex justify-between">
                                Rotate (หมุนรูป)
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    class="btn btn-sm btn-ghost bg-base-200 hover:bg-base-300 rounded-xl transition-all h-12 flex items-center justify-center"
                                    @click="traceImage.rotation = (Math.round((traceImage.rotation - 90) / 90) * 90)"
                                    title="หมุนทวนเข็มนาฬิกา"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2.5 2v6h6"/>
                                        <path d="M2.66 15.57a10 10 0 1 0 .57-8.38"/>
                                    </svg>
                                </button>
                                <button 
                                   class="btn btn-sm btn-ghost bg-base-200 hover:bg-base-300 rounded-xl transition-all h-12 flex items-center justify-center"
                                    @click="traceImage.rotation = (Math.round((traceImage.rotation + 90) / 90) * 90)"
                                    title="หมุนตามเข็มนาฬิกา"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.5 2v6h-6"/>
                                        <path d="M21.34 15.57a10 10 0 1 1-.57-8.38"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="!traceImage.locked" class="p-2 bg-info/10 rounded-lg text-[10px] text-info-content/70">
                            💡 <b>Tip:</b> คุณสามารถลากรูปภาพ, ลากจุดขวาล่างเพื่อย่อขยาย, หรือลากจุดด้านบนเพื่อหมุนรูปได้อิสระ
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 opacity-50 pointer-events-none">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Scale</label>
                                <div class="text-xs">{{ traceImage.scale.toFixed(2) }}x</div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Position</label>
                                <div class="text-xs">{{ Math.round(traceImage.x) }}, {{ Math.round(traceImage.y) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>
                
                <div class="space-y-3">
                    <button class="btn btn-ghost btn-block btn-sm opacity-50" @click="emit('cancel')">ยกเลิก</button>
                </div>
            </div>

            <!-- Quick Guide Script -->
            <div class="mt-auto pt-6 border-t border-base-content/5">
                <h4 class="text-[10px] font-bold opacity-30 uppercase tracking-[0.2em] mb-4">Quick Guide</h4>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-base-content/80">การปรับแก้รูปทรง</span>
                            <span class="text-[10px] text-base-content/40 leading-tight">ลากจุดสีขาวที่ปลายเพื่อยืดหด หรือหมุนกำแพงได้อิสระ</span>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-base-content/80">ย้ายตำแหน่งชิ้นงาน</span>
                            <span class="text-[10px] text-base-content/40 leading-tight">ลากที่เส้นกำแพงเพื่อย้ายตำแหน่งของทั้งกำแพง</span>
                        </div>
                    </div>

                    <div class="p-4 bg-base-200/50 rounded-2xl border border-base-content/5">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                            <span class="text-[10px] font-bold text-success/80 uppercase">Smart Snapping Active</span>
                        </div>
                        <p class="text-[10px] text-base-content/40 leading-tight">กำแพงจะเชื่อมต่อกันเองเมื่อลากจุดไปใกล้กัน</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 bg-base-300 relative overflow-hidden bg-[radial-gradient(currentColor_1px,transparent_1px)] [background-size:24px_24px] text-base-content/5">
            <svg 
                id="gym-canvas"
                class="w-full h-full cursor-crosshair select-none touch-none"
                :viewBox="`${svgViewBox.x} ${svgViewBox.y} ${svgViewBox.w} ${svgViewBox.h}`"
                @wheel="handleWheel"
                @mousedown="handleCanvasMouseDown"
                @mousemove="handleMouseMove"
            >
                 <!-- Background Tracing Image -->
                 <g v-if="traceImage.url" :transform="`translate(${traceImage.x}, ${traceImage.y}) scale(${traceImage.scale}) rotate(${traceImage.rotation}, ${traceImage.width/2}, ${traceImage.height/2})`" class="transition-none">
                    <image 
                        :href="traceImage.url"
                        x="0"
                        y="0"
                        :width="traceImage.width"
                        :height="traceImage.height"
                        :opacity="traceImage.opacity"
                        :class="[!traceImage.locked ? 'cursor-move active:opacity-40' : 'pointer-events-none']"
                        @mousedown="handleTraceMouseDown('move', $event)"
                    />
                    
                    <!-- Free Resize Handle (Bottom Right) -->
                    <rect 
                        v-if="!traceImage.locked"
                        :x="traceImage.width - 20" 
                        :y="traceImage.height - 20" 
                        width="40" height="40" 
                        fill="rgba(87, 13, 248, 0.4)" 
                        stroke="white" 
                        stroke-width="2"
                        class="cursor-se-resize hover:fill-primary"
                        @mousedown="handleTraceMouseDown('resize', $event)"
                    />



                    <!-- Rotate Handle (Top) -->
                    <g v-if="!traceImage.locked" :transform="`translate(${traceImage.width / 2}, -40)`">
                        <line x1="0" y1="0" x2="0" y2="40" stroke="rgba(87, 13, 248, 0.4)" stroke-width="2" />
                        <circle 
                            r="15" 
                            fill="white" 
                            stroke="rgba(87, 13, 248, 0.4)" 
                            stroke-width="3" 
                            class="cursor-ew-resize hover:fill-primary shadow-lg"
                            @mousedown="handleTraceMouseDown('rotate', $event)"
                        />
                    </g>
                 </g>

                 <!-- Render Walls -->
                 <g v-for="w in floorWalls" :key="w.id">
                     <!-- The Wall Line -->
                     <line 
                        :x1="w.x1" :y1="w.y1" :x2="w.x2" :y2="w.y2"
                        :stroke="selectedWallIds.has(w.id) ? '#f59e0b' : '#570df8'"
                        :stroke-width="selectedWallIds.has(w.id) ? 14 : 8"
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
                        fill="white" :stroke="selectedWallIds.has(w.id) ? '#f59e0b' : '#570df8'" stroke-width="4"
                        class="cursor-pointer shadow-sm"
                        @mousedown.stop="handleEndPointMouseDown(w, 'start', $event)"
                     />
                     <circle 
                        :cx="w.x2" :cy="w.y2" r="10" 
                        fill="white" :stroke="selectedWallIds.has(w.id) ? '#f59e0b' : '#570df8'" stroke-width="4"
                        class="cursor-pointer shadow-sm"
                        @mousedown.stop="handleEndPointMouseDown(w, 'end', $event)"
                     />
                 </g>

                 <!-- Box Selection UI -->
                 <rect 
                    v-if="selectionBox"
                    :x="selectionBox.x" :y="selectionBox.y" :width="selectionBox.w" :height="selectionBox.h"
                    fill="rgba(87, 13, 248, 0.1)"
                    stroke="#570df8"
                    stroke-width="1"
                    stroke-dasharray="4"
                    class="pointer-events-none"
                 />
            </svg>
        </main>
    </div>
</template>
