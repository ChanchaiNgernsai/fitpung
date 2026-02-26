<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from '@/language';

const { t } = useI18n();

const page = usePage();
const currentRoute = computed(() => page.props.ziggy.location);

const themeColor = ref(localStorage.getItem('fitpung-theme-color') || '#ec5b13');
const isDarkMode = ref(localStorage.getItem('fitpung-dark-mode') === 'true');

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('fitpung-dark-mode', isDarkMode.value);
    applyDarkMode(isDarkMode.value);
};

const applyDarkMode = (dark) => {
    if (dark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

const navItems = [
    { name: 'nav.home', routeName: 'mobile.home', icon: 'home', pattern: /mobile$/ },
    { name: 'nav.maps', routeName: 'mobile.maps', icon: 'map', pattern: /(\/mobile\/maps|\/gyms\/.*\/white-map)/ },
    { name: 'nav.workout', routeName: 'mobile.workout', icon: 'fitness_center', pattern: /mobile\/workout/ },
    { name: 'nav.stats', routeName: 'mobile.stats', icon: 'analytics', pattern: /mobile\/stats/ },
    { name: 'nav.profile', routeName: 'mobile.profile', icon: 'person', pattern: /mobile\/profile/ },
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
    applyThemeColor(themeColor.value);

    // Initial storage listener
    window.addEventListener('storage', (e) => {
        if (e.key === 'fitpung-theme-color') {
            themeColor.value = e.newValue;
            applyThemeColor(e.newValue);
        }
        if (e.key === 'fitpung-dark-mode') {
            isDarkMode.value = e.newValue === 'true';
            applyDarkMode(isDarkMode.value);
        }
    });

    applyDarkMode(isDarkMode.value);

    // Handle internal changes if we don't refresh
    watch(themeColor, (newColor) => {
        applyThemeColor(newColor);
    });
});
</script>

<template>
    <div class="min-h-screen bg-[var(--page-bg)] flex flex-col items-center justify-center p-0 md:p-4 transition-colors duration-500">
        <!-- Mobile Frame -->
        <div class="relative w-full max-w-md bg-[var(--app-bg)] h-screen shadow-2xl overflow-hidden flex flex-col font-['Public_Sans'] transition-colors duration-500">
            
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
            <nav class="absolute bottom-0 left-0 w-full bg-[var(--nav-bg)] backdrop-blur-md border-t border-[var(--border-color)] px-6 py-5 flex items-center justify-between z-50 transition-colors duration-500">
                <Link v-for="item in navItems" 
                    :key="item.routeName"
                    :href="route(item.routeName)" 
                    class="flex flex-col items-center gap-1 transition-all active:scale-90"
                    :class="isActive(item) ? 'text-[var(--theme-color)]' : 'text-[var(--text-muted)]'"
                >
                    <span class="material-symbols-outlined" :class="{ 'fill-icon': isActive(item) }">
                        {{ item.icon }}
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-tight leading-none text-center">
                        {{ t(item.name) }}
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
    
    /* Light Mode Tokens */
    --page-bg: #f0f2f5;
    --app-bg: #f8f6f6;
    --card-bg: #ffffff;
    --nav-bg: rgba(255, 255, 255, 0.9);
    --text-main: #111827;
    --text-muted: #6b7280;
    --border-color: #f3f4f6;
}

.dark {
    /* Dark Mode Tokens */
    --page-bg: #000000;
    --app-bg: #0f1115;
    --card-bg: #1a1d23;
    --nav-bg: rgba(26, 29, 35, 0.9);
    --text-main: #ffffff;
    --text-muted: #d1d5db;
    --border-color: #2d3139;
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
