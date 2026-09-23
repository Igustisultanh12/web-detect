export interface Rank {
  id: number;
  name: string;
  code: string;
  order: number;
  status: string;
}

export interface Unit {
  id: number;
  parent_id?: number | null;
  code: string;
  name: string;
  description?: string;
  status: string;
  children?: Unit[];
}

export interface Position {
  id: number;
  name: string;
  code: string;
  description?: string;
  status: string;
}

export interface Role {
  id: number;
  name: string;
  label: string;
  description?: string;
}

export interface Permission {
  id: number;
  name: string;
  label: string;
  group: string;
}

export interface UserProfile {
  id: number;
  user_id: number;
  nik?: string;
  birthplace?: string;
  birthdate?: string;
  gender?: string;
  address?: string;
  city?: string;
  province?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  notes?: string;
}

export interface UserDocument {
  id: number;
  uuid: string;
  user_id: number;
  document_type: 'KTP' | 'KTA';
  file_path: string;
  original_filename: string;
  mime_type: string;
  file_size: number;
  sha256: string;
  uploaded_at: string;
  uploaded_by?: number;
  versions?: UserDocumentVersion[];
}

export interface UserDocumentVersion {
  id: number;
  document_id: number;
  version: number;
  sha256: string;
  uploaded_at: string;
  notes?: string;
}

export interface UserSession {
  id: number;
  ip_address?: string;
  user_agent?: string;
  browser?: string;
  os?: string;
  device?: string;
  is_current: boolean;
  last_active_at?: string;
}

export interface User {
  id: number;
  uuid: string;
  name: string;
  email: string;
  nrp?: string;
  phone?: string;
  whatsapp_number?: string;
  masked_whatsapp?: string;
  rank?: Rank | string;
  rank_code?: string;
  position?: Position | string;
  unit?: Unit | string;
  roles: Role[] | string[];
  permissions: string[];
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED' | 'PENDING_VERIFICATION';
  two_factor_enabled: boolean;
  active_from?: string;
  activated_by_admin_at?: string;
  deactivated_at?: string;
  deactivation_reason?: string;
  last_login_at?: string;
  last_login_ip?: string;
  profile?: UserProfile;
  documents?: UserDocument[];
  investigations_count?: number;
}

export interface Investigation {
  id: number;
  uuid: string;
  investigation_code: string;
  user_id: number;
  user?: User;
  target_url: string;
  target_domain: string;
  category: string;
  priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';
  reason?: string;
  tags?: string[];
  status: 'QUEUED' | 'ANALYZING' | 'COMPLETED' | 'FAILED' | 'PARTIAL_RESULT';
  error_message?: string;
  started_at?: string;
  completed_at?: string;
  created_at: string;
  updated_at: string;

  domain_record?: DomainRecord;
  dns_records?: DnsRecord[];
  ip_addresses?: IpAddress[];
  asn_records?: AsnRecord[];
  hosting_records?: HostingRecord[];
  ssl_certificate?: SslCertificate;
  http_result?: HttpResult;
  technologies?: Technology[];
  subdomains?: Subdomain[];
  reputation_results?: ReputationResult[];
  screenshots?: Screenshot[];
  evidences?: Evidence[];
  timeline?: InvestigationTimeline[];
  reports?: Report[];

  evidences_count?: number;
  dns_records_count?: number;
  ip_addresses_count?: number;
  subdomains_count?: number;
}

export interface DomainRecord {
  id: number;
  domain: string;
  tld?: string;
  registrar?: string;
  registered_at?: string;
  expires_at?: string;
  domain_status?: string[];
  nameservers?: string[];
  dnssec_status?: string;
  rdap_data?: any;
}

export interface DnsRecord {
  id: number;
  record_type: string;
  host: string;
  target: string;
  ttl?: number;
  priority?: number;
  raw_entry?: string;
}

export interface IpAddress {
  id: number;
  ip_address: string;
  ip_version: string;
  is_cdn_or_proxy: boolean;
  cdn_provider?: string;
  reverse_dns?: string;
}

export interface AsnRecord {
  id: number;
  ip_address: string;
  asn?: string;
  asn_org?: string;
  bgp_prefix?: string;
  registry?: string;
}

export interface HostingRecord {
  id: number;
  ip_address: string;
  isp?: string;
  organization?: string;
  hosting_type?: string;
  country?: string;
  country_code?: string;
  region?: string;
  city?: string;
  latitude?: number;
  longitude?: number;
  timezone?: string;
  is_datacenter: boolean;
}

export interface SslCertificate {
  id: number;
  subject_cn?: string;
  subject_org?: string;
  issuer_cn?: string;
  issuer_org?: string;
  san_list?: string[];
  valid_from?: string;
  valid_until?: string;
  is_valid: boolean;
  tls_version?: string;
  cipher?: string;
  signature_algorithm?: string;
  public_key_bits?: number;
  cert_chain?: any[];
  ct_status?: string;
}

export interface HttpResult {
  id: number;
  http_status?: number;
  https_available: boolean;
  final_url?: string;
  redirect_chain?: string[];
  server_header?: string;
  content_type?: string;
  content_length?: number;
  compression?: string;
  hsts_header?: string;
  csp_header?: string;
  x_frame_options?: string;
  x_content_type_options?: string;
  referrer_policy?: string;
  permissions_policy?: string;
  cookies_data?: any[];
  security_score?: number;
  security_notes?: any[];
  raw_headers?: Record<string, string>;
}

export interface Technology {
  id: number;
  category: string;
  name: string;
  version?: string;
  confidence: number;
  matched_pattern?: string;
  icon?: string;
}

export interface Subdomain {
  id: number;
  subdomain: string;
  source: string;
  ip_address?: string;
  is_active: boolean;
}

export interface ReputationResult {
  id: number;
  provider_name: string;
  status: 'CLEAN' | 'SUSPICIOUS' | 'MALICIOUS' | 'UNKNOWN';
  threat_type?: string;
  score?: number;
  checked_at?: string;
  details?: any;
}

export interface Screenshot {
  id: number;
  uuid: string;
  file_path: string;
  original_url: string;
  sha256: string;
  width: number;
  height: number;
  file_size?: number;
  captured_at: string;
}

export interface Evidence {
  id: number;
  uuid: string;
  evidence_code: string;
  type: string;
  source: string;
  raw_data: string;
  parsed_data?: any;
  sha256: string;
  collected_at: string;
  notes?: string;
  creator?: User;
  investigation?: Investigation;
  versions?: EvidenceVersion[];
}

export interface EvidenceVersion {
  id: number;
  version: number;
  notes?: string;
  updated_by?: number;
  updater?: User;
  created_at: string;
}

export interface InvestigationTimeline {
  id: number;
  action: string;
  event_type: 'INFO' | 'SUCCESS' | 'WARNING' | 'ERROR';
  description: string;
  metadata?: any;
  created_at: string;
}

export interface Report {
  id: number;
  uuid: string;
  report_number: string;
  title: string;
  format: string;
  file_path: string;
  file_size?: number;
  sha256: string;
  generated_at: string;
  generator?: User;
  investigation?: Investigation;
}

export interface AuditLog {
  id: number;
  user_id?: number;
  user?: User;
  action: string;
  target_type?: string;
  target_id?: string;
  ip_address?: string;
  user_agent?: string;
  result: string;
  details?: any;
  created_at: string;
}

export interface SecurityEvent {
  id: number;
  event_type: string;
  severity: 'INFO' | 'WARNING' | 'HIGH' | 'CRITICAL';
  description: string;
  ip_address?: string;
  user_agent?: string;
  user_id?: number;
  user?: User;
  metadata?: any;
  created_at: string;
}

export interface LoginAttempt {
  id: number;
  email: string;
  ip_address?: string;
  user_agent?: string;
  status: 'SUCCESS' | 'FAILED' | 'BLOCKED';
  failure_reason?: string;
  created_at: string;
}
