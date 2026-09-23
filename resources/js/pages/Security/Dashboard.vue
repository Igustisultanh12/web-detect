<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Security Posture & Monitoring</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Sistem Terproteksi Aktif
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Pantauan pertahanan perimeter, audit percobaan otentikasi, sanitasi SSRF, dan integritas sesi pengguna.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <router-link
            to="/security/audit-logs"
            class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-sm transition flex items-center gap-1.5"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Buka Audit Trail Lengkap
          </router-link>
        </div>
      </div>

      <!-- Security Stat Cards (Sisfopers KC Style) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 2FA Adoption -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Adopsi 2FA</span>
            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900">{{ stats.two_factor_enabled || 0 }} Personel</div>
            <div class="text-xs text-slate-500 mt-0.5">
              dari {{ stats.total_users || 0 }} total akun personel terdaftar
            </div>
          </div>
        </div>

        <!-- Failed Logins (24h) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gagal Login (24j)</span>
            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900">{{ stats.failed_logins_24h || 0 }}</div>
            <div class="text-xs text-slate-500 mt-0.5">Percobaan login ditolak rate limiter / bad credential</div>
          </div>
        </div>

        <!-- Security Events -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Insiden Keamanan</span>
            <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v4m0 4h.01" />
              </svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900">{{ stats.total_security_events || 0 }}</div>
            <div class="text-xs text-rose-600 font-semibold mt-0.5">
              {{ stats.critical_security_events || 0 }} Kategori Critical
            </div>
          </div>
        </div>

        <!-- Active Sessions -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status Akun Aktif</span>
            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900">{{ stats.active_users || 0 }}</div>
            <div class="text-xs text-slate-500 mt-0.5">
              {{ stats.suspended_users || 0 }} akun dalam status penangguhan
            </div>
          </div>
        </div>
      </div>

      <!-- Two Column Layout: Recent Login Activity & Security Events -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Card 1: Login Activity Stream -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900">Aktivitas Percobaan Login</h3>
              <p class="text-xs text-slate-500">Log otentikasi pengguna dan deteksi anomali IP</p>
            </div>
            <button @click="loadLoginActivity" class="text-xs font-bold text-blue-600 hover:text-blue-700">
              Segarkan
            </button>
          </div>

          <div class="overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/70 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                  <th class="py-3 px-4">Pengguna / Email</th>
                  <th class="py-3 px-3">Status</th>
                  <th class="py-3 px-3">IP Address</th>
                  <th class="py-3 px-4">Waktu</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="log in loginLogs" :key="log.id" class="hover:bg-slate-50/50">
                  <td class="py-2.5 px-4">
                    <div class="font-semibold text-slate-800">{{ log.email }}</div>
                  </td>
                  <td class="py-2.5 px-3">
                    <span
                      :class="log.status === 'SUCCESS' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                      class="px-2 py-0.5 rounded-full font-bold text-[10px] border"
                    >
                      {{ log.status }}
                    </span>
                  </td>
                  <td class="py-2.5 px-3 font-mono text-slate-600">{{ log.ip_address || '127.0.0.1' }}</td>
                  <td class="py-2.5 px-4 text-slate-400 font-mono">{{ formatDate(log.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Card 2: Security Events & Guard Warnings -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900">Event & Alarm Keamanan Sistem</h3>
              <p class="text-xs text-slate-500">Pelanggaran SSRF, brute force, dan manipulasi data</p>
            </div>
            <button @click="loadDashboardStats" class="text-xs font-bold text-blue-600 hover:text-blue-700">
              Segarkan
            </button>
          </div>

          <div class="p-5 space-y-3 flex-1 overflow-y-auto max-h-[380px]">
            <div v-if="!stats.recent_events || stats.recent_events.length === 0" class="py-8 text-center text-slate-400 text-xs">
              Tidak ada event keamanan mencurigakan dalam 24 jam terakhir. Sistem aman.
            </div>

            <div
              v-for="ev in stats.recent_events"
              :key="ev.id"
              class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/40 space-y-1.5"
            >
              <div class="flex items-center justify-between">
                <span
                  :class="getSeverityBadge(ev.severity)"
                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                >
                  {{ ev.severity }}
                </span>
                <span class="text-[11px] font-mono text-slate-400">{{ formatDate(ev.created_at) }}</span>
              </div>
              <div class="text-xs font-bold text-slate-800">{{ ev.title || ev.event_type }}</div>
              <p class="text-[11px] text-slate-500">{{ ev.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import api from '../../services/api';

const stats = ref<any>({});
const loginLogs = ref<any[]>([]);

const loadDashboardStats = async () => {
  try {
    const res = await api.get('/security/dashboard');
    stats.value = res.data.stats;
  } catch (e) {
    console.error('Failed to load security dashboard', e);
  }
};

const loadLoginActivity = async () => {
  try {
    const res = await api.get('/security/login-activity', { params: { per_page: 10 } });
    loginLogs.value = res.data.data;
  } catch (e) {
    console.error('Failed to load login activity', e);
  }
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    day: 'numeric',
    month: 'short',
  });
};

const getSeverityBadge = (sev: string) => {
  switch (sev?.toUpperCase()) {
    case 'CRITICAL':
      return 'bg-rose-100 text-rose-800 font-black';
    case 'HIGH':
      return 'bg-amber-100 text-amber-800';
    case 'MEDIUM':
      return 'bg-blue-100 text-blue-800';
    default:
      return 'bg-slate-100 text-slate-700';
  }
};

onMounted(() => {
  loadDashboardStats();
  loadLoginActivity();
});
</script>
