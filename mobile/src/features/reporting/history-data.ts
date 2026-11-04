import type { RecentReport } from '../dashboard/service';
import type { Coordinates } from './service';

export const HISTORY_PAGE_SIZE = 10;

export interface HistoryReport extends RecentReport {
  latitude: number | null;
  longitude: number | null;
}

const categoryNames: Record<string, string> = {
  'organic-waste': 'Sampah Organik',
  'plastic-waste': 'Sampah Plastik',
  'paper-waste': 'Sampah Kertas',
};

export function categoryLabel(report: { category: { name: string; slug?: string } | null }): string {
  const category = report.category;
  if (!category) return 'Tanpa kategori';
  const key = category.slug ?? category.name.toLowerCase().replace(/\s+/g, '-');
  return categoryNames[key] ?? category.name;
}

export function locationLabel(report: HistoryReport): string {
  const point = reportCoordinates(report);
  return point
    ? `${point.latitude.toFixed(5)}, ${point.longitude.toFixed(5)}`
    : 'Lokasi tidak tersedia';
}

export function reportCoordinates(report: Pick<HistoryReport, 'latitude' | 'longitude'>): Coordinates | null {
  const { latitude, longitude } = report;
  return typeof latitude === 'number' && Number.isFinite(latitude) && Math.abs(latitude) <= 90
    && typeof longitude === 'number' && Number.isFinite(longitude) && Math.abs(longitude) <= 180
    ? { latitude, longitude } : null;
}

export function googleMapsUrl(point: Coordinates): string {
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${point.latitude},${point.longitude}`)}`;
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
