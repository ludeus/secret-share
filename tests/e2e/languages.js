/*
 * Traduction sans navigation : formulaires, jetons, liens et secrets conservés.
 * E2E_BASE_URL=http://127.0.0.1:8765 node tests/e2e/languages.js
 * @license AGPL-3.0-or-later
 */
const assert = require('node:assert/strict');
const puppeteer = require('puppeteer-core');

const base = (process.env.E2E_BASE_URL || '').replace(/\/$/, '');
if (!/^https?:\/\//.test(base)) {
    throw new Error('E2E_BASE_URL doit être défini');
}

(async () => {
    const browser = await puppeteer.launch({
        executablePath: process.env.CHROME_PATH || '/usr/bin/google-chrome',
        headless: true,
        args: ['--no-sandbox', '--no-first-run'],
    });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => {
            if (message.type() === 'error' && !message.text().includes('status of 404')) {
                errors.push(message.text());
            }
        });
        await page.setViewport({width: 1100, height: 1080});
        await page.setExtraHTTPHeaders({'Accept-Language': 'fr-FR'});
        await page.emulateMediaFeatures([{name: 'prefers-color-scheme', value: 'light'}]);

        async function changeLanguage(locale) {
            await page.evaluate(() => { window.languageTestSentinel = true; });
            const originalUrl = page.url();
            await page.select('#lang-select', locale);
            await page.waitForFunction((value) => document.documentElement.lang === value
                && !document.querySelector('#lang-select').disabled, {}, locale);
            assert.equal(await page.evaluate(() => window.languageTestSentinel), true, 'Aucune navigation');
            assert.equal(new URL(page.url()).pathname, new URL(originalUrl).pathname);
            assert.equal(await page.$eval('html', (element) => element.dir), locale === 'ar' ? 'rtl' : 'ltr');
        }

        await page.goto(base + '/?lang=fr', {waitUntil: 'networkidle0'});
        assert.equal(await page.$('.lang-icon'), null);
        await page.click('button[type="submit"]');
        assert.equal(await page.$eval('#secret-error', (element) => element.hidden), false);
        await changeLanguage('en');
        assert.match(await page.$eval('#secret-error', (element) => element.textContent), /Please/);
        await page.type('#secret', 'home.title <b>secret conservé</b>');
        await page.type('#password', 'mot-de-passe-test');
        await page.click('[data-toggle-password]');
        await changeLanguage('es');
        assert.equal(await page.$eval('#secret', (element) => element.value), 'home.title <b>secret conservé</b>');
        assert.equal(await page.$eval('#password', (element) => element.value), 'mot-de-passe-test');
        assert.equal(await page.$eval('[data-toggle-password]', (element) => element.title), 'Ocultar la contraseña');
        assert.match(await page.$eval('#secret', (element) => element.placeholder), /contraseña/i);
        const durationLabel = await page.$eval('#duration option[value="1d"]', (element) => element.textContent.trim());
        assert.equal(durationLabel, '1 día');
        assert.equal(new URL(page.url()).searchParams.get('lang'), 'es');
        for (const locale of ['ar', 'de', 'zh', 'hi', 'pt', 'ru', 'fr']) {
            await changeLanguage(locale);
        }
        assert.equal(await page.$eval('#secret', (element) => element.value), 'home.title <b>secret conservé</b>');
        assert.ok((await page.cookies()).some((cookie) => cookie.name === 'lang' && cookie.value === 'fr'));
        console.log('OK : neuf langues, RTL, saisies, erreurs, attributs et cookie sans navigation.');

        // Page d'ouverture : le jeton retiré de l'URL doit rester dans le formulaire.
        await page.goto(base + '/s?lang=fr#abc.def.ghi', {waitUntil: 'networkidle0'});
        await changeLanguage('es');
        assert.equal(await page.$eval('[data-fragment-token]', (element) => element.value), 'abc.def.ghi');

        await page.goto(base + '/?lang=fr', {waitUntil: 'networkidle0'});
        await page.type('#secret', 'revealed.title <svg onload="alert(1)"> secret de test');
        await page.select('#duration', '10m');
        await page.click('#short_url');
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('button[type="submit"]')]);
        const share = await page.$eval('#share-url', (element) => element.value);
        const revoke = await page.$eval('#revoke-url', (element) => element.value);
        const qr = await page.$eval('.qr img', (element) => element.src);
        try {
            await changeLanguage('en');
            assert.equal(await page.$eval('h1', (element) => element.textContent), 'Your link is ready');
            assert.equal(await page.$eval('#share-url', (element) => element.value), share);
            assert.equal(await page.$eval('#revoke-url', (element) => element.value), revoke);
            assert.equal(await page.$eval('.qr img', (element) => element.src), qr);
        } catch (error) {
            await page.goto(revoke, {waitUntil: 'networkidle0'});
            await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
            throw error;
        }
        await page.goto(share, {waitUntil: 'networkidle0'});
        await changeLanguage('fr');
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('button[type="submit"]')]);
        const secret = await page.$eval('#secret', (element) => element.value);
        await changeLanguage('ar');
        assert.equal(await page.$eval('#secret', (element) => element.value), secret);
        assert.equal(await page.$eval('#secret', (element) => element.readOnly), true);
        await changeLanguage('en');
        assert.equal(await page.$eval('#secret', (element) => element.value), secret);
        assert.equal(await page.$('#secret svg'), null);
        console.log('OK : jeton, liens créés, QR et secret révélé conservés ; aucun renvoi de formulaire.');

        await page.goto(revoke, {waitUntil: 'networkidle0'});
        await changeLanguage('fr');
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('button[type="submit"]')]);
        await changeLanguage('es');
        await page.goto(base + '/page-inexistante?lang=fr', {waitUntil: 'networkidle0'});
        await changeLanguage('ar');
        assert.deepEqual(errors, []);
        console.log('OK : sélection de langue sur les confirmations et erreurs, aucune erreur JavaScript/CSP.');
    } finally {
        await browser.close();
    }
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
