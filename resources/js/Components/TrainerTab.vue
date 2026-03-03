<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from '@/language';

const { t } = useI18n();

const props = defineProps({
    trainers: { type: Array, default: () => [] },
    initialTrainerId: { type: [Number, String], default: null },
    bookings: { type: Array, default: () => [] }
});

onMounted(() => {
    if (props.initialTrainerId) {
        const trainer = mappedTrainers.value.find(t => t.id == props.initialTrainerId);
        if (trainer) {
            selectTrainer(trainer);
        }
    }
});

const emit = defineEmits(['back', 'start-workout']);

const defaultCalendar = [
    { day: 'Mon', slots: ['09:00', '14:00', '18:00'] },
    { day: 'Tue', slots: ['10:00', '15:00'] },
    { day: 'Wed', slots: ['09:00', '18:00'] },
];

const formatImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/images/') || path.startsWith('/storage/')) return path;
    return `/storage/${path}`;
};

const state = ref('list'); // 'list' | 'detail'
const subTab = ref('trainers'); // 'trainers' | 'courses'
const selectedTrainer = ref(null);
const priceFilter = ref(1000); 

const isBookingModalOpen = ref(false);
const isReviewModalOpen = ref(false);

const bookingForm = ref({
    trainer_id: '',
    course_name: '',
    booking_date: '',
    notes: ''
});

const reviewForm = ref({
    trainer_id: '',
    rating: 5,
    comment: ''
});

const trainerSchedules = ref({}); // { trainerId: [schedules] }
const isFetchingSchedules = ref(false);
const currentMonth = ref(new Date());
const selectedDayData = ref(null);
const isDayDetailOpen = ref(false);

const confirmModal = ref({
    isOpen: false,
    title: '',
    message: '',
    type: 'confirm', // 'confirm' | 'success'
    onConfirm: null
});

const showConfirm = (title, message, onConfirm) => {
    confirmModal.value = { isOpen: true, title, message, type: 'confirm', onConfirm };
};

const showSuccess = (title, message) => {
    confirmModal.value = { isOpen: true, title, message, type: 'success', onConfirm: null };
};

const changeMonth = (offset) => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + offset, 1);
};

const daysInMonth = (y, m) => new Date(y, m + 1, 0).getDate();
const firstDayOfMonth = (y, m) => new Date(y, m, 1).getDay();

const coursePalette = []; // Deprecated, using system theme


const getCourseColor = (course) => {
    return { 
        primary: 'var(--theme-color)', 
        shadow: 'rgba(var(--theme-color-rgb), 0.3)' 
    };
};

const getCourseMaxDays = (course) => {
    if (!course) return 28;
    const dur = course.duration?.toLowerCase() || '';
    if (dur.includes('week')) {
        const weeks = parseInt(dur) || 4;
        return Math.min(weeks * 7, 56);
    }
    if (dur.includes('day')) {
        return Math.min(parseInt(dur) || 28, 60);
    }
    return 28;
};

const getCalendarDays = (trainerId) => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const days = [];
    const firstDay = firstDayOfMonth(year, month);
    const totalDays = daysInMonth(year, month);
    
    for (let i = 0; i < firstDay; i++) days.push({ day: null });
    
    for (let i = 1; i <= totalDays; i++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const schedule = trainerSchedules.value[trainerId]?.find(s => s.date === dateStr);
        days.push({
            day: i,
            dateStr,
            schedule,
            isToday: dateStr === new Date().toISOString().split('T')[0]
        });
    }
    return days;
};

const findSyllabusDay = (trainer, dayNumber) => {
    const course = trainer.active_course || trainer.courses?.[0];
    if (!course || !course.lesson_plan) return null;
    return course.lesson_plan.find(l => parseInt(l.day) === dayNumber);
};

const getScheduledInfo = (trainer, dayNumber) => {
    const lesson = findSyllabusDay(trainer, dayNumber);
    if (!lesson || !trainerSchedules.value[trainer.id]) return null;
    
    // Attempt to find a real schedule date for this lesson focus
    const focus = lesson.focus?.toLowerCase();
    const schedule = trainerSchedules.value[trainer.id].find(s => 
        s.focus_area && (s.focus_area.toLowerCase().includes(focus) || focus.includes(s.focus_area.toLowerCase()))
    );
    
    if (!schedule) return null;
    
    const d = new Date(schedule.date);
    return {
        dateStr: d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' }),
        fullDate: schedule.date,
        present: schedule.verifications_present_count > 0,
        absent: schedule.verifications_absent_count > 0,
        schedule
    };
};

const openBlueprintDetail = (trainer, dayNumber) => {
    const lesson = findSyllabusDay(trainer, dayNumber);
    if (!lesson) return;
    
    const info = getScheduledInfo(trainer, dayNumber);
    selectedDayData.value = { 
        trainer, 
        dateStr: info?.fullDate || new Date().toISOString(),
        isBlueprint: true,
        dayNumber,
        schedule: info?.schedule || {
            focus_area: lesson.focus,
            description: lesson.description || 'No specific details provided for this session.',
            exercises: lesson.exercises || [],
            start_time: lesson.duration || 'Session Starts: TBA',
            verifications_present_count: 0,
            verifications_absent_count: 0
        }
    };
    isDayDetailOpen.value = true;
};

const openDayDetail = (trainer, day) => {
    selectedDayData.value = { trainer, ...day };
    isDayDetailOpen.value = true;
};

const monthlyStats = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    
    // Only get schedules for trainers the user is actually enrolled with
    const activeTrainerIds = attendanceTrainers.value.map(t => t.id);
    const relevantSchedules = [];
    
    activeTrainerIds.forEach(id => {
        if (trainerSchedules.value[id]) {
            relevantSchedules.push(...trainerSchedules.value[id]);
        }
    });

    const currentMonthSchedules = relevantSchedules.filter(s => {
        const d = new Date(s.date);
        return d.getFullYear() === year && d.getMonth() === month;
    });

    const planned = currentMonthSchedules.filter(s => s.focus_area).length;
    const present = currentMonthSchedules.reduce((acc, s) => acc + (s.verifications_present_count || 0), 0);
    const absent = currentMonthSchedules.reduce((acc, s) => acc + (s.verifications_absent_count || 0), 0);
    const rate = present + absent > 0 ? Math.round((present / (present + absent)) * 100) : 0;

    return { planned, present, absent, rate };
});

const fetchAllSchedules = async () => {
    isFetchingSchedules.value = true;
    try {
        for (const trainer of mappedTrainers.value) {
            const res = await axios.get(`/api/trainer/${trainer.id}/schedule`);
            trainerSchedules.value[trainer.id] = res.data.schedules;
        }
    } catch (e) {
        console.error('Schedules fetch failed', e);
    } finally {
        isFetchingSchedules.value = false;
    }
};

watch(subTab, (newTab) => {
    if (newTab === 'attendance') {
        fetchAllSchedules();
    }
});

const openBookingModal = (trainer, course = null) => {
    bookingForm.value.trainer_id = trainer.id;
    bookingForm.value.course_name = course ? course.title : '';
    // Auto-set current date/time
    bookingForm.value.booking_date = new Date().toISOString().slice(0, 16);
    isBookingModalOpen.value = true;
};

const openReviewModal = (trainer) => {
    reviewForm.value.trainer_id = trainer.id;
    isReviewModalOpen.value = true;
};

const submitBooking = () => {
    showConfirm(
        'Confirm Enrollment',
        `Do you want to register for ${bookingForm.value.course_name || 'this program'}?`,
        () => {
            const trainerId = bookingForm.value.trainer_id;
            router.post('/api/trainer/book', bookingForm.value, {
                onSuccess: () => {
                    isBookingModalOpen.value = false;
                    
                    // Re-sync selectedTrainer with updated props data
                    const updatedTrainer = mappedTrainers.value.find(t => t.id === trainerId);
                    if (updatedTrainer) {
                        selectedTrainer.value = updatedTrainer;
                    }
                    
                    bookingForm.value = { trainer_id: '', course_name: '', booking_date: '', notes: '' };
                    showSuccess('Request Sent', 'Please wait for your trainer to confirm!');
                }
            });
        }
    );
};

const submitReview = () => {
    router.post('/api/trainer/review', reviewForm.value, {
        onSuccess: () => {
            isReviewModalOpen.value = false;
            reviewForm.value = { trainer_id: '', rating: 5, comment: '' };
            showSuccess('Review Sent', 'Thank you for your feedback!');
        }
    });
};

const verifyTrainer = (trainerId, status, date) => {
    router.post(`/api/trainer/${trainerId}/verify`, { status, date }, {
        preserveScroll: true,
        onSuccess: () => {
            const msg = status === 'present' 
                ? 'Presence verified! Thank you.' 
                : 'Report received. Thank you for the community check.';
            alert(msg);
            // Refresh that trainer's schedule to update counts
            fetchTrainerSchedule(trainerId);
        }
    });
};

const fetchTrainerSchedule = async (trainerId) => {
    try {
        const res = await axios.get(`/api/trainer/${trainerId}/schedule`);
        trainerSchedules.value[trainerId] = res.data.schedules;
    } catch (e) {
        console.error('Failed to refresh schedule', e);
    }
};

const mappedTrainers = computed(() => {
    if (!props.trainers || !Array.isArray(props.trainers)) return [];
    return props.trainers.map(t => {
        const fallbackCourses = [
            { id: 'f1', title: `${t.specialty || 'General'} Fundamentals`, duration: '4 Weeks', level: 'Beginner' },
            { id: 'f2', title: `Advanced ${t.specialty || 'Training'} Masterclass`, duration: '8 Weeks', level: 'Advanced' }
        ];

        const realCourses = t.courses && t.courses.length > 0 ? t.courses : fallbackCourses;

        return {
            id: t.id,
            user_id: t.user_id,
            name: t.user?.name || 'Coach',
            specialty: t.specialty || 'General Training',
            rating: (4.5 + Math.random() * 0.5).toFixed(1),
            reviews: Math.floor(Math.random() * 200) + 50,
            price: t.price_per_session ? `฿${Number(t.price_per_session).toLocaleString()}` : '฿500',
            isAvailable: true,
            bio: t.bio,
            experience: t.experience_years || 5,
            gender: t.user?.gender || 'Other',
            image: t.image_path 
                ? formatImageUrl(t.image_path) 
                : (t.user?.profile_photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(t.user?.name || 'Trainer')}&color=7F9CF5&background=EBF4FF`),
            courses: realCourses,
            verifications_present_count: t.verifications_present_count || 0,
            verifications_absent_count: t.verifications_absent_count || 0,
            has_verified_today: t.has_verified_today || false,
            my_verification_status: t.my_verification_status || null,
            active_course: t.active_course || null,
            my_booking: t.my_booking || null
        };
    });
});

const attendanceTrainers = computed(() => {
    return mappedTrainers.value.filter(t => t.active_course !== null);
});

const sliderPercentage = computed(() => {
    return ((priceFilter.value - 500) / 9500) * 100;
});

const allCourses = computed(() => {
    const courses = [];
    mappedTrainers.value.forEach(trainer => {
        trainer.courses.forEach(course => {
            const coursePrice = course.price ? `฿${Number(course.price).toLocaleString()}` : trainer.price;
            const courseRawPrice = course.price ? parseInt(course.price) : parseInt(trainer.price.replace(/[^\d]/g, ''));
            
            courses.push({
                ...course,
                trainerName: trainer.name,
                trainerId: trainer.id,
                price: coursePrice,
                rawPrice: courseRawPrice
            });
        });
    });
    return courses;
});

const filteredCourses = computed(() => {
    return allCourses.value
        .filter(c => c.rawPrice <= priceFilter.value)
        .sort((a, b) => b.rawPrice - a.rawPrice);
});

const selectTrainer = (trainer, course = null) => {
    if (!trainer) return;
    selectedTrainer.value = trainer;
    state.value = 'detail';
    if (course) {
        openBookingModal(trainer, course);
    }
};

const goBack = () => {
    if (state.value === 'detail') {
        state.value = 'list';
        selectedTrainer.value = null;
    } else {
        emit('back');
    }
};
</script>

<template>
    <div class="transition-colors min-h-screen">
        <!-- Unified Header -->
        <header class="p-6 pb-2 transition-colors relative">
            <div class="flex flex-col items-center mb-4 transition-colors">
                <p class="text-xs font-black uppercase tracking-wider text-orange-500 mb-1 transition-colors">{{ t('trainer.title') }}</p>
                <h1 class="text-[26px] font-black uppercase italic tracking-tighter text-[var(--text-main)] leading-none transition-colors">
                    {{ state === 'list' ? 'Personal Training' : 'Coach Profile' }}
                </h1>
            </div>
            <!-- Back Button -->
            <button @click="goBack" class="absolute left-6 top-8 size-9 rounded-full flex items-center justify-center border border-[var(--border-color)] bg-[var(--card-bg)] shadow-sm transition-all active:scale-90">
                <span class="material-symbols-outlined text-[var(--text-muted)] text-xl">arrow_back</span>
            </button>
        </header>

        <!-- Unified Tabs -->
        <div v-if="state === 'list'" class="px-6 mb-8 flex justify-center sticky top-0 z-[50] py-2 bg-[var(--page-bg)]/80 backdrop-blur-md">
            <div class="flex p-1 bg-[var(--card-bg)] rounded-2xl border border-[var(--border-color)] gap-1 w-full max-w-sm shadow-lg shadow-black/5 relative z-[60]">
                <button v-for="tab in ['trainers', 'courses', 'attendance']" :key="tab"
                    @click.stop="subTab = tab"
                    class="flex-1 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap px-2 flex items-center justify-center cursor-pointer relative z-[70] pointer-events-auto"
                    :class="subTab === tab ? 'bg-[var(--theme-color)] text-white shadow-lg shadow-[var(--theme-color)]/20' : 'text-[var(--text-muted)] hover:text-[var(--text-main)] active:bg-[var(--theme-color)]/5'">
                    {{ t(`trainer.${tab}`) }}
                </button>
            </div>
        </div>

        <!-- Trainer List View -->
        <div v-if="state === 'list' && subTab === 'trainers'" class="p-6 pt-0 transition-all">
            <!-- Motivation Hero Card -->
            <div class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-[#1e293b] to-[#0f172a] p-8 mb-8 shadow-2xl transition-colors">
                <div class="relative z-10 space-y-2">
                    <h2 class="text-white text-xl font-black uppercase italic leading-tight">{{ t('trainer.motivation') }}</h2>
                    <p class="text-gray-400 text-xs max-w-[70%] leading-relaxed">{{ t('trainer.help_subtitle') }}</p>
                </div>
                <div class="absolute -right-4 -bottom-4 size-32 opacity-20 rotate-12 transition-colors">
                    <span class="material-symbols-outlined text-9xl text-white">volunteer_activism</span>
                </div>
            </div>

            <h3 class="text-xs font-black uppercase tracking-widest text-[var(--text-muted)] mb-4 transition-colors">{{ t('trainer.choose_trainer') }}</h3>

            <div class="space-y-4 pb-20 transition-colors">
                <div v-for="trainer in mappedTrainers" :key="trainer.id" 
                    @click="selectTrainer(trainer)"
                    class="bg-[var(--card-bg)] rounded-[24px] border border-[var(--border-color)] overflow-hidden shadow-sm active:scale-[0.98] transition-all flex items-center p-4 gap-4">
                    <div class="size-20 rounded-2xl bg-[var(--theme-color)]/10 flex items-center justify-center flex-shrink-0 transition-colors overflow-hidden border border-[var(--border-color)]/30">
                        <img v-if="trainer.image" :src="trainer.image" class="w-full h-full object-cover">
                        <span v-else class="material-symbols-outlined text-4xl text-[var(--theme-color)]">person</span>
                    </div>
                    <div class="flex-1 min-w-0 transition-colors">
                        <h4 class="text-lg font-black uppercase italic text-[var(--text-main)] leading-none mb-1 transition-colors">{{ trainer.name }}</h4>
                        <p class="text-xs font-bold text-[var(--text-muted)] uppercase mb-2 transition-colors">{{ trainer.specialty }}</p>
                        <div class="flex items-center gap-3 transition-colors">
                            <div class="flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-xs text-yellow-500 fill-icon">star</span>
                                <span class="text-[10px] font-black text-[var(--text-main)] transition-colors">{{ trainer.rating }}</span>
                            </div>
                            <span class="text-[10px] font-black text-[var(--theme-color)] uppercase transition-colors">{{ trainer.price }} / Session</span>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">chevron_right</span>
                </div>
            </div>
        </div>

        <!-- Global Course List View -->
        <div v-else-if="state === 'list' && subTab === 'courses'" class="p-6 pt-0 transition-all pb-32">
            <!-- Price Filter -->
            <div class="bg-[var(--card-bg)] border border-[var(--border-color)] rounded-[24px] p-6 mb-8 transition-colors relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-xs font-black uppercase tracking-widest text-[var(--text-main)] transition-colors">Price Filter</h4>
                    <span class="text-sm font-black text-[var(--theme-color)] transition-colors">Max: ฿{{ priceFilter.toLocaleString() }}</span>
                </div>
                <div class="relative py-4">
                    <input type="range" min="500" max="10000" step="100" v-model.number="priceFilter" 
                        :style="{ 
                            background: `linear-gradient(to right, var(--theme-color) 0%, var(--theme-color) ${sliderPercentage}%, var(--page-bg) ${sliderPercentage}%, var(--page-bg) 100%)` 
                        }"
                        class="w-full h-2 rounded-lg appearance-none cursor-pointer accent-[var(--theme-color)] border border-[var(--border-color)] relative z-20 pointer-events-auto">
                </div>
                <div class="flex justify-between mt-2 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-wider transition-colors">
                    <span>฿500</span>
                    <span>฿5,000</span>
                    <span>฿10,000</span>
                </div>
            </div>

            <div class="space-y-4 transition-colors">
                <div v-for="course in filteredCourses" :key="`${course.trainerId}-${course.id}`"
                    @click="selectTrainer(mappedTrainers.find(t => t.id === course.trainerId), course)"
                    class="bg-[var(--card-bg)] rounded-[24px] border border-[var(--border-color)] p-5 flex items-center justify-between group active:scale-[0.98] transition-all">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-[var(--theme-color)]/10 text-[var(--theme-color)] rounded-md text-[8px] font-black uppercase tracking-wider">{{ course.level }}</span>
                            <span class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-wider">by {{ course.trainerName }}</span>
                        </div>
                        <h4 class="text-base font-black uppercase italic text-[var(--text-main)] leading-tight group-hover:text-[var(--theme-color)] transition-colors">{{ course.title }}</h4>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-bold text-[var(--text-muted)] uppercase">{{ course.duration }}</span>
                            <span class="text-[10px] font-black text-[var(--text-main)] uppercase">{{ course.price }}</span>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">chevron_right</span>
                </div>

                <div v-if="filteredCourses.length === 0" class="py-12 text-center opacity-40">
                    <span class="material-symbols-outlined text-4xl mb-2">filter_list_off</span>
                    <p class="text-xs font-bold uppercase italic">No courses found matching this price</p>
                </div>
            </div>
        </div>

        <!-- Attendance / Proof of Teaching View -->
        <div v-else-if="state === 'list' && subTab === 'attendance'" class="p-6 pt-0 transition-all pb-32">
            <!-- Summary Card -->
            <div class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-[#1e293b] to-[#0f172a] p-8 mb-8 shadow-2xl transition-colors">
                <div class="relative z-10 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-[10px] font-black text-green-500 uppercase tracking-widest leading-none">Live Presence system</span>
                    </div>
                    <h2 class="text-xl font-black text-white uppercase italic leading-tight">Verified Coaching</h2>
                    <p class="text-gray-400 text-[11px] leading-relaxed max-w-[80%]">FITPUNG tracks real-time trainer attendance to ensure elite standards for every session.</p>
                </div>
                <div class="absolute -right-4 -bottom-4 size-32 opacity-10 rotate-12 transition-colors">
                    <span class="material-symbols-outlined text-9xl text-white">verified_user</span>
                </div>
            </div>

            <!-- Attendance List -->
            <div class="space-y-4">
                <!-- Global Verification Summary -->
                <div class="grid grid-cols-3 gap-3 mb-2">
                    <div class="bg-[var(--card-bg)] p-4 rounded-[24px] border border-[var(--border-color)] text-center shadow-sm">
                        <span class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-widest block mb-1">Teaching</span>
                        <div class="flex items-baseline justify-center gap-0.5">
                            <span class="text-base font-black italic text-[var(--text-main)]">{{ monthlyStats.planned }}</span>
                            <span class="text-[7px] font-black text-[var(--text-muted)] uppercase">Days</span>
                        </div>
                    </div>
                    <div class="bg-[var(--card-bg)] p-4 rounded-[24px] border border-[var(--border-color)] text-center shadow-sm relative overflow-hidden">
                        <span class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-widest block mb-1">Verified</span>
                        <span class="text-base font-black italic text-[var(--theme-color)]">{{ monthlyStats.rate }}%</span>
                    </div>
                    <div class="bg-[var(--card-bg)] p-4 rounded-[24px] border border-[var(--border-color)] text-center shadow-sm">
                        <span class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-widest block mb-1">Presence</span>
                        <div class="flex items-center justify-center gap-1.5 mt-0.5">
                            <span class="text-[8px] font-black text-green-500">{{ monthlyStats.present }}</span>
                            <span class="text-[8px] font-black text-red-400">{{ monthlyStats.absent }}</span>
                        </div>
                    </div>
                </div>

                <div v-for="trainer in attendanceTrainers" :key="`att-${trainer.id}`" 
                    class="bg-[var(--card-bg)] rounded-[24px] border border-[var(--border-color)] p-5 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="size-14 rounded-2xl bg-[var(--theme-color)]/10 border border-[var(--border-color)] overflow-hidden flex-shrink-0">
                            <img v-if="trainer.image" :src="trainer.image" class="w-full h-full object-cover">
                            <span v-else class="material-symbols-outlined text-3xl text-[var(--theme-color)] flex items-center justify-center h-full">person</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-sm font-black uppercase italic text-[var(--text-main)] truncate leading-none">{{ trainer.name }}</h4>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="px-2 py-0.5 rounded-full text-[7px] font-black uppercase tracking-wider flex-shrink-0" 
                                        :class="Math.random() > 0.5 ? 'bg-green-500/10 text-green-500' : 'bg-amber-500/10 text-amber-500'">
                                        {{ Math.random() > 0.5 ? 'Teaching now' : 'On-Site Today' }}
                                    </span>
                                    <div class="flex items-center gap-1 opacity-60">
                                        <span class="material-symbols-outlined text-[10px] text-[var(--text-muted)]">verified_user</span>
                                        <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-tighter">Teaching Proof List</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-[9px] font-bold text-[var(--text-muted)] uppercase tracking-tight">{{ trainer.specialty }}</p>
                                <div class="px-3 py-1 bg-[var(--theme-color)]/10 rounded-lg">
                                    <span class="text-[8px] font-black text-[var(--theme-color)] uppercase tracking-widest italic">Live Coaching Mode</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Stats Grid -->
                    <!-- Course Blueprint Section (Replaces Monthly Calendar) -->
                    <div v-if="trainer.active_course" class="border-t border-[var(--border-color)] pt-5 pb-1 mt-4">
                        <div v-for="course in [trainer.active_course]" :key="course.id" class="space-y-6">
                            <!-- Blueprint Header Stats -->
                            <div class="flex items-center justify-between px-1">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[14px] font-black italic text-[var(--text-main)]">///</span>
                                        <span class="text-[12px] font-black uppercase italic text-[var(--text-main)] leading-none tracking-tighter">{{ course.title }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[8px] font-bold text-[var(--text-muted)] opacity-50">{{ course.id }}</span>
                                        <span class="material-symbols-outlined text-[4px] text-[var(--text-muted)] opacity-30">circle</span>
                                        <span class="text-[9px] font-black text-[var(--theme-color)] uppercase tracking-wider italic">{{ course.level || 'Expert' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 bg-[var(--page-bg)] border border-[var(--border-color)] p-1.5 rounded-xl">
                                    <button class="size-7 flex items-center justify-center rounded-lg bg-[var(--card-bg)] shadow-sm">
                                        <span class="material-symbols-outlined text-sm text-[var(--theme-color)]">grid_view</span>
                                    </button>
                                    <button class="size-7 flex items-center justify-center rounded-lg opacity-30">
                                        <span class="material-symbols-outlined text-sm">list</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Progress Info Card -->
                            <div class="bg-[var(--page-bg)]/60 rounded-[28px] border border-[var(--border-color)] p-5 flex items-center justify-between">
                                <div class="space-y-1.5 text-center flex-1 border-r border-[var(--border-color)]">
                                    <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest leading-none">Program Status</p>
                                <p class="text-[11px] font-black italic uppercase leading-none" :style="{ color: getCourseColor(course).primary }">
                                    {{ course.lesson_plan?.length || 0 }} of {{ getCourseMaxDays(course) }} Days Set
                                </p>
                            </div>
                            <div class="space-y-1.5 text-center flex-1">
                                <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest leading-none">Target Hours</p>
                                <p class="text-[11px] font-black italic text-[var(--text-main)] uppercase leading-none">{{ course.hours || 10 }}H Course</p>
                            </div>
                        </div>

                        <!-- Blueprint Grid -->
                        <div class="grid grid-cols-7 gap-1.5 p-1 bg-[var(--page-bg)]/30 rounded-[32px] border border-[var(--border-color)]/20 shadow-inner">
                            <div v-for="dayNum in getCourseMaxDays(course)" :key="dayNum"
                                @click="openBlueprintDetail(trainer, dayNum)"
                                class="aspect-square rounded-[14px] flex flex-col items-center justify-center relative transition-all overflow-hidden p-1 border"
                                :class="[
                                    findSyllabusDay(trainer, dayNum) 
                                        ? 'text-white active:scale-95 cursor-pointer shadow-lg' 
                                        : 'bg-[var(--page-bg)]/40 border-[var(--border-color)]/30 text-[var(--text-muted)]/30 pointer-events-none'
                                ]"
                                :style="findSyllabusDay(trainer, dayNum) ? { 
                                    backgroundColor: getCourseColor(course).primary, 
                                    borderColor: `${getCourseColor(course).primary}AA`,
                                    boxShadow: `0 4px 10px ${getCourseColor(course).shadow}`
                                } : {}"
                            >
                                <span class="text-[10px] font-black italic leading-none">{{ dayNum }}</span>
                                
                                <!-- Date Display -->
                                <p v-if="getScheduledInfo(trainer, dayNum)" class="text-[5.5px] font-black uppercase text-white/90 mt-0.5 leading-none">
                                    {{ getScheduledInfo(trainer, dayNum).dateStr }}
                                </p>

                                <!-- Status Dots -->
                                <div v-if="getScheduledInfo(trainer, dayNum)" class="absolute bottom-1 flex gap-0.5">
                                    <span v-if="getScheduledInfo(trainer, dayNum).present" class="size-0.5 rounded-full bg-green-300 shadow-sm"></span>
                                    <span v-if="getScheduledInfo(trainer, dayNum).absent" class="size-0.5 rounded-full bg-red-300 shadow-sm"></span>
                                </div>

                                <!-- Mini Label -->
                                <p v-if="findSyllabusDay(trainer, dayNum)" class="text-[4px] font-black uppercase text-white/60 truncate w-full text-center mt-0.5 px-0.5">
                                    {{ findSyllabusDay(trainer, dayNum).focus }}
                                </p>
                            </div>
                        </div>

                            <!-- Grid Legend -->
                            <div class="flex items-center gap-4 px-4 pt-1">
                                <div class="flex items-center gap-2">
                                    <span class="size-2.5 rounded-full border shadow-sm" :style="{ backgroundColor: getCourseColor(course).primary, borderColor: `${getCourseColor(course).primary}44` }"></span>
                                    <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest">Active Session</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="size-2.5 rounded-full bg-[var(--page-bg)] border border-[var(--border-color)]/10"></span>
                                    <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest opacity-40">Rest/Empty</span>
                                </div>
                            </div>

                            <!-- Empty Curriculum Warning -->
                            <div v-if="!course.lesson_plan || course.lesson_plan.length === 0" class="text-center py-6 opacity-30">
                                <span class="material-symbols-outlined text-3xl mb-1">pending_actions</span>
                                <p class="text-[7px] font-black uppercase italic tracking-widest">Blueprint pending curriculum setup</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Day Detail Modal -->
        <div v-if="isDayDetailOpen && selectedDayData" class="fixed inset-0 z-[120] flex items-center justify-center p-6">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="isDayDetailOpen = false"></div>
            <div class="relative w-full max-w-sm bg-[var(--card-bg)] rounded-[32px] p-8 shadow-2xl border border-[var(--border-color)] animate-in fade-in zoom-in duration-200">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex flex-col">
                        <span v-if="selectedDayData.dateStr" class="text-[10px] font-black text-[var(--theme-color)] uppercase tracking-widest mb-1">{{ new Date(selectedDayData.dateStr).toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' }) }}</span>
                        <h3 class="text-xl font-black uppercase italic text-[var(--text-main)] leading-none">{{ selectedDayData.trainer?.name || 'Coach' }}</h3>
                    </div>
                    <button @click="isDayDetailOpen = false" class="size-10 flex items-center justify-center rounded-2xl bg-[var(--page-bg)] border border-[var(--border-color)] active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-[var(--text-muted)]">close</span>
                    </button>
                </div>

                <div class="space-y-6">
                    <!-- Exercises Structured View (Read Only) -->
                    <div v-if="(selectedDayData.schedule?.exercises && selectedDayData.schedule.exercises.length > 0) || (findSyllabusDay(selectedDayData.trainer, selectedDayData.dayNumber)?.exercises?.length > 0)" class="space-y-4">
                        <div class="flex items-center gap-2 px-1">
                            <span class="material-symbols-outlined text-sm text-[var(--theme-color)]">list_alt</span>
                            <span class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest">Exercise Routine (Read Only)</span>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(ex, idx) in (selectedDayData.schedule?.exercises || findSyllabusDay(selectedDayData.trainer, selectedDayData.dayNumber)?.exercises)" :key="idx" 
                                class="p-5 bg-white border border-[var(--border-color)] rounded-[40px] shadow-sm space-y-4"
                            >
                                <!-- Header: Image + Name -->
                                <div class="flex items-center gap-4">
                                    <!-- Machine Image -->
                                    <div class="size-16 rounded-[24px] bg-[var(--page-bg)] border border-[var(--border-color)] overflow-hidden flex-shrink-0 flex items-center justify-center p-2">
                                        <img v-if="ex.image" :src="ex.image" class="w-full h-full object-contain">
                                        <span v-else class="material-symbols-outlined text-2xl text-[var(--text-muted)] opacity-20">fitness_center</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <div class="size-2 rounded-full bg-[var(--theme-color)]"></div>
                                            <h5 class="text-[14px] font-black uppercase text-[var(--text-main)] italic truncate">{{ ex.name }}</h5>
                                        </div>
                                        <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest opacity-60 ml-4">Planned Syllabus</p>
                                    </div>
                                </div>

                                <!-- Prescribed Sets List (Read Only) -->
                                <div class="space-y-2">
                                    <div v-for="(set, sIdx) in (ex.sets_data || Array.from({ length: parseInt(ex.sets) || 1 }, () => ({ weight: ex.weight || '0', reps: ex.reps || '0' })))" :key="sIdx"
                                        class="flex items-center justify-between p-3.5 bg-[var(--page-bg)]/50 rounded-2xl border border-[var(--border-color)]/30"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-black text-[var(--text-main)] uppercase italic">Set {{ sIdx + 1 }}</span>
                                        </div>
                                        <div class="flex items-center gap-5">
                                            <div class="flex flex-col items-center">
                                                <span class="text-[12px] font-black text-[var(--text-main)] italic leading-none">{{ set.weight }}</span>
                                                <span class="text-[6px] font-black text-[var(--text-muted)] uppercase tracking-tighter mt-1 opacity-40">Weight</span>
                                            </div>
                                            <div class="h-4 w-px bg-[var(--border-color)] opacity-20"></div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-[12px] font-black text-[var(--text-main)] italic leading-none">{{ set.reps }}</span>
                                                <span class="text-[6px] font-black text-[var(--text-muted)] uppercase tracking-tighter mt-1 opacity-40">Reps</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Huge Action Button to Start Workout -->
                        <button 
                            @click="emit('start-workout', { title: selectedDayData.schedule?.focus_area || 'Coaching Session', exercises: selectedDayData.schedule?.exercises || findSyllabusDay(selectedDayData.trainer, selectedDayData.dayNumber)?.exercises }); isDayDetailOpen = false;"
                            class="w-full py-5 bg-[var(--text-main)] text-[var(--card-bg)] text-[12px] font-black uppercase tracking-[0.2em] rounded-3xl shadow-2xl active:scale-95 transition-all mt-4 flex items-center justify-center gap-3 hover:bg-[var(--theme-color)] hover:shadow-[var(--theme-color)]/20"
                        >
                            <span class="material-symbols-outlined animate-pulse text-lg">play_circle</span>
                            Start Prescribed Routine
                        </button>
                    </div>

                    <!-- No Official Plan State -->
                    <div v-else class="bg-[var(--page-bg)]/40 p-12 rounded-3xl border border-dashed border-[var(--border-color)] text-center">
                        <span class="material-symbols-outlined text-4xl text-[var(--text-muted)] opacity-20 mb-3">event_busy</span>
                        <p class="text-[11px] font-black text-[var(--text-muted)] uppercase tracking-widest leading-tight">No exercises planned for this date</p>
                    </div>

                    <!-- Trainer-only message -->
                    <div v-if="$page.props.auth.user?.id === selectedDayData.trainer.user_id" class="px-2 py-4 text-center bg-[var(--page-bg)]/50 rounded-2xl border border-dotted border-[var(--border-color)]">
                        <p class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-wider opacity-60">
                            You are viewing this as the Coach.<br/>Only students can verify your attendance.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail View -->
        <div v-else-if="state === 'detail' && selectedTrainer" class="p-6 pt-0 pb-32 transition-all">

            <div class="flex gap-6 mb-8 transition-colors">
                <div class="size-32 rounded-[32px] bg-[var(--theme-color)]/10 border-4 border-[var(--theme-color)] flex items-center justify-center flex-shrink-0 transition-colors overflow-hidden">
                    <img v-if="selectedTrainer.image" :src="selectedTrainer.image" class="w-full h-full object-cover">
                    <span v-else class="material-symbols-outlined text-6xl text-[var(--theme-color)]">person</span>
                </div>
                <div class="flex-1 pt-2 transition-colors">
                    <span class="px-3 py-1 bg-[var(--theme-color)]/10 text-[var(--theme-color)] rounded-full text-[10px] font-black uppercase tracking-wider mb-2 inline-block transition-colors">Pro Mentor</span>
                    <h3 class="text-2xl font-black uppercase italic text-[var(--text-main)] leading-none mb-2 transition-colors">{{ selectedTrainer.name }}</h3>
                    <p class="text-xs font-bold text-[var(--text-muted)] leading-relaxed transition-colors">{{ selectedTrainer.specialty }} expert with years of results.</p>
                </div>
            </div>

            <!-- Bio/About -->
            <div class="mb-8" v-if="selectedTrainer.bio">
                <h4 class="text-xs font-black uppercase tracking-widest text-[var(--text-muted)] mb-3">About Coach</h4>
                <p class="text-sm text-[var(--text-main)] leading-relaxed opacity-80 italic">"{{ selectedTrainer.bio }}"</p>
            </div>

            <!-- Courses Section -->
            <div class="space-y-6 transition-colors">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-black uppercase tracking-widest text-[var(--text-muted)] flex items-center gap-2 transition-colors">
                        <span class="material-symbols-outlined text-lg">exercise</span>
                        Expert Courses
                    </h4>
                    <div v-if="$page.props.auth.user?.id !== selectedTrainer.user_id" class="flex gap-2">
                        <button 
                            @click="openReviewModal(selectedTrainer)"
                            class="px-4 py-2.5 bg-[var(--page-bg)] text-[var(--text-main)] text-[10px] font-black uppercase tracking-widest rounded-full border border-[var(--border-color)] active:scale-95 transition-all"
                        >
                            Rate
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-[var(--card-bg)] p-4 rounded-3xl border border-[var(--border-color)] flex flex-col items-center">
                        <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest mb-1">Experience</span>
                        <span class="text-sm font-black text-[var(--text-main)] uppercase tracking-tighter">{{ selectedTrainer.experience }} Years</span>
                    </div>
                    <div class="bg-[var(--card-bg)] p-4 rounded-3xl border border-[var(--border-color)] flex flex-col items-center">
                        <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-widest mb-1">Gender</span>
                        <span class="text-sm font-black text-[var(--text-main)] uppercase tracking-tighter">{{ selectedTrainer.gender }}</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div v-for="course in selectedTrainer.courses" :key="course.id"
                        @click="selectedTrainer.my_booking?.status === 'pending' ? null : openBookingModal(selectedTrainer, course)"
                        class="p-5 rounded-[24px] border border-[var(--border-color)] bg-[var(--card-bg)] flex items-center justify-between group cursor-pointer active:scale-[0.98] transition-all"
                        :class="{ 'opacity-80 active:scale-100 cursor-default': selectedTrainer.my_booking?.status === 'pending' }"
                    >
                        <div class="space-y-1">
                            <h5 class="text-sm font-black uppercase italic text-[var(--text-main)] group-hover:text-[var(--theme-color)] transition-colors">{{ course.title }}</h5>
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-wider">{{ course.duration }}</span>
                                <span class="size-1 rounded-full bg-[var(--border-color)]"></span>
                                <span class="text-[10px] font-black text-[var(--theme-color)] uppercase tracking-wider">{{ course.level }}</span>
                            </div>
                        </div>
                        
                        <!-- Status Badge / Action Icon -->
                        <div class="flex items-center gap-2">
                            <template v-if="selectedTrainer.my_booking?.course_name === course.title">
                                <span v-if="selectedTrainer.my_booking.status === 'pending'" 
                                    class="px-2 py-1 bg-amber-500/10 text-amber-600 rounded-lg text-[8px] font-black uppercase italic tracking-wider flex items-center gap-1">
                                    <span class="size-1 rounded-full bg-amber-500 animate-pulse"></span>
                                    Waiting for Approval
                                </span>
                                <span v-else-if="selectedTrainer.my_booking.status === 'confirmed'" 
                                    class="px-2 py-1 bg-green-500/10 text-green-600 rounded-lg text-[8px] font-black uppercase italic tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[10px]">check_circle</span>
                                    Active Now
                                </span>
                                <span v-else-if="selectedTrainer.my_booking.status === 'cancelled'" 
                                    class="px-2 py-1 bg-rose-500/10 text-rose-600 rounded-lg text-[8px] font-black uppercase italic tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[10px]">cancel</span>
                                    ปฏิเสธ (Rejected)
                                </span>
                            </template>
                            <span v-else class="material-symbols-outlined text-[var(--text-muted)] opacity-50">arrow_forward</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isBookingModalOpen" class="fixed inset-0 z-[110] flex items-center justify-center bg-black/60 backdrop-blur-md p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">enrollment request</h2>
                    <p v-if="bookingForm.course_name" class="text-[12px] font-black text-[var(--theme-color)] uppercase italic tracking-tighter mt-1">{{ bookingForm.course_name }}</p>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-1">with coach {{ selectedTrainer?.name }}</p>
                </div>

                <div class="space-y-6">
                    <!-- Member Identity Display (Replaces Date Input) -->
                    <div class="bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl p-4 flex items-center gap-4">
                        <img 
                            :src="$page.props.auth.user.profile_photo_url" 
                            class="size-12 rounded-full border-2 border-[var(--theme-color)]"
                            alt="Member Profile"
                        />
                        <div class="flex-1">
                            <label class="text-[8px] font-black uppercase tracking-widest text-[var(--theme-color)] block mb-0.5">Applicant Identity</label>
                            <h4 class="text-sm font-black italic uppercase text-[var(--text-main)] leading-none">{{ $page.props.auth.user.name }}</h4>
                            <p class="text-[9px] font-black text-[var(--text-muted)] truncate mt-1">{{ $page.props.auth.user.email }}</p>
                        </div>
                        <div class="flex flex-col items-end opacity-40">
                            <span class="material-symbols-outlined text-sm">verified</span>
                            <span class="text-[7px] font-black uppercase">verified</span>
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Notes / Preferences</label>
                        <textarea 
                            v-model="bookingForm.notes"
                            rows="3"
                            placeholder="Tell the coach what you want to achieve..."
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] placeholder:text-[var(--text-muted)]/40 focus:outline-none focus:border-[var(--theme-color)] transition-all"
                        ></textarea>
                    </div>

                    <div class="pt-2">
                        <button 
                            @click="submitBooking"
                            class="w-full py-5 bg-[var(--theme-color)] text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-[var(--theme-color)]/20 active:scale-[0.98] transition-all"
                        >
                            Confirm Registration
                        </button>
                        <button 
                            @click="isBookingModalOpen = false"
                            class="w-full py-4 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-2"
                        >
                            Go Back
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Modal -->
        <div v-if="isReviewModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">Submit Review</h2>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-1">Feedback for {{ selectedTrainer?.name }}</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Rating</label>
                        <div class="flex items-center justify-center gap-2 mt-4">
                            <button 
                                v-for="star in 5" 
                                :key="star"
                                @click="reviewForm.rating = star"
                                class="material-symbols-outlined text-3xl transition-all"
                                :class="reviewForm.rating >= star ? 'text-amber-500 fill-1' : 'text-[var(--text-muted)]/30'"
                                :style="{ fontVariationSettings: `'FILL' ${reviewForm.rating >= star ? 1 : 0}` }"
                            >
                                star
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Your Comment</label>
                        <textarea 
                            v-model="reviewForm.comment"
                            rows="4"
                            placeholder="Share your experience with this coach..."
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] placeholder:text-[var(--text-muted)]/40 focus:outline-none focus:border-[var(--theme-color)] transition-all"
                        ></textarea>
                    </div>

                    <button 
                        @click="submitReview"
                        class="w-full py-4 bg-[var(--theme-color)] text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-[var(--theme-color)]/20 active:scale-[0.98] transition-all"
                    >
                        Submit Feedback
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

        <!-- Premium Confirmation / Success Modal -->
        <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[200] flex items-center justify-center p-8">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-xl" @click="confirmModal.isOpen = false"></div>
            <div class="relative w-full max-w-sm bg-[var(--card-bg)] rounded-[40px] border border-[var(--border-color)] p-8 shadow-2xl overflow-hidden active:scale-[0.99] transition-all">
                <!-- Decorative Circle -->
                <div class="absolute -right-12 -top-12 size-40 rounded-full bg-[var(--theme-color)]/5 blur-3xl"></div>
                
                <div class="text-center relative z-10">
                    <div class="size-20 bg-[var(--theme-color)]/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-[var(--theme-color)]/20 shadow-lg shadow-[var(--theme-color)]/10">
                        <span class="material-symbols-outlined text-4xl text-[var(--theme-color)]">
                            {{ confirmModal.type === 'success' ? 'verified' : 'help_center' }}
                        </span>
                    </div>

                    <h3 class="text-xl font-black italic uppercase text-[var(--text-main)] mb-3 tracking-tighter">
                        {{ confirmModal.title }}
                    </h3>
                    <p class="text-xs font-medium text-[var(--text-muted)] leading-relaxed mb-8 px-4 opacity-80">
                        {{ confirmModal.message }}
                    </p>

                    <div class="flex gap-3">
                        <template v-if="confirmModal.type === 'confirm'">
                            <button 
                                @click="confirmModal.isOpen = false"
                                class="flex-1 py-4 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-[20px] text-[10px] font-black uppercase text-[var(--text-muted)] tracking-widest active:scale-95 transition-all"
                            >
                                No, Cancel
                            </button>
                            <button 
                                @click="() => { confirmModal.onConfirm?.(); confirmModal.isOpen = false; }"
                                class="flex-1 py-4 bg-[var(--theme-color)] rounded-[20px] text-[10px] font-black uppercase text-white shadow-xl shadow-[var(--theme-color)]/30 tracking-widest active:scale-95 transition-all"
                            >
                                Yes, Confirm
                            </button>
                        </template>
                        <template v-else>
                            <button 
                                @click="confirmModal.isOpen = false"
                                class="w-full py-4 bg-[var(--theme-color)] rounded-[20px] text-[10px] font-black uppercase text-white shadow-xl shadow-[var(--theme-color)]/30 tracking-widest active:scale-95 transition-all"
                            >
                                Awesome!
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.fill-icon { font-variation-settings: 'FILL' 1; }
</style>
