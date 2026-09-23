<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard Takedown & Incident Response</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
              Operasi Penindakan Siber
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Pemantauan proses pelaporan, respons penyedia layanan, pemantauan batas waktu (SLA), dan perlindungan defensif internal.
          </p>
        </div>

        <div class="flex items-center gap-2">
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

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Cases -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Kasus Takedown</span>
            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-black text-slate-900">{{ metrics?.total_cases || 0 }} Kasus</div>
          <div class="text-xs text-slate-500">Dari {{ metrics?.total_investigations || 0 }} investigasi pasif</div>
        </div>

        <!-- Card 2: Action Taken (Resolved) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Takedown Berhasil</span>
            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-black text-emerald-600">{{ metrics?.status_distribution?.action_taken || 0 }}</div>
          <div class="text-xs text-emerald-700">Layanan / domain ilegal telah ditangguhkan</div>
        </div>

        <!-- Card 3: In Progress (Submitted & Under Review) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Sedang Ditinjau Provider</span>
            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-black text-slate-900">
            {{ (metrics?.status_distribution?.submitted || 0) + (metrics?.status_distribution?.under_review || 0) }}
          </div>
          <div class="text-xs text-slate-500">Tiket aktif dalam antrean review provider</div>
        </div>

        <!-- Card 4: Overdue Follow-ups -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Follow-up Jatuh Tempo</span>
            <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-black text-rose-600">{{ metrics?.overdue_count || 0 }} Kasus</div>
          <div class="text-xs text-rose-600 font-semibold">Melebihi estimasi batas waktu respons (SLA)</div>
        </div>
      </div>

      <!-- Two-Column Layout: Overdue Cases & Provider Stats -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Column 1: Overdue Cases Alert Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
          <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                Tindak Lanjut Mendekati / Lewat SLA
              </h3>
              <p class="text-xs text-slate-500">Perlu eskalasi atau konfirmasi ulang ke penyedia layanan</p>
            </div>
            <router-link to="/takedown/cases?overdue_only=true" class="text-xs font-bold text-blue-600 hover:text-blue-700">
              Lihat Semua →
            </router-link>
          </div>

          <div class="overflow-x-auto flex-1">
            <div v-if="!metrics?.overdue_cases || metrics.overdue_cases.length === 0" class="p-8 text-center text-xs text-slate-400">
              Semua tindak lanjut berjalan sesuai target SLA provider.
            </div>
            <table v-else class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/70 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                  <th class="py-3 px-4">Kasus</th>
                  <th class="py-3 px-3">Domain Target</th>
                  <th class="py-3 px-3">Provider</th>
                  <th class="py-3 px-3">Jatuh Tempo</th>
                  <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="c in metrics.overdue_cases" :key="c.id" class="hover:bg-slate-50/50">
                  <td class="py-2.5 px-4 font-mono font-bold text-blue-600">{{ c.case_number }}</td>
                  <td class="py-2.5 px-3 font-semibold text-slate-800">{{ c.target_domain }}</td>
                  <td class="py-2.5 px-3 text-slate-600">{{ c.provider_name || 'Umum' }}</td>
                  <td class="py-2.5 px-3 text-rose-600 font-bold font-mono">{{ formatDate(c.next_follow_up_at) }}</td>
                  <td class="py-2.5 px-4 text-right">
                    <router-link :to="`/takedown/cases/${c.uuid}`" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                      Follow-up →
                    </router-link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Column 2: Provider Performance & Distribution -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Distribusi Provider Abuse Terdaftar</h3>
              <p class="text-xs text-slate-500">Penyedia layanan penerima permohonan penanganan kasus terbanyak</p>
            </div>
            <router-link to="/takedown/providers" class="text-xs font-bold text-blue-600 hover:text-blue-700">
              Katalog Provider →
            </router-link>
          </div>

          <div class="space-y-3">
            <div
              v-for="(prov, idx) in metrics?.provider_stats"
              :key="idx"
              class="flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/50 text-xs"
            >
              <div>
                <div class="font-bold text-slate-900">{{ prov.name }}</div>
                <div class="text-slate-500 text-[11px]">{{ prov.type }} • Standar SLA: {{ prov.sla_hours }} Jam</div>
              </div>
              <div class="text-right">
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs border border-blue-200/60">
                  {{ prov.cases_count }} Kasus
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Defensive Actions Overview Banner -->
      <div class="bg-gradient-to-r from-blue-900 to-indigo-950 rounded-2xl p-6 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-5">
        <div class="space-y-1">
          <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/10 text-blue-200 border border-white/20 uppercase tracking-wider">
            Pertahanan Perimeter Internal
          </div>
          <h3 class="text-lg font-bold">Aksi Defensif & Aturan Blokir Internal</h3>
          <p class="text-xs text-blue-200 max-w-xl">
            Terapkan blocklist internal, sinkhole DNS, aturan WAF/Firewall dinas, serta checklist respon insiden untuk melindungi infrastruktur institusi Anda.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <router-link
            to="/takedown/defensive-actions"
            class="px-4 py-2.5 rounded-xl bg-white text-blue-900 hover:bg-slate-100 text-xs font-bold transition shadow-sm"
          >
            Buka Modul Aksi Defensif →
          </router-link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { useTakedownStore } from '../../stores/takedown';

const takedownStore = useTakedownStore();
const metrics = ref<any>(null);

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

onMounted(async () => {
  metrics.value = await takedownStore.fetchDashboard();
});
</script>
