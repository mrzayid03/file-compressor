```php
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

$allowedExtensions = [
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx'
];


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

    $units = [
        'Bytes',
        'KB',
        'MB',
        'GB'
    ];

    $i = floor(
        log($bytes, 1024)
    );

    return round(
        $bytes / pow(1024, $i),
        2
    ) . ' ' . $units[$i];
}


// ============================================================
// ESCAPE HTML
// ============================================================

function e($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
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

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Error | File Compressor</title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, Helvetica, sans-serif;
                background: #f8fafc;
                color: #1e293b;
            }

            .container {
                width: 92%;
                max-width: 650px;
                margin: 100px auto;
            }

            .card {
                background: white;
                border-radius: 20px;
                padding: 40px;
                text-align: center;
                box-shadow:
                    0 20px 50px rgba(15, 23, 42, .08);
            }

            .icon {
                width: 70px;
                height: 70px;
                margin: auto auto 20px;
                border-radius: 50%;
                background: #fee2e2;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 32px;
            }

            h1 {
                margin-bottom: 12px;
            }

            p {
                color: #64748b;
                line-height: 1.6;
            }

            a {
                display: inline-block;
                margin-top: 20px;
                padding: 13px 25px;
                border-radius: 10px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                font-weight: 700;
            }

        </style>

    </head>

    <body>

        <div class="container">

            <div class="card">

                <div class="icon">
                    ⚠️
                </div>

                <h1>Terjadi Kesalahan</h1>

                <p>
                    <?php echo e($message); ?>
                </p>

                <a href="index.php">
                    ← Kembali
                </a>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit;
}


// ============================================================
// CEK REQUEST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    showError(
        'Metode akses tidak valid.'
    );

}


// ============================================================
// CEK FILE
// ============================================================

if (
    !isset($_FILES['file']) ||
    !is_array($_FILES['file'])
) {

    showError(
        'Tidak ada file yang dikirim.'
    );

}


$file = $_FILES['file'];


// ============================================================
// CEK ERROR UPLOAD
// ============================================================

if ($file['error'] !== UPLOAD_ERR_OK) {

    switch ($file['error']) {

        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:

            $message =
                'Ukuran file melebihi batas yang diperbolehkan.';

            break;

        case UPLOAD_ERR_PARTIAL:

            $message =
                'File hanya terupload sebagian.';

            break;

        case UPLOAD_ERR_NO_FILE:

            $message =
                'Tidak ada file yang dipilih.';

            break;

        default:

            $message =
                'Terjadi kesalahan ketika mengupload file.';

            break;

    }

    showError($message);

}


// ============================================================
// INFORMASI FILE
// ============================================================

$originalName =
    basename($file['name']);

$fileSize =
    (int) $file['size'];

$tmpName =
    $file['tmp_name'];


// ============================================================
// CEK UKURAN
// ============================================================

if ($fileSize <= 0) {

    showError(
        'File kosong atau tidak valid.'
    );

}

if ($fileSize > $maxFileSize) {

    showError(
        'Ukuran file terlalu besar. Maksimal 100 MB.'
    );

}


// ============================================================
// CEK EXTENSION
// ============================================================

$extension =
    strtolower(
        pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        )
    );

if (
    !in_array(
        $extension,
        $allowedExtensions,
        true
    )
) {

    showError(
        'Format file tidak didukung.'
    );

}


// ============================================================
// CEK IS_UPLOADED_FILE
// ============================================================

if (!is_uploaded_file($tmpName)) {

    showError(
        'File upload tidak valid.'
    );

}


// ============================================================
// NAMA FILE AMAN
// ============================================================

$safeBaseName =
    pathinfo(
        $originalName,
        PATHINFO_FILENAME
    );

$safeBaseName =
    preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '_',
        $safeBaseName
    );

if (!$safeBaseName) {
    $safeBaseName = 'file';
}


// ============================================================
// ID UNIK
// ============================================================

$uniqueId =
    date('Ymd_His') .
    '_' .
    bin2hex(
        random_bytes(5)
    );


// ============================================================
// NAMA FILE UPLOAD
// ============================================================

$uploadedFileName =
    $safeBaseName .
    '_' .
    $uniqueId .
    '.' .
    $extension;

$uploadedPath =
    $uploadDir .
    $uploadedFileName;


// ============================================================
// PINDAHKAN FILE
// ============================================================

if (
    !move_uploaded_file(
        $tmpName,
        $uploadedPath
    )
) {

    showError(
        'Gagal menyimpan file upload.'
    );

}

// ============================================================
// VALIDASI TINGKAT KOMPRESI
// ============================================================

$compression = $_POST['compression'] ?? 'medium';

$validCompression = ['low', 'medium', 'high'];

if (!in_array($compression, $validCompression, true)) {
    $compression = 'medium';
}


// ============================================================
// SIAPKAN NAMA FILE HASIL KOMPRESI
// ============================================================

$compressedFileName = $safeBaseName . '_' . $uniqueId . '_compressed.' . $extension;
$compressedPath = $compressedDir . $compressedFileName;

$originalSize = $fileSize;
$compressedSize = $originalSize;
$method = 'copy';


// ============================================================
// KOMPRESI PDF (VIA GHOSTSCRIPT, JIKA TERSEDIA)
// ============================================================

if ($extension === 'pdf') {

    $gsSettingMap = [
        'low'    => '/prepress',
        'medium' => '/ebook',
        'high'   => '/screen',
    ];

    $gsSetting = $gsSettingMap[$compression];

    // Sesuaikan 'gswin64c' jika Ghostscript diinstal dengan nama lain,
    // atau ganti dengan path lengkap misalnya:
    // 'C:\\Program Files\\gs\\gs10.03.0\\bin\\gswin64c.exe'
    if (stripos(PHP_OS, 'WIN') === 0) {
    $gsExecutable = 'C:\\Program Files\\gs\\gs10.08.0\\bin\\gswin64c.exe';
} else {
    $gsExecutable = 'gs';
}
    
    $cmd = escapeshellarg($gsExecutable)
        . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4'
        . ' -dPDFSETTINGS=' . $gsSetting
        . ' -dNOPAUSE -dQUIET -dBATCH'
        . ' -sOutputFile=' . escapeshellarg($compressedPath)
        . ' ' . escapeshellarg($uploadedPath);

    $output = [];
    $returnCode = 1;

    if (function_exists('exec')) {
        @exec($cmd . ' 2>&1', $output, $returnCode);
    }

    if (
        $returnCode === 0 &&
        file_exists($compressedPath) &&
        filesize($compressedPath) > 0
    ) {
        $method = 'Ghostscript';
    } else {
        copy($uploadedPath, $compressedPath);
        $method = 'Disalin tanpa kompresi (Ghostscript tidak ditemukan di sistem)';
    }
}


// ============================================================
// KOMPRESI DOCX / XLSX / PPTX (FORMAT ZIP)
// ============================================================

elseif (in_array($extension, ['docx', 'xlsx', 'pptx'], true)) {

    $zipLevelMap = [
        'low'    => 6,
        'medium' => 8,
        'high'   => 9,
    ];

    $zipLevel = $zipLevelMap[$compression];

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
                    new RecursiveDirectoryIterator(
                        $tempExtractDir,
                        RecursiveDirectoryIterator::SKIP_DOTS
                    ),
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
                $method = 'Rezip (kompresi ulang struktur file)';

            } else {
                copy($uploadedPath, $compressedPath);
                $method = 'Disalin tanpa kompresi (gagal membuat arsip baru)';
            }

        } else {
            copy($uploadedPath, $compressedPath);
            $method = 'Disalin tanpa kompresi (gagal membaca struktur file)';
        }

        // Bersihkan folder sementara
        deleteFolderRecursive($tempExtractDir);

    } else {
        copy($uploadedPath, $compressedPath);
        $method = 'Disalin tanpa kompresi (ZipArchive tidak tersedia)';
    }
}


// ============================================================
// FORMAT LAMA (DOC / XLS / PPT) — TIDAK BISA DIKOMPRES DI SINI
// ============================================================

else {
    copy($uploadedPath, $compressedPath);
    $method = 'Disalin tanpa kompresi (format lama tidak didukung)';
}


// ============================================================
// HITUNG HASIL
// ============================================================

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
// SIMPAN HASIL KE SESSION, ARAHKAN KE HALAMAN HASIL
// ============================================================

$_SESSION['compress_result'] = [
    'original_name'     => $originalName,
    'original_size'     => formatFileSize($originalSize),
    'compressed_name'   => $compressedFileName,
    'compressed_size'   => formatFileSize($compressedSize),
    'reduction'         => $reduction,
    'method'            => $method,
];

header('Location: hasil.php');
exit;


// ============================================================
// FUNGSI HAPUS FOLDER REKURSIF (dipakai untuk bersih-bersih temp)
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