<?php
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function getStatusBadge($status) {
    $classes = [
        'lunas' => 'bg-green-100 text-green-800',
        'belum lunas' => 'bg-red-100 text-red-800',
        'proses' => 'bg-yellow-100 text-yellow-800'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-100 text-gray-800';
    
    return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $class . '">' 
           . ucfirst($status) . '</span>';
}
?>