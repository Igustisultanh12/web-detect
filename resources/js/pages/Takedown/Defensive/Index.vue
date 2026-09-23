<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Aksi Defensif & Mitigasi Internal</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
              {{ defensiveStore.totalActions }} Rule Terdaftar
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Manajemen perimeter pertahanan internal: Blokir Firewall, WAF, DNS Sinkhole, dan Aturan Deteksi SIEM Sigma untuk melindungi jaringan sendiri.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-500/10 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Rekomendasikan Rule Baru
          </button>
        </div>
      </div>

      <!-- Defensive Integrity Guard Banner -->
      <div class="rounded-2xl bg-blue-50/60 border border-blue-200/80 p-4.5 flex items-start gap-3.5">
        <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <div class="text-xs text-blue-900 leading-relaxed">
          <p class="font-bold text-blue-950 mb-0.5">Strictly Defensive Policy Guard (Jaminan Integritas Pertahanan)</p>
          <p class="text-blue-800">
            Seluruh aturan teknis yang dibuat secara ketat dibatasi untuk perimeter internal (Host Firewall, Gateway WAF, Corporate DNS Resolver, dan SIEM SOC). Sistem secara mutlak melarang serangan balasan, port flooding, exploit target, atau tindakan ofensif apa pun terhadap infrastruktur pihak ketiga.
          </p>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Total Rule Defensif</p>
          <p class="text-2xl font-black text-slate-900 mt-1">{{ defensiveStore.totalActions }}</p>
          <p class="text-[11px] text-slate-500 mt-1">Aturan perlindungan internal</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Aktif Diterapkan</p>
          <p class="text-2xl font-black text-emerald-600 mt-1">{{ deployedCount }}</p>
          <p class="text-[11px] text-slate-500 mt-1">Telah aktif di firewall/DNS</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Menunggu Persetujuan</p>
          <p class="text-2xl font-black text-amber-600 mt-1">{{ pendingApprovalCount }}</p>
          <p class="text-[11px] text-slate-500 mt-1">Review Admin / Super Admin</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Kepatuhan Checklist</p>
          <p class="text-2xl font-black text-blue-600 mt-1">100%</p>
          <p class="text-[11px] text-slate-500 mt-1">SOP tanggap insiden 8 langkah</p>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Search -->
          <div class="relative">
            <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              placeholder="Cari kode, domain, IP, atau nama rule..."
              class="w-full pl-10 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
            />
          </div>

          <!-- Rule Type Filter -->
          <div>
            <select
              v-model="filters.rule_type"
              @change="loadActions"
              class="w-full px-3 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
            >
              <option value="">Semua Tipe Rule</option>
              <option value="FIREWALL_RULE">FIREWALL RULE (iptables / UFW)</option>
              <option value="WAF_RULE">WAF RULE (ModSecurity / Nginx / Cloudflare)</option>
              <option value="DNS_SINKHOLE">DNS SINKHOLE (BIND RPZ / Pi-hole)</option>
              <option value="SIEM_RULE">SIEM RULE (Sigma Detection Rule)</option>
              <option value="INTERNAL_BLOCKLIST">INTERNAL BLOCKLIST (IP/Domain)</option>
              <option value="EMAIL_FILTER">EMAIL FILTER (SpamAssassin / Postfix)</option>
              <option value="PROXY_BLOCK">PROXY BLOCK (Squid ACL)</option>
              <option value="INCIDENT_CHECKLIST">SOP CHECKLIST</option>
            </select>
          </div>

          <!-- Status Filter -->
          <div>
            <select
              v-model="filters.status"
              @change="loadActions"
              class="w-full px-3 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-xs text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
            >
              <option value="">Semua Status</option>
              <option value="DRAFT">DRAFT (Penyusunan)</option>
              <option value="PENDING_REVIEW">PENDING REVIEW (Verifikasi Admin)</option>
              <option value="PENDING_APPROVAL">PENDING APPROVAL (Persetujuan Super Admin)</option>
              <option value="APPROVED">APPROVED (Disetujui)</option>
              <option value="DEPLOYED_INTERNALLY">DEPLOYED (Telah Diterapkan)</option>
              <option value="REJECTED">REJECTED (Ditolak)</option>
              <option value="REVOKED">REVOKED (Dicabut)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="defensiveStore.loading" class="flex flex-col items-center justify-center min-h-[300px]">
        <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        <p class="mt-4 text-xs font-semibold text-slate-500 uppercase tracking-widest">Memuat Aturan Defensif...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="actions.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
        <p class="text-sm font-semibold text-slate-600">Belum ada aturan defensif yang terdaftar.</p>
        <button @click="openCreateModal" class="mt-3 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition">
          Buat Rule Pertama
        </button>
      </div>

      <!-- Defensive Actions Table -->
      <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-400 font-extrabold uppercase tracking-wider text-[10px] border-b border-slate-200/80">
              <tr>
                <th class="py-3.5 px-5">Kode / Judul Rule</th>
                <th class="py-3.5 px-5">Tipe Perlindungan</th>
                <th class="py-3.5 px-5">Target Indikator (IOC)</th>
                <th class="py-3.5 px-5">Ruang Lingkup (Scope)</th>
                <th class="py-3.5 px-5">Status & Otorisasi</th>
                <th class="py-3.5 px-5 text-right">Tindakan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="act in actions" :key="act.id" class="hover:bg-slate-50/50 transition">
                <td class="py-4 px-5">
                  <div class="space-y-0.5">
                    <span class="font-mono text-xs font-bold text-slate-900">{{ act.action_code }}</span>
                    <p class="font-bold text-slate-800 text-[13px] line-clamp-1">{{ act.title }}</p>
                    <p class="text-[11px] text-slate-400">Direkomendasikan oleh {{ act.recommender?.name || 'Petugas' }}</p>
                  </div>
                </td>

                <td class="py-4 px-5">
                  <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider border" :class="getRuleTypeBadgeClass(act.rule_type)">
                    {{ formatRuleType(act.rule_type) }}
                  </span>
                </td>

                <td class="py-4 px-5">
                  <div class="font-mono text-xs text-slate-800 space-y-0.5">
                    <p class="font-bold text-blue-600 truncate max-w-[220px]" :title="act.target_value">{{ act.target_value }}</p>
                    <span class="text-[10px] text-slate-400 uppercase font-semibold">Tipe: {{ act.target_type }}</span>
                  </div>
                </td>

                <td class="py-4 px-5">
                  <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-mono font-bold">
                    {{ act.scope }}
                  </span>
                </td>

                <td class="py-4 px-5">
                  <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold" :class="getStatusBadgeClass(act.status)">
                      <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(act.status)"></span>
                      {{ formatStatusLabel(act.status) }}
                    </span>
                    <p v-if="act.approver" class="text-[10px] text-slate-400">
                      Disetujui: {{ act.approver?.name }}
                    </p>
                  </div>
                </td>

                <td class="py-4 px-5 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- View Syntax -->
                    <button
                      @click="viewRulePayload(act)"
                      class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition flex items-center gap-1"
                      title="Lihat Sintaks Konfigurasi Rule"
                    >
                      <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                      </svg>
                      Sintaks
                    </button>

                    <!-- Export File -->
                    <button
                      @click="exportRule(act)"
                      class="p-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition"
                      title="Unduh File Aturan (.conf / .yaml)"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                      </svg>
                    </button>

                    <!-- Review Button (Admin) -->
                    <button
                      v-if="authStore.isAdmin && (act.status === 'DRAFT' || act.status === 'PENDING_REVIEW')"
                      @click="reviewAction(act)"
                      class="px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold transition"
                      title="Review dan teruskan ke Super Admin"
                    >
                      Review
                    </button>

                    <!-- Approve Button (Super Admin) -->
                    <button
                      v-if="authStore.isSuperAdmin && act.status === 'PENDING_APPROVAL'"
                      @click="approveAction(act)"
                      class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm"
                      title="Beri Otorisasi / Setujui Rule"
                    >
                      Approve
                    </button>

                    <!-- Deploy Button -->
                    <button
                      v-if="authStore.isAdmin && act.status === 'APPROVED'"
                      @click="deployAction(act)"
                      class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm"
                      title="Tandai Diterapkan pada Perimeter"
                    >
                      Deploy
                    </button>

                    <!-- Reject Button -->
                    <button
                      v-if="authStore.isAdmin && (act.status === 'PENDING_REVIEW' || act.status === 'PENDING_APPROVAL')"
                      @click="rejectAction(act)"
                      class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition"
                      title="Tolak Rule"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="defensiveStore.lastPage > 1" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <p class="text-xs text-slate-500">
            Halaman {{ defensiveStore.currentPage }} dari {{ defensiveStore.lastPage }}
          </p>
          <div class="flex items-center gap-2">
            <button
              :disabled="defensiveStore.currentPage <= 1"
              @click="changePage(defensiveStore.currentPage - 1)"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 disabled:opacity-40"
            >
              Sebelumnya
            </button>
            <button
              :disabled="defensiveStore.currentPage >= defensiveStore.lastPage"
              @click="changePage(defensiveStore.currentPage + 1)"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 disabled:opacity-40"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 1: Rekomendasikan Rule Defensif Baru -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900">Rekomendasikan Rule Defensif Internal</h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitCreate" class="space-y-3.5">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Judul / Deskripsi Kebijakan <span class="text-rose-500">*</span></label>
            <input
              v-model="createForm.title"
              required
              type="text"
              placeholder="Contoh: Blokir IP Host Server Phishing Perbankan"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Rule Defensif <span class="text-rose-500">*</span></label>
              <select
                v-model="createForm.rule_type"
                required
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
              >
                <option value="FIREWALL_RULE">FIREWALL (iptables/UFW)</option>
                <option value="WAF_RULE">WAF (ModSecurity/Cloudflare/Nginx)</option>
                <option value="DNS_SINKHOLE">DNS SINKHOLE (BIND RPZ/Pi-hole)</option>
                <option value="SIEM_RULE">SIEM RULE (Sigma Detection)</option>
                <option value="INTERNAL_BLOCKLIST">INTERNAL BLOCKLIST</option>
                <option value="EMAIL_FILTER">EMAIL FILTER (SpamAssassin/Postfix)</option>
                <option value="PROXY_BLOCK">PROXY BLOCK (Squid ACL)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Target (IOC) <span class="text-rose-500">*</span></label>
              <select
                v-model="createForm.target_type"
                required
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
              >
                <option value="DOMAIN">DOMAIN</option>
                <option value="IP">IP ADDRESS</option>
                <option value="SUBNET">SUBNET (CIDR)</option>
                <option value="URL">URL SPESIFIK</option>
                <option value="HASH">HASH FILE (SHA-256)</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nilai Target (Domain / IP / Subnet) <span class="text-rose-500">*</span></label>
            <input
              v-model="createForm.target_value"
              required
              type="text"
              placeholder="Contoh: 198.51.100.45 atau malicious-domain.com"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Ruang Lingkup (Scope Perlindungan)</label>
            <input
              v-model="createForm.scope"
              type="text"
              placeholder="Contoh: INTERNAL_NETWORK atau CORPORATE_FIREWALL"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan / Justifikasi</label>
            <textarea
              v-model="createForm.description"
              rows="3"
              placeholder="Jelaskan alasan pemblokiran internal dan referensi temuan..."
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white shadow-sm shadow-blue-500/10 disabled:opacity-50"
            >
              {{ saving ? 'Menyimpan...' : 'Simpan & Susun Rule' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: Syntax / Rule Payload Viewer -->
    <div v-if="selectedAction" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between">
          <div class="space-y-0.5">
            <span class="font-mono text-xs font-bold text-blue-600">{{ selectedAction.action_code }}</span>
            <h3 class="text-base font-bold text-slate-900">{{ selectedAction.title }}</h3>
          </div>
          <button @click="selectedAction = null" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Details Info Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs bg-slate-50 p-3 rounded-xl border border-slate-200/60">
          <div>
            <span class="text-slate-400 text-[10px] uppercase font-bold block">Tipe Rule:</span>
            <span class="font-bold text-slate-800">{{ selectedAction.rule_type }}</span>
          </div>
          <div>
            <span class="text-slate-400 text-[10px] uppercase font-bold block">Target IOC:</span>
            <span class="font-mono font-bold text-blue-600 truncate block">{{ selectedAction.target_value }}</span>
          </div>
          <div>
            <span class="text-slate-400 text-[10px] uppercase font-bold block">Scope:</span>
            <span class="font-mono text-slate-700">{{ selectedAction.scope }}</span>
          </div>
          <div>
            <span class="text-slate-400 text-[10px] uppercase font-bold block">Status:</span>
            <span class="font-bold text-slate-800">{{ selectedAction.status }}</span>
          </div>
        </div>

        <!-- Syntax Payload Editor/Viewer -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-bold text-slate-700">Payload Konfigurasi Teknis Defensif</label>
            <div class="flex items-center gap-2">
              <button
                @click="copyPayload(selectedAction.rule_payload || '')"
                class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-1"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Salin Sintaks
              </button>
              <button
                @click="exportRule(selectedAction)"
                class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1 shadow-sm"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Unduh Berkas
              </button>
            </div>
          </div>
          <pre class="w-full p-4 rounded-xl bg-slate-900 text-slate-100 font-mono text-xs overflow-x-auto leading-relaxed max-h-64 select-all">{{ selectedAction.rule_payload || '# Belum ada payload konfigurasi yang digenerate' }}</pre>
        </div>

        <!-- Checklist Insiden Terkait -->
        <div v-if="selectedAction.checklists && selectedAction.checklists.length" class="space-y-2 pt-2">
          <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Checklist Langkah Verifikasi</h4>
          <div class="space-y-2">
            <div
              v-for="item in selectedAction.checklists"
              :key="item.id"
              class="flex items-start gap-2.5 p-2.5 rounded-lg border bg-slate-50 text-xs"
            >
              <input
                type="checkbox"
                :checked="item.is_completed"
                @change="toggleChecklistItem(item)"
                class="mt-0.5 w-4 h-4 rounded text-blue-600 border-slate-300 cursor-pointer"
              />
              <div class="flex-1">
                <p class="font-bold text-slate-800" :class="{ 'line-through text-slate-400': item.is_completed }">
                  {{ item.task_name }}
                </p>
                <p class="text-slate-500 text-[11px]">{{ item.instructions }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end pt-3 border-t border-slate-100">
          <button
            @click="selectedAction = null"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 transition"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Swal from 'sweetalert2';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { useDefensiveStore } from '@/stores/defensive';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import type { DefensiveAction, IncidentChecklist } from '@/types';

const defensiveStore = useDefensiveStore();
const authStore = useAuthStore();

const saving = ref(false);
const showCreateModal = ref(false);
const selectedAction = ref<DefensiveAction | null>(null);

const filters = ref({
  search: '',
  rule_type: '',
  status: '',
});

const createForm = ref({
  title: '',
  rule_type: 'FIREWALL_RULE',
  target_type: 'IP',
  target_value: '',
  scope: 'INTERNAL_NETWORK',
  description: '',
});

const actions = computed(() => defensiveStore.actions || []);

const deployedCount = computed(() => actions.value.filter(a => a.status === 'DEPLOYED_INTERNALLY').length);
const pendingApprovalCount = computed(() => actions.value.filter(a => a.status === 'PENDING_APPROVAL' || a.status === 'PENDING_REVIEW').length);

let searchTimeout: any = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadActions();
  }, 400);
};

const loadActions = async (page = 1) => {
  await defensiveStore.fetchActions({
    page,
    search: filters.value.search || undefined,
    rule_type: filters.value.rule_type || undefined,
    status: filters.value.status || undefined,
  });
};

const changePage = (page: number) => {
  loadActions(page);
};

const openCreateModal = () => {
  createForm.value = {
    title: '',
    rule_type: 'FIREWALL_RULE',
    target_type: 'IP',
    target_value: '',
    scope: 'INTERNAL_NETWORK',
    description: '',
  };
  showCreateModal.value = true;
};

const submitCreate = async () => {
  saving.value = true;
  try {
    await defensiveStore.createAction(createForm.value);
    showCreateModal.value = false;
    Swal.fire({
      icon: 'success',
      title: 'Rule Defensif Disusun',
      text: 'Aturan pertahanan internal berhasil dibuat dan masuk antrean review.',
      timer: 1500,
      showConfirmButton: false,
    });
    await loadActions();
  } catch (err: any) {
    Swal.fire('Gagal Menyimpan', err.response?.data?.message || 'Gagal menyusun rule defensif.', 'error');
  } finally {
    saving.value = false;
  }
};

const viewRulePayload = async (act: DefensiveAction) => {
  try {
    const detail = await defensiveStore.fetchActionDetail(act.id);
    selectedAction.value = detail;
  } catch (err) {
    selectedAction.value = act;
  }
};

const reviewAction = async (act: DefensiveAction) => {
  const confirm = await Swal.fire({
    title: 'Review Aturan Defensif?',
    text: `Teruskan aturan ${act.action_code} ke Super Admin untuk persetujuan final deployment?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Teruskan',
    cancelButtonText: 'Batal',
  });

  if (confirm.isConfirmed) {
    try {
      await defensiveStore.reviewAction(act.id);
      Swal.fire('Berhasil Ditinjau', 'Status aturan kini PENDING_APPROVAL.', 'success');
      await loadActions(defensiveStore.currentPage);
    } catch (err: any) {
      Swal.fire('Gagal Review', err.response?.data?.message || 'Terjadi kesalahan.', 'error');
    }
  }
};

const approveAction = async (act: DefensiveAction) => {
  const confirm = await Swal.fire({
    title: 'Beri Otorisasi Persetujuan?',
    text: `Anda memberikan otorisasi Super Admin untuk menerapkan aturan ${act.action_code} pada perimeter internal.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Setujui (Approve)',
    cancelButtonText: 'Batal',
  });

  if (confirm.isConfirmed) {
    try {
      await defensiveStore.approveAction(act.id);
      Swal.fire('Aturan Disetujui', 'Aturan siap diterapkan (APPROVED).', 'success');
      await loadActions(defensiveStore.currentPage);
    } catch (err: any) {
      Swal.fire('Gagal Otorisasi', err.response?.data?.message || 'Terjadi kesalahan.', 'error');
    }
  }
};

const deployAction = async (act: DefensiveAction) => {
  const confirm = await Swal.fire({
    title: 'Tandai Diterapkan (Deploy)?',
    text: `Konfirmasi bahwa aturan ${act.action_code} telah aktif dimuat ke firewall / DNS / gateway internal?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Tandai Aktif',
    cancelButtonText: 'Batal',
  });

  if (confirm.isConfirmed) {
    try {
      await defensiveStore.deployAction(act.id);
      Swal.fire('Berhasil Diterapkan', 'Aturan berstatus DEPLOYED_INTERNALLY.', 'success');
      await loadActions(defensiveStore.currentPage);
    } catch (err: any) {
      Swal.fire('Gagal Deploy', err.response?.data?.message || 'Terjadi kesalahan.', 'error');
    }
  }
};

const rejectAction = async (act: DefensiveAction) => {
  const { value: reason } = await Swal.fire({
    title: 'Tolak Aturan Defensif',
    input: 'textarea',
    inputLabel: 'Alasan Penolakan',
    inputPlaceholder: 'Tuliskan alasan penolakan rule ini...',
    showCancelButton: true,
    confirmButtonText: 'Tolak Rule',
    cancelButtonText: 'Batal',
    inputValidator: (value) => {
      if (!value) return 'Alasan penolakan wajib diisi!';
    }
  });

  if (reason) {
    try {
      await defensiveStore.rejectAction(act.id, reason);
      Swal.fire('Aturan Ditolak', 'Status aturan diperbarui menjadi REJECTED.', 'info');
      await loadActions(defensiveStore.currentPage);
    } catch (err: any) {
      Swal.fire('Gagal Menolak', err.response?.data?.message || 'Terjadi kesalahan.', 'error');
    }
  }
};

const exportRule = async (act: DefensiveAction) => {
  try {
    const res = await api.get(`/defensive-actions/${act.id}/export`, { responseType: 'blob' });
    const ext = act.rule_type === 'SIEM_RULE' ? 'yaml' : (act.rule_type === 'DNS_SINKHOLE' ? 'zone' : 'conf');
    const filename = `${act.action_code}.${ext}`;
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err: any) {
    Swal.fire('Gagal Ekspor', 'Gagal mengunduh file aturan defensif.', 'error');
  }
};

const toggleChecklistItem = async (item: IncidentChecklist) => {
  if (!selectedAction.value) return;
  try {
    await defensiveStore.toggleChecklist(selectedAction.value.id, item.id);
  } catch (err) {
    item.is_completed = !item.is_completed;
  }
};

const copyPayload = (text: string) => {
  navigator.clipboard.writeText(text);
  Swal.fire({
    icon: 'success',
    title: 'Disalin!',
    text: 'Sintaks rule berhasil disalin ke papan klip.',
    timer: 1200,
    showConfirmButton: false,
  });
};

const formatRuleType = (type: string) => {
  const map: Record<string, string> = {
    FIREWALL_RULE: 'Firewall Rule',
    WAF_RULE: 'WAF Rule',
    DNS_SINKHOLE: 'DNS Sinkhole',
    SIEM_RULE: 'SIEM Sigma Rule',
    INTERNAL_BLOCKLIST: 'Internal Blocklist',
    EMAIL_FILTER: 'Email Filter',
    PROXY_BLOCK: 'Proxy Block',
    INCIDENT_CHECKLIST: 'SOP Checklist',
  };
  return map[type] || type;
};

const getRuleTypeBadgeClass = (type: string) => {
  switch (type) {
    case 'FIREWALL_RULE': return 'bg-amber-50 text-amber-800 border-amber-200';
    case 'WAF_RULE': return 'bg-purple-50 text-purple-800 border-purple-200';
    case 'DNS_SINKHOLE': return 'bg-blue-50 text-blue-800 border-blue-200';
    case 'SIEM_RULE': return 'bg-indigo-50 text-indigo-800 border-indigo-200';
    case 'EMAIL_FILTER': return 'bg-cyan-50 text-cyan-800 border-cyan-200';
    default: return 'bg-slate-100 text-slate-800 border-slate-200';
  }
};

const formatStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    DRAFT: 'Draft',
    PENDING_REVIEW: 'Pending Review',
    PENDING_APPROVAL: 'Pending Approval',
    APPROVED: 'Approved',
    DEPLOYED_INTERNALLY: 'Deployed',
    REJECTED: 'Rejected',
    REVOKED: 'Revoked',
  };
  return map[status] || status;
};

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'DEPLOYED_INTERNALLY': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    case 'APPROVED': return 'bg-blue-50 text-blue-700 border border-blue-200';
    case 'PENDING_APPROVAL':
    case 'PENDING_REVIEW': return 'bg-amber-50 text-amber-700 border border-amber-200';
    case 'REJECTED': return 'bg-rose-50 text-rose-700 border border-rose-200';
    default: return 'bg-slate-100 text-slate-600 border border-slate-200';
  }
};

const getStatusDotClass = (status: string) => {
  switch (status) {
    case 'DEPLOYED_INTERNALLY': return 'bg-emerald-500';
    case 'APPROVED': return 'bg-blue-500';
    case 'PENDING_APPROVAL':
    case 'PENDING_REVIEW': return 'bg-amber-500';
    case 'REJECTED': return 'bg-rose-500';
    default: return 'bg-slate-400';
  }
};

onMounted(() => {
  loadActions();
});
</script>
