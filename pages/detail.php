<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: all-data.php");
    exit();
}

$tax_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

if (isAdmin()) {
    $stmt = $conn->prepare("SELECT t.*, u.full_name as user_name FROM tax_records t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
} else {
    $stmt = $conn->prepare("SELECT * FROM tax_records WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $tax_id, $user_id);
}

$stmt->bind_param("i", $tax_id);
$stmt->execute();
$result = $stmt->get_result();
$tax_data = $result->fetch_assoc();

if (!$tax_data) {
    header("Location: all-data.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pajak - Sistem Pajak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Detail Pajak</h1>
            <a href="all-data.php" class="text-blue-600 hover:underline">Kembali</a>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <h2 class="text-xl font-semibold mb-4">Informasi Utama</h2>
                    
                    <?php if (isAdmin()): ?>
                        <div class="mb-4">
                            <p class="text-gray-600">Pemilik</p>
                            <p class="font-medium"><?= htmlspecialchars($tax_data['user_name']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <p class="text-gray-600">Nama</p>
                        <p class="font-medium"><?= htmlspecialchars($tax_data['name']) ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-gray-600">Tahun</p>
                        <p class="font-medium"><?= $tax_data['year'] ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-gray-600">Status</p>
                        <p class="font-medium">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $tax_data['status'] === 'lunas' ? 'bg-green-100 text-green-800' : 
                                   ($tax_data['status'] === 'belum lunas' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                <?= ucfirst($tax_data['status']) ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-xl font-semibold mb-4">Informasi Keuangan</h2>
                    
                    <div class="mb-4">
                        <p class="text-gray-600">Pajak Terutang</p>
                        <p class="font-medium">Rp <?= number_format($tax_data['tax_owed'], 0, ',', '.') ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-gray-600">Pajak Tahun Lalu</p>
                        <p class="font-medium">Rp <?= number_format($tax_data['last_year'], 0, ',', '.') ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-gray-600">Total</p>
                        <p class="font-medium">Rp <?= number_format($tax_data['tax_owed'] + $tax_data['last_year'], 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            
            <div>
                <h2 class="text-xl font-semibold mb-4">Catatan</h2>
                <div class="bg-gray-50 p-4 rounded-md">
                    <?= !empty($tax_data['notes']) ? nl2br(htmlspecialchars($tax_data['notes'])) : 'Tidak ada catatan' ?>
                </div>
            </div>
            
            <div class="mt-8">
                <a href="add-member.php?edit=<?= $tax_data['id'] ?>" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                    Edit Data
                </a>
            </div>
        </div>
    </div>
</body>
</html>