import { defineStore } from 'pinia';
import api from '../services/api';
import type { User } from '../types';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('webguard_user') || 'null') as User | null,
    token: localStorage.getItem('webguard_token') || null as string | null,
    loading: false,
    error: null as string | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isSuperAdmin: (state) => state.user?.roles?.includes('super_admin') ?? false,
    isAdmin: (state) => {
      const roles = state.user?.roles || [];
      return roles.includes('super_admin') || roles.includes('admin');
    },
    isInvestigator: (state) => {
      const roles = state.user?.roles || [];
      return roles.includes('super_admin') || roles.includes('admin') || roles.includes('investigator');
    },
    hasPermission: (state) => (permission: string) => {
      if (state.user?.roles?.includes('super_admin')) return true;
      return state.user?.permissions?.includes(permission) ?? false;
    },
  },

  actions: {
    async login(payload: { email: string; password: string; two_factor_code?: string; recovery_code?: string }) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/auth/login', payload);
        const data = response.data;

        if (data.requires_two_factor) {
          return { requires_two_factor: true, message: data.message };
        }

        if (data.token && data.user) {
          this.token = data.token;
          this.user = data.user;
          localStorage.setItem('webguard_token', data.token);
          localStorage.setItem('webguard_user', JSON.stringify(data.user));
        }

        return { success: true };
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Login gagal. Periksa koneksi atau kredensial Anda.';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async fetchMe() {
      if (!this.token) return;
      try {
        const response = await api.get('/auth/me');
        if (response.data.success) {
          this.user = response.data.user;
          localStorage.setItem('webguard_user', JSON.stringify(response.data.user));
        }
      } catch (err) {
        console.error('Failed to fetch user', err);
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout');
      } catch (e) {
        // ignore
      } finally {
        this.user = null;
        this.token = null;
        localStorage.removeItem('webguard_token');
        localStorage.removeItem('webguard_user');
      }
    },
  },
});
