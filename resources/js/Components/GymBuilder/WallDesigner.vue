<script setup>
import { ref } from 'vue';

const props = defineProps({
    floorWalls: Array,
    selectedWallId: [Number, String],
    svgViewBox: Object,
    pixelsPerMeter: Number,
    activeTool: String
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
    'onEndPointMouseDown'
]);

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
</script>

<template>
    <div class="flex-1 flex flex-col md:flex-row overflow-hidden p-0 relative">
        <aside class="w-full md:w-80 bg-base-100 border-r border-base-content/10 flex flex-col z-20 p-6 shadow-2xl shrink-0 overflow-y-auto h-full">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-primary">Wall Designer</h2>
                <p class="text-xs opacity-50 uppercase tracking-widest mt-1">ประกอบโครงสร้างยิมของคุณ</p>
            </div>
            
            <div class="flex flex-col gap-4">
                <button class="btn btn-primary gap-3 shadow-lg h-14" @click="emit('addWall')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    <span class="text-lg">เพิ่มกำแพงใหม่</span>
                </button>
                
                <button v-if="selectedWallId" class="btn btn-error btn-outline btn-sm animate-in fade-in zoom-in duration-200" @click="emit('deleteWall')">
                    ลบกำแพงที่เลือก
                </button>

                <div class="divider"></div>
                
                <div class="space-y-3">
                    <button class="btn btn-outline btn-block" @click="emit('saveTemplate')">บันทึกเป็นแบบของฉัน</button>
                    <button class="btn btn-primary btn-block shadow-xl text-lg h-14" @click="emit('finishWalls')">เสร็จสิ้น และวางเครื่อง</button>
                    <button class="btn btn-ghost btn-block btn-sm opacity-50" @click="emit('cancel')">ยกเลิก</button>
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
                @wheel="handleWheel"
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
</template>
