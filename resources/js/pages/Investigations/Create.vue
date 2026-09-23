<template>
  <AuthenticatedLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="flex items-center gap-3">
        <router-link to="/investigations" class="p-2 hover:bg-slate-200/60 rounded-xl text-slate-500 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
        </router-link>
        <div>
          <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Formulir Investigasi Pasif Baru</h2>
          <p class="text-xs text-slate-500 mt-0.5">Analisis teknis domain dan pengumpulan bukti digital publik.</p>
        </div>
      </div>

      <!-- Legal & Defensive Notice -->
      <div class="p-5 rounded-2xl bg-blue-50/70 border border-blue-200 text-xs text-blue-900 space-y-2">
        <div class="flex items-center gap-2 font-bold text-sm text-blue-800">
          <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          Prinsip Investigasi & Batasan Etika Operasi
        </div>
        <p class="leading-relaxed text-blue-800">
          Aplikasi ini dirancang khusus untuk <strong>analisis pasif defensif</strong> terhadap aset informasi publik. Sistem secara otomatis menolak dan <strong>TIDAK MENYEDIAKAN</strong> fitur eksploitasi, DDoS, brute force, bypassing keamanan, atau tindakan merusak server target.
        </p>
      </div>

      <!-- Form Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <form @submit.prevent="submitForm" class="space-y-6">
          <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            {{ errorMessage }}
          </div>

          <!-- Target URL -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              URL / Domain Target <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="form.target_url"
                type="text"
                required
                placeholder="sisinden.my.id atau https://contoh-situs.com"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
              Masukkan URL lengkap atau nama domain. Sistem otomatis memvalidasi terhadap kerentanan SSRF (loopback, IP privat, dan metadata terlarang).
            </p>
          </div>

          <!-- Grid: Category & Priority -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Kategori Dugaan Pelanggaran
              </label>
              <select
                v-model="form.category"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="Phishing">Phishing / Pemalsuan Identitas</option>
                <option value="Malware">Penyebaran Malware / Script Berbahaya</option>
                <option value="Fraud">Fraud / Penipuan Finansial</option>
                <option value="Illegal Content">Konten Ilegal / Perjudian / Pornografi</option>
                <option value="Copyright">Pelanggaran Hak Cipta / Pembajakan</option>
                <option value="Spam">Spamming / Botnet</option>
                <option value="Suspicious Domain">Suspicious Domain (Domain Mencurigakan)</option>
                <option value="Other">Lainnya (Penyelidikan Umum)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Tingkat Prioritas Penanganan
              </label>
              <select
                v-model="form.priority"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="LOW">Rendah (LOW)</option>
                <option value="MEDIUM">Sedang (MEDIUM)</option>
                <option value="HIGH">Tinggi (HIGH)</option>
                <option value="CRITICAL">Kritis / Mendesak (CRITICAL)</option>
              </select>
            </div>
          </div>

          <!-- Reason / Notes -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Latar Belakang / Alasan Investigasi (Opsional)
            </label>
            <textarea
              v-model="form.reason"
              rows="3"
              placeholder="Catatan awal investigator, laporan dari masyarakat, indikasi temuan awal..."
              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <!-- Submit Button -->
          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <router-link
              to="/investigations"
              class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
            >
              Batal
            </router-link>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider transition shadow-md shadow-blue-500/25 flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-if="loading">Menjalankan Pipeline Investigasi...</span>
              <span v-else>Mulai Investigasi Pasif &rarr;</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- LIVE CYBER INTELLIGENCE PROGRESS MODAL -->
    <div
      v-if="showLiveModal"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden flex flex-col max-h-[90vh] text-slate-100">
        <!-- Terminal Header -->
        <div class="px-6 py-4 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
              <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
              <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
            </div>
            <div class="h-4 w-px bg-slate-800 mx-1"></div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-black tracking-widest text-blue-400 uppercase font-mono">LIVE INTELLIGENCE STREAM</span>
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                ACTIVE PIPELINE
              </span>
            </div>
          </div>
          <div class="text-right">
            <span class="font-mono text-xs font-bold text-slate-300">{{ cleanDomain(form.target_url) }}</span>
          </div>
        </div>

        <!-- Progress Overview Bar -->
        <div class="p-6 border-b border-slate-800/80 bg-slate-900/60 space-y-3">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-300 flex items-center gap-2">
              <svg class="w-4 h-4 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ currentStageLabel }}
            </span>
            <span class="font-mono font-bold text-blue-400">{{ progressPercent }}%</span>
          </div>

          <div class="w-full bg-slate-950 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-800">
            <div
              class="bg-gradient-to-r from-blue-600 via-cyan-500 to-emerald-400 h-1.5 rounded-full transition-all duration-500 ease-out"
              :style="{ width: `${progressPercent}%` }"
            ></div>
          </div>
        </div>

        <!-- Pipeline Stages Pills -->
        <div class="px-6 py-3.5 bg-slate-950/40 border-b border-slate-800/60 overflow-x-auto">
          <div class="flex items-center gap-2 min-w-max text-[11px] font-mono">
            <div
              v-for="(stage, idx) in pipelineStages"
              :key="idx"
              class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border transition"
              :class="getStageClass(idx)"
            >
              <svg v-if="idx < currentStageIdx" class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <div v-else-if="idx === currentStageIdx" class="w-2 h-2 rounded-full bg-blue-400 animate-ping shrink-0"></div>
              <div v-else class="w-1.5 h-1.5 rounded-full bg-slate-600 shrink-0"></div>
              <span>{{ stage.name }}</span>
            </div>
          </div>
        </div>

        <!-- Terminal Console Log Stream -->
        <div class="p-6 flex-1 overflow-y-auto font-mono text-xs space-y-2 bg-slate-950 min-h-[220px] max-h-[320px] select-text" ref="terminalContainer">
          <div v-for="(log, idx) in logEntries" :key="idx" class="leading-relaxed flex items-start gap-2.5">
            <span class="text-slate-500 shrink-0">{{ log.time }}</span>
            <span
              class="px-1 rounded text-[10px] font-bold uppercase shrink-0"
              :class="getLogLevelClass(log.level)"
            >
              {{ log.level }}
            </span>
            <span class="text-slate-300 break-all">{{ log.message }}</span>
          </div>

          <div v-if="!pipelineFinished && !pipelineFailed" class="flex items-center gap-1 text-cyan-400 pt-1">
            <span class="inline-block w-2 h-4 bg-cyan-400 animate-pulse"></span>
            <span class="text-[11px] text-slate-500 italic">Mengeksekusi analisis pasif di latar belakang...</span>
          </div>
        </div>

        <!-- Terminal Footer -->
        <div class="p-4 bg-slate-950/90 border-t border-slate-800 flex items-center justify-between text-xs">
          <div class="flex items-center gap-2 text-slate-400 text-[11px]">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Strictly Passive & Non-Destructive Mode</span>
          </div>

          <div v-if="pipelineFailed">
            <button
              @click="closeLiveModal"
              class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition"
            >
              Tutup & Perbaiki Input
            </button>
          </div>
          <div v-else-if="pipelineFinished" class="text-emerald-400 font-bold flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span>Analisis Selesai! Mengalihkan ke lembar kasus...</span>
          </div>
          <div v-else class="text-slate-500 font-mono text-[11px]">
            Harap tunggu, proses membutuhkan waktu ~15-20 detik...
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { reactive, ref, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { useInvestigationStore } from '@/stores/investigation';

const router = useRouter();
const investigationStore = useInvestigationStore();

const form = reactive({
  target_url: '',
  category: 'Suspicious Domain',
  priority: 'CRITICAL',
  reason: '',
});

const loading = ref(false);
const errorMessage = ref('');
const showLiveModal = ref(false);
const progressPercent = ref(10);
const currentStageIdx = ref(0);
const currentStageLabel = ref('Inisialisasi & Verifikasi SSRF...');
const pipelineFinished = ref(false);
const pipelineFailed = ref(false);
const terminalContainer = ref<HTMLElement | null>(null);

interface LogEntry {
  time: string;
  level: 'INIT' | 'INFO' | 'RECON' | 'SUCCESS' | 'ERROR';
  message: string;
}

const logEntries = ref<LogEntry[]>([]);

const pipelineStages = [
  { name: '1. SSRF Pre-flight' },
  { name: '2. DNS & WHOIS' },
  { name: '3. IP & GeoIP' },
  { name: '4. SSL/TLS Cert' },
  { name: '5. HTTP Posture' },
  { name: '6. Tech Stack' },
  { name: '7. Threat Intel' },
  { name: '8. Visual Capture' },
];

let stageTimer: any = null;

const cleanDomain = (url: string) => {
  if (!url) return 'Target Domain';
  try {
    const raw = url.trim();
    const parsed = raw.startsWith('http') ? new URL(raw).hostname : raw.split('/')[0];
    return parsed.toLowerCase();
  } catch {
    return url;
  }
};

const getCurrentTimestamp = () => {
  const now = new Date();
  return now.toTimeString().split(' ')[0];
};

const appendLog = (level: LogEntry['level'], message: string) => {
  logEntries.value.push({
    time: getCurrentTimestamp(),
    level,
    message,
  });
  nextTick(() => {
    if (terminalContainer.value) {
      terminalContainer.value.scrollTop = terminalContainer.value.scrollHeight;
    }
  });
};

const getLogLevelClass = (level: LogEntry['level']) => {
  switch (level) {
    case 'SUCCESS': return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
    case 'RECON': return 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30';
    case 'INFO': return 'bg-blue-500/20 text-blue-400 border border-blue-500/30';
    case 'ERROR': return 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
    default: return 'bg-slate-700/50 text-slate-300 border border-slate-600';
  }
};

const getStageClass = (idx: number) => {
  if (idx < currentStageIdx.value) {
    return 'bg-emerald-950/40 text-emerald-400 border-emerald-800/60 font-semibold';
  } else if (idx === currentStageIdx.value) {
    return 'bg-blue-950/70 text-blue-300 border-blue-600 shadow-sm shadow-blue-500/20 font-bold';
  }
  return 'bg-slate-900/60 text-slate-500 border-slate-800';
};

const startSimulatedStageStream = (domain: string) => {
  const steps = [
    {
      stageIdx: 0,
      percent: 15,
      label: '1/8: Memeriksa perimeter keamanan & SSRF...',
      level: 'INFO' as const,
      message: `Inisialisasi pipeline investigasi pasif terhadap ${domain}...`,
    },
    {
      stageIdx: 0,
      percent: 25,
      label: '1/8: Memeriksa perimeter keamanan & SSRF...',
      level: 'SUCCESS' as const,
      message: 'Proteksi SSRF valid: Alamat IP publik terverifikasi di luar subnet privat.',
    },
    {
      stageIdx: 1,
      percent: 35,
      label: '2/8: Mengambil Catatan DNS publik & WHOIS...',
      level: 'RECON' as const,
      message: `Melakukan resolusi pasif DNS (A, AAAA, MX, NS, TXT, SOA) untuk ${domain}...`,
    },
    {
      stageIdx: 2,
      percent: 48,
      label: '3/8: Menganalisis IP publik, ASN & Geolokasi...',
      level: 'RECON' as const,
      message: 'Mendeteksi alamat IP publik host server, rute BGP ASN, dan estimasi ISP datacenter...',
    },
    {
      stageIdx: 3,
      percent: 60,
      label: '4/8: Melakukan Handshake & Inspeksi SSL/TLS...',
      level: 'RECON' as const,
      message: 'Memeriksa masa berlaku sertifikat X.509, Subject Alternative Names (SAN), dan Cipher Suite...',
    },
    {
      stageIdx: 4,
      percent: 72,
      label: '5/8: Mengevaluasi Postur HTTP Security Headers...',
      level: 'INFO' as const,
      message: 'Menganalisis skor postur keamanan HTTP (HSTS, CSP, X-Frame-Options, Referrer-Policy)...',
    },
    {
      stageIdx: 5,
      percent: 82,
      label: '6/8: Mengidentifikasi Sidik Jari Teknologi (Tech Stack)...',
      level: 'RECON' as const,
      message: 'Mendeteksi komponen web server, framework backend, library frontend, dan CDN proxy edge...',
    },
    {
      stageIdx: 6,
      percent: 90,
      label: '7/8: Mengumpulkan Reputasi Global & Threat Intelligence...',
      level: 'INFO' as const,
      message: 'Mengkroscek basis data reputasi ancaman, blacklist DNSBL, dan skor indikasi kecurigaan...',
    },
    {
      stageIdx: 7,
      percent: 96,
      label: '8/8: Mengambil Tangkapan Visual Forensik (Visual Evidence)...',
      level: 'RECON' as const,
      message: 'Merekam snapshot visual forensik website terisolasi & menghitung hash kriptografis SHA-256...',
    },
  ];

  let currentStep = 0;
  stageTimer = setInterval(() => {
    if (currentStep < steps.length && !pipelineFinished.value && !pipelineFailed.value) {
      const step = steps[currentStep];
      currentStageIdx.value = step.stageIdx;
      progressPercent.value = step.percent;
      currentStageLabel.value = step.label;
      appendLog(step.level, step.message);
      currentStep++;
    } else {
      clearInterval(stageTimer);
    }
  }, 1600);
};

const closeLiveModal = () => {
  showLiveModal.value = false;
  clearInterval(stageTimer);
};

const submitForm = async () => {
  errorMessage.value = '';
  loading.value = true;
  pipelineFinished.value = false;
  pipelineFailed.value = false;
  logEntries.value = [];
  progressPercent.value = 10;
  currentStageIdx.value = 0;

  const domain = cleanDomain(form.target_url);
  showLiveModal.value = true;

  appendLog('INIT', `Memulai sesi analisis forensik pasif untuk target: ${domain}`);
  startSimulatedStageStream(domain);

  try {
    const res = await investigationStore.createInvestigation(form);

    clearInterval(stageTimer);
    currentStageIdx.value = 8;
    progressPercent.value = 100;
    currentStageLabel.value = 'Investigasi Berhasil Selesai!';
    pipelineFinished.value = true;

    appendLog('SUCCESS', `Berkas perkara ${res.investigation?.investigation_code || 'resmi'} berhasil disimpan ke dalam database.`);
    appendLog('SUCCESS', 'Seluruh barang bukti digital forensik telah diindeks ke dalam Evidence Vault.');
    appendLog('INFO', 'Mengalihkan tampilan ke lembar rincian kasus dalam 1 detik...');

    setTimeout(() => {
      if (res.success && res.investigation?.id) {
        router.push(`/investigations/${res.investigation.id}`);
      } else {
        router.push('/investigations');
      }
    }, 1200);
  } catch (err: any) {
    clearInterval(stageTimer);
    pipelineFailed.value = true;
    const msg = err.response?.data?.message || 'Gagal memproses investigasi. Periksa format URL target atau koneksi jaringan.';
    errorMessage.value = msg;
    appendLog('ERROR', `Kegagalan Pipeline: ${msg}`);
  } finally {
    loading.value = false;
  }
};
</script>
