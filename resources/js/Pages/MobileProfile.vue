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

const isInitialSync = ref(true);
const isWeightEditing = ref(false);
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

const integerRef = ref(null);
const decimalRef = ref(null);

const integers = Array.from({ length: 281 }, (_, i) => 20 + i);
const decimals = Array.from({ length: 10 }, (_, i) => i);

const onIntegerScroll = (e) => {
    if (isInitialSync.value) return;
    const scrollTop = e.target.scrollTop;
    const index = Math.round(scrollTop / 34); // Updated to 34px
    const intPart = integers[index] || 20;
    const decPart = Math.round((localWeight.value % 1) * 10) / 10;
    localWeight.value = parseFloat((intPart + decPart).toFixed(1));
};

const onDecimalScroll = (e) => {
    if (isInitialSync.value) return;
    const scrollTop = e.target.scrollTop;
    const index = Math.round(scrollTop / 34); // Updated to 34px
    const decPart = (decimals[index] || 0) / 10;
    const intPart = Math.floor(localWeight.value);
    localWeight.value = parseFloat((intPart + decPart).toFixed(1));
};

const hasWeightChanged = computed(() => {
    return localWeight.value !== user.value.weight;
});

const cancelWeightEdit = () => {
    localWeight.value = user.value.weight;
    syncPickersToWeight(localWeight.value);
    isWeightEditing.value = false;
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
            isWeightEditing.value = false;
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
    
    // 34px per item height
    integerRef.value.scrollTop = (intPart - 20) * 34;
    decimalRef.value.scrollTop = decPart * 34;
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
        <div v-if="showToast" class="fixed top-6 left-1/2 -translate-x-1/2 z-[200] animate-slide-down pointer-events-none">
            <div class="bg-[var(--text-main)] text-[var(--card-bg)] px-5 py-3 rounded-full shadow-2xl flex items-center gap-2 border border-[var(--border-color)] backdrop-blur-md transition-colors">
                <span class="material-symbols-outlined text-[var(--theme-color)] text-lg">check_circle</span>
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

                <button @click="openEditModal" class="w-full py-5 bg-[var(--text-main)] text-[var(--card-bg)] font-black italic uppercase tracking-widest rounded-[24px] shadow-xl shadow-black/10 active:scale-95 transition-all mb-10">
                    Edit Profile
                </button>
            </div>

            <!-- Quick Stats -->
            <section class="px-6 py-4">
                <div class="grid grid-cols-3 gap-3">
                    <!-- Weight Picker (Compact & First) -->
                    <div class="bg-[var(--card-bg)] p-4 rounded-[28px] border border-[var(--border-color)] shadow-sm flex flex-col items-center justify-start text-center relative overflow-hidden h-[130px] pt-7 transition-colors">
                        <span class="absolute top-2.5 text-[8px] font-black uppercase tracking-widest text-[var(--text-muted)]">Weight</span>
                        
                        <div class="relative w-full h-[60px] flex items-center justify-center gap-1 transition-opacity duration-300" :class="{ 'opacity-40 grayscale pointer-events-none': !isWeightEditing }">
                            <!-- Center Focus Bar -->
                            <div class="absolute inset-x-2 h-[34px] bg-[var(--page-bg)] rounded-xl -z-0 border border-black/5"></div>
                            
                            <!-- Integer Picker -->
                            <div class="relative h-full w-10 overflow-hidden">
                                <div 
                                    ref="integerRef"
                                    @scroll="onIntegerScroll"
                                    class="h-full overflow-y-auto no-scrollbar snap-y snap-mandatory py-[13px]"
                                >
                                    <div v-for="int in integers" :key="int" 
                                        class="h-[34px] flex items-center justify-center snap-center transition-all duration-300"
                                        :class="Math.floor(localWeight) === int ? 'text-[12px] font-black text-[var(--text-main)]' : 'text-[9px] font-bold text-[var(--text-muted)]'"
                                    >
                                        {{ int }}
                                    </div>
                                </div>
                            </div>

                            <!-- Separator -->
                            <div class="text-xl font-black text-[var(--theme-color)] mb-1">.</div>

                            <!-- Decimal Picker -->
                            <div class="relative h-full w-6 overflow-hidden">
                                <div 
                                    ref="decimalRef"
                                    @scroll="onDecimalScroll"
                                    class="h-full overflow-y-auto no-scrollbar snap-y snap-mandatory py-[13px]"
                                >
                                    <div v-for="dec in decimals" :key="dec" 
                                        class="h-[34px] flex items-center justify-center snap-center transition-all duration-300"
                                        :class="Math.round((localWeight % 1) * 10) === dec ? 'text-[12px] font-black text-[var(--text-main)]' : 'text-[9px] font-bold text-[var(--text-muted)]'"
                                    >
                                        {{ dec }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="absolute bottom-2.5 w-full px-2">
                            <template v-if="!isWeightEditing">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-baseline gap-0.5 transition-colors">
                                        <span class="text-lg font-black text-[var(--theme-color)] leading-none italic transition-colors">{{ Number(localWeight).toFixed(1) }}</span>
                                        <span class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest transition-colors">KG</span>
                                    </div>
                                    <button 
                                        @click="isWeightEditing = true"
                                        class="bg-[var(--theme-color)]/10 text-[var(--theme-color)] text-[6px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full active:scale-95 transition-all border border-[var(--theme-color)]/20 shadow-sm"
                                    >
                                        Edit
                                    </button>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex items-center justify-center gap-1.5 w-full">
                                    <button 
                                        @click="cancelWeightEdit"
                                        class="flex-1 bg-[var(--page-bg)] text-[var(--text-muted)] text-[6px] font-black uppercase tracking-widest py-1.5 rounded-full active:scale-95 transition-all text-center border border-[var(--border-color)]"
                                    >
                                        Cancel
                                    </button>
                                    <button 
                                        @click="saveWeightToServer(localWeight.value)"
                                        :disabled="!hasWeightChanged"
                                        class="flex-1 bg-[var(--theme-color)] text-white text-[6px] font-black uppercase tracking-widest py-1.5 rounded-full shadow-lg shadow-[var(--theme-color)]/20 active:scale-95 transition-all disabled:opacity-50 disabled:shadow-none text-center"
                                    >
                                        Save
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Height Section -->
                    <div class="bg-[var(--card-bg)] p-4 rounded-[28px] border border-[var(--border-color)] shadow-sm flex flex-col items-center justify-center text-center relative h-[130px] transition-colors">
                        <span class="absolute top-2.5 text-[8px] font-black uppercase tracking-widest text-[var(--text-muted)]">Height</span>
                        <span class="text-lg font-black text-[var(--text-main)] leading-none transition-colors">
                            {{ user.height || 0 }}<span class="text-[10px] ml-0.5 text-[var(--text-muted)] uppercase tracking-tighter transition-colors">cm</span>
                        </span>
                    </div>

                    <!-- Goal Section -->
                    <div class="bg-[var(--card-bg)] p-4 rounded-[28px] border border-[var(--border-color)] shadow-sm flex flex-col items-center justify-center text-center relative h-[130px] transition-colors">
                        <span class="absolute top-2.5 text-[8px] font-black uppercase tracking-widest text-[var(--text-muted)]">Goal</span>
                        <span class="text-[12px] font-black text-[var(--text-main)] uppercase leading-tight mt-1 transition-colors">{{ user.goal || 'No Goal' }}</span>
                    </div>
                </div>
            </section>

            <!-- Metrics List -->
            <div class="px-6 w-full space-y-4 pt-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-muted)] transition-colors">Performance</h3>
                    <span class="text-[10px] font-bold text-[var(--theme-color)] uppercase tracking-widest">History</span>
                </div>
                <div class="space-y-3">
                    <div class="bg-[var(--card-bg)] p-5 rounded-[24px] border border-[var(--border-color)] flex items-center justify-between shadow-sm transition-all hover:scale-[1.02]">
                        <div class="flex items-center gap-4">
                            <div class="size-12 rounded-2xl bg-[var(--theme-color)]/10 flex items-center justify-center text-[var(--theme-color)]">
                                <span class="material-symbols-outlined fill-icon">fitness_center</span>
                            </div>
                            <div class="ml-1 flex-1 text-left">
                                <h4 class="font-black uppercase text-sm text-[var(--text-main)] leading-none mb-1 transition-colors">Workouts</h4>
                                <p class="text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest transition-colors">128 Completed</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-[var(--theme-color)] leading-none">82%</p>
                            <p class="text-[8px] font-black text-[var(--text-muted)] uppercase tracking-widest transition-colors">Progress</p>
                        </div>
                    </div>
                    
                    <div class="bg-[var(--card-bg)] rounded-[28px] border border-[var(--border-color)] overflow-hidden shadow-sm mt-6 transition-colors">
                        <a href="#" class="flex items-center justify-between p-5 hover:bg-[var(--page-bg)] border-b border-[var(--border-color)] transition-colors">
                            <div class="flex items-center gap-4">
                                <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">workspace_premium</span>
                                <span class="font-black uppercase text-[10px] tracking-widest text-[var(--text-muted)] transition-colors">Personal Bests</span>
                            </div>
                            <span class="material-symbols-outlined text-[var(--text-muted)] font-bold text-sm transition-colors">arrow_forward_ios</span>
                        </a>
                        <a href="#" class="flex items-center justify-between p-5 hover:bg-[var(--page-bg)] transition-colors">
                            <div class="flex items-center gap-4">
                                <span class="material-symbols-outlined text-[var(--text-muted)] transition-colors">logout</span>
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
            <div class="relative w-full max-w-lg bg-[var(--card-bg)] rounded-t-[40px] p-8 pb-12 animate-slide-up shadow-2xl flex flex-col max-h-[90vh] border-t border-[var(--border-color)] transition-colors text-[var(--text-main)]">
                <div class="w-12 h-1 bg-[var(--border-color)] rounded-full mx-auto mb-8 shrink-0 transition-colors"></div>
                
                <h3 class="text-xl font-black italic uppercase tracking-tighter text-[var(--text-main)] mb-6 shrink-0 transition-colors">Edit Profile</h3>
                
                <div class="space-y-6 overflow-y-auto pr-2 pb-10 flex-1 custom-scrollbar">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold uppercase text-[10px] tracking-widest text-[var(--text-muted)]">Full Name</span></label>
                        <input v-model="editForm.name" type="text" class="input input-bordered w-full rounded-2xl bg-[var(--page-bg)] border-none text-[var(--text-main)] focus:ring-2 focus:ring-[var(--theme-color)] transition-all" />
                        <span v-if="errors.name" class="text-[10px] text-red-500 mt-1 ml-2 font-bold uppercase tracking-wider">{{ errors.name }}</span>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold uppercase text-[10px] tracking-widest text-[var(--text-muted)]">Height (cm)</span></label>
                        <input v-model="editForm.height" type="number" class="input input-bordered w-full rounded-2xl bg-[var(--page-bg)] border-none text-[var(--text-main)] focus:ring-2 focus:ring-[var(--theme-color)] transition-all" />
                        <span v-if="errors.height" class="text-[10px] text-red-500 mt-1 ml-2 font-bold uppercase tracking-wider">{{ errors.height }}</span>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold uppercase text-[10px] tracking-widest text-[var(--text-muted)]">Goal</span></label>
                        <select v-model="editForm.goal" class="select select-bordered w-full rounded-2xl bg-[var(--page-bg)] border-none text-[var(--text-main)] focus:ring-2 focus:ring-[var(--theme-color)] transition-all px-4">
                            <option value="Muscle Gain">Muscle Gain</option>
                            <option value="Lose Weight">Lose Weight</option>
                            <option value="Keep Fit">Keep Fit</option>
                            <option value="Endurance">Endurance</option>
                        </select>
                        <span v-if="errors.goal" class="text-[10px] text-red-500 mt-1 ml-2 font-bold uppercase tracking-wider">{{ errors.goal }}</span>
                    </div>
                </div>

                <div class="mt-8 flex gap-3 pb-safe shrink-0">
                    <button @click="isEditModalOpen = false" class="flex-1 py-4 bg-[var(--page-bg)] text-[var(--text-muted)] font-black italic uppercase tracking-widest rounded-2xl active:scale-95 transition-all">Cancel</button>
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
