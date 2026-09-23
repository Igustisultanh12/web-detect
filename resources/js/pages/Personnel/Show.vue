<template>
  <AuthenticatedLayout>
    <div v-if="loading" class="p-12 text-center">
      <div class="inline-block animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mb-3"></div>
      <p class="text-sm font-medium text-slate-500">Memuat detail profil personel...</p>
    </div>

    <div v-else-if="user" class="space-y-6">
      <!-- Top Action Bar & Breadcrumb -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <router-link
            to="/personnel"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Daftar Personel
          </router-link>
          <span class="text-xs text-slate-400">/</span>
          <span class="text-xs font-mono text-slate-500">{{ user.nrp || user.uuid.substring(0, 8) }}</span>
        </div>

        <!-- Status Management Actions -->
        <div class="flex items-center gap-2">
          <button
            v-if="user.status !== 'ACTIVE'"
            @click="handleActivate"
            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition"
          >
            Aktifkan Akses
          </button>
          <button
            v-if="user.status === 'ACTIVE'"
            @click="handleDeactivate"
            class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition"
          >
            Nonaktifkan Akses
          </button>
          <button
            @click="showUploadModal = true"
            class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-sm transition flex items-center gap-1.5"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Unggah KTP/KTA
          </button>
        </div>
      </div>

      <!-- Personnel Identity Profile Card (Sisfopers KC Style) -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-5 pointer-events-none">
          <svg class="w-64 h-64 text-slate-900" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
          </svg>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
          <!-- Avatar -->
          <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-600 text-white font-extrabold text-2xl flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/20 border-2 border-white">
            {{ getInitials(user.name) }}
          </div>

          <!-- Basic Info -->
          <div class="space-y-1.5 flex-1">
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900">{{ user.name }}</h1>
              <span
                :class="getStatusBadgeClass(user.status)"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold"
              >
                <span :class="getStatusDotClass(user.status)" class="w-1.5 h-1.5 rounded-full"></span>
                {{ user.status }}
              </span>
            </div>

            <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500">
              <span class="font-semibold text-slate-700">NRP: {{ user.nrp || 'Non-NRP' }}</span>
              <span>•</span>
              <span class="font-medium text-slate-700">{{ user.rank?.name || 'Non-Pangkat' }}</span>
              <span>•</span>
              <span>{{ user.position?.name || 'Staf Analis' }}</span>
              <span>•</span>
              <span class="text-blue-600 font-semibold">{{ user.unit?.name || 'Mabes Siber' }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-1">
              <span
                v-for="r in user.roles"
                :key="r.id"
                class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200/60"
              >
                {{ r.name.toUpperCase() }}
              </span>
              <span v-if="user.email" class="text-xs text-slate-500 ml-2">
                ✉ {{ user.email }}
              </span>
              <span v-if="user.whatsapp_number" class="text-xs text-emerald-600 font-mono font-medium ml-2">
                💬 +{{ user.whatsapp_number }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex border-b border-slate-200 space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-blue-600 text-blue-600 font-bold'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium',
            'whitespace-nowrap py-3 px-1 border-b-2 text-sm transition duration-150 flex items-center gap-2'
          ]"
        >
          <span v-html="tab.icon"></span>
          {{ tab.name }}
          <span
            v-if="tab.badge !== undefined"
            :class="activeTab === tab.id ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'"
            class="ml-1.5 py-0.5 px-2 rounded-full text-xs font-semibold"
          >
            {{ tab.badge }}
          </span>
        </button>
      </div>

      <!-- TAB 1: RINGKASAN & DATA DIRI -->
      <div v-if="activeTab === 'profile'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Personal Information Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
            Data Personel & Kepegawaian
          </h3>
          <dl class="divide-y divide-slate-100 text-sm">
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Nama Lengkap</dt>
              <dd class="text-slate-900 font-semibold">{{ user.name }}</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">NRP / NIP</dt>
              <dd class="text-slate-900 font-mono">{{ user.nrp || '-' }}</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Pangkat / Korps</dt>
              <dd class="text-slate-900 font-medium">{{ user.rank?.name || '-' }} ({{ user.rank?.code || '-' }})</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Jabatan</dt>
              <dd class="text-slate-900 font-medium">{{ user.position?.name || '-' }}</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Satuan Penugasan</dt>
              <dd class="text-slate-900 font-medium">{{ user.unit?.name || '-' }} ({{ user.unit?.code || '-' }})</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Mulai Bertugas</dt>
              <dd class="text-slate-900">{{ formatDate(user.active_from) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Contact & Security Posture -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
            Kontak & Keamanan Akun
          </h3>
          <dl class="divide-y divide-slate-100 text-sm">
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Email Dinas</dt>
              <dd class="text-slate-900 font-mono">{{ user.email }}</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">WhatsApp</dt>
              <dd class="text-slate-900 font-mono">{{ user.whatsapp_number ? '+' + user.whatsapp_number : '-' }}</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Autentikasi 2FA (TOTP)</dt>
              <dd>
                <span
                  :class="user.two_factor_enabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : 'bg-rose-50 text-rose-700 border-rose-200/60'"
                  class="px-2 py-0.5 rounded-full text-xs font-semibold border"
                >
                  {{ user.two_factor_enabled ? 'Aktif Terlindungi' : 'Belum Diaktifkan' }}
                </span>
              </dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Login Terakhir</dt>
              <dd class="text-slate-900">{{ user.last_login_at ? formatDate(user.last_login_at) : 'Belum pernah' }}</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">IP Terakhir</dt>
              <dd class="text-slate-900 font-mono text-xs">{{ user.last_login_ip || '-' }}</dd>
            </div>
            <div class="py-2.5 flex justify-between">
              <dt class="text-slate-500 font-medium">Sesi Login Aktif</dt>
              <dd class="text-slate-900 font-semibold">{{ user.user_sessions?.length || 0 }} sesi</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- TAB 2: DOKUMEN KEDINASAN (KTP / KTA) -->
      <div v-if="activeTab === 'documents'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Berkas Identitas Sensitif (KTP & KTA)</h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Penyimpanan aman terisolasi di disk private dengan checksum integritas SHA-256 dan watermark dinamis.
              </p>
            </div>
            <button
              @click="showUploadModal = true"
              class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition"
            >
              + Unggah Berkas Baru
            </button>
          </div>

          <div v-if="!user.documents || user.documents.length === 0" class="py-12 text-center">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <p class="text-sm font-semibold text-slate-700">Belum ada berkas KTP atau KTA tersimpan</p>
            <p class="text-xs text-slate-400 mt-1">Unggah salinan identitas resmi personel untuk verifikasi keamanan fisik.</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
            <div
              v-for="doc in user.documents"
              :key="doc.id"
              class="border border-slate-200/80 rounded-2xl p-5 hover:border-blue-400/60 transition group bg-slate-50/30"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-xs">
                    {{ doc.document_type }}
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-slate-900">{{ doc.document_type }} Personel</h4>
                    <p class="text-[11px] text-slate-400 font-mono">{{ doc.original_filename }}</p>
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  Terverifikasi
                </span>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-500">
                <div class="flex justify-between font-mono text-[11px]">
                  <span>SHA-256:</span>
                  <span class="text-slate-700 truncate max-w-[200px]" :title="doc.sha256_hash">{{ doc.sha256_hash }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Ukuran:</span>
                  <span class="text-slate-700 font-medium">{{ (doc.file_size / 1024).toFixed(1) }} KB</span>
                </div>
                <div class="flex justify-between">
                  <span>Diupload:</span>
                  <span class="text-slate-700">{{ formatDate(doc.created_at) }}</span>
                </div>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                <button
                  @click="openWatermarkPreview(doc)"
                  class="flex-1 py-2 px-3 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 flex items-center justify-center gap-1.5 transition"
                >
                  <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  Lihat Berkas (Watermark)
                </button>
                <button
                  @click="downloadSecureDoc(doc)"
                  class="py-2 px-3 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-semibold flex items-center gap-1.5 transition"
                  title="Unduh Berkas Asli (Dicatat dalam Audit Trail)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  Unduh
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: RIWAYAT INVESTIGASI -->
      <div v-if="activeTab === 'investigations'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900">Kasus Investigasi Terkait</h3>
          <span class="text-xs text-slate-500">{{ user.investigations?.length || 0 }} Investigasi Baru-baru Ini</span>
        </div>

        <div v-if="!user.investigations || user.investigations.length === 0" class="p-12 text-center">
          <p class="text-sm font-semibold text-slate-700">Belum ada investigasi oleh personel ini</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-200/80">
                <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode Kasus</th>
                <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Domain / Target</th>
                <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Klasifikasi</th>
                <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Waktu</th>
                <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="inv in user.investigations" :key="inv.id" class="hover:bg-slate-50/60 transition">
                <td class="py-3 px-5 font-mono text-xs font-bold text-blue-600">{{ inv.case_code }}</td>
                <td class="py-3 px-4 font-semibold text-slate-900 text-sm">{{ inv.target_domain }}</td>
                <td class="py-3 px-4 text-xs">{{ inv.classification }}</td>
                <td class="py-3 px-4">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ inv.status }}
                  </span>
                </td>
                <td class="py-3 px-4 text-xs text-slate-500">{{ formatDate(inv.created_at) }}</td>
                <td class="py-3 px-5 text-right">
                  <router-link :to="`/investigations/${inv.id}`" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                    Lihat Kasus →
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 4: SESI & AUDIT TRAIL -->
      <div v-if="activeTab === 'sessions'" class="space-y-6">
        <!-- Active User Sessions -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
          <h3 class="text-base font-bold text-slate-900">Sesi Login Aktif</h3>
          <div v-if="!user.user_sessions || user.user_sessions.length === 0" class="text-xs text-slate-400">
            Tidak ada data sesi aktif.
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="s in user.user_sessions"
              :key="s.id"
              class="flex items-center justify-between p-3.5 rounded-xl border border-slate-100 bg-slate-50/40 text-xs"
            >
              <div>
                <div class="font-bold text-slate-800">{{ s.ip_address }}</div>
                <div class="text-slate-500 truncate max-w-md">{{ s.user_agent }}</div>
              </div>
              <div class="text-right">
                <span class="px-2 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  Aktif
                </span>
                <div class="text-slate-400 mt-1">{{ formatDate(s.last_activity_at) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Audit Trail Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
          <div class="p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Jejak Audit Aktivitas (Audit Trail)</h3>
            <p class="text-xs text-slate-500 mt-0.5">Catatan log aktivitas personel yang tidak dapat dimanipulasi.</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/70 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                  <th class="py-3 px-5">Waktu</th>
                  <th class="py-3 px-4">Tindakan</th>
                  <th class="py-3 px-4">Target Entitas</th>
                  <th class="py-3 px-4">Alamat IP</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs">
                <tr v-for="log in user.audit_logs" :key="log.id" class="hover:bg-slate-50/50">
                  <td class="py-2.5 px-5 font-mono text-slate-500">{{ formatDate(log.created_at) }}</td>
                  <td class="py-2.5 px-4 font-bold text-slate-800">{{ log.action }}</td>
                  <td class="py-2.5 px-4 font-mono text-slate-600">{{ log.auditable_type }}:{{ log.auditable_id }}</td>
                  <td class="py-2.5 px-4 font-mono text-slate-600">{{ log.ip_address || '127.0.0.1' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Watermark Preview Modal -->
      <div v-if="previewDoc" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden flex flex-col max-h-[90vh]">
          <!-- Modal Header -->
          <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
              <h3 class="font-bold text-slate-900 text-base">Pratinjau Dokumen Rahasia (KTP/KTA)</h3>
              <p class="text-xs text-slate-500 font-mono">Tervalidasi SHA-256: {{ previewDoc.sha256_hash.substring(0, 16) }}...</p>
            </div>
            <button @click="previewDoc = null" class="text-slate-400 hover:text-slate-600 p-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Document Body with Dynamic Watermark Overlay -->
          <div class="p-6 overflow-y-auto relative flex-1 flex items-center justify-center min-h-[350px] bg-slate-100 select-none">
            <!-- Simulated Secure Watermark Overlay -->
            <div class="absolute inset-0 pointer-events-none z-10 flex flex-wrap items-center justify-around opacity-20 rotate-[-25deg] select-none text-slate-800 font-black text-sm tracking-widest leading-loose">
              <div v-for="n in 12" :key="n" class="p-4 text-center">
                DOKUMEN RAHASIA<br>
                WEBDETECT / {{ authStore.user?.name }}<br>
                {{ new Date().toISOString() }}
              </div>
            </div>

            <!-- Image or PDF viewer -->
            <div class="relative z-0 max-w-full max-h-[500px] border border-slate-300 rounded-xl overflow-hidden shadow-lg bg-white p-2">
              <img
                :src="previewUrl"
                alt="Document Preview"
                class="max-w-full max-h-[480px] object-contain rounded"
                @error="previewError = true"
              />
              <div v-if="previewError" class="p-8 text-center text-slate-500">
                <p class="font-bold text-sm">Pratinjau visual tidak dapat dimuat langsung.</p>
                <p class="text-xs mt-1">Gunakan tombol 'Unduh Berkas' dengan otorisasi resmi untuk melihat file.</p>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-xs text-slate-500">
            <span>Watermark dicetak otomatis sesuai identitas pengakses</span>
            <button
              @click="previewDoc = null"
              class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>

      <!-- Upload Modal -->
      <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-900">Unggah Dokumen KTP / KTA</h3>
            <button @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
          </div>

          <form @submit.prevent="handleUploadDocument" class="space-y-4">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Jenis Dokumen</label>
              <select
                v-model="uploadForm.document_type"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm"
              >
                <option value="KTP">KTP (Kartu Tanda Penduduk)</option>
                <option value="KTA">KTA (Kartu Tanda Anggota / Dinas)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Pilih Berkas (Max 5MB)</label>
              <input
                type="file"
                accept="image/*,.pdf"
                @change="onFileChange"
                required
                class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
              <button
                type="button"
                @click="showUploadModal = false"
                class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="uploading"
                class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold disabled:opacity-50"
              >
                {{ uploading ? 'Mengunggah...' : 'Unggah Berkas' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import { useRoute } from 'vue-router';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { usePersonnelStore } from '../../stores/personnel';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import Swal from 'sweetalert2';

const route = useRoute();
const personnelStore = usePersonnelStore();
const authStore = useAuthStore();

const user = ref<any>(null);
const loading = ref(true);
const activeTab = ref('profile');

const tabs = [
  { id: 'profile', name: 'Profil & Kepegawaian', icon: '👤' },
  { id: 'documents', name: 'Dokumen KTP/KTA', icon: '📄' },
  { id: 'investigations', name: 'Riwayat Kasus', icon: '🔍' },
  { id: 'sessions', name: 'Sesi & Audit Trail', icon: '🛡️' },
];

const showUploadModal = ref(false);
const uploading = ref(false);
const uploadFile = ref<File | null>(null);
const uploadForm = reactive({
  document_type: 'KTP',
});

const previewDoc = ref<any>(null);
const previewUrl = ref('');
const previewError = ref(false);

const loadUser = async () => {
  loading.value = true;
  try {
    user.value = await personnelStore.fetchPersonnelDetail(route.params.uuid as string);
  } catch (e: any) {
    Swal.fire('Gagal', 'Gagal memuat profil personel.', 'error');
  } finally {
    loading.value = false;
  }
};

const getInitials = (name: string) => {
  if (!name) return 'PG';
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getStatusBadgeClass = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'active':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200/60';
    case 'inactive':
      return 'bg-slate-100 text-slate-600 border border-slate-200';
    case 'suspended':
      return 'bg-rose-50 text-rose-700 border border-rose-200/60';
    default:
      return 'bg-amber-50 text-amber-700 border border-amber-200/60';
  }
};

const getStatusDotClass = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'active': return 'bg-emerald-500';
    case 'inactive': return 'bg-slate-400';
    case 'suspended': return 'bg-rose-500';
    default: return 'bg-amber-500';
  }
};

const onFileChange = (e: any) => {
  if (e.target.files && e.target.files[0]) {
    uploadFile.value = e.target.files[0];
  }
};

const handleUploadDocument = async () => {
  if (!uploadFile.value) return;
  uploading.value = true;
  try {
    const formData = new FormData();
    formData.append('document', uploadFile.value);
    formData.append('document_type', uploadForm.document_type);

    await api.post(`/users/${user.value.uuid}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    Swal.fire('Berhasil', 'Dokumen berhasil diunggah secara aman.', 'success');
    showUploadModal.value = false;
    uploadFile.value = null;
    loadUser();
  } catch (e: any) {
    Swal.fire('Gagal', e.response?.data?.message || 'Gagal mengunggah berkas.', 'error');
  } finally {
    uploading.value = false;
  }
};

const openWatermarkPreview = (doc: any) => {
  previewDoc.value = doc;
  previewError.value = false;
  // Get token to fetch image with Sanctum authorization
  const token = localStorage.getItem('webguard_token');
  previewUrl.value = `/api/v1/documents/${doc.uuid}/view?token=${token}`;
};

const downloadSecureDoc = async (doc: any) => {
  try {
    const res = await api.get(`/documents/${doc.uuid}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', doc.original_filename || `dokumen-${doc.document_type}.bin`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (e: any) {
    Swal.fire('Akses Ditolak', e.response?.data?.message || 'Anda tidak memiliki hak unduh berkas ini.', 'error');
  }
};

const handleActivate = async () => {
  try {
    await personnelStore.activatePersonnel(user.value.uuid);
    Swal.fire('Berhasil', 'Akses personel telah diaktifkan.', 'success');
    loadUser();
  } catch (e) {
    Swal.fire('Gagal', 'Gagal mengaktifkan personel.', 'error');
  }
};

const handleDeactivate = async () => {
  const { value: reason } = await Swal.fire({
    title: 'Nonaktifkan Akses?',
    input: 'text',
    inputLabel: 'Alasan penonaktifan:',
    showCancelButton: true,
    confirmButtonColor: '#E11D48',
  });
  if (reason) {
    try {
      await personnelStore.deactivatePersonnel(user.value.uuid, reason);
      Swal.fire('Berhasil', 'Akses dinonaktifkan.', 'success');
      loadUser();
    } catch (e) {
      Swal.fire('Gagal', 'Gagal memproses.', 'error');
    }
  }
};

onMounted(() => {
  loadUser();
});
</script>
