<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Home - FitPung" />

    <div class="min-h-screen bg-base-100 font-sans text-base-content selection:bg-primary selection:text-primary-content">
        <!-- Navbar -->
        <nav class="navbar fixed top-0 z-50 bg-base-100/80 backdrop-blur-md border-b border-base-content/10">
            <div class="container mx-auto">
                <div class="flex-1">
                    <a class="btn btn-ghost text-2xl font-black tracking-tighter">
                        <span class="text-primary">FIT</span>PUNG
                    </a>
                </div>
                <div class="flex-none gap-2">
                    <Link v-if="!$page.props.auth.user" :href="route('login')" class="btn btn-ghost btn-sm">Log in</Link>
                    <Link v-if="!$page.props.auth.user" :href="route('register')" class="btn btn-primary btn-sm">Get Started</Link>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-primary btn-sm">Dashboard</Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section with Video/Image Background Aesthetic -->
        <div class="hero min-h-screen relative overflow-hidden">
             <!-- Abstract Sporting Background -->
            <div class="absolute inset-0 bg-neutral -z-20">
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2670&auto=format&fit=crop')] bg-cover bg-center opacity-20 grayscale mix-blend-multiply"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-base-100 via-transparent to-base-100/50"></div>
                <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-primary/10 to-transparent"></div>
            </div>

            <div class="hero-content text-center text-neutral-content py-20">
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
                        <Link :href="route('gym-builder')" class="btn btn-primary btn-lg px-8 font-bold border-0 bg-gradient-to-r from-primary to-accent hover:scale-105 transition-transform shadow-[0_0_20px_rgba(var(--p),0.5)]">
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
                            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-4 text-primary text-3xl">
                                📐
                            </div>
                            <h3 class="card-title text-2xl font-bold uppercase">Smart Layout</h3>
                            <p>Drag, drop, and organize equipment with pixel-perfect precision on your gym floor plan.</p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="card bg-base-100 shadow-xl border-t-4 border-secondary hover:-translate-y-2 transition-transform duration-300">
                        <div class="card-body items-center text-center">
                            <div class="w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center mb-4 text-secondary text-3xl">
                                💪
                            </div>
                            <h3 class="card-title text-2xl font-bold uppercase">EquipmentDB</h3>
                            <p>Access a vast library of gym equipment models to accurately represent your setup.</p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="card bg-base-100 shadow-xl border-t-4 border-accent hover:-translate-y-2 transition-transform duration-300">
                        <div class="card-body items-center text-center">
                            <div class="w-16 h-16 rounded-full bg-accent/10 flex items-center justify-center mb-4 text-accent text-3xl">
                                🚀
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
                <Link :href="route('gym-builder')" class="btn btn-accent btn-wide btn-lg shadow-lg hover:shadow-accent/50">Build Now</Link>
             </div>
        </section>

        <!-- Footer -->
        <footer class="footer p-10 bg-base-300 text-base-content">
            <div>
                <span class="footer-title">Services</span> 
                <a class="link link-hover">Branding</a>
                <a class="link link-hover">Design</a>
                <a class="link link-hover">Marketing</a>
                <a class="link link-hover">Advertisement</a>
            </div> 
            <div>
                <span class="footer-title">Company</span> 
                <a class="link link-hover">About us</a>
                <a class="link link-hover">Contact</a>
                <a class="link link-hover">Jobs</a>
                <a class="link link-hover">Press kit</a>
            </div> 
            <div>
                <span class="footer-title">Legal</span> 
                <a class="link link-hover">Terms of use</a>
                <a class="link link-hover">Privacy policy</a>
                <a class="link link-hover">Cookie policy</a>
            </div>
        </footer>
    </div>
</template>
