<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, onMounted, computed } from 'vue';
import { useI18n } from '@/language';

const { t, currentLanguage } = useI18n();

const workoutHistory = ref([]);
const activeTimeframe = ref('week'); // 'week' | 'month' | 'year'

onMounted(() => {
    const savedHistory = localStorage.getItem('fitpung_workout_history');
    if (savedHistory) {
        workoutHistory.value = JSON.parse(savedHistory);
    }
});

const categorizeExercise = (name) => {
    const n = (name || '').toLowerCase();
    // Check Legs first
    if (n.includes('squat') || n.includes('leg') || n.includes('calf') || n.includes('lunge') || n.includes('glute') || n.includes('hip') || n.includes('thigh') || n.includes('quad')) return 'Legs';
    
    // Pull category (Back/Biceps)
    if (n.includes('row') || n.includes('pull') || n.includes('bicep') || n.includes('curl') || n.includes('deadlift') || n.includes('lat') || n.includes('back')) return 'Pull';

    // Push category (Chest/Shoulders/Triceps)
    if (n.includes('bench') || n.includes('press') || n.includes('push') || n.includes('tricep') || n.includes('lateral') || n.includes('shoulder') || n.includes('chest')) return 'Push';
    
    // Core & Cardio
    if (n.includes('crunch') || n.includes('plank') || n.includes('abs') || n.includes('core') || n.includes('sit up')) return 'Core';
    if (n.includes('treadmill') || n.includes('run') || n.includes('cycle') || n.includes('elliptical') || n.includes('cardio') || n.includes('bike') || n.includes('stair')) return 'Cardio';
    
    return 'Other';
};

const periods = computed(() => {
    const now = new Date();
    let cutoff = new Date();
    let prevCutoff = new Date();
    
    if (activeTimeframe.value === 'week') {
        cutoff.setDate(now.getDate() - 7);
        prevCutoff.setDate(cutoff.getDate() - 7);
    } else if (activeTimeframe.value === 'month') {
        cutoff.setMonth(now.getMonth() - 1);
        prevCutoff.setMonth(cutoff.getMonth() - 1);
    } else if (activeTimeframe.value === 'year') {
        cutoff.setFullYear(now.getFullYear() - 1);
        prevCutoff.setFullYear(cutoff.getFullYear() - 1);
    }
    
    return { now, cutoff, prevCutoff };
});

const filteredHistory = computed(() => {
    const { cutoff } = periods.value;
    return workoutHistory.value.filter(entry => new Date(entry.id) >= cutoff);
});

const prevFilteredHistory = computed(() => {
    const { cutoff, prevCutoff } = periods.value;
    return workoutHistory.value.filter(entry => {
        const d = new Date(entry.id);
        return d >= prevCutoff && d < cutoff;
    });
});

const calculateStats = (history) => {
    const uniqueDays = new Set(history.map(entry => new Date(entry.id).toDateString())).size;
    const totalSets = history.reduce((sum, entry) => sum + (entry.sets || 0), 0);
    const totalHours = (totalSets * 2.5 / 60).toFixed(1);
    
    // Category Breakdown
    const categories = { Push: 0, Pull: 0, Legs: 0, Core: 0, Cardio: 0, Other: 0 };
    history.forEach(entry => {
        if (entry.exercises) {
            entry.exercises.forEach(ex => {
                const cat = categorizeExercise(ex.name);
                if (categories[cat] !== undefined) {
                    categories[cat] += (ex.sets?.length || 0);
                }
            });
        }
    });

    return { days: uniqueDays, sets: totalSets, hours: totalHours, categories };
};

const stats = computed(() => calculateStats(filteredHistory.value));
const prevStats = computed(() => calculateStats(prevFilteredHistory.value));

const getPercentage = (current, prev) => {
    const c = parseFloat(current) || 0;
    const p = parseFloat(prev) || 0;
    if (p === 0) return c > 0 ? '+100%' : '+0%';
    const diff = ((c - p) / p) * 100;
    return (diff >= 0 ? '+' : '') + Math.round(diff) + '%';
};

// Volume Distribution for Bottom Section
const volumeDistribution = computed(() => {
    const currentCats = stats.value.categories;
    const total = Object.values(currentCats).reduce((sum, v) => sum + v, 0);
    
    return Object.keys(currentCats).map(cat => {
        const value = currentCats[cat];
        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
        
        // Also keep growth for comparison
        const prevValue = prevStats.value.categories[cat] || 0;
        const growth = getPercentage(value, prevValue);
        
        return {
            name: cat,
            value,
            percentage,
            growth,
            isActive: value > 0
        };
    });
});

// Graph Data Logic
const graphPoints = computed(() => {
    const history = filteredHistory.value;
    const now = new Date();
    
    if (activeTimeframe.value === 'week') {
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const counts = Array(7).fill(0);
        
        history.forEach(entry => {
            const entryDate = new Date(entry.id);
            const diffDays = Math.floor((now - entryDate) / (1000 * 60 * 60 * 24));
            if (diffDays < 7) {
                const dayIndex = entryDate.getDay();
                counts[dayIndex]++;
            }
        });
        
        const todayIdx = now.getDay();
        return Array.from({ length: 7 }, (_, i) => {
            const idx = (todayIdx - (6 - i) + 7) % 7;
            const count = counts[idx];
            return {
                x: (i / 6) * 300,
                y: 100 - (Math.min(count, 5) * 15) - 10,
                label: days[idx],
                active: count > 0
            };
        });
    } else if (activeTimeframe.value === 'month') {
        // Show last 4 weeks
        const weekCounts = [0, 0, 0, 0];
        history.forEach(entry => {
            const entryDate = new Date(entry.id);
            const diffDays = Math.floor((now - entryDate) / (1000 * 60 * 60 * 24));
            const weekIdx = Math.floor(diffDays / 7);
            if (weekIdx >= 0 && weekIdx < 4) {
                weekCounts[3 - weekIdx]++;
            }
        });
        
        return weekCounts.map((count, i) => ({
            x: (i / 3) * 300,
            y: 100 - (Math.min(count, 10) * 8) - 10,
            label: `W${i + 1}`,
            active: count > 0
        }));
    } else if (activeTimeframe.value === 'year') {
        // Show last 6 months (to keep it readable on mobile) or 12? Let's do 6 for better spacing
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthCounts = Array(6).fill(0);
        const labels = [];
        
        for (let i = 5; i >= 0; i--) {
            const d = new Date();
            d.setMonth(now.getMonth() - i);
            labels.push(months[d.getMonth()]);
            
            history.forEach(entry => {
                const entryDate = new Date(entry.id);
                if (entryDate.getMonth() === d.getMonth() && entryDate.getFullYear() === d.getFullYear()) {
                    monthCounts[5 - i]++;
                }
            });
        }
        
        return monthCounts.map((count, i) => ({
            x: (i / 5) * 300,
            y: 100 - (Math.min(count, 20) * 4) - 10,
            label: labels[i],
            active: count > 0
        }));
    }
    
    return [];
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

// Daily Breakdown Section
const dailyHistory = computed(() => {
    const now = new Date();
    const history = workoutHistory.value;
    const days = [];
    
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const thaiDays = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    
    for (let i = 6; i >= 0; i--) {
        const d = new Date();
        d.setDate(now.getDate() - i);
        const dateStr = d.toDateString();
        
        const sessions = history.filter(entry => new Date(entry.id).toDateString() === dateStr);
        const categories = new Set();
        let totalSets = 0;
        
        sessions.forEach(session => {
            totalSets += session.sets || 0;
            if (session.exercises) {
                session.exercises.forEach(ex => {
                    categories.add(categorizeExercise(ex.name));
                });
            }
        });
        
        days.push({
            date: d,
            label: dayNames[d.getDay()],
            thaiLabel: thaiDays[d.getDay()],
            isToday: i === 0,
            categories: Array.from(categories),
            sets: totalSets,
            hasData: sessions.length > 0
        });
    }
    
    return days;
});

const viewMode = ref('list'); // 'list' | 'calendar'
const currentMonth = ref(new Date());

const monthNamesEN = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const monthNamesTH = [
    'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
];

const thaiMonths = monthNamesTH; // For backward compatibility if any

const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    const days = [];
    const firstDayOfWeek = firstDay.getDay();
    
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
        days.push({ date: new Date(year, month, -i), isCurrentMonth: false });
    }
    
    for (let i = 1; i <= lastDay.getDate(); i++) {
        days.push({ date: new Date(year, month, i), isCurrentMonth: true });
    }
    
    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
        days.push({ date: new Date(year, month + 1, i), isCurrentMonth: false });
    }
    
    return days.map(day => {
        const dateStr = day.date.toDateString();
        const sessions = workoutHistory.value.filter(entry => new Date(entry.id).toDateString() === dateStr);
        let totalSets = 0;
        const categories = new Set();
        
        sessions.forEach(session => {
            totalSets += session.sets || 0;
            if (session.exercises) {
                session.exercises.forEach(ex => {
                    categories.add(categorizeExercise(ex.name));
                });
            }
        });
        
        return {
            ...day,
            hasData: sessions.length > 0,
            sets: totalSets,
            categories: Array.from(categories),
            isToday: day.date.toDateString() === new Date().toDateString()
        };
    });
});

const nextMonth = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1);
};

const prevMonth = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1);
};

// Workout Detail Modal Logic
const isDetailModalOpen = ref(false);
const selectedDate = ref(null);
const selectedDateSessions = ref([]);

const showWorkoutDetails = (date) => {
    selectedDate.value = date;
    const dateStr = date.toDateString();
    selectedDateSessions.value = workoutHistory.value.filter(entry => 
        new Date(entry.id).toDateString() === dateStr
    );
    
    if (selectedDateSessions.value.length > 0) {
        isDetailModalOpen.value = true;
    }
};

const formatDateLocal = (date) => {
    if (!date) return '';
    if (currentLanguage.value === 'TH') {
        const day = date.getDate();
        const month = monthNamesTH[date.getMonth()];
        const year = date.getFullYear() + 543;
        const thaiDayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        return `วัน${thaiDayNames[date.getDay()]}ที่ ${day} ${month} พ.ศ. ${year}`;
    } else {
        return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
};
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Performance" />

        <header class="flex items-center justify-between px-6 pt-8 pb-4 transition-colors">
            <div class="flex flex-col">
                <span class="text-sm font-black uppercase tracking-tight text-[var(--theme-color)]">{{ t('home.elite') }}</span>
                <h1 class="text-3xl font-black tracking-tighter uppercase italic text-[var(--text-main)] leading-none mt-1 transition-colors">{{ t('stats.title') }}</h1>
            </div>
            <div class="relative">
                <div class="size-10 rounded-full border-2 border-[var(--theme-color)] p-0.5">
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjuchVWDk_IRP1TbfrAkzG8C4dA-u_ZSX_bmaJ7iTLsz349d2YCZwMsRA1jv1NHNq-FTa1WuuTrctIi_d9WHJb2VI1NZrJ3p_BqZcczzKpP4SZPQj3B_XX6EDlPU5fbHMh9GznMXlc3-Koi2GaRlBBu-73j1pHp39bRwxLX-V_fo3bm3pe---4bpS8o-nSgL6mxkoqqAL8GatxFr8B0_Jqchl4PZb4VDP9b3_v-iSeR5UM_i9ZA9WxigaAtHyyyxzav-yqEqFoT0U" class="size-full rounded-full object-cover">
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="px-6">
            <!-- Time Filter -->
            <div class="mt-4 flex p-1 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)] transition-colors">
                <button 
                    @click="activeTimeframe = 'week'"
                    :class="[
                        'flex-1 py-2 text-xs font-black rounded-xl uppercase tracking-widest transition-all',
                        activeTimeframe === 'week' ? 'bg-[var(--card-bg)] shadow-sm text-[var(--text-main)]' : 'text-[var(--text-muted)]'
                    ]"
                >{{ t('stats.week') }}</button>
                <button 
                    @click="activeTimeframe = 'month'"
                    :class="[
                        'flex-1 py-2 text-xs font-black rounded-xl uppercase tracking-widest transition-all',
                        activeTimeframe === 'month' ? 'bg-[var(--card-bg)] shadow-sm text-[var(--text-main)]' : 'text-[var(--text-muted)]'
                    ]"
                >{{ t('stats.month') }}</button>
                <button 
                    @click="activeTimeframe = 'year'"
                    :class="[
                        'flex-1 py-2 text-xs font-black rounded-xl uppercase tracking-widest transition-all',
                        activeTimeframe === 'year' ? 'bg-[var(--card-bg)] shadow-sm text-[var(--text-main)]' : 'text-[var(--text-muted)]'
                    ]"
                >{{ t('stats.year') }}</button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-3 mt-6">
                <div class="flex flex-col items-center justify-center p-4 bg-[var(--card-bg)] rounded-[24px] border border-[var(--border-color)] shadow-[0_4px_20px_rgba(0,0,0,0.03)] transition-colors">
                    <span class="text-xs font-black text-[var(--text-muted)] uppercase tracking-wider mb-1 transition-colors text-center leading-tight">{{ t('stats.workout_days') }}</span>
                    <span class="text-2xl font-black text-[var(--theme-color)] italic leading-none">{{ stats.days }}</span>
                </div>
                <div class="flex flex-col items-center justify-center p-4 bg-[var(--card-bg)] rounded-[24px] border border-[var(--border-color)] shadow-[0_4px_20px_rgba(0,0,0,0.03)] transition-colors text-center">
                    <span class="text-xs font-black text-[var(--text-muted)] uppercase tracking-wider mb-1 transition-colors leading-tight">{{ t('stats.total_sets') }}</span>
                    <span class="text-2xl font-black text-[var(--text-main)] italic leading-none transition-colors">{{ stats.sets }}</span>
                </div>
                <div class="flex flex-col items-center justify-center p-4 bg-[var(--card-bg)] rounded-[24px] border border-[var(--border-color)] shadow-[0_4px_20px_rgba(0,0,0,0.03)] transition-colors text-center">
                    <span class="text-xs font-black text-[var(--text-muted)] uppercase tracking-wider mb-1 transition-colors leading-tight">{{ t('stats.active_time') }}</span>
                    <span class="text-2xl font-black text-[var(--text-main)] italic leading-none transition-colors">{{ stats.hours }}</span>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-black uppercase tracking-wider text-[var(--text-muted)] transition-colors">{{ t('stats.consistency') }}</h2>
                    <span class="text-xs font-bold text-[var(--theme-color)] uppercase">{{ t('stats.avg') }} {{ (stats.sets / 4).toFixed(1) }}/{{ t('stats.wk') }}</span>
                </div>
                <div class="bg-[var(--card-bg)] rounded-[32px] p-6 border border-[var(--border-color)] shadow-[0_8px_30px_rgba(0,0,0,0.04)] transition-colors">
                    <div class="relative h-40 w-full mb-4">
                        <svg class="w-full h-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 300 100">
                            <!-- Helper Lines -->
                            <line x1="0" y1="80" x2="300" y2="80" stroke="var(--border-color)" stroke-width="1" class="transition-colors" />
                            <line x1="0" y1="40" x2="300" y2="40" stroke="var(--border-color)" stroke-width="1" class="transition-colors" />
                            
                            <!-- Main Path -->
                            <path 
                                :d="svgPath" 
                                fill="none" 
                                stroke="var(--theme-color)" 
                                stroke-width="5" 
                                stroke-linecap="round" 
                                class="transition-all duration-1000"
                            />
                            
                            <!-- Points -->
                            <template v-for="(p, i) in graphPoints" :key="i">
                                <circle 
                                    v-if="p.active"
                                    :cx="p.x" 
                                    :cy="p.y" 
                                    fill="var(--theme-color)" 
                                    r="5" 
                                    stroke="white" 
                                    stroke-width="3" 
                                    class="shadow-sm"
                                />
                            </template>
                        </svg>
                    </div>
                    <!-- X-Axis Labels -->
                    <div class="flex justify-between px-1 text-[8px] font-black text-[var(--text-muted)] uppercase tracking-wider transition-colors">
                        <span v-for="(p, i) in graphPoints" :key="i" class="transition-colors">{{ p.label }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 mb-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-black uppercase tracking-wider text-[var(--text-muted)] transition-colors">{{ t('stats.daily_split') }}</h2>
                    <div class="flex p-1 bg-[var(--page-bg)] rounded-xl border border-[var(--border-color)]">
                        <button 
                            @click="viewMode = 'list'"
                            :class="[
                                'px-3 py-1 text-xs font-black uppercase rounded-lg transition-all',
                                viewMode === 'list' ? 'bg-[var(--card-bg)] text-[var(--text-main)] shadow-sm' : 'text-[var(--text-muted)]'
                            ]"
                        >{{ t('stats.list') }}</button>
                        <button 
                            @click="viewMode = 'calendar'"
                            :class="[
                                'px-3 py-1 text-xs font-black uppercase rounded-lg transition-all',
                                viewMode === 'calendar' ? 'bg-[var(--card-bg)] text-[var(--text-main)] shadow-sm' : 'text-[var(--text-muted)]'
                            ]"
                        >{{ t('stats.calendar') }}</button>
                    </div>
                </div>
                <div v-if="viewMode === 'list'" class="space-y-3">
                    <div v-for="day in dailyHistory" :key="day.label" 
                        @click="day.hasData && showWorkoutDetails(day.date)"
                        class="bg-[var(--card-bg)] p-4 rounded-[24px] border border-[var(--border-color)] flex items-center justify-between shadow-sm transition-colors cursor-pointer active:scale-[0.98]"
                        :class="[
                            {'border-[var(--theme-color)]/30 ring-1 ring-[var(--theme-color)]/10': day.isToday},
                            day.hasData ? '' : 'opacity-60 grayscale-[0.5]'
                        ]"
                    >
                        <div class="flex items-center gap-4">
                            <div class="size-10 rounded-2xl flex flex-col items-center justify-center transition-colors"
                                :class="day.hasData ? 'bg-[var(--theme-color)] text-white' : 'bg-[var(--page-bg)] text-[var(--text-muted)]'"
                            >
                                <span class="text-[8px] font-black uppercase leading-none mb-1">{{ day.label }}</span>
                                <span class="text-sm font-black italic leading-none">{{ day.date.getDate() }}</span>
                            </div>
                            <div>
                                <span class="text-xs font-black uppercase text-[var(--text-main)] tracking-wider leading-none mb-2 transition-colors">
                                    {{ currentLanguage === 'TH' ? day.thaiLabel : day.label }} {{ day.isToday ? '(' + t('common.today') + ')' : '' }}
                                </span>
                                <div class="flex flex-wrap gap-1.5" v-if="day.hasData">
                                    <span v-for="cat in day.categories" :key="cat" 
                                        class="px-2 py-1 bg-[var(--page-bg)] text-[10px] font-black uppercase tracking-wider text-[var(--text-muted)] rounded-lg border border-[var(--border-color)] transition-colors"
                                    >{{ cat }}</span>
                                </div>
                                <span v-else class="text-xs font-bold text-[var(--text-muted)]/50 uppercase tracking-widest italic transition-colors">{{ t('stats.no_workout') }}</span>
                            </div>
                        </div>
                        <div v-if="day.hasData" class="text-right">
                            <p class="text-base font-black text-[var(--text-main)] leading-none transition-colors">{{ day.sets }}</p>
                            <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest transition-colors">{{ t('stats.sets') }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-[var(--card-bg)] p-6 rounded-[32px] border border-[var(--border-color)] shadow-sm transition-colors">
                    <!-- Calendar Header -->
                    <div class="flex items-center justify-between mb-6">
                        <button @click="prevMonth" class="p-2 hover:bg-[var(--page-bg)] rounded-xl transition-colors">
                            <svg class="w-4 h-4 text-[var(--text-main)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="text-center">
                            <h3 class="text-base font-black uppercase tracking-wider text-[var(--text-main)] leading-none">
                                {{ currentLanguage === 'TH' ? monthNamesTH[currentMonth.getMonth()] : monthNamesEN[currentMonth.getMonth()] }}
                            </h3>
                            <span class="text-xs font-bold text-[var(--text-muted)] uppercase tracking-tight">{{ currentLanguage === 'TH' ? currentMonth.getFullYear() + 543 : currentMonth.getFullYear() }}</span>
                        </div>
                        <button @click="nextMonth" class="p-2 hover:bg-[var(--page-bg)] rounded-xl transition-colors">
                            <svg class="w-4 h-4 text-[var(--text-main)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Weekdays -->
                    <div class="grid grid-cols-7 mb-4 px-1">
                        <span v-for="d in (currentLanguage === 'TH' ? ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'] : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])" :key="d" 
                            class="text-[9px] font-black text-center text-[var(--text-muted)] uppercase tracking-wider"
                        >{{ d }}</span>
                    </div>

                    <!-- Days Grid -->
                    <div class="grid grid-cols-7 gap-2">
                        <div v-for="(day, idx) in calendarDays" :key="idx" 
                            @click="day.hasData && showWorkoutDetails(day.date)"
                            class="relative aspect-square flex items-center justify-center rounded-xl transition-all"
                            :class="[
                                !day.isCurrentMonth ? 'opacity-20' : '',
                                day.isToday ? 'ring-2 ring-[var(--theme-color)] ring-offset-2 ring-offset-[var(--card-bg)]' : '',
                                day.hasData ? 'bg-[var(--theme-color)] text-white shadow-lg cursor-pointer active:scale-90' : 'bg-[var(--page-bg)] text-[var(--text-muted)]'
                            ]"
                        >
                            <span class="text-[11px] font-black italic">{{ day.date.getDate() }}</span>
                            
                            <!-- Dots for workouts -->
                            <div v-if="day.hasData" class="absolute bottom-1 flex gap-0.5 justify-center w-full">
                                <div class="size-1 rounded-full bg-white/60"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Detail Modal -->
        <div v-if="isDetailModalOpen" 
            class="fixed inset-0 z-[100] flex items-end justify-center px-4 pb-10 sm:items-center sm:p-0"
        >
            <div class="fixed inset-0 bg-black/40 backdrop-blur-md transition-opacity" @click="isDetailModalOpen = false"></div>
            
            <div class="relative w-full max-w-lg bg-[var(--page-bg)] rounded-[40px] shadow-2xl overflow-hidden animate-slide-up border border-white/20">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <span class="text-xs font-black uppercase tracking-wider text-[var(--theme-color)]">{{ t('stats.details') }}</span>
                            <h2 class="text-xl font-black text-[var(--text-main)] italic uppercase mt-1">
                                {{ formatDateLocal(selectedDate) }}
                            </h2>
                        </div>
                        <button @click="isDetailModalOpen = false" class="size-10 rounded-full bg-[var(--card-bg)] border border-[var(--border-color)] flex items-center justify-center text-[var(--text-muted)] active:scale-95 transition-all">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto space-y-6 pr-2 custom-scrollbar">
                        <div v-for="(session, sIdx) in selectedDateSessions" :key="sIdx" class="space-y-4">
                            <div v-for="(ex, eIdx) in session.exercises" :key="eIdx" 
                                class="bg-[var(--card-bg)] p-6 rounded-[32px] border border-[var(--border-color)] shadow-sm"
                            >
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-black uppercase tracking-wider text-[var(--text-main)]">{{ ex.name }}</h3>
                                    <span class="px-2 py-0.5 bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[8px] font-black uppercase rounded-lg border border-[var(--theme-color)]/20">
                                        {{ session.type || 'Strength' }}
                                    </span>
                                </div>
                                
                                <div class="space-y-2">
                                    <div v-for="(set, setIdx) in ex.sets" :key="setIdx" 
                                        class="flex items-center justify-between p-3 bg-[var(--page-bg)] rounded-xl border border-[var(--border-color)]/50"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="size-5 rounded-md bg-[var(--theme-color)] text-[10px] font-black text-white flex items-center justify-center italic">
                                                {{ setIdx + 1 }}
                                            </span>
                                            <span class="text-xs font-bold text-[var(--text-muted)]">{{ t('stats.sets') }} {{ setIdx + 1 }}</span>
                                        </div>
                                        <div class="flex gap-4">
                                            <div class="text-right">
                                                <p class="text-xs font-black text-[var(--text-main)] leading-none italic">{{ set.weight }} {{ t('common.kg') }}</p>
                                                <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-0.5">{{ t('stats.weight') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs font-black text-[var(--text-main)] leading-none italic">{{ set.reps }}</p>
                                                <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-0.5">{{ t('stats.reps') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<style scoped>
.transition-all {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.animate-slide-up {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
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
</style>
