import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import fs from 'node:fs';
import path from 'node:path';

const outDir = path.resolve('docs/screenshots/landing');
if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
}

async function run() {
    console.log('Meluncurkan Chrome untuk verifikasi visual & aksesibilitas...');
    const browser = await chromium.launch({
        channel: 'chrome',
        headless: true
    });

    const viewports = [
        { width: 1920, height: 1080, name: '1920' },
        { width: 1440, height: 900, name: '1440' },
        { width: 1366, height: 768, name: '1366' },
        { width: 1024, height: 768, name: '1024' },
        { width: 768, height: 1024, name: '768' },
        { width: 390, height: 844, name: '390' }
    ];

    let axeResults = null;

    for (const vp of viewports) {
        console.log(`Menguji tampilan pada viewport ${vp.name} (${vp.width}x${vp.height})...`);
        const context = await browser.newContext({
            viewport: { width: vp.width, height: vp.height },
            deviceScaleFactor: 1
        });
        const page = await context.newPage();
        await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });

        // Cek overflow horizontal (kecuali kontainer scroll kartu)
        const hasHorizontalScroll = await page.evaluate(() => {
            return document.documentElement.scrollWidth > window.innerWidth;
        });

        if (hasHorizontalScroll) {
            console.warn(`[PERINGATAN] Terdeteksi scroll horizontal tidak diinginkan pada lebar ${vp.width}px!`);
        } else {
            console.log(`✓ Tidak ada scroll horizontal pada lebar ${vp.width}px`);
        }

        // Pastikan semua animasi reveal terpicu saat screenshot fullPage
        await page.evaluate(() => {
            document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
        });

        // Ambil screenshot full page untuk dokumentasi
        const screenshotPath = path.join(outDir, `landing-${vp.name}.png`);
        await page.screenshot({ path: screenshotPath, fullPage: true });
        console.log(`✓ Screenshot tersimpan: docs/screenshots/landing/landing-${vp.name}.png`);

        // Screenshot area hero
        const heroScreenshotPath = path.join(outDir, `landing-hero-${vp.name}.png`);
        await page.screenshot({ path: heroScreenshotPath, fullPage: false });

        // Jalankan evaluasi Axe pada desktop 1440 & mobile 390
        if (vp.name === '1440' || vp.name === '390') {
            console.log(`Menjalankan audit aksesibilitas Axe pada lebar ${vp.name}...`);
            const results = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa'])
                .analyze();

            const severeViolations = results.violations.filter(v =>
                v.impact === 'critical' || v.impact === 'serious'
            );

            console.log(`Hasil Axe (${vp.name}): ${results.violations.length} total temuan, ${severeViolations.length} critical/serious.`);

            if (severeViolations.length > 0) {
                console.error(`[PELANGGARAN AXE]`, JSON.stringify(severeViolations, null, 2));
            } else {
                console.log(`✓ Audit Axe (${vp.name}) LULUS: 0 pelanggaran critical / serious!`);
            }
        }

        // Uji menu hamburger pada mobile 390
        if (vp.name === '390') {
            console.log('Menguji interaksi menu hamburger pada mobile...');
            const hamburger = page.locator('#nav-toggle');
            const mobileMenu = page.locator('#mobile-menu');

            await hamburger.click();
            await page.waitForTimeout(300);

            const isMenuOpen = await mobileMenu.evaluate(el => el.classList.contains('is-open'));
            console.log(`Menu mobile terbuka: ${isMenuOpen}`);

            // Ambil screenshot menu terbuka
            await page.screenshot({ path: path.join(outDir, 'mobile-menu-open.png') });

            // Tekan Escape untuk menutup
            await page.keyboard.press('Escape');
            await page.waitForTimeout(300);
            const isMenuClosed = await mobileMenu.evaluate(el => !el.classList.contains('is-open'));
            console.log(`Menu mobile ditutup dengan Escape: ${isMenuClosed}`);
        }

        await context.close();
    }

    await browser.close();
    console.log('Semua pengujian Playwright & Axe selesai!');
}

run().catch(err => {
    console.error('Error saat menjalankan Playwright/Axe:', err);
    process.exit(1);
});
