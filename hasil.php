<?php
session_start();

if (!isset($_SESSION['compress_result'])) {
    header('Location: index.php');
    exit;
}

$result = $_SESSION['compress_result'];
unset($_SESSION['compress_result']);

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hasil Kompresi | File Compressor</title>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

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
    max-width: 650px;
    margin: 60px auto;
}

.card {
    background: white;
    border-radius: 24px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .08);
}

.success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: #dcfce7;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
}

h1 {
    font-size: 26px;
    margin-bottom: 8px;
    color: #0f172a;
}

.subtitle {
    color: #64748b;
    margin-bottom: 30px;
}

.file-name {
    font-weight: 700;
    word-break: break-word;
}

.stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 25px 0;
}

.stat-box {
    background: #f1f5f9;
    border-radius: 14px;
    padding: 18px;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 6px;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
}

.reduction-badge {
    display: inline-block;
    background: #dcfce7;
    color: #16a34a;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 16px;
    margin-bottom: 10px;
}

.method-note {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 25px;
}

.actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn {
    flex: 1;
    min-width: 150px;
    padding: 15px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    font-size: 15px;
    display: block;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #174a8b, #2563eb);
    color: white;
}

.btn-secondary {
    background: #f1f5f9;
    color: #334155;
}

@media (max-width: 500px) {
    .card { padding: 25px; }
    .stats { grid-template-columns: 1fr; }
}
</style>
</head>

<body>

<div class="container">
    <div class="card">

        <div class="success-icon">&#10003;</div>

        <h1>Kompresi Berhasil!</h1>

        <p class="subtitle">
            File <span class="file-name"><?php echo e($result['original_name']); ?></span> sudah selesai diproses.
        </p>

        <?php if ($result['reduction'] > 0): ?>
            <div class="reduction-badge">
                Ukuran berkurang <?php echo e($result['reduction']); ?>%
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-label">Ukuran Awal</div>
                <div class="stat-value"><?php echo e($result['original_size']); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Ukuran Setelah</div>
                <div class="stat-value"><?php echo e($result['compressed_size']); ?></div>
            </div>
        </div>

        <div class="method-note">
            Metode: <?php echo e($result['method']); ?>
        </div>

        <div class="actions">
            <?php
            $downloadUrl = 'compressed/' . rawurlencode($result['compressed_name']);
            $downloadName = e($result['original_name']);
            ?>
            <a class="btn btn-primary" href="<?php echo $downloadUrl; ?>" download="<?php echo $downloadName; ?>">Download File</a>
            <a class="btn btn-secondary" href="index.php">Kompres File Lain</a>
        </div>

    </div>
</div>

</body>
</html>