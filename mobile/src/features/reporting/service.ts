import { Platform } from 'react-native';
import { File as NativeFile } from 'expo-file-system';
import type { ImagePickerAsset } from 'expo-image-picker';
import { apiClient, ApiError } from '../../services/api/client';
export interface Category { id: number; name: string }
export interface Coordinates { latitude: number; longitude: number }
export interface Draft { description: string; categoryId: number | null; photo: ImagePickerAsset | null; location: Coordinates | null }
export const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
export function validatePhoto(photo: ImagePickerAsset): string | null {
  const type = photo.file?.type || photo.mimeType;
  if (!type || !['image/jpeg', 'image/png', 'image/webp'].includes(type)) return 'Pilih foto JPEG, PNG, atau WebP.';
  const size = photo.file?.size ?? photo.fileSize;
  if (size === undefined || !Number.isFinite(size) || size <= 0) return 'Ukuran foto tidak dapat diperiksa. Pilih foto lain.';
  if (size > MAX_IMAGE_BYTES) return 'Foto maksimal 5 MB.';
  return null;
}
export function validateDraft(draft: Draft): Record<string, string[]> {
  const errors: Record<string, string[]> = {};
  if (!draft.description.trim() || draft.description.length > 1000) errors.description = ['Deskripsi wajib diisi, maksimal 1.000 karakter.'];
  if (!draft.photo) errors.image = ['Tambahkan foto sampah.'];
  else { const error = validatePhoto(draft.photo); if (error) errors.image = [error]; }
  if (draft.location && (!Number.isFinite(draft.location.latitude) || Math.abs(draft.location.latitude) > 90 || !Number.isFinite(draft.location.longitude) || Math.abs(draft.location.longitude) > 180)) errors.latitude = ['Koordinat lokasi tidak valid.'];
  return errors;
}
export async function getCategories(signal?: AbortSignal) {
  return (await apiClient.request<Category[]>('categories', { signal })).data;
}
export function createReportBody(draft: Draft): FormData {
  if (Object.keys(validateDraft(draft)).length) throw new Error('Periksa data laporan.');
  const photo = draft.photo!;
  const body = new FormData();
  body.append('description', draft.description.trim());
  if (draft.categoryId !== null) body.append('category_id', String(draft.categoryId));
  if (draft.location) {
    body.append('latitude', String(draft.location.latitude));
    body.append('longitude', String(draft.location.longitude));
  }
  if (Platform.OS === 'web') {
    if (!photo.file) throw new Error('File foto tidak tersedia. Pilih ulang foto.');
    body.append('image', photo.file, photo.file.name);
  } else {
    // Expo SDK 58 memakai expo/fetch secara global. Encoder-nya membaca bytes(),
    // bukan descriptor URI React Native lama. File tetap membaca URI perangkat.
    body.append('image', new NativeFile(photo.uri) as unknown as Blob);
  }
  return body;
}
export async function submitReport(draft: Draft) {
  const { data } = await apiClient.request<{ id: number }>('reports', { method: 'POST', body: createReportBody(draft) });
  if (!data || !Number.isSafeInteger(data.id) || data.id <= 0) {
    throw new ApiError('Konfirmasi laporan tidak valid. Periksa riwayat sebelum mengirim ulang.', 'invalid-response');
  }
  return data;
}
