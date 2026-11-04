import { useEffect, useState } from 'react';
import { ActivityIndicator, Linking, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { SymbolView } from 'expo-symbols';
import type { Coordinates } from './service';
import { mapTiles } from './map-data';

export default function LocationMap({ point }: { point: Coordinates }) {
  const [width, setWidth] = useState(0);
  const [loaded, setLoaded] = useState<string[]>([]);
  const [failed, setFailed] = useState(false);
  const [revision, setRevision] = useState(0);
  const tiles = width ? mapTiles(point, width) : [];
  const identity = `${point.latitude}/${point.longitude}/${width}/${revision}`;
  useEffect(() => {
    setLoaded([]); setFailed(false);
    const timer = setTimeout(() => setFailed(true), 15000);
    return () => clearTimeout(timer);
  }, [identity]);
  const ready = tiles.length > 0 && tiles.every(tile => loaded.includes(`${identity}/${tile.key}`));
  return <View style={styles.frame}>
    <View accessibilityLabel={`Peta lokasi ${point.latitude}, ${point.longitude}`} onLayout={event => setWidth(event.nativeEvent.layout.width)} style={styles.map}>
      {tiles.map(tile => <Image key={`${identity}/${tile.key}`} source={{ uri: tile.uri, ...(Platform.OS === 'web' ? {} : { headers: { 'User-Agent': 'EcoTrackMobile/1.0 (+https://github.com/Riowayy/smart-waste-management)' } }) }} cachePolicy="disk" contentFit="fill" style={[styles.tile, {left:tile.left,top:tile.top}]} onLoad={() => setLoaded(old => [...old,`${identity}/${tile.key}`])} onError={() => setFailed(true)}/>)}
      {ready && <View pointerEvents="none" style={styles.pin}><SymbolView name={{ios:'mappin',android:'location_on',web:'location_on'}} tintColor="#D32F2F" size={38}/></View>}
      {!ready && <View style={styles.overlay}>{failed || (width > 0 && !tiles.length) ? <><Text style={styles.text}>Peta belum dapat ditampilkan. Koordinat tetap tersedia.</Text><Pressable accessibilityRole="button" onPress={() => setRevision(value => value + 1)} style={styles.retry}><Text style={styles.text}>Muat ulang peta</Text></Pressable></> : <><ActivityIndicator color="#006C49"/><Text style={styles.text}>Memuat peta...</Text></>}</View>}
    </View>
    <Pressable accessibilityRole="link" accessibilityLabel="Hak cipta OpenStreetMap" onPress={() => void Linking.openURL('https://www.openstreetmap.org/copyright').catch(() => {})} style={styles.credit}><Text style={styles.creditText}>{'\u00A9'} OpenStreetMap contributors</Text></Pressable>
  </View>;
}
const styles = StyleSheet.create({
  frame:{borderRadius:12,overflow:'hidden',backgroundColor:'#BFEEDA'},map:{height:220,overflow:'hidden'},tile:{position:'absolute',width:256,height:256},
  pin:{position:'absolute',left:'50%',top:'50%',width:38,height:38,transform:[{translateX:-19},{translateY:-38}],alignItems:'center',justifyContent:'center'},
  overlay:{position:'absolute',top:0,right:0,bottom:0,left:0,backgroundColor:'#D7FBE9',alignItems:'center',justifyContent:'center',gap:10,padding:16},text:{color:'#315B49',fontSize:12,textAlign:'center'},retry:{minHeight:44,justifyContent:'center',paddingHorizontal:12},credit:{minHeight:32,justifyContent:'center',alignItems:'flex-end',paddingHorizontal:8,backgroundColor:'#FFFFFF'},creditText:{fontSize:11,color:'#315B49'},
});