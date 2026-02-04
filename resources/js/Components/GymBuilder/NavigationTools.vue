<script setup>
const props = defineProps({
    activeTool: String,
});

const emit = defineEmits(['update:activeTool', 'zoomIn', 'zoomOut', 'resetView']);

const setActiveTool = (tool) => {
    emit('update:activeTool', tool);
};
</script>

<template>
    <div class="fixed bottom-10 right-10 flex flex-col gap-3 z-50 animate-in slide-in-from-right-10 duration-500">
        <!-- Tool Selection (Select vs Hand) -->
        <div class="flex flex-col gap-1 bg-base-100 p-1.5 rounded-2xl shadow-2xl border border-primary/20">
            <button 
                @click="setActiveTool('select')"
                class="btn btn-circle btn-sm transition-all duration-300"
                :class="activeTool === 'select' ? 'btn-primary shadow-lg scale-110' : 'bg-slate-700'"
                title="เครื่องมือเลือก (Select Tool)"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                </svg>
            </button>
            <button 
                @click="setActiveTool('hand')"
                class="btn btn-circle btn-sm transition-all duration-300"
                :class="activeTool === 'hand' ? 'btn-primary shadow-lg scale-110' : 'bg-slate-700'"
                title="เครื่องมือรูปมือ (Hand Tool - เลื่อนหน้าจอ)"
            >
                <span class="text-base select-none" style="filter: brightness(0) invert(1)">✋</span>
            </button>
        </div>

        <!-- Zoom Controls -->
        <div class="flex flex-col gap-1 bg-base-100 p-1.5 rounded-2xl shadow-2xl border border-primary/20">
            <button 
                @click="emit('zoomIn')" 
                class="btn btn-circle btn-sm btn-primary text-white shadow-lg hover:scale-110 transition-all duration-300"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            </button>
            <button 
                @click="emit('zoomOut')" 
                class="btn btn-circle btn-sm btn-warning text-white shadow-lg hover:scale-110 transition-all duration-300"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
            </button>
        </div>

        <!-- Reset View Group -->
        <div class="flex flex-col gap-1 bg-base-100 p-1.5 rounded-2xl shadow-2xl border border-primary/20">
            <button 
                @click="emit('resetView')"
                class="btn btn-circle btn-sm bg-slate-800 hover:bg-slate-700 text-white border-none transition-all duration-300 hover:scale-110"
                title="Reset View"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
            </button>
        </div>
    </div>
</template>
