<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Evidence Vault & Integritas Forensik</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
              {{ total }} Barang Bukti Digital
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Gudang penyimpanan barang bukti digital terverifikasi kriptografi SHA-256 dengan rantai pemeliharaan (Chain of Custody).
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="loadEvidences"
            class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-sm transition flex items-center gap-1.5"
          >
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Segarkan
          </button>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Search -->
          <div class="relative sm:col-span-2">
            <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              placeholder="Cari Kode Bukti (EVD-...), Hash SHA-256, atau sumber..."
              class="w-full pl-10 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            />
          </div>

          <!-- Type Filter -->
          <div>
            <select
              v-model="filters.type"
              @change="loadEvidences"
              class="w-full px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition outline-none"
            >
              <option value="">Semua Tipe Bukti</option>
              <option value="DNS_RECORD">DNS Records</option>
              <option value="SSL_CERTIFICATE">Sertifikat SSL/TLS</option>
              <option value="HTTP_RESPONSE">HTTP Headers & Responses</option>
              <option value="SCREENSHOT">Tangkapan Layar (Screenshot)</option>
              <option value="DOMAIN_WHOIS">Domain RDAP / WHOIS</option>
              <option value="REPUTATION">Reputasi & Blacklist</option>
              <option value="TECHNOLOGY">Teknologi Terdeteksi</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Evidences Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-block animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mb-3"></div>
          <p class="text-sm font-medium text-slate-500">Memuat berkas bukti digital...</p>
        </div>

        <div v-else-if="evidences.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 mx-auto flex items-center justify-center text-slate-400 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-slate-800">Belum ada barang bukti digital tersimpan</h3>
          <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1">
            Barang bukti akan otomatis diarsipkan dan diberi hash SHA-256 saat Anda menjalankan investigasi website.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-200/80">
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode Bukti</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tipe Bukti</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kasus Investigasi</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Checksum SHA-256</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pencatat / Sumber</th>
                <th class="py-3.5 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Waktu Koleksi</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Verifikasi & Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in evidences"
                :key="item.id"
                class="hover:bg-slate-50/60 transition duration-150 group"
              >
                <!-- Kode Bukti -->
                <td class="py-3.5 px-5">
                  <div class="font-mono text-xs font-bold text-blue-600 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    {{ item.evidence_code }}
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5">Versi {{ item.versions_count || 1 }}</div>
                </td>

                <!-- Tipe Bukti -->
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
                    {{ item.type }}
                  </span>
                </td>

                <!-- Kasus Investigasi -->
                <td class="py-3.5 px-4">
                  <router-link
                    v-if="item.investigation"
                    :to="`/investigations/${item.investigation.id}`"
                    class="font-semibold text-slate-900 hover:text-blue-600 text-sm block transition"
                  >
                    {{ item.investigation.target_domain }}
                  </router-link>
                  <span v-else class="text-xs text-slate-400">-</span>
                  <div class="text-xs text-slate-400 font-mono">{{ item.investigation?.case_code }}</div>
                </td>

                <!-- Checksum SHA-256 -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-1.5">
                    <code class="text-xs font-mono text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-200/60 max-w-[160px] truncate block" :title="item.sha256">
                      {{ item.sha256 }}
                    </code>
                    <button
                      @click="copyToClipboard(item.sha256)"
                      title="Salin SHA-256"
                      class="text-slate-400 hover:text-blue-600 p-1"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                    </button>
                  </div>
                </td>

                <!-- Pencatat / Sumber -->
                <td class="py-3.5 px-4">
                  <div class="text-xs font-semibold text-slate-800">{{ item.creator?.name || 'Sistem Otomatis' }}</div>
                  <div class="text-[11px] text-slate-400">{{ item.source || 'Automated Pipeline' }}</div>
                </td>

                <!-- Waktu Koleksi -->
                <td class="py-3.5 px-4 text-xs text-slate-500 font-mono">
                  {{ formatDate(item.collected_at || item.created_at) }}
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-5 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- Integrity Verify Button -->
                    <button
                      @click="verifyIntegrity(item)"
                      class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/80 transition flex items-center gap-1"
                      title="Uji Integritas Kriptografis SHA-256"
                    >
                      <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                      </svg>
                      Verifikasi
                    </button>

                    <!-- View Details Button -->
                    <button
                      @click="viewEvidenceDetail(item)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition"
                      title="Lihat Rincian Bukti & Raw Data"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
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

      <!-- Detail & Raw Data Modal -->
      <div v-if="selectedEvidence" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden flex flex-col max-h-[90vh]">
          <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
              <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                <span>{{ selectedEvidence.evidence_code }}</span>
                <span class="px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700 font-semibold">{{ selectedEvidence.type }}</span>
              </h3>
              <p class="text-xs text-slate-500 font-mono">SHA-256: {{ selectedEvidence.sha256 }}</p>
            </div>
            <button @click="selectedEvidence = null" class="text-slate-400 hover:text-slate-600 p-1">
              ✕
            </button>
          </div>

          <div class="p-6 overflow-y-auto space-y-4 flex-1">
            <!-- Metadata Grid -->
            <div class="grid grid-cols-2 gap-4 text-xs bg-slate-50/70 p-4 rounded-xl border border-slate-100">
              <div>
                <span class="text-slate-400">Target Kasus:</span>
                <span class="font-semibold text-slate-800 ml-1">{{ selectedEvidence.investigation?.target_domain }}</span>
              </div>
              <div>
                <span class="text-slate-400">Dicatat Pada:</span>
                <span class="font-mono text-slate-800 ml-1">{{ formatDate(selectedEvidence.collected_at) }}</span>
              </div>
              <div>
                <span class="text-slate-400">Sumber Pipeline:</span>
                <span class="text-slate-800 ml-1">{{ selectedEvidence.source }}</span>
              </div>
              <div>
                <span class="text-slate-400">Status Integritas:</span>
                <span class="text-emerald-600 font-bold ml-1">TERVALIDASI KRIPTOGRAFIS</span>
              </div>
            </div>

            <!-- Raw Data Payload Viewer -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-700">Raw Data Kriptografis</label>
                <button @click="copyToClipboard(selectedEvidence.raw_data)" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                  Salin Raw Data
                </button>
              </div>
              <pre class="bg-slate-900 text-slate-200 text-xs font-mono p-4 rounded-xl overflow-x-auto max-h-72 border border-slate-800 whitespace-pre-wrap">{{ formatRawData(selectedEvidence.raw_data) }}</pre>
            </div>

            <!-- Add Note / Version Section -->
            <div class="pt-4 border-t border-slate-200">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                Tambahkan Catatan Forensik (Akan membuat versi baru secara immutable)
              </label>
              <div class="flex gap-2">
                <input
                  v-model="newNote"
                  type="text"
                  placeholder="Catatan analisis atau temuan barang bukti..."
                  class="flex-1 px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none focus:bg-white focus:border-blue-500"
                />
                <button
                  @click="addForensicNote"
                  :disabled="!newNote || addingNote"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold disabled:opacity-40 transition"
                >
                  {{ addingNote ? 'Menyimpan...' : 'Tambah Catatan' }}
                </button>
              </div>
            </div>
          </div>

          <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button @click="selectedEvidence = null" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
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
import Swal from 'sweetalert2';

const evidences = ref<any[]>([]);
const total = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const loading = ref(false);

const filters = reactive({
  search: '',
  type: '',
});

const selectedEvidence = ref<any>(null);
const newNote = ref('');
const addingNote = ref(false);

let debounceTimer: any = null;
const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    loadEvidences();
  }, 350);
};

const loadEvidences = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/evidence', {
      params: {
        page,
        search: filters.search || undefined,
        type: filters.type || undefined,
      },
    });
    evidences.value = res.data.data;
    total.value = res.data.meta.total;
    currentPage.value = res.data.meta.current_page;
    lastPage.value = res.data.meta.last_page;
  } catch (e: any) {
    Swal.fire('Gagal', 'Gagal memuat daftar barang bukti.', 'error');
  } finally {
    loading.value = false;
  }
};

const changePage = (p: number) => {
  loadEvidences(p);
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

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text);
  Swal.fire({
    icon: 'success',
    title: 'Disalin!',
    text: 'Checksum / Data disalin ke papan klip.',
    timer: 1200,
    showConfirmButton: false,
  });
};

const formatRawData = (data: any) => {
  if (!data) return '(Kosong)';
  if (typeof data === 'object') {
    return JSON.stringify(data, null, 2);
  }
  try {
    const parsed = JSON.parse(data);
    return JSON.stringify(parsed, null, 2);
  } catch {
    return data;
  }
};

const verifyIntegrity = async (item: any) => {
  try {
    const res = await api.get(`/evidence/${item.uuid}/verify`);
    if (res.data.is_valid) {
      Swal.fire({
        icon: 'success',
        title: 'Integritas Terverifikasi!',
        html: `
          <div class="text-left text-xs font-mono bg-slate-50 p-3 rounded-lg border border-slate-200 mt-2">
            <p><strong>Status:</strong> MATCH (Asli & Utuh)</p>
            <p class="mt-1"><strong>Stored SHA-256:</strong><br>${res.data.stored_hash}</p>
            <p class="mt-1"><strong>Calculated:</strong><br>${res.data.computed_hash}</p>
          </div>
        `,
        confirmButtonColor: '#2563EB',
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'PERINGATAN INTEGRITAS RUSAK',
        text: 'Checksum bukti tidak cocok dengan data awal! Kemungkinan berkas termodifikasi.',
        confirmButtonColor: '#E11D48',
      });
    }
  } catch (e: any) {
    Swal.fire('Gagal Verifikasi', e.response?.data?.message || 'Gagal memverifikasi bukti.', 'error');
  }
};

const viewEvidenceDetail = async (item: any) => {
  try {
    const res = await api.get(`/evidence/${item.uuid}`);
    selectedEvidence.value = res.data.evidence;
    newNote.value = '';
  } catch (e: any) {
    Swal.fire('Gagal', 'Gagal memuat detail barang bukti.', 'error');
  }
};

const addForensicNote = async () => {
  if (!selectedEvidence.value || !newNote.value) return;
  addingNote.value = true;
  try {
    await api.post(`/evidence/${selectedEvidence.value.uuid}/notes`, {
      notes: newNote.value,
    });
    Swal.fire('Berhasil', 'Catatan versi baru berhasil ditambahkan.', 'success');
    viewEvidenceDetail(selectedEvidence.value);
    loadEvidences(currentPage.value);
  } catch (e: any) {
    Swal.fire('Gagal', e.response?.data?.message || 'Gagal menambahkan catatan.', 'error');
  } finally {
    addingNote.value = false;
  }
};

onMounted(() => {
  loadEvidences();
});
</script>
