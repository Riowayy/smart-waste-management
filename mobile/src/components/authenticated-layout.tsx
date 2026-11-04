import type { ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import BottomNavigation from './bottom-navigation';
import { NavigationLockProvider } from './navigation-lock';

export default function AuthenticatedLayout({ authenticated, children }: { authenticated: boolean; children: ReactNode }) {
  const insets = useSafeAreaInsets();
  return <NavigationLockProvider>
    <View style={[styles.shell, authenticated && { paddingBottom: insets.bottom }]}>
      <View style={styles.content}>{children}</View>
      {authenticated && <BottomNavigation />}
    </View>
  </NavigationLockProvider>;
}

const styles = StyleSheet.create({
  shell: { flex: 1, backgroundColor: '#E7FFF3' },
  content: { flex: 1 },
});
