<?php
require_once '../includes/functions.php';
check_login();
if (!has_access(['manager', 'karyawan'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php'; // Koneksi MySQLi

$message = '';
$current_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? '';
    $movement_type = $_POST['movement_type'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $source_location = trim($_POST['source_location'] ?? '');
    $destination_location = trim($_POST['destination_location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($product_id) || empty($movement_type) || $quantity <= 0) {
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4' role='alert'>Harap lengkapi semua field yang wajib diisi.</div>";
    } else {
        try {
            $conn->begin_transaction();

            if ($movement_type == 'Outbound to Shipping') {
                $stmt_check_stock = $conn->prepare("SELECT stock_qty FROM products WHERE product_id = ?");
                if ($stmt_check_stock === false) {
                    throw new Exception("Error mempersiapkan cek stok: " . $conn->error);
                }
                $stmt_check_stock->bind_param("i", $product_id);
                $stmt_check_stock->execute();
                $result_check_stock = $stmt_check_stock->get_result();
                $current_stock = $result_check_stock->fetch_assoc()['stock_qty'];
                $stmt_check_stock->close();

                if ($current_stock < $quantity) {
                    throw new Exception("Stok produk tidak mencukupi untuk pergerakan outbound ini. Stok tersedia: " . $current_stock);
                }
            }

            $stmt = $conn->prepare("INSERT INTO finished_goods_movement (product_id, movement_type, quantity, source_location, destination_location, notes, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Error mempersiapkan statement pergerakan: " . $conn->error);
            }
            $stmt->bind_param("isissis", $product_id, $movement_type, $quantity, $source_location, $destination_location, $notes, $current_user_id);
            $stmt->execute();
            $stmt->close();
            
            // Trigger di DB sudah menangani update stock, jadi tidak perlu UPDATE di sini

            $conn->commit();
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Pergerakan produk jadi berhasil dicatat!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Data untuk dropdown (produk)
$products = [];
$result_products = $conn->query("SELECT product_id, product_name, stock_qty FROM products ORDER BY product_name ASC");
if ($result_products) {
    while ($row = $result_products->fetch_assoc()) { $products[] = $row; }
    $result_products->free();
}

// Data untuk tabel
$latest_movements = [];
$sql_latest_movements = "SELECT fgm.*, p.product_name, u.full_name as processed_by_user
                        FROM finished_goods_movement fgm
                        JOIN products p ON fgm.product_id = p.product_id
                        LEFT JOIN users u ON fgm.processed_by = u.user_id
                        ORDER BY fgm.movement_date DESC
                        LIMIT 10";
$result_latest = $conn->query($sql_latest_movements);
if ($result_latest) {
    while ($row = $result_latest->fetch_assoc()) { $latest_movements[] = $row; }
    $result_latest->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Produk Jadi - SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Pemesanan Produk Jadi (Pergerakan Stok)</h1>
                <?php echo $message; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Catat Pergerakan Produk Jadi</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="product_id" class="block text-gray-700 text-sm font-bold mb-2">Produk Jadi <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="product_id" name="product_id" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                            <?php echo htmlspecialchars($product['product_name']); ?> (Stok: <?php echo htmlspecialchars($product['stock_qty']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="movement_type" class="block text-gray-700 text-sm font-bold mb-2">Jenis Pergerakan <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="movement_type" name="movement_type" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="Inbound from Production">Masuk dari Produksi</option>
                                    <option value="Outbound to Shipping">Keluar ke Pengiriman</option>
                                    <option value="Adjustment">Penyesuaian (In/Out)</option>
                                </select>
                            </div>
                            <div>
                                <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Kuantitas <span class="text-red-500">*</span></label>
                                <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity" name="quantity" min="1" required>
                            </div>
                            <div>
                                <label for="source_location" class="block text-gray-700 text-sm font-bold mb-2">Lokasi Asal (Opsional)</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="source_location" name="source_location">
                            </div>
                            <div>
                                <label for="destination_location" class="block text-gray-700 text-sm font-bold mb-2">Lokasi Tujuan (Opsional)</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="destination_location" name="destination_location">
                            </div>
                            <div class="md:col-span-2">
                                <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Catatan</label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Catat Pergerakan
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Riwayat Pergerakan Produk Jadi</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Gerak</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produk</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jenis</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kuantitas</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Asal</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tujuan</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($latest_movements)): ?>
                                    <?php foreach ($latest_movements as $movement): ?>
                                        <tr>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($movement['movement_id']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($movement['product_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($movement['movement_type']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($movement['quantity']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($movement['source_location'] ?? '-'); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($movement['destination_location'] ?? '-'); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($movement['movement_date']))); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($movement['processed_by_user'] ?? 'N/A'); ?></p></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Belum ada data pergerakan produk jadi.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>