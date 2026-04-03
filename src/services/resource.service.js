import api from "./api";

export async function getResourceList(resource, params = {}) {
  const { data } = await api.get(`/${resource}`, { params });
  if (Array.isArray(data)) {
    return data;
  }

  if (Array.isArray(data?.data)) {
    return data.data;
  }

  return [];
}

export async function createResource(resource, payload) {
  const { data } = await api.post(`/${resource}`, payload);
  return data;
}

export async function updateResource(resource, id, payload) {
  const { data } = await api.put(`/${resource}/${id}`, payload);
  return data;
}

export async function deleteResource(resource, id) {
  const { data } = await api.delete(`/${resource}/${id}`);
  return data;
}
