<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

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

const user = usePage().props.auth.user;
</script>

<template>
    <div class="min-h-screen bg-base-200 text-base-content font-sans flex flex-col">
        <!-- Top Navbar -->
        <nav class="navbar bg-base-100/90 backdrop-blur border-b border-base-content/10 sticky top-0 z-50 shrink-0 h-16">
            <div class="container mx-auto px-4 w-full flex items-center justify-between">
                
                <!-- Left: Logo -->
                <div class="flex items-center gap-6">
                    <Link href="/" class="btn btn-ghost text-2xl font-black italic tracking-tighter p-0 hover:bg-transparent">
                        <span class="text-primary">FIT</span>PUNG
                    </Link>
                </div>

                <!-- Right: Actions, Theme, User -->
                <div class="flex items-center gap-3">
                    
                    <!-- Slot for page-specific actions (e.g. Save Button) -->
                    <slot name="header-actions" />

                    <!-- Theme Toggle -->
                    <button @click="toggleTheme" class="btn btn-circle btn-ghost btn-sm" title="Toggle Theme">
                        <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    <!-- User Dropdown -->
                    <div v-if="user" class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
                            <div class="bg-primary/20 text-primary rounded-full w-9 ring ring-primary ring-offset-base-100 ring-offset-2 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow-lg menu menu-sm dropdown-content bg-base-100 rounded-box w-52 border border-base-content/10">
                            <li class="px-2 py-1 text-xs opacity-50 font-bold uppercase tracking-wider">{{ user.name }}</li>
                            <li><Link :href="route('profile.edit')">Profile</Link></li>
                            <div class="divider my-1"></div>
                            <li><Link :href="route('logout')" method="post" as="button" class="text-error">Log Out</Link></li>
                        </ul>
                    </div>
                    <div v-else class="flex gap-2">
                        <Link :href="route('login')" class="btn btn-ghost btn-sm">Log In</Link>
                        <Link :href="route('register')" class="btn btn-primary btn-sm">Sign Up</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading (Optional) -->
        <header class="bg-base-100 shadow-sm z-40 relative" v-if="$slots.header">
            <div class="container mx-auto px-4 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
                 <div class="flex-1 w-full">
                    <slot name="header" />
                 </div>
                 
                 <div v-if="!route().current('dashboard')" class="flex gap-2 shrink-0">
                    <Link 
                        :href="route('dashboard')" 
                        :class="[
                            'btn btn-sm shadow', 
                            route().current('dashboard') 
                                ? (isDark ? 'bg-white text-black hover:bg-gray-200 border-none' : 'btn-neutral') 
                                : 'btn-ghost bg-base-200/50 hover:bg-base-300'
                        ]"
                    >
                        Dashboard
                    </Link>
                 </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 relative flex flex-col min-h-0 overflow-hidden">
            <!-- Inner scrolling container -->
            <div class="flex-1 overflow-y-auto overflow-x-hidden">
                <slot />
            </div>
        </main>
    </div>
</template>
