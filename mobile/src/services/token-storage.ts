import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

export interface TokenStorage {
  getToken(): Promise<string | null>;
  setToken(token: string): Promise<void>;
  clearToken(): Promise<void>;
}

const TOKEN_KEY = 'ecotrack.access-token';
const options: SecureStore.SecureStoreOptions = {
  keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
};

// Web hanya menyimpan sesi di memori; jangan berbagi token antar-request SSR.
let webToken: string | null = null;

export const tokenStorage: TokenStorage = {
  async getToken() {
    if (Platform.OS === 'web') {
      return typeof window === 'undefined' ? null : webToken;
    }
    return SecureStore.getItemAsync(TOKEN_KEY, options);
  },

  async setToken(token) {
    if (!token.trim() || /\s/.test(token)) {
      throw new Error('Token autentikasi tidak valid.');
    }
    if (Platform.OS === 'web') {
      if (typeof window === 'undefined') {
        throw new Error('Token Web hanya boleh disimpan di browser.');
      }
      webToken = token;
      return;
    }
    await SecureStore.setItemAsync(TOKEN_KEY, token, options);
  },

  async clearToken() {
    if (Platform.OS === 'web') {
      webToken = null;
      return;
    }
    await SecureStore.deleteItemAsync(TOKEN_KEY, options);
  },
};
