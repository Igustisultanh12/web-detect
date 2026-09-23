<template>
  <AuthenticatedLayout>
    <div v-if="investigationStore.detailLoading" class="py-20 text-center text-xs text-slate-400">
      <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-3"></div>
      <p>Mengambil data forensik dan intelijen pasif...</p>
    </div>

    <div v-else-if="!inv" class="py-20 text-center text-xs text-slate-400">
      Berkas investigasi tidak ditemukan.
    </div>

    <div v-else class="space-y-6">
      <!-- Investigation Header Bar -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-700 to-blue-500 text-white flex items-center justify-center font-extrabold text-base shrink-0 shadow-md shadow-blue-500/20 select-none">
            {{ inv.target_domain.substring(0, 2).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl font-extrabold text-slate-900 tracking-tight truncate">{{ inv.target_domain }}</h1>
              <span :class="getStatusBadgeClass(inv.status)" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wide uppercase">
                {{ inv.status }}
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                {{ inv.category }}
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
              <span class="font-mono font-bold text-slate-600">{{ inv.investigation_code }}</span>
              <span>&bull;</span>
              <span>Target: {{ inv.target_url }}</span>
              <span>&bull;</span>
              <span>Investigator: {{ inv.user?.name }} ({{ inv.user?.rank?.name || 'Analis' }})</span>
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0 w-full md:w-auto justify-end">
          <button
            @click="downloadReport('PDF')"
            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Unduh PDF
          </button>
          <button
            @click="downloadReport('JSON')"
            class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs uppercase tracking-wider transition cursor-pointer"
          >
            JSON
          </button>
          <button
            @click="downloadReport('CSV')"
            class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs uppercase tracking-wider transition cursor-pointer"
          >
            CSV
          </button>
        </div>
      </div>

      <!-- 15 Tabs Navigation Bar -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-2 shadow-sm overflow-x-auto scrollbar-thin">
        <div class="flex items-center gap-1 min-w-max">
          <button
            v-for="t in tabs"
            :key="t.key"
            @click="activeTab = t.key"
            :class="activeTab === t.key ? 'bg-[#2563EB]/10 text-[#2563EB] font-bold shadow-sm' : 'text-slate-500 hover:text-slate-800 font-medium hover:bg-slate-50'"
            class="px-3.5 py-2 rounded-xl text-xs transition duration-150 flex items-center gap-1.5 cursor-pointer"
          >
            <span>{{ t.label }}</span>
            <span v-if="t.count !== undefined" class="px-1.5 py-0.2 rounded-full text-[9px] bg-slate-200/70 text-slate-700 font-bold">
              {{ t.count }}
            </span>
          </button>
        </div>
      </div>

      <!-- Tab Contents Area -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm min-h-[400px]">

        <!-- TAB 1: OVERVIEW -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Summary Specs -->
            <div class="md:col-span-2 space-y-4">
              <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3">
                Ringkasan Intelijen Teknis
              </h3>
              <div class="grid grid-cols-2 gap-4 text-xs">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-400 block mb-1">Registrar Domain</span>
                  <span class="font-bold text-slate-800">{{ inv.domain_record?.registrar || 'Tidak Dipublikasi / Privacy' }}</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-400 block mb-1">Masa Berlaku Domain</span>
                  <span class="font-bold text-slate-800">{{ formatDate(inv.domain_record?.expires_at) }}</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-400 block mb-1">Alamat IP Server</span>
                  <span class="font-bold text-slate-800 font-mono">{{ inv.ip_addresses?.[0]?.ip_address || 'Tidak Terdeteksi' }}</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-400 block mb-1">Deteksi CDN / Proxy</span>
                  <span :class="inv.ip_addresses?.[0]?.is_cdn_or_proxy ? 'text-blue-600 font-bold' : 'text-slate-700 font-semibold'">
                    {{ inv.ip_addresses?.[0]?.is_cdn_or_proxy ? 'YA (' + (inv.ip_addresses?.[0]?.cdn_provider || 'CDN') + ')' : 'TIDAK (Direct Hosting)' }}
                  </span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-400 block mb-1">Keabsahan SSL/TLS</span>
                  <span :class="inv.ssl_certificate?.is_valid ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold'">
                    {{ inv.ssl_certificate?.is_valid ? 'VALID (' + (inv.ssl_certificate?.issuer_cn || 'Penerbit Sah') + ')' : 'TIDAK VALID / EXPIRED' }}
                  </span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                  <span class="text-slate-400 block mb-1">Skor Security Header</span>
                  <span class="font-black text-slate-900 text-sm">{{ inv.http_result?.security_score ?? 0 }} / 100</span>
                </div>
              </div>

              <!-- Disclaimer Banner -->
              <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200/80 text-[11px] text-amber-900 space-y-1">
                <p class="font-bold">Batasan Analisis Pasif:</p>
                <p>Data hasil analisis dapat berubah sewaktu-waktu dan tidak selalu menjadi bukti mutlak bahwa suatu pihak melakukan pelanggaran hukum. Seluruh pemeriksaan dilakukan tanpa mengganggu atau memodifikasi server target.</p>
              </div>
            </div>

            <!-- Screenshot Thumbnail & Quick Stats -->
            <div class="space-y-4">
              <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3">
                Visual Evidence
              </h3>
              <div v-if="inv.screenshots?.length" class="border border-slate-200 rounded-xl overflow-hidden bg-slate-900 shadow-inner group cursor-pointer" @click="activeTab = 'screenshots'">
                <!-- Thumbnail rendering -->
                <div class="aspect-video relative overflow-hidden bg-slate-950 flex items-center justify-center">
                  <img :src="`/api/v1/evidence/${inv.screenshots[0].sha256}`" class="w-full h-full object-cover object-top transition duration-300 group-hover:scale-105" alt="Visual Evidence Thumbnail" />
                  <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition flex items-end p-3">
                    <span class="text-white text-xs font-semibold flex items-center gap-1.5">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                      Klik untuk memperbesar bukti visual
                    </span>
                  </div>
                </div>
                <div class="p-3 bg-white text-[11px] text-slate-500 flex justify-between items-center border-t border-slate-100">
                  <span class="font-mono">SHA-256: {{ inv.screenshots[0].sha256.substring(0, 12) }}...</span>
                  <span class="text-blue-600 font-bold hover:underline flex items-center gap-1">Perbesar &rarr;</span>
                </div>
              </div>
              <div v-else class="h-44 rounded-xl border border-dashed border-slate-200 flex items-center justify-center text-xs text-slate-400 italic">
                Tangkapan visual belum selesai diproses.
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: DOMAIN -->
        <div v-else-if="activeTab === 'domain'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Informasi Domain & RDAP Publik</h3>
          <div v-if="inv.domain_record" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Domain Name</span>
              <span class="font-bold font-mono text-sm text-slate-800">{{ inv.domain_record.domain }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Top-Level Domain (TLD)</span>
              <span class="font-bold text-slate-800">{{ inv.domain_record.tld }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Registrar</span>
              <span class="font-bold text-slate-800">{{ inv.domain_record.registrar || 'Privasi / Protected' }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">DNSSEC Status</span>
              <span class="font-bold text-slate-800">{{ inv.domain_record.dnssec_status }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Tanggal Pendaftaran (Created)</span>
              <span class="font-bold text-slate-800">{{ formatDate(inv.domain_record.registered_at) }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Tanggal Kedaluwarsa (Expires)</span>
              <span class="font-bold text-slate-800">{{ formatDate(inv.domain_record.expires_at) }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 md:col-span-2">
              <span class="text-slate-400 block mb-2">Authoritative Nameservers</span>
              <div class="flex flex-wrap gap-2">
                <span v-for="ns in inv.domain_record.nameservers" :key="ns" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-mono text-[11px] font-semibold text-slate-700">
                  {{ ns }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-xs text-slate-400 italic">Belum ada catatan domain yang terindeks.</div>
        </div>

        <!-- TAB 3: DNS -->
        <div v-else-if="activeTab === 'dns'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Catatan DNS Publik (DNS Records)</h3>
          <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200">
                <tr>
                  <th class="px-4 py-3">Tipe</th>
                  <th class="px-4 py-3">Host</th>
                  <th class="px-4 py-3">Target / Value</th>
                  <th class="px-4 py-3">TTL</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="dns in inv.dns_records" :key="dns.id" class="hover:bg-slate-50/50">
                  <td class="px-4 py-2.5 font-bold text-blue-600">{{ dns.record_type }}</td>
                  <td class="px-4 py-2.5 font-mono text-slate-700">{{ dns.host }}</td>
                  <td class="px-4 py-2.5 font-mono text-slate-800 break-all">{{ dns.target }}</td>
                  <td class="px-4 py-2.5 text-slate-400">{{ dns.ttl || '-' }}s</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 4: IP & MAP -->
        <div v-else-if="activeTab === 'ip'" class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Alamat IP Server & Geolokasi</h3>
            <span class="text-xs text-slate-400">Ditemukan {{ inv.ip_addresses?.length || 0 }} IP</span>
          </div>

          <!-- Geolocation Disclaimer -->
          <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200/60 text-xs text-blue-900 flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span><strong>Disclaimer:</strong> Lokasi IP adalah estimasi berdasarkan database IP geolocation dan tidak selalu menunjukkan lokasi fisik server sebenarnya.</span>
          </div>

          <!-- IP Cards Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="ip in inv.ip_addresses" :key="ip.id" class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-3">
              <div class="flex items-center justify-between">
                <span class="font-mono font-black text-base text-slate-900">{{ ip.ip_address }}</span>
                <span :class="ip.is_cdn_or_proxy ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-700'" class="px-2 py-0.5 rounded-full text-[10px] font-bold">
                  {{ ip.is_cdn_or_proxy ? 'CDN: ' + (ip.cdn_provider || 'Endpoint') : 'Direct IP' }}
                </span>
              </div>
              <div class="text-xs text-slate-600 space-y-1">
                <p><strong>Reverse DNS:</strong> <span class="font-mono text-slate-500">{{ ip.reverse_dns || 'Tidak ada PTR record' }}</span></p>
                <p v-if="ip.is_cdn_or_proxy" class="text-[11px] text-amber-700 italic">
                  IP yang ditemukan kemungkinan merupakan endpoint CDN/reverse proxy dan bukan alamat server origin.
                </p>
              </div>
            </div>
          </div>

          <!-- Hosting & Map Section -->
          <div v-if="inv.hosting_records?.length" class="space-y-4 pt-4 border-t border-slate-100">
            <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700">Estimasi Koordinat & Peta Lokasi</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div v-for="geo in inv.hosting_records" :key="geo.id" class="p-4 rounded-xl bg-white border border-slate-200 text-xs space-y-1.5">
                <p class="font-bold text-slate-900">{{ geo.city }}, {{ geo.region }} {{ geo.country }}</p>
                <p class="text-slate-500">ISP / Jaringan: {{ geo.isp }}</p>
                <p class="text-slate-500">Organisasi: {{ geo.organization }}</p>
                <p class="font-mono text-slate-400 text-[11px]">Lat/Lng: {{ geo.latitude }}, {{ geo.longitude }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 5: ASN -->
        <div v-else-if="activeTab === 'asn'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Autonomous System Number (ASN)</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="asn in inv.asn_records" :key="asn.id" class="p-5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2 text-xs">
              <div class="flex items-center justify-between">
                <span class="font-mono font-bold text-base text-blue-600">{{ asn.asn }}</span>
                <span class="font-mono text-slate-500">{{ asn.ip_address }}</span>
              </div>
              <p class="font-semibold text-slate-800 text-sm">{{ asn.asn_org }}</p>
              <p class="text-slate-400">BGP Prefix: {{ asn.bgp_prefix || 'Standard Global Routing' }}</p>
            </div>
          </div>
        </div>

        <!-- TAB 6: HOSTING -->
        <div v-else-if="activeTab === 'hosting'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Penyedia Layanan Hosting & Infrastruktur</h3>
          <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
            <div v-for="h in inv.hosting_records" :key="h.id" class="p-4 text-xs space-y-1 bg-white">
              <div class="flex justify-between font-bold text-slate-800 text-sm">
                <span>{{ h.organization || h.isp }}</span>
                <span class="font-mono text-slate-400 text-xs">{{ h.ip_address }}</span>
              </div>
              <p class="text-slate-500">Tipe Layanan: {{ h.hosting_type || 'Cloud Datacenter' }}</p>
              <p class="text-slate-500">Wilayah: {{ h.region }}, {{ h.country }} (Timezone: {{ h.timezone }})</p>
            </div>
          </div>
        </div>

        <!-- TAB 7: SSL -->
        <div v-else-if="activeTab === 'ssl'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Analisis Kriptografi SSL / TLS</h3>
          <div v-if="inv.ssl_certificate" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Subject CN</span>
              <span class="font-bold text-slate-800">{{ inv.ssl_certificate.subject_cn }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Penerbit (Issuer)</span>
              <span class="font-bold text-slate-800">{{ inv.ssl_certificate.issuer_cn || inv.ssl_certificate.issuer_org }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Protokol & Cipher Suite</span>
              <span class="font-mono text-slate-800">{{ inv.ssl_certificate.tls_version }} / {{ inv.ssl_certificate.cipher }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Certificate Transparency Status</span>
              <span class="font-bold text-emerald-700">{{ inv.ssl_certificate.ct_status }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Masa Berlaku (Valid From)</span>
              <span class="text-slate-800">{{ formatDate(inv.ssl_certificate.valid_from) }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-slate-400 block mb-1">Kedaluwarsa (Valid Until)</span>
              <span class="font-bold text-slate-800">{{ formatDate(inv.ssl_certificate.valid_until) }}</span>
            </div>
          </div>
        </div>

        <!-- TAB 8: HTTP & SECURITY HEADERS -->
        <div v-else-if="activeTab === 'http'" class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Analisis Header HTTP & Security Score</h3>
            <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full font-black text-sm">
              Skor: {{ inv.http_result?.security_score ?? 0 }}/100
            </span>
          </div>

          <div v-if="inv.http_result" class="space-y-4">
            <!-- Header Checks Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
              <div v-for="note in inv.http_result.security_notes" :key="note.header" class="p-3.5 rounded-xl border flex items-start gap-3" :class="note.status === 'PASS' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200'">
                <div class="mt-0.5">
                  <span v-if="note.status === 'PASS'" class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs">✓</span>
                  <span v-else class="w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs">!</span>
                </div>
                <div>
                  <p class="font-bold text-slate-800">{{ note.header }}</p>
                  <p class="text-slate-500 text-[11px] mt-0.5">{{ note.detail }}</p>
                </div>
              </div>
            </div>

            <!-- Raw Headers Accordion/Block -->
            <div class="mt-4 pt-4 border-t border-slate-100">
              <h4 class="font-bold text-xs uppercase tracking-wider text-slate-600 mb-2">Raw Response Headers</h4>
              <pre class="p-4 bg-slate-900 text-emerald-400 font-mono text-[11px] rounded-xl overflow-x-auto max-h-60">{{ JSON.stringify(inv.http_result.raw_headers, null, 2) }}</pre>
            </div>
          </div>
        </div>

        <!-- TAB 9: TECHNOLOGY -->
        <div v-else-if="activeTab === 'technology'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Teknologi Terdeteksi (Passive Fingerprint)</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div v-for="tech in inv.technologies" :key="tech.id" class="p-4 rounded-xl border border-slate-200 bg-white shadow-sm space-y-1 text-xs">
              <span class="text-[10px] font-extrabold text-blue-600 uppercase tracking-wider">{{ tech.category }}</span>
              <p class="font-bold text-slate-900 text-sm">{{ tech.name }} <span v-if="tech.version" class="text-slate-400 font-normal">v{{ tech.version }}</span></p>
              <p class="text-slate-400 text-[11px]">Pola: {{ tech.matched_pattern }}</p>
            </div>
          </div>
        </div>

        <!-- TAB 10: SUBDOMAINS -->
        <div v-else-if="activeTab === 'subdomains'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Subdomain Pasif (Certificate Transparency)</h3>
          <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200">
                <tr>
                  <th class="px-4 py-3">Subdomain</th>
                  <th class="px-4 py-3">Sumber Data</th>
                  <th class="px-4 py-3">Alamat IP Terkait</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="sub in inv.subdomains" :key="sub.id" class="hover:bg-slate-50/50">
                  <td class="px-4 py-2.5 font-mono font-bold text-slate-800">{{ sub.subdomain }}</td>
                  <td class="px-4 py-2.5 text-slate-500">{{ sub.source }}</td>
                  <td class="px-4 py-2.5 font-mono text-slate-600">{{ sub.ip_address || 'Unresolved' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 11: REPUTATION -->
        <div v-else-if="activeTab === 'reputation'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Pemeriksaan Reputasi Publik (Threat Intelligence)</h3>
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
            <strong>Catatan Integritas:</strong> Status reputasi berasal dari provider eksternal dan perlu diverifikasi lebih lanjut.
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="rep in inv.reputation_results" :key="rep.id" class="p-5 rounded-xl border border-slate-200 bg-white space-y-2 text-xs">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900 text-sm">{{ rep.provider_name }}</span>
                <span :class="rep.status === 'CLEAN' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold">
                  {{ rep.status }}
                </span>
              </div>
              <p class="text-slate-600">{{ rep.threat_type || 'Tidak ada ancaman terdeteksi' }}</p>
              <p class="text-slate-400 text-[11px]">Waktu Pemeriksaan: {{ formatDate(rep.checked_at) }}</p>
            </div>
          </div>
        </div>

        <!-- TAB 12: SCREENSHOTS -->
        <div v-else-if="activeTab === 'screenshots'" class="space-y-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Tangkapan Layar Forensik Website (Visual Evidence)</h3>
              <p class="text-xs text-slate-500">Dokumentasi visual pasif tersimpan dan terlindungi dengan verifikasi hash kriptografis SHA-256.</p>
            </div>
            <div v-if="inv.screenshots?.length" class="flex items-center gap-2">
              <a :href="`/api/v1/evidence/${inv.screenshots[0].sha256}`" target="_blank" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 flex items-center gap-1.5 shadow-sm transition">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Buka Tab Baru
              </a>
              <a :href="`/api/v1/evidence/${inv.screenshots[0].sha256}`" download class="px-3 py-1.5 rounded-lg bg-blue-600 text-xs font-semibold text-white hover:bg-blue-700 flex items-center gap-1.5 shadow-sm transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Unduh Gambar
              </a>
            </div>
          </div>

          <div v-for="shot in inv.screenshots" :key="shot.id" class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm">
            <!-- Sleek Browser Chrome Header Mockup -->
            <div class="bg-slate-900 border-b border-slate-800 px-4 py-2.5 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
              </div>
              <div class="flex-1 max-w-xl mx-4">
                <div class="bg-slate-950 border border-slate-800 rounded-full px-4 py-1 text-xs text-slate-300 font-mono flex items-center justify-between shadow-inner">
                  <div class="flex items-center gap-1.5 truncate">
                    <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span class="text-slate-400 select-none">https://</span>
                    <span class="text-white font-medium truncate">{{ inv.target_domain }}</span>
                  </div>
                  <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-400 font-semibold border border-emerald-800/40 shrink-0">Captured</span>
                </div>
              </div>
              <div class="text-[11px] text-slate-400 font-mono shrink-0">
                {{ shot.width || 1280 }} &times; {{ shot.height || 800 }} px
              </div>
            </div>

            <!-- Image Viewport Canvas -->
            <div class="w-full bg-slate-950 flex items-center justify-center min-h-[420px] overflow-hidden">
              <img :src="`/api/v1/evidence/${shot.sha256}`" class="w-full h-auto max-h-[850px] object-contain block select-none" :alt="`Screenshot ${inv.target_domain}`" />
            </div>

            <!-- Metadata Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4 text-xs">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-slate-700">Checksum SHA-256:</span>
                  <span class="font-mono text-slate-600 select-all bg-white px-2 py-0.5 rounded border border-slate-200">{{ shot.sha256 }}</span>
                </div>
                <div class="text-slate-500">
                  Waktu Pengambilan: <span class="font-semibold text-slate-700">{{ formatDate(shot.captured_at) }}</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200 text-[11px]">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  Integritas Digital Terverifikasi
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 13: EVIDENCE -->
        <div v-else-if="activeTab === 'evidence'" class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Barang Bukti Digital Terenkripsi (Evidence Vault)</h3>
            <span class="text-xs text-slate-400">{{ inv.evidences?.length || 0 }} barang bukti</span>
          </div>
          <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
            <div v-for="ev in inv.evidences" :key="ev.id" class="p-4 bg-white hover:bg-slate-50/50 transition text-xs space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="font-mono font-bold text-blue-600 text-sm">{{ ev.evidence_code }}</span>
                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px]">{{ ev.type }}</span>
              </div>
              <p class="font-semibold text-slate-800">Sumber: {{ ev.source }}</p>
              <p class="font-mono text-slate-400 text-[11px] break-all">SHA-256: {{ ev.sha256 }}</p>
            </div>
          </div>
        </div>

        <!-- TAB 14: TIMELINE -->
        <div v-else-if="activeTab === 'timeline'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Kronologi Investigasi (Immutable Timeline)</h3>
          <div class="relative border-l-2 border-slate-200 ml-4 pl-6 space-y-6">
            <div v-for="log in inv.timeline" :key="log.id" class="relative">
              <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full border-2 border-white" :class="getTimelineColor(log.event_type)"></span>
              <p class="font-bold text-xs text-slate-800">{{ log.description }}</p>
              <span class="text-[10px] text-slate-400">{{ formatDate(log.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- TAB 15: REPORT -->
        <div v-else-if="activeTab === 'report'" class="space-y-4">
          <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-900">Laporan Resmi Tersedia</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-3">
              <div class="flex items-center gap-2 text-red-600 font-bold text-sm">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14h2v2h-2v-2zm0-10h2v8h-2V6z"/></svg>
                Laporan Dokumen PDF
              </div>
              <p class="text-xs text-slate-500">Laporan cetak resmi siap verifikasi pimpinan atau rujukan penegakan hukum.</p>
              <button @click="downloadReport('PDF')" class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs uppercase hover:bg-blue-700 cursor-pointer">
                Unduh PDF Resmi &rarr;
              </button>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-3">
              <div class="flex items-center gap-2 text-emerald-600 font-bold text-sm">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14h-2v-4H8l4-4 4 4h-2v4z"/></svg>
                Ekspor Data CSV / Excel
              </div>
              <p class="text-xs text-slate-500">Tabel data DNS, IP, dan catatan forensik untuk integrasi spreadsheet.</p>
              <button @click="downloadReport('CSV')" class="w-full py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs uppercase hover:bg-slate-50 cursor-pointer">
                Unduh CSV &rarr;
              </button>
            </div>

            <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-3">
              <div class="flex items-center gap-2 text-amber-600 font-bold text-sm">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                Ekspor JSON API Standar
              </div>
              <p class="text-xs text-slate-500">Struktur payload JSON machine-readable lengkap untuk SIEM dan SOAR.</p>
              <button @click="downloadReport('JSON')" class="w-full py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs uppercase hover:bg-slate-50 cursor-pointer">
                Unduh JSON &rarr;
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { useInvestigationStore } from '../../stores/investigation';
import api from '../../services/api';

const route = useRoute();
const investigationStore = useInvestigationStore();

const activeTab = ref('overview');

const inv = computed(() => investigationStore.currentInvestigation);

const tabs = computed(() => [
  { key: 'overview', label: 'Ringkasan' },
  { key: 'domain', label: 'Domain & RDAP' },
  { key: 'dns', label: 'DNS Records', count: inv.value?.dns_records?.length },
  { key: 'ip', label: 'Server IP & Peta', count: inv.value?.ip_addresses?.length },
  { key: 'asn', label: 'ASN' },
  { key: 'hosting', label: 'Hosting & ISP' },
  { key: 'ssl', label: 'SSL / TLS' },
  { key: 'http', label: 'HTTP & Headers' },
  { key: 'technology', label: 'Teknologi Web', count: inv.value?.technologies?.length },
  { key: 'subdomains', label: 'Subdomain Pasif', count: inv.value?.subdomains?.length },
  { key: 'reputation', label: 'Reputasi Publik' },
  { key: 'screenshots', label: 'Visual Evidence' },
  { key: 'evidence', label: 'Barang Bukti', count: inv.value?.evidences?.length },
  { key: 'timeline', label: 'Kronologi' },
  { key: 'report', label: 'Laporan Resmi' },
]);

const downloadReport = async (format: string) => {
  if (!inv.value) return;
  try {
    const res = await api.post(`/investigations/${inv.value.id}/generate-report`, { format });
    if (res.data?.report?.uuid) {
      window.open(`/api/v1/reports/${res.data.report.uuid}/download`, '_blank');
    }
  } catch (e) {
    alert('Gagal mengunduh laporan.');
  }
};

const formatDate = (d: any) => {
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

const getTimelineColor = (type: string) => {
  switch (type) {
    case 'SUCCESS': return 'bg-emerald-500';
    case 'ERROR': return 'bg-rose-500';
    case 'WARNING': return 'bg-amber-500';
    default: return 'bg-blue-500';
  }
};

onMounted(() => {
  const id = route.params.id as string;
  if (id) {
    investigationStore.fetchInvestigationDetail(id);
  }
});
</script>
