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
    $order_id = $_POST['order_id'] ?? '';
    $shipping_date = $_POST['shipping_date'] ?? date('Y-m-d');
    $carrier = trim($_POST['carrier'] ?? '');
    $tracking_number = trim($_POST['tracking_number'] ?? '');
    $status = $_POST['status'] ?? 'Pending';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($order_id) || empty($carrier) || empty($tracking_number) || empty($status)) {
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4' role='alert'>Harap lengkapi semua field yang wajib diisi.</div>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO shipments (order_id, shipping_date, carrier, tracking_number, status, shipped_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Error mempersiapkan statement: " . $conn->error);
            }
            $stmt->bind_param("issssis", $order_id, $shipping_date, $carrier, $tracking_number, $status, $current_user_id, $notes);
            $stmt->execute();
            $stmt->close();
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Pengiriman berhasil dicatat!</div>";
        } catch (Exception $e) {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Data untuk dropdown (pesanan sales yang siap kirim)
$sales_orders = [];
$result_orders = $conn->query("SELECT order_id, customer_name FROM sales_orders WHERE status IN ('Ready for Shipping', 'Confirmed') ORDER BY order_id DESC");
if ($result_orders) {
    while ($row = $result_orders->fetch_assoc()) { $sales_orders[] = $row; }
    $result_orders->free();
}

// Data untuk tabel
$latest_shipments = [];
$sql_latest_shipments = "SELECT s.*, so.customer_name, so.delivery_address, u.full_name as shipped_by_user
                        FROM shipments s
                        JOIN sales_orders so ON s.order_id = so.order_id
                        LEFT JOIN users u ON s.shipped_by = u.user_id
                        ORDER BY s.shipping_date DESC, s.shipment_id DESC
                        LIMIT 10";
$result_latest = $conn->query($sql_latest_shipments);
if ($result_latest) {
    while ($row = $result_latest->fetch_assoc()) { $latest_shipments[] = $row; }
    $result_latest->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pengiriman - SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Proses Pengiriman</h1>
                <?php echo $message; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Catat Pengiriman Baru</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="order_id" class="block text-gray-700 text-sm font-bold mb-2">Pesanan Sales <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="order_id" name="order_id" required>
                                    <option value="">-- Pilih Pesanan --</option>
                                    <?php foreach ($sales_orders as $order): ?>
                                        <option value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                            ID: <?php echo htmlspecialchars($order['order_id']); ?> - Pelanggan: <?php echo htmlspecialchars($order['customer_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="shipping_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Pengiriman <span class="text-red-500">*</span></label>
                                <input type="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="shipping_date" name="shipping_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div>
                                <label for="carrier" class="block text-gray-700 text-sm font-bold mb-2">Kurir / Ekspedisi <span class="text-red-500">*</span></label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="carrier" name="carrier" required>
                            </div>
                            <div>
                                <label for="tracking_number" class="block text-gray-700 text-sm font-bold mb-2">Nomor Resi / Pelacakan <span class="text-red-500">*</span></label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="tracking_number" name="tracking_number" required>
                            </div>
                            <div class="md:col-span-2">
                                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status Pengiriman <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="status" name="status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="In Transit">Dalam Perjalanan</option>
                                    <option value="Delivered">Terkirim</option>
                                    <option value="Failed">Gagal</option>
                                    <option value="Returned">Dikembalikan</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Catatan</label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-