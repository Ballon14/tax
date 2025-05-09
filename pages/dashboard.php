<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

// Ambil data user
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$current_year = date('Y');

// 1. Hitung Total Pajak
$total_query = $is_admin ? 
    "SELECT 
        SUM(tax_owed) as total_owed,
        SUM(CASE WHEN status = 'lunas' THEN tax_owed ELSE 0 END) as total_paid,
        COUNT(*) as total_records
     FROM tax_records" :
    "SELECT 
        SUM(tax_owed) as total_owed,
        SUM(CASE WHEN status = 'lunas' THEN tax_owed ELSE 0 END) as total_paid,
        COUNT(*) as total_records
     FROM tax_records WHERE user_id = ?";

$stmt = $conn->prepare($total_query);
if (!$is_admin) {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();
$totals['total_unpaid'] = ($totals['total_owed'] ?? 0) - ($totals['total_paid'] ?? 0);

// 2. Statistik per Tahun
$yearly_query = $is_admin ?
    "SELECT 
        year,
        SUM(tax_owed) as yearly_owed,
        SUM(CASE WHEN status = 'lunas' THEN tax_owed ELSE 0 END) as yearly_paid
     FROM tax_records
     GROUP BY year
     ORDER BY year DESC
     LIMIT 5" :
    "SELECT 
        year,
        SUM(tax_owed) as yearly_owed,
        SUM(CASE WHEN status = 'lunas' THEN tax_owed ELSE 0 END) as yearly_paid
     FROM tax_records
     WHERE user_id = ?
     GROUP BY year
     ORDER BY year DESC
     LIMIT 5";

$stmt = $conn->prepare($yearly_query);
if (!$is_admin) {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$yearly_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Statistik Status
$status_query = $is_admin ?
    "SELECT 
        status,
        COUNT(*) as count,
        SUM(tax_owed) as amount
     FROM tax_records
     GROUP BY status" :
    "SELECT 
        status,
        COUNT(*) as count,
        SUM(tax_owed) as amount
     FROM tax_records
     WHERE user_id = ?
     GROUP BY status";

$stmt = $conn->prepare($status_query);
if (!$is_admin) {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$status_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Data Terbaru
$recent_query = $is_admin ?
    "SELECT t.*, u.full_name as user_name 
     FROM tax_records t JOIN users u ON t.user_id = u.id
     ORDER BY t.created_at DESC LIMIT 5" :
    "SELECT * FROM tax_records WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";

$stmt = $conn->prepare($recent_query);
if (!$is_admin) {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$recent_taxes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format angka untuk tampilan
function formatCurrency($value) {
    return 'Rp ' . number_format($value ?? 0, 0, ',', '.');
}

function formatNumber($value) {
    return number_format($value ?? 0, 0, ',', '.');
}
?>

<?php include '../includes/header.php'; ?>

<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="card bg-gradient-to-r from-blue-500 to-blue-600 text-white">
        <h1 class="text-2xl font-bold">Selamat Datang, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h1>
        <p class="opacity-90"><?= date('l, d F Y') ?></p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Pajak -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">Total Pajak</h3>
                    <p class="text-2xl font-bold"><?= formatCurrency($totals['total_owed']) ?></p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400">
                    <i class="fas fa-file-invoice-dollar text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400"><?= formatNumber($totals['total_records']) ?> Data</span>
            </div>
        </div>
        
        <!-- Pajak Lunas -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">Pajak Lunas</h3>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= formatCurrency($totals['total_paid']) ?></p>
                </div>
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <?= $totals['total_owed'] > 0 ? round(($totals['total_paid'] / $totals['total_owed']) * 100, 2) : 0 ?>% dari total
                </span>
            </div>
        </div>
        
        <!-- Tunggakan -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">Tunggakan</h3>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= formatCurrency($totals['total_unpaid']) ?></p>
                </div>
                <div class="p-3 rounded-full bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <?= $totals['total_owed'] > 0 ? round(($totals['total_unpaid'] / $totals['total_owed']) * 100, 2) : 0 ?>% dari total
                </span>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Yearly Statistics -->
        <div class="card">
            <h2 class="text-xl font-semibold mb-4">Statistik per Tahun</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full data-table">
                    <thead>
                        <tr>
                            <th>Tahun</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Lunas</th>
                            <th class="text-right">Tunggakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($yearly_stats as $year): ?>
                        <?php $year_unpaid = $year['yearly_owed'] - $year['yearly_paid']; ?>
                        <tr>
                            <td><?= $year['year'] ?></td>
                            <td class="text-right"><?= formatCurrency($year['yearly_owed']) ?></td>
                            <td class="text-right text-green-600 dark:text-green-400"><?= formatCurrency($year['yearly_paid']) ?></td>
                            <td class="text-right text-red-600 dark:text-red-400"><?= formatCurrency($year_unpaid) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Status Distribution -->
        <div class="card">
            <h2 class="text-xl font-semibold mb-4">Distribusi Status</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php foreach ($status_stats as $status): ?>
                <div class="p-4 rounded-lg 
                    <?= $status['status'] === 'lunas' ? 'bg-green-50 dark:bg-green-900' : 
                       ($status['status'] === 'belum lunas' ? 'bg-red-50 dark:bg-red-900' : 'bg-yellow-50 dark:bg-yellow-900') ?>">
                    <h3 class="font-medium capitalize"><?= $status['status'] ?></h3>
                    <p class="text-2xl font-bold">
                        <?= $status['status'] === 'lunas' ? 
                            '<span class="text-green-600 dark:text-green-400">' . formatNumber($status['count']) . '</span>' :
                            ($status['status'] === 'belum lunas' ? 
                            '<span class="text-red-600 dark:text-red-400">' . formatNumber($status['count']) . '</span>' :
                            '<span class="text-yellow-600 dark:text-yellow-400">' . formatNumber($status['count']) . '</span>') ?>
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <?= formatCurrency($status['amount']) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Taxes -->
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Data Pajak Terbaru</h2>
            <a href="all-data.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                Lihat Semua <i class="fas fa-chevron-right ml-1 text-xs"></i>
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full data-table">
                <thead>
                    <tr>
                        <?php if ($is_admin): ?>
                            <th>Pemilik</th>
                        <?php endif; ?>
                        <th>Nama</th>
                        <th class="text-right">Pajak Terutang</th>
                        <th>Status</th>
                        <th>Tahun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_taxes as $tax): ?>
                    <tr>
                        <?php if ($is_admin): ?>
                            <td><?= htmlspecialchars($tax['user_name'] ?? '') ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($tax['name']) ?></td>
                        <td class="text-right"><?= formatCurrency($tax['tax_owed']) ?></td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?= $tax['status'] === 'lunas' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                   ($tax['status'] === 'belum lunas' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                   'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') ?>">
                                <?= ucfirst($tax['status']) ?>
                            </span>
                        </td>
                        <td><?= $tax['year'] ?></td>
                        <td>
                            <div class="flex space-x-2">
                                <a href="detail.php?id=<?= $tax['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline">Detail</a>
                                <?php if ($is_admin || $tax['user_id'] == $user_id): ?>
                                    <a href="add-member.php?edit=<?= $tax['id'] ?>" class="text-yellow-600 dark:text-yellow-400 hover:underline">Edit</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>