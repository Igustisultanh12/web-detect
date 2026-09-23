<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Laporan Resmi Investigasi</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
              {{ total }} Laporan Diterbitkan
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Arsip dokumen laporan resmi siap serah terima penegak hukum (Polri/Kominfo/BSSN) dengan klasifikasi fakta hukum ketat.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <router-link
            to="/investigations"
            class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Buat Laporan dari Kasus
          </router-link>
        </div>
      </div>

      <!-- Reports Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-block animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mb-3"></div>
          <p class="text-sm font-medium text-slate-500">Memuat berkas laporan resmi...</p>
        </div>

        <div v-else-if="reports.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 mx-auto flex items-center justify-center text-slate-400 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-slate-800">Belum ada laporan resmi diterbitkan</h3>
          <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1">
            Buka menu Investigasi, pilih salah satu kasus, dan klik "Generate Laporan Resmi" untuk menerbitkan dokumen forensik.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-200/80">
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nomor Laporan</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Target Domain</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Format</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Penyusun</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Integritas Hash</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Terbit</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="rep in reports"
                :key="rep.id"
                class="hover:bg-slate-50/60 transition duration-150 group"
              >
                <!-- Nomor Laporan -->
                <td class="py-3.5 px-5">
                  <div class="font-mono text-xs font-bold text-blue-600 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ rep.report_number }}
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5">Versi {{ rep.version || 1 }}</div>
                </td>

                <!-- Target Domain -->
                <td class="py-3.5 px-4">
                  <router-link
                    v-if="rep.investigation"
                    :to="`/investigations/${rep.investigation.id}`"
                    class="font-semibold text-slate-900 hover:text-blue-600 text-sm block transition"
                  >
                    {{ rep.investigation.target_domain }}
                  </router-link>
                  <span v-else class="text-xs text-slate-400">-</span>
                  <div class="text-xs text-slate-400 font-mono">{{ rep.investigation?.case_code }}</div>
                </td>

                <!-- Format Badge -->
                <td class="py-3.5 px-4">
                  <span
                    :class="getFormatBadgeClass(rep.format)"
                    class="px-2.5 py-0.5 rounded-md text-[11px] font-black uppercase tracking-wider"
                  >
                    {{ rep.format }}
                  </span>
                </td>

                <!-- Penyusun -->
                <td class="py-3.5 px-4">
                  <div class="text-xs font-semibold text-slate-800">{{ rep.generator?.name || 'Sistem' }}</div>
                  <div class="text-[11px] text-slate-400">{{ rep.generator?.rank?.name || 'Analis Siber' }}</div>
                </td>

                <!-- SHA-256 Checksum -->
                <td class="py-3.5 px-4">
                  <code class="text-xs font-mono text-slate-600 bg-slate-50 px-2 py-0.5 rounded border border-slate-200/60 max-w-[140px] truncate block" :title="rep.sha256">
                    {{ rep.sha256 || 'N/A' }}
                  </code>
                </td>

                <!-- Tanggal Terbit -->
                <td class="py-3.5 px-4 text-xs text-slate-500 font-mono">
                  {{ formatDate(rep.generated_at || rep.created_at) }}
                </td>

                <!-- Unduh Action -->
                <td class="py-3.5 px-5 text-right">
                  <button
                    @click="downloadReport(rep)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition shadow-xs"
                    title="Unduh Dokumen Laporan Resmi"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh {{ rep.format }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="px-6 py-4 bg-slate-50/50 border-t border-slate-200/80 flex items-center justify-between">
          <div class="text-xs text-slate-500">
            Halaman <span class="font-bold text-slate-700">{{ currentPage }}</span> dari <span class="font-bold text-slate-700">{{ lastPage }}</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              :disabled="currentPage <= 1"
              @click="changePage(currentPage - 1)"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 transition"
            >
              Sebelumnya
            </button>
            <button
              :disabled="currentPage >= lastPage"
              @click="changePage(currentPage + 1)"
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
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import api from '../../services/api';
import Swal from 'sweetalert2';

const reports = ref<any[]>([]);
const total = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const loading = ref(false);

const loadReports = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/reports', { params: { page } });
    reports.value = res.data.data;
    total.value = res.data.meta.total;
    currentPage.value = res.data.meta.current_page;
    lastPage.value = res.data.meta.last_page;
  } catch (e: any) {
    Swal.fire('Gagal', 'Gagal memuat arsip laporan.', 'error');
  } finally {
    loading.value = false;
  }
};

const changePage = (p: number) => {
  loadReports(p);
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getFormatBadgeClass = (format: string) => {
  switch (format?.toUpperCase()) {
    case 'PDF':
      return 'bg-rose-50 text-rose-700 border border-rose-200/80';
    case 'CSV':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200/80';
    case 'JSON':
      return 'bg-amber-50 text-amber-700 border border-amber-200/80';
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200';
  }
};

const downloadReport = async (rep: any) => {
  try {
    const res = await api.get(`/reports/${rep.uuid}/download`, { responseType: 'blob' });
    const ext = rep.format.toLowerCase();
    const filename = `${rep.report_number}.${ext}`;
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (e: any) {
    Swal.fire('Gagal Unduh', 'Gagal mengunduh dokumen laporan resmi.', 'error');
  }
};

onMounted(() => {
  loadReports();
});
</script>
