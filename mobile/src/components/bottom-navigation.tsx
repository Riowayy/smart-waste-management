import { useEffect, useState } from 'react';
import { Keyboard, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { router, usePathname } from 'expo-router';
import { SymbolView, type AndroidSymbol, type SFSymbol } from 'expo-symbols';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigationLock } from './navigation-lock';

export const navigationItems: {
  href: '/' | '/reports' | '/create-report' | '/profile';
  label: string;
  android: AndroidSymbol;
  ios: SFSymbol;
}[] = [
  { href: '/', label: 'Beranda', android: 'home', ios: 'house.fill' },
  { href: '/reports', label: 'Riwayat', android: 'schedule', ios: 'clock' },
  { href: '/create-report', label: 'Laporkan', android: 'photo_camera', ios: 'camera' },
  { href: '/profile', label: 'Profil', android: 'person_outline', ios: 'person' },
];

export function activeNavigationPath(pathname: string) {
  return navigationItems.find(item => item.href === pathname ||
    (item.href !== '/' && pathname.startsWith(`${item.href}/`)))?.href;
}

export default function BottomNavigation() {
  const active = activeNavigationPath(usePathname());
  const { locked, isLocked } = useNavigationLock();
  const insets = useSafeAreaInsets();
  const [keyboardVisible, setKeyboardVisible] = useState(() => Keyboard.isVisible());
  useEffect(() => {
    const show = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow', () => setKeyboardVisible(true));
    const hide = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide', () => setKeyboardVisible(false));
    return () => { show.remove(); hide.remove(); };
  }, []);
  if (locked || keyboardVisible || !active) return null;
  return <View style={[styles.footer, { paddingLeft: 12 + insets.left, paddingRight: 12 + insets.right }]}><View accessibilityLabel="Navigasi utama" style={styles.container}>
    {navigationItems.map(item => {
      const selected = active === item.href;
      return <Pressable key={item.href} accessibilityRole="tab" accessibilityLabel={item.label}
        accessibilityState={{ selected, disabled: locked }} disabled={locked}
        onPress={() => { if (!isLocked() && !selected) router.navigate(item.href); }}
        style={({ pressed }) => [styles.item, selected && styles.active, pressed && styles.pressed]}>
        <View style={styles.icon}>
          <SymbolView name={{ ios: item.ios, android: item.android, web: item.android }} size={23} tintColor={selected ? '#FFFFFF' : '#64748B'} />
          {item.href === '/create-report' && !selected && <View style={styles.dot} />}
        </View>
        <Text numberOfLines={1} style={[styles.label, selected && styles.activeLabel]}>{item.label}</Text>
      </Pressable>;
    })}
  </View></View>;
}

const styles = StyleSheet.create({
  footer: { paddingTop: 6, paddingBottom: 12, alignItems: 'center' },
  container: { flexDirection: 'row', alignItems: 'center', gap: 2, padding: 6, borderRadius: 40, backgroundColor: '#FAFFFD', shadowColor: '#006C49', shadowOffset: { width: 0, height: 5 }, shadowOpacity: 0.09, shadowRadius: 12, elevation: 3, width: '100%', maxWidth: 520 },
  item: { flex: 1, minWidth: 44, minHeight: 56, alignItems: 'center', justifyContent: 'center', gap: 3, paddingHorizontal: 3, borderRadius: 32 },
  active: { flex: 1.8, flexDirection: 'row', gap: 6, backgroundColor: '#008F64', paddingHorizontal: 10 },
  icon: { position: 'relative' },
  dot: { position: 'absolute', width: 7, height: 7, borderRadius: 4, right: -2, top: -2, backgroundColor: '#10B981', borderWidth: 1, borderColor: '#FAFFFD' },
  label: { fontSize: 11, lineHeight: 16, fontWeight: '600', color: '#64748B', flexShrink: 1 },
  activeLabel: { fontSize: 13, fontWeight: '700', color: '#FFFFFF' },
  pressed: { opacity: 0.72 },
});
