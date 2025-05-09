<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Query untuk mendapatkan semua data
if ($is_admin) {
    $query = "SELECT t.*, u.full_name as user_name FROM tax_records t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT * FROM tax_records WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$taxes = [];
while ($row = $result->fetch_assoc()) {
    $taxes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Data Pajak - Sistem Pajak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Semua Data Pajak</h1>
            <a href="add-member.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Tambah Data</a>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <?php if ($is_admin): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemilik</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pajak Terutang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun Lalu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($taxes as $tax): ?>
                        <tr>
                            <?php if ($is_admin): ?>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($tax['user_name'] ?? '') ?></td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($tax['name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($tax['tax_owed'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($tax['last_year'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $tax['status'] === 'lunas' ? 'bg-green-100 text-green-800' : 
                                       ($tax['status'] === 'belum lunas' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                    <?= ucfirst($tax['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $tax['year'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="detail.php?id=<?= $tax['id'] ?>" class="text-blue-600 hover:underline mr-2">Detail</a>
                                <?php if ($is_admin || $tax['user_id'] == $user_id): ?>
                                    <a href="add-member.php?edit=<?= $tax['id'] ?>" class="text-yellow-600 hover:underline mr-2">Edit</a>
                                    <a href="#" onclick="confirmDelete(<?= $tax['id'] ?>)" class="text-red-600 hover:underline">Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                window.location.href = `delete-tax.php?id=${id}`;
            }
        }
    </script>
</body>
</html>