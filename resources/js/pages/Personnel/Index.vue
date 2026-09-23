<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Data Personel & Penyidik</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
              {{ personnelStore.total }} Personel Terdaftar
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Manajemen personel intelijen, analis siber, dan penyidik dengan kontrol akses berbasis peran (RBAC).
          </p>
        </div>

        <div class="flex items-center gap-3">
          <router-link
            to="/personnel/create"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm shadow-blue-500/10 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Personel
          </router-link>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Search -->
          <div class="relative">
            <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              placeholder="Cari Nama, NRP, Email..."
              class="w-full pl-10 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            />
          </div>

          <!-- Status Filter -->
          <div>
            <select
              v-model="filters.status"
              @change="loadPersonnel"
              class="w-full px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            >
              <option value="">Semua Status</option>
              <option value="active">Aktif (Active)</option>
              <option value="inactive">Nonaktif (Inactive)</option>
              <option value="suspended">Ditangguhkan (Suspended)</option>
              <option value="pending">Menunggu Aktivasi (Pending)</option>
            </select>
          </div>

          <!-- Rank Filter -->
          <div>
            <select
              v-model="filters.rank_id"
              @change="loadPersonnel"
              class="w-full px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            >
              <option value="">Semua Pangkat</option>
              <option v-for="r in personnelStore.ranks" :key="r.id" :value="r.id">
                {{ r.name }} ({{ r.code }})
              </option>
            </select>
          </div>

          <!-- Role Filter -->
          <div>
            <select
              v-model="filters.role"
              @change="loadPersonnel"
              class="w-full px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            >
              <option value="">Semua Peran (Role)</option>
              <option value="superadmin">Super Admin</option>
              <option value="admin">Administrator</option>
              <option value="investigator">Penyidik / Analis</option>
              <option value="viewer">Pengamat (Viewer)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Personnel Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div v-if="personnelStore.loading" class="p-12 text-center">
          <div class="inline-block animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mb-3"></div>
          <p class="text-sm font-medium text-slate-500">Memuat data personel...</p>
        </div>

        <div v-else-if="personnelStore.personnel.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 mx-auto flex items-center justify-center text-slate-400 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-slate-800">Tidak ada data personel ditemukan</h3>
          <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1">
            Gunakan filter pencarian yang berbeda atau tambahkan personel baru ke dalam sistem.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-200/80">
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Personel</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">NRP / NIK</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pangkat & Jabatan</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Satuan</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Peran</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Investigasi</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="user in personnelStore.personnel"
                :key="user.id"
                class="hover:bg-slate-50/60 transition duration-150 group"
              >
                <!-- Nama & Avatar -->
                <td class="py-3.5 px-5">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-sm flex items-center justify-center shrink-0 shadow-sm shadow-blue-500/10">
                      {{ getInitials(user.name) }}
                    </div>
                    <div>
                      <router-link :to="`/personnel/${user.uuid}`" class="font-bold text-slate-900 group-hover:text-blue-600 text-sm transition">
                        {{ user.name }}
                      </router-link>
                      <div class="text-xs text-slate-500">{{ user.email }}</div>
                    </div>
                  </div>
                </td>

                <!-- NRP / NIK -->
                <td class="py-3.5 px-4">
                  <div class="text-sm font-semibold font-mono text-slate-800">{{ user.nrp || '-' }}</div>
                  <div class="text-xs text-slate-400 font-mono">{{ user.nik ? maskNik(user.nik) : '' }}</div>
                </td>

                <!-- Pangkat & Jabatan -->
                <td class="py-3.5 px-4">
                  <div class="text-sm font-semibold text-slate-800">
                    {{ user.rank?.name || 'Non-Pangkat' }}
                  </div>
                  <div class="text-xs text-slate-500">
                    {{ user.position?.name || '-' }}
                  </div>
                </td>

                <!-- Satuan -->
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                    {{ user.unit?.name || 'Mabes' }}
                  </span>
                </td>

                <!-- Roles -->
                <td class="py-3.5 px-4">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="r in user.roles"
                      :key="r.id"
                      class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/50"
                    >
                      {{ r.name }}
                    </span>
                  </div>
                </td>

                <!-- Status Badge -->
                <td class="py-3.5 px-4">
                  <span
                    :class="getStatusBadgeClass(user.status)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                  >
                    <span :class="getStatusDotClass(user.status)" class="w-1.5 h-1.5 rounded-full"></span>
                    {{ getStatusLabel(user.status) }}
                  </span>
                </td>

                <!-- Investigasi Count -->
                <td class="py-3.5 px-4 text-center">
                  <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 font-bold text-xs text-slate-700">
                    {{ user.investigations_count ?? 0 }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-5 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <router-link
                      :to="`/personnel/${user.uuid}`"
                      title="Lihat Detail Profil"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </router-link>

                    <!-- Toggle Status Button -->
                    <button
                      v-if="user.status !== 'active'"
                      @click="handleActivate(user)"
                      title="Aktifkan Personel"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </button>

                    <button
                      v-if="user.status === 'active'"
                      @click="handleDeactivate(user)"
                      title="Nonaktifkan / Suspend"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="personnelStore.lastPage > 1" class="px-6 py-4 bg-slate-50/50 border-t border-slate-200/80 flex items-center justify-between">
          <div class="text-xs text-slate-500">
            Halaman <span class="font-bold text-slate-700">{{ personnelStore.currentPage }}</span> dari <span class="font-bold text-slate-700">{{ personnelStore.lastPage }}</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              :disabled="personnelStore.currentPage <= 1"
              @click="changePage(personnelStore.currentPage - 1)"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 transition"
            >
              Sebelumnya
            </button>
            <button
              :disabled="personnelStore.currentPage >= personnelStore.lastPage"
              @click="changePage(personnelStore.currentPage + 1)"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 transition"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { usePersonnelStore } from '../../stores/personnel';
import Swal from 'sweetalert2';

const personnelStore = usePersonnelStore();

const filters = reactive({
  search: '',
  status: '',
  rank_id: '',
  role: '',
});

let debounceTimer: any = null;
const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    loadPersonnel();
  }, 350);
};

const loadPersonnel = (page = 1) => {
  personnelStore.fetchPersonnel({
    page,
    search: filters.search || undefined,
    status: filters.status || undefined,
    rank_id: filters.rank_id ? Number(filters.rank_id) : undefined,
    role: filters.role || undefined,
  });
};

const changePage = (p: number) => {
  loadPersonnel(p);
};

const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

const maskNik = (nik: string) => {
  if (nik.length < 8) return nik;
  return nik.substring(0, 6) + '******' + nik.substring(nik.length - 4);
};

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'active':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200/60';
    case 'inactive':
      return 'bg-slate-100 text-slate-600 border border-slate-200';
    case 'suspended':
      return 'bg-rose-50 text-rose-700 border border-rose-200/60';
    case 'pending':
      return 'bg-amber-50 text-amber-700 border border-amber-200/60';
    default:
      return 'bg-slate-100 text-slate-600 border border-slate-200';
  }
};

const getStatusDotClass = (status: string) => {
  switch (status) {
    case 'active': return 'bg-emerald-500';
    case 'inactive': return 'bg-slate-400';
    case 'suspended': return 'bg-rose-500';
    case 'pending': return 'bg-amber-500';
    default: return 'bg-slate-400';
  }
};

const getStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    active: 'Aktif',
    inactive: 'Nonaktif',
    suspended: 'Ditangguhkan',
    pending: 'Pending',
  };
  return map[status] || status;
};

const handleActivate = async (user: any) => {
  const result = await Swal.fire({
    title: 'Aktivasi Personel?',
    text: `Aktifkan akses sistem untuk ${user.name}?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#2563EB',
    cancelButtonColor: '#94A3B8',
    confirmButtonText: 'Ya, Aktifkan',
    cancelButtonText: 'Batal',
  });

  if (result.isConfirmed) {
    try {
      await personnelStore.activatePersonnel(user.uuid);
      Swal.fire('Berhasil', 'Status personel berhasil diaktifkan.', 'success');
      loadPersonnel();
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || 'Gagal mengubah status', 'error');
    }
  }
};

const handleDeactivate = async (user: any) => {
  const { value: reason } = await Swal.fire({
    title: 'Nonaktifkan Akses Personel?',
    input: 'text',
    inputLabel: `Alasan penonaktifan untuk ${user.name}:`,
    inputPlaceholder: 'Contoh: Mutasi tugas / Rotasi divisi...',
    showCancelButton: true,
    confirmButtonColor: '#E11D48',
    cancelButtonColor: '#94A3B8',
    confirmButtonText: 'Nonaktifkan',
    cancelButtonText: 'Batal',
    inputValidator: (val) => {
      if (!val) return 'Alasan wajib diisi!';
    }
  });

  if (reason) {
    try {
      await personnelStore.deactivatePersonnel(user.uuid, reason);
      Swal.fire('Berhasil', 'Akses personel berhasil dinonaktifkan.', 'success');
      loadPersonnel();
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || 'Gagal menonaktifkan', 'error');
    }
  }
};

onMounted(() => {
  personnelStore.fetchMasterOptions();
  loadPersonnel();
});
</script>
