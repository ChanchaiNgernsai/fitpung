<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />
    
    <div class="min-h-screen flex bg-base-300">
        <!-- Left Side: Brand/Hero (Hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-neutral text-neutral-content items-center justify-center p-12">
            <!-- Background Image/Gradient -->
            <div class="absolute inset-0 z-0">
                <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-accent/20 mix-blend-overlay"></div>
                <!-- Abstract Gym shapes -->
                <svg class="absolute top-0 left-0 w-full h-full opacity-10" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 0 L50 100 L100 0 Z" fill="currentColor" />
                </svg>
            </div>
            
            <div class="relative z-10 text-center">
                <Link href="/" class="btn btn-ghost text-5xl font-black italic tracking-tighter p-0 hover:bg-transparent mb-6">
                    <span class="text-primary">FIT</span>PUNG
                </Link>
                <h2 class="text-4xl font-bold mb-4">Welcome Back</h2>
                <p class="text-xl opacity-70 max-w-md mx-auto">
                    Continue building your dream gym layout. Your legacy waits for no one.
                </p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-base-100">
            <div class="w-full max-w-md space-y-8">
                <!-- Mobile Logo -->
                <div class="lg:hidden text-center mb-10">
                    <Link href="/" class="btn btn-ghost text-3xl font-black italic tracking-tighter p-0 hover:bg-transparent">
                        <span class="text-primary">FIT</span>PUNG
                    </Link>
                </div>

                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-bold">Sign in to your account</h2>
                    <p class="mt-2 text-sm text-base-content/60">
                        Or <Link :href="route('register')" class="font-medium text-primary hover:text-primary-focus transition-colors">Register now</Link>
                    </p>
                </div>

                <div v-if="status" class="alert alert-success shadow-lg">
                    <span>{{ status }}</span>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-bold">Email address</span>
                        </label>
                        <input 
                            id="email" 
                            type="email" 
                            v-model="form.email" 
                            required 
                            autofocus 
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
                            class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2 text-error" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="cursor-pointer label justify-start gap-2">
                             <input type="checkbox" class="checkbox checkbox-primary checkbox-sm" v-model="form.remember" />
                             <span class="label-text">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-primary hover:text-primary-focus"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            class="btn btn-primary w-full shadow-lg" 
                            :class="{ 'loading': form.processing }"
                            :disabled="form.processing"
                        >
                            Log in
                        </button>
                    </div>
                </form>

                <div class="divider">OR</div>

                <div class="grid grid-cols-2 gap-4">
                    <button class="btn btn-outline gap-2">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                        Google
                    </button>
                    <button class="btn btn-outline gap-2">
                         <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" /></svg>
                        Facebook
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
