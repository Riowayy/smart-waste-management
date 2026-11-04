import { useCallback, useRef, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import ReportImage from '../../components/report-image';
import { SymbolView, type AndroidSymbol, type SFSymbol } from 'expo-symbols';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Link, useFocusEffect } from 'expo-router';
import { ApiError } from '../../services/api/client';
import { useAuth } from '../auth/provider';
import { categoryLabel } from '../reporting/history-data';
import { formatReportDate, getDashboard, statusLabels, type Dashboard, type RecentReport, type ReportStatus } from './service';

const palette = {
  canvas: '#E7FFF3',
  surface: '#FFFFFF',
  forest: '#006C49',
  ink: '#102D23',
  muted: '#63766E',
  line: '#DCEFE5',
  mint: '#CCF5E4',
  pending: '#EFF8C9',
  inProgress: '#B9F0DC',
  completed: '#D2F4E6',
  rejected: '#FFE2DF',
  red: '#B4232E',
  lime: '#A6C84C',
} as const;

const statuses: ReportStatus[] = ['pending', 'in_progress', 'completed', 'rejected'];
const shortStatusLabels: Record<ReportStatus, string> = {
  pending: 'Menunggu', in_progress: 'Diproses', completed: 'Selesai', rejected: 'Ditolak',
};
const statusColors: Record<ReportStatus, { background: string; foreground: string; icon: AndroidSymbol; ios: SFSymbol }> = {
  pending: { background: palette.pending, foreground: '#50640B', icon: 'schedule', ios: 'clock' },
  in_progress: { background: palette.inProgress, foreground: palette.forest, icon: 'local_shipping', ios: 'truck.box.fill' },
  completed: { background: palette.completed, foreground: palette.forest, icon: 'check_circle', ios: 'checkmark.circle.fill' },
  rejected: { background: palette.rejected, foreground: palette.red, icon: 'cancel', ios: 'xmark.circle.fill' },
};

function EcoSymbol({ ios, material, color, size = 20 }: { ios: SFSymbol; material: AndroidSymbol; color: string; size?: number }) {
  return <SymbolView name={{ ios, android: material, web: material }} tintColor={color} size={size} />;
}

function DashboardHeader() {
  return <View style={styles.topBar}>
    <View style={styles.brandGroup}>
      <View style={styles.brandMark}><EcoSymbol ios="leaf" material="eco" color={palette.surface} size={22} /></View>
      <Text style={styles.brand}>EcoTrack</Text>
    </View>
    <Link href="/profile" asChild>
      <Pressable accessibilityRole="button" accessibilityLabel="Buka profil" style={styles.profileButton}>
        <EcoSymbol ios="person.fill" material="person" color={palette.surface} size={20} />
      </Pressable>
    </Link>
  </View>;
}

function StatusBadge({ status }: { status: ReportStatus }) {
  const tone = statusColors[status];
  return <View style={[styles.statusBadge, { backgroundColor: tone.background }]}>
    <Text style={[styles.statusText, { color: tone.foreground }]}>{shortStatusLabels[status] ?? statusLabels[status]}</Text>
  </View>;
}

export function ReportRow({ report }: { report: RecentReport }) {
  const categoryName = categoryLabel(report);
  return <View style={styles.reportCard}>
    <View style={styles.reportTopLine}>
      <StatusBadge status={report.status} />
      <Text style={styles.reportDate} numberOfLines={1}>{formatReportDate(report.created_at)}</Text>
    </View>
    <View style={styles.reportBody}>
      <ReportImage uri={report.image_url} label={`Foto laporan ${categoryName}`} style={styles.reportImage}/>
      <View style={styles.reportCopy}>
        <Text style={styles.reportDescription} numberOfLines={2}>{report.description}</Text>
        <View style={styles.categoryLine}>
          <EcoSymbol ios="tag" material="category" color={palette.muted} size={15} />
          <Text style={styles.category} numberOfLines={1}>{categoryName}</Text>
        </View>
      </View>
    </View>
  </View>;
}

function FilterChip({
  label, count, selected, onPress,
}: { label: string; count: number; selected: boolean; onPress: () => void }) {
  return <Pressable
    accessibilityRole="button"
    accessibilityState={{ selected }}
    onPress={onPress}
    style={[styles.filterChip, selected && styles.filterChipSelected]}
  >
    <Text style={[styles.filterText, selected && styles.filterTextSelected]}>{label} ({count})</Text>
  </Pressable>;
}

export function DashboardContent({ data }: { data: Dashboard }) {
  const [filter, setFilter] = useState<ReportStatus | ''>('');
  const reports = data.recent_reports.slice(0, 5);
  const visibleReports = filter ? reports.filter(report => report.status === filter) : reports;
  return <>
    <View style={styles.sectionHeading}>
      <Text accessibilityRole="header" style={styles.sectionTitle}>Statistik laporan</Text>
      <View style={styles.periodTag}><Text style={styles.periodText}>Semua waktu</Text></View>
    </View>

    <View style={styles.totalCard}>
      <View style={styles.totalHeader}>
        <View style={styles.totalIcon}><EcoSymbol ios="chart.bar.fill" material="bar_chart" color={palette.forest} size={22} /></View>
        <View style={styles.totalLabels}>
          <Text style={styles.totalLabel}>Total laporan Anda</Text>
          <Text style={styles.totalCaption}>Akumulasi laporan yang dikirim</Text>
        </View>
      </View>
      <View style={styles.totalValueLine}>
        <Text style={styles.totalValue}>{data.statistics.total}</Text>
        <Text style={styles.totalUnit}>laporan</Text>
      </View>
    </View>

    <View style={styles.statusGrid}>
      {statuses.map(status => {
        const tone = statusColors[status];
        return <View key={status} style={styles.statusCard}>
          <View style={styles.statusCardHeader}>
            <View style={[styles.statusIcon, { backgroundColor: tone.background }]}>
              <EcoSymbol ios={tone.ios} material={tone.icon} color={tone.foreground} size={19} />
            </View>
            <View style={[styles.statusBadge, { backgroundColor: tone.background }]}>
              <Text style={[styles.statusText, { color: tone.foreground }]}>{shortStatusLabels[status]}</Text>
            </View>
          </View>
          <Text style={styles.statusValue}>{data.statistics[status]}</Text>
          <Text style={styles.statusCaption}>{statusLabels[status]}</Text>
        </View>;
      })}
    </View>

    <View style={styles.recentSection}>
      <View style={styles.recentHeading}>
        <Text accessibilityRole="header" style={styles.sectionTitle}>Laporan terbaru</Text>
        <Link href="/reports" style={styles.viewAll}>Lihat semua <Text style={styles.chevron}>›</Text></Link>
      </View>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>
        <FilterChip label="Semua" count={data.statistics.total} selected={filter === ''} onPress={() => setFilter('')} />
        {statuses.map(status => <FilterChip
          key={status}
          label={shortStatusLabels[status]}
          count={data.statistics[status]}
          selected={filter === status}
          onPress={() => setFilter(status)}
        />)}
      </ScrollView>
      <View style={styles.reportList}>
        {visibleReports.length ? visibleReports.map(report => <ReportRow key={report.id} report={report} />) : <View style={styles.emptyState}>
          <Text style={styles.emptyTitle}>{reports.length ? 'Belum ada laporan terbaru dengan status ini' : 'Belum ada laporan'}</Text>
          <Text style={styles.emptyCopy}>{reports.length ? 'Lima laporan terbaru Anda tidak memuat status tersebut.' : 'Laporan yang Anda kirim akan muncul di sini.'}</Text>
        </View>}
      </View>
    </View>
  </>;
}

const navigation: { href: '/' | '/reports' | '/create-report' | '/profile'; label: string; icon: AndroidSymbol; activeIcon: AndroidSymbol; ios: SFSymbol }[] = [
  { href: '/' as const, label: 'Beranda', icon: 'home', activeIcon: 'home_filled', ios: 'house.fill' as const },
  { href: '/reports' as const, label: 'Riwayat', icon: 'history', activeIcon: 'history', ios: 'clock.arrow.circlepath' as const },
  { href: '/create-report' as const, label: 'Laporkan', icon: 'edit_square', activeIcon: 'edit_square', ios: 'square.and.pencil' as const },
  { href: '/profile' as const, label: 'Profil', icon: 'person', activeIcon: 'person', ios: 'person.fill' as const },
];

export function AppBottomNav({ active = '/' }: { active?: '/' | '/reports' | '/create-report' | '/profile' }) {
  return <View style={styles.bottomNav}>
    {navigation.map(item => {
      const selected = item.href === active;
      return <Link key={item.href} href={item.href} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={item.label} accessibilityState={{ selected }} style={styles.navItem}>
        <EcoSymbol ios={item.ios} material={selected ? item.activeIcon : item.icon} color={selected ? palette.forest : palette.muted} size={21} />
        <Text style={[styles.navLabel, selected && styles.navLabelActive]}>{item.label}</Text>
      </Pressable>
    </Link>;
    })}
  </View>;
}

export default function DashboardScreen() {
  const auth = useAuth();
  const { restore } = auth;
  const [data, setData] = useState<Dashboard | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const request = useRef<AbortController | null>(null);

  const load = useCallback(async () => {
    request.current?.abort();
    const controller = new AbortController();
    request.current = controller;
    setLoading(true);
    setError('');
    try {
      const dashboard = await getDashboard(controller.signal);
      if (!controller.signal.aborted) setData(dashboard);
    } catch (failure) {
      if (controller.signal.aborted) return;
      if (failure instanceof ApiError && (failure.status === 401 || failure.status === 403)) {
        setData(null);
        setError('Sesi tidak tersedia. Silakan periksa sesi kembali.');
        await restore();
      } else setError('Dashboard belum dapat dimuat. Periksa koneksi lalu coba lagi.');
    } finally {
      if (!controller.signal.aborted) setLoading(false);
    }
  }, [restore]);

  useFocusEffect(useCallback(() => {
    void load();
    return () => request.current?.abort();
  }, [load]));

  return <SafeAreaView edges={['top', 'left', 'right', 'bottom']} style={styles.page}>
    <ScrollView
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={loading && data !== null} onRefresh={() => void load()} tintColor={palette.forest} />}
    >
      <DashboardHeader />
      <View style={styles.greeting}>
        <Text accessibilityRole="header" style={styles.greetingTitle}>Halo, {auth.user?.name ?? 'Pengguna'}!</Text>
        <Text style={styles.greetingCopy}>Terima kasih telah berkontribusi menjaga kebersihan dan kelestarian lingkungan.</Text>
      </View>

      {loading && !data ? <View style={styles.stateCard}>
        <ActivityIndicator color={palette.forest} />
        <Text style={styles.stateCopy}>Memuat statistik dan laporan...</Text>
      </View> : null}
      {error ? <View style={styles.errorCard}>
        <Text accessibilityRole="alert" style={styles.errorText}>{error}</Text>
        {data ? <Text style={styles.errorHint}>Data sebelumnya tetap ditampilkan.</Text> : null}
        <Pressable accessibilityRole="button" accessibilityLabel="Coba lagi memuat dashboard" disabled={loading} onPress={() => void load()} style={styles.retryButton}>
          <Text style={styles.retryText}>{loading ? 'Memuat...' : 'Coba lagi'}</Text>
        </Pressable>
      </View> : null}
      {data ? <DashboardContent data={data} /> : null}
    </ScrollView>
    <AppBottomNav />
  </SafeAreaView>;
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: palette.canvas },
  content: { flexGrow: 1, width: '100%', maxWidth: 520, alignSelf: 'center', paddingHorizontal: 18, paddingTop: 8, paddingBottom: 18, gap: 12 },
  topBar: { minHeight: 48, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  brandGroup: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  brandMark: { width: 42, height: 42, alignItems: 'center', justifyContent: 'center', borderRadius: 14, backgroundColor: palette.forest },
  brand: { color: palette.forest, fontSize: 21, fontWeight: '700', letterSpacing: -0.45 },
  profileButton: { width: 42, height: 42, alignItems: 'center', justifyContent: 'center', borderRadius: 21, backgroundColor: palette.forest },
  greeting: { gap: 5, paddingTop: 1 },
  greetingTitle: { color: palette.ink, fontSize: 27, lineHeight: 34, fontWeight: '700', letterSpacing: -0.7 },
  greetingCopy: { color: '#52665D', fontSize: 15, lineHeight: 22 },
  sectionHeading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8, marginTop: 2 },
  sectionTitle: { color: palette.ink, fontSize: 19, lineHeight: 25, fontWeight: '700', letterSpacing: -0.25 },
  periodTag: { paddingHorizontal: 11, paddingVertical: 6, borderRadius: 15, backgroundColor: '#CFF4E3' },
  periodText: { color: palette.forest, fontSize: 12, fontWeight: '700' },
  totalCard: { padding: 15, borderRadius: 19, backgroundColor: palette.surface, gap: 15 },
  totalHeader: { flexDirection: 'row', alignItems: 'center', gap: 11 },
  totalIcon: { width: 43, height: 43, alignItems: 'center', justifyContent: 'center', borderRadius: 14, backgroundColor: palette.mint },
  totalLabels: { flex: 1, gap: 2 },
  totalLabel: { color: '#52665D', fontSize: 14, fontWeight: '600' },
  totalCaption: { color: palette.muted, fontSize: 12, fontWeight: '500' },
  totalValueLine: { flexDirection: 'row', alignItems: 'baseline', gap: 8 },
  totalValue: { color: '#111A16', fontSize: 34, lineHeight: 40, fontWeight: '700', fontVariant: ['tabular-nums'] },
  totalUnit: { color: palette.muted, fontSize: 14 },
  statusGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  statusCard: { flexBasis: '47%', flexGrow: 1, minHeight: 110, padding: 13, borderRadius: 19, backgroundColor: palette.surface, gap: 5 },
  statusCardHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 2 },
  statusIcon: { width: 37, height: 37, alignItems: 'center', justifyContent: 'center', borderRadius: 13 },
  statusBadge: { alignSelf: 'flex-start', paddingHorizontal: 9, paddingVertical: 4, borderRadius: 12 },
  statusText: { fontSize: 11, lineHeight: 15, fontWeight: '700' },
  statusValue: { color: '#101613', fontSize: 30, lineHeight: 35, fontWeight: '700', fontVariant: ['tabular-nums'] },
  statusCaption: { color: '#52665D', fontSize: 14, fontWeight: '600' },
  recentSection: { gap: 13, marginTop: 2 },
  recentHeading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  viewAll: { color: palette.forest, fontSize: 14, fontWeight: '700', paddingVertical: 7 },
  chevron: { fontSize: 19, lineHeight: 18 },
  filters: { flexDirection: 'row', alignItems: 'center', gap: 8, paddingRight: 18 },
  filterChip: { minHeight: 34, justifyContent: 'center', paddingHorizontal: 13, borderRadius: 18, backgroundColor: palette.surface },
  filterChipSelected: { backgroundColor: palette.forest },
  filterText: { color: '#53645C', fontSize: 12, fontWeight: '600' },
  filterTextSelected: { color: palette.surface },
  reportList: { gap: 12 },
  reportCard: { padding: 15, borderRadius: 19, backgroundColor: palette.surface, gap: 11 },
  reportTopLine: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  reportDate: { flexShrink: 1, color: '#85918B', fontSize: 12, textAlign: 'right' },
  reportBody: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  reportImage: { width: 66, height: 66, borderRadius: 13, backgroundColor: '#E8F5ED' },
  imageFallback: { alignItems: 'center', justifyContent: 'center' },
  reportCopy: { flex: 1, minWidth: 0, gap: 5 },
  reportDescription: { color: '#171B18', fontSize: 15, lineHeight: 20, fontWeight: '700' },
  categoryLine: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  category: { flexShrink: 1, color: palette.muted, fontSize: 13, lineHeight: 18 },
  emptyState: { padding: 18, borderRadius: 17, backgroundColor: palette.surface, gap: 5 },
  emptyTitle: { color: palette.ink, fontSize: 15, fontWeight: '700' },
  emptyCopy: { color: palette.muted, fontSize: 13, lineHeight: 19 },
  stateCard: { minHeight: 130, alignItems: 'center', justifyContent: 'center', gap: 12, borderRadius: 18, backgroundColor: palette.surface },
  stateCopy: { color: palette.muted, fontSize: 14 },
  errorCard: { padding: 15, borderRadius: 17, backgroundColor: '#FFF4F2', gap: 8 },
  errorText: { color: palette.red, fontSize: 14, lineHeight: 20, fontWeight: '600' },
  errorHint: { color: palette.muted, fontSize: 12, lineHeight: 18 },
  retryButton: { minHeight: 38, alignSelf: 'flex-start', justifyContent: 'center', paddingHorizontal: 13, borderRadius: 12, backgroundColor: palette.surface },
  retryText: { color: palette.forest, fontSize: 13, fontWeight: '700' },
  bottomNav: { minHeight: 65, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-around', borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: palette.line, backgroundColor: palette.canvas, paddingHorizontal: 7 },
  navItem: { minWidth: 65, minHeight: 54, alignItems: 'center', justifyContent: 'center', gap: 3, paddingHorizontal: 5 },
  navLabel: { color: palette.muted, fontSize: 12, lineHeight: 16, fontWeight: '600' },
  navLabelActive: { color: palette.forest, fontWeight: '700' },
});
