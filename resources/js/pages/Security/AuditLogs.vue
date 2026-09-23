<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Jejak Audit Sistem (Audit Trail)</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
              {{ total }} Catatan Log Audit
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Rekaman aktivitas forensik internal yang immutable untuk kepatuhan hukum dan transparansi operasional.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <router-link
            to="/security"
            class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-sm transition flex items-center gap-1.5"
          >
            ← Kembali ke Ringkasan Keamanan
          </router-link>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <!-- Search -->
          <div class="relative">
            <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              placeholder="Cari tindakan, IP Address, target entitas..."
              class="w-full pl-10 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            />
          </div>

          <!-- Action Filter -->
          <div>
            <input
              v-model="filters.action"
              @input="debounceSearch"
              type="text"
              placeholder="Filter tipe tindakan (misal: INVESTIGATION_CREATED, USER_LOGIN)..."
              class="w-full px-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            />
          </div>
        </div>
      </div>

      <!-- Audit Logs Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-block animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mb-3"></div>
          <p class="text-sm font-medium text-slate-500">Memuat riwayat log audit...</p>
        </div>

        <div v-else-if="logs.length === 0" class="p-12 text-center">
          <p class="text-sm font-semibold text-slate-700">Tidak ada log audit ditemukan</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-200/80">
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Waktu Kejadian</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aktor / Personel</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tindakan</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Target Entitas</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Alamat IP</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Rincian Metadata</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="log in logs"
                :key="log.id"
                class="hover:bg-slate-50/60 transition duration-150 group"
              >
                <!-- Waktu -->
                <td class="py-3 px-5 font-mono text-xs text-slate-500">
                  {{ formatDate(log.created_at) }}
                </td>

                <!-- Aktor -->
                <td class="py-3 px-4">
                  <div class="text-xs font-bold text-slate-900">{{ log.user?.name || 'Sistem Internal' }}</div>
                  <div class="text-[11px] text-slate-400">{{ log.user?.rank?.name || log.user?.email || 'System Daemon' }}</div>
                </td>

                <!-- Tindakan -->
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-800">
                    {{ log.action }}
                  </span>
                </td>

                <!-- Target Entitas -->
                <td class="py-3 px-4 text-xs font-mono text-slate-600">
                  {{ log.auditable_type ? log.auditable_type + ' #' + log.auditable_id : '-' }}
                </td>

                <!-- IP Address -->
                <td class="py-3 px-4 text-xs font-mono text-slate-600">
                  {{ log.ip_address || '127.0.0.1' }}
                </td>

                <!-- Metadata Action -->
                <td class="py-3 px-5 text-right">
                  <button
                    @click="selectedLog = log"
                    class="text-xs font-bold text-blue-600 hover:text-blue-800"
                  >
                    Lihat Payload →
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

      <!-- Payload Modal -->
      <div v-if="selectedLog" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full p-6 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm font-mono">Payload Audit: {{ selectedLog.action }}</h3>
            <button @click="selectedLog = null" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>

          <pre class="bg-slate-900 text-slate-200 text-xs font-mono p-4 rounded-xl overflow-x-auto max-h-80 whitespace-pre-wrap">{{ JSON.stringify(selectedLog.details || {}, null, 2) }}</pre>

          <div class="pt-3 border-t border-slate-100 flex justify-end">
            <button @click="selectedLog = null" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
              Tutup
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
import api from '../../services/api';

const logs = ref<any[]>([]);
const total = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const loading = ref(false);

const filters = reactive({
  search: '',
  action: '',
});

const selectedLog = ref<any>(null);

let debounceTimer: any = null;
const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    loadLogs();
  }, 350);
};

const loadLogs = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/security/audit-logs', {
      params: {
        page,
        search: filters.search || undefined,
        action: filters.action || undefined,
      },
    });
    logs.value = res.data.data;
    total.value = res.data.meta.total;
    currentPage.value = res.data.meta.current_page;
    lastPage.value = res.data.meta.last_page;
  } catch (e) {
    console.error('Failed to load audit logs', e);
  } finally {
    loading.value = false;
  }
};

const changePage = (p: number) => {
  loadLogs(p);
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

onMounted(() => {
  loadLogs();
});
</script>
