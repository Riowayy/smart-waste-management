import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { SymbolView, type AndroidSymbol } from 'expo-symbols';
import ReportImage from '../../components/report-image';
import { AppBottomNav } from '../dashboard/screen';
import { categoryLabel, locationLabel, visibleHistory } from './history-data';
import { Link, useFocusEffect } from 'expo-router';
import { apiClient, ApiError, type ApiPagination } from '../../services/api/client';

import { useAuth } from '../auth/provider';
import { formatReportDate, statusLabels, type RecentReport, type ReportStatus } from '../dashboard/service';
interface Report extends RecentReport {
  image_url: string;
  latitude: number | null;
  longitude: number | null;
  status_history?: { new_status: ReportStatus; note: string | null; changed_by: string | null; created_at: string | null }[];
}
export default function HistoryScreen() {
  const auth = useAuth();
  const [search, setSearch] = useState('');
  const [oldestFirst, setOldestFirst] = useState(false);
  const [notice, setNotice] = useState(false);
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState<ReportStatus | ''>('');
  const [selected, setSelected] = useState<number | null>(null);
  const [reports, setReports] = useState<Report[]>([]);
  const [detail, setDetail] = useState<Report | null>(null);
  const [pagination, setPagination] = useState<ApiPagination>();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [revision, setRevision] = useState(0);
  useFocusEffect(useCallback(() => {
    const controller = new AbortController();
    setLoading(true); setError(''); setDetail(null); setReports([]); setPagination(undefined);
    async function load() {
      try {
        if (selected !== null) {
          const response = await apiClient.request<Report>(`reports/${selected}`, { signal: controller.signal });
          if (!controller.signal.aborted) setDetail(response.data);
        } else {
          const response = await apiClient.request<Report[]>('reports', { query: { page, per_page: 15, status: status || undefined }, signal: controller.signal });
          if (!controller.signal.aborted) { setReports(response.data); setPagination(response.pagination); }
        }
      } catch (failure) {
        if (controller.signal.aborted) return;
        setError('Laporan belum dapat dimuat. Periksa koneksi lalu coba lagi.');
        if (failure instanceof ApiError && [401, 403].includes(failure.status ?? 0)) await auth.restore();
      } finally { if (!controller.signal.aborted) setLoading(false); }
    }
    void load();
    return () => controller.abort();
  }, [page, status, selected, revision]));
  const visible = visibleHistory(reports, search, oldestFirst);
  function button(label: string, onPress: () => void, disabled = false) {
    return <Pressable accessibilityRole="button" disabled={disabled} onPress={onPress} style={[styles.button, disabled && { opacity: 0.45 }]}><Text style={styles.buttonText}>{label}</Text></Pressable>;
  }
  function badge(value: ReportStatus) {
    return <View style={[styles.badge, { backgroundColor: tones[value] }]}><Text style={[styles.badgeText, value === 'rejected' && { color: '#B4232E' }]}>{statusLabels[value]}</Text></View>;
  }
  return <SafeAreaView style={styles.page} edges={['top', 'bottom', 'left', 'right']}>
    <View style={styles.header}>
      <View style={styles.brandGroup}><View style={styles.mark}><Icon name="eco" color="#FFFFFF" /></View><Text style={styles.brand}>EcoTrack</Text></View>
      <View style={styles.brandGroup}>
        <Pressable accessibilityRole="button" accessibilityLabel="Informasi notifikasi" onPress={() => setNotice(!notice)} style={styles.iconButton}><Icon name="notifications" /></Pressable>
        <Link href="/profile" asChild><Pressable accessibilityRole="link" accessibilityLabel="Buka profil" style={styles.avatar}><Icon name="person" color="#FFFFFF" /></Pressable></Link>
      </View>
    </View>
    <ScrollView keyboardShouldPersistTaps="handled" contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={() => setRevision(value => value + 1)} tintColor="#006C49" />}>
      {notice && <Text accessibilityRole="alert" style={styles.hint}>Notifikasi belum tersedia. Pantau perubahan status melalui detail laporan.</Text>}
      <Pressable accessibilityRole="button" accessibilityLabel="Perbarui riwayat laporan" disabled={loading} onPress={() => setRevision(value => value + 1)} style={styles.refreshButton}><Text style={styles.refreshHint}>Tarik untuk memuat ulang</Text><Icon name="refresh" size={16} /></Pressable>
      <View style={styles.heading}><Text accessibilityRole="header" style={styles.title}>{selected === null ? 'Riwayat Laporan' : 'Detail Laporan'}</Text><Text style={styles.subtitle}>{selected === null ? 'Pantau seluruh laporan sampah Anda.' : `Laporan #ECO-${selected}`}</Text></View>
      {selected === null && <>
        <View style={styles.search}><Icon name="search" /><TextInput accessibilityLabel="Cari laporan pada halaman ini" placeholder="Cari lokasi, kategori, atau ID laporan..." placeholderTextColor="#63766E" value={search} onChangeText={setSearch} style={styles.searchInput} returnKeyType="search" />
          {!!search && <Pressable accessibilityRole="button" accessibilityLabel="Hapus pencarian" onPress={() => setSearch('')} style={styles.iconButton}><Icon name="close" /></Pressable>}
        </View>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
          {(['', ...Object.keys(statusLabels)] as (ReportStatus | '')[]).map(value => <Pressable key={value} accessibilityRole="button" accessibilityState={{ selected: status === value }} onPress={() => { setStatus(value); setPage(1); }} style={[styles.chip, status === value && styles.chipActive]}><Text style={[styles.chipText, status === value && styles.chipTextActive]}>{value ? statusLabels[value] : 'Semua'}{status === value && pagination ? `  ${pagination.total}` : ''}</Text></Pressable>)}
        </ScrollView>
        <View style={styles.summary}><Text style={styles.summaryText}>Menampilkan {visible.length} dari {pagination?.total ?? 0} laporan</Text><Pressable accessibilityRole="button" accessibilityLabel={`Urutkan ${oldestFirst ? 'terbaru' : 'terlama'} pada halaman ini`} onPress={() => setOldestFirst(!oldestFirst)} style={styles.sort}><Text style={styles.sortText}>{oldestFirst ? 'Terlama' : 'Terbaru'}</Text><Icon name="swap_vert" size={18} /></Pressable></View>
        <Text style={styles.hint}>Pencarian dan urutan berlaku pada halaman ini. Lokasi berupa koordinat.</Text>
      </>}
      {loading ? <View style={styles.state}><ActivityIndicator color="#006C49" accessibilityLabel="Memuat laporan" /><Text style={styles.subtitle}>Memuat laporan...</Text></View> : error ? <View style={styles.state}><Text accessibilityRole="alert" style={styles.error}>{error}</Text>{button('Coba lagi', () => setRevision(value => value + 1))}</View> : selected === null ? <>
        {!visible.length && <View style={styles.state}><Icon name="history" size={32} /><Text style={styles.emptyTitle}>{search ? 'Tidak ada hasil pada halaman ini' : 'Belum ada laporan untuk filter ini'}</Text><Text style={styles.subtitle}>{search ? 'Coba kata lain atau periksa halaman berikutnya.' : 'Laporan yang Anda kirim akan tampil di sini.'}</Text></View>}
        {visible.map(report => <Pressable key={report.id} accessibilityRole="button" accessibilityLabel={`Buka detail laporan #${report.id}, ${statusLabels[report.status]}`} onPress={() => setSelected(report.id)} style={({ pressed }) => [styles.card, pressed && { opacity: 0.75 }]}>
          <View style={styles.cardBody}>
            <ReportImage uri={report.image_url} label={`Foto laporan #${report.id}`} style={styles.photo}/>
            <View style={styles.copy}>
              <View style={styles.tags}><View style={styles.category}><Text numberOfLines={1} style={styles.categoryText}>{categoryLabel(report)}</Text></View>{badge(report.status)}</View>
              <Text style={styles.id}>#ECO-{report.id}</Text>
              <Text numberOfLines={2} style={styles.cardTitle}>{report.description}</Text>
              <View style={styles.location}><Icon name="location_on" size={16} /><Text numberOfLines={1} style={styles.locationText}>{locationLabel(report)}</Text><Icon name="chevron_right" size={18} /></View>
            </View>
          </View>
          <View style={styles.footer}><Icon name="schedule" size={16} /><Text style={styles.date}>{formatReportDate(report.created_at)}</Text></View>
        </Pressable>)}
        {!!pagination && <View style={styles.pagination}>{button('Sebelumnya', () => setPage(page - 1), page <= 1 || loading)}<Text style={styles.hint}>{page} / {pagination.last_page}</Text>{button('Berikutnya', () => setPage(page + 1), page >= pagination.last_page || loading)}</View>}
      </> : detail && <View style={styles.card}>
        <View style={styles.tags}>{badge(detail.status)}<Text style={styles.categoryText}>{categoryLabel(detail)}</Text></View>
        <Text style={styles.subtitle}>{detail.description}</Text>
        <ReportImage uri={detail.image_url} label="Foto laporan" contain style={styles.detailPhoto}/>
        <Text style={styles.subtitle}>{formatReportDate(detail.created_at)}</Text>
        <Text style={styles.subtitle}>Lokasi: {locationLabel(detail)}</Text>
        <Text accessibilityRole="header" style={styles.emptyTitle}>Perubahan status</Text>
        {!detail.status_history?.length && <Text style={styles.subtitle}>Belum ada perubahan status.</Text>}
        {detail.status_history?.map((history, index) => <View key={index} style={styles.footer}>
          <View style={styles.copy}><Text style={styles.emptyTitle}>{statusLabels[history.new_status]}</Text><Text style={styles.hint}>{formatReportDate(history.created_at)}{history.changed_by ? ` · ${history.changed_by}` : ''}</Text>{!!history.note && <Text style={styles.subtitle}>{history.note}</Text>}</View>
        </View>)}
      </View>}
      {selected !== null && button('Kembali ke riwayat', () => setSelected(null))}
    </ScrollView>
    <AppBottomNav active="/reports" />
  </SafeAreaView>;
}

const tones: Record<ReportStatus, string> = { pending: '#EFF8C9', in_progress: '#B9F0DC', completed: '#CCF5E4', rejected: '#FFE2DF' };
function Icon({ name, color = '#006C49', size = 22 }: { name: AndroidSymbol; color?: string; size?: number }) {
  const ios = { eco: 'leaf', notifications: 'bell', person: 'person', search: 'magnifyingglass', close: 'xmark', swap_vert: 'arrow.up.arrow.down', history: 'clock.arrow.circlepath', image: 'photo', location_on: 'mappin', chevron_right: 'chevron.right', schedule: 'clock', refresh: 'arrow.clockwise' } as const;
  return <SymbolView name={{ ios: ios[name as keyof typeof ios] ?? 'leaf', android: name, web: name }} tintColor={color} size={size} />;
}
const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: '#E7FFF3' },
  header: { width: '100%', maxWidth: 520, alignSelf: 'center', paddingHorizontal: 18, paddingVertical: 12, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  brandGroup: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  mark: { width: 40, height: 40, borderRadius: 14, backgroundColor: '#006C49', alignItems: 'center', justifyContent: 'center' },
  brand: { fontSize: 21, fontWeight: '700', color: '#102D23' },
  avatar: { width: 42, height: 42, borderRadius: 22, backgroundColor: '#006C49', alignItems: 'center', justifyContent: 'center' },
  iconButton: { minWidth: 44, minHeight: 44, alignItems: 'center', justifyContent: 'center' },
  content: { width: '100%', maxWidth: 520, alignSelf: 'center', paddingHorizontal: 18, paddingBottom: 24, gap: 16 },
  refreshHint: { textAlign: 'center', color: '#63766E', fontSize: 12 },
  refreshButton: { minHeight: 44, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6 },
  heading: { gap: 5 },
  title: { fontSize: 26, lineHeight: 33, fontWeight: '700', letterSpacing: -0.6, color: '#102D23' },
  subtitle: { fontSize: 14, lineHeight: 21, color: '#52665D' },
  search: { flexDirection: 'row', alignItems: 'center', gap: 8, borderRadius: 16, backgroundColor: '#FFFFFF', paddingLeft: 14, paddingRight: 5, minHeight: 50 },
  searchInput: { flex: 1, minWidth: 0, paddingVertical: 14, fontSize: 14, color: '#102D23' },
  filters: { gap: 8 },
  chip: { backgroundColor: '#FFFFFF', minHeight: 44, borderRadius: 24, paddingHorizontal: 16, justifyContent: 'center' },
  chipActive: { backgroundColor: '#006C49' },
  chipText: { color: '#102D23', fontSize: 14, fontWeight: '600' },
  chipTextActive: { color: '#FFFFFF' },
  summary: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  summaryText: { flex: 1, color: '#52665D', fontSize: 12, fontWeight: '600' },
  sort: { minHeight: 44, flexDirection: 'row', alignItems: 'center', gap: 4 },
  sortText: { fontSize: 12, color: '#006C49', fontWeight: '600' },
  hint: { color: '#52665D', fontSize: 12, lineHeight: 18 },
  card: { backgroundColor: '#FFFFFF', borderRadius: 18, padding: 15, gap: 14 },
  cardBody: { flexDirection: 'row', gap: 12 },
  photo: { width: 80, height: 96, borderRadius: 10, backgroundColor: '#EEF9F3' },
  fallback: { alignItems: 'center', justifyContent: 'center' },
  copy: { flex: 1, minWidth: 0, gap: 4 },
  tags: { flexDirection: 'row', flexWrap: 'wrap', gap: 5, alignItems: 'center' },
  category: { maxWidth: '100%', borderRadius: 12, backgroundColor: '#D8F6E8', paddingHorizontal: 8, paddingVertical: 3 },
  categoryText: { color: '#315B49', fontSize: 11, fontWeight: '600' },
  badge: { borderRadius: 12, paddingHorizontal: 8, paddingVertical: 3 },
  badgeText: { color: '#315B49', fontSize: 11, fontWeight: '600' },
  id: { fontSize: 12, color: '#52665D' },
  cardTitle: { fontSize: 17, lineHeight: 22, fontWeight: '600', color: '#102D23' },
  location: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 2 },
  locationText: { flex: 1, fontSize: 12, color: '#52665D' },
  footer: { flexDirection: 'row', alignItems: 'center', gap: 7, backgroundColor: '#EFFBF5', borderRadius: 10, padding: 10 },
  date: { flex: 1, color: '#52665D', fontSize: 12 },
  detailPhoto: { width: '100%', height: 240, borderRadius: 12 },
  state: { padding: 22, borderRadius: 18, backgroundColor: '#FFFFFF', gap: 12, alignItems: 'center' },
  emptyTitle: { color: '#102D23', fontSize: 15, fontWeight: '600' },
  error: { color: '#B4232E', fontSize: 14, lineHeight: 21 },
  button: { minHeight: 44, justifyContent: 'center', paddingHorizontal: 12, borderRadius: 12, backgroundColor: '#CCF5E4' },
  buttonText: { color: '#006C49', fontWeight: '600', fontSize: 13 },
  pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
});
