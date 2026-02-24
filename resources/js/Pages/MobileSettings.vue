<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, onMounted } from 'vue';

const colors = [
    { name: 'FitPung Orange', hex: '#ec5b13' },
    { name: 'Royal Blue', hex: '#2563eb' },
    { name: 'Crimson Red', hex: '#dc2626' },
    { name: 'Forest Green', hex: '#16a34a' },
    { name: 'Deep Purple', hex: '#7c3aed' },
    { name: 'Hot Pink', hex: '#db2777' },
    { name: 'Amber', hex: '#d97706' },
    { name: 'Teal', hex: '#0d9488' },
    { name: 'Dark Gray', hex: '#374151' },
];

const selectedColor = ref('#ec5b13');
const showNotification = ref(false);

onMounted(() => {
    const savedColor = localStorage.getItem('fitpung-theme-color');
    if (savedColor) {
        selectedColor.value = savedColor;
    }
});

const selectColor = (hex) => {
    selectedColor.value = hex;
};

const saveChanges = () => {
    localStorage.setItem('fitpung-theme-color', selectedColor.value);
    window.dispatchEvent(new Event('storage'));
    
    // Show premium notification
    showNotification.value = true;
    setTimeout(() => {
        showNotification.value = false;
    }, 3000);
};
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Settings" />

        <!-- Success Notification -->
        <transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="transform -translate-y-full opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform -translate-y-full opacity-0"
        >
            <div v-if="showNotification" class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] w-[90%] max-w-xs transition-colors">
                <div class="bg-[var(--text-main)] backdrop-blur-md text-[var(--card-bg)] p-4 rounded-3xl shadow-2xl flex items-center gap-4 border border-[var(--border-color)] transition-colors">
                    <div class="size-10 rounded-2xl bg-green-500 flex items-center justify-center shadow-lg shadow-green-500/20">
                        <span class="material-symbols-outlined text-white text-xl">check_circle</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest leading-none mb-1">Success</p>
                        <p class="text-[11px] font-medium opacity-80 transition-colors">Theme updated successfully</p>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Header -->
        <header class="flex items-center gap-4 p-6 transition-colors">
            <Link :href="route('mobile.profile')" class="size-10 rounded-full bg-[var(--card-bg)] shadow-sm border border-[var(--border-color)] flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">arrow_back</span>
            </Link>
            <h1 class="text-xl font-black uppercase italic tracking-tighter text-[var(--text-main)] leading-none mt-1 transition-colors">Settings</h1>
        </header>

        <div class="px-6 py-4">
            <section class="mb-10 transition-colors">
                <div class="flex items-center gap-2 mb-6">
                    <span class="material-symbols-outlined text-[var(--theme-color)]">palette</span>
                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-[var(--text-muted)] transition-colors">Theme Customization</h2>
                </div>

                <div class="bg-[var(--card-bg)] rounded-[24px] p-8 border border-[var(--border-color)] shadow-sm transition-colors">
                    <p class="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)] mb-6 text-center transition-colors">Select Accent Color</p>
                    
                    <div class="grid grid-cols-3 gap-6">
                        <button v-for="color in colors" :key="color.hex"
                            @click="selectColor(color.hex)"
                            class="group flex flex-col items-center gap-2"
                        >
                            <div class="size-12 rounded-2xl transition-all duration-300 relative flex items-center justify-center shadow-lg"
                                :style="{ backgroundColor: color.hex }"
                                :class="selectedColor === color.hex ? 'scale-110 ring-4 ring-[var(--card-bg)]' : 'opacity-80 hover:opacity-100 hover:scale-105'"
                            >
                                <span v-if="selectedColor === color.hex" class="material-symbols-outlined text-white text-xl">check</span>
                            </div>
                            <span class="text-[8px] font-black uppercase tracking-tighter text-[var(--text-muted)] text-center transition-colors">{{ color.name }}</span>
                        </button>
                    </div>

                    <div class="mt-10 pt-10 border-t border-[var(--border-color)] transition-colors">
                        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)] mb-4 text-center transition-colors">Custom Hex Code</p>
                        <div class="flex items-center gap-3 bg-[var(--page-bg)] rounded-2xl p-4 border border-[var(--border-color)] transition-colors">
                            <div class="size-8 rounded-lg shadow-sm" :style="{ backgroundColor: selectedColor }"></div>
                            <input type="text" v-model="selectedColor" @input="selectColor(selectedColor)" 
                                class="flex-1 bg-transparent border-none text-sm font-black uppercase tracking-widest text-[var(--text-main)] focus:ring-0 transition-colors"
                                placeholder="#000000"
                            />
                        </div>
                    </div>

                    <!-- Save Action -->
                    <button @click="saveChanges" 
                        class="w-full mt-10 py-5 rounded-[24px] font-black italic uppercase tracking-[0.2em] shadow-xl transition-all active:scale-95 leading-none"
                        :style="{ backgroundColor: selectedColor, color: '#fff', boxShadow: `0 20px 40px -10px ${selectedColor}4D` }"
                    >
                        Save Changes
                    </button>
                </div>
            </section>

            <section class="mb-10 transition-colors">
                <div class="flex items-center gap-2 mb-6">
                    <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">info</span>
                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-[var(--text-muted)] transition-colors">Application Info</h2>
                </div>

                <div class="bg-[var(--card-bg)] rounded-[24px] overflow-hidden border border-[var(--border-color)] shadow-sm transition-colors">
                    <div class="p-5 flex items-center justify-between border-b border-[var(--border-color)] transition-colors">
                        <span class="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)] transition-colors">Version</span>
                        <span class="text-[10px] font-black text-[var(--text-muted)] opacity-60 transition-colors">2.4.0-ELITE</span>
                    </div>
                    <div class="p-5 flex items-center justify-between transition-colors">
                        <span class="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)] transition-colors">Developer</span>
                        <span class="text-[10px] font-black text-[var(--theme-color)] transition-colors">ANTIGRAVITY AI</span>
                    </div>
                </div>
            </section>
        </div>
    </MobileLayout>
</template>

<style scoped>
input::placeholder {
    color: #9ca3af;
}
</style>
