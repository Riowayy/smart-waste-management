import type { Coordinates } from './service';
export function mapTiles(point: Coordinates, width: number, height = 220) {
  if (!Number.isFinite(point.latitude) || !Number.isFinite(point.longitude) || Math.abs(point.latitude) > 85.05112878 || Math.abs(point.longitude) > 180) return [];
  const zoom = 16, count = 2 ** zoom, size = 256;
  const longitude = point.longitude === 180 ? -180 : point.longitude;
  const x = (longitude + 180) / 360 * count;
  const radians = point.latitude * Math.PI / 180;
  const y = (1 - Math.asinh(Math.tan(radians)) / Math.PI) / 2 * count;
  const tiles = [];
  for (let row = Math.floor(y - height / 2 / size); row <= Math.floor(y + height / 2 / size); row++) {
    if (row < 0 || row >= count) continue;
    for (let col = Math.floor(x - width / 2 / size); col <= Math.floor(x + width / 2 / size); col++) {
      const tileX = ((col % count) + count) % count;
      tiles.push({ key: `${zoom}/${tileX}/${row}`, uri: `https://tile.openstreetmap.org/${zoom}/${tileX}/${row}.png`, left: width / 2 + (col - x) * size, top: height / 2 + (row - y) * size });
    }
  }
  return tiles;
}
