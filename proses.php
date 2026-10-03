<?php
// ============================================================
// FILE COMPRESSOR
// PROSES.PHP
// ============================================================

session_start();


// ============================================================
// KONFIGURASI
// ============================================================

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
$compressedDir = __DIR__ . DIRECTORY_SEPARATOR . 'compressed' . DIRECTORY_SEPARATOR;

$maxFileSize = 100 * 1024 * 1024; // 100 MB

$allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];


// ============================================================
// BUAT FOLDER JIKA BELUM ADA
// ============================================================

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!is_dir($compressedDir)) {
    mkdir($compressedDir, 0755, true);
}


// ============================================================
// FUNGSI FORMAT UKURAN
// ============================================================

function formatFileSize($bytes)
{
    if ($bytes <= 0) {
        return '0 Bytes';
    }

    $units = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));

    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}


// ============================================================
// ESCAPE HTML
// ============================================================

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ============================================================
// FUNGSI HAPUS FOLDER REKURSIF
// ============================================================

function deleteFolderRecursive($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);

    foreach ($items as $item) {

        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            deleteFolderRecursive($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}


// ============================================================
// FUNGSI: SUSUN PDF DARI KUMPULAN JPEG (TANPA LIBRARY TAMBAHAN)
// ============================================================
//
// Teknik: setiap JPEG disisipkan mentah ke dalam struktur PDF
// lewat filter DCTDecode (JPEG decoder bawaan standar PDF),
// jadi tidak perlu re-encode gambar ke format lain.
//
// $jpegPaths : array path file .jpg, satu per halaman, berurutan
// $outputPath: path PDF hasil yang akan dibuat
// $dpi       : DPI yang dipakai saat JPEG itu di-render (untuk
//              menghitung ukuran halaman dalam point PDF)
//
function buildPdfFromJpegs($jpegPaths, $outputPath, $dpi)
{
    if (empty($jpegPaths)) {
        return false;
    }

    $pageEntries = [];
    $nextId = 3; // 1 = Catalog, 2 = Pages

    foreach ($jpegPaths as $jpegPath) {

        $data = @file_get_contents($jpegPath);
        $info = @getimagesize($jpegPath);

        if ($data === false || $info === false) {
            continue;
        }

        $pxW = $info[0];
        $pxH = $info[1];

        // px -> point PDF (1 inch = 72 point)
        $ptW = $pxW / $dpi * 72;
        $ptH = $pxH / $dpi * 72;

        $pageEntries[] = [
            'pageId'    => $nextId++,
            'contentId' => $nextId++,
            'imageId'   => $nextId++,
            'data'      => $data,
            'w'         => $pxW,
            'h'         => $pxH,
            'ptW'       => $ptW,
            'ptH'       => $ptH,
        ];
    }

    if (empty($pageEntries)) {
        return false;
    }

    $catalogId = 1;
    $pagesId = 2;

    $pdf = "%PDF-1.4\n";
    $body = '';
    $offsets = [];
    $pos = strlen($pdf);

    $writeObj = function ($id, $content) use (&$body, &$offsets, &$pos) {
        $offsets[$id] = $pos;
        $s = $id . " 0 obj\n" . $content . "\nendobj\n";
        $body .= $s;
        $pos += strlen($s);
    };

    $kidsList = [];

    foreach ($pageEntries as $e) {
        $kidsList[] = $e['pageId'] . ' 0 R';
    }

    $kids = implode(' ', $kidsList);

    foreach ($pageEntries as $e) {

        $w = $e['ptW'];
        $h = $e['ptH'];

        $contentStream = 'q ' . $w . ' 0 0 ' . $h . ' 0 0 cm /Im' . $e['imageId'] . ' Do Q';

        $writeObj(
            $e['contentId'],
            '<< /Length ' . strlen($contentStream) . ' >>' . "\nstream\n" . $contentStream . "\nendstream"
        );

        $imgHeader = '<< /Type /XObject /Subtype /Image /Width ' . $e['w']
            . ' /Height ' . $e['h']
            . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '
            . strlen($e['data']) . ' >>';

        $writeObj(
            $e['imageId'],
            $imgHeader . "\nstream\n" . $e['data'] . "\nendstream"
        );

        $pageContent = '<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 ' . $w . ' ' . $h
            . '] /Resources << /XObject << /Im' . $e['imageId'] . ' ' . $e['imageId'] . ' 0 R >> >> /Contents '
            . $e['contentId'] . ' 0 R >>';

        $writeObj($e['pageId'], $pageContent);
    }

    $pagesContent = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageEntries) . ' >>';
    $writeObj($pagesId, $pagesContent);

    $catalogContent = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';
    $writeObj($catalogId, $catalogContent);

    $pdf .= $body;

    $xrefPos = $pos;
    $totalObjs = $nextId - 1;

    $xref = "xref\n0 " . ($totalObjs + 1) . "\n0000000000 65535 f \n";

    for ($i = 1; $i <= $totalObjs; $i++) {
        $off = isset($offsets[$i]) ? $offsets[$i] : 0;
        $xref .= sprintf("%010d 00000 n \n", $off);
    }

    $pdf .= $xref;
    $pdf .= "trailer\n<< /Size " . ($totalObjs + 1) . ' /Root ' . $catalogId . " 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

    return (bool) file_put_contents($outputPath, $pdf);
}


// ============================================================
// HALAMAN ERROR
// ============================================================

function showError($message)
{
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error | File Compressor</title>
        <style>
            * { box-sizing: border-box; }
            body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f8fafc; color: #1e293b; }
            .container { width: 92%; max-width: 650px; margin: 100px auto; }
            .card { background: white; border-radius: 20px; padding: 40px; text-align: center; box-shadow: 0 20px 50px rgba(15, 23, 42, .08); }
            .icon { width: 70px; height: 70px; margin: auto auto 20px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; font-size: 32px; }
            h1 { margin-bottom: 12px; }
            p { color: #64748b; line-height: 1.6; }
            a { display: inline-block; margin-top: 20px; padding: 13px 25px; border-radius: 10px; background: #2563eb; color: white; text-decoration: none; font-weight: 700; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <div class="icon">⚠️</div>
                <h1>Terjadi Kesalahan</h1>
                <p><?php echo e($message); ?></p>
                <a href="index.php">← Kembali</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}


// ============================================================
// CEK REQUEST & FILE
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showError('Metode akses tidak valid.');
}

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    showError('Tidak ada file yang dikirim.');
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {

    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $message = 'Ukuran file melebihi batas yang diperbolehkan.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $message = 'File hanya terupload sebagian.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $message = 'Tidak ada file yang dipilih.';
            break;
        default:
            $message = 'Terjadi kesalahan ketika mengupload file.';
            break;
    }

    showError($message);
}

$originalName = basename($file['name']);
$fileSize = (int) $file['size'];
$tmpName = $file['tmp_name'];

if ($fileSize <= 0) {
    showError('File kosong atau tidak valid.');
}

if ($fileSize > $maxFileSize) {
    showError('Ukuran file terlalu besar. Maksimal 100 MB.');
}

$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions, true)) {
    showError('Format file tidak didukung.');
}

if (!is_uploaded_file($tmpName)) {
    showError('File upload tidak valid.');
}

$safeBaseName = pathinfo($originalName, PATHINFO_FILENAME);
$safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeBaseName);

if (!$safeBaseName) {
    $safeBaseName = 'file';
}

$uniqueId = date('Ymd_His') . '_' . bin2hex(random_bytes(5));
$uploadedFileName = $safeBaseName . '_' . $uniqueId . '.' . $extension;
$uploadedPath = $uploadDir . $uploadedFileName;

if (!move_uploaded_file($tmpName, $uploadedPath)) {
    showError('Gagal menyimpan file upload.');
}


// ============================================================
// VALIDASI TINGKAT KOMPRESI (PERSENTASE 10-90)
// ============================================================

$compression = intval($_POST['compression'] ?? 50);

if ($compression < 10) { $compression = 10; }
if ($compression > 90) { $compression = 90; }


// ============================================================
// SIAPKAN NAMA FILE HASIL
// ============================================================

$compressedFileName = $safeBaseName . '_' . $uniqueId . '_compressed.' . $extension;
$compressedPath = $compressedDir . $compressedFileName;

$originalSize = $fileSize;
$compressedSize = $originalSize;
$method = 'copy';


// ============================================================
// LOKASI GHOSTSCRIPT
// ============================================================

if (stripos(PHP_OS, 'WIN') === 0) {
    $gsExecutable = 'C:\\Program Files\\gs\\gs10.08.0\\bin\\gswin64c.exe';
} else {
    $gsExecutable = 'gs';
}


// ============================================================
// KOMPRESI PDF
// ============================================================

if ($extension === 'pdf') {

    if ($compression <= 35) {
        $gsSetting = '/prepress';
    } elseif ($compression <= 65) {
        $gsSetting = '/ebook';
    } else {
        $gsSetting = '/screen';
    }

    $dpi = round(300 - (($compression - 10) / 80) * (300 - 50));
    $jpegQuality = round(90 - (($compression - 10) / 80) * (90 - 25));

    $cmd = escapeshellarg($gsExecutable)
        . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4'
        . ' -dPDFSETTINGS=' . $gsSetting
        . ' -dNOPAUSE -dQUIET -dBATCH'
        . ' -dDownsampleColorImages=true -dColorImageResolution=' . $dpi
        . ' -dDownsampleGrayImages=true -dGrayImageResolution=' . $dpi
        . ' -dDownsampleMonoImages=true -dMonoImageResolution=' . $dpi
        . ' -dAutoFilterColorImages=false -dColorImageFilter=/DCTEncode'
        . ' -dAutoFilterGrayImages=false -dGrayImageFilter=/DCTEncode'
        . ' -dJPEGQ=' . $jpegQuality
        . ' -dDetectDuplicateImages=true'
        . ' -dCompressFonts=true'
        . ' -sOutputFile=' . escapeshellarg($compressedPath)
        . ' ' . escapeshellarg($uploadedPath);

    $output = [];
    $returnCode = 1;

    if (function_exists('exec')) {
        @exec($cmd . ' 2>&1', $output, $returnCode);
    }

    clearstatcache(true, $compressedPath);

    if ($returnCode === 0 && file_exists($compressedPath) && filesize($compressedPath) > 0 && filesize($compressedPath) < $fileSize) {
        $method = 'Ghostscript (kualitas ' . $compression . '%)';
    } else {
        copy($uploadedPath, $compressedPath);
        clearstatcache(true, $compressedPath);
        $method = 'File asli dipertahankan sementara';
    }


    // ============================================================
    // MODE EKSTREM: kalau masih >= 1 MB, render tiap halaman jadi
    // gambar JPEG lalu susun ulang jadi PDF baru (pakai
    // buildPdfFromJpegs, bukan Ghostscript lagi)
    // ============================================================

    $targetMaxBytes = 1000000; // ~1 MB

    clearstatcache(true, $compressedPath);

    if (filesize($compressedPath) >= $targetMaxBytes) {

        $rasterDir = $uploadDir . 'raster_' . $uniqueId . DIRECTORY_SEPARATOR;
        mkdir($rasterDir, 0755, true);

        $dpiTries = [150, 120, 100, 80, 65, 50, 40, 30];
        $lastGoodPdf = null;
        $lastGoodDpi = null;

        foreach ($dpiTries as $tryDpi) {

            foreach (glob($rasterDir . 'page_*.jpg') as $oldImg) {
                unlink($oldImg);
            }

            $pages = [];
            $pageNum = 1;
            $maxPages = 300;

            while ($pageNum <= $maxPages) {

                $outFile = $rasterDir . 'page_' . str_pad((string) $pageNum, 4, '0', STR_PAD_LEFT) . '.jpg';

                $cmdPage = escapeshellarg($gsExecutable)
                    . ' -sDEVICE=jpeg -dJPEGQ=55 -r' . $tryDpi
                    . ' -dFirstPage=' . $pageNum
                    . ' -dLastPage=' . $pageNum
                    . ' -dNOPAUSE -dQUIET -dBATCH'
                    . ' -o ' . escapeshellarg($outFile)
                    . ' ' . escapeshellarg($uploadedPath);

                $pageOutput = [];
                $pageCode = 1;
                @exec($cmdPage . ' 2>&1', $pageOutput, $pageCode);

                clearstatcache(true, $outFile);

                if ($pageCode === 0 && file_exists($outFile) && filesize($outFile) > 0) {
                    $pages[] = $outFile;
                    $pageNum++;
                } else {
                    break;
                }
            }

            if (count($pages) > 0) {

                $tempPdfPath = $rasterDir . 'rebuilt_' . $tryDpi . '.pdf';

                $built = buildPdfFromJpegs($pages, $tempPdfPath, $tryDpi);

                clearstatcache(true, $tempPdfPath);

                if ($built && file_exists($tempPdfPath) && filesize($tempPdfPath) > 0) {

                    $lastGoodPdf = $tempPdfPath;
                    $lastGoodDpi = $tryDpi;

                    if (filesize($tempPdfPath) < $targetMaxBytes) {
                        copy($tempPdfPath, $compressedPath);
                        clearstatcache(true, $compressedPath);
                        $method = 'Kompresi ekstrem (halaman jadi gambar, ' . $tryDpi . ' DPI) — teks tidak bisa di-select/search';
                        break;
                    }
                }
            }
        }

        clearstatcache(true, $compressedPath);

        if (filesize($compressedPath) >= $targetMaxBytes && $lastGoodPdf !== null) {
            copy($lastGoodPdf, $compressedPath);
            clearstatcache(true, $compressedPath);
            $method = 'Kompresi ekstrem maksimal (' . $lastGoodDpi . ' DPI) — teks tidak bisa di-select/search';
        }

        deleteFolderRecursive($rasterDir);
    }
}


// ============================================================
// KOMPRESI DOCX / XLSX / PPTX
// ============================================================

elseif (in_array($extension, ['docx', 'xlsx', 'pptx'], true)) {

    $zipLevel = (int) round(1 + (($compression - 10) / 80) * 8);

    if (class_exists('ZipArchive')) {

        $tempExtractDir = $uploadDir . 'tmp_' . $uniqueId . DIRECTORY_SEPARATOR;
        mkdir($tempExtractDir, 0755, true);

        $zip = new ZipArchive();

        if ($zip->open($uploadedPath) === true) {

            $zip->extractTo($tempExtractDir);
            $zip->close();

            $newZip = new ZipArchive();

            if ($newZip->open($compressedPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {

                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tempExtractDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $fileItem) {
                    $filePath = $fileItem->getRealPath();
                    $relativePath = substr($filePath, strlen($tempExtractDir));
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $newZip->addFile($filePath, $relativePath);
                    $newZip->setCompressionName($relativePath, ZipArchive::CM_DEFLATE, $zipLevel);
                }

                $newZip->close();
                clearstatcache(true, $compressedPath);

                if (filesize($compressedPath) >= $fileSize) {
                    copy($uploadedPath, $compressedPath);
                    clearstatcache(true, $compressedPath);
                    $method = 'File asli dipertahankan (sudah optimal, kompresi tidak mengurangi ukuran)';
                } else {
                    $method = 'Rezip (kompresi ulang struktur file)';
                }

            } else {
                copy($uploadedPath, $compressedPath);
                clearstatcache(true, $compressedPath);
                $method = 'Disalin tanpa kompresi (gagal membuat arsip baru)';
            }

        } else {
            copy($uploadedPath, $compressedPath);
            clearstatcache(true, $compressedPath);
            $method = 'Disalin tanpa kompresi (gagal membaca struktur file)';
        }

        deleteFolderRecursive($tempExtractDir);

    } else {
        copy($uploadedPath, $compressedPath);
        clearstatcache(true, $compressedPath);
        $method = 'Disalin tanpa kompresi (ZipArchive tidak tersedia)';
    }
}


// ============================================================
// FORMAT LAMA (DOC / XLS / PPT)
// ============================================================

else {
    copy($uploadedPath, $compressedPath);
    clearstatcache(true, $compressedPath);
    $method = 'Disalin tanpa kompresi (format lama tidak didukung)';
}


// ============================================================
// HITUNG HASIL
// ============================================================

clearstatcache(true, $compressedPath);

if (!file_exists($compressedPath)) {
    showError('Gagal membuat file hasil kompresi.');
}

$compressedSize = filesize($compressedPath);

$reduction = $originalSize > 0
    ? round((($originalSize - $compressedSize) / $originalSize) * 100, 1)
    : 0;

if ($reduction < 0) {
    $reduction = 0;
}


// ============================================================
// SIMPAN HASIL & REDIRECT
// ============================================================

$_SESSION['compress_result'] = [
    'original_name'   => $originalName,
    'original_size'   => formatFileSize($originalSize),
    'compressed_name' => $compressedFileName,
    'compressed_size' => formatFileSize($compressedSize),
    'reduction'       => $reduction,
    'method'          => $method,
];

header('Location: hasil.php');
exit;