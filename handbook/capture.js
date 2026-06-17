// Capture handbook screenshots via headless Chrome (puppeteer-core drives the installed Chrome).
// Regenerates the JPEGs in handbook/img/. Requires the php-backend (:8000) + MySQL running.
// Setup:  npm i puppeteer-core@23
// Usage:  node handbook/capture.js
const puppeteer = require('puppeteer-core');
const path = require('path');

const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const B = 'http://localhost:8000';
const IMG = path.join(__dirname, 'img');
const ADMIN_PASS = 'MouazAdmin#2026';
const VIEW = { width: 1280, height: 860, deviceScaleFactor: 1 };

const shot = async (page, name, opts = {}) => {
  await new Promise(r => setTimeout(r, 600)); // settle fonts/animations
  await page.screenshot({ path: path.join(IMG, name), type: 'jpeg', quality: 80, fullPage: !!opts.full });
  console.log('  saved', name);
};

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--hide-scrollbars'] });
  const page = await browser.newPage();
  await page.setViewport(VIEW);
  const go = async (url) => page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

  // ---- Unauthenticated shots ----
  await go(`${B}/`);                                                   await shot(page, '01-dev-servers.jpg');
  await go(`${B}/work/case-study/case-study-balady-app.html`);        await shot(page, '02-rich-case-study.jpg');
  await go(`${B}/rec/collect.php?action=admin`);                      await shot(page, '03-admin-login.jpg');
  await go(`${B}/i18n/demo.html`);                                    await shot(page, '07-i18n-demo-en.jpg');
  await go(`${B}/i18n/demo.html?lang=ar`);                            await shot(page, '08-i18n-demo-ar-rtl.jpg');
  await go(`${B}/rec/index.html`);                                    await shot(page, '09-recommendation-form.jpg');

  // ---- Log in as admin (CSRF token already in the form) ----
  await go(`${B}/rec/collect.php?action=admin`);
  await page.type('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('button[type="submit"]')]);
  console.log('  logged in:', page.url());

  // ---- New-page form (fill, capture, then save draft) ----
  await go(`${B}/rec/pages.php?action=new`);
  await page.type('input[name="title"]', 'Quarterly Brand Report');
  await page.type('input[name="slug"]', 'handbook-demo-page');
  await page.select('select[name="type"]', 'case-study');
  await page.type('input[name="og_image"]', 'assets/images/projects/project1.webp');
  await page.type('textarea[name="body_html"]', '<p>A concise narrative of the engagement, delivering <strong>3.2x</strong> brand lift.</p>');
  await shot(page, '04-pages-new-form.jpg');

  // Save Draft = the submit button WITHOUT name="publish"
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.evaluate(() => {
      const btns = [...document.querySelectorAll('form[action*="action=save"] button[type="submit"]')];
      (btns.find(b => !b.name) || btns[0]).click();
    }),
  ]);
  await shot(page, '05-pages-list-draft.jpg');

  // ---- Publish via Edit -> Publish so the redirect carries &card=ID (shows the card snippet) ----
  await page.evaluate(() => { document.querySelector('a[href*="action=edit"]').click(); });
  await page.waitForNavigation({ waitUntil: 'networkidle2' });
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.click('button[name="publish"]'),
  ]);
  await shot(page, '06-published-card-snippet.jpg');

  // ---- Recommendations admin dashboard ----
  await go(`${B}/rec/collect.php?action=admin`);
  await shot(page, '10-admin-dashboard.jpg');

  await browser.close();
  console.log('done');
})().catch(e => { console.error('CAPTURE ERROR', e); process.exit(1); });
