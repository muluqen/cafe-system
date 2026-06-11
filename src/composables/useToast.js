import { reactive } from 'vue';

const state = reactive({
  toasts: [],
});

let nextId = 0;

/**
 * Add a toast notification.
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {string} message
 * @param {number} duration - Auto dismiss in ms
 * @param {{ label: string, onClick: Function }} [action] - Optional action button
 */
function addToast(type, message, duration = 2500, action = null) {
  const id = nextId++;
  state.toasts.push({ id, type, message, duration, action });

  if (duration > 0) {
    setTimeout(() => remove(id), duration);
  }

  return id;
}

/**
 * Remove a toast by id.
 * @param {number} id
 */
function remove(id) {
  const idx = state.toasts.findIndex(t => t.id === id);
  if (idx !== -1) {
    state.toasts.splice(idx, 1);
  }
}

/**
 * Composable for toast notifications.
 */
export function useToast() {
  return {
    toasts: state.toasts,

    success: (message, duration, action) => addToast('success', message, duration ?? 2500, action),
    error: (message, duration, action) => addToast('error', message, duration ?? 4000, action),
    warning: (message, duration, action) => addToast('warning', message, duration ?? 3000, action),
    info: (message, duration, action) => addToast('info', message, duration ?? 2500, action),

    add: addToast,
    remove,
  };
}
