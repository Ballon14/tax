<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$is_edit = isset($_GET['edit']);
$tax_id = $is_edit ? intval($_GET['edit']) : 0;
$tax_data = null;
$errors = [];

if ($is_edit) {
    // Ambil data yang akan diedit
    $user_id = $_SESSION['user_id'];
    
    if (isAdmin()) {
        $stmt = $conn->prepare("SELECT * FROM tax_records WHERE id = ?");
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
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $tax_owed = floatval(str_replace(['.', ','], ['', '.'], $_POST['tax_owed']));
    $last_year = floatval(str_replace(['.', ','], ['', '.'], $_POST['last_year']));
    $status = $_POST['status'];
    $year = intval($_POST['year']);
    $notes = trim($_POST['notes']);
    
    // Validasi
    if (empty($name)) {
        $errors[] = "Nama wajib diisi";
    }
    
    if ($tax_owed <= 0) {
        $errors[] = "Pajak terutang harus lebih dari 0";
    }
    
    if ($year < 2000 || $year > date('Y') + 1) {
        $errors[] = "Tahun tidak valid";
    }
    
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        
        if ($is_edit) {
            // Update data
            $stmt = $conn->prepare("UPDATE tax_records SET name = ?, tax_owed = ?, last_year = ?, status = ?, year = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("sddssii", $name, $tax_owed, $last_year, $status, $year, $notes, $tax_id);
        } else {
            // Tambah data baru
            $stmt = $conn->prepare("INSERT INTO tax_records (user_id, name, tax_owed, last_year, status, year, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isddssi", $user_id, $name, $tax_owed, $last_year, $status, $year, $notes);
        }
        
        if ($stmt->execute()) {
            header("Location: all-data.php?success=1");
            exit();
        } else {
            $errors[] = "Terjadi kesalahan saat menyimpan data";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Data Pajak - Sistem Pajak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6"><?= $is_edit ? 'Edit' : 'Tambah' ?> Data Pajak</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-gray-700 mb-2">Nama</label>
                        <input type="text" id="name" name="name" required 
                               value="<?= htmlspecialchars($tax_data['name'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="tax_owed" class="block text-gray-700 mb-2">Pajak Terutang</label>
                        <input type="text" id="tax_owed" name="tax_owed" required 
                               value="<?= isset($tax_data['tax_owed']) ? number_format($tax_data['tax_owed'], 0, ',', '.') : '' ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="last_year" class="block text-gray-700 mb-2">Pajak Tahun Lalu</label>
                        <input type="text" id="last_year" name="last_year" 
                               value="<?= isset($tax_data['last_year']) ? number_format($tax_data['last_year'], 0, ',', '.') : '0' ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="status" class="block text-gray-700 mb-2">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="belum lunas" <?= isset($tax_data['status']) && $tax_data['status'] === 'belum lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                            <option value="proses" <?= isset($tax_data['status']) && $tax_data['status'] === 'proses' ? 'selected' : '' ?>>Proses</option>
                            <option value="lunas" <?= isset($tax_data['status']) && $tax_data['status'] === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="year" class="block text-gray-700 mb-2">Tahun</label>
                        <input type="number" id="year" name="year" required min="2000" max="<?= date('Y') + 1 ?>"
                               value="<?= $tax_data['year'] ?? date('Y') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="mt-6">
                    <label for="notes" class="block text-gray-700 mb-2">Catatan</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($tax_data['notes'] ?? '') ?></textarea>
                </div>
                
                <div class="mt-8">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <?= $is_edit ? 'Update' : 'Simpan' ?> Data
                    </button>
                    <a href="all-data.php" class="ml-4 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>d