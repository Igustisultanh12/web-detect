<template>
  <AuthenticatedLayout>
    <div v-if="loading && !cCase" class="flex flex-col items-center justify-center min-h-[400px]">
      <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-xs font-semibold text-slate-500 uppercase tracking-widest">Memuat Berkas Kasus Takedown...</p>
    </div>

    <div v-else-if="!cCase" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center max-w-lg mx-auto my-12">
      <div class="w-16 h-16 rounded-full bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h3 class="text-base font-bold text-slate-900">Kasus Tidak Ditemukan</h3>
      <p class="text-xs text-slate-500 mt-1 mb-6">Berkas kasus tidak tersedia atau telah dihapus dari sistem.</p>
      <router-link to="/takedown/cases" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition">
        Kembali ke Daftar Kasus
      </router-link>
    </div>

    <div v-else class="space-y-6">
      <!-- Breadcrumb & Top Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
          <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <router-link to="/takedown/dashboard" class="hover:text-blue-600">Takedown</router-link>
            <span>/</span>
            <router-link to="/takedown/cases" class="hover:text-blue-600">Kasus</router-link>
            <span>/</span>
            <span class="text-slate-800 font-bold font-mono">{{ cCase.case_number }}</span>
          </div>
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-2xl font-black tracking-tight text-slate-900 font-mono">{{ cCase.case_number }}</h1>
            <span :class="getPriorityClass(cCase.priority)" class="px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wider uppercase border">
              {{ cCase.priority }}
            </span>
            <span :class="getStatusBadgeClass(cCase.status)" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold">
              <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(cCase.status)"></span>
              {{ getStatusLabel(cCase.status) }}
            </span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap">
          <button
            @click="openStatusModal"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-sm"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Ubah Status
          </button>

          <button
            @click="openFollowUpModal"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm shadow-blue-500/10"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Tindak Lanjut
          </button>

          <button
            @click="downloadReport"
            :disabled="downloadingReport"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-sm disabled:opacity-50"
            title="Unduh Laporan Resmi Takedown format PDF"
          >
            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            {{ downloadingReport ? 'Mengunduh...' : 'Laporan PDF' }}
          </button>

          <button
            @click="downloadEvidencePackage"
            :disabled="downloadingPackage"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-sm disabled:opacity-50"
            title="Unduh Berkas Bukti Digital Utuh (ZIP + SHA-256 Manifest)"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            {{ downloadingPackage ? 'Mengemas...' : 'Evidence Package (ZIP)' }}
          </button>
        </div>
      </div>

      <!-- Overdue / SLA Alert Banner -->
      <div v-if="isCaseOverdue" class="rounded-2xl bg-rose-50 border border-rose-200 p-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <h4 class="text-sm font-bold text-rose-900">Peringatan SLA Terlampaui (Overdue)</h4>
            <p class="text-xs text-rose-700">
              Jadwal tindak lanjut terakhir ({{ formatDate(cCase.next_follow_up_at) }}) telah melewati batas toleransi respons provider. Segera eskalasi atau hubungi provider kembali.
            </p>
          </div>
        </div>
        <button
          @click="openFollowUpModal"
          class="shrink-0 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition"
        >
          Eskalasi Sekarang
        </button>
      </div>

      <!-- Main Overview Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <!-- Target Info -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm space-y-3">
          <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Target Investigasi</p>
          <div>
            <p class="text-base font-bold text-slate-900 break-all">{{ cCase.target_domain }}</p>
            <p class="text-xs text-slate-500 truncate mt-0.5" :title="cCase.target_url">{{ cCase.target_url }}</p>
          </div>
          <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-400">Kategori:</span>
            <span class="font-semibold text-slate-800">{{ cCase.category }}</span>
          </div>
          <div v-if="cCase.target_ip" class="flex items-center justify-between text-xs">
            <span class="text-slate-400">IP Host:</span>
            <span class="font-mono font-medium text-slate-700">{{ cCase.target_ip }}</span>
          </div>
        </div>

        <!-- Provider Info -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm space-y-3">
          <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Penyedia Layanan / Otoritas</p>
          <div>
            <p class="text-base font-bold text-slate-900">{{ cCase.provider?.name || cCase.provider_name || 'Tidak Ditentukan' }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ cCase.provider?.type || cCase.provider_type || 'GENERAL' }}</p>
          </div>
          <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-400">Kontak Abuse:</span>
            <span class="text-slate-800 font-mono text-[11px] truncate max-w-[140px]" :title="cCase.provider?.abuse_email || cCase.provider_contact">
              {{ cCase.provider?.abuse_email || cCase.provider_contact || '-' }}
            </span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-400">Target SLA:</span>
            <span class="font-bold text-blue-600">{{ cCase.provider?.sla_hours || 48 }} Jam</span>
          </div>
        </div>

        <!-- Ticket & Reference -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm space-y-3">
          <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">No. Tiket / Referensi Eksternal</p>
          <div>
            <p class="text-base font-bold font-mono text-slate-900">{{ cCase.external_reference_number || 'Belum Ada Tiket' }}</p>
            <p class="text-xs text-slate-500 mt-0.5">ID Laporan Provider</p>
          </div>
          <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-400">Terkirim Pada:</span>
            <span class="font-medium text-slate-700">{{ formatDate(cCase.submitted_at) }}</span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-400">Tindak Lanjut Berikut:</span>
            <span :class="isCaseOverdue ? 'text-rose-600 font-bold' : 'text-slate-700 font-medium'">
              {{ formatDate(cCase.next_follow_up_at) }}
            </span>
          </div>
        </div>

        <!-- Personnel & Assignment -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4.5 shadow-sm space-y-3">
          <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Petugas Penanggung Jawab</p>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 font-bold flex items-center justify-center border border-blue-100 shrink-0">
              {{ (cCase.assigned_officer?.name || cCase.creator?.name || 'WG').substring(0, 2).toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-bold text-slate-900 truncate">
                {{ cCase.assigned_officer?.name || cCase.creator?.name || 'Admin Investigasi' }}
              </p>
              <p class="text-[11px] text-slate-500 truncate">
                {{ cCase.assigned_officer?.rank?.name || 'Petugas Intelijen' }}
              </p>
            </div>
          </div>
          <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-400">Dibuat Oleh:</span>
            <span class="font-medium text-slate-700 truncate max-w-[130px]">{{ cCase.creator?.name || 'System' }}</span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-400">Tanggal Buat:</span>
            <span class="text-slate-700">{{ formatDate(cCase.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Detail Tabs Navigation -->
      <div class="border-b border-slate-200">
        <nav class="flex gap-6 -mb-px">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-blue-600 text-blue-600 font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-700 font-medium hover:border-slate-300',
              'py-3 px-1 border-b-2 text-xs uppercase tracking-wider flex items-center gap-2 transition'
            ]"
          >
            <span>{{ tab.name }}</span>
            <span v-if="tab.badge !== undefined" class="px-2 py-0.5 rounded-full text-[10px] font-bold"
              :class="activeTab === tab.id ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">
              {{ tab.badge }}
            </span>
          </button>
        </nav>
      </div>

      <!-- TAB 1: Rincian & Dasar Hukum -->
      <div v-show="activeTab === 'details'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 space-y-6">
            <!-- Ringkasan Dugaan Pelanggaran -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
              <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Ringkasan Dugaan Pelanggaran</h3>
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                {{ cCase.allegation_summary }}
              </div>
            </div>

            <!-- Dasar Hukum / Ketentuan Kebijakan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
              <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Dasar Hukum & Ketentuan Layanan (ToS)</h3>
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                {{ cCase.legal_or_policy_basis || 'Belum dicantumkan rincian dasar hukum spesifik.' }}
              </div>
            </div>

            <!-- Ringkasan Temuan Bukti -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
              <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Uraian Bukti yang Ditemukan</h3>
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                {{ cCase.evidence_summary || 'Bukti teknis disinkronkan dari hasil investigasi digital.' }}
              </div>
            </div>
          </div>

          <!-- Side info: Investigasi Terkait & Provider Spec -->
          <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
              <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Investigasi Terkait</h3>
              <div v-if="cCase.investigation" class="p-4 rounded-xl bg-blue-50/50 border border-blue-100 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-blue-900 font-mono">{{ cCase.investigation.case_number }}</span>
                  <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-200/60 text-blue-800 font-bold uppercase">
                    {{ cCase.investigation.status }}
                  </span>
                </div>
                <p class="text-xs font-semibold text-slate-800">{{ cCase.investigation.target_domain }}</p>
                <p class="text-[11px] text-slate-500 line-clamp-2">{{ cCase.investigation.notes || 'Tidak ada catatan tambahan.' }}</p>
                <div class="pt-2">
                  <router-link
                    :to="`/investigations/${cCase.investigation.id}`"
                    class="text-xs font-bold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1"
                  >
                    Buka Kasus Investigasi
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                  </router-link>
                </div>
              </div>
              <div v-else class="text-xs text-slate-500 italic p-3 text-center">
                Kasus takedown independen (tanpa link investigasi terdahulu).
              </div>
            </div>

            <!-- Provider Requirements Card -->
            <div v-if="cCase.provider" class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
              <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Persyaratan Pelaporan Provider</h3>
              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                {{ cCase.provider.requirements || 'Ikuti format standar bukti: Whois, DNS record, Screenshot, dan URL lengkap.' }}
              </div>
              <div class="pt-2 text-[11px] text-slate-500 space-y-1">
                <p><span class="font-semibold text-slate-700">Tipe Pelaporan:</span> {{ cCase.provider.report_types?.join(', ') || 'Abuse Report' }}</p>
                <p v-if="cCase.provider.abuse_url">
                  <span class="font-semibold text-slate-700">Portal Abuse:</span>
                  <a :href="cCase.provider.abuse_url" target="_blank" class="text-blue-600 hover:underline ml-1">Buka Formulir ↗</a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: Barang Bukti (Evidence Vault) -->
      <div v-show="activeTab === 'evidences'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900">Barang Bukti Digital Terlampir</h3>
              <p class="text-xs text-slate-500">Bukti forensik pasif yang dipaketkan ke dalam berkas pelaporan takedown.</p>
            </div>
            <button
              @click="downloadEvidencePackage"
              :disabled="downloadingPackage"
              class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Unduh Seluruh Bukti (ZIP)
            </button>
          </div>

          <div v-if="attachedEvidences.length === 0" class="p-8 text-center border-2 border-dashed border-slate-200 rounded-xl">
            <p class="text-xs font-semibold text-slate-500">Belum ada barang bukti digital terlampir pada kasus ini.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-400 font-extrabold uppercase tracking-wider text-[10px] border-y border-slate-200/80">
                <tr>
                  <th class="py-3 px-4">Tipe Bukti</th>
                  <th class="py-3 px-4">Nama Berkas</th>
                  <th class="py-3 px-4">Checksum (SHA-256)</th>
                  <th class="py-3 px-4">Ukuran</th>
                  <th class="py-3 px-4">Waktu Perolehan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr v-for="ev in attachedEvidences" :key="ev.id" class="hover:bg-slate-50/50 transition">
                  <td class="py-3.5 px-4 font-semibold text-slate-900">
                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px] font-bold">
                      {{ ev.type || 'DIGITAL_EVIDENCE' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 font-medium text-slate-800">
                    {{ ev.file_name || ev.title || 'Bukti Digital' }}
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-1.5 font-mono text-[11px] text-slate-600">
                      <span class="truncate max-w-[200px]" :title="ev.sha256_hash">{{ ev.sha256_hash || '-' }}</span>
                      <button
                        v-if="ev.sha256_hash"
                        @click="copyText(ev.sha256_hash)"
                        class="p-1 text-slate-400 hover:text-blue-600 transition"
                        title="Salin Hash SHA-256"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                      </button>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-mono text-slate-500">
                    {{ formatFileSize(ev.file_size) }}
                  </td>
                  <td class="py-3.5 px-4 text-slate-500">
                    {{ formatDate(ev.created_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 3: Timeline Tindak Lanjut & Komunikasi Provider -->
      <div v-show="activeTab === 'timeline'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900">Riwayat Komunikasi & Pembaruan Tiket</h3>
              <p class="text-xs text-slate-500">Pencatatan korespondensi resmi dengan pihak registrar, hosting, regulator, atau CERT/CSIRT.</p>
            </div>
            <button
              @click="openFollowUpModal"
              class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
              Tambah Catatan / Balasan
            </button>
          </div>

          <div v-if="!cCase.follow_ups || cCase.follow_ups.length === 0" class="p-8 text-center border-2 border-dashed border-slate-200 rounded-xl">
            <p class="text-xs font-semibold text-slate-500">Belum ada korespondensi yang dicatat untuk kasus ini.</p>
          </div>

          <!-- Vertical Timeline -->
          <div v-else class="relative pl-6 space-y-8 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
            <div
              v-for="fu in cCase.follow_ups"
              :key="fu.id"
              class="relative group"
            >
              <!-- Dot Indicator -->
              <div
                class="absolute -left-6 top-1.5 w-5 h-5 rounded-full border-2 bg-white flex items-center justify-center transition"
                :class="fu.direction === 'INBOUND' ? 'border-emerald-500 text-emerald-600' : 'border-blue-500 text-blue-600'"
              >
                <div class="w-2 h-2 rounded-full" :class="fu.direction === 'INBOUND' ? 'bg-emerald-500' : 'bg-blue-500'"></div>
              </div>

              <!-- Card Content -->
              <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4.5 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase border"
                      :class="fu.direction === 'INBOUND' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                    >
                      {{ fu.direction === 'INBOUND' ? 'Respons Masuk (Provider)' : 'Pengajuan Keluar (Investigator)' }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                      {{ fu.channel }}
                    </span>
                    <span v-if="fu.ticket_number" class="text-xs font-mono font-bold text-slate-800">
                      Tiket: {{ fu.ticket_number }}
                    </span>
                  </div>
                  <span class="text-xs text-slate-400 font-medium">{{ formatDate(fu.created_at) }}</span>
                </div>

                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ fu.subject }}</h4>
                  <p class="text-xs text-slate-700 mt-1 whitespace-pre-line leading-relaxed">{{ fu.message }}</p>
                </div>

                <!-- Attachment if present -->
                <div v-if="fu.attachment_path" class="p-3 bg-white rounded-xl border border-slate-200/60 flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span class="font-medium text-slate-800">Lampiran Korespondensi</span>
                    <span class="text-[10px] font-mono text-slate-400 truncate max-w-[200px]" :title="fu.attachment_sha256">
                      (SHA-256: {{ fu.attachment_sha256?.substring(0, 16) }}...)
                    </span>
                  </div>
                  <a
                    :href="fu.attachment_path"
                    target="_blank"
                    class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition"
                  >
                    Buka Berkas ↗
                  </a>
                </div>

                <div class="pt-2 border-t border-slate-200/40 flex items-center justify-between text-[11px] text-slate-500">
                  <span>Dicatat oleh: <strong class="text-slate-700">{{ fu.officer?.name || 'Petugas' }}</strong></span>
                  <span v-if="fu.next_action">Tindakan Berikut: <strong class="text-blue-600">{{ fu.next_action }}</strong></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 4: Aksi Defensif Terkait -->
      <div v-show="activeTab === 'defensive'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900">Perlindungan Defensif Internal (Internal Defensive Rules)</h3>
              <p class="text-xs text-slate-500">
                Rule Firewall, WAF, Sinkhole DNS, dan Filter internal untuk mencegah personel/jaringan internal terdampak ancaman target.
              </p>
            </div>
            <router-link
              to="/takedown/defensive-actions"
              class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-2"
            >
              Lihat Modul Defensif
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </router-link>
          </div>

          <div v-if="!cCase.defensive_actions || cCase.defensive_actions.length === 0" class="p-8 text-center border-2 border-dashed border-slate-200 rounded-xl">
            <p class="text-xs font-semibold text-slate-500">Belum ada rule defensif internal yang dikaitkan ke kasus ini.</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="act in cCase.defensive_actions"
              :key="act.id"
              class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 space-y-3"
            >
              <div class="flex items-center justify-between">
                <span class="font-mono text-xs font-bold text-slate-900">{{ act.action_code }}</span>
                <span :class="getDefensiveBadgeClass(act.status)" class="px-2 py-0.5 rounded text-[10px] font-bold">
                  {{ act.status }}
                </span>
              </div>
              <h4 class="text-sm font-bold text-slate-800">{{ act.title }}</h4>
              <p class="text-xs text-slate-500 line-clamp-2">{{ act.description || 'Tidak ada keterangan.' }}</p>
              <div class="pt-2 border-t border-slate-200/50 flex items-center justify-between text-xs font-mono text-slate-600">
                <span>{{ act.rule_type }}</span>
                <span class="font-bold text-blue-600">{{ act.target_value }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5: Checklist Respons Insiden (8 Langkah) -->
      <div v-show="activeTab === 'checklist'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900">Checklist SOP Tanggap Insiden & Takedown</h3>
              <p class="text-xs text-slate-500">8 langkah berurutan tanggap insiden dan mitigasi defensif sesuai standar intelijen siber.</p>
            </div>
            <div class="text-xs font-bold px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200/60">
              {{ completedChecklistCount }} / {{ checklists.length }} Selesai
            </div>
          </div>

          <!-- Progress bar -->
          <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${checklists.length ? (completedChecklistCount / checklists.length) * 100 : 0}%` }"
            ></div>
          </div>

          <div class="space-y-3 pt-2">
            <div
              v-for="item in checklists"
              :key="item.id"
              class="flex items-start gap-3 p-3.5 rounded-xl border transition"
              :class="item.is_completed ? 'bg-emerald-50/40 border-emerald-200/60' : 'bg-slate-50/50 border-slate-200/80'"
            >
              <input
                type="checkbox"
                :checked="item.is_completed"
                @change="toggleChecklistItem(item)"
                class="mt-1 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer"
              />
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-700">
                    Langkah {{ item.step_number }}
                  </span>
                  <span class="text-xs font-bold text-slate-900" :class="{ 'line-through text-slate-400': item.is_completed }">
                    {{ item.task_name }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ item.instructions }}</p>
                <div v-if="item.is_completed" class="mt-2 text-[10px] text-emerald-700 font-medium">
                  ✓ Selesai diverifikasi oleh {{ item.completer?.name || 'Petugas' }} pada {{ formatDate(item.completed_at || '') }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 1: Update Status Kasus -->
    <div v-if="showStatusModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900">Perbarui Status Kasus</h3>
          <button @click="showStatusModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitStatusUpdate" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Status Baru</label>
            <select
              v-model="statusForm.status"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
              <option value="DRAFT">DRAFT (Penyusunan Berkas)</option>
              <option value="READY_TO_SUBMIT">READY TO SUBMIT (Siap Dikirim)</option>
              <option value="SUBMITTED">SUBMITTED (Terkirim ke Provider)</option>
              <option value="ACKNOWLEDGED">ACKNOWLEDGED (Dikonfirmasi Provider)</option>
              <option value="UNDER_REVIEW">UNDER REVIEW (Sedang Ditinjau)</option>
              <option value="ADDITIONAL_INFORMATION_REQUESTED">ADDITIONAL INFO (Meminta Tambahan Bukti)</option>
              <option value="ACTION_TAKEN">ACTION TAKEN (Takedown Berhasil)</option>
              <option value="REJECTED">REJECTED (Permohonan Ditolak)</option>
              <option value="ESCALATED">ESCALATED (Dieskalasi ke Regulator/Apgakum)</option>
              <option value="CLOSED">CLOSED (Kasus Ditutup Selesai)</option>
              <option value="NO_RESPONSE">NO RESPONSE (Tidak Ada Respon)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Perubahan Status</label>
            <textarea
              v-model="statusForm.note"
              rows="3"
              placeholder="Berikan alasan atau ringkasan perkembangan status kasus..."
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              @click="showStatusModal = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingStatus"
              class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white shadow-sm shadow-blue-500/10 disabled:opacity-50"
            >
              {{ savingStatus ? 'Menyimpan...' : 'Simpan Status' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: Tambah Tindak Lanjut / Korespondensi Tiket -->
    <div v-if="showFollowUpModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900">Catat Tindak Lanjut / Tiket Provider</h3>
          <button @click="showFollowUpModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitFollowUp" class="space-y-3.5">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Arah Komunikasi</label>
              <select
                v-model="followUpForm.direction"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
              >
                <option value="OUTBOUND">Kirim Keluar (Pengajuan/Follow-up)</option>
                <option value="INBOUND">Terima Masuk (Balasan Provider)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kanal Media</label>
              <select
                v-model="followUpForm.channel"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
              >
                <option value="EMAIL">Email Resmi</option>
                <option value="PORTAL">Portal Web Abuse</option>
                <option value="API">API Gateway</option>
                <option value="PHONE">Telepon / Hotline</option>
                <option value="WHATSAPP">WhatsApp Alert</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Tiket Provider</label>
              <input
                v-model="followUpForm.ticket_number"
                type="text"
                placeholder="Contoh: TKT-994821"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Status dari Provider</label>
              <input
                v-model="followUpForm.provider_status"
                type="text"
                placeholder="Contoh: Pending Verification"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Subjek / Perihal <span class="text-rose-500">*</span></label>
            <input
              v-model="followUpForm.subject"
              required
              type="text"
              placeholder="Contoh: Konfirmasi penerimaan laporan penipuan domain"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Isi Pesan / Catatan <span class="text-rose-500">*</span></label>
            <textarea
              v-model="followUpForm.message"
              required
              rows="3"
              placeholder="Ketik rincian pesan, respons dari provider, atau catatan perkembangan..."
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Lampiran Berkas (Opsional, Maks 10MB)</label>
            <input
              type="file"
              @change="handleFileChange"
              class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Rencana Tindakan Berikut</label>
              <input
                v-model="followUpForm.next_action"
                type="text"
                placeholder="Contoh: Cek kembali respons 24 jam"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Jadwal Tindak Lanjut Berikut</label>
              <input
                v-model="followUpForm.next_follow_up_at"
                type="datetime-local"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 outline-none"
              />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showFollowUpModal = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingFollowUp"
              class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white shadow-sm shadow-blue-500/10 disabled:opacity-50"
            >
              {{ savingFollowUp ? 'Menyimpan...' : 'Simpan Tindak Lanjut' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Swal from 'sweetalert2';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { useTakedownStore } from '@/stores/takedown';
import { useDefensiveStore } from '@/stores/defensive';
import api from '@/services/api';
import type { TakedownCase, IncidentChecklist } from '@/types';

const route = useRoute();
const takedownStore = useTakedownStore();
const defensiveStore = useDefensiveStore();

const loading = ref(false);
const cCase = ref<TakedownCase | null>(null);
const activeTab = ref('details');

const downloadingReport = ref(false);
const downloadingPackage = ref(false);

const showStatusModal = ref(false);
const savingStatus = ref(false);
const statusForm = ref({
  status: 'SUBMITTED',
  note: '',
});

const showFollowUpModal = ref(false);
const savingFollowUp = ref(false);
const followUpFile = ref<File | null>(null);
const followUpForm = ref({
  direction: 'OUTBOUND',
  channel: 'EMAIL',
  ticket_number: '',
  provider_status: '',
  subject: '',
  message: '',
  next_action: '',
  next_follow_up_at: '',
});

const tabs = computed(() => [
  { id: 'details', name: 'Rincian & Legalitas' },
  { id: 'evidences', name: 'Barang Bukti', badge: attachedEvidences.value.length },
  { id: 'timeline', name: 'Timeline Tindak Lanjut', badge: cCase.value?.follow_ups?.length || 0 },
  { id: 'defensive', name: 'Aksi Defensif', badge: cCase.value?.defensive_actions?.length || 0 },
  { id: 'checklist', name: 'Checklist Insiden', badge: `${completedChecklistCount.value}/${checklists.value.length}` },
]);

const attachedEvidences = computed(() => {
  return cCase.value?.investigation?.evidences || [];
});

const checklists = computed<IncidentChecklist[]>(() => {
  return cCase.value?.incident_checklists || [];
});

const completedChecklistCount = computed(() => {
  return checklists.value.filter(c => c.is_completed).length;
});

const isCaseOverdue = computed(() => {
  if (!cCase.value?.next_follow_up_at) return false;
  if (['ACTION_TAKEN', 'CLOSED', 'RESOLVED', 'REJECTED'].includes(cCase.value.status)) return false;
  return new Date(cCase.value.next_follow_up_at) < new Date();
});

const loadCaseDetail = async () => {
  loading.value = true;
  try {
    const id = route.params.id || route.params.uuid;
    const data = await takedownStore.fetchCaseDetail(id as string);
    cCase.value = data;
    if (cCase.value) {
      statusForm.value.status = cCase.value.status;
    }
  } catch (err: any) {
    console.error('Failed to load case detail', err);
  } finally {
    loading.value = false;
  }
};

const openStatusModal = () => {
  if (cCase.value) {
    statusForm.value.status = cCase.value.status;
    statusForm.value.note = '';
  }
  showStatusModal.value = true;
};

const submitStatusUpdate = async () => {
  if (!cCase.value) return;
  savingStatus.value = true;
  try {
    await takedownStore.updateCaseStatus(cCase.value.id, statusForm.value.status, statusForm.value.note);
    showStatusModal.value = false;
    Swal.fire({
      icon: 'success',
      title: 'Status Berhasil Diperbarui',
      text: `Status kasus kini telah berubah menjadi ${statusForm.value.status}`,
      timer: 2000,
      showConfirmButton: false,
    });
    await loadCaseDetail();
  } catch (err: any) {
    Swal.fire('Gagal', err.response?.data?.message || 'Gagal memperbarui status kasus.', 'error');
  } finally {
    savingStatus.value = false;
  }
};

const openFollowUpModal = () => {
  followUpForm.value = {
    direction: 'OUTBOUND',
    channel: 'EMAIL',
    ticket_number: cCase.value?.external_reference_number || '',
    provider_status: '',
    subject: `[Follow-up] Takedown Notice ${cCase.value?.target_domain} (${cCase.value?.case_number})`,
    message: '',
    next_action: '',
    next_follow_up_at: '',
  };
  followUpFile.value = null;
  showFollowUpModal.value = true;
};

const handleFileChange = (e: any) => {
  const file = e.target.files?.[0];
  if (file) {
    followUpFile.value = file;
  }
};

const submitFollowUp = async () => {
  if (!cCase.value) return;
  savingFollowUp.value = true;
  try {
    const formData = new FormData();
    formData.append('direction', followUpForm.value.direction);
    formData.append('channel', followUpForm.value.channel);
    formData.append('ticket_number', followUpForm.value.ticket_number || '');
    formData.append('provider_status', followUpForm.value.provider_status || '');
    formData.append('subject', followUpForm.value.subject);
    formData.append('message', followUpForm.value.message);
    if (followUpForm.value.next_action) formData.append('next_action', followUpForm.value.next_action);
    if (followUpForm.value.next_follow_up_at) formData.append('next_follow_up_at', followUpForm.value.next_follow_up_at);
    if (followUpFile.value) formData.append('attachment', followUpFile.value);

    await takedownStore.addFollowUp(cCase.value.id, formData);
    showFollowUpModal.value = false;
    Swal.fire({
      icon: 'success',
      title: 'Tindak Lanjut Disimpan',
      text: 'Korespondensi resmi berhasil dicatat dalam timeline.',
      timer: 2000,
      showConfirmButton: false,
    });
    await loadCaseDetail();
  } catch (err: any) {
    Swal.fire('Gagal', err.response?.data?.message || 'Gagal menyimpan tindak lanjut.', 'error');
  } finally {
    savingFollowUp.value = false;
  }
};

const toggleChecklistItem = async (item: IncidentChecklist) => {
  try {
    // If defensive action is linked, toggle through defensiveStore
    if (item.defensive_action_id) {
      await defensiveStore.toggleChecklist(item.defensive_action_id, item.id);
    } else {
      // Toggle directly via API endpoint if needed
      await api.post(`/defensive-actions/${item.defensive_action_id || 1}/checklists/${item.id}/toggle`);
    }
    item.is_completed = !item.is_completed;
  } catch (err) {
    // Local toggle fallback
    item.is_completed = !item.is_completed;
  }
};

const downloadReport = async () => {
  if (!cCase.value) return;
  downloadingReport.value = true;
  try {
    const res = await api.get(`/takedown/cases/${cCase.value.id}/report`, { responseType: 'blob' });
    const filename = `Takedown-Report-${cCase.value.case_number}.pdf`;
    const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (e: any) {
    Swal.fire('Gagal Unduh', 'Gagal membuat atau mengunduh laporan PDF resmi takedown.', 'error');
  } finally {
    downloadingReport.value = false;
  }
};

const downloadEvidencePackage = async () => {
  if (!cCase.value) return;
  downloadingPackage.value = true;
  try {
    const res = await api.get(`/takedown/cases/${cCase.value.id}/download-package`, { responseType: 'blob' });
    const filename = `Evidence-Package-${cCase.value.case_number}.zip`;
    const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/zip' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (e: any) {
    Swal.fire('Gagal Unduh Paket', 'Gagal mengemas barang bukti digital ke dalam ZIP.', 'error');
  } finally {
    downloadingPackage.value = false;
  }
};

const copyText = (txt: string) => {
  navigator.clipboard.writeText(txt);
  Swal.fire({
    icon: 'success',
    title: 'Disalin!',
    text: 'Checksum SHA-256 disalin ke papan klip.',
    timer: 1200,
    showConfirmButton: false,
  });
};

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatFileSize = (bytes?: number) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'ACTION_TAKEN': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    case 'SUBMITTED':
    case 'ACKNOWLEDGED':
    case 'UNDER_REVIEW': return 'bg-blue-50 text-blue-700 border border-blue-200';
    case 'ADDITIONAL_INFORMATION_REQUESTED':
    case 'READY_TO_SUBMIT': return 'bg-amber-50 text-amber-700 border border-amber-200';
    case 'REJECTED': return 'bg-rose-50 text-rose-700 border border-rose-200';
    default: return 'bg-slate-100 text-slate-600 border border-slate-200';
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
    ADDITIONAL_INFORMATION_REQUESTED: 'Perlu Info Tambahan',
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
    case 'CRITICAL': return 'bg-rose-100 text-rose-800 border-rose-200';
    case 'HIGH': return 'bg-amber-100 text-amber-800 border-amber-200';
    case 'MEDIUM': return 'bg-blue-100 text-blue-800 border-blue-200';
    default: return 'bg-slate-100 text-slate-700 border-slate-200';
  }
};

const getDefensiveBadgeClass = (status: string) => {
  switch (status) {
    case 'DEPLOYED_INTERNALLY': return 'bg-emerald-100 text-emerald-800';
    case 'APPROVED': return 'bg-blue-100 text-blue-800';
    case 'PENDING_APPROVAL':
    case 'PENDING_REVIEW': return 'bg-amber-100 text-amber-800';
    case 'REJECTED': return 'bg-rose-100 text-rose-800';
    default: return 'bg-slate-100 text-slate-700';
  }
};

onMounted(() => {
  loadCaseDetail();
});
</script>
