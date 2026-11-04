import { apiClient } from '../../services/api/client';
import type { Draft } from './service';
import type { HistoryReport } from './history-data';

interface Attempt { draft: Draft; baseline: number }
// Mempertahankan penguncian selama sesi aplikasi, termasuk ketika berpindah route.
const attempts = new Map<number, Attempt>();
export const pendingReport = {
  get: (userId: number) => attempts.get(userId),
  set: (userId: number, attempt: Attempt) => { attempts.set(userId, attempt); },
  clear: (userId: number) => { attempts.delete(userId); },
};
export async function reportBaseline(): Promise<number> {
  const response = await apiClient.request<HistoryReport[]>('reports', { query: { per_page: 1 } });
  return response.data[0]?.id ?? 0;
}
export async function findSubmittedReports(attempt: Attempt): Promise<HistoryReport[]> {
  const matches: HistoryReport[] = [];
  // Membatasi pembacaan; hasil kosong bukan bukti bahwa POST tidak akan selesai.
  for (let page = 1; page <= 5; page++) {
    const response = await apiClient.request<HistoryReport[]>('reports', { query: { page, per_page: 50 } });
    for (const report of response.data) {
      if (report.id > attempt.baseline && report.description === attempt.draft.description.trim() &&
          (report.category?.id ?? null) === attempt.draft.categoryId &&
          attempt.draft.location && report.latitude !== null && report.longitude !== null &&
          Math.abs(Number(report.latitude) - attempt.draft.location.latitude) < 0.000001 &&
          Math.abs(Number(report.longitude) - attempt.draft.location.longitude) < 0.000001) matches.push(report);
    }
    if (!response.pagination || page >= response.pagination.last_page || response.data.some(report => report.id <= attempt.baseline)) break;
  }
  return matches;
}
