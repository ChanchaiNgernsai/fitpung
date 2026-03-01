<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';

const { props } = usePage();
const user = computed(() => props.auth.user);

const form = useForm({
    specialty: '',
    bio: '',
    experience_years: 0,
    price_per_session: 0,
});

const submit = () => {
    form.post(route('trainer.store'));
};
</script>

<template>
    <Head title="Apply as Trainer" />

    <div class="min-h-screen flex bg-base-300">
        <!-- Left Side: Brand/Hero (Hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-neutral text-neutral-content items-center justify-center p-12">
            <!-- Background Image/Gradient -->
            <div class="absolute inset-0 z-0 text-orange-500">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-500/20 to-primary/20 mix-blend-overlay"></div>
                <!-- Abstract Gym shapes -->
                <svg class="absolute top-0 right-0 w-full h-full opacity-10" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M100 0 L50 100 L0 0 Z" fill="currentColor" />
                </svg>
            </div>
            
            <div class="relative z-10 text-center">
                <Link href="/" class="btn btn-ghost text-5xl font-black italic tracking-tighter p-0 hover:bg-transparent mb-6">
                    <span class="text-primary">FIT</span>PUNG
                </Link>
                <h2 class="text-4xl font-bold mb-4 uppercase italic">Forge New Legacies</h2>
                <p class="text-xl opacity-70 max-w-md mx-auto leading-relaxed">
                    Join our elite network of professional trainers. Your expertise is the tool others need to build their future.
                </p>
                
                <div class="mt-10 p-6 bg-base-100/10 backdrop-blur-md rounded-[2rem] border border-white/10 max-w-sm mx-auto transition-all hover:bg-base-100/20">
                    <p class="italic text-lg">"FitPung gave me the platform to reach athletes I could never find anywhere else."</p>
                    <div class="mt-4 font-black uppercase text-primary tracking-widest text-xs">- Coach Mike, Master Trainer</div>
                </div>
            </div>
        </div>

        <!-- Right Side: Registration Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-base-100 overflow-y-auto">
            <div class="w-full max-w-md space-y-8 my-12">
                <!-- Mobile Logo -->
                <div class="lg:hidden text-center mb-10">
                    <Link href="/" class="btn btn-ghost text-3xl font-black italic tracking-tighter p-0 hover:bg-transparent">
                        <span class="text-primary">FIT</span>PUNG
                    </Link>
                </div>

                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-bold uppercase italic tracking-tight">Trainer Application</h2>
                    <p class="mt-2 text-sm text-base-content/60">
                        Become a professional mentor on our platform.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Read-only User Identity -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-bold">Identity</span>
                            </label>
                            <div class="input input-bordered w-full bg-base-200 flex items-center text-sm font-bold opacity-60">
                                {{ user.name }}
                            </div>
                        </div>
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-bold opacity-0">Email</span>
                            </label>
                            <div class="input input-bordered w-full bg-base-200 flex items-center text-xs font-medium opacity-60 truncate">
                                {{ user.email }}
                            </div>
                        </div>
                    </div>

                    <!-- Professional Specialty -->
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-bold">Your Specialty</span>
                        </label>
                        <input 
                            type="text" 
                            v-model="form.specialty" 
                            required 
                            autofocus 
                            class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                            placeholder="e.g. Weight Loss & HIIT, Bodybuilding"
                        />
                        <InputError class="mt-2 text-error" :message="form.errors.specialty" />
                    </div>

                    <!-- Experience & Pricing -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-bold">Experience (Yrs)</span>
                            </label>
                            <input 
                                type="number" 
                                v-model="form.experience_years" 
                                required 
                                class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                            />
                            <InputError class="mt-2 text-error" :message="form.errors.experience_years" />
                        </div>
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-bold">Price / Session (฿)</span>
                            </label>
                            <input 
                                type="number" 
                                v-model="form.price_per_session" 
                                required 
                                class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                            />
                            <InputError class="mt-2 text-error" :message="form.errors.price_per_session" />
                        </div>
                    </div>

                    <!-- Biography -->
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-bold">Coaching Biography</span>
                        </label>
                        <textarea 
                            v-model="form.bio" 
                            required 
                            rows="4"
                            class="textarea textarea-bordered h-32 w-full focus:textarea-primary bg-base-200 focus:bg-base-100 transition-all font-medium" 
                            placeholder="Describe your coaching style, certifications, and philosophy..."
                        ></textarea>
                        <InputError class="mt-2 text-error" :message="form.errors.bio" />
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit" 
                            class="btn btn-primary w-full shadow-lg font-black uppercase italic tracking-widest text-lg h-16" 
                            :class="{ 'loading': form.processing }"
                            :disabled="form.processing"
                        >
                            Submit Application
                        </button>
                    </div>

                    <div class="text-xs text-center text-base-content/40 mt-6">
                        By applying, you agree to our <a href="#" class="underline hover:text-base-content font-bold">Trainer Terms of Service</a>.
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
