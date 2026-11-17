import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const inputDir = path.resolve('public/images/asli');
const outputDir = path.resolve('public/images');
const widths = [800, 1600, 2400];

if (!fs.existsSync(inputDir)) {
    fs.mkdirSync(inputDir, { recursive: true });
}

if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
}

const files = fs.readdirSync(inputDir).filter(file => /\.(jpe?g|png|webp|avif)$/i.test(file));

if (files.length === 0) {
    console.log('ℹ Tidak ada gambar di public/images/asli/. Taruh foto mentah di folder tersebut lalu jalankan "npm run foto".');
    process.exit(0);
}

console.log(`Menemukan ${files.length} gambar untuk diproses...`);

for (const file of files) {
    const ext = path.extname(file);
    const basename = path.basename(file, ext);
    const inputPath = path.join(inputDir, file);

    console.log(`Memproses: ${file}`);

    // Salin file asli sebagai basis jika belum ada
    const basePath = path.join(outputDir, file);
    fs.copyFileSync(inputPath, basePath);

    for (const width of widths) {
        // WebP
        const webpOutput = path.join(outputDir, `${basename}-${width}.webp`);
        await sharp(inputPath)
            .resize({ width, withoutEnlargement: true })
            .webp({ quality: 82 })
            .toFile(webpOutput);

        // AVIF
        const avifOutput = path.join(outputDir, `${basename}-${width}.avif`);
        await sharp(inputPath)
            .resize({ width, withoutEnlargement: true })
            .avif({ quality: 75 })
            .toFile(avifOutput);
    }

    // Default WebP utama
    const defaultWebp = path.join(outputDir, `${basename}.webp`);
    await sharp(inputPath)
        .webp({ quality: 85 })
        .toFile(defaultWebp);

    console.log(`✓ Selesai: ${basename} (WebP & AVIF 800w, 1600w, 2400w)`);
}

console.log('Semua gambar berhasil diproses ke public/images/!');
