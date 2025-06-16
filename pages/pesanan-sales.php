<?php
require_once '../includes/functions.php';
check_login();
if (!has_access(['manager', 'karyawan'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php'; // Koneksi MySQLi

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $product_id = $_POST['product_id'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $unit_price = $_POST['unit_price'] ?? 0.00;
    $order_date = date('Y-m-d');

    if (empty($customer_name) || empty($product_id) || $quantity <= 0 || $unit_price <= 0) {
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4' role='alert'>Harap lengkapi semua field yang wajib diisi dan pastikan kuantitas/harga valid.</div>";
    } else {
        // Mulai transaksi database
        $conn->begin_transaction();

        try {
            // 1. Masukkan data ke tabel sales_orders
            $stmt_order = $conn->prepare("INSERT INTO sales_orders (customer_name, customer_email, customer_phone, order_date, delivery_address, status, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_order === false) {
                throw new Exception("Error mempersiapkan statement order: " . $conn->error);
            }
            $total_amount = $quantity * $unit_price;
            $status = 'Pending';
            $stmt_order->bind_param("ssssssd", $customer_name, $customer_email, $customer_phone, $order_date, $delivery_address, $status, $total_amount);
            $stmt_order->execute();
            $order_id = $conn->insert_id; // Dapatkan ID pesanan yang baru saja di-insert
            $stmt_order->close();

            // 2. Masukkan data ke tabel sales_order_items
            $stmt_item = $conn->prepare("INSERT INTO sales_order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            if ($stmt_item === false) {
                throw new Exception("Error mempersiapkan statement item: " . $conn->error);
            }
            $stmt_item->bind_param("iiid", $order_id, $product_id, $quantity, $unit_price);
            $stmt_item->execute();
            $stmt_item->close();

            // Commit transaksi jika semua berhasil
            $conn->commit();
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Pesanan sales berhasil ditambahkan dengan ID: " . htmlspecialchars($order_id) . "!</div>";

        } catch (Exception $e) {
            // Rollback transaksi jika ada error
            $conn->rollback();
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Ambil data produk untuk dropdown
$products = [];
$result_products = $conn->query("SELECT product_id, product_name, price FROM products ORDER BY product_name ASC");
if ($result_products) {
    while ($row = $result_products->fetch_assoc()) {
        $products[] = $row;
    }
    $result_products->free();
} else {
    // Handle error fetching products
}

// Ambil data pesanan sales terbaru untuk ditampilkan di tabel
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
} else {
    // Handle error fetching latest orders
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Sales - SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Input Pesanan Sales</h1>
                <?php echo $message; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Form Pesanan Baru</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="customer_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Pelanggan <span class="text-red-500">*</span></label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="customer_name" name="customer_name" required>
                            </div>
                            <div>
                                <label for="customer_email" class="block text-gray-700 text-sm font-bold mb-2">Email Pelanggan</label>
                                <input type="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="customer_email" name="customer_email">
                            </div>
                            <div>
                                <label for="customer_phone" class="block text-gray-700 text-sm font-bold mb-2">Telepon Pelanggan</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="customer_phone" name="customer_phone">
                            </div>
                            <div class="md:col-span-2">
                                <label for="delivery_address" class="block text-gray-700 text-sm font-bold mb-2">Alamat Pengiriman</label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="delivery_address" name="delivery_address" rows="3"></textarea>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Detail Produk Pesanan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label for="product_id" class="block text-gray-700 text-sm font-bold mb-2">Pilih Produk <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="product_id" name="product_id" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo htmlspecialchars($product['product_id']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>">
                                            <?php echo htmlspecialchars($product['product_name']); ?> (Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Jumlah <span class="text-red-500">*</span></label>
                                <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity" name="quantity" min="1" value="1" required>
                            </div>
                            <div>
                                <label for="unit_price" class="block text-gray-700 text-sm font-bold mb-2">Harga per Unit <span class="text-red-500">*</span></label>
                                <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="unit_price" name="unit_price" step="0.01" min="0.01" required readonly>
                                <p class="text-xs text-gray-500 mt-1">Harga akan otomatis terisi dari pilihan produk.</p>
                            </div>
                        </div>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Simpan Pesanan
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Pesanan Sales Terbaru</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Pesanan</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pelanggan</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produk</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jumlah</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($latest_orders)): ?>
                                    <?php foreach ($latest_orders as $order): ?>
                                        <tr>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['order_id']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['customer_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['product_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['quantity']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($order['order_date']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php
                                                    switch ($order['status']) {
                                                        case 'Pending': echo 'text-yellow-900 bg-yellow-200'; break;
                                                        case 'Confirmed': echo 'text-blue-900 bg-blue-200'; break;
                                                        case 'In Production': echo 'text-purple-900 bg-purple-200'; break;
                                                        case 'Ready for Shipping': echo 'text-indigo-900 bg-indigo-200'; break;
                                                        case 'Shipped': echo 'text-teal-900 bg-teal-200'; break;
                                                        case 'Completed': echo 'text-green-900 bg-green-200'; break;
                                                        case 'Cancelled': echo 'text-red-900 bg-red-200'; break;
                                                        default: echo 'text-gray-900 bg-gray-200'; break;
                                                    }
                                                ?> rounded-full">
                                                    <?php echo htmlspecialchars($order['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Belum ada data pesanan.</td></tr>
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
        document.getElementById('product_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const unitPriceInput = document.getElementById('unit_price');
            const price = selectedOption.getAttribute('data-price');
            unitPriceInput.value = price ? parseFloat(price).toFixed(2) : '0.00';
        });
    </script>
</body>
</html>