<template>
  <div class="min-h-screen bg-[#F8FAFC] flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center select-none">
      <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#1E40AF] to-[#2563EB] items-center justify-center text-white shadow-xl shadow-blue-500/20 mb-4">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
      </div>
      <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">
        WEBGUARD<span class="text-[#2563EB]">.</span>
      </h2>
      <p class="mt-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
        Platform Investigasi & Pelaporan Siber
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/50 sm:rounded-2xl border border-slate-200/80 sm:px-10">
        <form class="space-y-5" @submit.prevent="submitLogin">
          <div v-if="errorMessage" class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            {{ errorMessage }}
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Alamat Email Kedinasan
            </label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="nama@webguard.id"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Kata Sandi (Password)
            </label>
            <input
              v-model="form.password"
              type="password"
              required
              placeholder="••••••••••••"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition"
            />
          </div>

          <!-- 2FA Input (Shown when user has 2FA enabled) -->
          <div v-if="requires2FA" class="p-4 bg-blue-50/60 rounded-xl border border-blue-200/80 space-y-3">
            <div class="flex items-center gap-2 text-xs font-bold text-blue-900">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              Two-Factor Authentication
            </div>
            <p class="text-[11px] text-blue-700">
              Buka aplikasi Google Authenticator dan masukkan 6 digit kode yang tertera.
            </p>
            <input
              v-model="form.two_factor_code"
              type="text"
              maxlength="6"
              placeholder="123456"
              class="w-full px-4 py-2.5 bg-white rounded-lg border border-blue-300 font-mono tracking-widest text-center text-lg font-bold focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3.5 px-4 rounded-xl text-sm font-bold text-white bg-[#2563EB] hover:bg-blue-700 shadow-md shadow-blue-500/25 transition duration-150 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <span v-if="loading">Memverifikasi...</span>
            <span v-else>Masuk ke Sistem &rarr;</span>
          </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
          <p class="text-[11px] text-slate-400 leading-relaxed">
            Hanya personel terotorisasi yang diizinkan masuk.<br>
            Akun default demo: <strong>superadmin@webguard.id</strong> / <strong>WebGuardPassword123!</strong>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const form = reactive({
  email: 'superadmin@webguard.id',
  password: 'WebGuardPassword123!',
  two_factor_code: '',
  recovery_code: '',
});

const loading = ref(false);
const requires2FA = ref(false);
const errorMessage = ref('');

const submitLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const result = await authStore.login({
      email: form.email,
      password: form.password,
      two_factor_code: form.two_factor_code || undefined,
      recovery_code: form.recovery_code || undefined,
    });

    if (result.requires_two_factor) {
      requires2FA.value = true;
    } else {
      router.push('/dashboard');
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Login gagal. Periksa kembali email dan password Anda.';
  } finally {
    loading.value = false;
  }
};
</script>
