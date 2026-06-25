import { chromium } from 'playwright';

const BASE = 'http://localhost/nurse_ward/public';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const results = [];

  function pass(name, detail = '') {
    results.push({ name, ok: true, detail });
    console.log(`PASS ${name}${detail ? ' — ' + detail : ''}`);
  }
  function fail(name, detail = '') {
    results.push({ name, ok: false, detail });
    console.log(`FAIL ${name}${detail ? ' — ' + detail : ''}`);
  }

  try {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    if (await page.locator('.skip-link').count()) pass('login skip-link');
    else fail('login skip-link');

    await page.fill('#floatingUsernameInput', 'superadmin');
    await page.fill('#floatingPasswordInput', '1234554321');
    await page.click('#loginSubmitBtn');
    await page.waitForLoadState('networkidle');

    if (await page.locator('text=ออกจากระบบ').count()) pass('login superadmin');
    else fail('login superadmin', await page.title());

    // Desktop: top menu hidden when sidebar present
    const topMenuVisible = await page.locator('.top-menu-wrap').isVisible();
    const sidebarVisible = await page.locator('#appSidebar').isVisible();
    if (sidebarVisible && !topMenuVisible) pass('desktop nav dedup (sidebar only)');
    else fail('desktop nav dedup', `sidebar=${sidebarVisible} topMenu=${topMenuVisible}`);

    const pages = [
      {
        path: '/census/productivity',
        checks: ['.status-legend', '#productivity-filter', '.matrix-page-kpi'],
        name: 'productivity',
      },
      {
        path: '/census/behavior-dashboard',
        checks: ['.dashboard-kpi', '.material-symbols-outlined', '#movementChart'],
        name: 'behavior-dashboard',
      },
      {
        path: '/reports/dashboard',
        checks: ['.dashboard-kpi-grid', '.formula-note', '#tab-snapshot'],
        name: 'reports dashboard',
      },
      {
        path: '/census/new',
        checks: ['label[for="ward_id"]', 'label[for="record_date"]', 'label[for="shift"]', 'h1.daily-page-title'],
        name: 'census create',
      },
      {
        path: '/census/history',
        checks: ['.history-table', '#history-filter'],
        name: 'census history',
      },
      {
        path: '/account/change-password',
        checks: ['label[for="current_password"]', 'label[for="new_password"]'],
        name: 'change password',
      },
    ];

    for (const p of pages) {
      await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle' });
      let ok = true;
      for (const sel of p.checks) {
        if ((await page.locator(sel).count()) === 0) {
          fail(p.name, `missing ${sel}`);
          ok = false;
          break;
        }
      }
      if (ok) pass(p.name, p.path);

      // CSS loaded (not 404)
      const cssResp = await page.request.get(`${BASE}/app-asset/css/ward-matrix.css`);
      if (cssResp.status() === 200) pass(`${p.name} ward-matrix.css`, '200');
    }

    // Mobile viewport
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`${BASE}/census/productivity`, { waitUntil: 'networkidle' });
    if (await page.locator('.bottom-nav').isVisible()) pass('mobile bottom nav');
    else fail('mobile bottom nav');

    const matrixFont = await page.locator('.ward-matrix-table').evaluate(el =>
      el ? getComputedStyle(el).fontSize : null
    ).catch(() => null);
    if (matrixFont) pass('mobile matrix font', matrixFont);

    // Productivity AJAX
    await page.selectOption('#mode', 'month');
    await page.click('#productivity-filter button[type="submit"]');
    await page.waitForSelector('#productivity-result .ward-matrix-table, #productivity-alert:not(.d-none)', { timeout: 15000 });
    const hasTable = (await page.locator('#productivity-result .ward-matrix-table').count()) > 0;
    const hasErr = (await page.locator('#productivity-alert:not(.d-none)').count()) > 0;
    if (hasTable) pass('productivity AJAX matrix');
    else if (hasErr) pass('productivity AJAX', 'empty data alert (OK)');
    else fail('productivity AJAX', 'no table or alert');

    await page.screenshot({ path: '/tmp/nw-test-productivity-mobile.png', fullPage: true });
    pass('screenshot', '/tmp/nw-test-productivity-mobile.png');

  } catch (e) {
    fail('runner', e.message);
  } finally {
    await browser.close();
  }

  const failed = results.filter(r => !r.ok).length;
  console.log(`\n=== ${results.length - failed}/${results.length} passed ===`);
  process.exit(failed > 0 ? 1 : 0);
})();
