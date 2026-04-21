// UI flow tests for the WPB2Elementor plugin running at localhost:10038
const { test, expect } = require('@playwright/test');

const WP_URL      = 'http://localhost:10038';
const WP_USER     = 'alexander';
const WP_PASS     = 'N$WT^&vY2lDvau1mihjtJ#f7';
const PLUGIN_PAGE = `${WP_URL}/wp-admin/admin.php?page=wpb2elementor`;
const TIMEOUT = 90_000;

async function login(page) {
  await page.goto(`${WP_URL}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  if ( page.url().includes('/wp-admin') ) return;
  await page.fill('#user_login', WP_USER);
  await page.fill('#user_pass', WP_PASS);
  await page.click('#wp-submit');
  await page.waitForURL('**/wp-admin/**');
}

async function goToPlugin(page) {
  await page.goto(PLUGIN_PAGE, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('h1')).toContainText('WPB2Elementor');
}

test.describe('WPB2Elementor plugin', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  // ── Layout ────────────────────────────────────────────────────────────────

  test('admin page lädt ohne kritischen Fehler', async ({ page }) => {
    await goToPlugin(page);
    await expect(page.locator('.wpb2el-wrap')).toBeVisible();
    await expect(page.locator('body')).not.toContainText('kritischen Fehler');
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('Flexbox Container Warnung erscheint nicht wenn Container aktiv', async ({ page }) => {
    await goToPlugin(page);
    const warning = page.locator('.wpb2el-notice.error');
    // Entweder kein Fehler-Banner, oder es enthält nicht "Flexbox Container"
    const count = await warning.count();
    if (count > 0) {
      await expect(warning).not.toContainText('Flexbox Container');
    }
  });

  test('Seitenliste zeigt Seiten an', async ({ page }) => {
    await goToPlugin(page);
    const rows = page.locator('.wp-list-table tbody tr');
    await expect(rows).toHaveCount( await rows.count() ); // mind. vorhanden
    expect( await rows.count() ).toBeGreaterThan(0);
  });

  test('Buttons ragen nicht aus der Tabelle heraus', async ({ page }) => {
    await goToPlugin(page);
    const table = page.locator('.wp-list-table');
    const tableBox = await table.boundingBox();
    const buttons  = page.locator('.wpb2el-actions .button');
    const count    = await buttons.count();
    for (let i = 0; i < count; i++) {
      const btn = buttons.nth(i);
      if ( ! await btn.isVisible() ) continue;
      const box = await btn.boundingBox();
      if (!box || !tableBox) continue;
      expect(box.x + box.width).toBeLessThanOrEqual(tableBox.x + tableBox.width + 5); // 5px Toleranz
    }
  });

  // ── Konvertierungs-Flow ────────────────────────────────────────────────────

  test('Konvertieren → Erfolgsmeldung → Buttons erscheinen', async ({ page }) => {
    await goToPlugin(page);

    // Finde erste WPBakery-Seite
    const convertBtn = page.locator('.wpb2el-actions form button[type="submit"]').first();
    const rowBefore  = convertBtn.locator('xpath=ancestor::tr');
    await expect(rowBefore.locator('.status-wpbakery')).toBeVisible();

    // Konvertieren klicken und auf Redirect + Notice warten
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: TIMEOUT }),
      convertBtn.click(),
    ]);

    // Erfolgsmeldung
    await expect(page.locator('.wpb2el-notice.success')).toBeVisible({ timeout: TIMEOUT });
    await expect(page.locator('.wpb2el-notice.success')).toContainText('Entwurf');
  });

  test('nach Konvertierung: Elementor-Status + alle 3 Buttons', async ({ page }) => {
    await goToPlugin(page);

    // Finde eine bereits konvertierte Zeile (Status ✅)
    const elementorRow = page.locator('tr').filter({ has: page.locator('.status-elementor') }).first();
    const count = await elementorRow.count();

    if (count === 0) {
      // Erst konvertieren
      const convertBtn = page.locator('.wpb2el-actions form button[type="submit"]').first();
      await convertBtn.click();
      await page.waitForURL('**/wpb2elementor**');
    }

    await goToPlugin(page);
    const row = page.locator('tr').filter({ has: page.locator('.status-elementor') }).first();
    await expect(row.locator('.status-elementor')).toBeVisible();
    await expect(row.locator('a:has-text("Mit Elementor bearbeiten")')).toBeVisible();
    await expect(row.locator('a:has-text("Seite ansehen")')).toBeVisible();
    await expect(row.locator('button:has-text("Zurücksetzen")')).toBeVisible();
  });

  test('Seite ansehen öffnet eine Vorschau-URL', async ({ page, context }) => {
    await goToPlugin(page);
    const previewLink = page.locator('a:has-text("Seite ansehen")').first();
    const count = await previewLink.count();
    if (count === 0) test.skip();

    const href = await previewLink.getAttribute('href');
    expect(href).toBeTruthy();
    expect(href).toContain('preview');
  });

  test('Zurücksetzen löscht Kopie und zeigt wieder Konvertieren-Button', async ({ page }) => {
    await goToPlugin(page);

    // Stelle sicher, dass mind. eine konvertierte Seite vorhanden ist
    let elementorRow = page.locator('tr').filter({ has: page.locator('.status-elementor') }).first();
    if ( await elementorRow.count() === 0 ) {
      const convertBtn = page.locator('.wpb2el-actions form button[type="submit"]').first();
      await convertBtn.click();
      await page.waitForURL('**/wpb2elementor**');
      await goToPlugin(page);
      elementorRow = page.locator('tr').filter({ has: page.locator('.status-elementor') }).first();
    }

    // Titel merken
    const title = await elementorRow.locator('td').first().textContent();

    // Zurücksetzen bestätigen
    page.once('dialog', d => d.accept());
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: TIMEOUT }),
      elementorRow.locator('button:has-text("Zurücksetzen")').click(),
    ]);

    // Erfolgsmeldung
    await expect(page.locator('.wpb2el-notice.success')).toContainText('gelöscht');

    // Zeile zeigt wieder WPBakery
    const restoredRow = page.locator('tr').filter({ hasText: title?.trim() ?? '' }).first();
    await expect(restoredRow.locator('.status-wpbakery')).toBeVisible();
    await expect(restoredRow.locator('button:has-text("Konvertieren")')).toBeVisible();
  });
});
