import { useCallback, useEffect, useRef, useState } from 'react';
import EcoModal from '../../components/eco-modal';
import ReportImage from '../../components/report-image';
import { freshLocation } from './location';
import { lookupReportAddress } from './address';
import { useNavigationLock } from '../../components/navigation-lock';
import { ActivityIndicator, Image, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { SymbolView, type AndroidSymbol, type SFSymbol } from 'expo-symbols';

import LocationMap from './location-map';
import { pendingReport, reportBaseline, findSubmittedReports } from './recovery';
import { categoryLabel, type HistoryReport } from './history-data';
import { Link, router } from 'expo-router';
import * as Picker from 'expo-image-picker';
import * as Location from 'expo-location';

import { useAuth } from '../auth/provider';
import { ApiError } from '../../services/api/client';
import { getCategories, submitReport, validateDraft, validatePhoto, type Category, type Draft } from './service';

const photoSources = [
  { label: 'Kamera', camera: true, icon: 'photo_camera', ios: 'camera' },
  { label: 'Galeri', camera: false, icon: 'photo_library', ios: 'photo' },
] as const;

export function validateReportForm(draft: Draft): Record<string, string[]> {
  const errors = validateDraft(draft);
  if (!draft.location) errors.latitude = ['Ambil lokasi Anda sebelum menyimpan laporan.'];
  if (draft.categoryId === null) errors.category_id = ['Pilih kategori sampah.'];
  return errors;
}

export default function CreateReportScreen() {
  const auth = useAuth();
  const { acquire } = useNavigationLock();
  const ownerId = auth.user?.id ?? 0;
  const [draft,setDraft] = useState<Draft>(pendingReport.get(ownerId)?.draft ?? {description:'',categoryId:null,photo:null,location:null});
  const [categories,setCategories] = useState<Category[]>([]);
  const [categoryState,setCategoryState] = useState('loading');
  const [busy,setBusy] = useState(false);
  const [message,setMessage] = useState('');
  const [errors,setErrors] = useState<Record<string,string[]>>({});
  const [success,setSuccess] = useState(false);
  const [uncertain,setUncertain] = useState(!!pendingReport.get(ownerId));
  const [categoryOpen,setCategoryOpen] = useState(false);
  const [address,setAddress] = useState('');
  const [accuracy,setAccuracy] = useState<number | null>(null);
  const [matches,setMatches] = useState<HistoryReport[]>([]);
  const [gpsBusy,setGpsBusy] = useState(false);
  const [gpsError,setGpsError] = useState('');
  const [photoSourceOpen,setPhotoSourceOpen] = useState(false);
  const [locationTime,setLocationTime] = useState<number | null>(null);
  const lock = useRef(false);
  const active = useRef(true);
  const locationRevision = useRef(0);
  const gpsRequest = useRef<AbortController | null>(null);
  const photoChoice = useRef<{camera:boolean;release:()=>void} | null>(null);
  useEffect(() => () => { gpsRequest.current?.abort(); photoChoice.current?.release(); locationRevision.current++; }, []);
  const photoUri = draft.photo?.uri;
  useEffect(() => {
    const uri = photoUri;
    return () => { if (Platform.OS === 'web' && uri?.startsWith('blob:') && pendingReport.get(ownerId)?.draft.photo?.uri !== uri) URL.revokeObjectURL(uri); };
  }, [photoUri, ownerId]);
  const loadCategories = useCallback(async (signal?: AbortSignal) => {
    await getCategories(signal).then(
      data => { if (!signal?.aborted) {setCategories(data);setCategoryState('ready');} },
      () => { if (!signal?.aborted) setCategoryState('error'); },
    );
  }, []);
  async function load() {
    setCategoryState('loading');
    await loadCategories();
  }
  useEffect(()=>{active.current=true;const c=new AbortController();void loadCategories(c.signal);return ()=>{active.current=false;c.abort();};},[loadCategories]);
  async function operation(action:()=>Promise<void>) {
    if(lock.current)return;
    const releaseNavigation = acquire();
    lock.current=true;setBusy(true);setMessage('');
    try {await action();}
    catch(error) {if(active.current)setMessage(error instanceof Error?error.message:'Permintaan belum berhasil.');}
    finally {releaseNavigation();lock.current=false;if(active.current)setBusy(false);}
  }
  async function selectPhoto(camera:boolean) {
    if(camera && Platform.OS!=='web' && !(await Picker.requestCameraPermissionsAsync()).granted) throw new Error('Izin kamera ditolak. Pilih galeri atau izinkan kamera melalui pengaturan.');
    const options:Picker.ImagePickerOptions={mediaTypes:['images'],quality:1,allowsMultipleSelection:false};
    const result=camera?await Picker.launchCameraAsync(options):await Picker.launchImageLibraryAsync(options);
    if(result.canceled)return;
    const photo=result.assets[0], error=validatePhoto(photo);
    if(error || !active.current) {
      if(Platform.OS==='web' && photo.uri.startsWith('blob:'))URL.revokeObjectURL(photo.uri);
      if(error)throw new Error(error);
      return;
    }
    setDraft(old=>({...old,photo}));setErrors(old=>({...old,image:[]}));
  }
  function choosePhotoSource(camera: boolean) {
    if (photoChoice.current || lock.current) return;
    photoChoice.current = {camera, release:acquire()};
    lock.current = true;
    setBusy(true);
    setPhotoSourceOpen(false);
    if (Platform.OS === 'web') finishPhotoChoice();
    else if (Platform.OS !== 'ios') requestAnimationFrame(finishPhotoChoice);
  }
  function finishPhotoChoice() {
    const choice = photoChoice.current;
    if (!choice) return;
    photoChoice.current = null;
    lock.current = false;
    if (active.current) void operation(() => selectPhoto(choice.camera));
    choice.release();
  }
  function showPhotoSources() {
    if (!lock.current && !photoChoice.current) setPhotoSourceOpen(true);
  }
  async function resolveAddress(coordinates: {latitude:number;longitude:number}, revision:number) {
    const result = await lookupReportAddress(coordinates);
    if(active.current && revision===locationRevision.current && result!==null) setAddress(result);
  }
  async function locate() {
    const revision=++locationRevision.current;
    const controller = new AbortController();
    gpsRequest.current = controller;
    setGpsBusy(true);setGpsError('');
    try {
    if(!(await Location.requestForegroundPermissionsAsync()).granted)throw new Error('Izin lokasi diperlukan. Izinkan lokasi melalui pengaturan lalu coba lagi.');
      if (controller.signal.aborted) throw new Error('Pengambilan lokasi dibatalkan.');
      const point=await freshLocation(controller.signal);
      if (!active.current || controller.signal.aborted || revision !== locationRevision.current) return;
      const coordinates = { latitude: point.coords.latitude, longitude: point.coords.longitude };
      if (!Number.isFinite(coordinates.latitude) || Math.abs(coordinates.latitude) > 90 || !Number.isFinite(coordinates.longitude) || Math.abs(coordinates.longitude) > 180) throw new Error('Koordinat lokasi tidak valid. Coba lagi.');
      setDraft(old=>({...old,location:coordinates}));
      setLocationTime(point.timestamp);
      setAccuracy(point.coords.accuracy); setAddress('');
      setErrors(old=>({...old,latitude:[],longitude:[]}));
      if (Platform.OS !== 'web') void resolveAddress(coordinates,revision);
    } catch (error) {
      if(active.current)setGpsError(error instanceof Error?error.message:'Lokasi gagal diperoleh. Coba lagi.');
    } finally {if (gpsRequest.current === controller) gpsRequest.current = null;if(active.current)setGpsBusy(false);}
  }
  async function send() {
    if(uncertain || success || pendingReport.get(ownerId))return;
    const invalid=validateReportForm(draft);
    if (!categories.some(category=>category.id===draft.categoryId)) invalid.category_id=['Pilih kategori yang tersedia.'];
    setErrors(invalid);
    if(Object.keys(invalid).length)return;
    // Jika pembacaan awal gagal, POST belum dilakukan dan aman dicoba kembali.
    const baseline = await reportBaseline();
    if (!active.current) return;
    pendingReport.set(ownerId,{draft,baseline});
    try {await submitReport(draft);pendingReport.clear(ownerId);if(active.current)setSuccess(true);}
    catch(error) {
      if(error instanceof ApiError) {
        setErrors(error.errors);
        if(['network','timeout','invalid-response'].includes(error.kind) || (error.status ?? 0)>=500) {
          setUncertain(true);
          const reason = error.kind==='timeout'?'Waktu tunggu upload habis.':error.kind==='network'?'Koneksi upload terputus.':error.status?'Server mengembalikan HTTP '+error.status+'.':'Konfirmasi server tidak valid.';
          throw new Error(reason+' Status pengiriman belum pasti. Tekan Periksa Status Pengiriman sebelum mencoba lagi.');
        }
      }
      pendingReport.clear(ownerId);
      if(error instanceof ApiError && [401,403].includes(error.status??0))await auth.restore();
      throw error;
    }
  }
  async function recover() {
    const attempt = pendingReport.get(ownerId);
    if (!attempt) return;
    const candidates = await findSubmittedReports(attempt);
    if (!active.current) return;
    setMatches(candidates);
    setMessage(candidates.length ? 'Ditemukan laporan yang cocok. Cocokkan foto dan deskripsinya sebelum mengonfirmasi.' : 'Belum ditemukan laporan yang cocok. Hasil kosong belum memastikan server berhenti memproses. Pengiriman ulang tetap dikunci untuk mencegah duplikat; coba periksa lagi atau tinjau Riwayat.');
  }
  function confirmSaved() {
    pendingReport.clear(ownerId);setUncertain(false);setSuccess(true);setMatches([]);setMessage('');
  }
  function newReport() {
    locationRevision.current++;
    setPhotoSourceOpen(false);
    setDraft({description:'',categoryId:null,photo:null,location:null});setSuccess(false);setUncertain(false);setErrors({});setAddress('');setAccuracy(null);setLocationTime(null);setMessage('');
  }
  const fieldErrors=(name:string)=>errors[name]?.map((text,i)=><Text key={i} accessibilityRole="alert" style={styles.error}>{text}</Text>);
  const selectedCategory = categories.find(category=>category.id===draft.categoryId);
  const canSave = !busy && !uncertain && !success && categoryState === 'ready' && !!selectedCategory && !Object.keys(validateReportForm(draft)).length;
  const frozen = busy || uncertain;
  const photoSize = draft.photo?.file?.size ?? draft.photo?.fileSize;
  return <SafeAreaView style={styles.page} edges={['top','left','right']}>
    <View style={styles.header}>
      <View style={styles.brandGroup}><View style={styles.brandMark}><FormIcon name="eco" ios="leaf" /></View><View><Text style={styles.brand}>EcoTrack</Text><Text style={styles.brandCaption}>Laporkan</Text></View></View>
      <Link href="/profile" asChild><Pressable accessibilityRole="link" accessibilityLabel="Buka profil" disabled={busy} style={styles.avatar}><FormIcon name="person" ios="person" color="#FFFFFF" /></Pressable></Link>
    </View>
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS==='ios'?'padding':undefined}>
      <ScrollView keyboardShouldPersistTaps="handled" contentContainerStyle={styles.content}>
        {success ? <View style={styles.card}><FormIcon name="check_circle" ios="checkmark.circle" size={36}/><Text accessibilityRole="header" style={styles.title}>Laporan terkirim</Text><Text style={styles.copy}>Terima kasih. Laporan Anda menunggu pemeriksaan.</Text><FormAction label="Buat Laporan Baru" onPress={newReport} primary /><FormAction label="Kembali ke dashboard" onPress={()=>router.replace('/')} primary /></View> : <>
          {!!message && <View style={styles.errorCard}><Text accessibilityRole="alert" style={styles.error}>{message}</Text>{uncertain && <Link href="/reports" style={styles.textLink}>Periksa riwayat laporan</Link>}</View>}
          {uncertain && <View style={styles.errorCard}>
            <Text style={styles.copy}>Pengiriman sebelumnya belum terkonfirmasi. Jangan kirim ulang dari perangkat lain.</Text>
            <FormAction label="Periksa Status Pengiriman" disabled={busy} busy={busy} onPress={()=>void operation(recover)}/>
            {matches.map(report=><View key={report.id} style={styles.card}>
              <Text style={styles.copy}>Laporan #{report.id}: {report.description}</Text>
              {!!report.image_url && <ReportImage uri={report.image_url} label="Foto laporan yang ditemukan" style={{height:160,width:'100%'}}/>}
              <Text style={styles.locationHint}>Dibuat: {report.created_at ? new Date(report.created_at).toLocaleString('id-ID') : 'Tanggal tidak tersedia'}</Text>
              <FormAction label="Ini laporan saya, konfirmasi tersimpan" disabled={busy} onPress={confirmSaved}/>
            </View>)}
          </View>}
          <View style={styles.card}>
            <Text accessibilityRole="header" style={styles.title}>Foto Sampah <Text style={styles.required}>*</Text></Text>
            {draft.photo ? <View style={styles.preview}>
              <Image source={{uri:draft.photo.uri}} accessibilityLabel="Preview foto laporan" resizeMode="cover" style={styles.previewImage}/>
              <View style={styles.photoBadge}><FormIcon name="check_circle" ios="checkmark.circle" size={16} color="#FFFFFF"/><Text style={styles.photoBadgeText}>1 foto terlampir{photoSize ? ` (${(photoSize/1024/1024).toFixed(1)} MB)` : ''}</Text></View>
              <View style={styles.photoActions}><View style={styles.flex}><FormAction label="Ganti Foto" icon="photo_library" ios="photo" disabled={frozen} onPress={showPhotoSources}/></View><FormAction label="Hapus" icon="delete" ios="trash" danger disabled={frozen} onPress={()=>setDraft(old=>({...old,photo:null}))}/></View>
            </View> : <Pressable accessibilityRole="button" accessibilityLabel="Tambahkan foto laporan" accessibilityState={{disabled:frozen,expanded:photoSourceOpen}} disabled={frozen} onPress={showPhotoSources} style={({pressed})=>[styles.photoEmpty,{opacity:frozen?0.5:pressed?0.75:1}]}><FormIcon name="add_photo_alternate" ios="photo.badge.plus" size={36}/></Pressable>}
            <View style={styles.info}><FormIcon name="info" ios="info.circle"/><Text style={[styles.copy,styles.flex]}>Format: JPG, PNG, WebP{ '\n' }Maks. 5 MB</Text></View>
            {fieldErrors('image')}
          </View>
          <View style={styles.card}>
            <Text accessibilityRole="header" style={styles.title}>Kategori Sampah <Text style={styles.required}>*</Text></Text>
            {categoryState==='loading' ? <View style={styles.row}><ActivityIndicator color="#006C49"/><Text style={styles.copy}>Memuat kategori...</Text></View> : categoryState==='error' ? <><Text style={styles.error}>Kategori belum dapat dimuat.</Text><FormAction label="Muat ulang kategori" disabled={frozen} onPress={()=>void operation(()=>load())}/></> : !categories.length ? <><Text style={styles.copy}>Belum ada kategori tersedia.</Text><FormAction label="Muat ulang kategori" disabled={frozen} onPress={()=>void operation(()=>load())}/></> : <>
              <Pressable accessibilityRole="button" accessibilityLabel="Pilih kategori sampah" accessibilityState={{expanded:categoryOpen,disabled:frozen}} disabled={frozen} onPress={()=>setCategoryOpen(!categoryOpen)} style={styles.select}><FormIcon name="recycling" ios="arrow.3.trianglepath"/><Text style={[styles.copy,styles.flex]}>{selectedCategory ? categoryLabel({category:selectedCategory}) : 'Pilih kategori sampah'}</Text><FormIcon name={categoryOpen?'expand_less':'expand_more'} ios={categoryOpen?'chevron.up':'chevron.down'}/></Pressable>
              {categoryOpen && <View accessibilityRole="radiogroup" style={styles.options}>{categories.map(category=><Pressable key={category.id} accessibilityRole="radio" accessibilityState={{checked:draft.categoryId===category.id,disabled:frozen}} disabled={frozen} onPress={()=>{setDraft(old=>({...old,categoryId:category.id}));setCategoryOpen(false);setErrors(old=>({...old,category_id:[]}));}} style={styles.option}><Text style={[styles.copy,styles.flex]}>{categoryLabel({category})}</Text>{draft.categoryId===category.id && <FormIcon name="check" ios="checkmark"/>}</Pressable>)}</View>}
            </>}
            {fieldErrors('category_id')}
          </View>
          <View style={styles.card}>
            <Text accessibilityRole="header" style={styles.title}>Deskripsi Laporan <Text style={styles.required}>*</Text></Text>
            <View style={styles.descriptionBox}><TextInput accessibilityLabel="Deskripsi laporan" multiline textAlignVertical="top" editable={!frozen} maxLength={1000} value={draft.description} onChangeText={description=>setDraft(old=>({...old,description}))} placeholder="Jelaskan temuan sampah dan kondisi di lokasi..." placeholderTextColor="#52665D" style={styles.textarea}/><Text style={styles.counter}>{draft.description.length} / 1.000 karakter</Text></View>
            {fieldErrors('description')}
          </View>
          <View style={styles.card}>
            <View style={styles.row}><FormIcon name="location_on" ios="mappin.circle" size={26}/><Text accessibilityRole="header" style={[styles.title,styles.flex]}>Lokasi Pengaduan <Text style={styles.required}>*</Text></Text></View>
            <FormAction label={draft.location?"Perbarui Lokasi":"Ambil Lokasi Saya"} icon="my_location" ios="location.circle" disabled={frozen} busy={gpsBusy} onPress={()=>void operation(locate)}/>
            {!!gpsError && <Text accessibilityRole="alert" style={styles.error}>{gpsError}{draft.location ? ' Koordinat sebelumnya tetap digunakan.' : ''}</Text>}
            {!!locationTime && <Text style={styles.copy}>Lokasi diperoleh pukul {new Date(locationTime).toLocaleTimeString('id-ID')}.</Text>}
            {gpsBusy && <View style={styles.info}><Text accessibilityLiveRegion="polite" style={[styles.copy,styles.flex]}>Mencari lokasi terbaru. Koordinat sebelumnya tetap aman.</Text><FormAction label="Batalkan GPS" onPress={()=>gpsRequest.current?.abort()}/></View>}
            <View style={styles.locationBox}>
              {draft.location ? <LocationMap key={`${draft.location.latitude}/${draft.location.longitude}`} point={draft.location}/> : <View style={styles.locationVisual}><FormIcon name="location_searching" ios="mappin.circle" size={36}/><Text style={styles.locationTitle}>Lokasi belum diambil</Text><Text style={styles.locationHint}>Lokasi perangkat melalui izin GPS</Text></View>}
              {!!address && <View style={styles.row}><FormIcon name="location_on" ios="mappin" size={18}/><View style={styles.flex}><Text style={styles.smallLabel}>Alamat</Text><Text style={styles.copy}>{address}</Text></View></View>}
              {draft.location ? <><Text style={styles.coordinates}>{draft.location.latitude.toFixed(6)}, {draft.location.longitude.toFixed(6)}</Text>{accuracy!==null && Number.isFinite(accuracy) && <Text style={styles.locationHint}>Akurasi perangkat: ±{Math.round(accuracy)} meter</Text>}{!address && <Text style={styles.locationHint}>Alamat belum tersedia. Laporan akan memakai koordinat di atas.</Text>}</> : <Text style={styles.copy}>Izinkan lokasi dan aktifkan GPS sebelum menyimpan laporan.</Text>}
            </View>
            {fieldErrors('latitude')}{fieldErrors('longitude')}
          </View>
          {busy && <View accessibilityLiveRegion="polite" style={styles.row}><ActivityIndicator color="#006C49"/><Text style={styles.copy}>Sedang memproses, mohon tunggu...</Text></View>}
          {!uncertain && <FormAction label="Simpan Laporan" icon="check_circle" ios="checkmark.circle" primary disabled={!canSave} busy={busy} onPress={()=>void operation(send)}/>}
        </>}
      </ScrollView>
    </KeyboardAvoidingView>
    <EcoModal visible={photoSourceOpen} sheet onClose={()=>setPhotoSourceOpen(false)} onDismiss={finishPhotoChoice}>
      {photoSourceOpen && <>
        <Text accessibilityRole="header" style={styles.title}>Tambah foto sampah</Text>
        <Text style={styles.copy}>Pilih sumber foto untuk melanjutkan pelaporan.</Text>
        {photoSources.map(source=><FormAction key={source.label} large label={source.label} icon={source.icon} ios={source.ios} onPress={()=>choosePhotoSource(source.camera)}/>)}
        <FormAction label="Batal" onPress={()=>setPhotoSourceOpen(false)}/>
      </>}
    </EcoModal>
  </SafeAreaView>;
}
function FormIcon({name,ios,color='#006C49',size=21}:{name:AndroidSymbol;ios:SFSymbol;color?:string;size?:number}) {
  return <SymbolView name={{ios,android:name,web:name}} tintColor={color} size={size}/>;
}
function FormAction({label,onPress,disabled=false,busy=false,primary=false,danger=false,large=false,icon,ios}:{label:string;onPress:()=>void;disabled?:boolean;busy?:boolean;primary?:boolean;danger?:boolean;large?:boolean;icon?:AndroidSymbol;ios?:SFSymbol}) {
  const color=primary?'#FFFFFF':danger?'#A92330':'#006C49';
  return <Pressable accessibilityRole="button" accessibilityLabel={label} accessibilityState={{disabled,busy}} disabled={disabled} onPress={onPress} style={({pressed})=>[styles.button,large&&styles.largeChoice,primary&&styles.primary,danger&&styles.danger,{opacity:disabled?0.5:pressed?0.75:1}]}>{busy?<ActivityIndicator color={color}/>:icon&&ios?<FormIcon name={icon} ios={ios} color={color}/>:null}<Text style={[styles.buttonText,{color}]}>{label}</Text></Pressable>;
}
const styles=StyleSheet.create({
  flex:{flex:1}, page:{flex:1,backgroundColor:'#E7FFF3'},
  header:{width:'100%',maxWidth:520,alignSelf:'center',paddingHorizontal:18,paddingVertical:14,flexDirection:'row',alignItems:'center',justifyContent:'space-between'},
  brandGroup:{flexDirection:'row',alignItems:'center',gap:10},brandMark:{width:38,height:38,borderRadius:20,backgroundColor:'#CCF5E4',alignItems:'center',justifyContent:'center'},
  brand:{fontSize:20,fontWeight:'700',color:'#006C49'},brandCaption:{fontSize:12,color:'#102D23'},avatar:{width:42,height:42,borderRadius:22,backgroundColor:'#006C49',alignItems:'center',justifyContent:'center'},
  content:{width:'100%',maxWidth:520,alignSelf:'center',padding:16,paddingBottom:32,gap:20},
  card:{padding:16,borderRadius:20,backgroundColor:'#FFFFFF',gap:14},title:{fontSize:19,lineHeight:26,fontWeight:'600',color:'#102D23'},required:{color:'#A92330'},
  copy:{fontSize:14,lineHeight:21,color:'#233F32'},row:{flexDirection:'row',alignItems:'center',gap:9},
  preview:{height:230,borderRadius:13,overflow:'hidden',backgroundColor:'#D7F8E8'},previewImage:{width:'100%',height:'100%'},
  photoBadge:{position:'absolute',top:12,left:12,right:12,alignSelf:'flex-start',flexDirection:'row',alignItems:'center',gap:5,padding:8,borderRadius:16,backgroundColor:'#006C49'},photoBadgeText:{fontSize:12,fontWeight:'600',color:'#FFFFFF',flexShrink:1},
  photoActions:{position:'absolute',bottom:12,left:12,right:12,flexDirection:'row',gap:8},photoEmpty:{minHeight:100,borderRadius:13,backgroundColor:'#D7F8E8',alignItems:'center',justifyContent:'center',padding:16},
  info:{flexDirection:'row',alignItems:'center',gap:9,padding:10,borderRadius:13,backgroundColor:'#E3FCF0'},
  select:{flexDirection:'row',alignItems:'center',gap:10,minHeight:54,padding:13,borderRadius:13,backgroundColor:'#D7FBE9'},options:{backgroundColor:'#EFFBF5',borderRadius:13,padding:5},option:{minHeight:48,padding:10,flexDirection:'row',alignItems:'center',gap:8},
  descriptionBox:{padding:13,borderRadius:13,backgroundColor:'#D7FBE9',gap:8},textarea:{minHeight:126,fontSize:14,lineHeight:23,color:'#102D23',padding:0},counter:{textAlign:'right',fontSize:12,color:'#52665D'},
  locationBox:{padding:12,borderRadius:13,backgroundColor:'#D7FBE9',gap:10},locationVisual:{minHeight:125,padding:12,borderRadius:12,backgroundColor:'#BFEEDA',alignItems:'center',justifyContent:'center',gap:6},locationTitle:{fontSize:14,fontWeight:'600',color:'#006C49'},locationHint:{fontSize:12,lineHeight:18,color:'#315B49'},coordinates:{fontSize:14,color:'#233F32',fontVariant:['tabular-nums']},smallLabel:{fontSize:12,color:'#52665D'},
  largeChoice:{minHeight:68,borderRadius:16},
  button:{minHeight:46,paddingHorizontal:13,paddingVertical:10,borderRadius:12,backgroundColor:'#D2F6E5',flexDirection:'row',alignItems:'center',justifyContent:'center',gap:8},buttonText:{fontSize:14,fontWeight:'600'},primary:{minHeight:54,borderRadius:16,backgroundColor:'#006C49'},danger:{backgroundColor:'#FFE2DF'},
  error:{color:'#A92330',fontSize:13,lineHeight:20},errorCard:{padding:16,borderRadius:16,backgroundColor:'#FFF2F0',gap:10},textLink:{color:'#006C49',fontSize:14,fontWeight:'600',paddingVertical:10},
});
