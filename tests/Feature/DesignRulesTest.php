<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class DesignRulesTest extends TestCase
{
    /**
     * Daftar direktori target pemindaian desain.
     */
    protected array $scanDirs = [
        'resources/views',
        'resources/css',
    ];

    /**
     * Frasa terlarang (klaim kosong dan kata formal).
     */
    protected array $forbiddenPhrases = [
        'terpercaya',
        'terbaik',
        'solusi',
        'transparan tanpa',
        'instan',
        'standar turnamen',
    ];

    /**
     * Memindai seluruh file dan mengembalikan daftar file yang valid untuk diuji.
     */
    protected function getFilesToScan(): array
    {
        $files = [];
        foreach ($this->scanDirs as $dir) {
            $path = base_path($dir);
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['php', 'css', 'blade'])) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Aturan: Dilarang menggunakan kata formal "Anda" (harus memakai "kamu").
     */
    public function test_tidak_ada_kata_anda_di_views_dan_css(): void
    {
        $violations = [];

        foreach ($this->getFilesToScan() as $filePath) {
            $content = file_get_contents($filePath);
            if (preg_match('/\bAnda\b/u', $content)) {
                $violations[] = $filePath;
            }
        }

        $this->assertEmpty(
            $violations,
            'Ditemukan kata "Anda" yang dilarang pada file: ' . implode(', ', $violations) . '. Gunakan "kamu".'
        );
    }

    /**
     * Aturan: Dilarang menggunakan frasa klaim kosong / AI slop.
     */
    public function test_tidak_ada_klaim_kosong_di_views_dan_css(): void
    {
        $violations = [];

        foreach ($this->getFilesToScan() as $filePath) {
            $content = file_get_contents($filePath);
            foreach ($this->forbiddenPhrases as $phrase) {
                if (stripos($content, $phrase) !== false) {
                    $violations[] = basename($filePath) . " (mengandung '{$phrase}')";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Ditemukan klaim kosong terlarang pada: ' . implode(', ', $violations)
        );
    }

    /**
     * Aturan: Dilarang menggunakan emoji apapun di antarmuka.
     */
    public function test_tidak_ada_emoji_di_views_dan_css(): void
    {
        // Regex rentang Unicode emoji umum
        $emojiPattern = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}]/u';
        $violations = [];

        foreach ($this->getFilesToScan() as $filePath) {
            $content = file_get_contents($filePath);
            if (preg_match($emojiPattern, $content, $matches)) {
                $violations[] = basename($filePath) . " (menemukan emoji '{$matches[0]}')";
            }
        }

        $this->assertEmpty(
            $violations,
            'Ditemukan emoji terlarang pada antarmuka: ' . implode(', ', $violations)
        );
    }

    /**
     * Aturan: Dilarang menggunakan efek teks gradient (background-clip: text).
     */
    public function test_tidak_ada_background_clip_text_di_views_dan_css(): void
    {
        $violations = [];

        foreach ($this->getFilesToScan() as $filePath) {
            $content = file_get_contents($filePath);
            if (preg_match('/-?(?:webkit-)?background-clip\s*:\s*text/i', $content)) {
                $violations[] = basename($filePath);
            }
        }

        $this->assertEmpty(
            $violations,
            'Ditemukan background-clip: text (gradient text) yang dilarang pada: ' . implode(', ', $violations)
        );
    }

    /**
     * Menghapus komentar dari kode sebelum menganalisis aturan CSS/properti.
     */
    protected function stripComments(string $content): string
    {
        $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content);
        $content = preg_replace('/<!--.*?-->/s', '', $content);
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        $content = preg_replace('/\/\/.*$/m', '', $content);

        return $content;
    }

    /**
     * Aturan: Dilarang menggunakan bayangan berwarna atau glow (box-shadow colored / glow).
     */
    public function test_tidak_ada_box_shadow_berwarna_atau_glow(): void
    {
        $violations = [];

        foreach ($this->getFilesToScan() as $filePath) {
            $rawContent = file_get_contents($filePath);
            $cleanCode = $this->stripComments($rawContent);

            // Periksa penggunaan glow dalam kode aktual
            if (preg_match('/\bglow\b/i', $cleanCode)) {
                $violations[] = basename($filePath) . ' (mengandung properti/kelas glow)';
                continue;
            }

            // Periksa box-shadow berwarna dalam kode aktual
            if (preg_match('/box-shadow\s*:\s*(?!none\b)[^;]*(?:#[0-9a-fA-F]{3,8}|rgba?\(|hsla?\(|var\(--lime|var\(--biru)/i', $cleanCode)) {
                $violations[] = basename($filePath) . ' (box-shadow berwarna)';
            }
        }

        $this->assertEmpty(
            $violations,
            'Ditemukan efek glow atau bayangan berwarna yang dilarang pada: ' . implode(', ', $violations)
        );
    }

    /**
     * Aturan: backdrop-filter HANYA diizinkan untuk navbar.
     */
    public function test_backdrop_filter_hanya_pada_navbar(): void
    {
        $violations = [];

        foreach ($this->getFilesToScan() as $filePath) {
            $content = file_get_contents($filePath);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNum => $line) {
                if (preg_match('/-?(?:webkit-)?backdrop-filter/i', $line)) {
                    // Hanya izinkan jika berada di file css dan dalam konteks navbar
                    $fileName = basename($filePath);
                    if ($fileName !== 'components.css') {
                        $violations[] = "{$fileName}:L" . ($lineNum + 1);
                    } else {
                        // Pastikan aturan sekitarnya adalah navbar
                        // Kita periksa 10 baris ke atas
                        $start = max(0, $lineNum - 10);
                        $context = implode("\n", array_slice($lines, $start, 11));
                        if (!str_contains($context, '.navbar')) {
                            $violations[] = "components.css:L" . ($lineNum + 1) . " (di luar konteks .navbar)";
                        }
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Ditemukan penggunaan backdrop-filter di luar navbar pada: ' . implode(', ', $violations)
        );
    }
}
