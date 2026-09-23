<template>
  <div class="min-h-screen bg-[#F8FAFC] flex text-[#334155] font-sans antialiased">
    <!-- Desktop Sidebar -->
    <aside class="hidden lg:flex w-72 bg-white border-r border-[#E2E8F0] flex-col justify-between shadow-sm shrink-0">
      <div class="overflow-y-auto max-h-[calc(100vh-120px)]">
        <!-- Logo & Brand Header -->
        <div class="pt-8 pb-6 px-7 flex flex-col select-none">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#1E40AF] to-[#2563EB] flex items-center justify-center text-white font-black text-sm shadow-md shadow-blue-500/20">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div class="text-2xl font-extrabold tracking-tight text-slate-900">
              WEBGUARD<span class="text-[#2563EB]">.</span>
            </div>
          </div>
          <span class="text-[9px] font-bold tracking-wider text-[#94A3B8] uppercase mt-2">
            Platform Investigasi & Pelaporan Siber
          </span>
        </div>

        <!-- Navigation Menu Groups -->
        <nav class="px-4 py-2 space-y-2">
          <!-- Group 1: UTAMA -->
          <div class="space-y-1">
            <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest px-5 pt-1 pb-1">Utama</p>
            <router-link
              to="/dashboard"
              :class="$route.path === '/dashboard' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              Dashboard
            </router-link>
          </div>

          <!-- Group 2: INVESTIGASI -->
          <div class="space-y-1 pt-3 border-t border-slate-100/60 mt-3">
            <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest px-5 pb-1">Investigasi Website</p>
            <router-link
              to="/investigations"
              :class="$route.path === '/investigations' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              Semua Investigasi
            </router-link>
            <router-link
              to="/investigations/create"
              :class="$route.path === '/investigations/create' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
              </svg>
              Investigasi Baru
            </router-link>
          </div>

          <!-- Group 3: BUKTI & PELAPORAN -->
          <div class="space-y-1 pt-3 border-t border-slate-100/60 mt-3">
            <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest px-5 pb-1">Bukti & Pelaporan</p>
            <router-link
              to="/evidence"
              :class="$route.path.startsWith('/evidence') ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              Evidence Vault
            </router-link>
            <router-link
              to="/reports"
              :class="$route.path.startsWith('/reports') ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              Laporan Resmi
            </router-link>
          </div>

          <!-- Group 4: MANAJEMEN PERSONEL (Like Sisfopers KC) -->
          <div v-if="authStore.isAdmin" class="space-y-1 pt-3 border-t border-slate-100/60 mt-3">
            <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest px-5 pb-1">Manajemen Personel</p>
            <router-link
              to="/personnel"
              :class="$route.path === '/personnel' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
              Master Personel
            </router-link>
            <router-link
              to="/personnel/create"
              :class="$route.path === '/personnel/create' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
              </svg>
              Tambah Personel
            </router-link>
          </div>

          <!-- Group 5: AUDIT & KEAMANAN -->
          <div v-if="authStore.isAdmin" class="space-y-1 pt-3 border-t border-slate-100/60 mt-3">
            <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest px-5 pb-1">Audit & Keamanan</p>
            <router-link
              to="/security"
              :class="$route.path === '/security' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
              </svg>
              Security Posture
            </router-link>
            <router-link
              to="/security/audit-logs"
              :class="$route.path === '/security/audit-logs' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Audit Trail Sistem
            </router-link>
          </div>

          <!-- Group 6: PENGATURAN -->
          <div class="space-y-1 pt-3 border-t border-slate-100/60 mt-3">
            <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest px-5 pb-1">Pengaturan</p>
            <router-link
              to="/profile"
              :class="$route.path === '/profile' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              Profil & Keamanan 2FA
            </router-link>
            <router-link
              v-if="authStore.isSuperAdmin"
              to="/settings"
              :class="$route.path === '/settings' ? 'bg-[#2563EB]/5 text-[#2563EB] font-bold shadow-sm shadow-blue-500/[0.02]' : 'text-[#64748B] hover:text-slate-800 font-medium'"
              class="group flex items-center gap-4 px-5 py-3 rounded-2xl text-[14px] transition duration-150"
            >
              <svg class="w-5 h-5 opacity-80 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              Pengaturan Sistem
            </router-link>
          </div>
        </nav>
      </div>

      <!-- User Profile Card in Sidebar Footer (Like Sisfopers KC) -->
      <div class="p-5 border-t border-[#E2E8F0] bg-slate-50/50">
        <router-link to="/profile" class="flex items-center gap-3.5 mb-3 p-2 rounded-xl transition duration-150 hover:bg-slate-100/80 group cursor-pointer">
          <div class="w-10 h-10 rounded-xl bg-[#2563EB]/10 flex items-center justify-center font-bold text-xs text-[#2563EB] uppercase select-none group-hover:bg-[#2563EB] group-hover:text-white transition duration-150">
            {{ userInitials }}
          </div>
          <div class="text-xs truncate flex-1">
            <p class="font-bold text-slate-800 truncate group-hover:text-[#2563EB] transition duration-150">
              {{ authStore.user?.name || 'Investigator' }}
            </p>
            <p class="text-slate-400 truncate text-[11px] mt-0.5">
              {{ authStore.user?.rank || authStore.user?.email }}
            </p>
          </div>
        </router-link>

        <button
          @click="handleLogout"
          class="w-full py-2.5 px-3 text-center text-xs text-[#EF4444] hover:bg-red-50 rounded-xl font-bold border border-transparent hover:border-red-100/50 transition duration-150 cursor-pointer flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Keluar Sistem
        </button>
      </div>
    </aside>

    <!-- Mobile Sidebar Drawer -->
    <div v-if="isSidebarOpen" class="fixed inset-0 z-50 flex lg:hidden">
      <div @click="isSidebarOpen = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
      <aside class="relative w-72 max-w-xs bg-white h-full flex flex-col justify-between shadow-2xl z-50">
        <div class="overflow-y-auto">
          <div class="pt-6 pb-4 px-6 flex items-center justify-between border-b border-[#E2E8F0]">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-xs">WG</div>
              <div class="text-xl font-extrabold tracking-tight text-slate-900">
                WEBGUARD<span class="text-[#2563EB]">.</span>
              </div>
            </div>
            <button @click="isSidebarOpen = false" class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>
          <nav class="px-4 py-4 space-y-1">
            <router-link to="/dashboard" @click="isSidebarOpen = false" class="block px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Dashboard</router-link>
            <router-link to="/investigations" @click="isSidebarOpen = false" class="block px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Semua Investigasi</router-link>
            <router-link to="/investigations/create" @click="isSidebarOpen = false" class="block px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Investigasi Baru</router-link>
            <router-link to="/evidence" @click="isSidebarOpen = false" class="block px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Evidence Vault</router-link>
            <router-link to="/reports" @click="isSidebarOpen = false" class="block px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Laporan Resmi</router-link>
            <router-link v-if="authStore.isAdmin" to="/personnel" @click="isSidebarOpen = false" class="block px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Master Personel</router-link>
            <router-link v-if="authStore.isAdmin" to="/security" @click="isSidebarOpen = false" class="block px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Audit & Keamanan</router-link>
            <router-link to="/profile" @click="isSidebarOpen = false" class="block px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Profil Saya</router-link>
          </nav>
        </div>
        <div class="p-4 border-t border-[#E2E8F0]">
          <button @click="handleLogout" class="w-full py-2.5 text-center text-xs text-red-600 font-bold hover:bg-red-50 rounded-xl">Keluar Sistem</button>
        </div>
      </aside>
    </div>

    <!-- Main Content Shell -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Navigation Header -->
      <header class="h-16 bg-white border-b border-[#E2E8F0] flex items-center justify-between px-4 sm:px-8 shadow-sm shrink-0">
        <div class="flex items-center gap-3">
          <button @click="isSidebarOpen = true" class="lg:hidden p-2 hover:bg-slate-100 rounded-xl text-slate-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
          </button>
          <div class="text-xs font-bold tracking-wider text-slate-400 uppercase select-none flex items-center gap-2">
            <span>WEBGUARD</span>
            <span>&bull;</span>
            <span class="text-slate-800 font-extrabold">{{ currentTitle }}</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Ethical / Defensive Badge -->
          <div class="hidden md:flex items-center gap-2 px-3 py-1 bg-blue-50 border border-blue-200/60 rounded-full text-[11px] font-semibold text-blue-700">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Mode: Analisis Pasif & Defensif
          </div>

          <!-- User Pill -->
          <div class="flex items-center gap-2 pl-2">
            <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-xs text-slate-700">
              {{ userInitials }}
            </div>
            <div class="hidden sm:block text-left">
              <p class="text-xs font-bold text-slate-800 leading-tight">{{ authStore.user?.name }}</p>
              <p class="text-[10px] text-slate-400">{{ authStore.user?.rank || 'Personel' }}</p>
            </div>
          </div>
        </div>
      </header>

      <!-- Security Notice Banner -->
      <div class="bg-amber-50/70 border-b border-amber-200/50 px-4 sm:px-8 py-2 text-[11px] text-amber-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>
            <strong>Disclaimer:</strong> Penggunaan proxy/backend mengurangi paparan langsung alamat IP investigator kepada target, tetapi tidak menjamin anonimitas mutlak. Seluruh aktivitas tercatat dalam audit log internal.
          </span>
        </div>
      </div>

      <!-- Main Slot Content -->
      <main class="p-4 sm:p-8 flex-1 overflow-y-auto">
        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const isSidebarOpen = ref(false);

const userInitials = computed(() => {
  const name = authStore.user?.name || 'US';
  return name.substring(0, 2).toUpperCase();
});

const currentTitle = computed(() => {
  if (route.path.startsWith('/investigations/create')) return 'Investigasi Baru';
  if (route.path.startsWith('/investigations/')) return 'Detail Investigasi';
  if (route.path.startsWith('/investigations')) return 'Daftar Investigasi';
  if (route.path.startsWith('/personnel')) return 'Manajemen Personel';
  if (route.path.startsWith('/evidence')) return 'Evidence Vault';
  if (route.path.startsWith('/reports')) return 'Laporan Resmi';
  if (route.path.startsWith('/security')) return 'Audit & Keamanan';
  if (route.path.startsWith('/profile')) return 'Profil & Keamanan';
  if (route.path.startsWith('/settings')) return 'Pengaturan Sistem';
  return 'Dashboard Utama';
});

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>
