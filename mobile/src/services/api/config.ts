export function getApiBaseUrl(): string {
  const value = process.env.EXPO_PUBLIC_API_URL?.trim();

  if (!value) {
    throw new Error('EXPO_PUBLIC_API_URL belum diatur pada environment mobile.');
  }

  let url: URL;
  try {
    url = new URL(value);
  } catch {
    throw new Error('EXPO_PUBLIC_API_URL harus berupa URL HTTP/HTTPS yang valid.');
  }

  if (
    !['http:', 'https:'].includes(url.protocol) ||
    url.username || url.password || url.search || url.hash ||
    !url.pathname.replace(/\/+$/, '').endsWith('/api/v1')
  ) {
    throw new Error('URL API harus memakai HTTP/HTTPS, berakhir /api/v1, tanpa kredensial, query, atau fragment.');
  }

  return url.toString().replace(/\/+$/, '');
}
