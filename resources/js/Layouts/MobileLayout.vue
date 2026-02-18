<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const page = usePage();
const currentRoute = computed(() => page.props.ziggy.location);

const themeColor = ref(localStorage.getItem('fitpung-theme-color') || '#ec5b13');

const navItems = [
    { name: 'Home', routeName: 'mobile.home', icon: 'home', pattern: /mobile$/ },
    { name: 'Maps', routeName: 'mobile.maps', icon: 'map', pattern: /(\/mobile\/maps|\/gyms\/.*\/white-map)/ },
    { name: 'Workout', routeName: 'mobile.workout', icon: 'fitness_center', pattern: /mobile\/workout/ },
    { name: 'Stats', routeName: 'mobile.stats', icon: 'analytics', pattern: /mobile\/stats/ },
    { name: 'Profile', routeName: 'mobile.profile', icon: 'person', pattern: /mobile\/profile/ },
];

const isActive = (item) => {
    const path = window.location.pathname;
    return item.pattern.test(path);
};

const applyThemeColor = (color) => {
    document.documentElement.style.setProperty('--theme-color', color);
    // Also set hex-only variable for some components
    document.documentElement.style.setProperty('--theme-color-rgb', hexToRgb(color));
};

const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? 
        `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : 
        '236, 91, 19';
};

onMounted(() => {
    document.documentElement.classList.remove('dark');
    applyThemeColor(themeColor.value);

    // Initial storage listener
    window.addEventListener('storage', (e) => {
        if (e.key === 'fitpung-theme-color') {
            themeColor.value = e.newValue;
            applyThemeColor(e.newValue);
        }
    });

    // Handle internal changes if we don't refresh
    watch(themeColor, (newColor) => {
        applyThemeColor(newColor);
    });
});
</script>

<template>
    <div class="min-h-screen bg-[#f0f2f5] flex flex-col items-center justify-center p-0 md:p-4 transition-colors duration-300">
        <!-- Mobile Frame -->
        <div class="relative w-full max-w-md bg-[#f8f6f6] h-screen shadow-2xl overflow-hidden flex flex-col font-['Public_Sans']">
            
            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto pb-24 relative">
                <slot />
            </div>

            <!-- Fixed Bottom Actions (Optional) -->
            <div class="absolute bottom-[88px] left-0 w-full px-6 z-40 pointer-events-none">
                <div class="pointer-events-auto">
                    <slot name="footer" />
                </div>
            </div>

            <!-- Bottom Navigation -->
            <nav class="absolute bottom-0 left-0 w-full bg-white/90  backdrop-blur-md border-t border-gray-100  px-6 py-5 flex items-center justify-between z-50">
                <Link v-for="item in navItems" 
                    :key="item.routeName"
                    :href="route(item.routeName)" 
                    class="flex flex-col items-center gap-1 transition-all active:scale-90"
                    :class="isActive(item) ? 'text-[var(--theme-color)]' : 'text-gray-500'"
                >
                    <span class="material-symbols-outlined" :class="{ 'fill-icon': isActive(item) }">
                        {{ item.icon }}
                    </span>
                    <span class="text-[9px] font-black uppercase tracking-widest leading-none">
                        {{ item.name }}
                    </span>
                </Link>
            </nav>
        </div>
    </div>
</template>

<style>
:root {
    --theme-color: #ec5b13;
    --theme-color-rgb: 236, 91, 19;
}

.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.fill-icon {
    font-variation-settings: 'FILL' 1;
}
/* Hide scrollbar for Chrome, Safari and Opera */
.overflow-y-auto::-webkit-scrollbar {
    display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.overflow-y-auto {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
