import { defineStore } from 'pinia';
import api from '../services/api';
import type { TakedownCase, TakedownProvider, TakedownFollowUp } from '../types';

export const useTakedownStore = defineStore('takedown', {
  state: () => ({
    cases: [] as TakedownCase[],
    currentCase: null as TakedownCase | null,
    providers: [] as TakedownProvider[],
    currentProvider: null as TakedownProvider | null,
    metrics: null as any,
    totalCases: 0,
    currentPage: 1,
    lastPage: 1,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchDashboard() {
      this.loading = true;
      try {
        const res = await api.get('/takedown/dashboard');
        this.metrics = res.data.metrics;
        return res.data.metrics;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Gagal memuat dashboard takedown.';
      } finally {
        this.loading = false;
      }
    },

    async fetchCases(params: { page?: number; search?: string; status?: string; category?: string; priority?: string; overdue_only?: boolean } = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/takedown/cases', { params });
        this.cases = res.data.data;
        this.totalCases = res.data.meta.total;
        this.currentPage = res.data.meta.current_page;
        this.lastPage = res.data.meta.last_page;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Gagal memuat daftar kasus takedown.';
      } finally {
        this.loading = false;
      }
    },

    async fetchCaseDetail(id: string | number) {
      this.loading = true;
      try {
        const res = await api.get(`/takedown/cases/${id}`);
        this.currentCase = res.data.case;
        return res.data.case;
      } finally {
        this.loading = false;
      }
    },

    async createCase(payload: any) {
      const res = await api.post('/takedown/cases', payload);
      return res.data;
    },

    async updateCase(id: string | number, payload: any) {
      const res = await api.put(`/takedown/cases/${id}`, payload);
      if (this.currentCase && this.currentCase.id === Number(id)) {
        this.currentCase = res.data.case;
      }
      return res.data;
    },

    async updateCaseStatus(id: string | number, status: string, note?: string) {
      const res = await api.post(`/takedown/cases/${id}/status`, { status, note });
      if (this.currentCase && this.currentCase.id === Number(id)) {
        this.currentCase = res.data.case;
      }
      return res.data;
    },

    async addFollowUp(id: string | number, formData: FormData | any) {
      const isFormData = formData instanceof FormData;
      const headers = isFormData ? { 'Content-Type': 'multipart/form-data' } : {};
      const res = await api.post(`/takedown/cases/${id}/follow-ups`, formData, { headers });
      if (this.currentCase && this.currentCase.id === Number(id)) {
        this.currentCase = res.data.case;
      }
      return res.data;
    },

    async fetchProviders(params: { type?: string; search?: string; active_only?: boolean } = {}) {
      try {
        const res = await api.get('/takedown/providers', { params });
        this.providers = res.data.providers;
        return res.data.providers;
      } catch (err: any) {
        console.error('Failed to load providers', err);
      }
    },

    async createProvider(payload: any) {
      const res = await api.post('/takedown/providers', payload);
      return res.data;
    },

    async updateProvider(id: number | string, payload: any) {
      const res = await api.put(`/takedown/providers/${id}`, payload);
      return res.data;
    },
  },
});
