<template>
  <AuthenticatedLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="flex items-center gap-3">
        <router-link to="/investigations" class="p-2 hover:bg-slate-200/60 rounded-xl text-slate-500 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
        </router-link>
        <div>
          <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Formulir Investigasi Pasif Baru</h2>
          <p class="text-xs text-slate-500 mt-0.5">Analisis teknis domain dan pengumpulan bukti digital publik.</p>
        </div>
      </div>

      <!-- Legal & Defensive Notice -->
      <div class="p-5 rounded-2xl bg-blue-50/70 border border-blue-200 text-xs text-blue-900 space-y-2">
        <div class="flex items-center gap-2 font-bold text-sm text-blue-800">
          <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          Prinsip Investigasi & Batasan Etika Operasi
        </div>
        <p class="leading-relaxed text-blue-800">
          Aplikasi ini dirancang khusus untuk <strong>analisis pasif defensif</strong> terhadap aset informasi publik. Sistem secara otomatis menolak dan <strong>TIDAK MENYEDIAKAN</strong> fitur eksploitasi, DDoS, brute force, bypassing keamanan, atau tindakan merusak server target.
        </p>
      </div>

      <!-- Form Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <form @submit.prevent="submitForm" class="space-y-6">
          <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            {{ errorMessage }}
          </div>

          <!-- Target URL -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              URL / Domain Target <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="form.target_url"
                type="text"
                required
                placeholder="https://contoh-situs-mencurigakan.com"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
              Masukkan URL lengkap atau nama domain. Sistem otomatis memvalidasi terhadap kerentanan SSRF (loopback, IP privat, dan metadata terlarang).
            </p>
          </div>

          <!-- Grid: Category & Priority -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Kategori Dugaan Pelanggaran
              </label>
              <select
                v-model="form.category"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="Phishing">Phishing / Pemalsuan Identitas</option>
                <option value="Malware">Penyebaran Malware / Script Berbahaya</option>
                <option value="Fraud">Fraud / Penipuan Finansial</option>
                <option value="Illegal Content">Konten Ilegal / Perjudian / Pornografi</option>
                <option value="Copyright">Pelanggaran Hak Cipta / Pembajakan</option>
                <option value="Spam">Spamming / Botnet</option>
                <option value="Suspicious Domain">Suspicious Domain (Domain Mencurigakan)</option>
                <option value="Other">Lainnya (Penyelidikan Umum)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Tingkat Prioritas Penanganan
              </label>
              <select
                v-model="form.priority"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="LOW">Rendah (LOW)</option>
                <option value="MEDIUM">Sedang (MEDIUM)</option>
                <option value="HIGH">Tinggi (HIGH)</option>
                <option value="CRITICAL">Kritis / Mendesak (CRITICAL)</option>
              </select>
            </div>
          </div>

          <!-- Reason / Notes -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Latar Belakang / Alasan Investigasi (Opsional)
            </label>
            <textarea
              v-model="form.reason"
              rows="3"
              placeholder="Catatan awal investigator, laporan dari masyarakat, indikasi temuan awal..."
              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <!-- Submit Button -->
          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <router-link
              to="/investigations"
              class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
            >
              Batal
            </router-link>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider transition shadow-md shadow-blue-500/25 flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="loading">Memproses & Mengantrekan...</span>
              <span v-else>Antrekan Investigasi (Queue) &rarr;</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { useInvestigationStore } from '../../stores/investigation';

const router = useRouter();
const investigationStore = useInvestigationStore();

const form = reactive({
  target_url: '',
  category: 'Suspicious Domain',
  priority: 'MEDIUM',
  reason: '',
});

const loading = ref(false);
const errorMessage = ref('');

const submitForm = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const res = await investigationStore.createInvestigation(form);
    if (res.success && res.investigation?.id) {
      router.push(`/investigations/${res.investigation.id}`);
    } else {
      router.push('/investigations');
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Gagal membuat investigasi. Periksa format URL target.';
  } finally {
    loading.value = false;
  }
};
</script>
