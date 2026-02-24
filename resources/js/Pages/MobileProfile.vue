<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { computed, onMounted, ref, watch, nextTick } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user || {});
const localWeight = ref(user.value.weight || 0);

// Keep local weight in sync with page props
watch(() => user.value.weight, (newVal) => {
    if (newVal !== undefined && newVal !== null) {
        localWeight.value = newVal;
    }
}, { immediate: true });

const isEditModalOpen = ref(false);
const errors = ref({});
const editForm = ref({
    name: '',
    height: 0,
    goal: ''
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

const updateWeight = (delta) => {
    const newWeight = Number(localWeight.value) + delta;
    if (newWeight < 20 || newWeight > 300) return;
    
    localWeight.value = newWeight;
    
    // Debounce the server update
    if (updateTimeout) clearTimeout(updateTimeout);
    
    updateTimeout = setTimeout(() => {
        router.patch(route('profile.update'), { 
            weight: newWeight 
        }, {
            preserveScroll: true,
            only: ['auth', 'flash'],
            onError: (err) => {
                localWeight.value = user.value.weight; // Revert on error
                alert('Failed to update weight: ' + (Object.values(err)[0] || 'Error'));
            }
        });
    }, 500);
};

let updateTimeout = null;

const goBack = () => {
    window.history.back();
};

const showToast = ref(false);
const toastMessage = ref('');

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
        <div v-if="showToast" class="fixed top-6 left-6 right-6 z-[200] animate-slide-down">
            <div class="bg-gray-900 text-white px-6 py-4 rounded-[24px] shadow-2xl flex items-center gap-3 border border-white/10 backdrop-blur-md">
                <span class="material-symbols-outlined text-[var(--theme-color)]">check_circle</span>
                <span class="text-xs font-black uppercase tracking-widest">{{ toastMessage }}</span>
            </div>
        </div>

        <!-- Header -->
        <header class="flex items-center justify-between p-6">
            <button @click="goBack" class="size-10 rounded-full bg-white  shadow-sm border border-gray-100  flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-900 font-bold">arrow_back</span>
            </button>
            <h1 class="text-xs font-black uppercase tracking-[0.3em] text-gray-400">Account Profile</h1>
            <Link :href="route('mobile.settings')" class="size-10 rounded-full bg-white  shadow-sm border border-gray-100  flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-900 font-bold">settings</span>
            </Link>
        </header>

        <!-- Profile Detail -->
        <div class="pb-8">
            <!-- User Intro -->
            <div class="px-6 py-4 flex flex-col items-center">
            <div class="relative mb-6">
                <div class="size-36 rounded-full border-4 border-[var(--theme-color)] p-1.5 shadow-2xl shadow-[var(--theme-color)]/20">
                    <div class="size-full rounded-full overflow-hidden bg-gray-100">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjuchVWDk_IRP1TbfrAkzG8C4dA-u_ZSX_bmaJ7iTLsz349d2YCZwMsRA1jv1NHNq-FTa1WuuTrctIi_d9WHJb2VI1NZrJ3p_BqZcczzKpP4SZPQj3B_XX6EDlPU5fbHMh9GznMXlc3-Koi2GaRlWBu-73j1pHp39bRwxLX-V_fo3bm3pe---4bpS8o-nSgL6mxkoqqAL8GatxFr8B0_Jqchl4PZb4VDP9b3_v-iSeR5UM_i9ZA9WxigaAtHyyyxzav-yqEqFoT0U" class="size-full object-cover">
                    </div>
                </div>
                <div class="absolute bottom-2 right-2 size-8 bg-[var(--theme-color)] rounded-full border-4 border-white  flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-sm fill-icon">verified</span>
                </div>
            </div>
                <div class="text-center mb-8">
                <h2 class="text-3xl font-black italic uppercase tracking-tighter text-gray-900 leading-none">{{ user.name || 'User' }}</h2>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--theme-color)] mt-3">Elite Athlete • Level 42</p>
            </div>

            <button @click="openEditModal" class="w-full py-5 bg-[var(--theme-color)] text-white font-black italic uppercase tracking-widest rounded-[24px] shadow-xl shadow-[var(--theme-color)]/30 active:scale-95 transition-all mb-10">
                Edit Profile
            </button>
            </div>

            <!-- Quick Stats -->
            <section class="px-6 py-4">
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white p-3 rounded-[24px] border border-gray-100 shadow-sm flex flex-col items-center text-center relative overflow-hidden group">
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Weight</span>
                        <div class="flex items-center gap-1.5">
                            <button @click="updateWeight(-1)" class="size-6 rounded-full bg-gray-50 flex items-center justify-center active:scale-90 active:bg-gray-100 transition-all">
                                <span class="material-symbols-outlined text-[14px] font-black">remove</span>
                            </button>
                            <span class="text-base font-black text-gray-900 leading-none">{{ Math.round(localWeight) }}<span class="text-[9px] ml-0.5 text-gray-400 uppercase tracking-tighter">kg</span></span>
                            <button @click="updateWeight(1)" class="size-6 rounded-full bg-gray-50 flex items-center justify-center active:scale-90 active:bg-gray-100 transition-all">
                                <span class="material-symbols-outlined text-[14px] font-black">add</span>
                            </button>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-[24px] border border-gray-100 shadow-sm flex flex-col items-center text-center">
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Height</span>
                        <span class="text-lg font-black text-gray-900 leading-none">{{ user.height || 0 }}<span class="text-[10px] ml-0.5 text-gray-400 uppercase tracking-tighter">cm</span></span>
                    </div>
                    <div class="bg-white p-5 rounded-[24px] border border-gray-100 shadow-sm flex flex-col items-center text-center">
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Goal</span>
                        <span class="text-[9px] font-black text-gray-900 uppercase leading-tight mt-1">{{ user.goal || 'No Goal' }}</span>
                    </div>
                </div>
            </section>

            <!-- Metrics List -->
            <div class="px-6 w-full space-y-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Performance</h3>
                    <span class="text-[10px] font-bold text-[var(--theme-color)] uppercase tracking-widest">History</span>
                </div>
                <div class="space-y-3">
                    <div class="bg-white  p-5 rounded-[24px] border border-gray-100  flex items-center justify-between shadow-sm transition-all hover:scale-[1.02]">
                        <div class="flex items-center gap-4">
                            <div class="size-12 rounded-2xl bg-[var(--theme-color)]/10 flex items-center justify-center text-[var(--theme-color)]">
                                <span class="material-symbols-outlined fill-icon">fitness_center</span>
                            </div>
                            <div class="ml-1 flex-1 text-left">
                                <h4 class="font-black uppercase text-sm text-gray-900 leading-none mb-1">Workouts</h4>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">128 Completed</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-[var(--theme-color)] leading-none">82%</p>
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Progress</p>
                        </div>
                    </div>
                    
                    <div class="bg-white  rounded-[28px] border border-gray-100  overflow-hidden shadow-sm mt-6">
                        <a href="#" class="flex items-center justify-between p-5 hover:bg-gray-50  border-b border-gray-100  transition-colors">
                            <div class="flex items-center gap-4">
                                <span class="material-symbols-outlined text-gray-600">workspace_premium</span>
                                <span class="font-black uppercase text-[10px] tracking-widest text-gray-600 ">Personal Bests</span>
                            </div>
                            <span class="material-symbols-outlined text-gray-500 font-bold text-sm">arrow_forward_ios</span>
                        </a>
                        <a href="#" class="flex items-center justify-between p-5 hover:bg-gray-50  transition-colors">
                            <div class="flex items-center gap-4">
                                <span class="material-symbols-outlined text-gray-400">logout</span>
                                <span class="font-black uppercase text-[10px] tracking-widest text-red-500">Sign Out</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-[100] flex items-end justify-center">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isEditModalOpen = false"></div>
            <div class="relative w-full max-w-lg bg-white rounded-t-[40px] p-8 pb-12 animate-slide-up shadow-2xl flex flex-col max-h-[90vh]">
                <div class="w-12 h-1 bg-gray-200 rounded-full mx-auto mb-8 shrink-0"></div>
                
                <h3 class="text-xl font-black italic uppercase tracking-tighter text-gray-900 mb-6 shrink-0">Edit Profile</h3>
                
                <div class="space-y-6 overflow-y-auto pr-2 pb-10 flex-1 custom-scrollbar">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold uppercase text-[10px] tracking-widest text-gray-400">Full Name</span></label>
                        <input v-model="editForm.name" type="text" class="input input-bordered w-full rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-[var(--theme-color)] transition-all" />
                        <span v-if="errors.name" class="text-[10px] text-red-500 mt-1 ml-2 font-bold uppercase tracking-wider">{{ errors.name }}</span>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold uppercase text-[10px] tracking-widest text-gray-400">Height (cm)</span></label>
                        <input v-model="editForm.height" type="number" class="input input-bordered w-full rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-[var(--theme-color)] transition-all" />
                        <span v-if="errors.height" class="text-[10px] text-red-500 mt-1 ml-2 font-bold uppercase tracking-wider">{{ errors.height }}</span>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold uppercase text-[10px] tracking-widest text-gray-400">Goal</span></label>
                        <select v-model="editForm.goal" class="select select-bordered w-full rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-[var(--theme-color)] transition-all px-4">
                            <option value="Muscle Gain">Muscle Gain</option>
                            <option value="Lose Weight">Lose Weight</option>
                            <option value="Keep Fit">Keep Fit</option>
                            <option value="Endurance">Endurance</option>
                        </select>
                        <span v-if="errors.goal" class="text-[10px] text-red-500 mt-1 ml-2 font-bold uppercase tracking-wider">{{ errors.goal }}</span>
                    </div>
                </div>

                <div class="mt-8 flex gap-3 pb-safe shrink-0">
                    <button @click="isEditModalOpen = false" class="flex-1 py-4 bg-gray-100 text-gray-600 font-black italic uppercase tracking-widest rounded-2xl active:scale-95 transition-all">Cancel</button>
                    <button @click="saveProfile" class="flex-1 py-4 bg-[var(--theme-color)] text-white font-black italic uppercase tracking-widest rounded-2xl shadow-lg shadow-[var(--theme-color)]/30 active:scale-95 transition-all">Save Changes</button>
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
    background: #eee;
    border-radius: 10px;
}
.custom-scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.custom-scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
