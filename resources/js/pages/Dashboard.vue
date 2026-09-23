<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Welcome & Quick Action Card -->
      <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
          <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-bold uppercase tracking-wider mb-4 border border-blue-400/30">
            <span class="w-2 h-2 rounded-full bg-blue-400 animate-ping"></span>
            Sistem Siaga Operasi Pasif
          </span>
          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mb-2">
            Selamat Datang, {{ authStore.user?.name }}
          </h1>
          <p class="text-slate-300 text-sm leading-relaxed mb-6">
            Platform intelijen pasif WebGuard mengumpulkan metadata DNS, RDAP, IP Intelligence, SSL/TLS, dan status reputasi publik secara aman tanpa koneksi langsung dari browser Anda ke website target.
          </p>
          <div class="flex flex-wrap gap-3">
            <router-link
              to="/investigations/create"
              class="px-5 py-3 rounded-xl bg-[#2563EB] hover:bg-blue-600 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-blue-500/30 flex items-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
              Mulai Investigasi Baru
            </router-link>
            <router-link
              to="/investigations"
              class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs uppercase tracking-wider transition border border-white/20 flex items-center gap-2 cursor-pointer"
            >
              Lihat Semua Kasus
            </router-link>
          </div>
        </div>
        <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
          <svg class="w-96 h-96 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
        </div>
      </div>

      <!-- KPI Summary Cards Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
          <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Total Investigasi</p>
          <p class="text-2xl font-black text-slate-900 mt-2">{{ stats.summary?.total_investigations || 0 }}</p>
          <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-blue-600">
            <span>{{ stats.summary?.completed || 0 }} selesai</span>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
          <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Kasus Berjalan</p>
          <p class="text-2xl font-black text-amber-600 mt-2">{{ stats.summary?.running || 0 }}</p>
          <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-slate-500">
            <span>Antrean Worker</span>
          </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
          <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Temuan Mencurigakan</p>
          <p class="text-2xl font-black text-rose-600 mt-2">{{ stats.summary?.suspicious || 0 }}</p>
          <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-rose-600">
            <span>Indikasi Malicious / Phish</span>
          </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
          <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Barang Bukti & Laporan</p>
          <p class="text-2xl font-black text-emerald-600 mt-2">{{ stats.summary?.total_evidence || 0 }}</p>
          <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-emerald-600">
            <span>{{ stats.summary?.total_reports || 0 }} Dokumen PDF</span>
          </div>
        </div>
      </div>

      <!-- Content Split: Recent Investigations & Analytics Breakdown -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Investigations (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <h3 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider">
              Investigasi Terbaru
            </h3>
            <router-link to="/investigations" class="text-xs font-bold text-[#2563EB] hover:underline">
              Lihat Semua &rarr;
            </router-link>
          </div>

          <div v-if="loading" class="py-12 text-center text-xs text-slate-400">
            Memuat ringkasan data...
          </div>

          <div v-else-if="!stats.recent_investigations?.length" class="py-12 text-center text-xs text-slate-400 italic">
            Belum ada investigasi yang dilakukan. Klik 'Mulai Investigasi Baru'.
          </div>

          <div v-else class="divide-y divide-slate-100">
            <div
              v-for="inv in stats.recent_investigations"
              :key="inv.id"
              class="py-3.5 flex items-center justify-between hover:bg-slate-50/50 rounded-xl px-2 transition group"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0 select-none">
                  {{ inv.target_domain.substring(0, 2).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <router-link :to="`/investigations/${inv.id}`" class="font-bold text-sm text-slate-800 hover:text-blue-600 truncate block">
                    {{ inv.target_domain }}
                  </router-link>
                  <p class="text-[11px] text-slate-400 truncate">
                    {{ inv.investigation_code }} &bull; {{ inv.category }} &bull; {{ inv.user?.name }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-3 shrink-0">
                <span :class="getStatusBadgeClass(inv.status)" class="px-2.5 py-1 rounded-full text-[10px] font-extrabold tracking-wide uppercase">
                  {{ inv.status }}
                </span>
                <router-link :to="`/investigations/${inv.id}`" class="p-1.5 hover:bg-slate-100 text-slate-400 hover:text-slate-700 rounded-lg transition">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Breakdown Widgets (1 col) -->
        <div class="space-y-6">
          <!-- Categories Card -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h3 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-4 pb-3 border-b border-slate-100">
              Kategori Dugaan
            </h3>
            <div class="space-y-2.5">
              <div v-for="cat in stats.categories" :key="cat.category" class="flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-600">{{ cat.category }}</span>
                <span class="px-2 py-0.5 rounded-full bg-slate-100 font-bold text-slate-700 text-[11px]">
                  {{ cat.total }}
                </span>
              </div>
            </div>
          </div>

          <!-- Top Hosting Countries Card -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h3 class="font-extrabold text-slate-900 text-sm uppercase tracking-wider mb-4 pb-3 border-b border-slate-100">
              Negara Hosting Server
            </h3>
            <div class="space-y-2.5">
              <div v-for="c in stats.top_countries" :key="c.country" class="flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-600">{{ c.country }}</span>
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold text-[11px]">
                  {{ c.total }} server
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';

const authStore = useAuthStore();
const stats = ref<any>({});
const loading = ref(true);

const fetchStats = async () => {
  try {
    const response = await api.get('/dashboard/stats');
    stats.value = response.data;
  } catch (err) {
    console.error('Failed to load dashboard metrics', err);
  } finally {
    loading.value = false;
  }
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
  fetchStats();
});
</script>
