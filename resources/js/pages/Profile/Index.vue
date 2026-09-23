<template>
  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Page Header -->
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Profil & Keamanan Akun</h1>
        <p class="text-sm text-slate-500 mt-1">
          Pengaturan informasi personel, kata sandi dinas, dan otentikasi dua faktor (TOTP Google Authenticator).
        </p>
      </div>

      <!-- Identity Banner (Sisfopers KC Style) -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7 relative overflow-hidden">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
          <div class="w-18 h-18 rounded-2xl bg-gradient-to-tr from-blue-700 to-indigo-600 text-white font-extrabold text-2xl flex items-center justify-center shrink-0 shadow-md shadow-blue-500/10">
            {{ getInitials(authStore.user?.name || '') }}
          </div>
          <div class="space-y-1">
            <div class="flex items-center gap-3">
              <h2 class="text-xl font-bold text-slate-900">{{ authStore.user?.name }}</h2>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                {{ authStore.user?.status || 'ACTIVE' }}
              </span>
            </div>
            <p class="text-xs text-slate-500 flex items-center gap-2">
              <span>NRP: {{ authStore.user?.nrp || '-' }}</span>
              <span>•</span>
              <span class="font-medium text-slate-700">{{ authStore.user?.rank || 'Non-Pangkat' }}</span>
              <span>•</span>
              <span>{{ authStore.user?.unit || 'Mabes Siber' }}</span>
            </p>
            <div class="text-xs text-blue-600 font-mono">{{ authStore.user?.email }}</div>
          </div>
        </div>
      </div>

      <!-- Two-Factor Authentication (2FA) Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900">Autentikasi Dua Faktor (2FA / TOTP)</h3>
              <p class="text-xs text-slate-500">Wajib untuk seluruh personel intelijen guna mencegah pengambilalihan akun.</p>
            </div>
          </div>

          <span
            :class="authStore.user?.two_factor_enabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
            class="px-3 py-1 rounded-full text-xs font-bold border"
          >
            {{ authStore.user?.two_factor_enabled ? 'Aktif & Terlindungi' : 'Belum Aktif' }}
          </span>
        </div>

        <!-- If 2FA already enabled -->
        <div v-if="authStore.user?.two_factor_enabled" class="bg-emerald-50/60 border border-emerald-200/80 rounded-2xl p-5 flex items-start gap-4">
          <svg class="w-6 h-6 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="text-xs text-emerald-900 space-y-1">
            <p class="font-bold text-sm">Akun Anda Dilindungi Google Authenticator</p>
            <p class="text-emerald-800">
              Setiap kali masuk ke sistem WebGuard dari perangkat baru, Anda akan dimintai kode 6 digit dari aplikasi autentikator atau kode pemulihan cadangan.
            </p>
          </div>
        </div>

        <!-- If 2FA not enabled: Setup Flow -->
        <div v-else class="space-y-5">
          <div v-if="!setupData" class="space-y-3">
            <p class="text-xs text-slate-600">
              Tingkatkan standar keamanan akun Anda dengan mengaktifkan TOTP (Time-based One-Time Password) yang kompatibel dengan Google Authenticator, Microsoft Authenticator, atau Aegis.
            </p>
            <button
              @click="initiate2faSetup"
              :disabled="loadingSetup"
              class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-2"
            >
              <div v-if="loadingSetup" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              <span>Mulai Setup 2FA Sekarang</span>
            </button>
          </div>

          <!-- Setup Walkthrough Step 2: Show Secret / QR and Verify Code -->
          <div v-else class="space-y-5 bg-slate-50 p-6 rounded-2xl border border-slate-200/80">
            <div class="space-y-1">
              <h4 class="text-sm font-bold text-slate-900">Langkah 1: Pindai Barcode atau Salin Secret Key</h4>
              <p class="text-xs text-slate-500">
                Buka aplikasi Google Authenticator di smartphone Anda, pilih Tambah Akun (+) dan masukkan kunci rahasia berikut:
              </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-6 bg-white p-4 rounded-xl border border-slate-200">
              <div class="text-center">
                <img
                  v-if="setupData.qr_code_url"
                  :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(setupData.qr_code_url)}`"
                  alt="QR Code 2FA"
                  class="w-40 h-40 border border-slate-200 rounded-lg p-1 mx-auto"
                />
              </div>
              <div class="space-y-2 flex-1 text-center sm:text-left">
                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider">Secret Key (Manual Entry):</div>
                <code class="px-3 py-1.5 bg-slate-100 rounded-lg font-mono text-sm font-bold text-blue-700 tracking-widest block max-w-sm">
                  {{ setupData.secret }}
                </code>
                <p class="text-[11px] text-slate-400">
                  Ketikkan kunci ini ke aplikasi autentikator jika Anda tidak dapat memindai barcode.
                </p>
              </div>
            </div>

            <!-- Confirmation Code Input -->
            <div class="space-y-2 pt-2">
              <h4 class="text-sm font-bold text-slate-900">Langkah 2: Konfirmasi Kode 6 Digit</h4>
              <p class="text-xs text-slate-500">
                Masukkan kode angka 6 digit yang muncul pada aplikasi Authenticator Anda:
              </p>
              <div class="flex gap-3 max-w-xs">
                <input
                  v-model="confirmationCode"
                  maxlength="6"
                  type="text"
                  placeholder="000000"
                  class="w-full text-center px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-lg font-mono font-bold tracking-widest outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
                <button
                  @click="confirm2fa"
                  :disabled="confirmationCode.length !== 6 || verifying2fa"
                  class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm disabled:opacity-40 transition"
                >
                  {{ verifying2fa ? '...' : 'Aktifkan' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Recovery Codes Display Modal/Banner -->
          <div v-if="recoveryCodes.length > 0" class="bg-amber-50 border border-amber-200 rounded-2xl p-5 space-y-3">
            <div class="flex items-center gap-2">
              <span class="text-amber-700 font-bold text-sm">⚠️ SIMPAN KODE PEMULIHAN INI DI TEMPAT AMAN!</span>
            </div>
            <p class="text-xs text-amber-800">
              Jika Anda kehilangan akses ke smartphone, Anda HANYA dapat masuk menggunakan salah satu kode darurat sekali pakai berikut:
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 font-mono text-xs font-bold text-slate-900 bg-white p-4 rounded-xl border border-amber-200">
              <div v-for="(code, idx) in recoveryCodes" :key="idx" class="p-1 text-center bg-slate-50 rounded">
                {{ code }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import Swal from 'sweetalert2';

const authStore = useAuthStore();

const loadingSetup = ref(false);
const setupData = ref<any>(null);
const confirmationCode = ref('');
const verifying2fa = ref(false);
const recoveryCodes = ref<string[]>([]);

const getInitials = (name: string) => {
  if (!name) return 'PG';
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

const initiate2faSetup = async () => {
  loadingSetup.value = true;
  try {
    const res = await api.post('/auth/2fa/setup');
    setupData.value = res.data;
  } catch (e: any) {
    Swal.fire('Gagal', e.response?.data?.message || 'Gagal memulai setup 2FA.', 'error');
  } finally {
    loadingSetup.value = false;
  }
};

const confirm2fa = async () => {
  if (confirmationCode.value.length !== 6) return;
  verifying2fa.value = true;
  try {
    const res = await api.post('/auth/2fa/enable', {
      secret: setupData.value.secret,
      code: confirmationCode.value,
    });

    recoveryCodes.value = res.data.recovery_codes || [];
    await authStore.fetchUser();

    Swal.fire({
      icon: 'success',
      title: '2FA Berhasil Diaktifkan!',
      text: 'Akun Anda sekarang terlindungi secara maksimal dengan Google Authenticator.',
      confirmButtonColor: '#2563EB',
    });
  } catch (e: any) {
    Swal.fire('Kode Salah', e.response?.data?.message || 'Kode verifikasi tidak cocok. Silakan coba lagi.', 'error');
  } finally {
    verifying2fa.value = false;
  }
};
</script>
