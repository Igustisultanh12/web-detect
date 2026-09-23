import { defineStore } from 'pinia';
import api from '../services/api';
import type { User, Rank, Unit, Position, Role } from '../types';

export const usePersonnelStore = defineStore('personnel', {
  state: () => ({
    personnel: [] as User[],
    currentPersonnel: null as User | null,
    ranks: [] as Rank[],
    units: [] as Unit[],
    positions: [] as Position[],
    roles: [] as Role[],
    total: 0,
    currentPage: 1,
    lastPage: 1,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchPersonnel(params: { page?: number; search?: string; status?: string; role?: string; rank_id?: number } = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/users', { params });
        this.personnel = response.data.data;
        this.total = response.data.meta.total;
        this.currentPage = response.data.meta.current_page;
        this.lastPage = response.data.meta.last_page;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Gagal memuat data personel.';
      } finally {
        this.loading = false;
      }
    },

    async fetchPersonnelDetail(uuid: string | number) {
      this.loading = true;
      try {
        const response = await api.get(`/users/${uuid}`);
        this.currentPersonnel = response.data.user;
        return response.data.user;
      } finally {
        this.loading = false;
      }
    },

    async fetchMasterOptions() {
      try {
        const response = await api.get('/master/personnel-options');
        this.ranks = response.data.ranks;
        this.units = response.data.all_units;
        this.positions = response.data.positions;
        this.roles = response.data.roles;
      } catch (e) {
        console.error('Failed to load master options', e);
      }
    },

    async createPersonnel(payload: any) {
      const response = await api.post('/users', payload);
      return response.data;
    },

    async activatePersonnel(uuid: string) {
      const response = await api.post(`/users/${uuid}/activate`);
      return response.data;
    },

    async deactivatePersonnel(uuid: string, reason: string) {
      const response = await api.post(`/users/${uuid}/deactivate`, { reason });
      return response.data;
    },

    async suspendPersonnel(uuid: string, reason: string) {
      const response = await api.post(`/users/${uuid}/suspend`, { reason });
      return response.data;
    },
  },
});
