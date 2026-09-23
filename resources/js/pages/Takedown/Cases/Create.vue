<template>
  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Breadcrumb & Back -->
      <div class="flex items-center gap-3">
        <router-link
          to="/takedown/cases"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
          Kembali ke Daftar Kasus
        </router-link>
      </div>

      <!-- Header Title -->
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inisiasi Kasus Takedown Baru</h1>
        <p class="text-sm text-slate-500 mt-1">
          Penyusunan berkas permohonan penurunan website ilegal berdasarkan observasi teknis dan bukti digital forensik.
        </p>
      </div>

      <!-- Main Form Card -->
      <form @submit.prevent="handleSubmit" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-8">
        <!-- Section 1: Target Information -->
        <div>
          <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
              1
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">Identitas Target & Kategori Kasus</h2>
              <p class="text-xs text-slate-500">Domain, URL, dan klasifikasi dugaan tindak pelanggaran.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">
            <!-- Target Domain -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Target Domain <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.target_domain"
                required
                type="text"
                placeholder="misal: judi-online-slot88.xyz atau berita-hoax.com"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-mono"
              />
            </div>

            <!-- Target URL -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Target Full URL <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.target_url"
                required
                type="url"
                placeholder="https://judi-online-slot88.xyz/register"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-mono"
              />
            </div>

            <!-- Kategori Pelanggaran -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Kategori Dugaan Pelanggaran <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.category"
                required
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-semibold text-slate-800"
              >
                <option value="Hoax / Disinformasi">Hoax / Disinformasi Publik</option>
                <option value="Phishing">Phishing / Pencurian Kredensial</option>
                <option value="Penipuan Finansial">Penipuan Finansial / Investasi Bodong</option>
                <option value="Judi Online">Judi Online / Perjudian Siber</option>
                <option value="Malware Distribution">Distribusi Malware / Ransomware</option>
                <option value="Pencemaran Nama Baik / Ilegal">Konten Ilegal / Pencemaran Institusi</option>
                <option value="Pelanggaran Hak Cipta">Pelanggaran Hak Cipta / Pembajakan</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>

            <!-- Prioritas Kasus -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Tingkat Prioritas (SLA Urgency)
              </label>
              <select
                v-model="form.priority"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-semibold text-slate-800"
              >
                <option value="CRITICAL">CRITICAL (Mendesak / Dampak Masif)</option>
                <option value="HIGH">HIGH (Tinggi)</option>
                <option value="MEDIUM">MEDIUM (Standar)</option>
                <option value="LOW">LOW (Rendah)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Section 2: Provider Tujuan & Saluran Pelaporan -->
        <div>
          <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
              2
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">Penyedia Layanan (Provider) Tujuan</h2>
              <p class="text-xs text-slate-500">Pilih dari direktori provider atau masukkan entitas tujuan secara manual.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">
            <!-- Provider Selection -->
            <div class="sm:col-span-2">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Pilih Dari Direktori Provider
              </label>
              <select
                v-model="form.provider_id"
                @change="onProviderSelect"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              >
                <option value="">Pilih Provider Terdaftar (PANDI, Kominfo, BSSN, Cloudflare, dll)</option>
                <option v-for="p in takedownStore.providers" :key="p.id" :value="p.id">
                  {{ p.name }} ({{ p.type }}) — SLA: {{ p.sla_hours }} Jam
                </option>
              </select>
            </div>

            <!-- Provider Name -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Nama Entitas Provider
              </label>
              <input
                v-model="form.provider_name"
                type="text"
                placeholder="Contoh: PANDI / Cloudflare / Kominfo"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              />
            </div>

            <!-- Provider Contact / Abuse URL -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Kontak Abuse / URL Portal Pelaporan
              </label>
              <input
                v-model="form.provider_contact"
                type="text"
                placeholder="abuse@pandi.id atau https://aduankonten.id"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-mono"
              />
            </div>
          </div>
        </div>

        <!-- Section 3: Legal Basis & Allegation Details -->
        <div>
          <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
              3
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">Uraian Dugaan & Dasar Kebijakan</h2>
              <p class="text-xs text-slate-500">Dasar hukum dan kronologi temuan berdasarkan hasil observasi.</p>
            </div>
          </div>

          <div class="space-y-4 mt-5">
            <!-- Uraian Dugaan Pelanggaran -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Rangkuman Uraian Aktivitas Bermasalah <span class="text-rose-500">*</span>
              </label>
              <textarea
                v-model="form.allegation_summary"
                required
                rows="4"
                placeholder="Jelaskan secara faktual: Website target memuat konten penipuan / hoax dengan meniru institusi resmi, mendistribusikan informasi palsu yang merugikan publik..."
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              ></textarea>
              <p class="text-[11px] text-slate-400 mt-1">
                Gunakan bahasa faktual dan objektif (misal: "Berdasarkan hasil observasi dan bukti yang terlampir, ditemukan indikasi...").
              </p>
            </div>

            <!-- Dasar Hukum / Kebijakan -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Dasar Hukum / Kebijakan Pelaporan
              </label>
              <input
                v-model="form.legal_or_policy_basis"
                type="text"
                placeholder="UU ITE No. 1 Tahun 2024 Pasal 27/28, Peraturan Menteri Kominfo, Ketentuan Layanan (ToS) Provider"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              />
            </div>
          </div>
        </div>

        <!-- Section 4: Evidence Selection (if investigation is linked) -->
        <div v-if="investigationEvidences.length > 0">
          <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
              4
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">Pilih Barang Bukti Digital Forensik</h2>
              <p class="text-xs text-slate-500">Bukti yang dicentang akan otomatis dimasukkan ke dalam Evidence Package (ZIP + SHA-256 Manifest).</p>
            </div>
          </div>

          <div class="space-y-2 mt-4 max-h-60 overflow-y-auto border border-slate-200 rounded-xl p-3 bg-slate-50/40">
            <label
              v-for="ev in investigationEvidences"
              :key="ev.id"
              class="flex items-center gap-3 p-2.5 bg-white rounded-lg border border-slate-200 hover:border-blue-300 transition cursor-pointer text-xs"
            >
              <input
                type="checkbox"
                :value="ev.id"
                v-model="form.selected_evidence_ids"
                class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
              />
              <div class="flex-1">
                <span class="font-mono font-bold text-blue-600">{{ ev.evidence_code }}</span>
                <span class="ml-2 font-semibold text-slate-800">{{ ev.type }}</span>
                <span class="ml-2 text-slate-400 font-mono text-[11px] truncate block">{{ ev.sha256 }}</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Submit & Cancel Buttons -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <router-link
            to="/takedown/cases"
            class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition"
          >
            Batal
          </router-link>
          <button
            type="submit"
            :disabled="submitting"
            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-sm shadow-blue-500/10 disabled:opacity-50 transition"
          >
            <div v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <span>{{ submitting ? 'Memproses...' : 'Simpan & Susun Kasus Takedown' }}</span>
          </button>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.vue';
import { useTakedownStore } from '../../../stores/takedown';
import api from '../../../services/api';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const takedownStore = useTakedownStore();

const submitting = ref(false);
const investigationEvidences = ref<any[]>([]);

const form = reactive({
  investigation_id: null as number | null,
  target_domain: '',
  target_url: '',
  target_ip: '',
  category: 'Hoax / Disinformasi',
  priority: 'MEDIUM',
  provider_id: '',
  provider_name: '',
  provider_contact: '',
  allegation_summary: '',
  legal_or_policy_basis: 'UU ITE No. 1 Tahun 2024 Pasal 27/28, Peraturan Menkominfo, dan Ketentuan Layanan (ToS) Provider',
  evidence_summary: '',
  selected_evidence_ids: [] as number[],
});

const onProviderSelect = () => {
  if (!form.provider_id) return;
  const p = takedownStore.providers.find(item => item.id === Number(form.provider_id));
  if (p) {
    form.provider_name = p.name;
    form.provider_contact = p.abuse_email || p.abuse_url || '';
  }
};

const loadInvestigationData = async (invId: string | number) => {
  try {
    const res = await api.get(`/investigations/${invId}`);
    const inv = res.data.investigation;
    form.investigation_id = inv.id;
    form.target_domain = inv.target_domain || '';
    form.target_url = inv.target_url || '';
    form.target_ip = inv.target_ip || '';

    // Load evidences for selection
    const evRes = await api.get(`/investigations/${invId}/evidence`);
    investigationEvidences.value = evRes.data.data || [];
    form.selected_evidence_ids = investigationEvidences.value.map(e => e.id);
  } catch (e) {
    console.error('Failed to load investigation data', e);
  }
};

const handleSubmit = async () => {
  submitting.value = true;
  try {
    const payload = {
      ...form,
      provider_id: form.provider_id ? Number(form.provider_id) : null,
    };

    const res = await takedownStore.createCase(payload);

    await Swal.fire({
      icon: 'success',
      title: 'Kasus Takedown Disusun!',
      text: `Berkas kasus ${res.case.case_number} berhasil dibuat. Lanjutkan ke langkah pengajuan laporan atau pembuatan paket bukti.`,
      confirmButtonColor: '#2563EB',
    });

    router.push(`/takedown/cases/${res.case.uuid}`);
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal Menyimpan Kasus',
      text: err.response?.data?.message || 'Gagal menyimpan berkas kasus takedown.',
      confirmButtonColor: '#2563EB',
    });
  } finally {
    submitting.value = false;
  }
};

onMounted(async () => {
  await takedownStore.fetchProviders({ active_only: true });

  const invQuery = route.query.investigation_id;
  if (invQuery) {
    await loadInvestigationData(invQuery as string);
  }
});
</script>
