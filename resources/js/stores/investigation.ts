import { defineStore } from 'pinia';
import api from '../services/api';
import type { Investigation } from '../types';

export const useInvestigationStore = defineStore('investigation', {
  state: () => ({
    investigations: [] as Investigation[],
    currentInvestigation: null as Investigation | null,
    total: 0,
    currentPage: 1,
    lastPage: 1,
    loading: false,
    detailLoading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchInvestigations(params: { page?: number; search?: string; status?: string; category?: string } = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/investigations', { params });
        this.investigations = response.data.data;
        this.total = response.data.meta.total;
        this.currentPage = response.data.meta.current_page;
        this.lastPage = response.data.meta.last_page;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Gagal memuat data investigasi.';
      } finally {
        this.loading = false;
      }
    },

    async fetchInvestigationDetail(id: string | number) {
      this.detailLoading = true;
      this.error = null;
      try {
        const response = await api.get(`/investigations/${id}`);
        this.currentInvestigation = response.data.investigation;
        return response.data.investigation;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Gagal memuat rincian investigasi.';
        throw err;
      } finally {
        this.detailLoading = false;
      }
    },

    async createInvestigation(payload: { target_url: string; category?: string; priority?: string; reason?: string }) {
      this.loading = true;
      try {
        const response = await api.post('/investigations', payload);
        return response.data;
      } finally {
        this.loading = false;
      }
    },
  },
});
