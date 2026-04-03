import { defineStore } from "pinia";
import {
  createResource,
  deleteResource,
  getResourceList,
  updateResource
} from "../services/resource.service";

export const useEntityStore = defineStore("entityStore", {
  state: () => ({
    byEntity: {},
    loading: false,
    error: "",
    saving: false
  }),
  actions: {
    async fetchEntities(entityName, query = "") {
      this.loading = true;
      this.error = "";
      try {
        const params = query ? { search: query } : {};
        const rows = await getResourceList(entityName, params);
        this.byEntity[entityName] = rows;
      } catch (error) {
        this.error =
          error?.response?.data?.message ||
          `Unable to load ${entityName}. Check API route/auth.`;
      } finally {
        this.loading = false;
      }
    },
    async createEntity(entityName, payload) {
      this.saving = true;
      this.error = "";
      try {
        await createResource(entityName, payload);
      } catch (error) {
        this.error = error?.response?.data?.message || "Create failed";
        throw error;
      } finally {
        this.saving = false;
      }
    },
    async updateEntity(entityName, id, payload) {
      this.saving = true;
      this.error = "";
      try {
        await updateResource(entityName, id, payload);
      } catch (error) {
        this.error = error?.response?.data?.message || "Update failed";
        throw error;
      } finally {
        this.saving = false;
      }
    },
    async deleteEntity(entityName, id) {
      this.saving = true;
      this.error = "";
      try {
        await deleteResource(entityName, id);
      } catch (error) {
        this.error = error?.response?.data?.message || "Delete failed";
        throw error;
      } finally {
        this.saving = false;
      }
    }
  }
});
