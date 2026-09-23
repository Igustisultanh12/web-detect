<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Daftar Investigasi Website</h2>
          <p class="text-xs text-slate-500 mt-1">Seluruh berkas investigasi pasif, metadata teknis, dan catatan barang bukti digital.</p>
        </div>
        <router-link
          to="/investigations/create"
          class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider transition shadow-md shadow-blue-500/20 shrink-0 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
          Investigasi Baru
        </router-link>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="relative w-full md:w-80">
          <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Cari domain, kode, atau URL..."
            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="flex flex-wrap gap-2 w-full md:w-auto">
          <select v-model="filters.status" @change="loadData" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Status</option>
            <option value="COMPLETED">Selesai (COMPLETED)</option>
            <option value="ANALYZING">Sedang Dianalisis</option>
            <option value="QUEUED">Antrean (QUEUED)</option>
            <option value="FAILED">Gagal (FAILED)</option>
            <option value="PARTIAL_RESULT">Sebagian (PARTIAL)</option>
          </select>

          <select v-model="filters.category" @change="loadData" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Kategori</option>
            <option value="Phishing">Phishing</option>
            <option value="Malware">Malware</option>
            <option value="Fraud">Fraud / Penipuan</option>
            <option value="Illegal Content">Konten Ilegal</option>
            <option value="Copyright">Hak Cipta</option>
            <option value="Spam">Spam</option>
            <option value="Suspicious Domain">Suspicious Domain</option>
            <option value="Other">Lainnya</option>
          </select>
        </div>
      </div>

      <!-- Investigations Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/80 text-slate-400 font-extrabold uppercase tracking-wider border-b border-slate-200/80 text-[10px]">
              <tr>
                <th class="px-6 py-4">Nomor & Domain Target</th>
                <th class="px-4 py-4">Kategori & Prioritas</th>
                <th class="px-4 py-4">Status Analisis</th>
                <th class="px-4 py-4">Investigator</th>
                <th class="px-4 py-4">Waktu Dibuat</th>
                <th class="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="investigationStore.loading">
                <td colspan="6" class="px-6 py-12 text-center text-slate-400">Memuat data investigasi...</td>
              </tr>
              <tr v-else-if="!investigationStore.investigations?.length">
                <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Tidak ada investigasi yang cocok dengan kriteria pencarian.</td>
              </tr>
              <tr
                v-for="inv in investigationStore.investigations"
                :key="inv.id"
                class="hover:bg-slate-50/60 transition group"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs select-none">
                      {{ inv.target_domain.substring(0, 2).toUpperCase() }}
                    </div>
                    <div>
                      <router-link :to="`/investigations/${inv.id}`" class="font-bold text-sm text-slate-800 hover:text-blue-600 block">
                        {{ inv.target_domain }}
                      </router-link>
                      <span class="font-mono text-[11px] text-slate-400">{{ inv.investigation_code }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                    {{ inv.category }}
                  </span>
                  <span v-if="inv.priority === 'CRITICAL'" class="ml-1 text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">KRITIS</span>
                </td>
                <td class="px-4 py-4">
                  <span :class="getStatusBadgeClass(inv.status)" class="px-2.5 py-1 rounded-full text-[10px] font-extrabold tracking-wide uppercase">
                    {{ inv.status }}
                  </span>
                </td>
                <td class="px-4 py-4 text-slate-600 font-medium">
                  {{ inv.user?.name || '-' }}
                  <span v-if="inv.user?.rank" class="block text-[10px] text-slate-400">{{ inv.user.rank?.name }}</span>
                </td>
                <td class="px-4 py-4 text-slate-500 text-[11px]">
                  {{ formatDate(inv.created_at) }}
                </td>
                <td class="px-6 py-4 text-right">
                  <router-link
                    :to="`/investigations/${inv.id}`"
                    class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-600 font-bold text-[11px] transition inline-flex items-center gap-1"
                  >
                    Buka Rincian &rarr;
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span>Menampilkan {{ investigationStore.investigations.length }} dari {{ investigationStore.total }} data</span>
          <div class="flex gap-2">
            <button
              :disabled="investigationStore.currentPage <= 1"
              @click="changePage(investigationStore.currentPage - 1)"
              class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-40 cursor-pointer"
            >
              Sebelumnya
            </button>
            <button
              :disabled="investigationStore.currentPage >= investigationStore.lastPage"
              @click="changePage(investigationStore.currentPage + 1)"
              class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-40 cursor-pointer"
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
import { reactive, onMounted } from 'vue';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { useInvestigationStore } from '../../stores/investigation';

const investigationStore = useInvestigationStore();

const filters = reactive({
  search: '',
  status: '',
  category: '',
  page: 1,
});

let searchTimeout: any = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.page = 1;
    loadData();
  }, 350);
};

const loadData = () => {
  investigationStore.fetchInvestigations({
    page: filters.page,
    search: filters.search || undefined,
    status: filters.status || undefined,
    category: filters.category || undefined,
  });
};

const changePage = (p: number) => {
  filters.page = p;
  loadData();
};

const formatDate = (d: string) => {
  if (!d) return '-';
  const date = new Date(d);
  return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'COMPLETED':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    case 'ANALYZING':
    case 'QUEUED':
      return 'bg-amber-50 text-amber-700 border border-amber-200 animate-pulse';
    case 'FAILED':
      return 'bg-rose-50 text-rose-700 border border-rose-200';
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200';
  }
};

onMounted(() => {
  loadData();
});
</script>
