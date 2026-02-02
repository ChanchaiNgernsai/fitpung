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
                        class="group bg-white rounded-[3rem] border border-gray-100 overflow-hidden flex flex-col shadow-[0_4px_20px_rgb(0,0,0,0.03)] hover:shadow-[0_40px_80px_rgba(0,0,0,0.12)] transition-all duration-700 hover:-translate-y-4"
                    >
                        <!-- Gym Exterior Image -->
                        <figure class="h-64 relative overflow-hidden">
                            <template v-if="gym.image_path">
                                <img :src="`/storage/${gym.image_path}`" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" />
                            </template>
                            <div v-else class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <!-- Subtle Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent opacity-60"></div>
                            
                            <!-- Verified Badge -->
                            <div class="absolute top-6 right-6">
                                <div class="badge bg-white/90 backdrop-blur-md border-none text-[#4338ca] font-black py-4 px-5 rounded-2xl text-[10px] uppercase italic tracking-widest shadow-xl">Verified</div>
                            </div>
                        </figure>

                        <div class="p-10 pt-4 space-y-6 flex-1 flex flex-col">
                            <div class="space-y-2">
                                <h3 class="text-3xl font-black italic uppercase tracking-tighter text-gray-900 group-hover:text-[#4338ca] transition-colors leading-none">{{ gym.name }}</h3>
                                <div class="flex items-center gap-2 text-[#4338ca] opacity-60">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span class="text-[11px] font-black uppercase tracking-widest">{{ gym.location || 'Professional Hub' }}</span>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-50 flex items-center justify-between">
                                <div class="space-y-1">
                                    <div class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-400">Status</div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                        <span class="text-xs font-bold text-gray-700">Open Today</span>
                                    </div>
                                </div>
                                <div class="text-right space-y-1">
                                    <div class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-400">Hours</div>
                                    <div class="text-xs font-bold text-gray-900 italic">08:00 - 22:00</div>
                                </div>
                            </div>

                            <div class="mt-auto pt-8">
                                <span class="btn bg-[#4338ca] hover:bg-[#3730a3] text-white btn-block h-16 rounded-[1.5rem] font-black italic uppercase tracking-[0.15em] border-none shadow-2xl shadow-indigo-500/20 group-hover:scale-[1.02] transition-all text-sm">
                                    View Workspace
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
