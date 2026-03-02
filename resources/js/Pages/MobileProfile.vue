<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { computed, onMounted, ref, watch, nextTick } from 'vue';
import { useI18n } from '@/language';

const { t, currentLanguage, toggleLanguage } = useI18n();

const props = defineProps({
    weightHistories: {
        type: Array,
        default: () => []
    },
    activePackage: {
        type: Object,
        default: null
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
const isFullHistoryModalOpen = ref(false);
const currentWeekIndex = ref(0);
const historyWeekOffset = ref(0);
const errors = ref({});
const editForm = ref({
    name: '',
    height: 0,
    goal: ''
});

const isReviewModalOpen = ref(false);
const reviewForm = ref({
    trainer_id: '',
    rating: 5,
    comment: ''
});

const openReviewModal = (trainerId) => {
    reviewForm.value.trainer_id = trainerId;
    isReviewModalOpen.value = true;
};

const submitReview = () => {
    router.post('/api/trainer/review', reviewForm.value, {
        onSuccess: () => {
            isReviewModalOpen.value = false;
            alert('Review submitted! Thank you.');
        }
    });
};

const latestWeightRecord = computed(() => {
    if (!props.weightHistories || props.weightHistories.length === 0) return { weight: user.value.weight || 0, created_at: new Date() };
    return [...props.weightHistories].sort((a, b) => {
        const dateDiff = new Date(b.created_at) - new Date(a.created_at);
        if (dateDiff !== 0) return dateDiff;
        return b.id - a.id;
    })[0];
});

const weightDiff = computed(() => {
    const sorted = [...props.weightHistories].sort((a, b) => {
        const dateDiff = new Date(b.created_at) - new Date(a.created_at);
        if (dateDiff !== 0) return dateDiff;
        return b.id - a.id;
    });
    
    if (sorted.length < 2) return null;
    
    // Total Change is global since the first record
    const latest = sorted[0].weight;
    const first = sorted[sorted.length - 1].weight;
    const diff = latest - first;
    
    return {
        value: Math.abs(diff).toFixed(1),
        isIncrease: diff > 0,
        isDecrease: diff < 0,
        type: diff < 0 ? 'success' : (diff > 0 ? 'warning' : 'neutral')
    };
});

const weeklyProgress = computed(() => {
    const points = graphPoints.value;
    if (points.length < 2) return null;
    
    // Find the first and last points that have either real data or are the first/last of the week
    const firstWeight = points[0].weight;
    const lastWeight = points[points.length - 1].weight;
    const diff = lastWeight - firstWeight;
    
    return {
        value: Math.abs(diff).toFixed(1),
        isIncrease: diff > 0,
        isDecrease: diff < 0
    };
});

const totalChange = computed(() => {
    if (!props.weightHistories || props.weightHistories.length < 2) return null;
    const sorted = [...props.weightHistories].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    const currentWeight = (latestWeightRecord.value?.weight || user.value.weight);
    const firstWeight = sorted[0].weight;
    const diff = currentWeight - firstWeight;
    return {
        value: Math.abs(diff).toFixed(1),
        isIncrease: diff > 0,
        isDecrease: diff < 0
    };
});

// Helper to get local date key YYYY-MM-DD
const getLocalDateKey = (date) => {
    if (!date) return '';
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) return date;
    if (typeof date === 'string' && date.includes('T')) return date.split('T')[0];

    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getMonday = (date) => {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    const day = d.getDay() || 7; // 1-7 (Mon-Sun)
    d.setDate(d.getDate() - day + 1);
    return d;
};

const weeklyHistoryTableData = computed(() => {
    if (!historyByWeek.value || historyByWeek.value.length === 0) return [];
    const week = historyByWeek.value[historyWeekOffset.value];
    return week ? week.entries.sort((a,b) => new Date(b.created_at) - new Date(a.created_at)) : [];
});

const currentWeekLabel = computed(() => {
    if (!historyByWeek.value || historyByWeek.value.length === 0) return '';
    const week = historyByWeek.value[historyWeekOffset.value];
    return week ? week.key : '';
});

const sortedHistory = computed(() => {
    return [...props.weightHistories].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const graphPoints = computed(() => {
    if (!props.weightHistories || props.weightHistories.length === 0) return [];

    // If we are on the main profile view (offset 0), show trailing history of the last 7 logged days
    // to ensure a meaningful trend graph is visible even if the user hasn't logged much this week.
    if (historyWeekOffset.value === 0 && props.weightHistories.length >= 2) {
        const entriesByDate = new Map();
        [...props.weightHistories]
            .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
            .forEach(h => {
                const dateKey = getLocalDateKey(h.created_at);
                entriesByDate.set(dateKey, { ...h, dateKey });
            });
        
        const dailyEntries = Array.from(entriesByDate.values());
        const recentEntries = dailyEntries.slice(-7);
        const width = 300;
        const weights = recentEntries.map(e => Number(e.weight));
        
        const minW = Math.min(...weights);
        const maxW = Math.max(...weights);
        const range = Math.max(maxW - minW, 2);
        
        const nowKey = getLocalDateKey(new Date());

        return recentEntries.map((e, i) => {
            const date = new Date(e.created_at);
            const isToday = e.dateKey === nowKey;
            return {
                x: (i / Math.max(recentEntries.length - 1, 1)) * width,
                y: 100 - ((e.weight - minW) / range) * 70 - 15,
                weight: e.weight,
                active: true,
                isToday: isToday,
                label: isToday ? t('common.present') : date.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short' }),
                dateLabel: date.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short' })
            };
        });
    }

    const currentWeek = historyByWeek.value[historyWeekOffset.value];
    if (!currentWeek) return [];
    
    const entriesByDate = new Map();
    [...props.weightHistories]
        .sort((a, b) => {
            const dateDiff = new Date(a.created_at) - new Date(b.created_at);
            if (dateDiff !== 0) return dateDiff;
            return a.id - b.id;
        })
        .forEach(h => {
            const dateKey = getLocalDateKey(h.created_at);
            entriesByDate.set(dateKey, h);
        });
    
    // Find last known weight BEFORE this week for interpolation anchor
    const allSorted = [...props.weightHistories].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    let lastKnownGlobal = allSorted.length > 0 ? allSorted[0].weight : (user.value.weight || 0);
    allSorted.forEach(h => {
        if (new Date(h.created_at) < currentWeek.monday) {
            lastKnownGlobal = h.weight;
        }
    });

    const points = [];
    const now = new Date();
    const todayKey = getLocalDateKey(now);
    const isCurrentWeek = historyWeekOffset.value === 0;

    for (let i = 0; i < 7; i++) {
        const dayDate = new Date(currentWeek.monday);
        dayDate.setDate(currentWeek.monday.getDate() + i);
        const dateKey = getLocalDateKey(dayDate);
        
        if (isCurrentWeek && dayDate > now && dateKey !== todayKey) continue;

        let weight = null;
        let active = false;
        
        if (entriesByDate.has(dateKey)) {
            weight = entriesByDate.get(dateKey).weight;
            active = true;
        } else if (dateKey === todayKey) {
            weight = latestWeightRecord.value?.weight || user.value.weight;
            active = true;
        } else if (dayDate < now) {
            weight = lastKnownGlobal;
            active = i === 0 && lastKnownGlobal !== null; // Anchor at Mon
        }

        if (active) lastKnownGlobal = weight;

        points.push({
            x: 0,
            weight: weight || lastKnownGlobal,
            active: active,
            isToday: dateKey === todayKey,
            date: dayDate,
            label: dayDate.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { weekday: 'short' }),
            dateLabel: dayDate.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short' })
        });
    }

    const width = 300;
    const finalPointsCount = points.length;
    points.forEach((p, i) => { p.x = (i / Math.max(finalPointsCount - 1, 1)) * width; });

    const weights = points.map(p => Number(p.weight));
    const minW = Math.min(...weights) - 1;
    const maxW = Math.max(...weights) + 1;
    const r = Math.max(maxW - minW, 2);
    
    return points.map(p => ({
        ...p,
        y: 100 - ((p.weight - minW) / r) * 75 - 15,
        label: p.isToday ? t('common.present') : p.label,
        active: p.active
    }));
});

const fullGraphPoints = computed(() => {
    if (!props.weightHistories || props.weightHistories.length === 0) return [];
    
    // Group histories by date and take the last one for each day
    const entriesByDate = new Map();
    props.weightHistories.forEach(h => {
        const dateKey = new Date(h.created_at).toISOString().split('T')[0];
        entriesByDate.set(dateKey, h);
    });
    
    const dailyHistories = Array.from(entriesByDate.values())
        .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    
    // Show up to 14 latest daily points
    const finalPoints = dailyHistories.slice(-14);
    const weights = finalPoints.map(h => h.weight);
    const minWeight = Math.min(...weights) - 1;
    const maxWeight = Math.max(...weights) + 1;
    const range = Math.max(maxWeight - minWeight, 2);
    
    const width = Math.max(finalPoints.length * 60, 300); // 60px per point for breathing room
    
    return {
        width,
        points: finalPoints.map((h, i) => {
            const x = (i / (finalPoints.length - 1)) * width;
            const y = 150 - ((h.weight - minWeight) / range) * 120 - 20;
            const date = new Date(h.created_at);
            
            return {
                x,
                y,
                weight: Number(h.weight) || 0,
                dateLabel: date.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short' }),
                timeLabel: date.toLocaleTimeString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { hour: '2-digit', minute: '2-digit', hour12: true }).replace(' AM', 'A').replace(' PM', 'P')
            };
        })
    };
});

const historyByWeek = computed(() => {
    const weeks = [];
    const now = new Date();
    const currentMonday = getMonday(now);
    
    // Always ensure the current week is at the top
    const currentSunday = new Date(currentMonday);
    currentSunday.setDate(currentMonday.getDate() + 6);
    currentSunday.setHours(23, 59, 59, 999);
    
    const getWeekLabel = (mon, sun) => {
        return mon.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short' }) + 
               ' - ' + 
               sun.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    weeks.push({
        key: getWeekLabel(currentMonday, currentSunday),
        monday: new Date(currentMonday),
        sunday: new Date(currentSunday),
        entries: []
    });

    if (props.weightHistories) {
        props.weightHistories.forEach(h => {
            const date = new Date(h.created_at);
            const mon = getMonday(date);
            const sun = new Date(mon);
            sun.setDate(mon.getDate() + 6);
            sun.setHours(23, 59, 59, 999);
            
            const weekKey = getWeekLabel(mon, sun);
            let week = weeks.find(w => w.key === weekKey);
            
            if (!week) {
                week = { 
                    key: weekKey, 
                    monday: new Date(mon),
                    sunday: new Date(sun),
                    entries: [] 
                };
                weeks.push(week);
            }
            const entryDate = new Date(h.created_at);
            week.entries.push({
                ...h,
                dayIndex: entryDate.getDay() || 7, // 1-7 (Mon-Sun)
                formattedDate: entryDate.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short', year: 'numeric' }),
                formattedTime: entryDate.toLocaleTimeString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { hour: '2-digit', minute: '2-digit' })
            });
            // Stable sort entries within the week: Newest first
            week.entries.sort((a, b) => {
                const dateDiff = new Date(b.created_at) - new Date(a.created_at);
                if (dateDiff !== 0) return dateDiff;
                return b.id - a.id;
            });
        });
    }
    
    return weeks.sort((a, b) => b.monday - a.monday);
});

const weeklyGraphData = computed(() => {
    const currentWeek = historyByWeek.value[currentWeekIndex.value];
    if (!currentWeek) return { width: 300, points: [] };
    
    const points = [];
    const width = 600; // Fixed width for 7 days
    
    // We need 7 points (Mon-Sun)
    for (let i = 0; i < 7; i++) {
        const dayDate = new Date(currentWeek.monday);
        dayDate.setDate(currentWeek.monday.getDate() + i);
        
        // Find the latest entry for this specific day
        const dayEntries = currentWeek.entries.filter(e => {
            const d = new Date(e.created_at);
            return d.getFullYear() === dayDate.getFullYear() && 
                   d.getMonth() === dayDate.getMonth() && 
                   d.getDate() === dayDate.getDate();
        });
        
        const lastEntry = dayEntries.length > 0 ? dayEntries[dayEntries.length - 1] : null;
        
        points.push({
            x: (i / 6) * width,
            weight: lastEntry ? Number(lastEntry.weight) : null,
            label: dayDate.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { weekday: 'short' }),
            dateLabel: dayDate.toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short' }),
            hasData: !!lastEntry
        });
    }
    
    // Calculate Y positions
    const validWeights = points.filter(p => p.hasData).map(p => p.weight);
    const minWeight = validWeights.length > 0 ? Math.min(...validWeights) - 1 : 0;
    const maxWeight = validWeights.length > 0 ? Math.max(...validWeights) + 1 : 100;
    const range = Math.max(maxWeight - minWeight, 2);
    
    // Fill in gaps for the line path if any
    let lastKnownWeight = null;
    const processedPoints = points.map(p => {
        if (p.hasData) {
            lastKnownWeight = p.weight;
        }
        const displayWeight = p.hasData ? p.weight : lastKnownWeight;
        return {
            ...p,
            y: displayWeight !== null ? 150 - ((displayWeight - minWeight) / range) * 120 - 20 : 150
        };
    });
    
    return {
        width,
        points: processedPoints
    };
});

const weeklySvgPath = computed(() => {
    const data = weeklyGraphData.value.points.filter(p => p.hasData || (p.y !== 150)); // Only draw if we have Y
    if (data.length < 2) return '';
    
    let path = `M ${data[0].x} ${data[0].y}`;
    for (let i = 1; i < data.length; i++) {
        path += ` L ${data[i].x} ${data[i].y}`;
    }
    return path;
});

const recentLogs = computed(() => {
    if (!props.weightHistories) return [];
    return [...props.weightHistories].sort((a, b) => {
        const dateDiff = new Date(b.created_at) - new Date(a.created_at);
        if (dateDiff !== 0) return dateDiff;
        return b.id - a.id;
    }).slice(0, 10); // Show up to 10 for better coverage
});

const svgPath = computed(() => {
    const points = graphPoints.value;
    if (points.length < 2) return '';
    
    const isCurrentWeek = historyWeekOffset.value === 0;
    
    // For historical weeks, we want the line to go all the way to Sunday
    // For current week, we stop at Today
    let lastActiveIndex = points.length - 1;
    if (isCurrentWeek) {
        for (let i = points.length - 1; i >= 0; i--) {
            if (points[i].active) {
                lastActiveIndex = i;
                break;
            }
        }
    }

    if (lastActiveIndex <= 0) return '';

    let d = `M ${points[0].x},${points[0].y}`;
    for (let i = 0; i < lastActiveIndex; i++) {
        const p0 = points[i];
        const p1 = points[i + 1];
        const cp1x = p0.x + (p1.x - p0.x) / 2;
        d += ` C ${cp1x},${p0.y} ${cp1x},${p1.y} ${p1.x},${p1.y}`;
    }
    return d;
});

const fullSvgPath = computed(() => {
    const points = fullGraphPoints.value.points;
    if (!points || points.length < 2) return '';
    
    let d = `M ${points[0].x},${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i];
        const p1 = points[i + 1];
        const cp1x = p0.x + (p1.x - p0.x) / 2;
        d += ` C ${cp1x},${p0.y} ${cp1x},${p1.y} ${p1.x},${p1.y}`;
    }
    return d;
});

const photoPreview = ref(null);
const photoInput = ref(null);

const onPhotoChange = () => {
    const photo = photoInput.value.files[0];
    if (!photo) return;

    editForm.value.photo = photo;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(photo);
};

const openEditModal = () => {
    errors.value = {};
    editForm.value = {
        name: user.value.name,
        height: user.value.height,
        goal: user.value.goal,
        photo: null
    };
    photoPreview.value = user.value.profile_photo_url;
    isEditModalOpen.value = true;
};

const saveProfile = () => {
    errors.value = {};
    
    // Use router.post with _method: 'patch' to support file uploads
    router.post(route('profile.update'), {
        ...editForm.value,
        _method: 'patch'
    }, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            photoPreview.value = null;
        },
        onError: (err) => {
            errors.value = err;
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
        only: ['auth', 'flash', 'weightHistories'],
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
            <h1 class="text-[11px] font-black uppercase tracking-wider text-[var(--text-muted)] transition-colors">{{ t('profile.title') }}</h1>
            <div class="flex items-center gap-2">
                <!-- Language Toggle -->
                <button @click="toggleLanguage" class="px-3 h-10 rounded-full bg-[var(--card-bg)] shadow-sm border border-[var(--border-color)] flex items-center justify-center transition-all active:scale-90 gap-1.5 focus:outline-none">
                    <span class="text-[10px] font-black transition-colors" :class="currentLanguage === 'TH' ? 'text-[var(--theme-color)]' : 'text-[var(--text-muted)]'">TH</span>
                    <div class="w-[1px] h-3 bg-[var(--border-color)]"></div>
                    <span class="text-[10px] font-black transition-colors" :class="currentLanguage === 'EN' ? 'text-[var(--theme-color)]' : 'text-[var(--text-muted)]'">EN</span>
                </button>

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
                            <img :src="user.profile_photo_url" class="size-full object-cover">
                        </div>
                    </div>
                    <div class="absolute bottom-2 right-2 size-8 bg-[var(--theme-color)] rounded-full border-4 border-[var(--app-bg)] flex items-center justify-center text-white transition-colors">
                        <span class="material-symbols-outlined text-sm fill-icon">verified</span>
                    </div>
                </div>
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-black italic uppercase tracking-tighter text-[var(--text-main)] leading-none transition-colors">{{ user.name || 'User' }}</h2>
                    <p class="text-[10px] font-black uppercase tracking-wider text-[var(--theme-color)] mt-3">Elite Athlete • Level 42</p>
                </div>

                <button @click="openEditModal" class="w-full py-5 bg-[var(--theme-color)] text-white font-black italic uppercase tracking-normal rounded-[24px] shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all mb-4 text-base">
                    {{ t('profile.edit') }}
                </button>

                <Link v-if="user.trainer" :href="route('trainer.dashboard')" class="w-full py-5 bg-[#0f172a] text-white font-black italic uppercase tracking-widest rounded-[24px] shadow-xl flex items-center justify-center gap-3 active:scale-95 transition-all mb-10 text-base border border-white/10">
                    <span class="material-symbols-outlined text-[var(--theme-color)]">monitoring</span>
                    Trainer Dashboard
                </Link>
            </div>


            <!-- Quick Stats -->
            <section class="px-6 py-4">
                <div class="grid grid-cols-3 gap-3">
                    <!-- Weight Card -->
                    <div class="bg-[var(--card-bg)] p-4 rounded-[32px] border border-[var(--border-color)] shadow-sm flex flex-col items-center justify-center text-center relative h-[140px] transition-colors">
                        <span class="absolute top-4 text-[8px] font-black uppercase tracking-wider text-[var(--text-muted)]">{{ t('profile.weight') }}</span>
                        <div class="flex flex-col items-center mt-2 relative">
                            <div class="flex items-baseline gap-0.5">
                                <span class="text-2xl font-black text-[var(--theme-color)] italic leading-none">{{ (latestWeightRecord.weight || 0).toFixed(1) }}</span>
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
                        <span class="absolute top-4 text-[8px] font-black uppercase tracking-wider text-[var(--text-muted)]">{{ t('profile.height') }}</span>
                        <div class="flex flex-col items-center mt-2">
                            <div class="flex items-baseline gap-0.5">
                                <span class="text-2xl font-black text-[var(--text-main)] italic leading-none">{{ user.height || 0 }}</span>
                                <span class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest">CM</span>
                            </div>
                        </div>
                    </div>

                    <!-- Goal Section -->
                    <div class="bg-[var(--card-bg)] p-4 rounded-[32px] border border-[var(--border-color)] shadow-sm flex flex-col items-center justify-center text-center relative h-[140px] transition-colors">
                        <span class="absolute top-4 text-[10px] font-black uppercase tracking-wider text-[var(--text-muted)]">{{ t('profile.goal') }}</span>
                        <div class="flex flex-col items-center mt-2">
                        <span class="text-[13px] font-black text-[var(--text-main)] uppercase leading-tight tracking-tighter h-8 flex items-center justify-center">{{ user.goal || 'No Goal' }}</span>
                        </div>
                    </div>
                </div>
            </section>


            <!-- Weight Trend & History Section -->
            <section class="px-6 py-4">
                <!-- Weight Trend Graph -->
                <div class="bg-[var(--card-bg)] rounded-[32px] p-6 border border-[var(--border-color)] shadow-sm transition-colors mb-6">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex flex-col gap-1">
                            <h3 class="text-[12px] font-black uppercase text-[var(--text-main)] tracking-[0.2em] transition-colors">{{ t('profile.weight_trends') }}</h3>
                            <p class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest">{{ historyWeekOffset === 0 ? 'Current Week' : currentWeekLabel }}</p>
                        </div>
                        
                        <div class="flex flex-col items-end gap-1">
                            <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest">
                                {{ historyWeekOffset === 0 ? 'Total Progress' : 'Weekly Progress' }}
                            </span>
                            <div v-if="historyWeekOffset === 0 ? totalChange : weeklyProgress" class="flex items-center gap-1.5 bg-[var(--page-bg)] px-3 py-1.5 rounded-full border border-[var(--border-color)]">
                                <span class="text-[10px] font-black italic" :class="(historyWeekOffset === 0 ? totalChange?.isDecrease : weeklyProgress?.isDecrease) ? 'text-[#22c55e]' : ((historyWeekOffset === 0 ? totalChange?.isIncrease : weeklyProgress?.isIncrease) ? 'text-[#ef4444]' : 'text-[var(--text-muted)]')">
                                    {{ (historyWeekOffset === 0 ? totalChange?.isIncrease : weeklyProgress?.isIncrease) ? '+' : ((historyWeekOffset === 0 ? totalChange?.isDecrease : weeklyProgress?.isDecrease) ? '-' : '') }}{{ (historyWeekOffset === 0 ? totalChange?.value : weeklyProgress?.value) }}
                                </span>
                                <span class="text-[7px] font-black text-[var(--text-muted)] uppercase">KG</span>
                            </div>
                        </div>
                    </div>

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
                            
                            <!-- Dots -->
                            <template v-for="(p, i) in graphPoints" :key="i">
                                <circle 
                                    :cx="p.x" 
                                    :cy="p.y" 
                                    :r="p.isToday ? 6 : 4" 
                                    class="transition-all duration-500 fill-[var(--theme-color)] stroke-[var(--card-bg)] shadow-sm"
                                    :class="p.isToday ? 'stroke-[3px]' : 'stroke-2'"
                                />
                                <text 
                                    :x="p.x" 
                                    :y="p.y - 12" 
                                    text-anchor="middle" 
                                    class="text-[9px] font-black fill-[var(--text-main)] tabular-nums transition-all"
                                >
                                    {{ (p.weight || 0).toFixed(1) }}
                                </text>
                            </template>
                        </svg>
                    </div>
                    <!-- Labels -->
                    <div class="flex justify-between px-1 text-[8px] font-black text-[var(--text-muted)] uppercase tracking-wider transition-colors">
                        <span v-for="(p, i) in graphPoints" :key="i">{{ p.label }}</span>
                    </div>
                </div>

                <!-- Recent Weight History Table -->
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex flex-col gap-1">
                            <h3 class="text-[12px] font-black uppercase text-[var(--text-main)] tracking-[0.2em] transition-colors">
                                {{ t('profile.history') }}
                            </h3>
                            <p class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest">{{ historyWeekOffset === 0 ? t('common.present') : currentWeekLabel }}</p>
                        </div>
                        
                        <!-- Weekly Pagination -->
                        <div class="flex items-center gap-2">
                            <button 
                                @click="historyWeekOffset++" 
                                :disabled="!historyByWeek[historyWeekOffset + 1]"
                                class="size-8 rounded-full bg-[var(--card-bg)] border border-[var(--border-color)] flex items-center justify-center disabled:opacity-20 disabled:pointer-events-none transition-all hover:border-[var(--theme-color)]"
                            >
                                <span class="material-symbols-outlined text-sm">chevron_left</span>
                            </button>
                            <button 
                                @click="historyWeekOffset--" 
                                :disabled="historyWeekOffset <= 0"
                                class="size-8 rounded-full bg-[var(--card-bg)] border border-[var(--border-color)] flex items-center justify-center disabled:opacity-20 disabled:pointer-events-none transition-all hover:border-[var(--theme-color)]"
                            >
                                <span class="material-symbols-outlined text-sm">chevron_right</span>
                            </button>
                        </div>
                    </div>

                    <div class="bg-[var(--card-bg)] rounded-[32px] border border-[var(--border-color)] overflow-hidden shadow-sm">
                        <div v-if="(historyWeekOffset === 0 ? recentLogs : weeklyHistoryTableData).length === 0" class="p-10 text-center">
                            <span class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest">No weight history for this period</span>
                        </div>
                        <div v-else class="divide-y divide-[var(--border-color)]/50">
                            <div v-for="(log, i) in (historyWeekOffset === 0 ? recentLogs : weeklyHistoryTableData)" :key="log.id" 
                                class="flex items-center justify-between p-6 transition-all hover:bg-[var(--theme-color)]/[0.02] active:scale-[0.99]">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-black text-[var(--text-main)] tabular-nums">{{ new Date(log.created_at).toLocaleDateString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { day: 'numeric', month: 'short' }) }}</span>
                                    <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest">{{ new Date(log.created_at).toLocaleTimeString(currentLanguage.value === 'TH' ? 'th-TH' : 'en-US', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                </div>
                                
                                <div class="flex items-baseline gap-0.5 bg-[var(--page-bg)] px-3 py-1.5 rounded-full border border-[var(--border-color)]">
                                    <span class="text-xs font-black text-[var(--theme-color)] italic tabular-nums">{{ (Number(log.weight) || 0).toFixed(1) }}</span>
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
                    <button @click="router.post(route('logout'))" class="w-full flex items-center justify-between p-5 hover:bg-[var(--page-bg)] transition-colors group">
                        <div class="flex items-center gap-4">
                            <span class="material-symbols-outlined text-[var(--text-muted)] group-hover:text-red-500 transition-colors">logout</span>
                            <span class="font-black uppercase text-[10px] tracking-widest text-red-500">Sign Out</span>
                        </div>
                        <span class="material-symbols-outlined text-[var(--text-muted)] font-bold text-sm opacity-20">arrow_forward_ios</span>
                    </button>
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

                                <!-- Photo Upload -->
                                <div class="flex flex-col items-center mb-4">
                                    <div class="relative group">
                                        <div class="size-24 rounded-full border-4 border-[var(--theme-color)]/20 overflow-hidden flex items-center justify-center transition-all group-hover:border-[var(--theme-color)]/50 shadow-lg">
                                            <img v-if="photoPreview" :src="photoPreview" class="size-full object-cover" />
                                            <span v-else class="material-symbols-outlined text-4xl opacity-20">person</span>
                                        </div>
                                        
                                        <button 
                                            type="button" 
                                            @click="$refs.photoInput.click()"
                                            class="absolute bottom-0 right-0 size-8 rounded-full bg-[var(--theme-color)] text-white shadow-lg flex items-center justify-center hover:scale-110 active:scale-95 transition-all"
                                        >
                                            <span class="material-symbols-outlined text-base">photo_camera</span>
                                        </button>
                                    </div>
                                    
                                    <input 
                                        type="file" 
                                        ref="photoInput" 
                                        class="hidden" 
                                        accept="image/*"
                                        @change="onPhotoChange"
                                    />
                                    <p v-if="errors.photo" class="text-[8px] text-red-500 font-bold uppercase tracking-widest mt-2">{{ errors.photo }}</p>
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
                        <span class="text-6xl font-black italic text-[var(--text-main)] tabular-nums transition-colors">{{ (localWeight || 0).toFixed(1) }}</span>
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

        <!-- Full History Modal -->
        <transition name="modal">
            <div v-if="isFullHistoryModalOpen" class="fixed inset-0 z-[150] flex items-end md:items-center justify-center p-0 md:p-8 bg-black/80 backdrop-blur-md">
                <div class="absolute inset-0" @click="isFullHistoryModalOpen = false"></div>
                
                <div class="bg-[var(--card-bg)] w-full max-w-2xl md:rounded-[40px] rounded-t-[40px] overflow-hidden shadow-2xl relative animate-in slide-in-from-bottom-full duration-500 h-[85vh] flex flex-col transition-colors border-t border-[var(--border-color)]">
                    <div class="p-8 border-b border-[var(--border-color)] flex items-center justify-between transition-colors shrink-0">
                        <div>
                            <h3 class="text-2xl font-black uppercase italic text-[var(--text-main)] leading-none transition-colors">Weight Journey</h3>
                            <p class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-[0.2em] mt-3 transition-colors">Detailed Progress Visualization</p>
                        </div>
                        <button @click="isFullHistoryModalOpen = false" class="size-12 rounded-full bg-[var(--page-bg)] flex items-center justify-center transition-all active:scale-90 border border-[var(--border-color)]">
                            <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">close</span>
                        </button>
                    </div>

                    <div class="flex-1 overflow-x-auto overflow-y-hidden no-scrollbar p-8 pb-12 cursor-grab active:cursor-grabbing shrink-0">
                        <div :style="{ width: weeklyGraphData.width + 'px' }" class="h-full relative flex flex-col justify-center">
                            <div class="h-64 w-full relative">
                                <svg class="w-full h-full overflow-visible" :viewBox="`0 0 ${weeklyGraphData.width} 200`" preserveAspectRatio="none">
                                    <!-- Grid Lines -->
                                    <line v-for="i in 5" :key="i" x1="0" :y1="i * 40" :x2="weeklyGraphData.width" :y2="i * 40" stroke="var(--border-color)" stroke-width="1" class="opacity-10" />
                                    
                                    <!-- Path (dashed for missing segments) -->
                                    <path 
                                        :d="weeklySvgPath" 
                                        fill="none" 
                                        stroke="var(--theme-color)" 
                                        stroke-width="5" 
                                        stroke-linecap="round" 
                                        class="transition-all duration-1000"
                                    />
                                    
                                    <!-- Points -->
                                    <template v-for="(p, i) in weeklyGraphData.points" :key="i">
                                        <g v-if="p.hasData" class="transition-all duration-300">
                                            <text 
                                                :x="p.x" 
                                                :y="p.y - 15" 
                                                text-anchor="middle" 
                                                class="text-[12px] font-black fill-[var(--text-main)] tracking-tighter"
                                            >
                                                {{ (p.weight || 0).toFixed(1) }}
                                            </text>
                                            <circle 
                                                :cx="p.x" 
                                                :cy="p.y" 
                                                fill="var(--theme-color)" 
                                                r="7" 
                                                stroke="var(--card-bg)" 
                                                stroke-width="4" 
                                            />
                                        </g>
                                        <!-- Placeholder for missing days -->
                                        <circle v-else :cx="p.x" :cy="p.y" r="2" fill="var(--border-color)" class="opacity-30" />
                                    </template>
                                </svg>
                            </div>
                            
                            <!-- Detailed Labels -->
                            <div class="flex justify-between w-full mt-8 relative h-10">
                                <div v-for="(p, i) in weeklyGraphData.points" :key="i" class="absolute -translate-x-1/2 flex flex-col items-center gap-1" :style="{ left: p.x + 'px' }">
                                    <span class="text-[9px] font-black italic text-[var(--text-main)] uppercase whitespace-nowrap">{{ p.label }}</span>
                                    <span class="text-[7px] font-bold text-[var(--text-muted)] uppercase tracking-tighter whitespace-nowrap">{{ p.dateLabel }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly History Table with Navigation -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar px-8 pb-12 flex flex-col">
                        <div class="flex items-center justify-between mb-8 sticky top-0 bg-[var(--card-bg)] py-4 z-10">
                            <button 
                                @click="currentWeekIndex++" 
                                :disabled="currentWeekIndex >= historyByWeek.length - 1"
                                class="size-10 rounded-full bg-[var(--page-bg)] border border-[var(--border-color)] flex items-center justify-center disabled:opacity-30 disabled:pointer-events-none transition-all"
                            >
                                <span class="material-symbols-outlined text-lg">chevron_left</span>
                            </button>
                            
                            <div class="text-center">
                                <h4 class="text-[12px] font-black uppercase text-[var(--text-main)] tracking-widest transition-colors">
                                    {{ historyByWeek[currentWeekIndex]?.key || 'History' }}
                                </h4>
                                <p class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-[0.2em] mt-1 transition-colors">Weekly Overview</p>
                            </div>

                            <button 
                                @click="currentWeekIndex--" 
                                :disabled="currentWeekIndex <= 0"
                                class="size-10 rounded-full bg-[var(--page-bg)] border border-[var(--border-color)] flex items-center justify-center disabled:opacity-30 disabled:pointer-events-none transition-all"
                            >
                                <span class="material-symbols-outlined text-lg">chevron_right</span>
                            </button>
                        </div>

                        <div v-if="historyByWeek[currentWeekIndex]" class="grid gap-3 mb-8">
                            <div v-for="entry in historyByWeek[currentWeekIndex].entries" :key="entry.id" 
                                class="flex items-center justify-between p-5 bg-[var(--page-bg)] rounded-[24px] border border-[var(--border-color)] transition-all hover:border-[var(--theme-color)]/20 active:scale-[0.98]">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[12px] font-black text-[var(--text-main)] transition-colors">{{ entry.formattedDate }}</span>
                                    <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase italic transition-colors">{{ entry.formattedTime }}</span>
                                </div>
                                <div class="flex items-end gap-1.5">
                                    <span class="text-xl font-black italic text-[var(--text-main)] leading-none transition-colors">{{ (entry.weight || 0).toFixed(1) }}</span>
                                    <span class="text-[9px] font-black text-[var(--text-muted)] mb-0.5 uppercase tracking-tighter">kg</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex-1 flex flex-col items-center justify-center py-20 opacity-20">
                            <span class="material-symbols-outlined text-6xl">history</span>
                            <p class="text-[10px] font-black uppercase tracking-widest mt-4">No data for this period</p>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
        <!-- Review Modal -->
        <div v-if="isReviewModalOpen" class="fixed inset-0 z-[110] flex items-end justify-center bg-black/60 backdrop-blur-md p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">Rate Trainer</h2>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-1">Share your experience</p>
                </div>

                <div class="space-y-6">
                    <div class="flex justify-center gap-2">
                        <button v-for="i in 5" :key="i" @click="reviewForm.rating = i">
                            <span class="material-symbols-outlined text-3xl" :class="i <= reviewForm.rating ? 'text-amber-400 fill-icon' : 'text-[var(--text-muted)]'">star</span>
                        </button>
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Comment</label>
                        <textarea 
                            v-model="reviewForm.comment"
                            rows="4"
                            placeholder="How was the training? Any improvements?"
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        ></textarea>
                    </div>

                    <div class="pt-4 flex flex-col gap-3">
                        <button 
                            @click="submitReview"
                            class="w-full py-4 bg-[var(--theme-color)] text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-[var(--theme-color)]/20 active:scale-[0.98] transition-all"
                        >
                            Submit Review
                        </button>
                        <button 
                            @click="isReviewModalOpen = false"
                            class="w-full py-2 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest"
                        >
                            Cancel
                        </button>
                    </div>
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
