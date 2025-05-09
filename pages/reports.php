<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Filter parameters
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Base query
if ($is_admin) {
    $query = "SELECT t.*, u.full_name as user_name FROM tax_records t JOIN users u ON t.user_id = u.id";
} else {
    $query = "SELECT * FROM tax_records WHERE user_id = ?";
    $params = [$user_id];
    $types = 'i';
}

// Add filters
$where = [];

if ($is_admin) {
    $params = [];
    $types = '';
}

if ($year > 0) {
    if ($is_admin) {
        $where[] = "year = ?";
        $params[] = $year;
        $types .= 'i';
    } else {
        $where[] = "year = ?";
        $params[] = $year;
        $types .= 'i';
    }
}

if ($status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

// Construct final query
if ($is_admin && !empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
} elseif (!$is_admin && !empty($where)) {
    $query .= " AND " . implode(" AND ", $where);
}

$query .= " ORDER BY year DESC, name ASC";

// Prepare and execute
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$taxes = $result->fetch_all(MYSQLI_ASSOC);

// Get available years for filter
$years_query = $is_admin ? 
    "SELECT DISTINCT year FROM tax_records ORDER BY year DESC" : 
    "SELECT DISTINCT year FROM tax_records WHERE user_id = ? ORDER BY year DESC";
    
$years_stmt = $conn->prepare($years_query);

if (!$is_admin) {
    $years_stmt->bind_param('i', $user_id);
}

$years_stmt->execute();
$years_result = $years_stmt->get_result();
$available_years = $years_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id" class="<?= isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Data Pajak - TaxSystem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/export-csv.js" defer></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 md:mb-0">Rekap Data Pajak</h1>
            
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full md:w-auto">
                <!-- Export Button -->
                <button id="exportBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-csv mr-2"></i>Export to CSV
                </button>
                
                <!-- Filter Form -->
                <form method="GET" class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    <select name="year" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-700 dark:text-gray-200">
                        <option value="0" <?= $year === 0 ? 'selected' : '' ?>>Semua Tahun</option>
                        <?php foreach ($available_years as $y): ?>
                            <option value="<?= $y['year'] ?>" <?= $year === $y['year'] ? 'selected' : '' ?>>
                                <?= $y['year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-700 dark:text-gray-200">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="lunas" <?= $status === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                        <option value="belum lunas" <?= $status === 'belum lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                        <option value="proses" <?= $status === 'proses' ? 'selected' : '' ?>>Proses</option>
                    </select>
                    
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </form>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table id="taxTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <?php if ($is_admin): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemilik</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pajak Terutang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tahun Lalu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tahun</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($taxes as $tax): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <?php if ($is_admin): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200"><?= htmlspecialchars($tax['user_name'] ?? '') ?></td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200"><?= htmlspecialchars($tax['name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200">Rp <?= number_format($tax['tax_owed'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200">Rp <?= number_format($tax['last_year'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200 font-medium">Rp <?= number_format($tax['tax_owed'] + $tax['last_year'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $tax['status'] === 'lunas' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                       ($tax['status'] === 'belum lunas' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                       'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') ?>">
                                    <?= ucfirst($tax['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200"><?= $tax['year'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hidden form for CSV export -->
    <form id="exportForm" action="../exports/export-csv.php" method="POST" class="hidden">
        <input type="hidden" name="year" value="<?= $year ?>">
        <input type="hidden" name="status" value="<?= $status ?>">
    </form>
</body>
</html>