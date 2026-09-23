<template>
  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Breadcrumb & Back -->
      <div class="flex items-center gap-3">
        <router-link
          to="/personnel"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
          Kembali ke Daftar Personel
        </router-link>
      </div>

      <!-- Header Title -->
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pendaftaran Personel Baru</h1>
        <p class="text-sm text-slate-500 mt-1">
          Daftarkan anggota tim investigasi, analis siber, atau administrator dengan otorisasi peran dinas resmi.
        </p>
      </div>

      <!-- Main Form Card -->
      <form @submit.prevent="handleSubmit" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-8">
        <!-- Section 1: Identitas Kedinasan -->
        <div>
          <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
              1
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">Identitas & Kepegawaian</h2>
              <p class="text-xs text-slate-500">Nama resmi, NRP, pangkat militer/polisi, dan penugasan satuan.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">
            <!-- Nama Lengkap -->
            <div class="sm:col-span-2">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Nama Lengkap & Gelar <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.name"
                required
                type="text"
                placeholder="Contoh: Mayor Inf. Hendra Wijaya, S.Kom., M.T.I."
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              />
            </div>

            <!-- NRP -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Nomor Registrasi Personel (NRP / NIP)
              </label>
              <input
                v-model="form.nrp"
                type="text"
                placeholder="Contoh: 1108028910001"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-mono"
              />
            </div>

            <!-- Pangkat -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Pangkat Kedinasan
              </label>
              <select
                v-model="form.rank_id"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              >
                <option value="">Pilih Pangkat</option>
                <option v-for="r in personnelStore.ranks" :key="r.id" :value="r.id">
                  {{ r.name }} ({{ r.code }}) - {{ r.category }}
                </option>
              </select>
            </div>

            <!-- Jabatan -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Jabatan Struktural / Fungsional
              </label>
              <select
                v-model="form.position_id"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              >
                <option value="">Pilih Jabatan</option>
                <option v-for="p in personnelStore.positions" :key="p.id" :value="p.id">
                  {{ p.name }}
                </option>
              </select>
            </div>

            <!-- Satuan Unit -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Satuan Kerja / Subdit
              </label>
              <select
                v-model="form.unit_id"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              >
                <option value="">Pilih Satuan Kerja</option>
                <option v-for="u in personnelStore.units" :key="u.id" :value="u.id">
                  {{ u.name }} ({{ u.code }})
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Section 2: Kontak & Notifikasi -->
        <div>
          <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
              2
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">Kontak & Notifikasi Resmi</h2>
              <p class="text-xs text-slate-500">Nomor WhatsApp dan email kedinasan untuk notifikasi investigasi penting.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">
            <!-- Email -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Email Kedinasan <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.email"
                required
                type="email"
                placeholder="nama@polri.go.id / personel@gov.id"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              />
            </div>

            <!-- WhatsApp -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Nomor WhatsApp (Awali 62)
              </label>
              <input
                v-model="form.whatsapp_number"
                type="tel"
                placeholder="6281234567890"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-mono"
              />
            </div>

            <!-- Phone -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Telepon Kantor / Alternatif
              </label>
              <input
                v-model="form.phone"
                type="tel"
                placeholder="021-12345678"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-mono"
              />
            </div>

            <!-- Tanggal Mulai Aktif -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Tanggal Mulai Penugasan
              </label>
              <input
                v-model="form.active_from"
                type="date"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              />
            </div>
          </div>
        </div>

        <!-- Section 3: Akun, Keamanan & Peran -->
        <div>
          <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
              3
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">Peran & Kredensial Sistem</h2>
              <p class="text-xs text-slate-500">Tentukan tingkat kewenangan hak akses dan password awal akun.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">
            <!-- Role Selection -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Peran Hak Akses (RBAC) <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.role"
                required
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
              >
                <option value="investigator">Penyidik / Analis Siber (Investigator)</option>
                <option value="admin">Administrator (Admin)</option>
                <option value="viewer">Pengamat / Tamu (Viewer)</option>
                <option value="super_admin">Super Administrator</option>
              </select>
            </div>

            <!-- Password -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                  Password Awal (Min. 12 Karakter)
                </label>
                <button
                  type="button"
                  @click="generateRandomPassword"
                  class="text-xs font-semibold text-blue-600 hover:text-blue-700"
                >
                  Generate Kuat
                </button>
              </div>
              <input
                v-model="form.password"
                type="text"
                placeholder="Kosongkan untuk otomatis generate"
                class="w-full px-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none font-mono"
              />
              <p class="text-[11px] text-slate-400 mt-1">
                Kombinasi huruf besar, kecil, angka, dan simbol unik disarankan.
              </p>
            </div>
          </div>
        </div>

        <!-- Submit & Cancel Buttons -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <router-link
            to="/personnel"
            class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition"
          >
            Batal
          </router-link>
          <button
            type="submit"
            :disabled="submitting"
            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm shadow-blue-500/10 disabled:opacity-50 transition"
          >
            <div v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <span>{{ submitting ? 'Menyimpan...' : 'Simpan Personel' }}</span>
          </button>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { usePersonnelStore } from '../../stores/personnel';
import Swal from 'sweetalert2';

const router = useRouter();
const personnelStore = usePersonnelStore();

const form = reactive({
  name: '',
  nrp: '',
  rank_id: '',
  position_id: '',
  unit_id: '',
  phone: '',
  whatsapp_number: '',
  email: '',
  role: 'investigator',
  password: '',
  active_from: new Date().toISOString().split('T')[0],
});

const submitting = ref(false);

const generateRandomPassword = () => {
  const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*';
  let pass = '';
  for (let i = 0; i < 16; i++) {
    pass += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  form.password = pass;
};

const handleSubmit = async () => {
  submitting.value = true;
  try {
    const payload = {
      ...form,
      rank_id: form.rank_id ? Number(form.rank_id) : null,
      position_id: form.position_id ? Number(form.position_id) : null,
      unit_id: form.unit_id ? Number(form.unit_id) : null,
    };

    await personnelStore.createPersonnel(payload);

    await Swal.fire({
      icon: 'success',
      title: 'Personel Ditambahkan!',
      text: 'Data personel baru berhasil didaftarkan ke sistem WebGuard.',
      confirmButtonColor: '#2563EB',
    });

    router.push('/personnel');
  } catch (err: any) {
    const errorMsg = err.response?.data?.message || 'Gagal menyimpan data personel.';
    Swal.fire({
      icon: 'error',
      title: 'Gagal Menyimpan',
      text: errorMsg,
      confirmButtonColor: '#2563EB',
    });
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  personnelStore.fetchMasterOptions();
});
</script>
