/**
 * Extract an array from an API response that may be paginated or flat.
 *
 * Backend ApiResponse::success($paginator) returns:
 *   { success: true, data: { current_page, data: [...], ... } }
 *
 * Axios wraps it: res.data = { success: true, data: { ... } }
 *
 * @param {import('axios').AxiosResponse} res - Axios response
 * @returns {Array}
 */
export function extractList(res) {
  const body = res?.data;
  const raw = body?.data;

  if (Array.isArray(raw)) return raw;
  if (Array.isArray(raw?.data)) return raw.data;
  return [];
}
