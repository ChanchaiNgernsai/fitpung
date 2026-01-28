<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen flex bg-base-300">
        <!-- Left Side: Brand/Hero (Hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-neutral text-neutral-content items-center justify-center p-12">
            <!-- Background Image/Gradient -->
            <div class="absolute inset-0 z-0">
                 <div class="absolute inset-0 bg-gradient-to-tl from-secondary/20 to-primary/20 mix-blend-overlay"></div>
                <!-- Abstract Gym shapes -->
                <svg class="absolute bottom-0 right-0 w-full h-full opacity-10" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <circle cx="100" cy="100" r="50" fill="currentColor" />
                </svg>
            </div>
            
            <div class="relative z-10 text-center">
                <Link href="/" class="btn btn-ghost text-5xl font-black italic tracking-tighter p-0 hover:bg-transparent mb-6">
                    <span class="text-primary">FIT</span>PUNG
                </Link>
                <h2 class="text-4xl font-bold mb-4">Start Your Journey</h2>
                <p class="text-xl opacity-70 max-w-md mx-auto">
                    Design the perfect workout space. It all starts with a single step.
                </p>
                <!-- Testimonial or Stat -->
                <div class="mt-8 p-4 bg-base-100/10 backdrop-blur-sm rounded-lg border border-white/10 max-w-sm mx-auto">
                    <p class="italic">"The easiest way to plan my home gym. Absolutely love the interface!"</p>
                    <div class="mt-2 font-bold text-primary">- Sarah K., Gym Owner</div>
                </div>
            </div>
        </div>

        <!-- Right Side: Register Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-base-100">
            <div class="w-full max-w-md space-y-8">
                <!-- Mobile Logo -->
                <div class="lg:hidden text-center mb-10">
                    <Link href="/" class="btn btn-ghost text-3xl font-black italic tracking-tighter p-0 hover:bg-transparent">
                        <span class="text-primary">FIT</span>PUNG
                    </Link>
                </div>

                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-bold">Create an Account</h2>
                    <p class="mt-2 text-sm text-base-content/60">
                        Already have an account? <Link :href="route('login')" class="font-medium text-primary hover:text-primary-focus transition-colors">Log in here</Link>
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-bold">Full Name</span>
                        </label>
                        <input 
                            id="name" 
                            type="text" 
                            v-model="form.name" 
                            required 
                            autofocus 
                            autocomplete="name"
                            class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                            placeholder="John Doe"
                        />
                         <InputError class="mt-2 text-error" :message="form.errors.name" />
                    </div>

                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-bold">Email address</span>
                        </label>
                        <input 
                            id="email" 
                            type="email" 
                            v-model="form.email" 
                            required 
                            autocomplete="username"
                            class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                            placeholder="you@example.com"
                        />
                         <InputError class="mt-2 text-error" :message="form.errors.email" />
                    </div>

                    <div class="form-control w-full">
                         <label class="label">
                            <span class="label-text font-bold">Password</span>
                        </label>
                        <input 
                            id="password" 
                            type="password" 
                            v-model="form.password" 
                            required 
                            autocomplete="new-password"
                            class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2 text-error" :message="form.errors.password" />
                    </div>

                    <div class="form-control w-full">
                         <label class="label">
                            <span class="label-text font-bold">Confirm Password</span>
                        </label>
                        <input 
                            id="password_confirmation" 
                            type="password" 
                            v-model="form.password_confirmation" 
                            required 
                            autocomplete="new-password"
                            class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2 text-error" :message="form.errors.password_confirmation" />
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit" 
                            class="btn btn-primary w-full shadow-lg" 
                            :class="{ 'loading': form.processing }"
                            :disabled="form.processing"
                        >
                            Create Account
                        </button>
                    </div>
                </form>

                 <div class="text-xs text-center text-base-content/40 mt-4">
                    By registering, you agree to our <a href="#" class="underline hover:text-base-content">Terms of Service</a> and <a href="#" class="underline hover:text-base-content">Privacy Policy</a>.
                </div>
            </div>
        </div>
    </div>
</template>
