<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const items = ref([
    { id: 1, name: 'Treadmill', icon: '🏃' },
    { id: 2, name: 'Bench Press', icon: '🏋️' },
    { id: 3, name: 'Dumbbell Set', icon: '💪' },
    { id: 4, name: 'Yoga Mat', icon: '🧘' },
    { id: 5, name: 'Cable Machine', icon: '🏗️' },
]);

const handleSave = () => {
    // Placeholder for save logic
    alert('Layout Saved! (Simulation)');
};
</script>

<template>
    <Head title="Gym Builder" />

    <div class="drawer h-screen w-full bg-base-300 font-sans text-base-content overflow-hidden">
        <input id="my-drawer-3" type="checkbox" class="drawer-toggle" /> 
        <div class="drawer-content flex flex-col h-full">
            <!-- Navbar -->
            <div class="w-full navbar bg-base-100/80 backdrop-blur-md sticky top-0 z-30 shadow-sm border-b border-base-content/5">
                <div class="flex-none lg:hidden">
                    <label for="my-drawer-3" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </label>
                </div> 
                <div class="flex-1 px-2 mx-2 text-xl font-black tracking-tight"><span class="text-primary">Fit</span>Pung Builder</div>
                <div class="flex-none hidden lg:block">
                    <ul class="menu menu-horizontal">
                        <li><a>Home</a></li>
                        <li><a>Templates</a></li>
                        <li><a>Settings</a></li>
                    </ul>
                </div>
                <div class="flex-none gap-2">
                     <button class="btn btn-ghost btn-circle">
                        <div class="indicator">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            <span class="badge badge-xs badge-primary indicator-item"></span>
                        </div>
                     </button>
                     <button class="btn btn-primary btn-sm gap-2 shadow-lg shadow-primary/30" @click="handleSave">
                        Save Layout
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                     </button>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="flex-1 flex overflow-hidden relative">
                <!-- Map Container -->
                <main class="flex-1 bg-neutral relative grid place-items-center overflow-auto p-4 bg-[radial-gradient(#ffffff05_1px,transparent_1px)] [background-size:16px_16px]">
                     <div class="card bg-base-100 shadow-2xl overflow-hidden border border-base-content/10 relative">
                        <!-- Use object to load SVG interactively -->
                        <object data="/images/fitpung_interactive.svg" type="image/svg+xml" class="w-full max-w-[1200px] max-h-[80vh] object-contain cursor-crosshair select-none"></object>
                        
                        <!-- Overlay instructions if needed -->
                        <div class="absolute top-4 left-4 badge badge-neutral opacity-80 pointer-events-none">Interactive Gym Map</div>
                     </div>
                     
                     <!-- Floating Tools / Overlay -->
                     <div class="absolute bottom-8 right-8 flex gap-2">
                        <button class="btn btn-circle btn-neutral glass hover:scale-110 active:scale-95 transition-transform" title="Zoom In">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        </button>
                        <button class="btn btn-circle btn-neutral glass hover:scale-110 active:scale-95 transition-transform" title="Zoom Out">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                        </button>
                     </div>
                </main>
                
                <!-- Sidebar (Desktop) -->
                <aside class="w-80 bg-base-100/95 backdrop-blur border-l border-base-content/5 p-4 flex flex-col gap-6 hidden lg:flex z-20 shadow-xl">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                             <h2 class="text-lg font-bold">Equipment</h2>
                             <span class="badge badge-sm badge-outline">{{ items.length }} Items</span>
                        </div>
                        <div class="form-control w-full max-w-xs mb-4">
                          <input type="text" placeholder="Search items..." class="input input-bordered input-sm w-full" />
                        </div>

                        <p class="text-xs text-base-content/60 mb-4 uppercase tracking-wider font-semibold">Available Units</p>
                        <div class="grid grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto pr-1">
                            <div v-for="item in items" :key="item.id" 
                                 draggable="true"
                                 class="card bg-base-200 hover:bg-primary hover:text-primary-content transition-all cursor-move p-4 flex flex-col items-center gap-2 group border border-transparent hover:scale-105 active:scale-95 shadow-sm select-none">
                                <span class="text-3xl filter drop-shadow-md group-hover:drop-shadow-none transition-all">{{ item.icon }}</span>
                                <span class="text-xs font-bold text-center leading-tight">{{ item.name }}</span>
                            </div>
                         </div>
                    </div>
                </aside>
            </div>
        </div> 
        
        <!-- Drawer Side (Mobile) -->
        <div class="drawer-side z-40">
            <label for="my-drawer-3" class="drawer-overlay"></label> 
            <ul class="menu p-4 w-80 min-h-full bg-base-200 text-base-content">
                <li class="menu-title">Gym Builder</li>
                <li><a>Select Area</a></li>
                <li>
                    <details open>
                        <summary>Equipment</summary>
                        <ul>
                             <li v-for="item in items" :key="item.id"><a>{{ item.icon }} {{ item.name }}</a></li>
                        </ul>
                    </details>
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent; 
}
::-webkit-scrollbar-thumb {
    background: color-mix(in srgb, currentColor 20%, transparent); 
    border-radius: 99px;
}
::-webkit-scrollbar-thumb:hover {
    background: color-mix(in srgb, currentColor 40%, transparent); 
}
</style>
