import { apiClient } from '../../services/api/client';

export const statusLabels = {
  pending: 'Menunggu', in_progress: 'Sedang diproses', completed: 'Selesai', rejected: 'Ditolak',
} as const;
export type ReportStatus = keyof typeof statusLabels;
export interface RecentReport {
  id: number;
  description: string;
  image_url?: string | null;
  category: { id: number; name: string; slug: string; description: string | null } | null;
  status: ReportStatus;
  created_at: string | null;
}
export interface Dashboard {
  statistics: Record<ReportStatus | 'total', number>;
  recent_reports: RecentReport[];
}
export async function getDashboard(signal?: AbortSignal): Promise<Dashboard> {
  return (await apiClient.request<Dashboard>('dashboard', { signal })).data;
}
export function formatReportDate(value: string | null): string {
  if (!value) return 'Tanggal tidak tersedia';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? 'Tanggal tidak tersedia' : date.toLocaleDateString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric',
  });
}
