import * as Location from 'expo-location';

export async function lookupReportAddress(coordinates: {latitude:number;longitude:number}): Promise<string | null> {
  let timer:ReturnType<typeof setTimeout>|undefined;
  try {
    const addresses = await Promise.race([
      Location.reverseGeocodeAsync(coordinates),
      new Promise<never>((_, reject) => { timer = setTimeout(() => reject(new Error('Alamat belum tersedia.')), 8000); }),
    ]);
    const place = addresses[0];
    return place
      ? [...new Set([place.street, place.streetNumber, place.district, place.city, place.region, place.postalCode].filter(Boolean))].join(', ')
      : null;
  } catch {
    // Alamat opsional; jangan menahan GPS atau pengiriman laporan.
    return null;
  } finally {
    clearTimeout(timer);
  }
}
