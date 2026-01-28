<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    layouts: Array
});

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
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    My Gym Designs
                </h2>
                <Link :href="route('gym-builder.create')" class="btn btn-primary btn-sm">
                    + New Design
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <div v-if="layouts.length === 0" class="text-center py-20 opacity-50">
                    <p class="text-2xl font-bold mb-4">No Designs Yet</p>
                    <Link :href="route('gym-builder.create')" class="btn btn-outline">Start Building</Link>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="layout in layouts" :key="layout.id" class="card bg-base-100 shadow-xl border border-white/10 hover:border-primary transition-all group overflow-hidden">
                        
                        <!-- Dynamic SVG Preview -->
                        <div class="h-48 bg-neutral relative overflow-hidden flex items-center justify-center p-4">
                            <svg 
                                :viewBox="getViewBox(layout.room_config.points)" 
                                class="w-full h-full opacity-90 group-hover:opacity-100 transition-opacity"
                                preserveAspectRatio="xMidYMid meet"
                            >
                                <defs>
                                    <pattern id="grid-preview" width="40" height="40" patternUnits="userSpaceOnUse">
                                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="gray" stroke-width="0.5" opacity="0.2"/>
                                    </pattern>
                                </defs>

                                <!-- Room Floor -->
                                <polygon 
                                    :points="layout.room_config.points" 
                                    fill="#f8fafc" 
                                    stroke="#333" 
                                    stroke-width="5" 
                                />
                                <!-- Grid overlay inside room -->
                                <polygon :points="layout.room_config.points" fill="url(#grid-preview)" opacity="0.6" />
                                
                                <!-- Equipment Images -->
                                <g 
                                    v-for="item in layout.items" 
                                    :key="item.id"
                                    :transform="`translate(${item.x}, ${item.y}) rotate(${item.rotation})`"
                                >
                                    <image 
                                        :href="item.src" 
                                        :x="-item.width/2" 
                                        :y="-item.height/2" 
                                        :width="item.width" 
                                        :height="item.height"
                                        class="drop-shadow-sm filter grayscale contrast-125"
                                    />
                                </g>
                            </svg>
                            <!-- Overlay Gradient -->
                            <div class="absolute inset-0 bg-gradient-to-t from-base-100/50 to-transparent pointer-events-none"></div>
                        </div>

                        <div class="card-body items-center text-center -mt-6 z-10">
                            <h2 class="card-title">{{ layout.name }}</h2>
                            <p class="text-xs opacity-50">Updated: {{ new Date(layout.updated_at).toLocaleDateString() }}</p>
                            <div class="card-actions mt-4">
                                <Link :href="route('gym-builder.edit', layout.id)" class="btn btn-primary btn-sm">Edit</Link>
                                <button @click="deleteLayout(layout.id)" class="btn btn-ghost btn-xs text-error">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>


