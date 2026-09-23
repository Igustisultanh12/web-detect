import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/Login.vue'),
    meta: { guest: true, title: 'Masuk Sistem' },
  },
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../pages/Dashboard.vue'),
    meta: { requiresAuth: true, title: 'Dashboard Investigasi' },
  },
  {
    path: '/investigations',
    name: 'investigations.index',
    component: () => import('../pages/Investigations/Index.vue'),
    meta: { requiresAuth: true, title: 'Daftar Investigasi' },
  },
  {
    path: '/investigations/create',
    name: 'investigations.create',
    component: () => import('../pages/Investigations/Create.vue'),
    meta: { requiresAuth: true, title: 'Investigasi Baru' },
  },
  {
    path: '/investigations/:id',
    name: 'investigations.show',
    component: () => import('../pages/Investigations/Show.vue'),
    meta: { requiresAuth: true, title: 'Rincian Kasus Investigasi' },
  },
  {
    path: '/personnel',
    name: 'personnel.index',
    component: () => import('../pages/Personnel/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true, title: 'Master Personel' },
  },
  {
    path: '/personnel/create',
    name: 'personnel.create',
    component: () => import('../pages/Personnel/Create.vue'),
    meta: { requiresAuth: true, requiresAdmin: true, title: 'Tambah Personel' },
  },
  {
    path: '/personnel/:uuid',
    name: 'personnel.show',
    component: () => import('../pages/Personnel/Show.vue'),
    meta: { requiresAuth: true, title: 'Profil Personel' },
  },
  {
    path: '/evidence',
    name: 'evidence.index',
    component: () => import('../pages/Evidence/Index.vue'),
    meta: { requiresAuth: true, title: 'Evidence Vault' },
  },
  {
    path: '/reports',
    name: 'reports.index',
    component: () => import('../pages/Reports/Index.vue'),
    meta: { requiresAuth: true, title: 'Laporan Resmi' },
  },
  {
    path: '/takedown/dashboard',
    name: 'takedown.dashboard',
    component: () => import('../pages/Takedown/Dashboard.vue'),
    meta: { requiresAuth: true, title: 'Dashboard Takedown & Insiden' },
  },
  {
    path: '/takedown/cases',
    name: 'takedown.cases.index',
    component: () => import('../pages/Takedown/Cases/Index.vue'),
    meta: { requiresAuth: true, title: 'Daftar Kasus Takedown' },
  },
  {
    path: '/takedown/cases/create',
    name: 'takedown.cases.create',
    component: () => import('../pages/Takedown/Cases/Create.vue'),
    meta: { requiresAuth: true, title: 'Buat Kasus Takedown' },
  },
  {
    path: '/takedown/cases/:id',
    name: 'takedown.cases.show',
    component: () => import('../pages/Takedown/Cases/Show.vue'),
    meta: { requiresAuth: true, title: 'Rincian Kasus Takedown' },
  },
  {
    path: '/takedown/providers',
    name: 'takedown.providers.index',
    component: () => import('../pages/Takedown/Providers/Index.vue'),
    meta: { requiresAuth: true, title: 'Direktori Provider & Otoritas' },
  },
  {
    path: '/takedown/defensive-actions',
    name: 'takedown.defensive.index',
    component: () => import('../pages/Takedown/Defensive/Index.vue'),
    meta: { requiresAuth: true, title: 'Aksi Defensif & Mitigasi Internal' },
  },
  {
    path: '/security',
    name: 'security.dashboard',
    component: () => import('../pages/Security/Dashboard.vue'),
    meta: { requiresAuth: true, requiresAdmin: true, title: 'Keamanan Sistem' },
  },
  {
    path: '/security/audit-logs',
    name: 'security.audit-logs',
    component: () => import('../pages/Security/AuditLogs.vue'),
    meta: { requiresAuth: true, requiresAdmin: true, title: 'Audit Trail Sistem' },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('../pages/Profile/Index.vue'),
    meta: { requiresAuth: true, title: 'Profil & Keamanan 2FA' },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('../pages/Settings/Index.vue'),
    meta: { requiresAuth: true, requiresSuperAdmin: true, title: 'Pengaturan Sistem' },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore();

  // Try to load user if token exists but user not loaded yet
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser();
  }

  // Set document title
  if (to.meta.title) {
    document.title = `${to.meta.title} - WebGuard Investigasi`;
  }

  // Handle protected routes
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      return next({ name: 'login', query: { redirect: to.fullPath } });
    }

    if (to.meta.requiresSuperAdmin && !authStore.isSuperAdmin) {
      return next({ name: 'dashboard' });
    }

    if (to.meta.requiresAdmin && !authStore.isAdmin) {
      return next({ name: 'dashboard' });
    }
  }

  // Handle guest routes
  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: 'dashboard' });
  }

  next();
});

export default router;
