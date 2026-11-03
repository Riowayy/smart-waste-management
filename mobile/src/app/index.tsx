import { ScrollView, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';

export default function HomeScreen() {
  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.container}>
        <ScrollView contentContainerStyle={styles.scrollContent}>
          <ThemedView style={styles.content}>
            <ThemedText type="title" themeColor="brand" accessibilityRole="header">
              EcoTrack
            </ThemedText>
            <ThemedText type="subtitle">Peduli lingkungan, mulai dari sekitar.</ThemedText>
            <ThemedText themeColor="textSecondary">
              Bersama menjaga lingkungan melalui pengelolaan sampah yang lebih baik.
            </ThemedText>
            <ThemedText type="small" themeColor="textSecondary" style={styles.notice}>
              Aplikasi sedang disiapkan. Fitur pelaporan akan segera tersedia.
            </ThemedText>
          </ThemedView>
        </ScrollView>
      </SafeAreaView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: Spacing.four,
  },
  content: {
    width: '100%',
    maxWidth: MaxContentWidth,
    alignSelf: 'center',
    gap: Spacing.three,
  },
  notice: {
    marginTop: Spacing.four,
  },
});
