<?php
require_once '../includes/functions.php';
check_login();
if (!has_access(['manager'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php'; // Koneksi MySQLi

$message = '';
$current_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_name = trim($_POST['plan_name'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $planned_product_id = $_POST['planned_product_id'] ?? '';
    $planned_quantity = $_POST['planned_quantity'] ?? 0;
    $notes = trim($_POST['notes'] ?? '');

    if (empty($plan_name) || empty($start_date) || empty($end_date) || empty($planned_product_id) || $planned_quantity <= 0) {
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4' role='alert'>Harap lengkapi semua field yang wajib diisi.</div>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO production_plans (plan_name, start_date, end_date, planned_product_id, planned_quantity, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Error mempersiapkan statement: " . $conn->error);
            }
            $status = 'Planned'; // Status default
            $stmt->bind_param("sssisis", $plan_name, $start_date, $end_date, $planned_product_id, $planned_quantity, $status, $notes, $current_user_id);
            $stmt->execute();
            $stmt->close();
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Perencanaan produksi berhasil ditambahkan!</div>";
        } catch (Exception $e) {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Data untuk dropdown (produk)
$products = [];
$result_products = $conn->query("SELECT product_id, product_name FROM products ORDER BY product_name ASC");
if ($result_products) {
    while ($row = $result_products->fetch_assoc()) {
        $products[] = $row;
    }
    $result_products->free();
}

// Data untuk tabel
$latest_plans = [];
$sql_latest_plans = "SELECT pp.*, p.product_name, u.full_name as created_by_user
                     FROM production_plans pp
                     JOIN products p ON pp.planned_product_id = p.product_id
                     LEFT JOIN users u ON pp.created_by = u.user_id
                     ORDER BY pp.created_at DESC
                     LIMIT 10";
$result_latest = $conn->query($sql_latest_plans);
if ($result_latest) {
    while ($row = $result_latest->fetch_assoc()) {
        $latest_plans[] = $row;
    }
    $result_latest->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perencanaan Produksi - SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Perencanaan Produksi</h1>
                <?php echo $message; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Form Perencanaan Baru</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="plan_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Rencana <span class="text-red-500">*</span></label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="plan_name" name="plan_name" required>
                            </div>
                            <div>
                                <label for="planned_product_id" class="block text-gray-700 text-sm font-bold mb-2">Produk Terencana <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="planned_product_id" name="planned_product_id" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo htmlspecialchars($product['product_id']); ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Mulai <span class="text-red-500">*</span></label>
                                <input type="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="start_date" name="start_date" required>
                            </div>
                            <div>
                                <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Selesai <span class="text-red-500">*</span></label>
                                <input type="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="end_date" name="end_date" required>
                            </div>
                            <div>
                                <label for="planned_quantity" class="block text-gray-700 text-sm font-bold mb-2">Kuantitas Terencana <span class="text-red-500">*</span></label>
                                <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="planned_quantity" name="planned_quantity" min="1" required>
                            </div>
                            <div class="md:col-span-2">
                                <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Catatan</label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Simpan Rencana
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Rencana Produksi Terbaru</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Rencana</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Rencana</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produk</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kuantitas</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Mulai</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Selesai</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dibuat Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($latest_plans)): ?>
                                    <?php foreach ($latest_plans as $plan): ?>
                                        <tr>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($plan['plan_id']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($plan['plan_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($plan['product_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($plan['planned_quantity']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($plan['start_date']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($plan['end_date']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php
                                                    switch ($plan['status']) {
                                                        case 'Planned': echo 'text-yellow-900 bg-yellow-200'; break;
                                                        case 'In Progress': echo 'text-blue-900 bg-blue-200'; break;
                                                        case 'Completed': echo 'text-green-900 bg-green-200'; break;
                                                        case 'Cancelled': echo 'text-red-900 bg-red-200'; break;
                                                        default: echo 'text-gray-900 bg-gray-200'; break;
                                                    }
                                                ?> rounded-full">
                                                    <?php echo htmlspecialchars($plan['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($plan['created_by_user'] ?? 'N/A'); ?></p></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Belum ada data perencanaan produksi.</td></tr>
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