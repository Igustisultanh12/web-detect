import { defineStore } from 'pinia';
import api from '../services/api';
import type { DefensiveAction } from '../types';

export const useDefensiveStore = defineStore('defensive', {
  state: () => ({
    actions: [] as DefensiveAction[],
    currentAction: null as DefensiveAction | null,
    totalActions: 0,
    currentPage: 1,
    lastPage: 1,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchActions(params: { page?: number; search?: string; rule_type?: string; status?: string } = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get('/defensive-actions', { params });
        this.actions = res.data.data;
        this.totalActions = res.data.meta.total;
        this.currentPage = res.data.meta.current_page;
        this.lastPage = res.data.meta.last_page;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Gagal memuat aksi defensif.';
      } finally {
        this.loading = false;
      }
    },

    async fetchActionDetail(id: string | number) {
      this.loading = true;
      try {
        const res = await api.get(`/defensive-actions/${id}`);
        this.currentAction = res.data.action;
        return res.data.action;
      } finally {
        this.loading = false;
      }
    },

    async createAction(payload: any) {
      const res = await api.post('/defensive-actions', payload);
      return res.data;
    },

    async reviewAction(id: string | number) {
      const res = await api.post(`/defensive-actions/${id}/review`);
      if (this.currentAction && this.currentAction.id === Number(id)) {
        this.currentAction = res.data.action;
      }
      return res.data;
    },

    async approveAction(id: string | number) {
      const res = await api.post(`/defensive-actions/${id}/approve`);
      if (this.currentAction && this.currentAction.id === Number(id)) {
        this.currentAction = res.data.action;
      }
      return res.data;
    },

    async rejectAction(id: string | number, reason: string) {
      const res = await api.post(`/defensive-actions/${id}/reject`, { reason });
      if (this.currentAction && this.currentAction.id === Number(id)) {
        this.currentAction = res.data.action;
      }
      return res.data;
    },

    async deployAction(id: string | number) {
      const res = await api.post(`/defensive-actions/${id}/deploy`);
      if (this.currentAction && this.currentAction.id === Number(id)) {
        this.currentAction = res.data.action;
      }
      return res.data;
    },

    async toggleChecklist(id: string | number, checklistId: number) {
      const res = await api.post(`/defensive-actions/${id}/checklists/${checklistId}/toggle`);
      if (this.currentAction && this.currentAction.checklists) {
        const idx = this.currentAction.checklists.findIndex(c => c.id === checklistId);
        if (idx !== -1) {
          this.currentAction.checklists[idx] = res.data.checklist_item;
        }
      }
      return res.data;
    },
  },
});
