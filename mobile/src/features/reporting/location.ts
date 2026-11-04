import * as Location from 'expo-location';

// Satu akuisisi, termasuk saat registrasi native belum mengembalikan subscription.
let acquiring = false;

export async function freshLocation(signal: AbortSignal): Promise<Location.LocationObject> {
  if (acquiring) throw new Error('GPS masih menyelesaikan permintaan sebelumnya. Coba lagi sebentar.');
  if (signal.aborted) throw new Error('Pengambilan lokasi dibatalkan.');
  acquiring = true;
  const started = Date.now();
  return new Promise((resolve, reject) => {
    let subscription: Location.LocationSubscription | undefined;
    let settled = false;
    let registrationFinished = true;
    const cleanup = () => {
      clearTimeout(timer);
      signal.removeEventListener('abort', cancel);
      subscription?.remove();
      if (registrationFinished) acquiring = false;
    };
    const finish = (point?: Location.LocationObject, error?: Error) => {
      if (settled) return;
      settled = true;
      cleanup();
      if (point) resolve(point); else reject(error);
    };
    const cancel = () => finish(undefined, new Error('Pengambilan lokasi dibatalkan.'));
    const timer = setTimeout(() => finish(undefined, new Error('Lokasi terbaru belum ditemukan. Coba di area terbuka lalu perbarui lokasi.')), 20000);
    signal.addEventListener('abort', cancel, { once: true });
    if (signal.aborted) { registrationFinished = true; cancel(); return; }
    void Location.hasServicesEnabledAsync().then(enabled => {
      if (settled) return;
      if (!enabled) { finish(undefined, new Error('GPS tidak aktif. Aktifkan layanan lokasi lalu coba lagi.')); return; }
      registrationFinished = false;
      return Location.watchPositionAsync({ accuracy: Location.Accuracy.Balanced, distanceInterval: 0, timeInterval: 1000 }, point => {
        // Provider dapat mengirim fix lama terlebih dahulu; jangan menyebutnya lokasi terbaru.
        if (!Number.isFinite(point.timestamp) || point.timestamp < started) return;
        const { latitude, longitude } = point.coords;
        if (!Number.isFinite(latitude) || Math.abs(latitude) > 90 || !Number.isFinite(longitude) || Math.abs(longitude) > 180) return;
        finish(point);
      }, () => finish(undefined, new Error('GPS belum dapat memperoleh lokasi. Periksa izin dan layanan lokasi lalu coba lagi.'))).then(value => {
        registrationFinished = true;
        subscription = value;
        if (settled) cleanup();
      }, () => {
        registrationFinished = true;
        if (settled) acquiring = false;
        else finish(undefined, new Error('GPS gagal dimulai. Periksa izin lokasi lalu coba lagi.'));
        });
    }).catch(() => finish(undefined, new Error('Layanan GPS belum dapat diperiksa. Coba lagi.')));
  });
}
