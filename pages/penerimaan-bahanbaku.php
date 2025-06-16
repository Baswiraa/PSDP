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
    $supplier_id = $_POST['supplier_id'] ?? '';
    $material_id = $_POST['material_id'] ?? '';
    $quantity_received = $_POST['quantity_received'] ?? 0;
    $unit_cost = $_POST['unit_cost'] ?? 0.00;
    $batch_number = trim($_POST['batch_number'] ?? '');
    $receipt_date = date('Y-m-d');

    if (empty($supplier_id) || empty($material_id) || $quantity_received <= 0) {
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4' role='alert'>Harap lengkapi semua field yang wajib diisi.</div>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO material_receipts (supplier_id, material_id, receipt_date, quantity_received, unit_cost, batch_number, received_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Error mempersiapkan statement: " . $conn->error);
            }
            $stmt->bind_param("iisddsi", $supplier_id, $material_id, $receipt_date, $quantity_received, $unit_cost, $batch_number, $current_user_id);
            $stmt->execute();
            $stmt->close();
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Penerimaan bahan baku berhasil dicatat!</div>";
        } catch (Exception $e) {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Data untuk dropdown
$suppliers = [];
$result_suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name ASC");
if ($result_suppliers) {
    while ($row = $result_suppliers->fetch_assoc()) {
        $suppliers[] = $row;
    }
    $result_suppliers->free();
}

$materials = [];
$result_materials = $conn->query("SELECT material_id, material_name, unit_of_measure FROM raw_materials ORDER BY material_name ASC");
if ($result_materials) {
    while ($row = $result_materials->fetch_assoc()) {
        $materials[] = $row;
    }
    $result_materials->free();
}

// Data untuk tabel
$latest_receipts = [];
$sql_latest_receipts = "SELECT mr.*, s.supplier_name, rm.material_name, rm.unit_of_measure, u.username as received_by_user
                        FROM material_receipts mr
                        JOIN suppliers s ON mr.supplier_id = s.supplier_id
                        JOIN raw_materials rm ON mr.material_id = rm.material_id
                        LEFT JOIN users u ON mr.received_by = u.user_id
                        ORDER BY mr.receipt_date DESC, mr.receipt_id DESC
                        LIMIT 10";
$result_latest = $conn->query($sql_latest_receipts);
if ($result_latest) {
    while ($row = $result_latest->fetch_assoc()) {
        $latest_receipts[] = $row;
    }
    $result_latest->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penerimaan Bahan Baku - SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Penerimaan Bahan Baku</h1>
                <?php echo $message; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Form Penerimaan Baru</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="supplier_id" class="block text-gray-700 text-sm font-bold mb-2">Pemasok <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="supplier_id" name="supplier_id" required>
                                    <option value="">-- Pilih Pemasok --</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo htmlspecialchars($supplier['supplier_id']); ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="material_id" class="block text-gray-700 text-sm font-bold mb-2">Bahan Baku <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="material_id" name="material_id" required>
                                    <option value="">-- Pilih Bahan Baku --</option>
                                    <?php foreach ($materials as $material): ?>
                                        <option value="<?php echo htmlspecialchars($material['material_id']); ?>"><?php echo htmlspecialchars($material['material_name']); ?> (<?php echo htmlspecialchars($material['unit_of_measure']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="quantity_received" class="block text-gray-700 text-sm font-bold mb-2">Kuantitas Diterima <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity_received" name="quantity_received" min="0.01" required>
                            </div>
                            <div>
                                <label for="unit_cost" class="block text-gray-700 text-sm font-bold mb-2">Biaya per Unit</label>
                                <input type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="unit_cost" name="unit_cost" min="0">
                            </div>
                            <div class="md:col-span-2">
                                <label for="batch_number" class="block text-gray-700 text-sm font-bold mb-2">Nomor Batch (Opsional)</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="batch_number" name="batch_number">
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Catat Penerimaan
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Penerimaan Bahan Baku Terbaru</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Terima</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pemasok</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bahan Baku</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kuantitas</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Biaya Unit</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Batch</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($latest_receipts)): ?>
                                    <?php foreach ($latest_receipts as $receipt): ?>
                                        <tr>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($receipt['receipt_id']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($receipt['supplier_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($receipt['material_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($receipt['quantity_received']) . ' ' . htmlspecialchars($receipt['unit_of_measure']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap">Rp <?php echo number_format($receipt['unit_cost'], 0, ',', '.'); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($receipt['batch_number'] ?? '-'); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($receipt['receipt_date']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($receipt['received_by_user'] ?? 'N/A'); ?></p></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Belum ada data penerimaan bahan baku.</td></tr>
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