<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { ref, onMounted, computed } from 'vue';

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
</script>

<template>
    <MobileLayout>
        <Head title="FitPung - Performance" />

        <!-- Header -->
        <header class="flex items-center justify-between px-6 pt-8 pb-4">
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)]">FitPung Elite</span>
                <h1 class="text-3xl font-black tracking-tighter uppercase italic text-gray-900 leading-none mt-1">Performance</h1>
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
            <div class="mt-4 flex p-1 bg-gray-50 rounded-2xl border border-gray-100">
                <button 
                    @click="activeTimeframe = 'week'"
                    :class="[
                        'flex-1 py-2 text-[10px] font-black rounded-xl uppercase tracking-widest transition-all',
                        activeTimeframe === 'week' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400'
                    ]"
                >Week</button>
                <button 
                    @click="activeTimeframe = 'month'"
                    :class="[
                        'flex-1 py-2 text-[10px] font-black rounded-xl uppercase tracking-widest transition-all',
                        activeTimeframe === 'month' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400'
                    ]"
                >Month</button>
                <button 
                    @click="activeTimeframe = 'year'"
                    :class="[
                        'flex-1 py-2 text-[10px] font-black rounded-xl uppercase tracking-widest transition-all',
                        activeTimeframe === 'year' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400'
                    ]"
                >Year</button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-3 mt-6">
                <div class="flex flex-col items-center justify-center p-4 bg-white rounded-[24px] border border-gray-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Workout Days</span>
                    <span class="text-2xl font-black text-[var(--theme-color)] italic leading-none">{{ stats.days }}</span>
                    <span class="text-[9px] font-bold text-gray-400 mt-1">วันที่มาเล่น</span>
                </div>
                <div class="flex flex-col items-center justify-center p-4 bg-white rounded-[24px] border border-gray-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Sets</span>
                    <span class="text-2xl font-black text-gray-900 italic leading-none">{{ stats.sets }}</span>
                    <span class="text-[9px] font-bold text-gray-400 mt-1">เซ็ตทั้งหมด</span>
                </div>
                <div class="flex flex-col items-center justify-center p-4 bg-white rounded-[24px] border border-gray-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Active Time</span>
                    <span class="text-2xl font-black text-gray-900 italic leading-none">{{ stats.hours }}</span>
                    <span class="text-[9px] font-bold text-gray-400 mt-1">ชั่วโมง (โดยประมาณ)</span>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Consistency (ความสม่ำเสมอ)</h2>
                    <span class="text-[10px] font-bold text-[var(--theme-color)] uppercase">Avg {{ (stats.sets / 4).toFixed(1) }}/wk</span>
                </div>
                <div class="bg-white rounded-[32px] p-6 border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.04)]">
                    <div class="relative h-40 w-full mb-4">
                        <svg class="w-full h-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 300 100">
                            <!-- Helper Lines -->
                            <line x1="0" y1="80" x2="300" y2="80" stroke="#f3f4f6" stroke-width="1" />
                            <line x1="0" y1="40" x2="300" y2="40" stroke="#f3f4f6" stroke-width="1" />
                            
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
                    <div class="flex justify-between px-1 text-[8px] font-black text-gray-400 uppercase tracking-widest">
                        <span v-for="(p, i) in graphPoints" :key="i">{{ p.label }}</span>
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
</style>
