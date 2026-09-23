<template>
  <AuthenticatedLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pengaturan Sistem & Integrasi API</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
              Super Admin Konfigurasi
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Konfigurasi parameter operasional platform, gateway notifikasi WhatsApp, dan provider intelijen eksternal.
          </p>
        </div>

        <div>
          <button
            @click="saveSettings"
            :disabled="saving"
            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm disabled:opacity-50 transition flex items-center gap-1.5"
          >
            <div v-if="saving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <span>{{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
          </button>
        </div>
      </div>

      <!-- Settings Tabs -->
      <div class="flex border-b border-slate-200 space-x-6">
        <button
          @click="activeTab = 'general'"
          :class="activeTab === 'general' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-slate-500 font-medium'"
          class="py-3 border-b-2 text-sm transition"
        >
          Konfigurasi Umum
        </button>
        <button
          @click="activeTab = 'providers'"
          :class="activeTab === 'providers' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-slate-500 font-medium'"
          class="py-3 border-b-2 text-sm transition"
        >
          Provider & API Intelijen
        </button>
      </div>

      <!-- TAB 1: General Settings -->
      <div v-if="activeTab === 'general'" class="space-y-6">
        <!-- Brand & App Identity -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7 space-y-4">
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Identitas Platform</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Nama Aplikasi</label>
              <input
                v-model="settings.app_name"
                type="text"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Tagline Kedinasan</label>
              <input
                v-model="settings.app_tagline"
                type="text"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm"
              />
            </div>
          </div>
        </div>

        <!-- Security & Session Policies -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7 space-y-4">
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Kebijakan Keamanan & Sesi</h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Session Lifetime (Menit)</label>
              <input
                v-model="settings.session_lifetime"
                type="number"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Max Login Attempts (Lockout)</label>
              <input
                v-model="settings.max_login_attempts"
                type="number"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Wajibkan 2FA untuk Semua</label>
              <select
                v-model="settings.enforce_2fa_all"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm"
              >
                <option value="false">Opsional / Direkomendasikan</option>
                <option value="true">Wajib (Mandatory)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Notification Integration Settings -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7 space-y-4">
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Gateway Notifikasi WhatsApp & Email</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">WhatsApp API Endpoint / Webhook</label>
              <input
                v-model="settings.whatsapp_endpoint"
                type="text"
                placeholder="https://api.fonnte.com/send atau Wappi API"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-mono"
              />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">WhatsApp API Token / Key</label>
              <input
                v-model="settings.whatsapp_token"
                type="password"
                placeholder="••••••••••••••••"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-mono"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: Providers List -->
      <div v-if="activeTab === 'providers'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Provider Layanan Eksternal Terdaftar</h3>
              <p class="text-xs text-slate-500">Kredensial API eksternal untuk passive scanning (DNS, IP intelligence, screenshot, reputation).</p>
            </div>
          </div>

          <div class="overflow-x-auto mt-4">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/70 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                  <th class="py-3 px-4">Nama Provider</th>
                  <th class="py-3 px-3">Tipe Layanan</th>
                  <th class="py-3 px-4">API Endpoint</th>
                  <th class="py-3 px-3">Status</th>
                  <th class="py-3 px-3">Default</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="p in providers" :key="p.id" class="hover:bg-slate-50/50">
                  <td class="py-3 px-4 font-bold text-slate-800">{{ p.name }}</td>
                  <td class="py-3 px-3 font-mono text-slate-600">{{ p.service_type }}</td>
                  <td class="py-3 px-4 font-mono text-slate-500 truncate max-w-xs">{{ p.api_endpoint || '(Built-in System Provider)' }}</td>
                  <td class="py-3 px-3">
                    <span :class="p.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'" class="px-2 py-0.5 rounded-full font-bold text-[10px]">
                      {{ p.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td class="py-3 px-3">
                    <span v-if="p.is_default" class="text-blue-600 font-bold">★ Utama</span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
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
import Swal from 'sweetalert2';

const activeTab = ref('general');
const saving = ref(false);

const settings = ref({
  app_name: 'WebGuard Investigasi',
  app_tagline: 'Platform Investigasi & Pelaporan Siber',
  session_lifetime: 120,
  max_login_attempts: 5,
  enforce_2fa_all: 'false',
  whatsapp_endpoint: '',
  whatsapp_token: '',
});

const providers = ref<any[]>([]);

const loadSettings = async () => {
  try {
    const res = await api.get('/settings');
    if (res.data.settings) {
      settings.value = { ...settings.value, ...res.data.settings };
    }
  } catch (e) {
    console.error('Failed to load settings', e);
  }
};

const loadProviders = async () => {
  try {
    const res = await api.get('/providers');
    providers.value = res.data.providers || [];
  } catch (e) {
    console.error('Failed to load providers', e);
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    await api.post('/settings', { settings: settings.value });
    Swal.fire('Berhasil', 'Pengaturan sistem berhasil disimpan.', 'success');
  } catch (e: any) {
    Swal.fire('Gagal', e.response?.data?.message || 'Gagal menyimpan pengaturan.', 'error');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSettings();
  loadProviders();
});
</script>
