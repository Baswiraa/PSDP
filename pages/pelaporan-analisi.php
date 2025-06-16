<?php
require_once '../includes/functions.php';
check_login();
if (!has_access(['manager'])) { // Hanya manajer
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';

// Logika PHP untuk mengambil data laporan dan analisis
// Contoh: Total penjualan per bulan, produk terlaris, efisiensi produksi, dll.

// Contoh data untuk laporan (akan diambil dari DB)
$total_sales_this_month = 0; // Query SUM total_amount dari sales_orders
$top_products = []; // Query GROUP BY product_id dari sales_order_items
$production_efficiency = 0; // Hitungan dari production_processes
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelaporan & Analisis - SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="../assets/lib/chart.js/chart.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Pelaporan & Analisis</h1>
                
                <p class="mb-6 text-gray-700">Halaman ini menyediakan berbagai laporan dan analisis untuk membantu manajer dalam pengambilan keputusan.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Penjualan Bulan Ini</h2>
                        <p class="text-3xl font-bold text-green-600">Rp <?php echo number_format($total_sales_this_month, 0, ',', '.'); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-700 mb-2">Produk Terlaris (Qty)</h2>
                        <ul class="list-disc list-inside text-gray-700">
                            <?php if (!empty($top_products)): ?>
                                <?php foreach ($top_products as $product): ?>
                                    <li><?php echo htmlspecialchars($product['product_name']); ?>: <?php echo htmlspecialchars($product['total_qty_sold']); ?> Pcs</li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>Belum ada data produk terlaris.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-700 mb-2">Efisiensi Produksi</h2>
                        <p class="text-3xl font-bold text-blue-600"><?php echo number_format($production_efficiency, 2); ?>%</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Tren Penjualan Bulanan</h2>
                    <canvas id="monthlySalesChart" class="w-full h-96"></canvas>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Laporan Stok Bahan Baku</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bahan Baku</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stok Saat Ini</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Min. Stok</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $raw_materials_stock = $pdo->query("SELECT material_name, current_stock_qty, unit_of_measure, min_stock_level FROM raw_materials ORDER BY material_name ASC")->fetchAll(PDO::FETCH_ASSOC);
                                    if (!empty($raw_materials_stock)): ?>
                                    <?php foreach ($raw_materials_stock as $material): ?>
                                        <tr>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($material['material_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($material['current_stock_qty']) . ' ' . htmlspecialchars($material['unit_of_measure']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($material['min_stock_level']) . ' ' . htmlspecialchars($material['unit_of_measure']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php
                                                    if ($material['current_stock_qty'] <= $material['min_stock_level']) {
                                                        echo 'text-red-900 bg-red-200';
                                                    } else if ($material['current_stock_qty'] <= $material['min_stock_level'] * 1.5) { // Misalnya 150% dari min stock
                                                        echo 'text-yellow-900 bg-yellow-200';
                                                    } else {
                                                        echo 'text-green-900 bg-green-200';
                                                    }
                                                ?> rounded-full">
                                                    <?php echo ($material['current_stock_qty'] <= $material['min_stock_level']) ? 'Kritis' : (($material['current_stock_qty'] <= $material['min_stock_level'] * 1.5) ? 'Rendah' : 'Aman'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Belum ada data bahan baku.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>

    <script>
        // Contoh Chart.js untuk Tren Penjualan Bulanan
        document.addEventListener('DOMContentLoaded', function() {
            // Data dummy (gantilah dengan data dari PHP/AJAX)
            const monthlySalesData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Total Penjualan (Rp Juta)',
                    data: [15, 20, 18, 25, 22, 30], // Contoh data
                    borderColor: 'rgb(59, 130, 246)', // Tailwind blue-500
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            };

            const ctx = document.getElementById('monthlySalesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: monthlySalesData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false,
                            text: 'Tren Penjualan Bulanan'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>