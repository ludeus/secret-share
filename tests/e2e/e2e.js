/*
 * Secret Share - tests fonctionnels de bout en bout dans un vrai navigateur (Chrome)
 *
 *   E2E_BASE_URL=http://127.0.0.1:8765 node tests/e2e/e2e.js
 *
 * Variables :
 *   E2E_BASE_URL           URL de l'instance testée (obligatoire)
 *   CHROME_PATH            navigateur à piloter (défaut : /usr/bin/google-chrome)
 *   E2E_RATE_LIMIT_PAUSE   1 (défaut) pour attendre la fin de la fenêtre de limitation des
 *                          créations à mi-parcours ; 0 si l'instance n'a pas de limite
 *
 * Les secrets créés sont consommés, détruits ou expirent sous 10 minutes : aucune donnée
 * de test ne subsiste durablement sur l'instance.
 *
 * @license AGPL-3.0-or-later
 */
const puppeteer = require('puppeteer-core');
const fs = require('fs');

const BASE = (process.env.E2E_BASE_URL || '').replace(/\/$/, '');
if (!/^https?:\/\//.test(BASE)) {
    console.error('E2E_BASE_URL doit être défini (ex. http://127.0.0.1:8765)');
    process.exit(2);
}
const HTTPS = BASE.startsWith('https://');
const CHROME_PATH = process.env.CHROME_PATH || '/usr/bin/google-chrome';
const RATE_LIMIT_PAUSE = process.env.E2E_RATE_LIMIT_PAUSE !== '0';
const SHOTS = __dirname + '/shots';
fs.mkdirSync(SHOTS, {recursive: true});

let passed = 0;
let failed = 0;
function check(label, condition, detail = '') {
    if (condition) {
        passed++;
        console.log('  OK   ' + label);
    } else {
        failed++;
        console.log('  FAIL ' + label + (detail ? ' -> ' + detail : ''));
    }
}

async function newPage(browser, {lang = 'fr-FR', dark = false, mobile = false} = {}) {
    const context = await browser.createBrowserContext();
    await context.overridePermissions(BASE, ['clipboard-read', 'clipboard-write', 'clipboard-sanitized-write']);
    const page = await context.newPage();
    page.consoleErrors = [];
    page.on('console', (m) => m.type() === 'error' && page.consoleErrors.push(m.text()));
    page.on('pageerror', (e) => page.consoleErrors.push(e.message));
    await page.setExtraHTTPHeaders({'Accept-Language': lang});
    await page.emulateMediaFeatures([{name: 'prefers-color-scheme', value: dark ? 'dark' : 'light'}]);
    await page.setViewport(mobile ? {width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2} : {width: 1280, height: 900});
    return page;
}

async function createSecret(page, {secret, duration = '1d', singleUse = true, password = '', shortUrl = false}) {
    await page.goto(BASE + '/', {waitUntil: 'networkidle0'});
    await page.type('#secret', secret);
    await page.select('#duration', duration);
    const su = await page.$eval('#single_use', (e) => e.checked);
    if (su !== singleUse) await page.click('#single_use');
    if (password) await page.type('#password', password);
    if (shortUrl) await page.click('#short_url');
    const [response] = await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
    return {
        status: response.status(),
        url: await page.$eval('#share-url', (e) => e.value).catch(() => null),
        revoke: await page.$eval('#revoke-url', (e) => e.value).catch(() => null),
    };
}

async function reveal(page, url, password = null) {
    await page.goto(url, {waitUntil: 'networkidle0'});
    const addressBar = page.url();
    let [response] = await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
    if (password !== null && await page.$('#password')) {
        await page.type('#password', password);
        [response] = await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
    }
    return {
        status: response.status(),
        addressBar,
        secret: await page.$eval('textarea#secret', (e) => e.value).catch(() => null),
        hasPasswordField: !!(await page.$('#password')),
        text: await page.$eval('main', (e) => e.innerText),
    };
}

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-first-run', '--no-sandbox'],
    });

    console.log('1. Page d\'accueil');
    let page = await newPage(browser);
    const home = await page.goto(BASE + '/', {waitUntil: 'networkidle0'});
    const headers = home.headers();
    check('statut 200', home.status() === 200);
    if (HTTPS) {
        check('HSTS présent', !!headers['strict-transport-security']);
    }
    check('CSP stricte', (headers['content-security-policy'] || '').includes("script-src 'self'"));
    check('Cache-Control no-store', (headers['cache-control'] || '').includes('no-store'));
    const cookies = await page.cookies();
    const csrf = cookies.find((c) => c.name === (HTTPS ? '__Host-csrf' : 'csrf'));
    check('cookie CSRF HttpOnly/Strict (Secure en HTTPS)', csrf && csrf.httpOnly && csrf.sameSite === 'Strict' && (!HTTPS || csrf.secure));
    check('usage unique coché par défaut', await page.$eval('#single_use', (e) => e.checked));
    check('URL courte décochée par défaut', !(await page.$eval('#short_url', (e) => e.checked)));
    check('durée par défaut 1 jour', (await page.$eval('#duration', (e) => e.value)) === '1d');
    const options = await page.$$eval('#duration option', (o) => o.map((x) => x.value));
    check('7 durées + sans limite', JSON.stringify(options) === JSON.stringify(['10m', '1h', '4h', '1d', '1w', '1mo', '1y', 'none']), options.join(','));
    await page.screenshot({path: SHOTS + '/home-light.png', fullPage: true});

    console.log('2. Validation JavaScript');
    await page.click('button[type=submit]');
    await new Promise((r) => setTimeout(r, 300));
    check('pas de navigation si secret vide', page.url() === BASE + '/');
    check('message d\'erreur affiché', await page.$eval('[data-error-for=secret]', (e) => !e.hidden && e.textContent.length > 0));
    check('focus sur le champ en erreur', await page.evaluate(() => document.activeElement.id === 'secret'));
    await page.type('#secret', 'abc😀');
    check('compteur Unicode', (await page.$eval('#secret-counter', (e) => e.textContent)).startsWith('4 /'));

    console.log('3. Règle de l\'URL courte');
    await page.click('#single_use');
    await page.select('#duration', 'none');
    check('URL courte désactivée sans usage unique ni limite', await page.$eval('#short_url', (e) => e.disabled));
    check('explication visible', await page.$eval('#short-url-disabled', (e) => !e.hidden));
    await page.select('#duration', '1h');
    check('réactivée avec une durée', !(await page.$eval('#short_url', (e) => e.disabled)));
    await page.select('#duration', 'none');
    await page.click('#single_use');
    check('réactivée avec usage unique', !(await page.$eval('#short_url', (e) => e.disabled)));

    console.log('4. Validation serveur (contournement du JS)');
    await page.goto(BASE + '/', {waitUntil: 'networkidle0'});
    await page.type('#secret', 'contournement');
    await page.click('#single_use');
    await page.select('#duration', 'none');
    await page.evaluate(() => {
        const s = document.getElementById('short_url');
        s.disabled = false;
        s.checked = true;
        document.getElementById('share-form').noValidate = true;
        HTMLFormElement.prototype.submit.call(document.getElementById('share-form'));
    });
    const bypass = await page.waitForNavigation();
    check('serveur refuse (422)', bypass.status() === 422, String(bypass.status()));
    check('saisie conservée', (await page.$eval('#secret', (e) => e.value)) === 'contournement');

    console.log('5. Secret JWT à usage unique + QR + copie');
    page = await newPage(browser);
    const secret1 = 'Identifiants VPN\nuser: alice\npass: S3cr3t! é€😀 <b>&amp;</b>';
    const c1 = await createSecret(page, {secret: secret1});
    check('création 201', c1.status === 201, String(c1.status));
    check('lien de la forme /s#jwt', /\/s#[\w-]+\.[\w-]+\.[\w-]+$/.test(c1.url || ''), c1.url);
    check('QR code affiché', await page.$eval('.qr img', (e) => e.naturalWidth > 0).catch(() => false));
    const qrSrc = await page.$eval('.qr img', (e) => e.src);
    fs.writeFileSync(SHOTS + '/qr1.svg', Buffer.from(qrSrc.split(',')[1], 'base64'));
    fs.writeFileSync(SHOTS + '/qr1.expected', c1.url);
    await page.click('[data-copy=share-url]');
    await new Promise((r) => setTimeout(r, 300));
    check('bouton copier : presse-papiers = lien', (await page.evaluate(() => navigator.clipboard.readText())) === c1.url);
    check('retour visuel « Copié »', await page.$eval('[data-copy=share-url]', (e) => e.classList.contains('is-copied')));
    check('date locale formatée par JS', !(await page.$eval('time[data-local-time]', (e) => e.textContent.includes('UTC'))));
    await page.screenshot({path: SHOTS + '/created-light.png', fullPage: true});

    const recipient = await newPage(browser);
    await recipient.goto(c1.url, {waitUntil: 'networkidle0'});
    check('jeton retiré de la barre d\'adresse', !recipient.url().includes('#'), recipient.url());
    check('rien n\'est révélé sur simple ouverture', !(await recipient.$('textarea#secret')));
    const [r1] = await Promise.all([recipient.waitForNavigation(), recipient.click('button[type=submit]')]);
    const shown = await recipient.$eval('textarea#secret', (e) => e.value).catch(() => null);
    check('secret révélé identique (multiligne, unicode, HTML échappé)', shown === secret1 && r1.status() === 200, JSON.stringify(shown));
    await recipient.click('[data-copy=secret]');
    await new Promise((r) => setTimeout(r, 300));
    check('copie du secret', (await recipient.evaluate(() => navigator.clipboard.readText())) === secret1);
    check('mention de destruction', (await recipient.$eval('main', (e) => e.innerText)).includes('détruit'));
    await recipient.screenshot({path: SHOTS + '/revealed-light.png', fullPage: true});
    const again = await reveal(await newPage(browser), c1.url);
    check('2e lecture refusée (404)', again.status === 404 && again.secret === null, String(again.status));

    console.log('6. URL courte + mot de passe, multi-usage');
    page = await newPage(browser);
    const c2 = await createSecret(page, {secret: 'code coffre 1234', duration: '10m', singleUse: false, password: 'P@ss w0rd', shortUrl: true});
    check('code court de 10 caractères', /\/s#[A-Za-z0-9]{10}$/.test(c2.url || ''), c2.url);
    check('résumé : contenu sur le serveur', (await page.$eval('.summary', (e) => e.innerText)).includes('Sur le serveur'));
    const viewer = await newPage(browser, {dark: true, mobile: true});
    const p1 = await reveal(viewer, c2.url);
    check('mot de passe demandé', p1.hasPasswordField && p1.secret === null);
    await viewer.screenshot({path: SHOTS + '/password-dark-mobile.png', fullPage: true});
    await viewer.type('#password', 'mauvais');
    let [rp] = await Promise.all([viewer.waitForNavigation(), viewer.click('button[type=submit]')]);
    const wrongText = await viewer.$eval('main', (e) => e.innerText);
    check('mauvais mot de passe : 401 + essais restants', rp.status() === 401 && /4\.?$/m.test(wrongText.replace(/\s+$/, '')) || wrongText.includes(': 4'), wrongText.slice(0, 200));
    check('affichage/masquage du mot de passe', await (async () => {
        await viewer.click('[data-toggle-password=password]');
        return (await viewer.$eval('#password', (e) => e.type)) === 'text';
    })());
    await viewer.$eval('#password', (e) => (e.value = ''));
    await viewer.type('#password', 'P@ss w0rd');
    [rp] = await Promise.all([viewer.waitForNavigation(), viewer.click('button[type=submit]')]);
    check('bon mot de passe : secret affiché', (await viewer.$eval('textarea#secret', (e) => e.value).catch(() => null)) === 'code coffre 1234');
    check('mention d\'expiration (multi-usage)', (await viewer.$eval('main', (e) => e.innerText)).includes('jusqu'));
    await viewer.screenshot({path: SHOTS + '/revealed-dark-mobile.png', fullPage: true});
    const p2 = await reveal(await newPage(browser), c2.url, 'P@ss w0rd');
    check('relisible (multi-usage)', p2.secret === 'code coffre 1234', p2.text.slice(0, 150));

    console.log('7. Lien de destruction');
    page = await newPage(browser);
    const c3 = await createSecret(page, {secret: 'à révoquer', singleUse: false});
    const owner = await newPage(browser);
    await owner.goto(c3.revoke, {waitUntil: 'networkidle0'});
    check('jeton de révocation retiré de la barre d\'adresse', !owner.url().includes('#'));
    let [rr] = await Promise.all([owner.waitForNavigation(), owner.click('button[type=submit]')]);
    check('destruction confirmée (200)', rr.status() === 200 && (await owner.$eval('main', (e) => e.innerText)).includes('détruit'));
    const afterRevoke = await reveal(await newPage(browser), c3.url);
    check('secret illisible après destruction', afterRevoke.status === 404);

    // la limite de 5 créations par minute et par visiteur est atteinte : nouvelle fenêtre
    if (RATE_LIMIT_PAUSE) {
        console.log('   (pause de 60 s : limite de créations par minute)');
        await new Promise((r) => setTimeout(r, 61000));
    }

    console.log('8. Trop d\'essais de mot de passe');
    page = await newPage(browser);
    const c4 = await createSecret(page, {secret: 'brute', password: 'bonmdp'});
    const attacker = await newPage(browser);
    await attacker.goto(c4.url, {waitUntil: 'networkidle0'});
    let last = null;
    [last] = await Promise.all([attacker.waitForNavigation(), attacker.click('button[type=submit]')]);
    for (let i = 0; i < 5; i++) {
        await attacker.type('#password', 'faux' + i);
        [last] = await Promise.all([attacker.waitForNavigation(), attacker.click('button[type=submit]')]);
    }
    check('5e échec : secret détruit (410)', last.status() === 410, String(last.status()));
    const afterBrute = await reveal(await newPage(browser), c4.url, 'bonmdp');
    check('même le bon mot de passe échoue ensuite', afterBrute.status === 404);

    console.log('9. Lien incomplet et secret long');
    const broken = await newPage(browser);
    await broken.goto(BASE + '/s', {waitUntil: 'networkidle0'});
    check('lien sans jeton : alerte + bouton désactivé', await broken.$eval('[data-missing-token]', (e) => !e.hidden) && await broken.$eval('button[type=submit]', (e) => e.disabled));
    page = await newPage(browser);
    const longSecret = 'x'.repeat(3000);
    await page.goto(BASE + '/', {waitUntil: 'networkidle0'});
    await page.$eval('#secret', (e, v) => (e.value = v), longSecret);
    await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
    check('lien trop long pour un QR : message explicite', !(await page.$('.qr img')) && (await page.$eval('main', (e) => e.innerText)).includes('URL courte'));
    const longUrl = await page.$eval('#share-url', (e) => e.value);
    const longReveal = await reveal(await newPage(browser), longUrl);
    check('secret de 3000 caractères restitué', longReveal.secret === longSecret);

    console.log('10. Anglais, mode sombre, mobile');
    const en = await newPage(browser, {lang: 'en-US,en', dark: true, mobile: true});
    await en.goto(BASE + '/', {waitUntil: 'networkidle0'});
    check('interface en anglais', (await en.$eval('html', (e) => e.lang)) === 'en' && (await en.$eval('h1', (e) => e.textContent)).includes('Share a secret'));
    const bg = await en.$eval('body', (e) => getComputedStyle(e).backgroundColor);
    check('thème sombre appliqué', bg === 'rgb(14, 16, 20)', bg);
    check('pas de défilement horizontal sur mobile', await en.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
    await en.screenshot({path: SHOTS + '/home-en-dark-mobile.png', fullPage: true});

    console.log('11. Sélecteur de langue');
    const switcher = await newPage(browser);
    await switcher.goto(BASE + '/s?lang=fr#abc.def.ghi', {waitUntil: 'networkidle0'});
    await Promise.all([switcher.waitForNavigation(), switcher.select('#lang-select', 'es')]);
    check('page traduite après changement de langue', (await switcher.$eval('html', (e) => e.lang)) === 'es');
    check('jeton du lien conservé', (await switcher.$eval('[data-fragment-token]', (e) => e.value)) === 'abc.def.ghi');
    const ar = await newPage(browser);
    await ar.goto(BASE + '/?lang=ar', {waitUntil: 'networkidle0'});
    check('arabe écrit de droite à gauche', (await ar.$eval('html', (e) => e.dir)) === 'rtl');
    check('fichiers statiques versionnés', /\/assets\/app\.css\?v=\d+/.test(await ar.content()));

    const pages = (await Promise.all(browser.browserContexts().map((c) => c.pages()))).flat();
    const errors = pages.flatMap((p) => p.consoleErrors || []).filter((e) => !e.includes('status of 4'));
    check('aucune erreur JavaScript ni violation CSP', errors.length === 0, errors.join(' | '));

    await browser.close();
    console.log(`\n${passed} OK, ${failed} échec(s)`);
    process.exit(failed ? 1 : 0);
})().catch((e) => {
    console.error(e);
    process.exit(2);
});
