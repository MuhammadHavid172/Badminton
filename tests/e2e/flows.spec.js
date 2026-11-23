import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

// Helper pemindaian aksesibilitas: memastikan tidak ada pelanggaran serious atau critical
async function expectNoSeriousOrCriticalAccessibilityViolations(page, contextName = '') {
  const scanResults = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa'])
    .analyze();

  const severeViolations = scanResults.violations.filter(
    (v) => v.impact === 'serious' || v.impact === 'critical'
  );

  expect(
    severeViolations,
    `Ditemukan pelanggaran aksesibilitas serious/critical pada [${contextName}]: ` +
      JSON.stringify(
        severeViolations.map((v) => ({
          id: v.id,
          impact: v.impact,
          description: v.description,
          nodes: v.nodes.map((n) => n.target),
        })),
        null,
        2
      )
  ).toEqual([]);
}

test.describe('Fase 8: Alur Pengguna & Audit Aksesibilitas (WCAG AA)', () => {
  // 1. Alur 1: Tamu melihat jadwal publik
  test('Alur 1: Tamu melihat jadwal per hari dan kalender tanpa pelanggaran aksesibilitas', async ({
    page,
  }) => {
    await page.goto('/jadwal');

    // Pastikan judul utama dan tepat 1 h1
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('h1')).toContainText('Jadwal Lapangan');

    // Verifikasi beralih ke tampilan Kalender
    const toggleKalender = page.locator('a, button').filter({ hasText: /Kalender/i }).first();
    if (await toggleKalender.isVisible()) {
      await toggleKalender.click();
      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/tampilan=kalender/);
    }

    // Pindai aksesibilitas
    await expectNoSeriousOrCriticalAccessibilityViolations(page, 'Halaman Jadwal');
  });

  // 2. Alur 2: Tamu mendaftar dan masuk
  test('Alur 2: Tamu mendaftar akun baru lalu masuk ke dashboard akun', async ({ page }) => {
    const randomSuffix = Date.now().toString().slice(-7);
    const newPhone = `0852${randomSuffix}`;

    // A. Registrasi
    await page.goto('/daftar');
    await expect(page.locator('h1')).toHaveCount(1);

    await page.fill('input[name="name"]', 'Pengguna Baru E2E');
    await page.fill('input[name="phone"]', newPhone);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    // Audit aksesibilitas halaman daftar
    await expectNoSeriousOrCriticalAccessibilityViolations(page, 'Halaman Daftar');

    await page.click('button[type="submit"]');

    // Pengalihan ke /akun setelah registrasi
    await page.waitForURL('**/akun');
    await expect(page.locator('h1')).toContainText('Akun Saya');

    // B. Logout
    await page.click('button:has-text("Keluar")');
    await page.waitForURL('**/');

    // C. Login ulang
    await page.goto('/masuk');
    await expect(page.locator('h1')).toHaveCount(1);
    await page.fill('input[name="identity"]', newPhone);
    await page.fill('input[name="password"]', 'password123');

    // Audit aksesibilitas halaman masuk
    await expectNoSeriousOrCriticalAccessibilityViolations(page, 'Halaman Masuk');

    await page.click('button[type="submit"]');
    await page.waitForURL('**/akun');
    await expect(page.locator('body')).toContainText('Pengguna Baru E2E');
  });

  // 3. Alur 3: Customer booking Harian lalu bayar simulasi
  test('Alur 3: Customer melakukan booking Harian dan menyelesaikan simulasi bayar', async ({
    page,
  }) => {
    // Login sebagai customer demo
    await page.goto('/masuk');
    await page.fill('input[name="identity"]', '081100000003');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/akun');

    // Buka jadwal dan cari slot kosong untuk booking
    await page.goto('/jadwal');
    const slotKosongLink = page.locator('a[href*="/booking/baru"]').first();
    await expect(slotKosongLink).toBeVisible();

    await slotKosongLink.click();
    await page.waitForURL('**/booking/baru**');
    await expect(page.locator('h1')).toHaveCount(1);

    // Pastikan kategori Harian terpilih (default atau radio)
    const radioHarian = page.locator('input[value="single"]');
    if (await radioHarian.isVisible()) {
      await radioHarian.check();
    }

    // Klik tombol submit booking
    const submitBtn = page.locator('button[type="submit"]:has-text("Booking Sekarang")');
    await submitBtn.click();

    // Diarahkan ke halaman pembayaran /booking/{code}/bayar
    await page.waitForURL('**/bayar');
    await expect(page.locator('h1')).toContainText('Pembayaran');

    // Audit aksesibilitas halaman bayar
    await expectNoSeriousOrCriticalAccessibilityViolations(page, 'Halaman Bayar');

    // Klik tombol "Simulasikan pembayaran berhasil"
    const bayarBtn = page.locator('button:has-text("Simulasikan pembayaran berhasil")');
    await bayarBtn.click();

    // Diarahkan ke detail booking lunas (tiket bukti bayar)
    await page.waitForURL(/\/booking\/[A-Z0-9]{8}$/);
    await expect(page.locator('body')).toContainText('LUNAS');

    // Audit aksesibilitas tiket bukti bayar
    await expectNoSeriousOrCriticalAccessibilityViolations(page, 'Halaman Tiket Bukti Bayar');
  });

  // 4. Alur 4: Admin check-in presensi
  test('Alur 4: Admin membuka landing Hari Ini dan mencatat presensi kehadiran pemain', async ({
    page,
  }) => {
    // Login sebagai admin
    await page.goto('/masuk');
    await page.fill('input[name="identity"]', '081100000001');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Admin otomatis diarahkan ke /admin
    await page.waitForURL('**/admin');
    await expect(page.locator('h1')).toContainText('Hari Ini');

    // Audit aksesibilitas halaman admin Hari Ini
    await expectNoSeriousOrCriticalAccessibilityViolations(page, 'Admin Hari Ini');

    // Cari tombol check-in jika ada slot terisi yang belum hadir
    const checkInBtn = page.locator('button:has-text("Sudah hadir")').first();
    if (await checkInBtn.isVisible()) {
      await checkInBtn.click();
      await page.waitForTimeout(500);
      // Tombol beralih menjadi "Batal hadir"
      await expect(page.locator('body')).toContainText('Batal hadir');
    }
  });

  // 5. Alur 5: Admin membatalkan booking berbayar dengan verifikasi password
  test('Alur 5: Admin melihat detail booking dan membatalkan booking dengan konfirmasi', async ({
    page,
  }) => {
    // Login admin
    await page.goto('/masuk');
    await page.fill('input[name="identity"]', '081100000001');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin');

    // Buka daftar booking
    await page.goto('/admin/booking');
    await expect(page.locator('h1')).toContainText('Daftar Booking');

    // Buka detail booking pertama
    const detailLink = page.locator('a:has-text("Detail")').first();
    if (await detailLink.isVisible()) {
      await detailLink.click();
      await page.waitForURL('**/admin/booking/**');
      await expect(page.locator('h1')).toContainText('Detail Booking');

      // Audit aksesibilitas halaman detail booking admin
      await expectNoSeriousOrCriticalAccessibilityViolations(page, 'Admin Detail Booking');

      // Jika tombol batalkan booking tersedia
      const cancelBtn = page.locator('button:has-text("Batalkan Booking")');
      if (await cancelBtn.isVisible()) {
        await page.fill('textarea[name="cancel_reason"]', 'Force majeure pemeliharaan teknis');
        const passField = page.locator('input[name="password"]');
        if (await passField.isVisible()) {
          await passField.fill('password');
        }
        await cancelBtn.click();
        await page.waitForTimeout(500);
        await expect(page.locator('body')).toContainText('DIBATALKAN');
      }
    }
  });
});
