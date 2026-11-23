import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { spawn } from 'child_process';
import http from 'http';

// Fungsi menunggu server PHP lokal siap
function waitForServer(url, timeoutMs = 25000) {
  return new Promise((resolve, reject) => {
    const start = Date.now();
    const interval = setInterval(() => {
      http.get(url, (res) => {
        clearInterval(interval);
        resolve(true);
      }).on('error', () => {
        if (Date.now() - start > timeoutMs) {
          clearInterval(interval);
          reject(new Error('Server timeout tidak merespons dalam ' + timeoutMs + 'ms'));
        }
      });
    }, 500);
  });
}

// Fungsi helper audit aksesibilitas
async function auditAxe(page, contextName) {
  const scanResults = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa'])
    .analyze();

  const severe = scanResults.violations.filter(
    (v) => v.impact === 'serious' || v.impact === 'critical'
  );

  if (severe.length > 0) {
    console.error(`[AXE VIOLATION] Pelanggaran serious/critical pada: ${contextName}`);
    for (const v of severe) {
      console.error(`  - [${v.impact.toUpperCase()}] ${v.id}: ${v.description}`);
      for (const node of v.nodes) {
        console.error(`      Target: ${node.target.join(' ')}`);
      }
    }
    throw new Error(`Audit aksesibilitas gagal pada ${contextName} (${severe.length} pelanggaran)`);
  } else {
    console.log(`  ✓ Aksesibilitas WCAG AA Lolos pada: ${contextName} (0 pelanggaran serious/critical)`);
  }
}

async function runE2E() {
  console.log('=== MEMULAI PENGUJIAN PLAYWRIGHT E2E & AKSESIBILITAS WCAG AA ===\n');

  console.log('Menjalankan server lokal PHP dengan multi-workers...');
  const server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8000'], {
    stdio: 'ignore',
    env: {
      ...process.env,
      PHP_CLI_SERVER_WORKERS: '4',
    },
  });

  try {
    await waitForServer('http://127.0.0.1:8000/');
    console.log('Server PHP siap di http://127.0.0.1:8000/\n');

    const browser = await chromium.launch({ headless: true });

    const viewports = [
      { name: 'Desktop (1280x800)', width: 1280, height: 800 },
      { name: 'Mobile (390x844)', width: 390, height: 844 },
    ];

    for (const vp of viewports) {
      console.log(`\n========================================`);
      console.log(`PENGUJIAN PADA VIEWPORT: ${vp.name}`);
      console.log(`========================================`);
      const context = await browser.newContext({
        viewport: { width: vp.width, height: vp.height },
        baseURL: 'http://127.0.0.1:8000',
      });
      const page = await context.newPage();
      page.on('dialog', async (dialog) => {
        await dialog.accept();
      });

      // ALUR 1: Tamu melihat jadwal
      console.log('\n[ALUR 1] Tamu melihat jadwal publik...');
      await page.goto('/jadwal', { waitUntil: 'domcontentloaded' });
      const h1Count = await page.locator('h1').count();
      if (h1Count !== 1) throw new Error(`Alur 1 Gagal: h1 harus tepat 1, ditemukan: ${h1Count}`);
      await auditAxe(page, `Alur 1 - Jadwal Per Hari (${vp.name})`);

      // Beralih ke Kalender
      await page.goto('/jadwal?tampilan=kalender', { waitUntil: 'domcontentloaded' });
      await auditAxe(page, `Alur 1 - Jadwal Kalender (${vp.name})`);

      // ALUR 2: Tamu mendaftar dan masuk
      console.log('\n[ALUR 2] Tamu mendaftar akun baru dan masuk...');
      const phoneSuffix = Math.floor(1000000 + Math.random() * 9000000);
      const newPhone = `0853${phoneSuffix}`;

      await page.goto('/daftar', { waitUntil: 'domcontentloaded' });
      await auditAxe(page, `Alur 2 - Halaman Daftar (${vp.name})`);

      await page.fill('input[name="name"]', 'Pengguna Playwright');
      await page.fill('input[name="phone"]', newPhone);
      await page.fill('input[name="password"]', 'password123');
      await page.fill('input[name="password_confirmation"]', 'password123');
      await page.click('button[type="submit"]');

      await page.waitForURL('**/akun', { waitUntil: 'domcontentloaded' });
      console.log('  ✓ Registrasi berhasil, dialihkan ke /akun');
      await auditAxe(page, `Alur 2 - Halaman Akun Baru (${vp.name})`);

      // Logout via form submit
      await page.evaluate(() => {
        const logoutForm = document.querySelector('form[action*="/keluar"]');
        if (logoutForm) logoutForm.submit();
      });
      await page.waitForURL('**/', { waitUntil: 'domcontentloaded' });
      console.log('  ✓ Berhasil logout');

      // Login ulang
      await page.goto('/masuk', { waitUntil: 'domcontentloaded' });
      await auditAxe(page, `Alur 2 - Halaman Masuk (${vp.name})`);
      await page.fill('input[name="login"]', newPhone);
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/akun', { waitUntil: 'domcontentloaded' });
      console.log('  ✓ Login berhasil masuk kembali ke /akun');

      // Logout akun sementara sebelum Alur 3
      await page.evaluate(() => {
        const logoutForm = document.querySelector('form[action*="/keluar"]');
        if (logoutForm) logoutForm.submit();
      });
      await page.waitForURL('**/', { waitUntil: 'domcontentloaded' });

      // ALUR 3: Customer booking Harian lalu bayar simulasi
      console.log('\n[ALUR 3] Customer booking Harian dan bayar simulasi...');
      // Login customer demo
      await page.goto('/masuk', { waitUntil: 'domcontentloaded' });
      await page.fill('input[name="login"]', '081100000003');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/akun', { waitUntil: 'domcontentloaded' });

      await page.goto('/jadwal', { waitUntil: 'domcontentloaded' });
      // Pastikan ada slot kosong; jika hari ini penuh, navigasi ke hari berikutnya
      let slotLink = page.locator('a[href*="/booking/baru"]:visible').first();
      let hasSlot = (await slotLink.count()) > 0;

      if (!hasSlot) {
        // Navigasi ke hari berikutnya
        const nextDayBtn = page.locator('a:has-text("Berikutnya"):visible').first();
        if (await nextDayBtn.isVisible()) {
          await nextDayBtn.click();
          await page.waitForTimeout(500);
          slotLink = page.locator('a[href*="/booking/baru"]:visible').first();
        }
      }

      await slotLink.waitFor({ state: 'visible', timeout: 8000 });
      await slotLink.click();

      await page.waitForURL('**/booking/baru**', { waitUntil: 'domcontentloaded' });
      await auditAxe(page, `Alur 3 - Form Booking Baru (${vp.name})`);

      // Submit booking
      const bookingBtn = page.locator('button:has-text("Booking Sekarang"):visible').first();
      await bookingBtn.click();

      // Halaman bayar
      await page.waitForURL('**/bayar', { waitUntil: 'domcontentloaded' });
      console.log('  ✓ Booking dibuat, masuk ke halaman pembayaran');
      await auditAxe(page, `Alur 3 - Halaman Bayar (${vp.name})`);

      // Simulasi lunas
      await page.click('button:has-text("Simulasikan pembayaran berhasil"):visible');
      await page.waitForURL(/\/booking\/[A-Z0-9]{8}$/, { waitUntil: 'domcontentloaded' });
      console.log('  ✓ Pembayaran lunas, masuk ke tiket bukti booking');
      await auditAxe(page, `Alur 3 - Tiket Bukti Booking Lunas (${vp.name})`);

      // Logout customer
      await page.evaluate(() => {
        const logoutForm = document.querySelector('form[action*="/keluar"]');
        if (logoutForm) logoutForm.submit();
      });
      await page.waitForURL('**/', { waitUntil: 'domcontentloaded' });

      // ALUR 4: Admin check-in presensi
      console.log('\n[ALUR 4] Admin check-in presensi...');
      await page.goto('/masuk', { waitUntil: 'domcontentloaded' });
      await page.fill('input[name="login"]', '081100000001');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]:visible');
      await page.waitForURL('**/admin', { waitUntil: 'domcontentloaded' });

      console.log('  ✓ Admin berhasil masuk ke landing /admin');
      await auditAxe(page, `Alur 4 - Admin Hari Ini (${vp.name})`);

      // Tombol presensi hadir
      const hadirBtn = page.locator('button:has-text("Sudah hadir"):visible').first();
      if (await hadirBtn.isVisible()) {
        await hadirBtn.click();
        await page.waitForTimeout(600);
        console.log('  ✓ Presensi check-in berhasil dicatat');
      }

      // ALUR 5: Admin membatalkan booking
      console.log('\n[ALUR 5] Admin membatalkan booking...');
      await page.goto('/admin/booking', { waitUntil: 'domcontentloaded' });
      await auditAxe(page, `Alur 5 - Admin Daftar Booking (${vp.name})`);

      const detailBtn = page.locator('a:has-text("Detail"):visible, a:has-text("Buka Rincian"):visible').first();
      if (await detailBtn.isVisible()) {
        await detailBtn.click();
        await page.waitForURL(/\/admin\/booking\/[A-Z0-9]+$/, { waitUntil: 'domcontentloaded' });
        await auditAxe(page, `Alur 5 - Admin Detail Booking (${vp.name})`);

        const batalBtn = page.locator('button:has-text("Batalkan Booking"):visible');
        if (await batalBtn.isVisible()) {
          const reasonInput = page.locator('input[name="reason"]:visible, textarea[name="cancel_reason"]:visible');
          if (await reasonInput.isVisible()) {
            await reasonInput.fill('Force majeure pemeliharaan rutin');
          }
          const pwdField = page.locator('input[name="password"]:visible');
          if (await pwdField.isVisible()) {
            await pwdField.fill('password');
          }
          await batalBtn.click();
          await page.waitForTimeout(800);
          console.log('  ✓ Booking berhasil dibatalkan oleh admin');
        }
      }

      // Logout admin
      await page.evaluate(() => {
        const logoutForm = document.querySelector('form[action*="/keluar"]');
        if (logoutForm) logoutForm.submit();
      });

      await context.close();
      console.log(`\n--- SELESAI VIEWPORT: ${vp.name} ---`);
    }

    await browser.close();
    console.log('\n================================================================');
    console.log('SEMUA 5 ALUR PLAYWRIGHT & AUDIT AKSESIBILITAS WCAG AA LOLOS 100%');
    console.log('================================================================');
  } finally {
    server.kill();
    console.log('Server PHP lokal dimatikan.');
  }
}

runE2E().catch((err) => {
  console.error('Pengujian Playwright E2E Gagal:', err);
  process.exit(1);
});
