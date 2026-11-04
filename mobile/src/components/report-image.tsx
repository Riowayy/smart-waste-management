import { useState } from 'react';
import { Pressable, StyleSheet, Text, View, type StyleProp, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import { SymbolView } from 'expo-symbols';
import { resolveReportImageUrl } from '../services/report-image-url';

export default function ReportImage({uri,label,style,contain=false}:{uri?:string|null;label:string;style:StyleProp<ViewStyle>;contain?:boolean}) {
  const url=resolveReportImageUrl(uri);
  const [failed,setFailed]=useState<string|null>(null);
  const [revision,setRevision]=useState(0);
  if(!url || failed===url)return <View accessibilityLabel={`${label}, foto tidak tersedia`} style={[style,styles.fallback]}>
    <SymbolView name={{ios:'photo',android:'image_not_supported',web:'image_not_supported'}} tintColor="#52665D" size={23}/>
    {url && <Pressable accessibilityRole="button" accessibilityLabel="Muat ulang foto laporan" onPress={()=>{setFailed(null);setRevision(value=>value+1);}} style={styles.retry}><Text style={styles.text}>Coba lagi</Text></Pressable>}
  </View>;
  return <View style={[style,{overflow:'hidden'}]}><Image key={`${url}/${revision}`} source={{uri:url}} accessibilityLabel={label} style={{width:'100%',height:'100%'}} contentFit={contain?'contain':'cover'} cachePolicy="memory-disk" recyclingKey={url} onError={()=>setFailed(url)}/></View>;
}
const styles=StyleSheet.create({fallback:{alignItems:'center',justifyContent:'center',backgroundColor:'#EEF9F3'},retry:{minHeight:44,justifyContent:'center',paddingHorizontal:4},text:{fontSize:11,color:'#52665D'}});
