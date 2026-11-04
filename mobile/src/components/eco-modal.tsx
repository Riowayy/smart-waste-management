import type { ReactNode } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

export default function EcoModal({ visible, onClose, onDismiss, sheet = false, children }: {
  visible: boolean; onClose: () => void; onDismiss?: () => void; sheet?: boolean; children: ReactNode;
}) {
  return <Modal visible={visible} transparent animationType={sheet ? 'none' : 'fade'} onRequestClose={onClose} onDismiss={onDismiss}>
    <View style={[styles.overlay, sheet && styles.sheetOverlay]}>
      <Pressable accessibilityRole="button" accessibilityLabel="Tutup dialog" onPress={onClose} style={StyleSheet.absoluteFill}/>
      <SafeAreaView edges={['top', 'bottom', 'left', 'right']} style={[styles.area, sheet && styles.sheetArea]}>
        <View accessibilityViewIsModal style={[styles.card, sheet && styles.sheet]}>
          <ScrollView bounces={false} contentContainerStyle={styles.content}>
            {sheet && <View style={styles.handle}/>}{children}
          </ScrollView>
        </View>
      </SafeAreaView>
    </View>
  </Modal>;
}

const styles = StyleSheet.create({
  overlay: { flex: 1, backgroundColor: 'rgba(8,35,26,0.48)', justifyContent: 'center', alignItems: 'center', padding: 20 },
  sheetOverlay: { justifyContent: 'flex-end', padding: 0 },
  area: { width: '100%', maxWidth: 460, maxHeight: '100%' },
  sheetArea: { maxWidth: 560, backgroundColor: '#FFFFFF', borderTopLeftRadius: 28, borderTopRightRadius: 28 },
  card: { backgroundColor: '#FFFFFF', borderRadius: 24, overflow: 'hidden', shadowColor: '#123E2C', shadowOffset: { width: 0, height: 5 }, shadowOpacity: 0.12, shadowRadius: 18, elevation: 6 },
  sheet: { borderBottomLeftRadius: 0, borderBottomRightRadius: 0 },
  content: { padding: 24, gap: 16 },
  handle: { width: 38, height: 4, borderRadius: 2, backgroundColor: '#C9DED3', alignSelf: 'center', marginBottom: 4 },
});
