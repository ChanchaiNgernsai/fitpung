<script setup>
import { computed } from 'vue';

const props = defineProps({
    roomShapes: Array,
    myLayouts: Array,
    currentUser: Object
});

const emit = defineEmits(['select', 'delete']);

const getCustomPoints = (walls) => {
    if (!walls || walls.length === 0) return "";
    return walls.map(w => `${w.x1},${w.y1} ${w.x2},${w.y2}`).join(' ');
};

const selectRoom = (room) => {
    emit('select', room);
};

const deleteTemplate = (id, event) => {
    emit('delete', id, event);
};
</script>

<template>
    <div class="flex-1 flex flex-col items-center p-8 animate-fade-in bg-neutral relative overflow-y-auto text-neutral-content min-h-full">
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
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full max-w-6xl px-4 relative z-10 pb-12">
            <div 
                v-for="shape in roomShapes" 
                :key="shape.id"
                @click="selectRoom(shape)"
                class="card bg-base-100 shadow-xl hover:shadow-2xl hover:scale-105 transition-all border-2 border-transparent hover:border-primary group cursor-pointer w-full aspect-square flex items-center justify-center relative overflow-hidden"
            >
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
            </div>
        </div>

        <!-- User Saved Layouts Section -->
        <div id="my-layouts-section" class="w-full max-w-6xl px-4 relative z-10 mt-8 mb-4 border-t border-white/10 pt-12">
            <div class="flex items-center gap-4 mb-8">
                <h2 class="text-3xl font-bold">แบบของฉัน</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-white/20 to-transparent"></div>
            </div>
            
            <div v-if="myLayouts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div 
                    v-for="shape in myLayouts" 
                    :key="shape.id"
                    @click="selectRoom(shape)"
                    class="card bg-base-100 shadow-xl hover:shadow-2xl hover:scale-105 transition-all border-2 border-transparent hover:border-primary group cursor-pointer w-full aspect-square flex items-center justify-center relative overflow-hidden"
                >
                    <button 
                        @click="deleteTemplate(shape.id, $event)"
                        class="absolute top-2 right-2 btn btn-xs btn-circle btn-error opacity-0 group-hover:opacity-100 transition-opacity z-20"
                    >
                        ✕
                    </button>
                    <svg viewBox="0 0 1000 800" class="w-full h-full p-8 transition-all duration-300">
                        <!-- Draw walls for custom saved layouts -->
                        <g v-if="shape.walls">
                            <polygon 
                                :points="getCustomPoints(shape.walls)" 
                                fill="currentColor" 
                                class="text-base-content/10 group-hover:text-primary/10 transition-colors"
                            />
                            <line 
                                v-for="w in shape.walls" 
                                :key="w.id"
                                :x1="w.x1" :y1="w.y1" :x2="w.x2" :y2="w.y2"
                                stroke="currentColor" stroke-width="24" stroke-linecap="round"
                                class="text-base-content/30 group-hover:text-primary transition-colors"
                            />
                        </g>
                        <polygon 
                            v-else-if="shape.points"
                            :points="shape.points" 
                            fill="currentColor" 
                            class="text-base-content/30 group-hover:text-primary transition-colors" 
                            stroke="currentColor" 
                            stroke-width="20"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <div class="absolute bottom-4 px-4 w-full text-center">
                        <div class="font-bold text-lg tracking-wider uppercase truncate">{{ shape.name }}</div>
                        <div class="text-[10px] opacity-60 mt-0.5 text-primary">
                            {{ shape.creatorEmail || currentUser?.email }}
                        </div>
                    </div>
                </div>
            </div>
            <!-- Empty State -->
            <div v-else class="text-center py-12 bg-base-100/50 rounded-3xl border-2 border-dashed border-white/5">
                <p class="text-white/30 italic">ยังไม่มีแบบแปลนที่บันทึกไว้ในบัญชีนี้</p>
            </div>
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
