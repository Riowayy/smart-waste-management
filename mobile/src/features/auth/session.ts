import { apiClient, ApiError } from '../../services/api/client';
import { tokenStorage } from '../../services/token-storage';
export interface User { id: number; name: string; email: string; role: 'user' }
export interface Credentials { email: string; password: string; name?: string; password_confirmation?: string }
export const session = {
 async restore(): Promise<User | null> {
  if (!await tokenStorage.getToken()) return null;
  try { return (await apiClient.request<User>('auth/me')).data; }
  catch (error) {
   if (error instanceof ApiError && [401,403].includes(error.status ?? 0)) { await tokenStorage.clearToken(); return null; }
   throw error;
  }
 },
 async authenticate(mode: 'login' | 'register', values: Credentials) {
  const { data } = await apiClient.request<{user: User; token: string}>(`auth/${mode}`, { method: 'POST', authenticated: false, body: {...values} });
  await tokenStorage.setToken(data.token);
  return data.user;
 },
 async logout() {
  try { await apiClient.request('auth/logout', {method: 'POST'}); }
  catch (error) { if (!(error instanceof ApiError) || ![401,403].includes(error.status ?? 0)) throw error; }
  await tokenStorage.clearToken();
 },
};
export function validateCredentials(mode: 'login' | 'register', v: Credentials): Record<string,string[]> {
 const errors: Record<string,string[]> = {};
 if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.email) || v.email.length > 255) errors.email = ['Masukkan email yang valid.'];
 if (!v.password || v.password.length > 255) errors.password = ['Password wajib diisi, maksimal 255 karakter.'];
 if (mode === 'register') {
  if (!v.name?.trim() || v.name.length > 255) errors.name = ['Nama wajib diisi, maksimal 255 karakter.'];
  if (v.password.length < 8) errors.password = ['Password minimal 8 karakter.'];
  if (v.password !== v.password_confirmation) errors.password_confirmation = ['Konfirmasi password tidak cocok.'];
 }
 return errors;
}
