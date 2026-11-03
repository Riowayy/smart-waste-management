import { tokenStorage } from '../token-storage';
import { getApiBaseUrl } from './config';
import { ApiError, type ApiResponse, type ValidationErrors } from './types';

export { ApiError } from './types';
export type { ApiResponse, ApiPagination, ValidationErrors } from './types';

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  body?: Record<string, unknown> | FormData;
  query?: Record<string, string | number | boolean | null | undefined>;
  authenticated?: boolean;
  signal?: AbortSignal;
  timeoutMs?: number;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function validationErrors(value: unknown): ValidationErrors {
  if (!isRecord(value)) return {};
  return Object.fromEntries(Object.entries(value).filter(
    (entry): entry is [string, string[]] =>
      Array.isArray(entry[1]) && entry[1].every((message) => typeof message === 'string'),
  ));
}

async function request<T>(path: string, options: RequestOptions = {}): Promise<ApiResponse<T>> {
  // Hanya path relatif dalam API; token tidak boleh dikirim ke URL arbitrer.
  if (!/^\/?[a-z\d_-]+(?:\/[a-z\d_-]+)*$/i.test(path)) {
    throw new Error('Path API harus relatif, tanpa query atau segmen traversal.');
  }
  const url = new URL(`${getApiBaseUrl()}/${path.replace(/^\//, '')}`);
  for (const [key, value] of Object.entries(options.query ?? {})) {
    if (value !== null && value !== undefined) url.searchParams.set(key, String(value));
  }

  const timeoutMs = options.timeoutMs ?? 30_000;
  if (!Number.isFinite(timeoutMs) || timeoutMs <= 0) {
    throw new Error('Timeout API harus berupa angka positif.');
  }
  const headers = new Headers({ Accept: 'application/json' });
  if (options.authenticated !== false) {
    const token = await tokenStorage.getToken();
    if (token) headers.set('Authorization', `Bearer ${token}`);
  }

  const multipart = options.body instanceof FormData;
  if (options.body !== undefined && !multipart) headers.set('Content-Type', 'application/json');
  const body = multipart ? options.body as FormData : JSON.stringify(options.body);
  const controller = new AbortController();
  const cancel = () => controller.abort();
  options.signal?.addEventListener('abort', cancel, { once: true });
  if (options.signal?.aborted) cancel();
  let timedOut = false;
  const timer = setTimeout(() => {
    timedOut = true;
    controller.abort();
  }, timeoutMs);

  try {
    const response = await fetch(url.toString(), {
      method: options.method ?? 'GET',
      headers,
      body,
      signal: controller.signal,
      credentials: 'omit',
      redirect: 'error',
    });
    const text = await response.text();
    let payload: unknown;
    try {
      payload = JSON.parse(text);
    } catch {
      throw new ApiError(
        response.ok ? 'Respons server bukan JSON yang valid.' : `Permintaan gagal (HTTP ${response.status}).`,
        response.ok ? 'invalid-response' : 'http',
        response.status,
      );
    }
    if (!response.ok) {
      throw new ApiError(
        isRecord(payload) && typeof payload.message === 'string'
          ? payload.message : `Permintaan gagal (HTTP ${response.status}).`,
        'http', response.status,
        validationErrors(isRecord(payload) ? payload.errors : undefined),
      );
    }
    if (!isRecord(payload) || payload.success !== true ||
        typeof payload.message !== 'string' || !('data' in payload)) {
      throw new ApiError('Format respons API tidak sesuai kontrak.', 'invalid-response', response.status);
    }
    const pagination = payload.pagination;
    if (pagination !== undefined && (
      !isRecord(pagination) ||
      !['current_page', 'per_page', 'total', 'last_page'].every(
        (key) => Number.isInteger(pagination[key]),
      )
    )) {
      throw new ApiError('Format pagination API tidak valid.', 'invalid-response', response.status);
    }
    return payload as unknown as ApiResponse<T>;
  } catch (error) {
    if (options.signal?.aborted) throw new ApiError('Permintaan dibatalkan.', 'cancelled');
    if (timedOut) throw new ApiError('Waktu tunggu API habis. Silakan coba lagi.', 'timeout');
    if (error instanceof ApiError) throw error;
    throw new ApiError('Tidak dapat terhubung ke server. Periksa koneksi dan URL API.', 'network');
  } finally {
    clearTimeout(timer);
    options.signal?.removeEventListener('abort', cancel);
  }
}

export const apiClient = { request };
