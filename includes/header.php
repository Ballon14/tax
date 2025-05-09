<?php
require_once 'auth.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id" class="<?= isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pajak - <?= ucfirst(str_replace('-', ' ', str_replace('.php', '', $current_page))) ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            600: '#0284c7',
                            700: '#0369a1',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen flex flex-col">
    <!-- Top Navigation Bar -->
    <header class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-2">
                    <i class="fas fa-file-invoice-dollar text-2xl text-primary-600 dark:text-primary-400"></i>
                    <span class="text-xl font-bold">Tax<span class="text-primary-600 dark:text-primary-400">System</span></span>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'bg-primary-100 dark:bg-primary-900' : '' ?> px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-gray-700 transition flex items-center">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    
                    <a href="all-data.php" class="<?= $current_page === 'all-data.php' ? 'bg-primary-100 dark:bg-primary-900' : '' ?> px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-gray-700 transition flex items-center">
                        <i class="fas fa-list mr-2"></i>Lihat Data
                    </a>
                    
                    <a href="add-member.php" class="<?= $current_page === 'add-member.php' ? 'bg-primary-100 dark:bg-primary-900' : '' ?> px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-gray-700 transition flex items-center">
                        <i class="fas fa-plus mr-2"></i>Tambah Data
                    </a>
                    
                    <a href="reports.php" class="<?= $current_page === 'reports.php' ? 'bg-primary-100 dark:bg-primary-900' : '' ?> px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-gray-700 transition flex items-center">
                        <i class="fas fa-file-export mr-2"></i>Rekap Data
                    </a>
                </nav>

                <!-- User Controls -->
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <button id="themeToggle" class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-yellow-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                    
                    <?php if (isLoggedIn()): ?>
                        <!-- User Info -->
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white">
                                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                            </div>
                            <span class="hidden md:inline"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                        </div>
                        <a href="../logout.php" class="hidden md:flex items-center px-3 py-1 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition">
                            <i class="fas fa-sign-out-alt mr-1"></i>
                        </a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobileMenuButton" class="md:hidden p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu (Hidden by default) -->
        <div id="mobileMenu" class="md:hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 hidden">
            <div class="container mx-auto px-4 py-2 space-y-1">
                <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'bg-primary-100 dark:bg-primary-900' : '' ?> block px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-gray-700 flex items-center">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="all-data.php" class="<?= $current_page === 'all-data.php' ? 'bg-primary-100 dark:bg-primary-900' : '' ?> block px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-gray-700 flex items-center">
                    <i class="fas fa-list mr-2"></i>Lihat Data
                </a>
                <a href="add-member.php" class="<?= $current_page === 'add-member.php' ? 'bg-primary-100 dark:bg-primary-900' : '' ?> block px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-gray-700 flex items-center">
                    <i class="fas fa-plus mr-2"></i>Tambah Data
                </a>
                <a href="reports.php" class="<?= $current_page === 'reports.php' ? 'bg-primary-100 dark:bg-primary-900' : '' ?> block px-4 py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-gray-700 flex items-center">
                    <i class="fas fa-file-export mr-2"></i>Rekap Data
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
                        <div class="flex items-center px-4 py-2">
                            <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white mr-3">
                                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400"><?= ucfirst($_SESSION['role']) ?></p>
                            </div>
                        </div>
                        <a href="../logout.php" class="block px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 rounded-lg flex items-center">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
</body>
</html>