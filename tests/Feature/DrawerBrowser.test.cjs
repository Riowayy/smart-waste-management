const { test } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

// Gunakan instalasi Playwright existing; tidak memasang dependency proyek.
const { chromium } = require(process.env.ECOTRACK_PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'public/build/manifest.json'), 'utf8'));
const assets = new Map(Object.values(manifest).map(entry => ['/build/' + entry.file, entry.file]));

for (const role of ['admin', 'user']) {
  test(`drawer ${role}: keyboard, penutupan, breakpoint dan preferensi desktop`, async () => {
    const html = execFileSync('php', [path.join(__dirname, 'DrawerFixture.php'), role], { cwd: root, encoding: 'utf8' });
    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    try {
      const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.route('**/*', route => {
        const pathname = new URL(route.request().url()).pathname;
        if (pathname === '/') return route.fulfill({ contentType: 'text/html', body: html });
        const asset = assets.get(pathname);
        if (asset) return route.fulfill({ contentType: asset.endsWith('.css') ? 'text/css' : 'application/javascript', body: fs.readFileSync(path.join(root, 'public/build', asset)) });
        return route.fulfill({ status: 404, body: '' });
      });
      await page.goto('http://ecotrack.test/');
      const state = () => page.evaluate(() => ({
        inert: document.getElementById('adminSidebar').inert,
        open: document.getElementById('adminSidebar').classList.contains('is-open'),
        overlay: document.getElementById('sidebarOverlay').classList.contains('is-visible'),
        scrollLocked: document.body.classList.contains('overflow-hidden'),
        expanded: document.querySelector('[data-sidebar-toggle]').getAttribute('aria-expanded'),
      }));
      await page.waitForFunction(() => document.getElementById('adminSidebar').inert);
      assert.deepEqual(await state(), { inert: true, open: false, overlay: false, scrollLocked: false, expanded: 'false' });
      await page.locator('[data-sidebar-toggle]').focus();
      await page.keyboard.press('Tab');
      assert.equal(await page.evaluate(() => document.getElementById('adminSidebar').contains(document.activeElement)), false);
      await page.locator('[data-sidebar-toggle]').click();
      assert.deepEqual(await state(), { inert: false, open: true, overlay: true, scrollLocked: true, expanded: 'true' });
      assert.equal(await page.evaluate(() => document.activeElement === document.querySelector('.sidebar-navigation .sidebar-link')), true);
      await page.locator('.sidebar-logout').focus();
      await page.keyboard.press('Tab');
      assert.equal(await page.evaluate(() => document.activeElement === document.querySelector('.sidebar-brand')), true);
      await page.keyboard.press('Shift+Tab');
      assert.equal(await page.evaluate(() => document.activeElement === document.querySelector('.sidebar-logout')), true);
      await page.keyboard.press('Escape');
      assert.equal((await state()).inert, true);
      assert.equal(await page.evaluate(() => document.activeElement.hasAttribute('data-sidebar-toggle')), true);
      for (const close of ['[data-sidebar-close]', '#sidebarOverlay']) {
        await page.locator('[data-sidebar-toggle]').click();
        if (close === '#sidebarOverlay') await page.locator(close).click({ position: { x: 350, y: 400 } });
        else await page.locator(close).click();
        assert.equal((await state()).open, false);
        assert.equal(await page.evaluate(() => document.activeElement.hasAttribute('data-sidebar-toggle')), true);
      }
      await page.locator('[data-sidebar-toggle]').click();
      await page.locator('[data-sidebar-close]').focus();
      await page.setViewportSize({ width: 1280, height: 800 });
      await page.waitForFunction(() => !document.body.classList.contains('overflow-hidden'));
      assert.deepEqual(await state(), { inert: false, open: false, overlay: false, scrollLocked: false, expanded: 'true' });
      assert.equal(await page.evaluate(() => document.activeElement === document.querySelector('.sidebar-navigation .sidebar-link')), true);
      await page.locator('[data-sidebar-collapse]').click();
      assert.equal(await page.evaluate(() => document.activeElement.hasAttribute('data-sidebar-expand')), true);
      assert.equal(await page.evaluate(() => sessionStorage.getItem(document.documentElement.dataset.sidebarStorage)), 'true');
      await page.setViewportSize({ width: 390, height: 844 });
      await page.waitForFunction(() => document.getElementById('adminSidebar').inert);
      assert.equal(await page.evaluate(() => document.activeElement.hasAttribute('data-sidebar-toggle')), true);
      await page.locator('[data-sidebar-toggle]').click();
      await page.setViewportSize({ width: 1280, height: 800 });
      await page.waitForFunction(() => !document.body.classList.contains('overflow-hidden'));
      assert.equal((await state()).expanded, 'false');
      assert.equal((await state()).inert, false);
      await page.locator('[data-sidebar-expand]').click();
      assert.equal(await page.evaluate(() => document.activeElement.hasAttribute('data-sidebar-collapse')), true);
      assert.equal(await page.evaluate(() => sessionStorage.getItem(document.documentElement.dataset.sidebarStorage)), 'false');
      assert.deepEqual(errors, []);
    } finally { await browser.close(); }
  });
}
