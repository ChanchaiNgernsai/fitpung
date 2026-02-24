<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const isThemeDark = ref(false);

onMounted(() => {
    isThemeDark.value = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (isThemeDark.value) {
        document.documentElement.classList.add('dark');
    }
});
</script>

<template>
    <Head title="Choose Your Experience - FitPung" />

    <div class="min-h-screen bg-[var(--page-bg)] flex flex-col items-center justify-center p-6 font-['Public_Sans'] transition-colors duration-500 overflow-hidden relative">
        <!-- Background Accents -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-[#ec5b13]/5 blur-[100px] rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500/5 blur-[100px] rounded-full translate-y-1/2 -translate-x-1/2"></div>

        <div class="max-w-md w-full space-y-12 relative z-10 text-center">
            <!-- Logo area -->
            <div class="space-y-4 animate-in fade-in slide-in-from-top-10 duration-700">
                <div class="size-20 bg-[var(--card-bg)] rounded-[24px] shadow-xl mx-auto flex items-center justify-center border border-[var(--border-color)] transition-colors">
                    <span class="material-symbols-outlined text-4xl text-[#ec5b13] fill-icon">fitness_center</span>
                </div>
                <h1 class="text-4xl font-black italic tracking-tighter uppercase leading-none text-[var(--text-main)] transition-colors">
                    <span class="text-[var(--text-main)]">Fit</span><span class="text-[#ec5b13]">Pung</span>
                </h1>
                <p class="text-xs font-black text-[var(--text-muted)] uppercase tracking-[0.3em] transition-colors">Select Experience</p>
            </div>

            <!-- Choice Cards -->
            <div class="grid gap-6 animate-in fade-in slide-in-from-bottom-10 duration-1000">
                <!-- Desktop Option -->
                <Link :href="route('home')" class="group p-8 bg-[var(--card-bg)] rounded-[32px] shadow-lg border border-[var(--border-color)] hover:border-[#ec5b13] transition-all duration-300 hover:-translate-y-2 text-left relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                        <span class="material-symbols-outlined text-8xl text-[var(--text-main)]">desktop_windows</span>
                    </div>
                    <div class="relative z-10 space-y-4">
                        <div class="size-12 rounded-2xl bg-[var(--page-bg)] flex items-center justify-center group-hover:bg-[#ec5b13]/10 transition-colors">
                            <span class="material-symbols-outlined text-[#ec5b13]">computer</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black uppercase italic leading-none mb-2 text-[var(--text-main)] transition-colors">Desktop View</h2>
                            <p class="text-xs text-[var(--text-muted)] font-medium leading-relaxed transition-colors">The original full-featured workspace for planning and building layouts.</p>
                        </div>
                        <div class="flex items-center gap-2 text-[#ec5b13] font-black text-[10px] uppercase tracking-widest">
                            Continue Legacy
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </div>
                    </div>
                </Link>

                <!-- Mobile Option -->
                <Link :href="route('mobile.home')" class="group p-8 bg-[var(--card-bg)] rounded-[32px] shadow-lg border border-[var(--border-color)] hover:border-[#ec5b13] transition-all duration-300 hover:-translate-y-2 text-left relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                        <span class="material-symbols-outlined text-8xl text-[var(--text-main)]">smartphone</span>
                    </div>
                    <div class="relative z-10 space-y-4">
                        <div class="size-12 rounded-2xl bg-[var(--page-bg)] flex items-center justify-center group-hover:bg-[#ec5b13]/10 transition-colors">
                            <span class="material-symbols-outlined text-[#ec5b13]">smartphone</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black uppercase italic leading-none mb-2 text-[var(--text-main)] transition-colors">Mobile Elite</h2>
                            <p class="text-xs text-[var(--text-muted)] font-medium leading-relaxed transition-colors">Simplified, premium experience optimized for your phone screens.</p>
                        </div>
                        <div class="flex items-center gap-2 text-[#ec5b13] font-black text-[10px] uppercase tracking-widest">
                            Go Mobile
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Footer info -->
            <div class="pt-8 opacity-30 text-[10px] font-bold uppercase tracking-[0.2em] text-[var(--text-muted)] transition-colors">
                FitPung Industries &copy; 2026
            </div>
        </div>
    </div>
</template>

<style scoped>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.fill-icon {
    font-variation-settings: 'FILL' 1;
}
</style>
