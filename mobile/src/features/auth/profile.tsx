import { useCallback, useRef, useState, type ComponentRef } from 'react';
import { ActivityIndicator, Alert, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Link, useFocusEffect } from 'expo-router';
import { SymbolView, type AndroidSymbol, type SFSymbol } from 'expo-symbols';
import { AppBottomNav } from '../dashboard/screen';
import { getDashboard } from '../dashboard/service';
import { ApiError } from '../../services/api/client';
import { useAuth } from './provider';

const colors = { canvas: '#E7FFF3', surface: '#FFFFFF', green: '#006C49', ink: '#102D23', muted: '#52665D', red: '#B4232E' } as const;

export default function ProfileScreen() {
  const auth = useAuth();
  const { restore } = auth;
  const [loggingOut, setLoggingOut] = useState(false);
  const [error, setError] = useState('');
  const [total, setTotal] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [statisticsError, setStatisticsError] = useState(false);
  const [revision, setRevision] = useState(0);
  const [about, setAbout] = useState(false);
  const [notifications, setNotifications] = useState(false);
  const [confirmLogoutOpen, setConfirmLogoutOpen] = useState(false);
  const lock = useRef(false);
  const scroll = useRef<ComponentRef<typeof ScrollView>>(null);
  const accountY = useRef(0);
  const request = useRef<(AbortController & { revision: number }) | null>(null);
  const confirmationLock = useRef(false);

  useFocusEffect(useCallback(() => {
    const controller = Object.assign(new AbortController(), { revision });
    request.current = controller;
    setLoading(true); setStatisticsError(false); setTotal(null);
    void getDashboard(controller.signal).then(data => {
      if (!controller.signal.aborted && request.current?.revision === revision) setTotal(data.statistics.total);
    }).catch(async failure => {
      if (controller.signal.aborted) return;
      setStatisticsError(true);
      if (failure instanceof ApiError && [401, 403].includes(failure.status ?? 0)) await restore();
    }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [revision, restore]));

  async function logout() {
    if (lock.current) return;
    lock.current = true;
    request.current?.abort();
    setLoading(false);
    if (total === null) setStatisticsError(true);
    setLoggingOut(true); setError('');
    try { await auth.logout(); }
    catch { setError('Belum dapat keluar. Periksa koneksi lalu coba lagi.'); }
    finally { lock.current = false; setLoggingOut(false); }
  }
  function cancelLogout() {
    confirmationLock.current = false;
    setConfirmLogoutOpen(false);
  }
  function confirmLogout() {
    cancelLogout();
    void logout();
  }
  function requestLogout() {
    if (lock.current || confirmationLock.current) return;
    confirmationLock.current = true;
    if (Platform.OS === 'web') { setConfirmLogoutOpen(true); return; }
    Alert.alert('Keluar dari akun?', 'Apakah Anda yakin ingin keluar?', [
      { text: 'Tidak', style: 'cancel', onPress: cancelLogout },
      { text: 'Ya', style: 'destructive', onPress: confirmLogout },
    ], { cancelable: true, onDismiss: cancelLogout });
  }
  const initials = auth.user?.name.trim().split(/\s+/).slice(0, 2).map(part => Array.from(part)[0]).join('').toLocaleUpperCase('id-ID') || 'E';

  return <SafeAreaView style={styles.page} edges={['top', 'left', 'right', 'bottom']}>
    <View style={styles.header}>
      <Link href="/" asChild><Pressable accessibilityRole="link" accessibilityLabel="EcoTrack, buka beranda" disabled={loggingOut} style={styles.brandGroup}><View style={styles.mark}><Icon name="eco" ios="leaf" /></View><View><Text style={styles.brand}>EcoTrack</Text><Text style={styles.caption}>Profil Pengguna EcoTrack</Text></View></Pressable></Link>
      <Pressable accessibilityRole="button" accessibilityLabel="Informasi notifikasi" accessibilityState={{expanded:notifications}} onPress={() => setNotifications(!notifications)} style={styles.iconButton}><Icon name="notifications" ios="bell" size={25}/></Pressable>
    </View>
    <ScrollView ref={scroll} contentContainerStyle={styles.content}>
      {notifications && <Text accessibilityRole="alert" style={styles.copy}>Notifikasi belum tersedia. Periksa perubahan status pada detail laporan.</Text>}
      <View style={styles.profileCard}>
        <View accessibilityLabel={`Inisial ${auth.user?.name ?? 'pengguna'}`} style={styles.avatar}><Text style={styles.initials}>{initials}</Text></View>
        <Text accessibilityRole="header" style={styles.name}>{auth.user?.name ?? 'Pengguna'}</Text>
        <Text style={styles.email}>{auth.user?.email ?? ''}</Text>
        <View style={styles.badge}><Icon name="eco" ios="leaf.fill" size={16}/><Text style={styles.badgeText}>Pengguna EcoTrack</Text></View>
        <View style={styles.summary}>
          {loading ? <><ActivityIndicator color={colors.green} accessibilityLabel="Memuat jumlah laporan"/><Text style={styles.copy}>Memuat jumlah laporan...</Text></> : statisticsError ? <><Text style={styles.copy}>Jumlah laporan belum dapat dimuat.</Text><Pressable accessibilityRole="button" onPress={() => setRevision(value => value + 1)} style={styles.retry}><Text style={styles.linkText}>Coba lagi</Text></Pressable></> : <><Text style={styles.total}>{total}</Text><Text style={styles.copy}>Laporan Anda</Text></>}
        </View>
      </View>

      <View onLayout={event => { accountY.current = event.nativeEvent.layout.y; }} style={styles.section}>
        <View style={styles.sectionHeading}><Text accessibilityRole="header" style={styles.sectionTitle}>Informasi Akun</Text><Text style={styles.copy}>Identitas dari akun EcoTrack Anda.</Text></View>
        <View style={styles.card}>
          <View style={styles.infoRow}><View style={styles.rowIcon}><Icon name="person" ios="person"/></View><View style={styles.rowCopy}><Text style={styles.label}>Nama Lengkap</Text><Text selectable style={styles.value}>{auth.user?.name ?? 'Nama belum tersedia'}</Text></View></View>
          <View style={styles.infoRow}><View style={styles.rowIcon}><Icon name="mail" ios="envelope"/></View><View style={styles.rowCopy}><Text style={styles.label}>Email Terdaftar</Text><Text selectable style={styles.value}>{auth.user?.email ?? 'Email belum tersedia'}</Text></View></View>
        </View>
      </View>

      <View style={styles.section}>
        <View style={styles.sectionHeading}><Text accessibilityRole="header" style={styles.sectionTitle}>Menu & Pengaturan</Text><Text style={styles.copy}>Akses riwayat dan informasi aplikasi.</Text></View>
        <View style={styles.menuCard}>
          <Link href="/reports" asChild><Pressable accessibilityRole="link" disabled={loggingOut} style={styles.menuRow}><View style={styles.rowIcon}><Icon name="history" ios="clock.arrow.circlepath"/></View><View style={styles.rowCopy}><Text style={styles.menuTitle}>Riwayat Laporan</Text><Text style={styles.copy}>{total !== null ? `${total} laporan sampah tersimpan` : 'Pantau laporan yang Anda kirim'}</Text></View><Icon name="chevron_right" ios="chevron.right" size={18}/></Pressable></Link>
          <Pressable accessibilityRole="button" onPress={() => scroll.current?.scrollTo({y:accountY.current,animated:true})} style={styles.menuRow}><View style={styles.rowIcon}><Icon name="badge" ios="person.text.rectangle"/></View><View style={styles.rowCopy}><Text style={styles.menuTitle}>Informasi Akun</Text><Text style={styles.copy}>Lihat identitas akun Anda</Text></View><Icon name="chevron_right" ios="chevron.right" size={18}/></Pressable>
          <Pressable accessibilityRole="button" accessibilityState={{expanded:about}} onPress={() => setAbout(!about)} style={[styles.menuRow,styles.aboutRow]}><View style={styles.rowIcon}><Icon name="eco" ios="leaf"/></View><View style={styles.rowCopy}><Text style={styles.menuTitle}>Tentang EcoTrack</Text><Text style={styles.copy}>Sistem manajemen sampah cerdas</Text></View><Icon name={about?'expand_less':'chevron_right'} ios={about?'chevron.up':'chevron.right'} size={18}/></Pressable>
          {about && <Text style={styles.aboutCopy}>EcoTrack membantu Anda melaporkan temuan sampah melalui foto dan lokasi, serta memantau perkembangan laporan yang dikelola petugas.</Text>}
          <View accessibilityLabel="Bantuan dan Dukungan, belum tersedia" style={styles.menuRow}><View style={styles.rowIcon}><Icon name="help_outline" ios="questionmark.square"/></View><View style={styles.rowCopy}><Text style={styles.menuTitle}>Bantuan & Dukungan</Text><Text style={styles.copy}>Pusat bantuan belum tersedia</Text></View></View>
        </View>
      </View>
      {!!error && <Text accessibilityRole="alert" style={styles.error}>{error}</Text>}
      <Pressable accessibilityRole="button" accessibilityState={{ busy: loggingOut, disabled: loggingOut }} disabled={loggingOut} onPress={requestLogout} style={({pressed}) => [styles.logoutButton, {opacity:loggingOut || pressed?0.65:1}]}>
        {loggingOut ? <ActivityIndicator color={colors.red}/> : <Icon name="logout" ios="rectangle.portrait.and.arrow.right" color={colors.red}/>}
        <Text style={styles.logoutText}>{loggingOut ? 'Keluar...' : 'Keluar dari Akun'}</Text>
      </Pressable>
      {confirmLogoutOpen && <View accessibilityRole="alert" style={styles.card}>
        <Text style={styles.copy}>Apakah Anda yakin ingin keluar?</Text>
        <View style={styles.infoRow}>
          <Pressable accessibilityRole="button" accessibilityLabel="Tidak" onPress={cancelLogout} style={[styles.retry,styles.confirmAction]}><Text style={styles.linkText}>Tidak</Text></Pressable>
          <Pressable accessibilityRole="button" accessibilityLabel="Ya" onPress={confirmLogout} style={[styles.logoutButton,styles.confirmAction]}><Text style={styles.logoutText}>Ya</Text></Pressable>
        </View>
      </View>}
      <Text style={styles.footer}>EcoTrack · Pelaporan sampah dan pemantauan status</Text>
    </ScrollView>
    {!loggingOut && <AppBottomNav active="/profile" />}
  </SafeAreaView>;
}
function Icon({name,ios,color=colors.green,size=21}:{name:AndroidSymbol;ios:SFSymbol;color?:string;size?:number}) {
  return <SymbolView name={{ios,android:name,web:name}} tintColor={color} size={size}/>;
}
const styles = StyleSheet.create({
  confirmAction:{flex:1,alignItems:'center'},
  page:{flex:1,backgroundColor:colors.canvas},
  header:{width:'100%',maxWidth:520,alignSelf:'center',paddingHorizontal:18,paddingVertical:14,flexDirection:'row',alignItems:'center',justifyContent:'space-between'},brandGroup:{flexDirection:'row',alignItems:'center',gap:10},
  mark:{width:40,height:40,borderRadius:20,backgroundColor:'#CCF5E4',alignItems:'center',justifyContent:'center'},brand:{fontSize:20,fontWeight:'700',color:colors.green},caption:{fontSize:12,color:colors.ink},iconButton:{minWidth:44,minHeight:44,alignItems:'center',justifyContent:'center'},
  content:{flexGrow:1,width:'100%',maxWidth:520,alignSelf:'center',paddingHorizontal:18,paddingBottom:26,gap:24},
  profileCard:{alignItems:'center',padding:18,paddingTop:22,borderRadius:22,backgroundColor:colors.surface,gap:7},avatar:{width:82,height:82,borderRadius:41,backgroundColor:'#CCF5E4',alignItems:'center',justifyContent:'center',marginBottom:6},initials:{fontSize:29,fontWeight:'600',color:colors.green},name:{fontSize:23,lineHeight:29,fontWeight:'600',color:colors.ink,textAlign:'center'},email:{fontSize:14,lineHeight:21,color:colors.muted,textAlign:'center'},badge:{flexDirection:'row',alignItems:'center',gap:6,borderRadius:20,backgroundColor:'#D7FBE9',paddingHorizontal:13,paddingVertical:6},badgeText:{fontSize:12,fontWeight:'600',color:colors.green},
  summary:{width:'100%',backgroundColor:'#E3FCF0',borderRadius:14,marginTop:13,padding:15,alignItems:'center',gap:4},total:{fontSize:24,fontWeight:'600',color:colors.green,fontVariant:['tabular-nums']},retry:{minHeight:44,justifyContent:'center',paddingHorizontal:16},linkText:{fontSize:14,fontWeight:'600',color:colors.green},
  section:{gap:12},sectionHeading:{gap:3,paddingHorizontal:4},sectionTitle:{fontSize:20,lineHeight:26,fontWeight:'600',color:colors.ink},copy:{fontSize:13,lineHeight:19,color:colors.muted},
  card:{backgroundColor:colors.surface,borderRadius:22,padding:18,gap:22},infoRow:{flexDirection:'row',alignItems:'center',gap:13},rowIcon:{width:40,height:40,borderRadius:20,backgroundColor:'#D7FBE9',alignItems:'center',justifyContent:'center'},rowCopy:{flex:1,minWidth:0,gap:2},label:{fontSize:12,color:colors.muted,lineHeight:18},value:{fontSize:16,lineHeight:22,fontWeight:'600',color:colors.ink},
  menuCard:{backgroundColor:colors.surface,borderRadius:22,padding:9,gap:4},menuRow:{minHeight:74,paddingHorizontal:10,paddingVertical:12,flexDirection:'row',alignItems:'center',gap:12,borderRadius:12},menuTitle:{fontSize:16,lineHeight:22,fontWeight:'600',color:colors.ink},aboutRow:{backgroundColor:'#D7FBE9'},aboutCopy:{padding:13,fontSize:14,lineHeight:22,color:colors.muted},
  logoutButton:{minHeight:52,flexDirection:'row',alignItems:'center',justifyContent:'center',gap:9,borderRadius:14,backgroundColor:'#FFDCD8'},logoutText:{color:colors.red,fontSize:16,fontWeight:'600'},error:{color:colors.red,fontSize:14,lineHeight:21},footer:{textAlign:'center',fontSize:12,lineHeight:18,color:colors.muted},
});
