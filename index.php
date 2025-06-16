<?php
require_once 'includes/functions.php';
$current_role = get_user_role();

require_once 'config/database.php'; // Koneksi MySQLi

// --- Ambil Data Ringkasan Langsung dari PHP ---

// Data untuk Pesanan Sales
$total_orders = 0;
$pending_orders = 0;
$result_sales = $conn->query("SELECT
                                (SELECT COUNT(*) FROM sales_orders) AS total_orders,
                                (SELECT COUNT(*) FROM sales_orders WHERE status = 'Pending') AS pending_orders");
if ($result_sales) {
    $data = $result_sales->fetch_assoc();
    $total_orders = $data['total_orders'];
    $pending_orders = $data['pending_orders'];
    $result_sales->free();
}

// Data untuk Bahan Baku (Inventaris)
$total_bahan_baku_qty = 0; // Menggunakan qty, bukan meter spesifik
$critical_stock_count = 0;
$result_materials = $conn->query("SELECT
                                  SUM(current_stock_qty) AS total_qty,
                                  COUNT(CASE WHEN current_stock_qty <= min_stock_level THEN 1 END) AS critical_count
                                  FROM raw_materials");
if ($result_materials) {
    $data = $result_materials->fetch_assoc();
    $total_bahan_baku_qty = $data['total_qty'] ?? 0;
    $critical_stock_count = $data['critical_count'] ?? 0;
    $result_materials->free();
}

// Data untuk Produksi Terencana
$total_planned_items = 0;
$result_planned = $conn->query("SELECT SUM(planned_quantity) as total_planned FROM production_plans WHERE status IN ('Planned', 'In Progress')");
if ($result_planned) {
    $data = $result_planned->fetch_assoc();
    $total_planned_items = $data['total_planned'] ?? 0;
    $result_planned->free();
}

// Data untuk Proses Produksi
$in_progress_count = 0;
$result_in_progress = $conn->query("SELECT COUNT(*) AS in_progress FROM production_processes WHERE status = 'Ongoing'");
if ($result_in_progress) {
    $data = $result_in_progress->fetch_assoc();
    $in_progress_count = $data['in_progress'] ?? 0;
    $result_in_progress->free();
}

// Data untuk Pemeriksaan Kualitas
$total_inspected_qc = 0;
$total_defective_qc = 0;
$defect_rate_qc = 0;
$result_qc = $conn->query("SELECT SUM(quantity_inspected) AS total_inspected, SUM(quantity_defective) AS total_defective FROM quality_inspections");
if ($result_qc) {
    $data = $result_qc->fetch_assoc();
    $total_inspected_qc = $data['total_inspected'] ?? 0;
    $total_defective_qc = $data['total_defective'] ?? 0;
    if ($total_inspected_qc > 0) {
        $defect_rate_qc = ($total_defective_qc / $total_inspected_qc) * 100;
    }
    $result_qc->free();
}

// Data untuk Produk Jadi (Pemesanan Produk Jadi / Stok Akhir)
$total_finished_goods = 0;
$result_finished_goods = $conn->query("SELECT SUM(stock_qty) AS total_stock FROM products");
if ($result_finished_goods) {
    $data = $result_finished_goods->fetch_assoc();
    $total_finished_goods = $data['total_stock'] ?? 0;
    $result_finished_goods->free();
}

// Data untuk Proses Pengiriman
$in_transit_count = 0;
$delivered_count = 0;
$result_shipments = $conn->query("SELECT 
                                  COUNT(CASE WHEN status = 'In Transit' THEN 1 END) AS in_transit,
                                  COUNT(CASE WHEN status = 'Delivered' THEN 1 END) AS delivered
                                  FROM shipments");
if ($result_shipments) {
    $data = $result_shipments->fetch_assoc();
    $in_transit_count = $data['in_transit'] ?? 0;
    $delivered_count = $data['delivered'] ?? 0;
    $result_shipments->free();
}


// --- Data untuk Tabel Pesanan Sales Terbaru (sama seperti di pesanan-sales.php) ---
$latest_orders = [];
$sql_latest_orders = "SELECT so.*, soi.quantity, soi.unit_price, p.product_name 
                      FROM sales_orders so
                      JOIN sales_order_items soi ON so.order_id = soi.order_id
                      JOIN products p ON soi.product_id = p.product_id
                      ORDER BY so.order_date DESC, so.order_id DESC
                      LIMIT 10";
$result_latest = $conn->query($sql_latest_orders);
if ($result_latest) {
    while ($row = $result_latest->fetch_assoc()) {
        $latest_orders[] = $row;
    }
    $result_latest->free();
}


// --- Data untuk Grafik Status Produksi (untuk Chart.js) ---
$production_status_data = [];
$result_prod_status = $conn->query("SELECT current_stage, COUNT(*) as count FROM production_processes GROUP BY current_stage");
if ($result_prod_status) {
    $labels = [];
    $values = [];
    while ($row = $result_prod_status->fetch_assoc()) {
        $labels[] = $row['current_stage'];
        $values[] = $row['count'];
    }
    $production_status_data = ['labels' => $labels, 'values' => $values];
    $result_prod_status->free();
}

// --- Data untuk Grafik Stok Bahan Baku Kritis (untuk Chart.js) ---
$critical_stock_data = [];
$result_critical_stock = $conn->query("SELECT material_name, current_stock_qty FROM raw_materials WHERE current_stock_qty <= min_stock_level ORDER BY current_stock_qty ASC LIMIT 5");
if ($result_critical_stock) {
    $labels = [];
    $values = [];
    while ($row = $result_critical_stock->fetch_assoc()) {
        $labels[] = $row['material_name'];
        $values[] = $row['current_stock_qty'];
    }
    $critical_stock_data = ['labels' => $labels, 'values' => $values];
    $result_critical_stock->free();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
    <script src="assets/lib/chart.js/chart.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard SCM</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-600 text-white p-6 rounded-lg shadow-md flex flex-col justify-between">
                        <div>
                            <h5 class="text-xl font-semibold mb-2"><i class="fas fa-shopping-cart mr-2"></i> Pesanan Sales</h5>
                            <p class="text-3xl font-bold">Total: <?php echo htmlspecialchars($total_orders); ?></p>
                            <p class="text-lg">Pending: <span class="font-bold text-blue-200"><?php echo htmlspecialchars($pending_orders); ?></span></p>
                        </div>
                        <a href="pages/pesanan-sales.php" class="mt-4 text-white text-sm font-medium hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="bg-teal-600 text-white p-6 rounded-lg shadow-md flex flex-col justify-between">
                        <div>
                            <h5 class="text-xl font-semibold mb-2"><i class="fas fa-truck-loading mr-2"></i> Bahan Baku</h5>
                            <p class="text-3xl font-bold">Tersedia: <?php echo number_format(htmlspecialchars($total_bahan_baku_qty), 0, ',', '.'); ?> Unit</p>
                            <p class="text-lg">Kritis: <span class="font-bold text-red-200"><?php echo htmlspecialchars($critical_stock_count); ?> Jenis</span></p>
                        </div>
                        <a href="pages/penerimaan-bahanbaku.php" class="mt-4 text-white text-sm font-medium hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="bg-yellow-500 text-white p-6 rounded-lg shadow-md flex flex-col justify-between">
                        <div>
                            <h5 class="text-xl font-semibold mb-2"><i class="fas fa-clipboard-list mr-2"></i> Produksi Terencana</h5>
                            <p class="text-3xl font-bold">Item: <?php echo htmlspecialchars($total_planned_items); ?></p>
                        </div>
                        <a href="pages/perencanaan-produksi.php" class="mt-4 text-white text-sm font-medium hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="bg-green-600 text-white p-6 rounded-lg shadow-md flex flex-col justify-between">
                        <div>
                            <h5 class="text-xl font-semibold mb-2"><i class="fas fa-industry mr-2"></i> Proses Produksi</h5>
                            <p class="text-3xl font-bold">Berjalan: <?php echo htmlspecialchars($in_progress_count); ?></p>
                        </div>
                        <a href="pages/detail-proses-produksi.php" class="mt-4 text-white text-sm font-medium hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="bg-red-600 text-white p-6 rounded-lg shadow-md flex flex-col justify-between">
                        <div>
                            <h5 class="text-xl font-semibold mb-2"><i class="fas fa-check-circle mr-2"></i> Pemeriksaan Kualitas</h5>
                            <p class="text-3xl font-bold">Cacat: <?php echo htmlspecialchars($total_defective_qc); ?></p>
                            <p class="text-lg">Ratio: <span class="font-bold text-red-200"><?php echo number_format($defect_rate_qc, 2); ?>%</span></p>
                        </div>
                        <a href="pages/pemeriksaan-kualitas.php" class="mt-4 text-white text-sm font-medium hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="bg-gray-700 text-white p-6 rounded-lg shadow-md flex flex-col justify-between">
                        <div>
                            <h5 class="text-xl font-semibold mb-2"><i class="fas fa-box mr-2"></i> Produk Jadi</h5>
                            <p class="text-3xl font-bold">Stok: <?php echo htmlspecialchars($total_finished_goods); ?></p>
                        </div>
                        <a href="pages/pemesanan-produk.php" class="mt-4 text-white text-sm font-medium hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <div class="bg-purple-600 text-white p-6 rounded-lg shadow-md flex flex-col justify-between">
                        <div>
                            <h5 class="text-xl font-semibold mb-2"><i class="fas fa-shipping-fast mr-2"></i> Pengiriman</h5>
                            <p class="text-3xl font-bold">Dikirim: <?php echo htmlspecialchars($in_transit_count); ?></p>
                            <p class="text-lg">Terkirim: <span class="font-bold text-purple-200"><?php echo htmlspecialchars($delivered_count); ?></span></p>
                        </div>
                        <a href="pages/proses-pengiriman.php" class="mt-4 text-white text-sm font-medium hover:underline">Lihat Detail &rarr;</a>
                    </div>
                    <?php if (has_access(['manager'])): ?>
                    <div class="bg-blue-800 text-white p-6 rounded-lg shadow-md flex flex-col justify-between">
                        <div>
                            <h5 class="text-xl font-semibold mb-2"><i class="fas fa-chart-line mr-2"></i> Pelaporan & Analisis</h5>
                            <p class="text-3xl font-bold">Akses Penuh</p>
                        </div>
                        <a href="pages/pelaporan-analisis.php" class="mt-4 text-white text-sm font-medium hover:underline">Akses Laporan &rarr;</a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Status Produksi Berjalan</h2>
                        <canvas id="productionStatusChart" class="w-full h-80"></canvas>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Level Stok Bahan Baku Kritis</h2>
                        <canvas id="criticalStockChart" class="w-full h-80"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Pesanan Sales Terbaru</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Pesanan</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pelanggan</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produk</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jumlah</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody id="latestOrdersTableBody">
                                <?php if (!empty($latest_orders)): ?>
                                    <?php foreach ($latest_orders as $order): ?>
                                        <tr>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['order_id']); ?></p>
                                            </td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                            </td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['product_name']); ?></p>
                                            </td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['quantity']); ?></p>
                                            </td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php
                                                    switch ($order['status']) {
                                                        case 'Pending': echo 'text-yellow-900 bg-yellow-200 rounded-full'; break;
                                                        case 'In Production': echo 'text-blue-900 bg-blue-200 rounded-full'; break;
                                                        case 'Ready for Shipping': echo 'text-purple-900 bg-purple-200 rounded-full'; break;
                                                        case 'Shipped': echo 'text-teal-900 bg-teal-200 rounded-full'; break;
                                                        case 'Completed': echo 'text-green-900 bg-green-200 rounded-full'; break;
                                                        case 'Cancelled': echo 'text-red-900 bg-red-200 rounded-full'; break;
                                                        default: echo 'text-gray-900 bg-gray-200 rounded-full'; break;
                                                    }
                                                ?>">
                                                    <?php echo htmlspecialchars($order['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Tidak ada pesanan terbaru.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data untuk Chart.js dari PHP
            const productionStatusData = <?php echo json_encode($production_status_data); ?>;
            const criticalStockData = <?php echo json_encode($critical_stock_data); ?>;

            if (productionStatusData && productionStatusData.labels && productionStatusData.labels.length > 0) {
                const ctxProd = document.getElementById('productionStatusChart').getContext('2d');
                new Chart(ctxProd, {
                    type: 'pie',
                    data: {
                        labels: productionStatusData.labels,
                        datasets: [{
                            data: productionStatusData.values,
                            backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#0EA5E9', '#EF4444', '#6B7280'], // Tailwind colors
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: false,
                                text: 'Status Produksi Berjalan'
                            }
                        }
                    }
                });
            } else {
                 document.getElementById('productionStatusChart').innerText = 'Tidak ada data status produksi.';
            }

            if (criticalStockData && criticalStockData.labels && criticalStockData.labels.length > 0) {
                const ctxCritical = document.getElementById('criticalStockChart').getContext('2d');
                new Chart(ctxCritical, {
                    type: 'bar',
                    data: {
                        labels: criticalStockData.labels,
                        datasets: [{
                            label: 'Stok Kritis',
                            data: criticalStockData.values,
                            backgroundColor: 'rgba(239, 68, 68, 0.6)', // Tailwind red-500 with transparency
                            borderColor: 'rgb(239, 68, 68)', // Tailwind red-500
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: false,
                                text: 'Level Stok Bahan Baku Kritis'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                document.getElementById('criticalStockChart').innerText = 'Tidak ada data stok kritis.';
            }
        });
    </script>
</body>
</html>