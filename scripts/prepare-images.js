import sharp from 'sharp';
import fs from 'node:fs';
import path from 'node:path';

const srcDir = 'C:/Users/GF63/.gemini/antigravity/brain/3e2a8c40-5211-4745-bc17-1563c2bbc384/.user_uploaded';
const outDir = path.resolve('public/images/asli');

if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
}

async function prepare() {
    console.log('Menyiapkan gambar ke public/images/asli/ ...');

    const hallSrc = path.join(srcDir, 'media_1794910897434_dfd29ff1.jpg');
    const raketSrc = path.join(srcDir, 'media_1794910917415_38d8c812.jpg');
    const pemainSrc = path.join(srcDir, 'media_1794910981211_8d6c89ac.jpg');
    const courtSrc = path.join(srcDir, 'media_1794911126961_8230b2b8.jpg');

    // 1. Hero.jpg (Hall arena badminton dengan pencahayaan sinematik)
    await sharp(hallSrc)
        .resize(2400, 1400, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 90 })
        .toFile(path.join(outDir, 'hero.jpg'));
    console.log('✓ hero.jpg siap (2400x1400)');

    // 2. Pernyataan.jpg (Makro raket, senar limau & kok - seperti bola padel di referensi)
    await sharp(raketSrc)
        .resize(2400, 1200, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 90 })
        .toFile(path.join(outDir, 'pernyataan.jpg'));
    console.log('✓ pernyataan.jpg siap (2400x1200)');

    // 3. Lapangan-besar.jpg (Arena lapangan badminton luas)
    await sharp(courtSrc)
        .resize(2400, 1400, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 90 })
        .toFile(path.join(outDir, 'lapangan-besar.jpg'));
    console.log('✓ lapangan-besar.jpg siap (2400x1400)');

    // 4. Cta.jpg (Pemain aksi smash di penutup besar)
    await sharp(pemainSrc)
        .resize(2400, 1200, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 90 })
        .toFile(path.join(outDir, 'cta.jpg'));
    console.log('✓ cta.jpg siap (2400x1200)');

    // 5. Pratinjau lapangan kecil (lapangan-1.jpg s/d lapangan-3.jpg)
    await sharp(courtSrc)
        .resize(800, 600, { fit: 'cover', position: 'left top' })
        .jpeg({ quality: 85 })
        .toFile(path.join(outDir, 'lapangan-1.jpg'));

    await sharp(hallSrc)
        .resize(800, 600, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 85 })
        .toFile(path.join(outDir, 'lapangan-2.jpg'));

    await sharp(courtSrc)
        .resize(800, 600, { fit: 'cover', position: 'right bottom' })
        .jpeg({ quality: 85 })
        .toFile(path.join(outDir, 'lapangan-3.jpg'));
    console.log('✓ lapangan-1..3.jpg siap (800x600)');

    // 6. Kartu langkah horizontal (langkah-1.jpg s/d langkah-5.jpg)
    await sharp(courtSrc)
        .resize(900, 1200, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 85 })
        .toFile(path.join(outDir, 'langkah-1.jpg'));

    await sharp(raketSrc)
        .resize(900, 1200, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 85 })
        .toFile(path.join(outDir, 'langkah-2.jpg'));

    await sharp(hallSrc)
        .resize(900, 1200, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 85 })
        .toFile(path.join(outDir, 'langkah-3.jpg'));

    await sharp(pemainSrc)
        .resize(900, 1200, { fit: 'cover', position: 'center' })
        .jpeg({ quality: 85 })
        .toFile(path.join(outDir, 'langkah-4.jpg'));

    await sharp(raketSrc)
        .resize(900, 1200, { fit: 'cover', position: 'top' })
        .jpeg({ quality: 85 })
        .toFile(path.join(outDir, 'langkah-5.jpg'));
    console.log('✓ langkah-1..5.jpg siap (900x1200)');

    console.log('Semua aset gambar asli selesai disiapkan!');
}

prepare().catch(err => {
    console.error('Error saat menyiapkan gambar:', err);
    process.exit(1);
});
