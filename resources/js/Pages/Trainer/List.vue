<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { useI18n } from '@/language';

const { t } = useI18n();
const page = usePage();
const props = defineProps(['trainers']);

const selectedTrainer = ref(null);
const isBookingModalOpen = ref(false);
const bookingForm = ref({
    trainer_id: '',
    course_name: '',
    booking_date: '',
    notes: ''
});

const openBooking = (trainer) => {
    selectedTrainer.value = trainer;
    bookingForm.value.trainer_id = trainer.id;
    if (trainer.courses && trainer.courses.length > 0) {
        bookingForm.value.course_name = trainer.courses[0].title;
    }
    isBookingModalOpen.value = true;
};

const submitBooking = () => {
    router.post(route('trainer.book'), bookingForm.value, {
        onSuccess: () => {
            isBookingModalOpen.value = false;
            alert('Booking requested successfully!');
        }
    });
};

const getStars = (rating) => {
    const stars = [];
    const r = rating || 0;
    for (let i = 1; i <= 5; i++) {
        if (i <= r) stars.push('star');
        else if (i - 0.5 <= r) stars.push('star_half');
        else stars.push('star_outline');
    }
    return stars;
};

</script>

<template>
    <MobileLayout>
        <Head title="Find Your Trainer" />

        <div class="p-6">
            <header class="flex items-center gap-4 mb-8">
                <button @click="router.get(route('mobile.home'))" class="size-10 rounded-full border border-[var(--border-color)] flex items-center justify-center flex-shrink-0 active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-[var(--text-main)]">arrow_back</span>
                </button>
                <div>
                    <h1 class="text-2xl font-black italic uppercase tracking-tighter text-[var(--text-main)] leading-none">Find Trainer</h1>
                    <p class="text-[10px] font-black text-[var(--theme-color)] uppercase tracking-wider mt-1">Elite Coaching Network</p>
                </div>
            </header>

            <div class="space-y-6">
                <div v-for="trainer in trainers" :key="trainer.id" 
                    class="bg-[var(--card-bg)] rounded-[32px] border border-[var(--border-color)] overflow-hidden shadow-xl shadow-black/5"
                >
                    <div class="relative h-48 w-full group overflow-hidden">
                        <img :src="trainer.user.profile_photo_url || '/images/gorila/GorillaLogo.png'" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between">
                            <div>
                                <h3 class="text-xl font-black italic uppercase text-white leading-none">{{ trainer.user.name }}</h3>
                                <p class="text-[10px] font-bold text-[var(--theme-color)] uppercase tracking-widest mt-1">{{ trainer.specialty }}</p>
                            </div>
                            <div class="flex items-center gap-1 bg-white/10 backdrop-blur-md px-2 py-1 rounded-lg border border-white/20">
                                <span class="material-symbols-outlined text-amber-400 text-xs fill-icon">star</span>
                                <span class="text-[10px] font-black text-white">{{ trainer.reviews_avg_rating ? parseFloat(trainer.reviews_avg_rating).toFixed(1) : 'New' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-2 mb-6">
                            <div class="bg-[var(--page-bg)] p-3 rounded-2xl border border-[var(--border-color)] text-center">
                                <p class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider mb-1">Experience</p>
                                <p class="text-xs font-black italic text-[var(--text-main)]">{{ trainer.experience_years }}Y+</p>
                            </div>
                            <div class="bg-[var(--page-bg)] p-3 rounded-2xl border border-[var(--border-color)] text-center">
                                <p class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider mb-1">Gender</p>
                                <p class="text-xs font-black italic text-[var(--text-main)] uppercase">{{ trainer.gender || 'N/A' }}</p>
                            </div>
                            <div class="bg-[var(--page-bg)] p-3 rounded-2xl border border-[var(--border-color)] text-center">
                                <p class="text-[7px] font-black text-[var(--text-muted)] uppercase tracking-wider mb-1">Price/Hr</p>
                                <p class="text-xs font-black italic text-[var(--theme-color)]">{{ trainer.price_per_session }}฿</p>
                            </div>
                        </div>

                        <p class="text-[11px] font-bold text-[var(--text-muted)] leading-relaxed line-clamp-2 mb-6 italic">
                            "{{ trainer.bio }}"
                        </p>

                        <button 
                            @click="openBooking(trainer)"
                            class="w-full py-4 bg-[var(--theme-color)] text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-[var(--theme-color)]/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2"
                        >
                            <span class="material-symbols-outlined text-sm">event_available</span>
                            Book Session
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Modal -->
        <div v-if="isBookingModalOpen" class="fixed inset-0 z-[100] flex items-end justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="w-full max-w-md bg-[var(--card-bg)] rounded-[40px] p-8 pb-10 shadow-2xl border border-[var(--border-color)]">
                <div class="flex items-center gap-4 mb-8">
                    <img :src="selectedTrainer?.user.profile_photo_url" class="size-16 rounded-[24px] object-cover border-2 border-[var(--theme-color)]">
                    <div>
                        <h2 class="text-xl font-black italic uppercase text-[var(--text-main)]">{{ selectedTrainer?.user.name }}</h2>
                        <p class="text-[9px] font-black text-[var(--theme-color)] uppercase tracking-widest mt-0.5">Booking Request</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div v-if="selectedTrainer?.courses?.length > 0">
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Select Course</label>
                        <select 
                            v-model="bookingForm.course_name"
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)] transition-all"
                        >
                            <option v-for="course in selectedTrainer.courses" :key="course.id" :value="course.title">
                                {{ course.title }} ({{ course.hours }}h / {{ course.price }}฿)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Preferred Date & Time</label>
                        <input 
                            v-model="bookingForm.booking_date"
                            type="datetime-local" 
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        >
                    </div>

                    <div>
                        <label class="text-[9px] font-black uppercase tracking-widest text-[var(--text-muted)] ml-1">Notes for Trainer</label>
                        <textarea 
                            v-model="bookingForm.notes"
                            rows="3"
                            placeholder="Your goals, injuries, or preferences..."
                            class="w-full mt-2 bg-[var(--page-bg)] border border-[var(--border-color)] rounded-2xl px-5 py-4 text-xs font-black text-[var(--text-main)] focus:outline-none focus:border-[var(--theme-color)]"
                        ></textarea>
                    </div>

                    <div class="pt-4 flex flex-col gap-3">
                        <button 
                            @click="submitBooking"
                            class="w-full py-4 bg-[var(--theme-color)] text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-[var(--theme-color)]/20 active:scale-[0.98] transition-all"
                        >
                            Confirm Request
                        </button>
                        <button 
                            @click="isBookingModalOpen = false"
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
