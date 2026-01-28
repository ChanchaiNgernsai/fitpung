<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const activeTab = ref('profile'); // profile, password, danger
</script>

<template>
    <Head title="Account Settings" />

    <AppLayout>
        <!-- Header Section -->
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-base-content">
                        Account Settings
                    </h2>
                    <p class="text-sm text-base-content/60 mt-1">Manage your profile, security, and preferences.</p>
                </div>
                <Link :href="route('dashboard')" class="btn btn-primary btn-sm shadow-lg shadow-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12 bg-base-200/50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    
                    <!-- Sidebar Navigation -->
                    <nav class="w-full lg:w-72 shrink-0 space-y-2 sticky top-24">
                        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-content/5 overflow-hidden p-3">
                            <div class="px-4 py-2 mb-2">
                                <h3 class="text-xs font-bold text-base-content/40 uppercase tracking-widest">General</h3>
                            </div>
                            
                            <button 
                                @click="activeTab = 'profile'"
                                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 text-sm font-medium relative group"
                                :class="activeTab === 'profile' ? 'bg-primary text-primary-content shadow-md shadow-primary/20' : 'text-base-content/70 hover:bg-base-200'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="activeTab === 'profile' ? 'opacity-100' : 'opacity-70 group-hover:opacity-100'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                Profile Information
                                <svg v-if="activeTab === 'profile'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>

                            <button 
                                @click="activeTab = 'password'"
                                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 text-sm font-medium relative group"
                                :class="activeTab === 'password' ? 'bg-primary text-primary-content shadow-md shadow-primary/20' : 'text-base-content/70 hover:bg-base-200'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="activeTab === 'password' ? 'opacity-100' : 'opacity-70 group-hover:opacity-100'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                Security & Password
                                <svg v-if="activeTab === 'password'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>

                        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-content/5 overflow-hidden p-3">
                             <div class="px-4 py-2 mb-2">
                                <h3 class="text-xs font-bold text-error/60 uppercase tracking-widest">Danger Zone</h3>
                            </div>
                            <button 
                                @click="activeTab = 'danger'"
                                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 text-sm font-medium text-error hover:bg-error/10"
                                :class="activeTab === 'danger' ? 'bg-error/10 font-bold ring-1 ring-error/20' : ''"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Delete Account
                            </button>
                        </div>
                    </nav>

                    <!-- Main Content Area -->
                    <div class="flex-1 min-w-0">
                        
                        <!-- Profile Tab -->
                        <div v-if="activeTab === 'profile'" class="animate-fade-in space-y-6">
                            <div class="bg-base-100 rounded-3xl shadow-sm border border-base-content/5 p-8 lg:p-10 relative overflow-hidden">
                                <!-- Decorative bg element -->
                                <div class="absolute top-0 right-0 p-10 opacity-5 pointer-events-none">
                                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>

                                <div class="relative z-10">
                                    <h3 class="text-2xl font-bold tracking-tight mb-2">Profile Information</h3>
                                    <p class="text-base-content/60 mb-8 max-w-2xl">Update your account's profile details and email address. Keep your information current to ensure seamless communication.</p>
                                    
                                    <div class="divider opacity-50 my-8"></div>
                                    
                                    <UpdateProfileInformationForm
                                        :must-verify-email="mustVerifyEmail"
                                        :status="status"
                                        class="max-w-2xl"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Password Tab -->
                        <div v-if="activeTab === 'password'" class="animate-fade-in space-y-6">
                            <div class="bg-base-100 rounded-3xl shadow-sm border border-base-content/5 p-8 lg:p-10 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-10 opacity-5 pointer-events-none">
                                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                                </div>
                                <div class="relative z-10">
                                    <h3 class="text-2xl font-bold tracking-tight mb-2">Security & Password</h3>
                                    <p class="text-base-content/60 mb-8 max-w-2xl">Ensure your account is using a long, random password to stay secure.</p>
                                    
                                    <div class="divider opacity-50 my-8"></div>

                                    <UpdatePasswordForm class="max-w-xl" />
                                </div>
                            </div>
                        </div>

                        <!-- Danger Zone -->
                        <div v-if="activeTab === 'danger'" class="animate-fade-in space-y-6">
                            <div class="bg-base-100 rounded-3xl shadow-sm border border-error/10 p-8 lg:p-10 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-10 text-error opacity-5 pointer-events-none">
                                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                                </div>
                                <div class="relative z-10">
                                    <h3 class="text-2xl font-bold tracking-tight text-error mb-2">Delete Account</h3>
                                    <div class="alert alert-error bg-error/10 text-error border-0 mb-8 mt-4 rounded-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <span>Warning: This action is permanent and cannot be undone. Once your account is deleted, all of its resources and data will be permanently deleted.</span>
                                    </div>
                                    
                                    <DeleteUserForm class="max-w-xl" />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
