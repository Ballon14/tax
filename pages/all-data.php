<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : 0;

// Base query - hanya gunakan kolom yang pasti ada
if ($is_admin) {
    $query = "SELECT t.*, u.full_name as user_name FROM tax_records t JOIN users u ON t.user_id = u.id";
    $count_query = "SELECT COUNT(t.id) as total FROM tax_records t JOIN users u ON t.user_id = u.id";
} else {
    $query = "SELECT * FROM tax_records WHERE user_id = ?";
    $count_query = "SELECT COUNT(id) as total FROM tax_records WHERE user_id = ?";
}

// Add search and filter conditions
$conditions = [];
$params = [];
$types = '';

if (!$is_admin) {
    $params[] = $user_id;
    $types .= 'i';
}

if (!empty($search)) {
    // HANYA gunakan kolom 'name' yang pasti ada
    $conditions[] = "t.name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if (!empty($status_filter)) {
    $conditions[] = "t.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($year_filter > 0) {
    $conditions[] = "t.year = ?";
    $params[] = $year_filter;
    $types .= 'i';
}

// Combine conditions
if (!empty($conditions)) {
    $where_clause = ' WHERE ' . implode(' AND ', $conditions);
    $query .= $where_clause;
    $count_query .= $where_clause;
}

// Add sorting
$valid_sort_columns = ['name', 'tax_owed', 'status', 'year', 'created_at'];
if ($is_admin) {
    $valid_sort_columns[] = 'user_name';
}

$sort = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sort_columns) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
$query .= " ORDER BY $sort $order";

// Add pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Prepare and execute main query
try {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $taxes = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Get total count for pagination
try {
    $count_stmt = $conn->prepare($count_query);
    if ($count_stmt === false) {
        throw new Exception("Count prepare failed: " . $conn->error);
    }

    // Bind parameters for count query (without pagination params)
    $count_params = array_slice($params, 0, count($params) - 2);
    $count_types = substr($types, 0, -2);

    if (!empty($count_types)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }

    if (!$count_stmt->execute()) {
        throw new Exception("Count execute failed: " . $count_stmt->error);
    }

    $total_result = $count_stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_taxes = $total_row['total'];
    $total_pages = ceil($total_taxes / $limit);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Get distinct years for filter dropdown
try {
    $years_query = $is_admin ? 
        "SELECT DISTINCT year FROM tax_records ORDER BY year DESC" : 
        "SELECT DISTINCT year FROM tax_records WHERE user_id = ? ORDER BY year DESC";
    $years_stmt = $conn->prepare($years_query);
    if ($years_stmt === false) {
        throw new Exception("Years prepare failed: " . $conn->error);
    }

    if (!$is_admin) {
        $years_stmt->bind_param('i', $user_id);
    }

    if (!$years_stmt->execute()) {
        throw new Exception("Years execute failed: " . $years_stmt->error);
    }

    $years_result = $years_stmt->get_result();
    $years = $years_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Data Pajak - Sistem Pajak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Semua Data Pajak</h1>
            <div>
                <a href="add-member.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mr-2">
                    <i class="fas fa-plus mr-1"></i> Tambah Data
                </a>
                <a href="export.php?<?= http_build_query($_GET) ?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-file-export mr-1"></i> Export
                </a>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <form method="GET" action="">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               class="w-full border rounded-md p-2" placeholder="Cari nama/deskripsi...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full border rounded-md p-2">
                            <option value="">Semua Status</option>
                            <option value="lunas" <?= $status_filter === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                            <option value="belum lunas" <?= $status_filter === 'belum lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                        <select name="year" class="w-full border rounded-md p-2">
                            <option value="0">Semua Tahun</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year['year'] ?>" <?= $year_filter == $year['year'] ? 'selected' : '' ?>>
                                    <?= $year['year'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 w-full">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                        <a href="?" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 ml-2">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <?php if (empty($taxes)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox fa-3x mb-4"></i>
                    <p>Tidak ada data pajak ditemukan</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php if ($is_admin): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        onclick="sortTable('user_name')">
                                        Pemilik
                                        <?= $sort === 'user_name' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                    </th>
                                <?php endif; ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                    onclick="sortTable('name')">
                                    Nama
                                    <?= $sort === 'name' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                    onclick="sortTable('tax_owed')">
                                    Pajak Terutang
                                    <?= $sort === 'tax_owed' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tahun Lalu
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                    onclick="sortTable('status')">
                                    Status
                                    <?= $sort === 'status' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                    onclick="sortTable('year')">
                                    Tahun
                                    <?= $sort === 'year' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
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
                                    <div class="flex space-x-2">
                                        <a href="detail.php?id=<?= $tax['id'] ?>" 
                                           class="text-blue-600 hover:text-blue-800" 
                                           title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($is_admin || $tax['user_id'] == $user_id): ?>
                                            <a href="add-member.php?edit=<?= $tax['id'] ?>" 
                                               class="text-yellow-600 hover:text-yellow-800" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" 
                                               onclick="confirmDelete(<?= $tax['id'] ?>)" 
                                               class="text-red-600 hover:text-red-800" 
                                               title="Hapus">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex items-center justify-between mt-4">
                    <div class="text-sm text-gray-700">
                        Menampilkan <span class="font-medium"><?= ($offset + 1) ?></span> sampai 
                        <span class="font-medium"><?= min($offset + $limit, $total_taxes) ?></span> dari 
                        <span class="font-medium"><?= $total_taxes ?></span> data
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" 
                               class="px-3 py-1 border rounded-md hover:bg-gray-100">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                               class="px-3 py-1 border rounded-md hover:bg-gray-100">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php 
                        // Show limited page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<span class="px-3 py-1">...</span>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="px-3 py-1 border rounded-md <?= $i == $page ? 'bg-blue-100 border-blue-300' : 'hover:bg-gray-100' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; 
                        
                        if ($end_page < $total_pages) {
                            echo '<span class="px-3 py-1">...</span>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                               class="px-3 py-1 border rounded-md hover:bg-gray-100">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" 
                               class="px-3 py-1 border rounded-md hover:bg-gray-100">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                window.location.href = `delete-tax.php?id=${id}`;
            }
        }

        function sortTable(column) {
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);
            
            // Toggle order if same column clicked
            if (params.get('sort') === column) {
                params.set('order', params.get('order') === 'ASC' ? 'DESC' : 'ASC');
            } else {
                params.set('sort', column);
                params.set('order', 'ASC');
            }
            
            // Reset to first page when sorting
            params.set('page', '1');
            
            window.location.href = `${url.pathname}?${params.toString()}`;
        }
    </script>
</body>
</html>
