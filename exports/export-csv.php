<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Get filter parameters
$year = isset($_POST['year']) ? intval($_POST['year']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : 'all';

// Base query
if ($is_admin) {
    $query = "SELECT t.*, u.full_name as user_name FROM tax_records t JOIN users u ON t.user_id = u.id";
} else {
    $query = "SELECT * FROM tax_records WHERE user_id = ?";
}

// Add filters
$where = [];
$params = [];
$types = '';

if (!$is_admin) {
    $params[] = $user_id;
    $types .= 'i';
}

if ($year > 0) {
    $where[] = "year = ?";
    $params[] = $year;
    $types .= 'i';
}

if ($status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
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

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=rekap_pajak_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
$headers = [];
if ($is_admin) {
    $headers = ['Pemilik', 'Nama', 'Pajak Terutang', 'Tahun Lalu', 'Total', 'Status', 'Tahun', 'Catatan'];
} else {
    $headers = ['Nama', 'Pajak Terutang', 'Tahun Lalu', 'Total', 'Status', 'Tahun', 'Catatan'];
}
fputcsv($output, $headers);

// Write data rows
foreach ($taxes as $tax) {
    $row = [];
    if ($is_admin) {
        $row = [
            $tax['user_name'] ?? '',
            $tax['name'],
            $tax['tax_owed'],
            $tax['last_year'],
            $tax['tax_owed'] + $tax['last_year'],
            ucfirst($tax['status']),
            $tax['year'],
            $tax['notes'] ?? ''
        ];
    } else {
        $row = [
            $tax['name'],
            $tax['tax_owed'],
            $tax['last_year'],
            $tax['tax_owed'] + $tax['last_year'],
            ucfirst($tax['status']),
            $tax['year'],
            $tax['notes'] ?? ''
        ];
    }
    fputcsv($output, $row);
}

fclose($output);
exit;
?>