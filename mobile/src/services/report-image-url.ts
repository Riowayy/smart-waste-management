import { getApiBaseUrl } from './api/config';

export function resolveReportImageUrl(value: string | null | undefined): string | null {
  if (!value?.trim()) return null;
  try {
    const api = new URL(getApiBaseUrl());
    const root = api.pathname.replace(/\/api\/v1\/?$/, '');
    const raw = value.trim().replace(/\\/g, '/');
    const url = new URL(raw, `${api.origin}${root}/`);
    if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) return null;
    const storageIndex = url.pathname.indexOf('/storage/');
    if (storageIndex >= 0 && (['localhost', '127.0.0.1', '[::1]', '0.0.0.0'].includes(url.hostname) || !/^https?:\/\//i.test(raw) || url.origin === api.origin)) {
      // APP_URL server dapat menunjuk loopback/root berbeda dari base API LAN.
      return `${api.origin}${root}${url.pathname.slice(storageIndex)}${url.search}`;
    }
    if (['localhost', '127.0.0.1', '[::1]', '0.0.0.0'].includes(url.hostname)) url.host = api.host;
    return url.toString();
  } catch { return null; }
}
