<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kasus Takedown & Incident Response</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
              {{ takedownStore.totalCases }} Kasus Terdaftar
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Penanganan laporan permohonan penurunan website ilegal, penipuan, hoax, dan koordinasi dengan otoritas/provider.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <router-link
            to="/takedown/cases/create"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-500/10 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Buat Kasus Takedown
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
              placeholder="Cari No. Kasus, Domain, Tiket..."
              class="w-full pl-10 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            />
          </div>

          <!-- Status Filter -->
          <div>
            <select
              v-model="filters.status"
              @change="loadCases"
              class="w-full px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            >
              <option value="">Semua Status</option>
              <option value="DRAFT">DRAFT (Penyusunan)</option>
              <option value="READY_TO_SUBMIT">READY TO SUBMIT (Siap Kirim)</option>
              <option value="SUBMITTED">SUBMITTED (Terkirim)</option>
              <option value="ACKNOWLEDGED">ACKNOWLEDGED (Dikonfirmasi)</option>
              <option value="UNDER_REVIEW">UNDER REVIEW (Ditinjau)</option>
              <option value="ADDITIONAL_INFORMATION_REQUESTED">INFO REQUESTED (Perlu Info)</option>
              <option value="ACTION_TAKEN">ACTION TAKEN (Berhasil Ditakedown)</option>
              <option value="REJECTED">REJECTED (Ditolak)</option>
              <option value="CLOSED">CLOSED (Ditutup)</option>
            </select>
          </div>

          <!-- Category Filter -->
          <div>
            <select
              v-model="filters.category"
              @change="loadCases"
              class="w-full px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            >
              <option value="">Semua Kategori</option>
              <option value="Hoax / Disinformasi">Hoax / Disinformasi</option>
              <option value="Phishing">Phishing</option>
              <option value="Penipuan Finansial">Penipuan Finansial</option>
              <option value="Judi Online">Judi Online</option>
              <option value="Malware Distribution">Distribusi Malware</option>
              <option value="Pencemaran Nama Baik / Ilegal">Konten Ilegal / Pencemaran</option>
            </select>
          </div>

          <!-- Priority Filter -->
          <div>
            <select
              v-model="filters.priority"
              @change="loadCases"
              class="w-full px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            >
              <option value="">Semua Prioritas</option>
              <option value="CRITICAL">CRITICAL</option>
              <option value="HIGH">HIGH</option>
              <option value="MEDIUM">MEDIUM</option>
              <option value="LOW">LOW</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Cases Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div v-if="takedownStore.loading" class="p-12 text-center">
          <div class="inline-block animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mb-3"></div>
          <p class="text-sm font-medium text-slate-500">Memuat berkas kasus takedown...</p>
        </div>

        <div v-else-if="takedownStore.cases.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 mx-auto flex items-center justify-center text-slate-400 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-slate-800">Tidak ada kasus takedown ditemukan</h3>
          <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1">
            Gunakan filter yang berbeda atau buat kasus takedown baru dari hasil investigasi website.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-200/80">
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Kasus</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Target Domain</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kategori</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Provider / Tiket</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Prioritas</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Batas SLA</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in takedownStore.cases"
                :key="item.id"
                class="hover:bg-slate-50/60 transition duration-150 group"
              >
                <!-- Nomor Kasus -->
                <td class="py-3.5 px-5">
                  <router-link :to="`/takedown/cases/${item.uuid}`" class="font-mono text-xs font-bold text-blue-600 hover:text-blue-800 block">
                    {{ item.case_number }}
                  </router-link>
                  <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ formatDate(item.created_at) }}</div>
                </td>

                <!-- Target Domain -->
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900 text-sm group-hover:text-blue-600 transition truncate max-w-[200px]" :title="item.target_domain">
                    {{ item.target_domain }}
                  </div>
                  <div class="text-xs text-slate-400 truncate max-w-[200px] font-mono">{{ item.target_url }}</div>
                </td>

                <!-- Kategori -->
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
                    {{ item.category }}
                  </span>
                </td>

                <!-- Provider & Tiket -->
                <td class="py-3.5 px-4">
                  <div class="text-xs font-semibold text-slate-800">{{ item.provider_name || 'Umum / Mandiri' }}</div>
                  <div class="text-[11px] font-mono text-slate-500">
                    {{ item.external_reference_number ? '#' + item.external_reference_number : '(Menunggu Tiket)' }}
                  </div>
                </td>

                <!-- Status Badge -->
                <td class="py-3.5 px-4">
                  <span
                    :class="getStatusBadgeClass(item.status)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                  >
                    <span :class="getStatusDotClass(item.status)" class="w-1.5 h-1.5 rounded-full"></span>
                    {{ getStatusLabel(item.status) }}
                  </span>
                </td>

                <!-- Priority -->
                <td class="py-3.5 px-4">
                  <span
                    :class="getPriorityClass(item.priority)"
                    class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider"
                  >
                    {{ item.priority }}
                  </span>
                </td>

                <!-- Batas SLA -->
                <td class="py-3.5 px-4 text-xs font-mono">
                  <span v-if="item.next_follow_up_at" :class="isOverdue(item) ? 'text-rose-600 font-bold' : 'text-slate-600'">
                    {{ formatDate(item.next_follow_up_at) }}
                    <span v-if="isOverdue(item)" class="text-[10px] block font-sans text-rose-500">Lewat Batas</span>
                  </span>
                  <span v-else class="text-slate-400">-</span>
                </td>

                <!-- Aksi -->
                <td class="py-3.5 px-5 text-right">
                  <router-link
                    :to="`/takedown/cases/${item.uuid}`"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition shadow-xs"
                  >
                    Kelola Kasus →
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="takedownStore.lastPage > 1" class="px-6 py-4 bg-slate-50/50 border-t border-slate-200/80 flex items-center justify-between">
          <div class="text-xs text-slate-500">
            Halaman <span class="font-bold text-slate-700">{{ takedownStore.currentPage }}</span> dari <span class="font-bold text-slate-700">{{ takedownStore.lastPage }}</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              :disabled="takedownStore.currentPage <= 1"
              @click="changePage(takedownStore.currentPage - 1)"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 transition"
            >
              Sebelumnya
            </button>
            <button
              :disabled="takedownStore.currentPage >= takedownStore.lastPage"
              @click="changePage(takedownStore.currentPage + 1)"
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
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.vue';
import { useTakedownStore } from '../../../stores/takedown';

const route = useRoute();
const takedownStore = useTakedownStore();

const filters = reactive({
  search: '',
  status: '',
  category: '',
  priority: '',
});

let debounceTimer: any = null;
const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    loadCases();
  }, 350);
};

const loadCases = (page = 1) => {
  takedownStore.fetchCases({
    page,
    search: filters.search || undefined,
    status: filters.status || undefined,
    category: filters.category || undefined,
    priority: filters.priority || undefined,
    overdue_only: route.query.overdue_only === 'true',
  });
};

const changePage = (p: number) => {
  loadCases(p);
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const isOverdue = (item: any) => {
  if (!item.next_follow_up_at) return false;
  if (['ACTION_TAKEN', 'CLOSED', 'RESOLVED', 'REJECTED'].includes(item.status)) return false;
  return new Date(item.next_follow_up_at) < new Date();
};

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'ACTION_TAKEN':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200/60';
    case 'SUBMITTED':
    case 'ACKNOWLEDGED':
    case 'UNDER_REVIEW':
      return 'bg-blue-50 text-blue-700 border border-blue-200/60';
    case 'ADDITIONAL_INFORMATION_REQUESTED':
    case 'READY_TO_SUBMIT':
      return 'bg-amber-50 text-amber-700 border border-amber-200/60';
    case 'REJECTED':
      return 'bg-rose-50 text-rose-700 border border-rose-200/60';
    default:
      return 'bg-slate-100 text-slate-600 border border-slate-200';
  }
};

const getStatusDotClass = (status: string) => {
  switch (status) {
    case 'ACTION_TAKEN': return 'bg-emerald-500';
    case 'SUBMITTED':
    case 'ACKNOWLEDGED':
    case 'UNDER_REVIEW': return 'bg-blue-500';
    case 'ADDITIONAL_INFORMATION_REQUESTED':
    case 'READY_TO_SUBMIT': return 'bg-amber-500';
    case 'REJECTED': return 'bg-rose-500';
    default: return 'bg-slate-400';
  }
};

const getStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    DRAFT: 'Draft',
    READY_TO_SUBMIT: 'Siap Kirim',
    SUBMITTED: 'Terkirim',
    ACKNOWLEDGED: 'Dikonfirmasi',
    UNDER_REVIEW: 'Ditinjau',
    ADDITIONAL_INFORMATION_REQUESTED: 'Perlu Info',
    ACTION_TAKEN: 'Takedown Berhasil',
    REJECTED: 'Ditolak',
    ESCALATED: 'Dieskalasi',
    CLOSED: 'Ditutup',
    NO_RESPONSE: 'Tidak Ada Respon',
  };
  return map[status] || status;
};

const getPriorityClass = (priority: string) => {
  switch (priority) {
    case 'CRITICAL': return 'bg-rose-100 text-rose-800 border border-rose-200';
    case 'HIGH': return 'bg-amber-100 text-amber-800 border border-amber-200';
    case 'MEDIUM': return 'bg-blue-100 text-blue-800 border border-blue-200';
    default: return 'bg-slate-100 text-slate-700 border border-slate-200';
  }
};

onMounted(() => {
  loadCases();
});
</script>
