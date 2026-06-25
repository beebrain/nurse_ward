const { chromium } = require('playwright');

const BASE = 'http://localhost/nurse_ward/public';

(async () => {
  const headed = process.env.HEADED !== '0';
  const browser = await chromium.launch({
    channel: 'chrome',
    headless: !headed,
  });
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  let pass = 0, fail = 0;
  const ok = (m) => { console.log('PASS', m); pass++; };
  const bad = (m, d = '') => { console.log('FAIL', m, d); fail++; };

  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  (await page.locator('.skip-link').count()) ? ok('login skip-link') : bad('login skip-link');

  await page.fill('#floatingUsernameInput', 'superadmin');
  await page.fill('#floatingPasswordInput', '1234554321');
  await page.click('#loginSubmitBtn');
  await page.waitForLoadState('networkidle');

  (await page.locator('text=ออกจากระบบ').count())
    ? ok('login as superadmin')
    : bad('login as superadmin', await page.url());

  const side = await page.locator('#appSidebar').isVisible();
  const top = await page.locator('.top-menu-wrap').isVisible();
  side && !top ? ok('desktop nav (sidebar only)') : bad('desktop nav', `side=${side} top=${top}`);

  for (const [path, sel, label] of [
    ['/census/productivity', '.status-legend-chip', 'productivity legend chips'],
    ['/census/behavior-dashboard', '.dashboard-kpi', 'behavior KPI'],
    ['/reports/dashboard', '.dashboard-page-title', 'reports dashboard header'],
    ['/census/new', 'label[for="ward_id"]', 'census labels'],
  ]) {
    await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    (await page.locator(sel).count()) ? ok(label) : bad(label, `missing ${sel}`);
  }

  await page.goto(`${BASE}/census/behavior-dashboard`, { waitUntil: 'networkidle' });
  (await page.locator('.fas').count()) === 0 ? ok('no FontAwesome') : bad('FontAwesome still present');

  await page.click('#btnFilter');
  await page.waitForTimeout(3000);
  const charts = await page.locator('#dashboardContent:not(.d-none) #movementChart').count();
  charts ? ok('behavior charts load') : bad('behavior charts load');

  const css = (await page.request.get(`${BASE}/app-asset/css/ward-matrix.css`)).status();
  css === 200 ? ok('ward-matrix.css 200') : bad('ward-matrix.css', String(css));

  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(`${BASE}/census/productivity`, { waitUntil: 'networkidle' });
  (await page.locator('.bottom-nav').isVisible()) ? ok('mobile bottom nav') : bad('mobile bottom nav');

  await page.click('#productivity-filter button[type="submit"]');
  await page.waitForTimeout(4000);
  const table = await page.locator('#productivity-result .ward-matrix-table').count();
  table ? ok('productivity matrix AJAX') : bad('productivity matrix AJAX');

  await page.screenshot({ path: '/tmp/nw-productivity-mobile.png', fullPage: true });
  ok('screenshot /tmp/nw-productivity-mobile.png');

  await browser.close();
  console.log(`\n=== ${pass}/${pass + fail} passed ===`);
  process.exit(fail ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
