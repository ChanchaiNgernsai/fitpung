<script setup>
const props = defineProps({
    equipmentList: Array,
    placedItems: Array,
    selectedItemIds: Object, // Set
    primarySelectedItem: Object,
    svgViewBox: Object,
    activeTool: String,
    isPanning: Boolean,
    selectionBox: Object,
    pixelsPerMeter: Number,
    selectedRoom: Object,
    computedRoomPoints: String
});

const emit = defineEmits([
    'dragstart', 
    'drop', 
    'wheel', 
    'mousedown', 
    'mousemove', 
    'mouseup',
    'startDragItem',
    'startResizeItem',
    'startRotateItem',
    'rotateSelected',
    'copySelected',
    'pasteSelected',
    'deleteSelected'
]);

const handleDragStartFromSidebar = (item, event) => {
    emit('dragstart', item, event);
};

const handleDropOnCanvas = (event) => {
    emit('drop', event);
};

const handleWheel = (event) => {
    emit('wheel', event);
};

const handleMouseDown = (event) => {
    emit('mousedown', event);
};

const handleMouseMove = (event) => {
    emit('mousemove', event);
};

const handleMouseUp = (event) => {
    emit('mouseup', event);
};
</script>

<template>
    <div class="flex-1 flex flex-col md:flex-row overflow-hidden p-0 relative h-full">
        <!-- Sidebar: Equipment -->
        <aside class="w-full md:w-72 bg-base-100 border-b md:border-b-0 md:border-r border-base-content/10 flex flex-col z-20 shadow-xl shrink-0 overflow-y-auto h-48 md:h-full">
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
                    class="card bg-base-200 hover:bg-neutral hover:text-neutral-content cursor-grab active:cursor-grabbing p-2 flex flex-col items-center transition-all hover:shadow-md border border-transparent hover:border-primary/20"
                >
                    <img :src="item.src" class="w-16 h-16 object-contain mb-2 pointer-events-none select-none" />
                    <span class="text-[10px] font-bold text-center uppercase">{{ item.name }}</span>
                    <span class="text-[8px] opacity-40">{{ item.w_m }}m x {{ item.h_m }}m</span>
                </div>
            </div>
            <!-- Mini controls info -->
            <div class="p-4 bg-base-200 text-xs opacity-60">
                <p>🖱️ Scroll to Zoom</p>
                <p>✋ Drag to Pan</p>
                <p>⌨️ Ctrl+C / Ctrl+V to Copy/Paste</p>
            </div>
        </aside>

        <!-- Main Canvas Area -->
        <main class="flex-1 bg-base-200 relative overflow-hidden text-base-content/20 bg-[radial-gradient(currentColor_1px,transparent_1px)] [background-size:20px_20px]">
            <svg 
                id="gym-canvas"
                :viewBox="`${svgViewBox.x} ${svgViewBox.y} ${svgViewBox.w} ${svgViewBox.h}`"
                :class="[
                    'w-full h-full touch-none',
                    activeTool === 'hand' 
                        ? (isPanning ? 'cursor-grabbing' : 'cursor-grab') 
                        : 'cursor-crosshair'
                ]"
                @dragover.prevent
                @dragenter.prevent
                @drop="handleDropOnCanvas"
                @wheel="handleWheel"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @mouseup="handleMouseUp"
                @mouseleave="handleMouseUp"
                @contextmenu.prevent
            >
                <defs>
                    <pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse">
                        <path d="M 50 0 L 0 0 0 50" fill="none" stroke="gray" stroke-width="0.5" opacity="0.2"/>
                    </pattern>
                </defs>

                <!-- Background Catcher -->
                <rect 
                    :x="svgViewBox.x - 5000" :y="svgViewBox.y - 5000" 
                    :width="svgViewBox.w + 10000" :height="svgViewBox.h + 10000" 
                    fill="white"
                    fill-opacity="0"
                    style="pointer-events: all; cursor: crosshair;"
                    @mousedown="handleMouseDown"
                    @dragover.prevent
                    @dragenter.prevent
                    @drop="handleDropOnCanvas"
                />

                <!-- Room Floor & Walls -->
                <g v-if="selectedRoom">
                    <polygon 
                        v-if="selectedRoom.points || computedRoomPoints"
                        :points="selectedRoom.points || computedRoomPoints" 
                        fill="#f8fafc" 
                        stroke="#1e293b" 
                        stroke-width="12"
                        stroke-linejoin="round"
                        class="drop-shadow-2xl"
                    />
                    <polygon 
                        v-if="selectedRoom.points || computedRoomPoints"
                        :points="selectedRoom.points || computedRoomPoints" 
                        fill="url(#grid)" 
                        pointer-events="none" 
                        opacity="0.6" 
                    />

                    <g v-if="selectedRoom.blocks">
                        <rect 
                            v-for="b in selectedRoom.blocks" 
                            :key="b.id"
                            :x="b.x" :y="b.y" :width="b.w" :height="b.h"
                            fill="#f8fafc" stroke="#1e293b" stroke-width="12"
                            class="drop-shadow-2xl"
                        />
                        <rect v-for="b in selectedRoom.blocks" :key="'grid-'+b.id" :x="b.x" :y="b.y" :width="b.w" :height="b.h" fill="url(#grid)" pointer-events="none" opacity="0.6" />
                    </g>
                </g>

                <!-- Placed Items -->
                <g 
                    v-for="item in placedItems" 
                    :key="item.id"
                    :transform="`translate(${item.x}, ${item.y})`"
                    class="cursor-move"
                    @mousedown="emit('startDragItem', $event, item)"
                >
                    <g :transform="`rotate(${item.rotation})`">
                         <image 
                            :href="item.src" 
                            :x="-item.width/2" 
                            :y="-item.height/2" 
                            :width="item.width" 
                            :height="item.height"
                            class="drop-shadow-md filter hover:brightness-110 transition-all"
                            draggable="false"
                        />
                        
                        <g v-if="selectedItemIds.has(item.id)">
                            <rect 
                                :x="-item.width/2 - 10" 
                                :y="-item.height/2 - 10" 
                                :width="item.width + 20" 
                                :height="item.height + 20" 
                                fill="none" 
                                stroke="#570df8" 
                                stroke-width="2" 
                                stroke-dasharray="6,4" 
                                class="animate-pulse"
                            />
                            
                            <g v-if="primarySelectedItem && primarySelectedItem.id === item.id">
                                <g 
                                    :transform="`translate(${item.width/2 + 10}, ${item.height/2 + 10})`"
                                    class="cursor-se-resize hover:scale-125 transition-transform"
                                    @mousedown.stop="emit('startResizeItem', $event, item)"
                                >
                                    <circle r="6" fill="#06b6d4" stroke="white" stroke-width="2" />
                                </g>
                                
                                <g 
                                    :transform="`translate(0, ${-item.height/2 - 30})`"
                                    class="cursor-ew-resize hover:scale-125 transition-transform"
                                    @mousedown.stop="emit('startRotateItem', $event, item)"
                                >
                                    <line x1="0" y1="0" x2="0" y2="20" stroke="#570df8" stroke-width="2" />
                                    <circle r="6" fill="#570df8" stroke="white" stroke-width="2" />
                                </g>
                            </g>
                        </g>
                    </g>

                    <foreignObject 
                        v-if="primarySelectedItem && primarySelectedItem.id === item.id"
                        :x="Math.max(item.width, item.height)/2 + 20" 
                        :y="-50" 
                        width="60" 
                        height="100"
                        class="overflow-visible"
                    >
                        <div class="flex flex-col gap-2" xmlns="http://www.w3.org/1999/xhtml" @mousedown.stop>
                            <button 
                                class="btn btn-circle btn-sm btn-info text-white shadow-xl hover:scale-110 transition-transform" 
                                @click.stop="emit('rotateSelected')"
                                title="Rotate 90°"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </button>
                            <button 
                                class="btn btn-circle btn-sm bg-blue-500 text-white shadow-xl hover:scale-110 transition-transform border-none" 
                                @click.stop="emit('copySelected'); emit('pasteSelected');"
                                title="Duplicate (Copy & Paste)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                            </button>
                            <button 
                                class="btn btn-circle btn-sm btn-error text-white shadow-xl hover:scale-110 transition-transform" 
                                @click.stop="emit('deleteSelected')"
                                title="Delete"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </foreignObject>
                </g>

                <!-- Selection Box -->
                <rect
                    v-if="selectionBox"
                    :x="selectionBox.x"
                    :y="selectionBox.y"
                    :width="selectionBox.w"
                    :height="selectionBox.h"
                    fill="rgba(87, 13, 248, 0.1)"
                    stroke="#570df8"
                    stroke-width="1"
                    stroke-dasharray="4,2"
                    class="pointer-events-none"
                />
                <!-- Scale Indicator -->
                <g :transform="`translate(${svgViewBox.x + 20}, ${svgViewBox.y + svgViewBox.h - 40})`" class="pointer-events-none opacity-50">
                    <line x1="0" y1="0" :x2="pixelsPerMeter" y2="0" stroke="currentColor" stroke-width="2" />
                    <line x1="0" y1="-5" x2="0" y2="5" stroke="currentColor" stroke-width="2" />
                    <line :x1="pixelsPerMeter" y1="-5" :x2="pixelsPerMeter" y2="5" stroke="currentColor" stroke-width="2" />
                    <text x="0" y="20" font-size="12" fill="currentColor" font-weight="bold">1.0m</text>
                </g>
            </svg>
        </main>
    </div>
</template>
