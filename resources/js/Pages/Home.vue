<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    gyms: Array, // Passed from route
});

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
    const padding = 50; 
    const w = maxX - minX + (padding * 2);
    const h = maxY - minY + (padding * 2);
    return `${minX - padding} ${minY - padding} ${w} ${h}`;
};
</script>

<template>
    <Head title="Home - FitPung" />

    <AppLayout>
        <!-- Hero Section -->
        <div class="hero min-h-[70vh] relative overflow-hidden">
            
            <div class="hero-content text-center py-20">
                <div class="max-w-4xl">
                    <!-- Badge -->
                    <div class="inline-block px-6 py-2 mb-8 border border-base-content/10 rounded-full bg-base-content/5 text-primary text-sm font-medium tracking-wide backdrop-blur-sm">
                        New Way to Plan Your Gym
                    </div>

                    <!-- Headline -->
                    <h1 class="mb-8 text-7xl md:text-8xl lg:text-9xl font-black uppercase italic tracking-tighter leading-none">
                        <span class="text-base-content drop-shadow-xl">Forge Your</span> <br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-teal-400 drop-shadow-2xl filter">Legacy</span>
                    </h1>

                    <!-- Description -->
                    <p class="mb-10 text-xl md:text-2xl opacity-60 font-light max-w-3xl mx-auto leading-relaxed">
                         Precision tools for gym owners and fitness enthusiasts. Visualize your perfect workout space with our interactive 3D map builder.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-6 justify-center">
                        <Link :href="route('dashboard')" class="btn btn-lg px-8 py-3 h-auto font-bold border-0 bg-gradient-to-r from-indigo-500 to-teal-400 text-white hover:scale-105 transition-transform shadow-lg shadow-indigo-500/30 rounded-lg uppercase tracking-wide">
                            New Gym
                        </Link>
                        <Link :href="route('register')" class="btn btn-outline btn-lg px-8 py-3 h-auto border-base-content/20 hover:bg-base-content hover:text-base-100 rounded-lg uppercase tracking-wide">
                            Open Gym Builder
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Gyms Section (Public) -->
        <section id="featured-gyms" class="py-24 bg-base-100 relative">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-black uppercase mb-4">Featured Gyms</h2>
                    <p class="opacity-60 max-w-xl mx-auto">Explore top-rated gym layouts designed by professionals. Click to view equipment details and workout plans.</p>
                </div>

                <div v-if="gyms && gyms.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <Link 
                        v-for="gym in gyms" 
                        :key="gym.id" 
                        :href="route('gyms.show', gym.id)"
                        class="card bg-base-200 shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group overflow-hidden border border-base-content/5"
                    >
                        <!-- Preview Box -->
                        <figure class="h-64 bg-neutral relative flex items-center justify-center p-6 overflow-hidden">
                             <svg 
                                :viewBox="getViewBox(gym.room_config.points)" 
                                class="w-full h-full opacity-80 group-hover:opacity-100 transition-opacity duration-500 scale-95 group-hover:scale-105"
                                preserveAspectRatio="xMidYMid meet"
                            >
                                <polygon 
                                    :points="gym.room_config.points" 
                                    fill="#374151" 
                                    stroke="currentColor" 
                                    class="text-primary/50" 
                                    stroke-width="5" 
                                />
                                <g 
                                    v-for="item in gym.items" 
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
                            <div class="absolute inset-0 bg-gradient-to-t from-base-200 to-transparent opacity-90"></div>
                            <div class="absolute bottom-4 left-4 right-4">
                                <h3 class="text-2xl font-bold text-white truncate">{{ gym.name }}</h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="badge badge-primary font-bold">Verified</span>
                                    <span v-if="gym.price" class="badge badge-ghost text-white">{{ gym.price }} THB/Day</span>
                                </div>
                            </div>
                        </figure>
                        
                        <div class="card-body">
                             <p class="line-clamp-2 text-sm opacity-70">{{ gym.description || 'Experience a world-class workout environment designed for performance.' }}</p>
                             <div class="card-actions justify-end mt-4">
                                <span class="btn btn-primary btn-sm btn-block">
                                    View Details
                                </span>
                             </div>
                        </div>
                    </Link>
                </div>
                
                <div v-else class="text-center py-20 opacity-50 bg-base-200 rounded-3xl">
                    <p class="text-2xl font-bold mb-4">No Verified Gyms Available Yet</p>
                    <p>Check back later or start building your own layout!</p>
                </div>

            </div>
        </section>


        
        <!-- Footer -->
        <footer class="footer footer-center p-10 bg-base-300 text-base-content rounded">
            <div class="grid grid-flow-col gap-4">
                <a class="link link-hover">About us</a>
                <a class="link link-hover">Contact</a>
                <a class="link link-hover">Jobs</a>
                <a class="link link-hover">Press kit</a>
            </div>
            <div>
                <p>Copyright © 2026 - All right reserved by FitPung Industries Ltd</p>
            </div>
        </footer>

    </AppLayout>
</template>
