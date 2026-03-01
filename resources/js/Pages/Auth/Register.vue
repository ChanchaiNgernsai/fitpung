<script setup>
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    weight: '',
    height: '',
    goal: '',
    photo: null,
});

const photoPreview = ref(null);
const photoInput = ref(null);

const previewPhoto = () => {
    const photo = photoInput.value.files[0];
    if (!photo) return;

    form.photo = photo;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(photo);
};

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
                    <!-- Profile Photo -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative group">
                            <div class="size-32 rounded-full border-4 border-primary/20 bg-base-200 overflow-hidden flex items-center justify-center transition-all group-hover:border-primary/50 shadow-xl">
                                <img v-if="photoPreview" :src="photoPreview" class="size-full object-cover" />
                                <span v-else class="material-symbols-outlined text-5xl opacity-20">person</span>
                            </div>
                            
                            <!-- Upload Button Overlay -->
                            <button 
                                type="button" 
                                @click="$refs.photoInput.click()"
                                class="absolute bottom-0 right-0 size-10 rounded-full bg-primary text-primary-content shadow-lg flex items-center justify-center hover:scale-110 active:scale-95 transition-all"
                            >
                                <span class="material-symbols-outlined text-xl">photo_camera</span>
                            </button>
                        </div>
                        
                        <input 
                            type="file" 
                            ref="photoInput" 
                            class="hidden" 
                            accept="image/*"
                            @change="previewPhoto"
                        />
                        
                        <p class="mt-3 text-xs font-bold uppercase tracking-wider text-base-content/40">Profile Photo</p>
                        <InputError class="mt-2 text-error" :message="form.errors.photo" />
                    </div>
                    
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

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-bold">Weight (kg)</span>
                            </label>
                            <input 
                                id="weight" 
                                type="number" 
                                v-model="form.weight" 
                                class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                                placeholder="kg"
                            />
                            <InputError class="mt-2 text-error" :message="form.errors.weight" />
                        </div>
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-bold">Height (cm)</span>
                            </label>
                            <input 
                                id="height" 
                                type="number" 
                                v-model="form.height" 
                                class="input input-bordered w-full focus:input-primary bg-base-200 focus:bg-base-100 transition-all" 
                                placeholder="cm"
                            />
                            <InputError class="mt-2 text-error" :message="form.errors.height" />
                        </div>
                    </div>

                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-bold">Fitness Goal</span>
                        </label>
                        <select 
                            id="goal" 
                            v-model="form.goal" 
                            class="select select-bordered w-full focus:select-primary bg-base-200 focus:bg-base-100 transition-all"
                        >
                            <option value="" disabled selected>-</option>
                            <option value="Muscle Gain">Muscle Gain (เพิ่มกล้ามเนื้อ)</option>
                            <option value="Lose Weight">Lose Weight (ลดน้ำหนัก)</option>
                            <option value="Keep Fit">Keep Fit (รักษารูปร่าง)</option>
                            <option value="Endurance">Endurance (เพิ่มความทนทาน)</option>
                        </select>
                        <InputError class="mt-2 text-error" :message="form.errors.goal" />
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
