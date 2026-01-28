<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    // Check local storage or system preference
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
    }
});
</script>

<template>
    <Head title="Home - FitPung" />

    <div class="min-h-screen bg-base-100 font-sans text-base-content selection:bg-primary selection:text-primary-content transition-colors duration-300">
        <!-- Navbar -->
        <nav class="navbar fixed top-0 z-50 bg-base-100/80 backdrop-blur-md border-b border-base-content/10 transition-colors duration-300">
            <div class="container mx-auto">
                <div class="flex-1">
                    <a class="btn btn-ghost text-2xl font-black tracking-tighter">
                        <span class="text-primary">FIT</span>PUNG
                    </a>
                </div>
                <div class="flex-none gap-2 items-center">
                    <!-- Theme Toggle -->
                    <button @click="toggleTheme" class="btn btn-circle btn-ghost btn-sm" title="Toggle Theme">
                        <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
</svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
</svg>
                    </button>

                    <Link v-if="!$page.props.auth.user" :href="route('login')" class="btn btn-ghost btn-sm">Log in</Link>
                    <Link v-if="!$page.props.auth.user" :href="route('register')" class="btn btn-primary btn-sm">Get Started</Link>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-primary btn-sm">Dashboard</Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section with Video/Image Background Aesthetic -->
        <div class="hero min-h-screen relative overflow-hidden">
             <!-- Abstract Sporting Background -->
            <div class="absolute inset-0 bg-base-200 dark:bg-neutral -z-20 transition-colors duration-300">
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2670&auto=format&fit=crop')] bg-cover bg-center opacity-20 grayscale mix-blend-multiply"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-base-100 via-transparent to-base-100/50"></div>
                <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-primary/10 to-transparent"></div>
            </div>

            <div class="hero-content text-center text-base-content py-20">
                <div class="max-w-3xl">
                    <div class="badge badge-primary badge-outline mb-6 animate-pulse">New Way to Plan Your Gym</div>
                    <h1 class="mb-5 text-6xl lg:text-8xl font-black uppercase italic tracking-tighter leading-none">
                        Forge Your <br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent">Legacy</span>
                    </h1>
                    <p class="mb-8 text-xl lg:text-2xl font-light opacity-90 max-w-2xl mx-auto">
                        Precision tools for gym owners and fitness enthusiasts. Visualize your perfect workout space with our interactive 3D map builder.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link :href="route('gym-builder.create')" class="btn btn-primary btn-lg px-8 font-bold border-0 bg-gradient-to-r from-primary to-accent hover:scale-105 transition-transform shadow-[0_0_20px_rgba(var(--p),0.5)]">
                            Launch Gym Builder
                        </Link>
                        <Link :href="route('register')" class="btn btn-outline btn-lg px-8 text-white hover:bg-white hover:text-black">
                            Join Community
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <section class="py-24 bg-base-200 relative">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-black uppercase mb-4">Why FitPung?</h2>
                    <div class="w-24 h-1 bg-primary mx-auto"></div>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="card bg-base-100 shadow-xl border-t-4 border-primary hover:-translate-y-2 transition-transform duration-300">
                        <div class="card-body items-center text-center">
                            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-4 text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                </svg>
                            </div>
                            <h3 class="card-title text-2xl font-bold uppercase">Smart Layout</h3>
                            <p>Drag, drop, and organize equipment with pixel-perfect precision on your gym floor plan.</p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="card bg-base-100 shadow-xl border-t-4 border-secondary hover:-translate-y-2 transition-transform duration-300">
                        <div class="card-body items-center text-center">
                            <div class="w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center mb-4 text-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                            </div>
                            <h3 class="card-title text-2xl font-bold uppercase">EquipmentDB</h3>
                            <p>Access a vast library of gym equipment models to accurately represent your setup.</p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="card bg-base-100 shadow-xl border-t-4 border-accent hover:-translate-y-2 transition-transform duration-300">
                        <div class="card-body items-center text-center">
                            <div class="w-16 h-16 rounded-full bg-accent/10 flex items-center justify-center mb-4 text-accent">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <h3 class="card-title text-2xl font-bold uppercase">3D Visualization</h3>
                            <p>Get a realistic top-down view of your gym layout to maximize space and flow.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="py-20 bg-neutral text-neutral-content relative overflow-hidden">
             <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-primary/20 via-neutral to-neutral"></div>
             <div class="container mx-auto px-4 text-center relative z-10">
                <h2 class="text-5xl font-black italic mb-8">READY TO SWEAT?</h2>
                <p class="text-xl mb-8 max-w-2xl mx-auto opacity-80">Start designing your professional gym layout today. No credit card required for the demo.</p>
                <Link :href="route('gym-builder.create')" class="btn btn-accent btn-wide btn-lg shadow-lg hover:shadow-accent/50">Build Now</Link>
             </div>
        </section>

        <!-- Footer -->
        <!-- Footer -->
        <footer class="bg-base-300 text-base-content pt-16 pb-8 mt-auto">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                    <!-- Brand -->
                    <div class="md:col-span-1">
                        <a class="text-3xl font-black tracking-tighter block mb-4">
                            <span class="text-primary">FIT</span>PUNG
                        </a>
                        <p class="opacity-70 mb-6">
                            Empowering gym owners with professional precision tools. Build your dream gym today.
                        </p>
                    </div>

                    <!-- Links Sections -->
                    <div class="flex flex-col gap-2">
                        <h6 class="font-bold uppercase tracking-wider opacity-100 text-primary mb-2">Services</h6>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-primary transition-colors">Gym Layout</a>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-primary transition-colors">3D Rendering</a>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-primary transition-colors">Consultation</a>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-primary transition-colors">Enterprise</a>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <h6 class="font-bold uppercase tracking-wider opacity-100 text-secondary mb-2">Company</h6>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-secondary transition-colors">About Us</a>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-secondary transition-colors">Contact</a>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-secondary transition-colors">Careers</a>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-secondary transition-colors">Press</a>
                    </div>

                    <div class="flex flex-col gap-2">
                        <h6 class="font-bold uppercase tracking-wider opacity-100 text-accent mb-2">Legal</h6>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-accent transition-colors">Terms of Use</a>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-accent transition-colors">Privacy Policy</a>
                        <a class="link link-hover opacity-70 hover:opacity-100 hover:text-accent transition-colors">Cookie Policy</a>
                    </div>
                </div>

                <div class="border-t border-base-content/10 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm opacity-60">
                    <p>© 2026 FitPung Inc. All rights reserved.</p>
                    <div class="flex gap-6">
                        <a class="hover:text-primary transition-colors cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </a>
                        <a class="hover:text-primary transition-colors cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                        </a>
                        <a class="hover:text-primary transition-colors cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
