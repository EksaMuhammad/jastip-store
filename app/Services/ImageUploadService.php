<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Simpan file gambar yang diunggah pengguna.
     * Jika file berformat HEIC/HEIF dari iPhone, otomatis dikonversi menjadi JPEG
     * agar dapat ditampilkan di semua peramban (Chrome, Firefox, Edge, Safari, Android).
     *
     * @param UploadedFile $file
     * @param string $directory Direktori tujuan di storage (misal: 'reports' atau 'support/attachments')
     * @param string $disk Driver storage (default: 'public')
     * @return string Path relatif file yang tersimpan
     */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $isHeic = in_array($extension, ['heic', 'heif']);

        // Simpan file asli terlebih dahulu
        $storedPath = $file->store($directory, $disk);
        $fullPath = Storage::disk($disk)->path($storedPath);

        if (!$isHeic) {
            return $storedPath;
        }

        // Target path file hasil konversi ke JPEG
        $jpgFilename = pathinfo($storedPath, PATHINFO_FILENAME) . '.jpg';
        $jpgRelativePath = $directory . '/' . $jpgFilename;
        $jpgFullPath = Storage::disk($disk)->path($jpgRelativePath);

        $converted = false;

        // 1. macOS native sips (Scriptable Image Processing System - bawaan Apple macOS)
        if (file_exists('/usr/bin/sips') && is_executable('/usr/bin/sips')) {
            $cmd = sprintf('/usr/bin/sips -s format jpeg %s --out %s 2>&1', escapeshellarg($fullPath), escapeshellarg($jpgFullPath));
            exec($cmd, $output, $code);
            if ($code === 0 && file_exists($jpgFullPath) && filesize($jpgFullPath) > 0) {
                $converted = true;
            }
        }

        // 2. ImageMagick (magick / convert)
        if (!$converted) {
            $magickBins = ['/usr/local/bin/magick', '/opt/homebrew/bin/magick', '/usr/bin/magick', '/usr/local/bin/convert', '/usr/bin/convert'];
            foreach ($magickBins as $bin) {
                if (file_exists($bin) && is_executable($bin)) {
                    $cmd = sprintf('%s %s %s 2>&1', escapeshellcmd($bin), escapeshellarg($fullPath), escapeshellarg($jpgFullPath));
                    exec($cmd, $output, $code);
                    if ($code === 0 && file_exists($jpgFullPath) && filesize($jpgFullPath) > 0) {
                        $converted = true;
                        break;
                    }
                }
            }
        }

        // 3. libheif (heif-convert)
        if (!$converted) {
            $heifBins = ['/opt/homebrew/bin/heif-convert', '/usr/local/bin/heif-convert', '/usr/bin/heif-convert'];
            foreach ($heifBins as $bin) {
                if (file_exists($bin) && is_executable($bin)) {
                    $cmd = sprintf('%s %s %s 2>&1', escapeshellcmd($bin), escapeshellarg($fullPath), escapeshellarg($jpgFullPath));
                    exec($cmd, $output, $code);
                    if ($code === 0 && file_exists($jpgFullPath) && filesize($jpgFullPath) > 0) {
                        $converted = true;
                        break;
                    }
                }
            }
        }

        if ($converted) {
            // Hapus file heic mentah agar menghemat ruang penyimpanan
            @unlink($fullPath);
            return $jpgRelativePath;
        }

        Log::warning("HEIC conversion skipped or failed for file {$storedPath}, keeping original format.");
        return $storedPath;
    }
}
