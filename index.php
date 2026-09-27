```php
<?php
// ============================================================
// FILE COMPRESSOR - INDEX.PHP
// Mendukung: PDF, Word, Excel, PowerPoint
// ============================================================

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>File Compressor | Kompres Dokumen Online</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, #dbeafe 0, transparent 35%),
                radial-gradient(circle at bottom right, #dcfce7 0, transparent 35%),
                #f8fafc;
            min-height: 100vh;
            color: #1e293b;
        }

        .container {
            width: 92%;
            max-width: 900px;
            margin: auto;
        }

        /* HEADER */
        header {
            padding: 28px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 22px;
            font-weight: 700;
            color: #174a8b;
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #174a8b, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 23px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .2);
        }

        /* HERO */
        .hero {
            text-align: center;
            padding: 45px 20px 30px;
        }

        .hero h1 {
            font-size: clamp(32px, 5vw, 50px);
            line-height: 1.1;
            margin-bottom: 16px;
            color: #0f172a;
        }

        .hero h1 span {
            color: #2563eb;
        }

        .hero p {
            font-size: 17px;
            color: #64748b;
            max-width: 650px;
            margin: auto;
            line-height: 1.7;
        }

        /* CARD */
        .compress-card {
            background: rgba(255,255,255,.95);
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, .08);
            margin: 20px auto 40px;
        }

        /* DROPZONE */
        .drop-zone {
            border: 2px dashed #94a3b8;
            border-radius: 18px;
            padding: 50px 20px;
            text-align: center;
            cursor: pointer;
            transition: .25s ease;
            background: #f8fafc;
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .upload-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 32px;
        }

        .drop-zone h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .drop-zone p {
            color: #64748b;
            margin-bottom: 15px;
        }

        .browse-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
        }

        #fileInput {
            display: none;
        }

        /* FILE INFO */
        .file-info {
            display: none;
            margin-top: 20px;
            padding: 16px;
            background: #f1f5f9;
            border-radius: 14px;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .file-info.active {
            display: flex;
        }

        .file-left {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }

        .file-icon {
            width: 45px;
            height: 45px;
            background: #e0f2fe;
            color: #0284c7;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .file-details {
            min-width: 0;
        }

        .file-name {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 450px;
        }

        .file-size {
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
        }

        .remove-btn {
            border: none;
            background: #fee2e2;
            color: #dc2626;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
        }

        /* SETTINGS */
        .settings {
            margin-top: 25px;
        }

        .settings-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .compression-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .compression-option {
            position: relative;
        }

        .compression-option input {
            display: none;
        }

        .compression-option label {
            display: block;
            padding: 18px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            cursor: pointer;
            text-align: center;
            transition: .2s;
        }

        .compression-option input:checked + label {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .option-title {
            display: block;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .option-desc {
            display: block;
            font-size: 12px;
            color: #64748b;
        }

        /* BUTTON */
        .compress-btn {
            width: 100%;
            margin-top: 25px;
            padding: 16px;
            border: none;
            border-radius: 13px;
            background: linear-gradient(135deg, #174a8b, #2563eb);
            color: white;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
        }

        .compress-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, .25);
        }

        .compress-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* SUPPORTED */
        .supported {
            text-align: center;
            margin-bottom: 50px;
        }

        .supported h3 {
            margin-bottom: 18px;
            color: #334155;
        }

        .formats {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .format {
            padding: 9px 15px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        /* FOOTER */
        footer {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
            font-size: 13px;
        }

        /* ALERT */
        .alert {
            display: none;
            margin-top: 15px;
            padding: 12px 15px;
            border-radius: 10px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 14px;
        }

        .alert.show {
            display: block;
        }

        /* RESPONSIVE */
        @media (max-width: 600px) {

            .compress-card {
                padding: 18px;
                border-radius: 18px;
            }

            .drop-zone {
                padding: 35px 15px;
            }

            .compression-options {
                grid-template-columns: 1fr;
            }

            .file-info {
                align-items: flex-start;
            }

            .file-name {
                max-width: 200px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="container">
        <div class="logo">
            <div class="logo-icon">📦</div>
            <span>File Compressor</span>
        </div>
    </div>
</header>


<main>

    <section class="hero">
        <h1>Kompres File <span>Lebih Mudah</span></h1>

        <p>
            Kurangi ukuran file PDF, Word, Excel, dan PowerPoint
            dengan cepat tanpa proses yang rumit.
        </p>
    </section>


    <div class="container">

        <form
            id="compressForm"
            action="proses.php"
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="compress-card">

                <!-- DROPZONE -->
                <div
                    class="drop-zone"
                    id="dropZone"
                >

                    <div class="upload-icon">
                        ↑
                    </div>

                    <h3>Upload File Anda</h3>

                    <p>
                        Drag & drop file ke sini atau pilih dari komputer
                    </p>

                    <span class="browse-btn">
                        Pilih File
                    </span>

                    <input
                        type="file"
                        name="file"
                        id="fileInput"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                    >

                </div>


                <!-- FILE INFO -->
                <div
                    class="file-info"
                    id="fileInfo"
                >

                    <div class="file-left">

                        <div
                            class="file-icon"
                            id="fileIcon"
                        >
                            📄
                        </div>

                        <div class="file-details">

                            <div
                                class="file-name"
                                id="fileName"
                            >
                            </div>

                            <div
                                class="file-size"
                                id="fileSize"
                            >
                            </div>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="remove-btn"
                        id="removeFile"
                        title="Hapus file"
                    >
                        ×
                    </button>

                </div>


                <div
                    class="alert"
                    id="alert"
                >
                </div>


                <!-- SETTINGS -->
<div class="settings">

    <div class="settings-title">
        Tingkat Kompresi
    </div>

    <div class="compression-options">

        <div class="compression-option">
            <input type="radio" name="preset" value="25" id="low">
            <label for="low">
                <span class="option-title">Rendah</span>
                <span class="option-desc">Kualitas tinggi</span>
            </label>
        </div>

        <div class="compression-option">
            <input type="radio" name="preset" value="50" id="medium" checked>
            <label for="medium">
                <span class="option-title">Sedang</span>
                <span class="option-desc">Seimbang</span>
            </label>
        </div>

        <div class="compression-option">
            <input type="radio" name="preset" value="75" id="high">
            <label for="high">
                <span class="option-title">Tinggi</span>
                <span class="option-desc">Ukuran terkecil</span>
            </label>
        </div>

    </div>

    <div style="margin-top: 20px;">

        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span style="font-size: 14px; font-weight: 600; color: #334155;">
                Atur Manual
            </span>
            <span style="font-size: 14px; font-weight: 700; color: #2563eb;">
                <span id="compressionValue">50</span>%
            </span>
        </div>

        <input
            type="range"
            name="compression"
            id="compressionSlider"
            min="10"
            max="90"
            value="50"
            step="5"
            style="width: 100%;"
        >

        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-top: 4px;">
            <span>Kualitas Tinggi</span>
            <span>Ukuran Terkecil</span>
        </div>

    </div>

</div>

                <!-- SUBMIT -->
                <button
                    type="submit"
                    class="compress-btn"
                    id="compressBtn"
                    disabled
                >
                    🚀 Kompres File
                </button>

            </div>

        </form>


        <!-- SUPPORTED FORMAT -->
        <section class="supported">

            <h3>Format yang Didukung</h3>

            <div class="formats">

                <span class="format">📄 PDF</span>

                <span class="format">📝 DOC</span>

                <span class="format">📝 DOCX</span>

                <span class="format">📊 XLS</span>

                <span class="format">📊 XLSX</span>

                <span class="format">📽️ PPT</span>

                <span class="format">📽️ PPTX</span>

            </div>

        </section>

    </div>

</main>


<footer>

    File Compressor &copy; <?php echo date('Y'); ?>

</footer>


<script>

const fileInput = document.getElementById('fileInput');
const dropZone = document.getElementById('dropZone');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const fileIcon = document.getElementById('fileIcon');
const removeFile = document.getElementById('removeFile');
const compressBtn = document.getElementById('compressBtn');
const alertBox = document.getElementById('alert');
const form = document.getElementById('compressForm');

const maxFileSize = <?php echo $maxFileSize; ?>;

const allowedExtensions = <?php echo json_encode($allowedExtensions); ?>;


// ============================================================
// FORMAT UKURAN FILE
// ============================================================

function formatFileSize(bytes) {

    if (bytes === 0) {
        return '0 Bytes';
    }

    const units = [
        'Bytes',
        'KB',
        'MB',
        'GB'
    ];

    const i = Math.floor(
        Math.log(bytes) / Math.log(1024)
    );

    return (
        bytes / Math.pow(1024, i)
    ).toFixed(2) + ' ' + units[i];
}


// ============================================================
// TAMPILKAN ALERT
// ============================================================

function showAlert(message) {

    alertBox.textContent = message;
    alertBox.classList.add('show');
}


// ============================================================
// HAPUS ALERT
// ============================================================

function hideAlert() {

    alertBox.textContent = '';
    alertBox.classList.remove('show');
}


// ============================================================
// ICON BERDASARKAN EXTENSION
// ============================================================

function getFileIcon(extension) {

    extension = extension.toLowerCase();

    if (extension === 'pdf') {
        return '📕';
    }

    if (
        extension === 'doc' ||
        extension === 'docx'
    ) {
        return '📝';
    }

    if (
        extension === 'xls' ||
        extension === 'xlsx'
    ) {
        return '📊';
    }

    if (
        extension === 'ppt' ||
        extension === 'pptx'
    ) {
        return '📽️';
    }

    return '📄';
}


// ============================================================
// PROSES FILE
// ============================================================

function handleFile(file) {

    hideAlert();

    if (!file) {
        return;
    }

    const fileExtension =
        file.name
            .split('.')
            .pop()
            .toLowerCase();


    // CEK FORMAT

    if (
        !allowedExtensions.includes(fileExtension)
    ) {

        showAlert(
            'Format file tidak didukung. Silakan pilih PDF, Word, Excel, atau PowerPoint.'
        );

        fileInput.value = '';

        return;
    }


    // CEK UKURAN

    if (file.size > maxFileSize) {

        showAlert(
            'Ukuran file terlalu besar. Maksimal 100 MB.'
        );

        fileInput.value = '';

        return;
    }


    // TAMPILKAN INFORMASI FILE

    fileName.textContent = file.name;

    fileSize.textContent =
        formatFileSize(file.size);

    fileIcon.textContent =
        getFileIcon(fileExtension);

    fileInfo.classList.add('active');

    compressBtn.disabled = false;
}


// ============================================================
// PILIH FILE
// ============================================================

fileInput.addEventListener(
    'change',
    function () {

        if (this.files.length > 0) {

            handleFile(
                this.files[0]
            );

        }

    }
);


// ============================================================
// DROP FILE
// ============================================================

dropZone.addEventListener(
    'dragover',
    function (event) {

        event.preventDefault();

        dropZone.classList.add('dragover');

    }
);


dropZone.addEventListener(
    'dragleave',
    function () {

        dropZone.classList.remove('dragover');

    }
);


dropZone.addEventListener(
    'drop',
    function (event) {

        event.preventDefault();

        dropZone.classList.remove('dragover');

        const files =
            event.dataTransfer.files;

        if (files.length > 0) {

            fileInput.files = files;

            handleFile(files[0]);

        }

    }
);


// ============================================================
// KLIK DROPZONE
// ============================================================

dropZone.addEventListener(
    'click',
    function () {

        fileInput.click();

    }
);


// ============================================================
// HAPUS FILE
// ============================================================

removeFile.addEventListener(
    'click',
    function (event) {

        event.stopPropagation();

        fileInput.value = '';

        fileInfo.classList.remove('active');

        compressBtn.disabled = true;

        hideAlert();

    }
);


// ============================================================
// SUBMIT FORM
// ============================================================

form.addEventListener(
    'submit',
    function (event) {

        if (!fileInput.files.length) {

            event.preventDefault();

            showAlert(
                'Silakan pilih file terlebih dahulu.'
            );

            return;
        }

        compressBtn.disabled = true;

        compressBtn.innerHTML =
            '⏳ Memproses file...';

    }
);

const compressionSlider = document.getElementById('compressionSlider');
const compressionValue = document.getElementById('compressionValue');
const presetRadios = document.querySelectorAll('input[name="preset"]');

// Slider digeser manual -> update angka, lepaskan pilihan preset
compressionSlider.addEventListener('input', function () {
    compressionValue.textContent = this.value;
    presetRadios.forEach(radio => radio.checked = false);
});

// Klik salah satu preset (Rendah/Sedang/Tinggi) -> slider ikut pindah
presetRadios.forEach(radio => {
    radio.addEventListener('change', function () {
        compressionSlider.value = this.value;
        compressionValue.textContent = this.value;
    });
});

</script>

</body>
</html>
```
