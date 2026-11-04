import type { RecentReport } from '../dashboard/service';

export interface HistoryReport extends RecentReport {
  latitude: number | null;
  longitude: number | null;
}

const categoryNames: Record<string, string> = {
  'organic-waste': 'Sampah Organik',
  'plastic-waste': 'Sampah Plastik',
  'paper-waste': 'Sampah Kertas',
};

export function categoryLabel(report: RecentReport): string {
  return categoryNames[report.category?.slug ?? ''] ?? report.category?.name ?? 'Tanpa kategori';
}

export function locationLabel(report: HistoryReport): string {
  return report.latitude !== null && report.longitude !== null
    ? `${Number(report.latitude).toFixed(5)}, ${Number(report.longitude).toFixed(5)}`
    : 'Lokasi tidak tersedia';
}

// API belum mendukung pencarian/pengurutan: hanya olah halaman yang telah dimuat.
export function visibleHistory(reports: HistoryReport[], search: string, oldestFirst: boolean): HistoryReport[] {
  const query = search.trim().toLocaleLowerCase('id-ID');
  const timestamp = (value: string | null) => value ? Date.parse(value) || 0 : 0;
  return reports.filter(report => [
    `#${report.id}`, `#ECO-${report.id}`, categoryLabel(report), report.category?.name ?? '',
    report.description, locationLabel(report),
  ].some(value => value.toLocaleLowerCase('id-ID').includes(query)))
    .sort((a, b) => (oldestFirst ? 1 : -1) * (timestamp(a.created_at) - timestamp(b.created_at) || a.id - b.id));
}
