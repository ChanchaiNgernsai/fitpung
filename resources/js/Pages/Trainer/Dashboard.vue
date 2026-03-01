<script setup>
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { useI18n } from '@/language';

const { t } = useI18n();
const page = usePage();

const props = defineProps(['trainer', 'clients', 'bookings', 'courses']);

const groupedClientsList = computed(() => {
    const groups = {};
    // Only group by actual course titles, skip "General Program"
    props.courses.forEach(c => {
        if (c.title && c.title !== 'General Program') {
            groups[c.title] = [];
        }
    });
    
    // Add clients to their respective course groups ONLY if they belong to a valid course
    props.clients.forEach(client => {
        const name = client.course_name;
        if (name && groups[name]) {
            groups[name].push(client);
        }
    });
    
    return groups;
});

const selectedCourseFilter = ref('All');

const activeTab = ref('clients');
const selectedClient = ref(null);
const selectedCourse = ref(null);
const isRecordingModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const isCourseModalOpen = ref(false);
const isScheduleModalOpen = ref(false);
const clientMetrics = ref([]);
const schedules = ref([]);
const currentMonth = ref(new Date());
const coursesSubTab = ref('blueprint'); // 'blueprint' | 'catalog'
const selectedScheduleCourse = ref(null);

const scheduleForm = ref({
    date: '',
    focus_area: '',
    description: ''
});

const recordForm = ref({
    user_id: '',
    hours: 1,
    notes: '',
    metrics: {
        weight: '',
        body_fat: '',
        muscle_mass: '',
        waist_circumference: ''
    }
});

const isManualBookingOpen = ref(false);
const isEditBookingOpen = ref(false);
const isAddClientModalOpen = ref(false);
const userSearchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

const editBookingForm = ref({
    id: null,
    booking_date: '',
    notes: ''
});

const openEditBooking = (booking) => {
    editBookingForm.value = {
        id: booking.id,
        booking_date: booking.booking_date.substring(0, 16), // Format for datetime-local
        notes: booking.notes || ''
    };
    isEditBookingOpen.value = true;
};

const confirmModal = ref({
    isOpen: false,
    title: '',
    message: '',
    type: 'success', // 'confirm' | 'success' | 'error'
    onConfirm: null
});

const showSuccess = (title, message) => {
    confirmModal.value = { 
        isOpen: true, title, message, type: 'success', 
        onConfirm: null 
    };
};

const showConfirm = (title, message, onConfirm) => {
    confirmModal.value = { 
        isOpen: true, title, message, type: 'confirm', 
        onConfirm 
    };
};

const showError = (title, message) => {
    confirmModal.value = { isOpen: true, title, message, type: 'error', onConfirm: null };
};

const submitEditBooking = () => {
    router.patch(`/api/bookings/${editBookingForm.value.id}`, editBookingForm.value, {
        onSuccess: () => {
            isEditBookingOpen.value = false;
            showSuccess('Rescheduled', 'Session time has been updated successfully.');
        }
    });
};

const searchUsers = async () => {
    if (userSearchQuery.value.length < 2) return;
    isSearching.value = true;
    try {
        const response = await axios.get(`/api/trainer/search-users?q=${userSearchQuery.value}`);
        searchResults.value = response.data.users;
    } catch (error) {
        console.error('Search failed:', error);
    } finally {
        isSearching.value = false;
    }
};

const addClient = (userId) => {
    router.post('/api/trainer/add-client', { 
        user_id: userId,
        course_name: selectedCourseFilter.value !== 'All' ? selectedCourseFilter.value : null 
    }, {
        onSuccess: () => {
            isAddClientModalOpen.value = false;
            searchResults.value = [];
            userSearchQuery.value = '';
            showSuccess('Client Added', 'The student has been added to your roster.');
        }
    });
};

const manualBookingForm = ref({
    user_id: '',
    course_name: '',
    booking_date: '',
    notes: ''
});
const clientHistory = ref([]);
const isFetchingHistory = ref(false);
const activeDetailTab = ref('progress'); // 'progress' or 'history'

const courseForm = ref({
    id: null,
    title: '',
    description: '',
    duration: '',
    level: 'Beginner',
    price: '',
    hours: 10,
    lesson_plan: []
});

const openRecordModal = (client) => {
    selectedClient.value = client;
    recordForm.value.user_id = client.user_id;
    isRecordingModalOpen.value = true;
};

const openCourseModal = (course = null) => {
    if (course) {
        selectedCourse.value = course;
        courseForm.value = { 
            ...course,
            lesson_plan: Array.isArray(course.lesson_plan) ? course.lesson_plan : []
        };
    } else {
        selectedCourse.value = null;
        courseForm.value = { 
            id: null, 
            title: '', 
            description: '', 
            duration: '', 
            level: 'Beginner', 
            price: '', 
            hours: 10,
            lesson_plan: []
        };
    }
    isCourseModalOpen.value = true;
    editingDay.value = null;
    originalCourseData.value = JSON.parse(JSON.stringify(courseForm.value));
};

const cancelCourseEdit = () => {
    showConfirm(
        'Discard Changes?',
        'Any modifications you made to this blueprint will be lost.',
        () => {
            isCourseModalOpen.value = false;
            showSuccess('Action Cancelled', 'No changes were saved.');
        }
    );
};

const originalCourseData = ref(null);

const addLessonPoint = () => {
    courseForm.value.lesson_plan.push({ focus: '', details: '' });
};

const removeLessonPoint = (index) => {
    courseForm.value.lesson_plan.splice(index, 1);
};

const monthlyStats = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const currentMonthSchedules = schedules.value.filter(s => {
        const d = new Date(s.date);
        return d.getFullYear() === year && d.getMonth() === month;
    });

    const planned = currentMonthSchedules.filter(s => s.focus_area).length;
    const present = currentMonthSchedules.reduce((acc, s) => acc + (s.verifications_present_count || 0), 0);
    const absent = currentMonthSchedules.reduce((acc, s) => acc + (s.verifications_absent_count || 0), 0);
    const rate = present + absent > 0 ? Math.round((present / (present + absent)) * 100) : 0;

    return { planned, present, absent, rate };
});

const hoursOptions = Array.from({ length: 12 }, (_, i) => i + 1);
const minutesOptions = ['00', '15', '30', '45'];
const periodsOptions = ['AM', 'PM'];

const getTimePart = (timeStr, part) => {
    if (!timeStr) return part === 'period' ? 'AM' : (part === 'hour' ? '8' : '00');
    const [time, period] = timeStr.split(' ');
    if (part === 'period') return period || 'AM';
    const [h, m] = time.split(':');
    return part === 'hour' ? parseInt(h).toString() : m;
};

const updateTimePart = (currentValue, part, newValue) => {
    let h = getTimePart(currentValue, 'hour');
    let m = getTimePart(currentValue, 'minute');
    let p = getTimePart(currentValue, 'period');
    
    if (part === 'hour') h = newValue;
    if (part === 'minute') m = newValue;
    if (part === 'period') p = newValue;
    
    return `${h}:${m} ${p}`;
};

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

const maxSyllabusDays = computed(() => {
    return getCourseMaxDays(courseForm.value);
});

const getLessonForDay = (day) => {
    return courseForm.value.lesson_plan.find(l => l.day === day);
};

const toggleLessonDay = (day) => {
    const index = courseForm.value.lesson_plan.findIndex(l => l.day === day);
    if (index === -1) {
        courseForm.value.lesson_plan.push({ day, focus: '', details: '', duration: '' });
        // Sort by day
        courseForm.value.lesson_plan.sort((a, b) => a.day - b.day);
    } else {
        // If empty, maybe let them edit it instead of removing immediately if they click?
        // Let's just keep it simple: click to open details if exists, or create then open.
    }
};

const editingDay = ref(null);
const openDayEditor = (day) => {
    if (editingDay.value === day) {
        // If clicking the same day again, check if it has a lesson and remove it
        // This follows the user request to "make it back to normal" (gray) on second click
        const lesson = getLessonForDay(day);
        if (lesson) {
            const idx = courseForm.value.lesson_plan.indexOf(lesson);
            if (idx !== -1) courseForm.value.lesson_plan.splice(idx, 1);
        }
        editingDay.value = null;
    } else {
        editingDay.value = day;
    }
};

const updateLessonDetails = (field, value) => {
    if (!editingDay.value) return;
    
    let lesson = getLessonForDay(editingDay.value);
    
    if (!lesson) {
        // Only create if we actually have some text
        if (!value) return;
        
        lesson = { day: editingDay.value, focus: '', details: '', duration: '' };
        courseForm.value.lesson_plan.push(lesson);
        courseForm.value.lesson_plan.sort((a, b) => a.day - b.day);
    }
    
    lesson[field] = value;
    
    // Cleanup only if all essential fields are empty to prevent accidental deletion while editing
    if (!lesson.focus && !lesson.details && !lesson.duration) {
        const idx = courseForm.value.lesson_plan.indexOf(lesson);
        if (idx !== -1) courseForm.value.lesson_plan.splice(idx, 1);
    }
};

const saveDayConfirm = () => {
    if (!editingDay.value) return;
    
    const lesson = getLessonForDay(editingDay.value);
    if (!lesson || !lesson.focus?.trim()) {
        showError('Missing Focus', 'Please enter a training focus (e.g. Chest Day) before saving.');
        return;
    }

    showConfirm(
        'Confirm Session?',
        `Ready to save details for Day ${editingDay.value}?`,
        () => {
            editingDay.value = null;
            showSuccess('Day Updated', 'Session details have been saved.');
        }
    );
};

const clearDayConfirm = () => {
    const lesson = getLessonForDay(editingDay.value);
    if (!lesson) return;
    showConfirm(
        'Clear this Day?',
        'Are you sure you want to remove all details for this session?',
        () => {
            const idx = courseForm.value.lesson_plan.indexOf(lesson);
            if (idx !== -1) courseForm.value.lesson_plan.splice(idx, 1);
            editingDay.value = null;
            showSuccess('Day Cleared', 'This day has been reset.');
        }
    );
};

const submitCourse = () => {
    showConfirm(
        courseForm.value.id ? 'Save Changes?' : 'Publish Course?',
        courseForm.value.id ? 'Do you want to update this program blueprint?' : 'Ready to make this training program live?',
        () => {
            if (courseForm.value.id) {
                router.patch(`/api/trainer/courses/${courseForm.value.id}`, courseForm.value, {
                    onSuccess: () => {
                        isCourseModalOpen.value = false;
                        showSuccess('Changes Saved', 'Your course blueprint has been updated.');
                    }
                });
            } else {
                router.post('/api/trainer/courses', courseForm.value, {
                    onSuccess: () => {
                        isCourseModalOpen.value = false;
                        showSuccess('Course Created', 'Your new training program is now live.');
                    }
                });
            }
        }
    );
};

const deleteCourse = (id) => {
    confirmModal.value = {
        isOpen: true,
        title: 'Delete Course?',
        message: 'This action cannot be undone. All lessons in this blueprint will be removed.',
        type: 'confirm',
        onConfirm: () => {
            router.delete(`/api/trainer/courses/${id}`, {
                onSuccess: () => showSuccess('Course Deleted', 'Your training program has been removed.')
            });
        }
    };
};

const openClientDetail = async (client) => {
    selectedClient.value = client;
    isFetchingDetails.value = true;
    isDetailModalOpen.value = true;
    activeDetailTab.value = 'progress';
    
    try {
        const [metricsRes, historyRes] = await Promise.all([
            axios.get(`/api/clients/${client.user.id}`),
            axios.get(`/api/clients/${client.user.id}/history`)
        ]);
        clientMetrics.value = metricsRes.data.metrics || [];
        clientHistory.value = historyRes.data.history || [];
    } catch (error) {
        console.error('Failed to fetch client details:', error);
    } finally {
        isFetchingDetails.value = false;
    }
};

const openSyllabusDay = (course, day) => {
    openCourseModal(course);
    editingDay.value = day;
};

const openManualBooking = (client = null) => {
    if (client) {
        manualBookingForm.value.user_id = client.user_id;
    }
    isManualBookingOpen.value = true;
};

const submitManualBooking = () => {
    router.post('/api/trainer/manual-book', manualBookingForm.value, {
        onSuccess: () => {
            isManualBookingOpen.value = false;
            manualBookingForm.value = { user_id: '', course_name: '', booking_date: '', notes: '' };
            showSuccess('Schedule Set', 'Manual appointment has been recorded.');
        }
    });
};

const submitSession = () => {
    router.post('/api/trainer/record-session', recordForm.value, {
        onSuccess: () => {
            isRecordingModalOpen.value = false;
            recordForm.value = {
                user_id: '',
                hours: 1,
                notes: '',
                metrics: { weight: '', body_fat: '' }
            },
            showSuccess('Record Saved', 'Session results have been logged for this student.');
        }
    });
};

const updateBookingStatus = (bookingId, status) => {
    router.patch(`/api/bookings/${bookingId}`, { status }, {
        onSuccess: () => {
            const statusMap = {
                'confirmed': ['Student Enrolled!', 'This member has been added to your ACTIVE CLIENTS list.'],
                'cancelled': ['Request Declined', 'The booking has been successfully removed.'],
                'completed': ['Session Finished', 'Great job! Training results have been logged.']
            };
            const [title, msg] = statusMap[status] || ['Updated', 'Status has been changed.'];
            showSuccess(title, msg);
        }
    });
};

// Schedule Methods
const fetchSchedules = async () => {
    try {
        const response = await axios.get('/api/trainer/schedules');
        schedules.value = response.data.schedules;
    } catch (error) {
        console.error('Failed to fetch schedules:', error);
    }
};

const openScheduleModal = (dateStr) => {
    const today = new Date().toISOString().split('T')[0];
    if (dateStr < today) return;

    const existing = schedules.value.find(s => s.date === dateStr);
    scheduleForm.value = {
        date: dateStr,
        focus_area: existing ? existing.focus_area : '',
        description: existing ? existing.description : ''
    };
    isScheduleModalOpen.value = true;
};

const submitSchedule = async () => {
    try {
        await axios.post('/api/trainer/schedules', scheduleForm.value);
        isScheduleModalOpen.value = false;
        fetchSchedules();
        showSuccess('Plan Updated', 'Your teaching schedule has been saved.');
    } catch (error) {
        console.error('Failed to save schedule:', error);
        alert('Failed to save schedule. Please try again.');
    }
};

// Calendar Helpers
const daysInMonth = (year, month) => new Date(year, month + 1, 0).getDate();
const firstDayOfMonth = (year, month) => new Date(year, month, 1).getDay();

const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const days = [];
    
    const today = new Date().toISOString().split('T')[0];
    
    // Empty slots for days of previous month
    const firstDay = firstDayOfMonth(year, month);
    for (let i = 0; i < firstDay; i++) {
        days.push({ day: null });
    }
    
    // Days of current month
    const totalDays = daysInMonth(year, month);
    for (let i = 1; i <= totalDays; i++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const hasSchedule = schedules.value.find(s => s.date === dateStr);
        days.push({ 
            day: i, 
            dateStr,
            hasSchedule,
            isToday: dateStr === today,
            isPast: dateStr < today
        });
    }
    
    return days;
});

const changeMonth = (offset) => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + offset, 1);
};

// Initial fetch
fetchSchedules();

// Agenda Grouping logic
const groupedBookings = computed(() => {
    const groups = {
        requests: [], // New requests (pending)
        today: [],
        tomorrow: [],
        upcoming: []
    };
    
    const today = new Date().setHours(0,0,0,0);
    const tomorrow = new Date(today + 86400000).setHours(0,0,0,0);
    
    props.bookings.forEach(b => {
        // Separate all pending requests regardless of date
        if (b.status === 'pending') {
            groups.requests.push(b);
        } else {
            const d = new Date(b.booking_date).setHours(0,0,0,0);
            if (d === today) groups.today.push(b);
            else if (d === tomorrow) groups.tomorrow.push(b);
            else groups.upcoming.push(b);
        }
    });
    
    return groups;
});

// SVG Graph Helpers
const getGraphPath = (metrics, type, width, height) => {
    const data = metrics.filter(m => m.metric_name === type);
    if (data.length < 2) return '';
    
    const values = data.map(d => parseFloat(d.metric_value));
    const min = Math.min(...values) * 0.98;
    const max = Math.max(...values) * 1.02;
    const range = max - min;
    
    return data.map((d, i) => {
        const x = (i / (data.length - 1)) * width;
        const y = height - ((parseFloat(d.metric_value) - min) / range) * height;
        return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
    }).join(' ');
};

</script>

<template>
    <MobileLayout>
        <Head title="Trainer Dashboard" />

        <div class="p-6">
            <header class="flex items-center gap-4 mb-8">
                <button @click="router.get(route('mobile.profile'))" class="size-10 rounded-full border border-[var(--border-color)] flex items-center justify-center flex-shrink-0 active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-[var(--text-main)]">arrow_back</span>
                </button>
                <div>
                    <h1 class="text-2xl font-black italic uppercase tracking-tighter text-[var(--text-main)] leading-none">Trainer Mode</h1>
                    <p class="text-[10px] font-black text-[var(--theme-color)] uppercase tracking-wider mt-1">{{ trainer.specialty }}</p>
                </div>
            </header>

            <div class="px-8 mb-8">
            <div class="flex p-1.5 bg-[var(--card-bg)] rounded-[24px] border border-[var(--border-color)]">
                <button 
                    @click="activeTab = 'clients'"
                    class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl transition-all"
                    :style="activeTab === 'clients' ? { backgroundColor: 'var(--theme-color)', boxShadow: '0 8px 16px rgba(var(--theme-color-rgb), 0.2)' } : {}"
                    :class="activeTab === 'clients' ? 'text-white shadow-lg' : 'text-[var(--text-muted)]'"
                >
                    Clients
                </button>
                <button 
                    @click="activeTab = 'bookings'"
                    class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl transition-all"
                    :style="activeTab === 'bookings' ? { backgroundColor: 'var(--theme-color)', boxShadow: '0 8px 16px rgba(var(--theme-color-rgb), 0.2)' } : {}"
                    :class="activeTab === 'bookings' ? 'text-white shadow-lg' : 'text-[var(--text-muted)]'"
                >
                    Agenda
                </button>
                <button 
                    @click="activeTab = 'schedule'"
                    class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl transition-all"
                    :style="activeTab === 'schedule' ? { backgroundColor: 'var(--theme-color)', boxShadow: '0 8px 16px rgba(var(--theme-color-rgb), 0.2)' } : {}"
                    :class="activeTab === 'schedule' ? 'text-white shadow-lg' : 'text-[var(--text-muted)]'"
                >
                    Plan (Management)
                </button>
            </div>
        </div>

            <!-- Clients List -->
            <div v-if="activeTab === 'clients'" class="space-y-4">
                <div class="flex items-center justify-between px-1 mb-6">
                    <div>
                        <h3 class="text-[12px] font-black uppercase tracking-[0.2em] text-[var(--text-main)] italic">Training Programs</h3>
                        <p class="text-[8px] font-black text-[var(--text-muted)] mt-0.5 uppercase tracking-tighter opacity-60">Separate course enrollment & member stats</p>
                    </div>
                </div>

                <!-- Course Summary Dashboard -->
                <div class="grid grid-cols-2 gap-3 mb-12">
                    <div 
                        @click="selectedCourseFilter = 'All'"
                        class="p-5 rounded-[32px] border-2 transition-all cursor-pointer relative overflow-hidden"
                        :class="selectedCourseFilter === 'All' ? 'border-[var(--theme-color)] bg-[var(--theme-color)]/5 shadow-lg shadow-[var(--theme-color)]/10' : 'border-[var(--border-color)] bg-[var(--card-bg)] opacity-60'"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <span class="material-symbols-outlined text-[var(--theme-color)]">groups</span>
                            <span class="text-[10px] font-black text-[var(--text-muted)]">ALL</span>
                        </div>
                        <h4 class="text-[18px] font-black text-[var(--text-main)] leading-none mb-1">{{ clients.length }}</h4>
                        <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest">Total Members</p>
                    </div>

                    <div 
                        v-for="(members, courseName) in groupedClientsList" :key="courseName"
                        @click="selectedCourseFilter = courseName"
                        class="p-5 rounded-[32px] border-2 transition-all cursor-pointer relative overflow-hidden group"
                        :class="selectedCourseFilter === courseName ? 'border-[var(--theme-color)] bg-[var(--theme-color)]/5 shadow-lg shadow-[var(--theme-color)]/10' : 'border-[var(--border-color)] bg-[var(--card-bg)] hover:opacity-100 opacity-60'"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <span class="material-symbols-outlined text-[var(--theme-color)]">fitness_center</span>
                            <span class="px-2 py-0.5 rounded-lg bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[6px] font-black">{{ members.length }}</span>
                        </div>
                        <h4 class="text-[10px] font-black text-[var(--text-main)] uppercase tracking-tight truncate mb-1">{{ courseName }}</h4>
                        <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest">Course Count</p>
                    </div>
                </div>

                <div v-if="clients.length === 0" class="text-center py-12">
                    <span class="material-symbols-outlined text-4xl text-[var(--text-muted)] opacity-20 mb-2">group_off</span>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest">No active clients yet</p>
                </div>
                
                <!-- Detailed Member List by Course -->
                <div v-for="(members, courseName) in groupedClientsList" :key="'list-' + courseName">
                    <div v-if="(selectedCourseFilter === 'All' || selectedCourseFilter === courseName) && members.length > 0" class="space-y-4 mb-10">
                        <div class="flex items-center justify-between px-2 mb-4 border-l-4 border-[var(--theme-color)] pl-4">
                            <div>
                                <h4 class="text-[12px] font-black uppercase tracking-widest text-[var(--text-main)] flex items-center gap-2">
                                    {{ courseName }}
                                    <span class="size-1.5 rounded-full bg-[var(--theme-color)]"></span>
                                </h4>
                                <p class="text-[8px] font-bold text-[var(--text-muted)] uppercase tracking-tighter opacity-60">Members Enrolled in this Course</p>
                            </div>
                            <div class="px-3 py-1 bg-[var(--theme-color)]/10 text-[var(--theme-color)] rounded-full text-[9px] font-black italic">
                                {{ members.length }} PEOPLE
                            </div>
                        </div>

                        <!-- Member Cards in this Course -->
                        <div class="space-y-3">
                            <div v-for="client in members" :key="client.id" 
                                @click="openClientDetail(client)"
                                class="p-5 bg-[var(--card-bg)] rounded-[32px] border border-[var(--border-color)] flex items-center justify-between active:scale-[0.98] transition-all cursor-pointer shadow-sm hover:shadow-md"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <img :src="client.user.profile_photo_url" class="size-12 rounded-2xl object-cover border-2 border-white shadow-sm ring-1 ring-[var(--border-color)]">
                                        <div class="absolute -bottom-1 -right-1 size-5 rounded-full bg-green-500 border-2 border-white flex items-center justify-center">
                                            <span class="material-symbols-outlined text-white text-[10px] font-bold">check</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-tight">{{ client.user.name }}</h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="h-1.5 w-20 bg-[var(--page-bg)] rounded-full overflow-hidden border border-[var(--border-color)]/30">
                                                <div 
                                                    class="h-full bg-[var(--theme-color)]" 
                                                    :style="{ width: (client.used_hours / client.total_hours * 100) + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-[9px] font-black text-[var(--text-muted)] uppercase italic">
                                                {{ client.used_hours }}/{{ client.total_hours }}H
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button 
                                    @click.stop="openRecordModal(client)"
                                    class="size-11 rounded-2xl bg-[var(--theme-color)]/10 text-[var(--theme-color)] transition-all active:scale-90 flex items-center justify-center border border-[var(--theme-color)]/20"
                                >
                                    <span class="material-symbols-outlined text-sm font-black">edit_note</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings List (Agenda View) -->
            <div v-if="activeTab === 'bookings'" class="space-y-8 pt-4">
                <div v-if="groupedBookings.requests.length === 0" class="text-center py-20">
                    <div class="size-20 bg-[var(--page-bg)] rounded-full flex items-center justify-center mx-auto mb-6 border border-[var(--border-color)]/30">
                        <span class="material-symbols-outlined text-4xl text-[var(--text-muted)] opacity-20">person_add</span>
                    </div>
                    <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-widest mb-2">No New Requests</h3>
                    <p class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-tighter opacity-60">Wait for new members to join your program</p>
                </div>

                <!-- New Requests Section -->
                <div v-if="groupedBookings.requests.length > 0" class="mb-10">
                    <h3 class="flex items-center justify-between px-1 mb-6">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-amber-500 animate-ping"></span>
                            <span class="text-[11px] font-black uppercase tracking-[0.2em] text-amber-500">Member Requests</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-600 text-[10px] font-black italic">{{ groupedBookings.requests.length }} Waiting</span>
                    </h3>
                    
                    <div class="space-y-4">
                        <div v-for="booking in groupedBookings.requests" :key="booking.id" class="p-6 bg-[var(--card-bg)] rounded-[32px] border border-[var(--border-color)] shadow-sm relative overflow-hidden group">
                            <!-- Subtle Background Pattern -->
                            <div class="absolute -right-4 -top-4 opacity-[0.05] rotate-12 transition-transform group-hover:scale-110 duration-700">
                                <span class="material-symbols-outlined text-[80px] text-[var(--theme-color)]">person_add</span>
                            </div>
                            
                            <div class="flex items-start justify-between mb-6 relative z-10">
                                <div class="flex items-center gap-4">
                                    <img :src="booking.user.profile_photo_url" class="size-14 rounded-2xl object-cover border-2 border-white shadow-md">
                                    <div>
                                        <h3 class="text-sm font-black text-[var(--text-main)] uppercase tracking-tight">{{ booking.user.name }}</h3>
                                        <div v-if="booking.course_name" class="flex items-center gap-1.5 mt-1">
                                            <span class="px-2 py-0.5 rounded-lg bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[7px] font-black uppercase italic tracking-wider">{{ booking.course_name }}</span>
                                        </div>
                                        <p class="text-[10px] font-bold text-[var(--text-muted)] mt-1 opacity-60">
                                            Requested on {{ new Date(booking.booking_date).toLocaleDateString() }} @ {{ new Date(booking.booking_date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 relative z-10">
                                <button 
                                    @click="updateBookingStatus(booking.id, 'confirmed')" 
                                    class="flex-[2] py-4 bg-[var(--theme-color)] text-white text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-[var(--theme-color)]/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2 border-b-4 border-orange-700"
                                >
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    รับเข้าคอร์ส
                                </button>
                                <button 
                                    @click="updateBookingStatus(booking.id, 'cancelled')" 
                                    class="flex-1 py-4 bg-[var(--page-bg)] text-[var(--text-muted)] text-[10px] font-black uppercase tracking-widest rounded-2xl border border-[var(--border-color)] hover:bg-rose-50 hover:text-rose-500 hover:border-rose-100 active:scale-95 transition-all flex items-center justify-center gap-2"
                                >
                                    ปฏิเสธ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Courses & Blueprint Management -->
            <div v-if="activeTab === 'schedule'" class="space-y-8 pb-20">
                
                <!-- Section Header with Sub-tab Switcher -->
                <div class="flex items-center justify-between px-1 mb-2">
                    <div>
                        <h3 class="text-[11px] font-black uppercase tracking-[0.1em] text-[var(--text-main)] transition-colors">
                            {{ coursesSubTab === 'blueprint' ? 'Course Blueprint List' : 'Your Training Catalog' }}
                        </h3>
                        <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest opacity-60 mt-0.5">
                            {{ coursesSubTab === 'blueprint' ? 'Manage curriculum & daily lessons' : 'Course pricing & public visibility' }}
                        </p>
                    </div>

                    <!-- Modern Switcher (In Red Circle Area) -->
                    <div class="flex p-1 bg-[var(--card-bg)] border border-[var(--border-color)] rounded-2xl shadow-sm">
                        <button 
                            @click="coursesSubTab = 'blueprint'"
                            class="px-4 py-2 text-[9px] font-black uppercase tracking-tighter rounded-xl transition-all flex items-center gap-1.5"
                            :style="coursesSubTab === 'blueprint' ? { backgroundColor: 'var(--theme-color)', boxShadow: '0 8px 16px rgba(var(--theme-color-rgb), 0.2)' } : {}"
                            :class="coursesSubTab === 'blueprint' ? 'text-white shadow-lg' : 'text-[var(--text-muted)]'"
                        >
                            <span class="material-symbols-outlined text-xs">grid_view</span>
                            Plan
                        </button>
                        <button 
                            @click="coursesSubTab = 'catalog'"
                            class="px-4 py-2 text-[9px] font-black uppercase tracking-tighter rounded-xl transition-all flex items-center gap-1.5"
                            :style="coursesSubTab === 'catalog' ? { backgroundColor: 'var(--theme-color)', boxShadow: '0 8px 16px rgba(var(--theme-color-rgb), 0.2)' } : {}"
                            :class="coursesSubTab === 'catalog' ? 'text-white shadow-lg' : 'text-[var(--text-muted)]'"
                        >
                            <span class="material-symbols-outlined text-xs">list_alt</span>
                            List
                        </button>
                    </div>
                </div>

                <!-- VIEW 1: BLUEPRINT GRID -->
                <div v-if="coursesSubTab === 'blueprint'" class="space-y-6">
                    <div v-for="course in courses" :key="course.id" class="space-y-4">
                        <!-- Course Header Card -->
                        <div class="p-6 bg-[var(--card-bg)] rounded-[40px] border border-[var(--border-color)] shadow-sm relative overflow-hidden">
                            <!-- Background Decor -->
                            <div class="absolute -right-4 -top-4 opacity-[0.03] rotate-12 transition-transform duration-700">
                                <span class="material-symbols-outlined text-[100px]" :style="{ color: getCourseColor(course.id).primary }">fitness_center</span>
                            </div>

                            <div class="flex items-center justify-between mb-8 relative z-10">
                                <div>
                                    <h3 class="text-base font-black uppercase italic" :style="{ color: getCourseColor(course.id).primary }">
                                        {{ course.title }}
                                    </h3>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest">{{ course.duration }}</span>
                                        <span class="size-1 rounded-full bg-[var(--border-color)]"></span>
                                        <span class="text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest">{{ course.level }}</span>
                                    </div>
                                </div>
                                <button 
                                    @click="openCourseModal(course)"
                                    class="size-10 flex items-center justify-center bg-[var(--page-bg)] border border-[var(--border-color)] text-[var(--text-muted)] rounded-2xl active:scale-95 transition-all"
                                >
                                    <span class="material-symbols-outlined text-sm">settings</span>
                                </button>
                            </div>

                            <!-- Dashboard Stats inside Card -->
                            <div class="flex items-center gap-8 mb-8 px-1">
                                <div class="flex flex-col">
                                    <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-1">Program Status</span>
                                    <span class="text-[10px] font-black uppercase tracking-widest italic" :style="{ color: getCourseColor(course.id).primary }">
                                        {{ course.lesson_plan?.length || 0 }} of {{ getCourseMaxDays(course) }} days set
                                    </span>
                                </div>
                                <div class="h-8 w-px bg-[var(--border-color)]"></div>
                                <div class="flex flex-col">
                                    <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-1">Target Hours</span>
                                    <span class="text-[10px] font-black text-[var(--text-main)] uppercase italic">{{ course.hours || 10 }}H Course</span>
                                </div>
                            </div>

                            <!-- Mini Syllabus Grid -->
                            <div class="grid grid-cols-7 gap-1.5 p-1 bg-[var(--page-bg)]/50 rounded-[28px] border border-[var(--border-color)]/30">
                                <div v-for="d in ['S','M','T','W','T','F','S']" :key="d" class="text-[6px] font-black text-center text-[var(--text-muted)] opacity-30">{{d}}</div>
                                <div v-for="day in getCourseMaxDays(course)" :key="day" 
                                    @click="openSyllabusDay(course, day)"
                                    class="aspect-square rounded-[12px] flex items-center justify-center transition-all cursor-pointer relative active:scale-95 overflow-hidden border"
                                    :class="[
                                        course.lesson_plan?.find(l => l.day === day)
                                            ? 'text-white border-transparent' 
                                            : 'bg-[var(--page-bg)] border-[var(--border-color)]/20 text-[var(--text-muted)]/20'
                                    ]"
                                    :style="course.lesson_plan?.find(l => l.day === day) ? {
                                        background: getCourseColor(course).primary,
                                        boxShadow: `0 4px 10px -2px ${getCourseColor(course).shadow}`
                                    } : {}"
                                >
                                    <span class="text-[9px] font-black">{{ day }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIEW 2: COURSE CATALOG LIST -->
                <div v-if="coursesSubTab === 'catalog'" class="space-y-6">
                    <div class="flex items-center justify-between px-1 p-4 rounded-3xl border border-[var(--theme-color)]/10" style="background-color: rgba(var(--theme-color-rgb), 0.05);">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[var(--theme-color)]">info</span>
                            <p class="text-[9px] font-black text-[var(--text-main)] uppercase italic">Public Listing Management</p>
                        </div>
                        <button 
                            @click="openCourseModal()"
                            class="px-4 py-2 text-white text-[9px] font-black uppercase tracking-widest rounded-xl transition-all flex items-center gap-2"
                            :style="{ backgroundColor: 'var(--theme-color)', boxShadow: '0 10px 20px rgba(var(--theme-color-rgb), 0.2)' }"
                        >
                            <span class="material-symbols-outlined text-sm">add</span> New Course
                        </button>
                    </div>

                    <div v-if="courses.length === 0" class="text-center py-12">
                        <span class="material-symbols-outlined text-4xl text-[var(--text-muted)] opacity-20 mb-2">exercise</span>
                        <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest">You haven't added any courses yet</p>
                    </div>

                    <div v-for="course in courses" :key="course.id" class="p-6 bg-[var(--card-bg)] rounded-[32px] border border-[var(--border-color)] group hover:border-[var(--theme-color)]/30 transition-all">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h4 class="text-lg font-black italic uppercase text-[var(--text-main)] group-hover:text-[var(--theme-color)] transition-colors">{{ course.title }}</h4>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-wider">{{ course.duration }}</span>
                                    <span class="size-1 rounded-full bg-[var(--border-color)]"></span>
                                    <span class="text-[10px] font-black text-[var(--theme-color)] uppercase tracking-wider">{{ course.level }}</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="openCourseModal(course)" class="size-9 rounded-xl border border-[var(--border-color)] flex items-center justify-center text-[var(--text-muted)] hover:text-[var(--theme-color)] transition-all active:scale-95">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button @click="deleteCourse(course.id)" class="size-9 rounded-xl border border-[var(--border-color)] flex items-center justify-center text-[var(--text-muted)] hover:text-red-500 transition-all active:scale-95">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] font-bold text-[var(--text-muted)] leading-relaxed line-clamp-2">
                            {{ course.description }}
                        </p>
                        <div v-if="course.price" class="mt-4 pt-4 border-t border-[var(--border-color)]/50 flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black uppercase text-[var(--text-muted)] tracking-widest">Total Sessions</span>
                                <span class="text-sm font-black italic text-[var(--text-main)]">{{ course.hours }} Hours</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-[9px] font-black uppercase text-[var(--text-muted)] tracking-widest">Price per course</span>
                                <span class="text-sm font-black italic text-[var(--text-main)]">{{ Number(course.price).toLocaleString() }} THB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Detail Modal -->
        <div v-if="isDetailModalOpen" class="fixed inset-0 z-[100] flex items-end justify-center bg-black/40 backdrop-blur-sm p-0 sm:p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-t-[40px] sm:rounded-[40px] h-[90vh] overflow-hidden flex flex-col shadow-2xl border border-[var(--border-color)]">
                <!-- Header -->
                <div class="p-8 pb-4 flex items-center justify-between bg-gradient-to-b from-[var(--page-bg)] to-transparent">
                    <div class="flex items-center gap-4">
                        <img :src="selectedClient?.user.profile_photo_url" class="size-16 rounded-[24px] object-cover border-2 border-[var(--theme-color)]">
                        <div>
                            <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">{{ selectedClient?.user.name }}</h2>
                            <p class="text-[9px] font-black text-[var(--theme-color)] uppercase tracking-widest mt-0.5">Active Student</p>
                        </div>
                    </div>
                    <button @click="isDetailModalOpen = false" class="size-10 rounded-full border border-[var(--border-color)] flex items-center justify-center bg-[var(--card-bg)]">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="px-8 mt-2">
                    <div class="flex p-1 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)]">
                        <button 
                            @click="activeDetailTab = 'progress'"
                            class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
                            :class="activeDetailTab === 'progress' ? 'bg-[var(--theme-color)] text-white shadow-lg' : 'text-[var(--text-muted)]'"
                        >
                            Progress
                        </button>
                        <button 
                            @click="activeDetailTab = 'history'"
                            class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
                            :class="activeDetailTab === 'history' ? 'bg-[var(--theme-color)] text-white shadow-lg' : 'text-[var(--text-muted)]'"
                        >
                            History
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-8 pb-10 space-y-8 no-scrollbar pt-4">
                    <!-- Progress Stats -->
                    <div v-if="isFetchingDetails" class="flex flex-col items-center justify-center py-20">
                        <div class="size-8 border-4 border-[var(--theme-color)]/20 border-t-[var(--theme-color)] rounded-full animate-spin"></div>
                    </div>

                    <template v-else>
                        <!-- Progress Tab -->
                        <div v-if="activeDetailTab === 'progress'" class="space-y-8">
                            <!-- Graphs Section -->
                            <div class="space-y-6">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)]">Progress Overview</h3>
                                
                                <!-- Weight Graph -->
                                <div class="bg-[var(--page-bg)] p-6 rounded-[32px] border border-[var(--border-color)]">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)]">Weight History (kg)</span>
                                    </div>
                                    <div class="h-32 w-full relative">
                                        <svg class="h-full w-full overflow-visible">
                                            <path 
                                                :d="getGraphPath(clientMetrics, 'weight', 300, 128)" 
                                                fill="none" 
                                                stroke="var(--theme-color)" 
                                                stroke-width="3" 
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Body Fat Graph -->
                                <div class="bg-[var(--page-bg)] p-6 rounded-[32px] border border-[var(--border-color)]">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)]">Body Fat (%)</span>
                                    </div>
                                    <div class="h-32 w-full relative">
                                        <svg class="h-full w-full overflow-visible">
                                            <path 
                                                :d="getGraphPath(clientMetrics, 'body_fat', 300, 128)" 
                                                fill="none" 
                                                stroke="#ef4444" 
                                                stroke-width="3" 
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-[var(--card-bg)] p-6 rounded-[32px] border border-[var(--border-color)]">
                                    <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-1">Package Done</p>
                                    <p class="text-xl font-black italic text-[var(--text-main)]">{{ (selectedClient?.used_hours / selectedClient?.total_hours * 100).toFixed(0) }}%</p>
                                </div>
                                <div class="bg-[var(--card-bg)] p-6 rounded-[32px] border border-[var(--border-color)]">
                                    <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-1">Left</p>
                                    <p class="text-xl font-black italic text-[var(--theme-color)]">{{ selectedClient?.total_hours - selectedClient?.used_hours }}h</p>
                                </div>
                            </div>
                        </div>

                        <!-- History Tab -->
                        <div v-if="activeDetailTab === 'history'" class="space-y-4">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)]">Training Logs</h3>
                            <div v-if="clientHistory.length === 0" class="text-center py-10 opacity-30">
                                <span class="material-symbols-outlined text-4xl">history_toggle_off</span>
                                <p class="text-[9px] font-black uppercase tracking-widest mt-2 px-10">No past sessions recorded yet</p>
                            </div>
                            <div v-for="session in clientHistory" :key="session.id" class="p-5 bg-[var(--page-bg)] rounded-[32px] border border-[var(--border-color)]">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-2 py-0.5 rounded-lg bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[8px] font-black uppercase italic">{{ session.type || 'Session' }}</span>
                                    <span class="text-[8px] font-bold text-[var(--text-muted)] uppercase">{{ new Date(session.created_at).toLocaleDateString() }}</span>
                                </div>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-[10px] font-black text-[var(--text-main)] uppercase tracking-tight">{{ session.hours }} Hours Training</span>
                                </div>
                                <p v-if="session.notes" class="text-[10px] font-bold text-[var(--text-muted)] italic leading-relaxed bg-[var(--card-bg)] p-3 rounded-2xl border border-[var(--border-color)]">
                                    "{{ session.notes }}"
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Recording Modal -->
        <div v-if="isRecordingModalOpen" class="fixed inset-0 z-[100] flex items-end justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">Record Session</h2>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-1">Client: {{ selectedClient?.user.name }}</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Hours Session</label>
                        <div class="flex items-center gap-4 mt-2">
                            <button @click="recordForm.hours > 1 && recordForm.hours--" class="size-10 rounded-xl bg-[var(--page-bg)] border border-[var(--border-color)] flex items-center justify-center text-[var(--text-main)]">-</button>
                            <span class="text-xl font-black italic text-[var(--text-main)]">{{ recordForm.hours }}h</span>
                            <button @click="recordForm.hours++" class="size-10 rounded-xl bg-[var(--page-bg)] border border-[var(--border-color)] flex items-center justify-center text-[var(--text-main)]">+</button>
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Quick Metrics</label>
                        <div class="grid grid-cols-2 gap-4 mt-2">
                            <input 
                                v-model="recordForm.metrics.weight"
                                type="number" 
                                placeholder="Weight (kg)"
                                class="w-full bg-[var(--page-bg)] border border-[var(--border-color)] rounded-xl px-4 py-3 text-xs font-black text-[var(--text-main)] placeholder:text-[var(--text-muted)]/40 focus:outline-none focus:border-[var(--theme-color)]"
                            >
                            <input 
                                v-model="recordForm.metrics.body_fat"
                                type="number" 
                                placeholder="Body Fat %"
                                class="w-full bg-[var(--page-bg)] border border-[var(--border-color)] rounded-xl px-4 py-3 text-xs font-black text-[var(--text-main)] placeholder:text-[var(--text-muted)]/40 focus:outline-none focus:border-[var(--theme-color)]"
                            >
                        </div>
                    </div>

                    <button 
                        @click="submitSession"
                        class="w-full py-4 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl active:scale-[0.98] transition-all"
                        :style="{ backgroundColor: 'var(--theme-color)', boxShadow: '0 15px 30px rgba(var(--theme-color-rgb), 0.3)' }"
                    >
                        Save Record
                    </button>
                    <button 
                        @click="isRecordingModalOpen = false"
                        class="w-full py-2 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        <!-- Course Modal -->
        <div v-if="isCourseModalOpen" class="fixed inset-0 z-[100] flex items-end justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)] max-h-[90vh] flex flex-col">
                <div class="text-center mb-6">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">
                        {{ courseForm.id ? 'Edit Course' : 'Create Course' }}
                    </h2>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-1">Design your training program</p>
                </div>

                <div class="flex-1 overflow-y-auto pr-2 no-scrollbar space-y-4">
                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Course Title</label>
                        <input 
                            v-model="courseForm.title"
                            type="text" 
                            placeholder="e.g. Extreme Weight Loss"
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)] transition-all"
                        >
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Level</label>
                        <select 
                            v-model="courseForm.level"
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-xl px-4 py-3 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        >
                            <option>Beginner</option>
                            <option>Intermediate</option>
                            <option>Advanced</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Price (THB)</label>
                            <input 
                                v-model="courseForm.price"
                                type="number" 
                                placeholder="0.00"
                                disabled
                                class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-xl px-4 py-3 text-xs font-black text-[var(--text-main)] placeholder:opacity-20 cursor-not-allowed opacity-50"
                            >
                        </div>
                        <div>
                            <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Total Hours</label>
                            <input 
                                v-model="courseForm.hours"
                                type="number" 
                                placeholder="10"
                                class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-xl px-4 py-3 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Description</label>
                        <textarea 
                            v-model="courseForm.description"
                            rows="2"
                            placeholder="What will students learn?"
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        ></textarea>
                    </div>

                    <!-- Modern Syllabus Calendar Grid -->
                    <div class="space-y-4 pt-4 border-t border-[var(--border-color)]/30">
                        <div class="flex items-center justify-between px-1">
                            <h4 class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--theme-color)]">Program Calendar</h4>
                            <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest opacity-60">
                                {{ maxSyllabusDays }} Days Plan
                            </span>
                        </div>
                        
                        <!-- Calendar Legend -->
                        <div class="flex gap-4 px-1 pb-1">
                            <div class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-[var(--theme-color)]"></span>
                                <span class="text-[7px] font-black uppercase text-[var(--text-muted)]">Active Session</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-[var(--page-bg)] border border-[var(--border-color)]"></span>
                                <span class="text-[7px] font-black uppercase text-[var(--text-muted)]">Rest/Empty</span>
                            </div>
                        </div>

                        <!-- 7-Column Grid -->
                        <div class="grid grid-cols-7 gap-1.5 p-1">
                            <div v-for="d in ['S','M','T','W','T','F','S']" :key="d" class="text-[6px] font-black text-center text-[var(--text-muted)] opacity-30">{{d}}</div>
                            <div v-for="day in maxSyllabusDays" :key="day" 
                                @click="openDayEditor(day)"
                                class="aspect-square rounded-xl flex items-center justify-center transition-all cursor-pointer relative active:scale-95 overflow-hidden border"
                                :class="[
                                    getLessonForDay(day) 
                                        ? 'bg-[var(--theme-color)] text-white shadow-lg shadow-[var(--theme-color)]/20 border-accent' 
                                        : 'bg-[var(--page-bg)] border-[var(--border-color)] hover:border-[var(--theme-color)]/40',
                                    editingDay === day ? 'ring-2 ring-[var(--theme-color)] ring-offset-2 ring-offset-[var(--card-bg)]' : ''
                                ]"
                            >
                                <span class="text-[10px] font-black" :class="getLessonForDay(day) ? 'text-white' : 'text-[var(--text-muted)]/50'">{{ day }}</span>
                                <div v-if="getLessonForDay(day)?.focus" class="absolute top-1 right-1 size-1 rounded-full bg-white/60"></div>
                            </div>
                        </div>

                        <!-- Active Day Editor Popup Modal -->
                        <div v-if="editingDay" class="fixed inset-0 z-[110] flex items-center justify-center p-6 bg-black/40 backdrop-blur-sm">
                            <div class="relative w-full max-w-sm bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)] animate-in zoom-in fade-in duration-200">
                                <div class="flex items-center justify-between mb-8">
                                    <div class="flex items-center gap-4">
                                        <div class="size-14 rounded-full flex flex-col items-center justify-center text-white shadow-lg"
                                            :style="{ backgroundColor: 'var(--theme-color)', boxShadow: '0 8px 16px rgba(var(--theme-color-rgb), 0.3)' }"
                                        >
                                            <span class="text-[10px] font-black italic leading-none">DAY</span>
                                            <span class="text-xl font-black italic mt-0.5">{{ editingDay }}</span>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black uppercase italic tracking-tighter text-[var(--text-main)] italic">Session Details</h3>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-[var(--text-muted)] mt-0.5">Customizing Blueprint</p>
                                        </div>
                                    </div>
                                    <button @click="editingDay = null" class="size-10 rounded-full bg-[var(--page-bg)] flex items-center justify-center border border-[var(--border-color)] text-[var(--text-muted)] hover:text-[var(--text-main)] transition-all">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </div>

                                <div class="space-y-6">
                                    <div>
                                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Focus Area</label>
                                        <input 
                                            :value="getLessonForDay(editingDay)?.focus || ''"
                                            @input="updateLessonDetails('focus', $event.target.value)"
                                            placeholder="E.G. CHEST FUNDAMENTALS"
                                            class="w-full mt-2 bg-white/50 border border-[var(--border-color)] rounded-2xl px-6 py-5 text-xs font-black uppercase italic text-[var(--text-main)] placeholder:text-[var(--text-muted)]/20 transition-all outline-none focus:border-[var(--theme-color)]"
                                        >
                                    </div>

                                    <div>
                                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Start Time</label>
                                        <div class="mt-2 flex gap-2">
                                            <div class="flex-1 relative">
                                                <select 
                                                    :value="getTimePart(getLessonForDay(editingDay)?.duration, 'hour')"
                                                    @change="updateLessonDetails('duration', updateTimePart(getLessonForDay(editingDay)?.duration, 'hour', $event.target.value))"
                                                    class="w-full bg-white/50 border border-[var(--border-color)] rounded-2xl pl-4 pr-8 py-5 text-[11px] font-black uppercase italic text-[var(--text-main)] transition-all outline-none appearance-none text-center focus:border-[var(--theme-color)]"
                                                >
                                                    <option v-for="h in hoursOptions" :key="h" :value="h">{{ h }}</option>
                                                </select>
                                            </div>
                                            <div class="flex-1 relative">
                                                <select 
                                                    :value="getTimePart(getLessonForDay(editingDay)?.duration, 'minute')"
                                                    @change="updateLessonDetails('duration', updateTimePart(getLessonForDay(editingDay)?.duration, 'minute', $event.target.value))"
                                                    class="w-full bg-white/50 border border-[var(--border-color)] rounded-2xl pl-4 pr-8 py-5 text-[11px] font-black uppercase italic text-[var(--text-main)] transition-all outline-none appearance-none text-center focus:border-[var(--theme-color)]"
                                                >
                                                    <option v-for="m in minutesOptions" :key="m" :value="m">{{ m }}</option>
                                                </select>
                                            </div>
                                            <div class="w-24 relative">
                                                <select 
                                                    :value="getTimePart(getLessonForDay(editingDay)?.duration, 'period')"
                                                    @change="updateLessonDetails('duration', updateTimePart(getLessonForDay(editingDay)?.duration, 'period', $event.target.value))"
                                                    class="w-full bg-white/80 border-2 rounded-2xl px-2 py-5 text-[10px] font-black uppercase tracking-tighter transition-all outline-none appearance-none text-center border-[var(--theme-color)]/20 text-[var(--theme-color)]"
                                                >
                                                    <option v-for="p in periodsOptions" :key="p" :value="p">{{ p }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Workout Details</label>
                                        <textarea 
                                            :value="getLessonForDay(editingDay)?.details || ''"
                                            @input="updateLessonDetails('details', $event.target.value)"
                                            placeholder="Add exercises, set counts, or key takeaways..."
                                            rows="4"
                                            class="w-full mt-2 bg-white/50 border border-[var(--border-color)] rounded-3xl px-6 py-5 text-sm font-medium text-[var(--text-muted)] placeholder:text-[var(--text-muted)]/20 transition-all resize-none outline-none focus:border-[var(--theme-color)]"
                                        ></textarea>
                                    </div>
                                    
                                    <div class="flex flex-col gap-3 pt-2">
                                        <button @click="saveDayConfirm" 
                                            class="w-full py-5 text-white text-xs font-black uppercase tracking-[0.2em] rounded-3xl shadow-xl active:scale-95 transition-all"
                                            style="background-color: var(--theme-color); box-shadow: 0 10px 20px rgba(var(--theme-color-rgb), 0.3);"
                                        >
                                            Save Day
                                        </button>
                                        <button v-if="getLessonForDay(editingDay)" @click="clearDayConfirm" 
                                            class="w-full py-2 text-[10px] font-black text-red-500/50 uppercase tracking-widest hover:text-red-500 transition-all">
                                            Clear this Day
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex flex-col gap-3 border-t border-[var(--border-color)]/10">
                    <button 
                        @click="submitCourse"
                        class="w-full py-4 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl active:scale-[0.98] transition-all"
                        style="background-color: var(--theme-color); box-shadow: 0 10px 20px rgba(var(--theme-color-rgb), 0.3);"
                    >
                        {{ courseForm.id ? 'Save Changes' : 'Publish Course' }}
                    </button>
                    <button 
                        @click="cancelCourseEdit"
                        class="w-full py-2 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-2"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Manual Booking Modal -->
        <div v-if="isManualBookingOpen" class="fixed inset-0 z-[100] flex items-end justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">New Appointment</h2>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-1">Schedule trainer session</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Select Client</label>
                        <select 
                            v-model="manualBookingForm.user_id"
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)] transition-all"
                        >
                            <option value="" disabled>Choose a student</option>
                            <option v-for="client in clients" :key="client.id" :value="client.user.id">{{ client.user.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Date & Time</label>
                        <input 
                            v-model="manualBookingForm.booking_date"
                            type="datetime-local" 
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        >
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Notes</label>
                        <textarea 
                            v-model="manualBookingForm.notes"
                            rows="2"
                            placeholder="Optional sessions notes..."
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        ></textarea>
                    </div>

                    <div class="pt-4 flex flex-col gap-3">
                        <button 
                            @click="submitManualBooking"
                            class="w-full py-4 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl active:scale-[0.98] transition-all"
                            :style="{ backgroundColor: 'var(--theme-color)', boxShadow: '0 15px 30px rgba(var(--theme-color-rgb), 0.3)' }"
                        >
                            Create Appointment
                        </button>
                        <button 
                            @click="isManualBookingOpen = false"
                            class="w-full py-2 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Client Modal -->
        <div v-if="isAddClientModalOpen" class="fixed inset-0 z-[110] flex items-end justify-center bg-black/60 backdrop-blur-md p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">Add Client</h2>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-1">Search user by name or email</p>
                </div>

                <div class="space-y-4">
                    <div class="relative">
                        <input 
                            v-model="userSearchQuery"
                            @input="searchUsers"
                            type="text" 
                            placeholder="Type to search..."
                            class="w-full bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        >
                        <span v-if="isSearching" class="absolute right-4 top-1/2 -translate-y-1/2 size-4 border-2 border-[var(--theme-color)]/20 border-t-[var(--theme-color)] rounded-full animate-spin"></span>
                    </div>

                    <div class="max-h-60 overflow-y-auto space-y-2 no-scrollbar">
                        <div v-for="user in searchResults" :key="user.id" 
                            class="p-3 bg-[var(--page-bg)] rounded-2xl border border-[var(--border-color)] flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <img :src="user.profile_photo_url" class="size-10 rounded-xl object-cover">
                                <div>
                                    <p class="text-xs font-black text-[var(--text-main)] uppercase">{{ user.name }}</p>
                                    <p class="text-[8px] font-bold text-[var(--text-muted)]">{{ user.email }}</p>
                                </div>
                            </div>
                            <button 
                                @click="addClient(user.id)"
                                class="px-3 py-1.5 bg-[var(--theme-color)] text-white text-[8px] font-black uppercase tracking-widest rounded-lg active:scale-95 transition-all"
                            >
                                Add
                            </button>
                        </div>
                        <div v-if="userSearchQuery.length >= 2 && searchResults.length === 0 && !isSearching" class="text-center py-4 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest">
                            No users found
                        </div>
                    </div>

                    <button 
                        @click="isAddClientModalOpen = false; searchResults = []; userSearchQuery = '';"
                        class="w-full py-4 bg-[var(--page-bg)] text-[var(--text-muted)] text-[10px] font-black uppercase tracking-widest rounded-2xl border border-[var(--border-color)] mt-2"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Booking Modal -->
        <div v-if="isEditBookingOpen" class="fixed inset-0 z-[100] flex items-end justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">Reschedule</h2>
                    <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-1">Adjust session time</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">New Date & Time</label>
                        <input 
                            v-model="editBookingForm.booking_date"
                            type="datetime-local" 
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        >
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Notes</label>
                        <textarea 
                            v-model="editBookingForm.notes"
                            rows="2"
                            placeholder="Optional notes for the change..."
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        ></textarea>
                    </div>

                    <div class="pt-4 flex flex-col gap-3">
                        <button 
                            @click="submitEditBooking"
                            class="w-full py-4 bg-[var(--theme-color)] text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-[var(--theme-color)]/20 active:scale-[0.98] transition-all"
                        >
                            Save Changes
                        </button>
                        <button 
                            @click="isEditBookingOpen = false"
                            class="w-full py-2 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Schedule Entry Modal -->
        <div v-if="isScheduleModalOpen" class="fixed inset-0 z-[110] flex items-end justify-center bg-black/60 backdrop-blur-md p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">Daily Teaching Plan</h2>
                    <p class="text-[10px] font-black text-[var(--theme-color)] uppercase tracking-widest mt-1">{{ new Date(scheduleForm.date).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Training Focus</label>
                        <input 
                            v-model="scheduleForm.focus_area"
                            type="text" 
                            placeholder="e.g. Legs & Glutes Day"
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)] transition-all"
                        >
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Teaching Details</label>
                        <textarea 
                            v-model="scheduleForm.description"
                            rows="4"
                            placeholder="What will you teach today? e.g. Deadlifts technique, Squat progression..."
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] placeholder:text-[var(--text-muted)]/40 focus:outline-none focus:border-[var(--theme-color)] transition-all"
                        ></textarea>
                    </div>

                    <div class="pt-2">
                        <button 
                            @click="submitSchedule"
                            class="w-full py-5 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl active:scale-[0.98] transition-all"
                            :style="{ backgroundColor: 'var(--theme-color)', boxShadow: '0 15px 30px rgba(var(--theme-color-rgb), 0.3)' }"
                        >
                            Update Schedule
                        </button>
                        <button 
                            @click="isScheduleModalOpen = false"
                            class="w-full py-4 text-[9px] font-black text-[var(--text-muted)] uppercase tracking-widest mt-2"
                        >
                            Discard
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Success/Confirm Modal -->
        <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[200] flex items-center justify-center p-6 bg-black/40 backdrop-blur-sm animate-in fade-in duration-300">
            <div class="relative w-full max-w-xs bg-[var(--card-bg)] rounded-[40px] p-8 shadow-2xl border border-white/20 overflow-hidden animate-in zoom-in duration-300">
                <!-- Background Glow -->
                <div class="absolute -right-12 -top-12 size-40 rounded-full blur-3xl opacity-20" :style="{ backgroundColor: confirmModal.type === 'error' ? '#ef4444' : 'var(--theme-color)' }"></div>

                <div class="text-center">
                    <div class="size-20 rounded-full flex items-center justify-center mx-auto mb-6 border shadow-lg"
                        :style="confirmModal.type !== 'error' ? { 
                            backgroundColor: 'rgba(var(--theme-color-rgb), 0.1)', 
                            borderColor: 'rgba(var(--theme-color-rgb), 0.2)', 
                            boxShadow: '0 8px 16px rgba(var(--theme-color-rgb), 0.1)' 
                        } : {
                            backgroundColor: '#ef444411',
                            borderColor: '#ef444422',
                            boxShadow: '0 8px 16px #ef444411'
                        }"
                    >
                        <span class="material-symbols-outlined text-4xl" 
                            :style="{ color: confirmModal.type === 'error' ? '#ef4444' : 'var(--theme-color)' }"
                        >
                            {{ confirmModal.type === 'success' ? 'verified' : (confirmModal.type === 'error' ? 'error' : 'help_center') }}
                        </span>
                    </div>

                    <h3 class="text-xl font-black italic uppercase text-[var(--text-main)] mb-3 tracking-tighter"
                        :style="{ color: confirmModal.type === 'error' ? '#ef4444' : 'var(--theme-color)' }"
                    >
                        {{ confirmModal.title }}
                    </h3>
                    <p class="text-xs font-semibold text-[var(--text-muted)] mb-8 leading-relaxed">
                        {{ confirmModal.message }}
                    </p>

                    <div class="flex gap-3">
                        <button v-if="confirmModal.type === 'confirm'" 
                            @click="confirmModal.isOpen = false"
                            class="flex-1 py-4 rounded-[20px] text-[10px] font-black uppercase text-[var(--text-muted)] bg-[var(--page-bg)] active:scale-95 transition-all text-center"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="() => { confirmModal.onConfirm?.(); confirmModal.isOpen = false; }"
                            class="flex-1 py-4 rounded-[20px] text-[10px] font-black uppercase text-white shadow-xl active:scale-95 transition-all"
                            :style="{ 
                                backgroundColor: confirmModal.type === 'error' ? '#ef4444' : 'var(--theme-color)', 
                                boxShadow: confirmModal.type === 'error' ? '0 10px 20px #ef444444' : '0 10px 20px rgba(var(--theme-color-rgb), 0.3)' 
                            }"
                        >
                            {{ confirmModal.type === 'confirm' ? 'Yes, Confirm' : (confirmModal.type === 'error' ? 'Got it' : 'Awesome!') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
