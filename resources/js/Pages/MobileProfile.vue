<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { computed, onMounted, ref, watch, nextTick } from 'vue';

const props = defineProps({
    weightHistories: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const user = computed(() => page.props.auth.user || {});
const localWeight = ref(user.value.weight || 0);

// Keep local weight in sync with page props
watch(() => user.value.weight, (newVal) => {
    if (newVal !== undefined && newVal !== null) {
        localWeight.value = newVal;
    }
}, { immediate: true });

const isInitialSync = ref(true);
const isEditModalOpen = ref(false);
const isWeightModalOpen = ref(false);
const errors = ref({});
const editForm = ref({
    name: '',
    height: 0,
    goal: ''
});

const weightDiff = computed(() => {
    if (!props.weightHistories || props.weightHistories.length < 2) return null;
    const sorted = [...props.weightHistories].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    const currentWeight = user.value.weight; // Use actual current weight
    const previousWeight = sorted[0].id === 'current' ? sorted[1].weight : sorted[0].weight; 
    // Wait, the logic for weightDiff needs to be simple: current profile weight vs last history record
    const lastRecord = sorted[0];
    const diff = currentWeight - lastRecord.weight;
    
    return {
        value: Math.abs(diff).toFixed(1),
        isIncrease: diff > 0,
        isDecrease: diff < 0,
        // Swap colors here: Success (Green) for Decrease, Warning (Red) for Increase
        type: diff < 0 ? 'success' : (diff > 0 ? 'warning' : 'neutral')
    };
});

const totalChange = computed(() => {
    if (!props.weightHistories || props.weightHistories.length < 2) return null;
    const currentWeight = props.weightHistories[props.weightHistories.length - 1].weight;
    const firstWeight = props.weightHistories[0].weight;
    const diff = currentWeight - firstWeight;
    return {
        value: Math.abs(diff).toFixed(1),
        isIncrease: diff > 0,
        isDecrease: diff < 0
    };
});

const sortedHistory = computed(() => {
    return [...props.weightHistories].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const graphPoints = computed(() => {
    if (!props.weightHistories) return [];
    
    // Take the last 6 entries for the graph
    let recentHistories = props.weightHistories.slice(-6);
    
    // Check if the current user.weight is different from the last history record to show the most recent change
    const lastHistory = recentHistories.length > 0 ? recentHistories[recentHistories.length - 1] : null;
    const currentWeight = user.value.weight;
    
    // If current weight is different from last recorded weight, or no records exist, add it as the present point
    const displayHistories = [...recentHistories];
    if (!lastHistory || lastHistory.weight !== currentWeight) {
        displayHistories.push({
            weight: currentWeight,
            created_at: new Date().toISOString()
        });
    }
    
    // Limit to 7 points total for a clean trend
    const finalPoints = displayHistories.slice(-7);
    
    const weights = finalPoints.map(h => h.weight);
    const minWeight = Math.min(...weights) - 1;
    const maxWeight = Math.max(...weights) + 1;
    const range = Math.max(maxWeight - minWeight, 2);
    
    return finalPoints.map((h, i) => {
        const x = (i / (finalPoints.length - 1)) * 300;
        const y = 100 - ((h.weight - minWeight) / range) * 75 - 15;
        const date = new Date(h.created_at);
        const isLatest = i === finalPoints.length - 1;
        
        return {
            x,
            y,
            weight: h.weight,
            label: isLatest ? 'ปัจจุบัน' : date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }),
            active: true
        };
    });
});

const recentLogs = computed(() => {
    // Show only 5 most recent to keep it clean
    return sortedHistory.value.slice(0, 5);
});

const svgPath = computed(() => {
    const points = graphPoints.value;
    if (points.length < 2) return '';
    
    let d = `M ${points[0].x},${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i];
        const p1 = points[i + 1];
        const cp1x = p0.x + (p1.x - p0.x) / 2;
        d += ` C ${cp1x},${p0.y} ${cp1x},${p1.y} ${p1.x},${p1.y}`;
    }
    return d;
});

const openEditModal = () => {
    errors.value = {};
    editForm.value = {
        name: user.value.name,
        height: user.value.height,
        goal: user.value.goal
    };
    isEditModalOpen.value = true;
};

const saveProfile = () => {
    errors.value = {};
    router.patch(route('profile.update'), editForm.value, {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
        onError: (err) => {
            errors.value = err;
            alert('Failed to save profile: ' + (Object.values(err)[0] || 'Unknown error'));
        },
        preserveScroll: true
    });
};

const integerRef = ref(null);
const decimalRef = ref(null);

const integers = Array.from({ length: 281 }, (_, i) => 20 + i);
const decimals = Array.from({ length: 10 }, (_, i) => i);

const onIntegerScroll = (e) => {
    const scrollTop = e.target.scrollTop;
    const index = Math.round(scrollTop / 48); // Fixed 48px height
    const intPart = integers[index] || 20;
    const decPart = Math.round((localWeight.value % 1) * 10) / 10;
    localWeight.value = parseFloat((intPart + decPart).toFixed(1));
};

const onDecimalScroll = (e) => {
    const scrollTop = e.target.scrollTop;
    const index = Math.round(scrollTop / 48); // Fixed 48px height
    const decPart = (decimals[index] || 0) / 10;
    const intPart = Math.floor(localWeight.value);
    localWeight.value = parseFloat((intPart + decPart).toFixed(1));
};

const hasWeightChanged = computed(() => {
    return localWeight.value !== user.value.weight;
});

const cancelWeightEdit = () => {
    localWeight.value = user.value.weight;
    isWeightModalOpen.value = false;
};

const openWeightModal = () => {
    localWeight.value = user.value.weight || 0;
    isWeightModalOpen.value = true;
    nextTick(() => {
        syncPickersToWeight(localWeight.value);
    });
};

// Auto-save logic removed in favor of manual save button

const saveWeightToServer = (weight) => {
    if (weight < 20 || weight > 300) return;
    router.patch(route('profile.update'), { 
        weight: weight 
    }, {
        preserveScroll: true,
        only: ['auth', 'flash'],
        onSuccess: () => {
            isWeightModalOpen.value = false;
        },
        onError: (err) => {
            syncPickersToWeight(user.value.weight);
            alert('Failed to update weight: ' + (Object.values(err)[0] || 'Error'));
        }
    });
};

const syncPickersToWeight = (weight) => {
    if (!integerRef.value || !decimalRef.value) return;
    const intPart = Math.floor(weight);
    const decPart = Math.round((weight % 1) * 10);
    
    // 48px per item height for full screen picker
    integerRef.value.scrollTop = (intPart - 20) * 48;
    decimalRef.value.scrollTop = decPart * 48;
};

onMounted(() => {
    setTimeout(() => {
        syncPickersToWeight(localWeight.value);
        // Reset flag after initial sync is done
        setTimeout(() => {
            isInitialSync.value = false;
        }, 300);
    }, 100);
});

let updateTimeout = null;

const goBack = () => {
    window.history.back();
};

const showToast = ref(false);
const toastMessage = ref('');

const isDarkMode = ref(localStorage.getItem('fitpung-dark-mode') === 'true');

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('fitpung-dark-mode', isDarkMode.value);
    // Dispatch storage event to notify Layout
    window.dispatchEvent(new StorageEvent('storage', {
        key: 'fitpung-dark-mode',
        newValue: isDarkMode.value ? 'true' : 'false'
    }));
    
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

watch(() => page.props.flash.status, (newStatus) => {
    if (newStatus) {
        toastMessage.value = newStatus;
        showToast.value = true;
        setTimeout(() => {
            showToast.value = false;
        }, 3000);
    }
}, { immediate: true });
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Profile" />

        <!-- Success Toast -->
        <div v-if="showToast" class="fixed top-6 left-1/2 -translate-x-1/2 z-[200] animate-slide-down pointer-events-none w-max">
            <div class="bg-[#111827] text-white px-5 py-3 rounded-full shadow-2xl flex items-center gap-3 border border-white/10 backdrop-blur-md">
                <div class="size-6 rounded-full bg-[var(--theme-color)] flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-[14px] font-black">check</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest whitespace-nowrap">{{ toastMessage }}</span>
            </div>
        </div>

        <!-- Header -->
        <header class="flex items-center justify-between p-6 transition-colors">
            <button @click="goBack" class="size-10 rounded-full bg-[var(--card-bg)] shadow-sm border border-[var(--border-color)] flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[var(--text-main)] font-bold">arrow_back</span>
            </button>
            <h1 class="text-[8px] font-black uppercase tracking-[0.3em] text-[var(--text-muted)] transition-colors">Account Profile</h1>
            <div class="flex items-center gap-2">
                <button @click="toggleDarkMode" class="size-10 rounded-full bg-[var(--card-bg)] shadow-sm border border-[var(--border-color)] flex items-center justify-center transition-all active:scale-90">
                    <span class="material-symbols-outlined text-[var(--text-main)] font-bold text-xl">
                        {{ isDarkMode ? 'light_mode' : 'dark_mode' }}
                    </span>
                </button>
                <Link :href="route('mobile.settings')" class="size-10 rounded-full bg-[var(--card-bg)] shadow-sm border border-[var(--border-color)] flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-[var(--text-main)] font-bold">settings</span>
                </Link>
            </div>
        </header>

        <!-- Profile Detail -->
        <div class="pb-8">
            <!-- User Intro -->
            <div class="px-6 py-4 flex flex-col items-center">
                <div class="relative mb-6">
                    <div class="size-36 rounded-full border-4 border-[var(--theme-color)] p-1.5 shadow-2xl shadow-[var(--theme-color)]/20 transition-colors">
                        <div class="size-full rounded-full overflow-hidden bg-[var(--page-bg)] transition-colors">
                            <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjuchVWDk_IRP1TbfrAkzG8C4dA-u_ZSX_bmaJ7iTLsz349d2YCZwMsRA1jv1NHNq-FTa1WuuTrctIi_d9WHJb2VI1NZrJ3p_BqZcczzKpP4SZPQj3B_XX6EDlPU5fbHMh9GznMXlc3-Koi2GaRlWBu-73j1pHp39bRwxLX-V_fo3bm3pe---4bpS8o-nSgL6mxkoqqAL8GatxFr8B0_Jqchl4PZb4VDP9b3_v-iSeR5UM_i9ZA9WxigaAtHyyyxzav-yqEqFoT0U" class="size-full object-cover">
                        </div>
                    </div>
                    <div class="absolute bottom-2 right-2 size-8 bg-[var(--theme-color)] rounded-full border-4 border-[var(--app-bg)] flex items-center justify-center text-white transition-colors">
                        <span class="material-symbols-outlined text-sm fill-icon">verified</span>
                    </div>
                </div>
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-black italic uppercase tracking-tighter text-[var(--text-main)] leading-none transition-colors">{{ user.name || 'User' }}</h2>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)] mt-3">Elite Athlete • Level 42</p>
                </div>

                <button @click="openEditModal" class="w-full py-5 bg-[var(--theme-color)] text-white font-black italic uppercase tracking-widest rounded-[24px] shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all mb-10">
                    Edit Profile
                </button>
            </div>


            <!-- Quick Stats -->
            <section class="px-6 py-4">
                <div class="grid grid-cols-3 gap-3">
                    <!-- Weight Card -->
                    <div class="bg-[var(--card-bg)] p-4 rounded-[32px] border border-[var(--border-color)] shadow-sm flex flex-col items-center justify-center text-center relative h-[140px] transition-colors">
                        <span class="absolute top-4 text-[8px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)]">Weight</span>
                        <div class="flex flex-col items-center mt-2 relative">
                            <div class="flex items-baseline gap-0.5">
                                <span class="text-2xl font-black text-[var(--theme-color)] italic leading-none">{{ Number(user.weight).toFixed(1) }}</span>
                                <span class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest">KG</span>
                            </div>
                            
                            <!-- Weight Change Indicator -->
                            <div v-if="weightDiff && weightDiff.value !== '0.0'" class="absolute -top-3 -right-6 flex items-center gap-0.5 px-2 py-1 rounded-full bg-[var(--card-bg)] shadow-md border border-[var(--border-color)] scale-90">
                                <span v-if="weightDiff.isIncrease" class="text-[8px] text-[#ef4444]">▲</span>
                                <span v-if="weightDiff.isDecrease" class="text-[8px] text-[#22c55e]">▼</span>
                                <span class="text-[9px] font-black tabular-nums" :class="weightDiff.isIncrease ? 'text-[#ef4444]' : 'text-[#22c55e]'">{{ weightDiff.value }}</span>
                            </div>

                            <button 
                                @click="openWeightModal"
                                class="mt-4 bg-[var(--theme-color)] text-white text-[8px] font-black uppercase tracking-[0.1em] px-5 py-2.5 rounded-full shadow-lg shadow-[var(--theme-color)]/20 active:scale-95 transition-all border border-[var(--theme-color)]/20"
                            >
                                Update
                            </button>
                        </div>
                    </div>

                    <!-- Height Section -->
                    <div class="bg-[var(--card-bg)] p-4 rounded-[32px] border border-[var(--border-color)] shadow-sm flex flex-col items-center justify-center text-center relative h-[140px] transition-colors">
                        <span class="absolute top-4 text-[8px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)]">Height</span>
                        <div class="flex flex-col items-center mt-2">
                            <div class="flex items-baseline gap-0.5">
                                <span class="text-2xl font-black text-[var(--text-main)] italic leading-none">{{ user.height || 0 }}</span>
                                <span class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest">CM</span>
                            </div>
                        </div>
                    </div>

                    <!-- Goal Section -->
                    <div class="bg-[var(--card-bg)] p-4 rounded-[32px] border border-[var(--border-color)] shadow-sm flex flex-col items-center justify-center text-center relative h-[140px] transition-colors">
                        <span class="absolute top-4 text-[8px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)]">Goal</span>
                        <div class="flex flex-col items-center mt-2">
                            <span class="text-[11px] font-black text-[var(--text-main)] uppercase leading-tight tracking-tighter h-8 flex items-center justify-center">{{ user.goal || 'No Goal' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Weight Trends Graph -->
            <section class="px-6 py-4" v-if="graphPoints.length > 0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--text-muted)] transition-colors">Weight Trends</h3>
                    <div v-if="totalChange" class="flex items-center gap-2">
                        <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest transition-colors">Total Change</span>
                        <div class="flex items-center gap-1 px-3 py-1.5 rounded-full bg-[var(--card-bg)] border border-[var(--border-color)]">
                            <span :class="totalChange.isDecrease ? 'text-green-500' : (totalChange.isIncrease ? 'text-red-500' : 'text-[var(--text-muted)]')" class="text-[10px] font-black tabular-nums transition-colors">
                                {{ totalChange.isIncrease ? '+' : (totalChange.isDecrease ? '-' : '') }}{{ totalChange.value }}
                            </span>
                            <span class="text-[7px] font-black text-[var(--text-muted)] transition-colors">KG</span>
                        </div>
                    </div>
                </div>

                <div class="bg-[var(--card-bg)] rounded-[32px] p-6 border border-[var(--border-color)] shadow-sm transition-colors">
                    <div class="relative h-36 w-full mb-4">
                        <svg class="w-full h-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 300 120">
                             <!-- Helper Lines -->
                            <line x1="0" y1="100" x2="300" y2="100" stroke="var(--border-color)" stroke-width="1" class="opacity-20 transition-colors" />
                            <line x1="0" y1="20" x2="300" y2="20" stroke="var(--border-color)" stroke-width="1" class="opacity-20 transition-colors" />

                            <!-- Path -->
                            <path 
                                :d="svgPath" 
                                fill="none" 
                                stroke="var(--theme-color)" 
                                stroke-width="5" 
                                stroke-linecap="round" 
                                class="transition-all duration-1000"
                            />
                            
                            <!-- Dots & Values -->
                            <template v-for="(p, i) in graphPoints" :key="i">
                                <!-- Weight Value Above Point -->
                                <text 
                                    :x="p.x" 
                                    :y="p.y - 12" 
                                    text-anchor="middle" 
                                    class="text-[10px] font-black fill-[var(--text-main)] tracking-tighter"
                                >
                                    {{ p.weight.toFixed(1) }}
                                </text>

                                <circle 
                                    :cx="p.x" 
                                    :cy="p.y" 
                                    fill="var(--theme-color)" 
                                    r="6" 
                                    stroke="var(--card-bg)" 
                                    stroke-width="3" 
                                    class="transition-all"
                                />
                            </template>
                        </svg>
                    </div>
                    <!-- Labels -->
                    <div class="flex justify-between px-1 text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest transition-colors">
                        <span v-for="(p, i) in graphPoints" :key="i">{{ p.label }}</span>
                    </div>
                </div>

                <!-- Weight History Table (Immediately after graph) -->
                <div class="mt-6 space-y-3">
                    <div class="flex items-center justify-between px-2">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)]">Recent History</h3>
                        <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest">Last {{ recentLogs.length }} Records</span>
                    </div>
                    
                    <div class="bg-[var(--card-bg)] rounded-[32px] border border-[var(--border-color)] overflow-hidden shadow-sm">
                        <div v-if="recentLogs.length === 0" class="p-10 text-center">
                            <span class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest">No weight history yet</span>
                        </div>
                        <div v-else class="divide-y divide-[var(--border-color)]/50">
                            <div v-for="(history, index) in recentLogs" :key="history.id" class="p-5 flex items-center justify-between transition-colors">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-black text-[var(--text-main)] tabular-nums">{{ new Date(history.created_at).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) }}</span>
                                    <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest">{{ new Date(history.created_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                </div>
                                
                                <!-- Change relative to previous -->
                                <div v-if="index < recentLogs.length - 1" class="flex flex-col items-end">
                                    <div class="flex items-center gap-1">
                                        <span class="text-[10px] font-black tabular-nums" :class="(history.weight - recentLogs[index + 1].weight) < 0 ? 'text-[#22c55e]' : ((history.weight - recentLogs[index + 1].weight) > 0 ? 'text-[#ef4444]' : 'text-[var(--text-muted)]')">
                                            {{ (history.weight - recentLogs[index + 1].weight) > 0 ? '+' : '' }}{{ (history.weight - recentLogs[index + 1].weight).toFixed(1) }}
                                        </span>
                                        <span v-if="(history.weight - recentLogs[index + 1].weight) !== 0" class="text-[8px]" :class="(history.weight - recentLogs[index + 1].weight) < 0 ? 'text-[#22c55e]' : 'text-[#ef4444]'">
                                            {{ (history.weight - recentLogs[index + 1].weight) > 0 ? '▲' : '▼' }}
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="flex flex-col items-end">
                                    <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest">Initial</span>
                                </div>

                                <div class="flex items-baseline gap-0.5 bg-[var(--page-bg)] px-3 py-1.5 rounded-full border border-[var(--border-color)]">
                                    <span class="text-xs font-black text-[var(--theme-color)] italic tabular-nums">{{ history.weight.toFixed(1) }}</span>
                                    <span class="text-[7px] font-black text-[var(--text-muted)] uppercase">KG</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Performance / Workouts -->
            <div class="px-6 w-full space-y-4 pt-4 pb-12">
                <div class="flex items-center justify-between">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)]">Performance</h3>
                </div>
                <div class="bg-[var(--card-bg)] p-5 rounded-[32px] border border-[var(--border-color)] flex items-center justify-between shadow-sm transition-all hover:scale-[1.02]">
                    <div class="flex items-center gap-4">
                        <div class="size-12 rounded-2xl bg-[var(--theme-color)]/10 flex items-center justify-center text-[var(--theme-color)]">
                            <span class="material-symbols-outlined fill-icon">fitness_center</span>
                        </div>
                        <div class="ml-1 flex-1 text-left">
                            <h4 class="font-black uppercase text-sm text-[var(--text-main)] leading-none mb-1">Workouts</h4>
                            <p class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest">128 Completed</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-black text-[var(--theme-color)] leading-none">82%</p>
                        <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest">Progress</p>
                    </div>
                </div>

                <!-- Bottom Actions -->
                <div class="bg-[var(--card-bg)] rounded-[32px] border border-[var(--border-color)] overflow-hidden shadow-sm mt-6">
                    <a href="#" class="flex items-center justify-between p-5 hover:bg-[var(--page-bg)] border-b border-[var(--border-color)] transition-colors">
                        <div class="flex items-center gap-4">
                            <span class="material-symbols-outlined text-[var(--text-muted)]">workspace_premium</span>
                            <span class="font-black uppercase text-[10px] tracking-widest text-[var(--text-muted)]">Personal Bests</span>
                        </div>
                        <span class="material-symbols-outlined text-[var(--text-muted)] font-bold text-sm">arrow_forward_ios</span>
                    </a>
                    <a href="#" class="flex items-center justify-between p-5 hover:bg-[var(--page-bg)] transition-colors">
                        <div class="flex items-center gap-4">
                            <span class="material-symbols-outlined text-[var(--text-muted)]">logout</span>
                            <span class="font-black uppercase text-[10px] tracking-widest text-red-500">Sign Out</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <transition name="modal">
            <div v-if="isEditModalOpen" class="fixed inset-0 z-[150] flex items-end md:items-center justify-center p-0 md:p-8 bg-black/60 backdrop-blur-sm">
                <div class="absolute inset-0" @click="isEditModalOpen = false"></div>
                
                <div class="bg-[var(--card-bg)] w-full max-w-lg md:rounded-[40px] rounded-t-[40px] overflow-hidden shadow-2xl relative animate-in slide-in-from-bottom-full duration-500 h-[85vh] md:h-auto flex flex-col transition-colors border-t border-[var(--border-color)]">
                    <div class="p-8 border-b border-[var(--border-color)] flex items-center justify-between transition-colors">
                        <div>
                            <h3 class="text-2xl font-black uppercase italic text-[var(--text-main)] transition-colors leading-none">Edit Profile</h3>
                            <p class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest mt-2 transition-colors">Update your information</p>
                        </div>
                        <button @click="isEditModalOpen = false" class="size-12 rounded-full bg-[var(--page-bg)] flex items-center justify-center transition-all active:scale-90 border border-[var(--border-color)]">
                            <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">close</span>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8 space-y-8 no-scrollbar scroll-smooth">
                        <!-- Personal Info -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-[var(--theme-color)] text-lg">person</span>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)]">Personal Details</h4>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-4">Full Name</label>
                                    <div class="bg-[var(--page-bg)] rounded-3xl p-5 border border-[var(--border-color)] flex items-center gap-3 transition-colors focus-within:border-[var(--theme-color)]/50 focus-within:ring-4 focus-within:ring-[var(--theme-color)]/5">
                                        <input v-model="editForm.name" type="text" class="flex-1 bg-transparent border-none text-sm font-black text-[var(--text-main)] placeholder:text-[var(--text-muted)]/30 focus:ring-0 p-0" placeholder="Display Name" />
                                    </div>
                                    <p v-if="errors.name" class="text-[8px] text-red-500 font-bold uppercase tracking-widest ml-4 mt-1">{{ errors.name }}</p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-4">Height (CM)</label>
                                    <div class="bg-[var(--page-bg)] rounded-3xl p-5 border border-[var(--border-color)] flex items-center gap-3 transition-colors focus-within:border-[var(--theme-color)]/50 focus-within:ring-4 focus-within:ring-[var(--theme-color)]/5">
                                        <input v-model="editForm.height" type="number" class="flex-1 bg-transparent border-none text-sm font-black text-[var(--text-main)] placeholder:text-[var(--text-muted)]/30 focus:ring-0 p-0" placeholder="0" />
                                        <span class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest">CM</span>
                                    </div>
                                    <p v-if="errors.height" class="text-[8px] text-red-500 font-bold uppercase tracking-widest ml-4 mt-1">{{ errors.height }}</p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-4">Weight Goal</label>
                                    <div class="bg-[var(--page-bg)] rounded-3xl p-5 border border-[var(--border-color)] flex items-center gap-3 transition-colors focus-within:border-[var(--theme-color)]/50 focus-within:ring-4 focus-within:ring-[var(--theme-color)]/5">
                                        <select v-model="editForm.goal" class="flex-1 bg-transparent border-none text-sm font-black text-[var(--text-main)] focus:ring-0 p-0 appearance-none">
                                            <option value="Keep Fit">Keep Fit</option>
                                            <option value="Lose Weight">Lose Weight</option>
                                            <option value="Build Muscle">Build Muscle</option>
                                            <option value="Extreme">Extreme</option>
                                        </select>
                                        <span class="material-symbols-outlined text-[var(--text-muted)] text-sm">expand_more</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 border-t border-[var(--border-color)] bg-[var(--card-bg)] transition-colors">
                        <button @click="saveProfile" class="w-full py-5 bg-[var(--theme-color)] text-white font-black italic uppercase tracking-[0.2em] rounded-[28px] shadow-2xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all text-sm leading-none">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Weight Selection Modal -->
        <div v-if="isWeightModalOpen" class="fixed inset-0 z-[150] flex flex-col items-center justify-end">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity duration-300" @click="cancelWeightEdit"></div>
            
            <div class="relative w-full max-w-lg bg-[var(--card-bg)] rounded-t-[50px] p-8 pb-10 animate-slide-up border-t border-white/10 shadow-2xl flex flex-col h-[75vh]">
                <div class="w-16 h-1.5 bg-[var(--border-color)] rounded-full mx-auto mb-8 shrink-0"></div>
                
                <div class="text-center mb-10 shrink-0">
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-[var(--theme-color)]">Select Your Weight</span>
                    <div class="flex items-center justify-center gap-2 mt-4">
                        <span class="text-6xl font-black italic text-[var(--text-main)] tabular-nums transition-colors">{{ localWeight.toFixed(1) }}</span>
                        <span class="text-sm font-black text-[var(--text-muted)] uppercase tracking-widest mt-6">KG</span>
                    </div>
                </div>

                <!-- Picker Area -->
                <div class="relative flex-1 flex flex-col items-center justify-center overflow-hidden">
                    <!-- Overlay selection gradient -->
                    <div class="absolute inset-0 pointer-events-none z-10 flex flex-col">
                        <div class="flex-1 bg-gradient-to-b from-[var(--card-bg)] to-transparent opacity-90"></div>
                        <div class="h-12 border-y border-[var(--theme-color)]/20 bg-[var(--theme-color)]/5 mx-6 rounded-2xl"></div>
                        <div class="flex-1 bg-gradient-to-t from-[var(--card-bg)] to-transparent opacity-90"></div>
                    </div>

                    <div class="flex items-center justify-center gap-8 w-full h-[240px] relative z-0">
                        <!-- Integer Picker -->
                        <div class="relative h-full w-24">
                            <div 
                                ref="integerRef"
                                @scroll="onIntegerScroll"
                                class="h-full overflow-y-auto no-scrollbar snap-y snap-mandatory py-[96px]"
                            >
                                <div v-for="int in integers" :key="int" 
                                    class="h-[48px] flex items-center justify-center snap-center transition-all duration-300"
                                    :class="Math.floor(localWeight) === int ? 'text-3xl font-black text-[var(--text-main)]' : 'text-sm font-bold text-[var(--text-muted)] opacity-20'"
                                >
                                    {{ int }}
                                </div>
                            </div>
                        </div>

                        <!-- Separator -->
                        <div class="text-4xl font-black text-[var(--theme-color)] mb-2">.</div>

                        <!-- Decimal Picker -->
                        <div class="relative h-full w-20">
                            <div 
                                ref="decimalRef"
                                @scroll="onDecimalScroll"
                                class="h-full overflow-y-auto no-scrollbar snap-y snap-mandatory py-[96px]"
                            >
                                <div v-for="dec in decimals" :key="dec" 
                                    class="h-[48px] flex items-center justify-center snap-center transition-all duration-300"
                                    :class="Math.round((localWeight % 1) * 10) === dec ? 'text-3xl font-black text-[var(--text-main)]' : 'text-sm font-bold text-[var(--text-muted)] opacity-20'"
                                >
                                    {{ dec }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-4 pb-safe shrink-0">
                    <button @click="cancelWeightEdit" class="flex-1 py-5 bg-[var(--page-bg)] text-[var(--text-muted)] font-black italic uppercase tracking-widest rounded-[28px] active:scale-95 transition-all border border-[var(--border-color)]">Cancel</button>
                    <button @click="saveWeightToServer(localWeight)" class="flex-1 py-5 bg-[var(--theme-color)] text-white font-black italic uppercase tracking-widest rounded-[28px] shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all">Update Weight</button>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<style scoped>
@keyframes slide-up {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
@keyframes slide-down {
    from { transform: translateY(-100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.animate-slide-up {
    animation: slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.animate-slide-down {
    animation: slide-down 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
/* Hide spin buttons for numeric inputs */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    appearance: none;
    -webkit-appearance: none;
    margin: 0;
}
input[type=number] {
    appearance: textfield;
    -moz-appearance: textfield;
}
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 10px;
}
.custom-scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
