import { useRef, useState, type ReactNode } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, Pressable, ScrollView, StatusBar, StyleSheet, Text, TextInput, useWindowDimensions, View } from 'react-native';
import { Image, useImage } from 'expo-image';
import { SymbolView } from 'expo-symbols';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Link } from 'expo-router';
import { ApiError } from '../../services/api/client';
import { useAuth } from './provider';
import { validateCredentials } from './session';

const backgroundImage = require('../../../assets/images/bg-mobile-optimized.jpg');
const brandMark = require('../../../assets/images/ecotrack-mark.svg');

export function AuthShell({children}: {children: ReactNode}) {
 return <SafeAreaView style={styles.page}><KeyboardAvoidingView style={{flex:1}} behavior={Platform.OS === 'ios' ? 'padding' : undefined}><ScrollView keyboardShouldPersistTaps="handled" contentContainerStyle={styles.scroll}><View style={styles.content}><Text style={styles.brand}>EcoTrack</Text>{children}</View></ScrollView></KeyboardAvoidingView></SafeAreaView>;
}

export function Action({label,onPress,busy=false}: {label:string;onPress():void;busy?:boolean}) {
 return <Pressable accessibilityRole="button" accessibilityState={{disabled:busy,busy}} disabled={busy} onPress={onPress} style={({pressed})=>[styles.button,{opacity:busy||pressed?0.65:1}]}>{busy?<ActivityIndicator color="white"/>:<Text style={styles.buttonText}>{label}</Text>}</Pressable>;
}

function LoginShell({children}: {children: ReactNode}) {
 const {width:windowWidth,height:windowHeight}=useWindowDimensions();
 const horizontalPadding=Math.max(16,Math.min(32,windowWidth*0.06));
 const contentGap=Math.max(16,Math.min(24,windowHeight*0.028));
 const [backgroundFailed,setBackgroundFailed]=useState(false);
 const decodedBackground=useImage(backgroundImage,{maxWidth:1080,onError:()=>setBackgroundFailed(true)});
 if(!decodedBackground&&!backgroundFailed)return <SafeAreaView style={styles.loginPage}><View style={{flex:1,alignItems:'center',justifyContent:'center'}}><ActivityIndicator color="#006C49" accessibilityLabel="Menyiapkan tampilan login"/></View></SafeAreaView>;
 return <View style={styles.loginPage}>
  <StatusBar barStyle="dark-content"/>
  <View pointerEvents="none" style={StyleSheet.absoluteFill}>
   <Image source={decodedBackground??backgroundImage} contentFit="cover" priority="high" cachePolicy="memory-disk" transition={0} decodeFormat="rgb" style={StyleSheet.absoluteFill}/>
   <View style={styles.backgroundWash}/>
  </View>
  <SafeAreaView style={styles.loginKeyboard}>
   <KeyboardAvoidingView style={styles.loginKeyboard} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
   <ScrollView keyboardShouldPersistTaps="handled" contentContainerStyle={[styles.loginScroll,{paddingHorizontal:horizontalPadding}]}>
    <View style={[styles.loginContent,{gap:contentGap}]}>
     <View style={[styles.loginHeader,{minHeight:Math.max(44,Math.min(60,windowHeight*0.07))}]}>
      <Image source={brandMark} contentFit="contain" accessibilityLabel="Logo EcoTrack" style={styles.logo}/>
      <Text style={styles.wordmark}>EcoTrack</Text>
     </View>
     {children}
    </View>
   </ScrollView>
  </KeyboardAvoidingView>
  </SafeAreaView>
 </View>;
}

function FieldIcon({kind}: {kind:'person'|'email'|'lock'}) {
 return <View accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.fieldIcon}>
  <SymbolView name={kind==='person'?{ios:'person',android:'person',web:'person'}:kind==='email'?{ios:'envelope',android:'mail',web:'mail'}:{ios:'lock.fill',android:'lock',web:'lock'}} tintColor="#73847A" size={21}/>
 </View>;
}

export function AuthScreen({mode}: {mode:'login'|'register'}) {
 const auth=useAuth(); const register=mode==='register';
 const {width:windowWidth}=useWindowDimensions();
 const titleSize=Math.max(24,Math.min(29,windowWidth*0.074));
 const [values,setValues]=useState({name:'',email:'',password:'',password_confirmation:''});
 const [errors,setErrors]=useState<Record<string,string[]>>({});
 const [message,setMessage]=useState(''); const [busy,setBusy]=useState(false); const [passwordVisibility,setPasswordVisibility]=useState({password:false,password_confirmation:false}); const lock=useRef(false);
 async function submit() {
  if(lock.current)return;
  const payload={...values,name:values.name.trim(),email:values.email.trim()};
  const invalid=validateCredentials(mode,payload);setErrors(invalid);setMessage('');
  if(Object.keys(invalid).length)return;
  lock.current=true;setBusy(true);
  try { await auth.authenticate(mode,register?payload:{email:payload.email,password:payload.password}); }
  catch(error) {
   if(error instanceof ApiError){setErrors(error.errors);setMessage(error.message);}
   else setMessage('Autentikasi belum berhasil. Periksa konfigurasi dan penyimpanan perangkat, lalu coba lagi.');
  } finally {lock.current=false;setBusy(false);}
 }
 const fields=register?['name','email','password','password_confirmation'] as const:['email','password'] as const;
 const labels={name:'Nama lengkap',email:'Alamat email',password:'Kata sandi',password_confirmation:'Konfirmasi kata sandi'};
 return <LoginShell>
  <View style={styles.welcomeBlock}>
   <Text accessibilityRole="header" style={[styles.loginTitle,{fontSize:titleSize,lineHeight:titleSize*1.25}]}>{register?'Mulai dari kepedulian.':'Selamat datang kembali!'}</Text>
   <Text style={styles.loginDescription}>{register?'Buat akun untuk melaporkan sampah dan memantau penanganannya.':'Mari bersama menjaga lingkungan melalui pelaporan sampah yang lebih mudah.'}</Text>
  </View>
  {message?<Text accessibilityRole="alert" style={styles.loginError}>{message}</Text>:null}
  <View style={styles.loginForm}>
   {fields.map(field=>{
    const isPassword=field==='password'||field==='password_confirmation';
    const visible=isPassword?passwordVisibility[field]:false;
    const icon=field==='name'?'person':field==='email'?'email':'lock';
    const label=labels[field].toLocaleUpperCase('id-ID');
    const placeholder=field==='email'?'nama@lingkungan.id':isPassword?String.fromCharCode(0x2022).repeat(12):labels[field];
    return <View key={field} style={styles.field}>
     <Text style={styles.loginLabel}>{label}</Text>
     <View style={[styles.inputShell,errors[field]?.length?styles.inputShellInvalid:null]}>
      <FieldIcon kind={icon}/>
      <TextInput accessibilityLabel={labels[field]} editable={!busy} value={values[field]} onChangeText={text=>{setValues(old=>({...old,[field]:text}));if(errors[field])setErrors(old=>({...old,[field]:[]}));}} placeholder={placeholder} placeholderTextColor={palette.placeholder} secureTextEntry={isPassword&&!visible} autoCapitalize={field==='name'?'words':'none'} autoCorrect={false} keyboardType={field==='email'?'email-address':'default'} autoComplete={field==='email'?'email':field==='name'?'name':register?'new-password':'current-password'} textContentType={field==='email'?'emailAddress':field==='name'?'name':register?'newPassword':'password'} maxLength={255} returnKeyType={field==='password'&&!register?'done':'next'} onSubmitEditing={()=>{if(field==='password'&&!register)void submit();}} style={styles.loginInput}/>
      {isPassword&&<Pressable accessibilityRole="button" accessibilityLabel={visible?'Sembunyikan kata sandi':'Tampilkan kata sandi'} accessibilityState={{disabled:busy}} disabled={busy} hitSlop={8} onPress={()=>setPasswordVisibility(old=>({...old,[field]:!old[field]}))} style={styles.visibilityButton}><SymbolView name={{ios:visible?'eye':'eye.slash',android:visible?'visibility':'visibility_off',web:visible?'visibility':'visibility_off'}} tintColor="#728078" size={22}/></Pressable>}
     </View>
     {errors[field]?.map((text,i)=><Text key={i} accessibilityRole="alert" style={styles.loginFieldError}>{text}</Text>)}
    </View>;
   })}
  </View>
  {register&&<Text style={[styles.loginDescription,styles.registerEmphasis]}>Kata sandi minimal 8 karakter.</Text>}
  <Pressable accessibilityRole="button" accessibilityState={{disabled:busy,busy}} disabled={busy} onPress={()=>void submit()} style={({pressed})=>[styles.loginButton,(busy||pressed)&&styles.loginButtonPressed]}>
   {busy?<ActivityIndicator color={palette.white} accessibilityLabel={register?'Sedang membuat akun':'Sedang masuk'}/>:<><Text style={styles.loginButtonText}>{register?'Buat akun':'Masuk ke Akun'}</Text><SymbolView name={{ios:'arrow.right',android:'arrow_forward',web:'arrow_forward'}} tintColor={palette.white} size={21}/></>}
  </Pressable>
  {!busy&&<View style={styles.registerRow}><Text style={[styles.registerPrompt,register&&styles.registerEmphasis]}>{register?'Sudah punya akun?':'Belum punya akun?'}</Text><Link href={register?'/login':'/register'} style={styles.registerLink}>{register?'Masuk':'Daftar sekarang'}</Link></View>}
 </LoginShell>;
}

const palette={surface:'#E7FFF3',forest:'#0B5C43',emerald:'#0D8B5B',lime:'#C7E96B',ink:'#173B2F',muted:'#52665D',placeholder:'#929D96',white:'#FFFFFF',error:'#B42318'} as const;
export const styles=StyleSheet.create({
 registerEmphasis:{fontWeight:'600',color:palette.forest},
 page:{flex:1,backgroundColor:'#f4f8f5'},scroll:{flexGrow:1,justifyContent:'center',padding:24},content:{width:'100%',maxWidth:440,alignSelf:'center',gap:16},
 brand:{color:'#006c49',fontSize:24,fontWeight:'700',marginBottom:24},title:{fontSize:32,fontWeight:'700',color:'#153b2d'},description:{fontSize:16,lineHeight:24,color:'#52655b'},field:{gap:8},label:{fontSize:15,fontWeight:'600',color:'#153b2d'},
 input:{minHeight:50,borderWidth:1,borderColor:'#81988b',borderRadius:8,padding:12,backgroundColor:'white',color:'#153b2d',fontSize:16},invalid:{borderColor:'#b42318'},error:{color:'#b42318',fontSize:14,lineHeight:21},button:{minHeight:50,justifyContent:'center',alignItems:'center',backgroundColor:'#006c49',borderRadius:8,padding:12},buttonText:{color:'white',fontSize:16,fontWeight:'600'},link:{color:'#006c49',textAlign:'center',padding:12,fontSize:15},
 loginPage:{flex:1,backgroundColor:palette.surface},backgroundWash:{...StyleSheet.absoluteFill,backgroundColor:'rgba(231,255,243,0.48)'},loginKeyboard:{flex:1},loginScroll:{flexGrow:1,paddingTop:8,paddingBottom:28},loginContent:{width:'100%',maxWidth:520,alignSelf:'center',gap:24},loginHeader:{minHeight:44,flexDirection:'row',alignItems:'center',gap:10},logo:{width:38,height:38},wordmark:{fontSize:27,fontWeight:'700',letterSpacing:-0.6,color:palette.forest},
 heroFrame:{height:210,borderRadius:22,overflow:'hidden',backgroundColor:'#BDEBD5',shadowColor:'#174E39',shadowOffset:{width:0,height:7},shadowOpacity:0.14,shadowRadius:16,elevation:4},heroArtwork:{flex:1,justifyContent:'flex-end'},heroImage:{transform:[{scale:1.03}],opacity:0.92},heroCaption:{alignSelf:'flex-start',flexDirection:'row',alignItems:'center',gap:9,margin:14,paddingHorizontal:15,paddingVertical:10,borderRadius:24,backgroundColor:'rgba(255,255,255,0.94)'},heroCaptionText:{fontSize:13,fontWeight:'700',color:palette.forest},leafMark:{width:17,height:17,borderWidth:2,borderColor:palette.emerald,borderTopLeftRadius:11,borderBottomRightRadius:11,transform:[{rotate:'-14deg'}]},leafStroke:{position:'absolute',width:1.5,height:13,backgroundColor:palette.emerald,top:0,left:7,transform:[{rotate:'36deg'}]},
 welcomeBlock:{gap:8,marginTop:1},loginTitle:{fontSize:29,lineHeight:36,fontWeight:'700',letterSpacing:-0.75,color:'#0B2F23'},loginDescription:{fontSize:16,lineHeight:25,color:palette.muted,maxWidth:500},loginError:{fontSize:14,lineHeight:20,color:palette.error},loginForm:{gap:20},loginLabel:{fontSize:13,lineHeight:18,fontWeight:'700',letterSpacing:0.65,color:'#34483D'},inputShell:{minHeight:58,flexDirection:'row',alignItems:'center',gap:13,paddingHorizontal:15,borderRadius:17,backgroundColor:palette.white,shadowColor:'#144832',shadowOffset:{width:0,height:3},shadowOpacity:0.06,shadowRadius:9,elevation:2},inputShellInvalid:{borderWidth:1,borderColor:palette.error},loginInput:{flex:1,minWidth:0,minHeight:58,paddingVertical:14,color:palette.ink,fontSize:16,outlineStyle:'none' as never,outlineWidth:0,outlineColor:'transparent',borderWidth:0,boxShadow:'none'},loginFieldError:{fontSize:13,lineHeight:18,color:palette.error},fieldIcon:{width:22,height:22,alignItems:'center',justifyContent:'center'},visibilityButton:{width:44,height:44,alignItems:'center',justifyContent:'center',borderRadius:12},
 loginButton:{minHeight:58,paddingVertical:14,marginTop:-2,position:'relative',flexDirection:'row',alignItems:'center',justifyContent:'center',gap:14,borderRadius:18,backgroundColor:palette.forest,paddingHorizontal:20,shadowColor:'#0B5C43',shadowOffset:{width:0,height:5},shadowOpacity:0.2,shadowRadius:11,elevation:4},loginButtonPressed:{opacity:0.78},loginButtonText:{color:palette.white,fontSize:18,fontWeight:'700'},buttonAccent:{position:'absolute',right:17,width:10,height:10,borderRadius:5,backgroundColor:palette.lime},arrowIcon:{width:19,height:19,justifyContent:'center'},arrowLine:{height:2,width:15,backgroundColor:palette.white},arrowHeadTop:{position:'absolute',right:1,top:4,width:8,height:8,borderTopWidth:2,borderRightWidth:2,borderColor:palette.white,transform:[{rotate:'45deg'}]},arrowHeadBottom:{position:'absolute',right:1,bottom:4,width:8,height:8,borderTopWidth:2,borderRightWidth:2,borderColor:palette.white,transform:[{rotate:'135deg'}]},registerRow:{flexDirection:'row',justifyContent:'center',alignItems:'center',flexWrap:'wrap',gap:7,paddingTop:0},registerPrompt:{fontSize:15,color:'#42574D'},registerLink:{fontSize:15,fontWeight:'700',color:palette.forest,paddingVertical:12,paddingHorizontal:2},
});
