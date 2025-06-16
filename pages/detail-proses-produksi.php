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
    $plan_id = $_POST['plan_id'] ?? null;
    $product_id = $_POST['product_id'] ?? '';
    $batch_id = trim($_POST['batch_id'] ?? '');
    $quantity_in_process = $_POST['quantity_in_process'] ?? 0;
    $current_stage = $_POST['current_stage'] ?? '';
    $status = $_POST['status'] ?? 'Ongoing';
    $assigned_to = $_POST['assigned_to'] ?? $current_user_id;

    if (empty($product_id) || empty($quantity_in_process) || empty($current_stage)) {
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4' role='alert'>Harap lengkapi semua field yang wajib diisi.</div>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO production_processes (plan_id, product_id, batch_id, quantity_in_process, current_stage, assigned_to, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Error mempersiapkan statement: " . $conn->error);
            }
            // Perhatikan tipe data: i, i, s, i, s, i, s
            $stmt->bind_param("iisissis", $plan_id, $product_id, $batch_id, $quantity_in_process, $current_stage, $assigned_to, $status);
            $stmt->execute();
            $stmt->close();
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Proses produksi berhasil dicatat!</div>";
        } catch (Exception $e) {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Data untuk dropdown
$products = [];
$result_products = $conn->query("SELECT product_id, product_name FROM products ORDER BY product_name ASC");
if ($result_products) {
    while ($row = $result_products->fetch_assoc()) { $products[] = $row; }
    $result_products->free();
}

$plans = [];
$result_plans = $conn->query("SELECT plan_id, plan_name FROM production_plans WHERE status IN ('Planned', 'In Progress') ORDER BY plan_name ASC");
if ($result_plans) {
    while ($row = $result_plans->fetch_assoc()) { $plans[] = $row; }
    $result_plans->free();
}

$users = [];
$result_users = $conn->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) { $users[] = $row; }
    $result_users->free();
}

// Data untuk tabel
$latest_processes = [];
$sql_latest_processes = "SELECT pp.*, p.product_name, u.full_name as assigned_to_user, pp2.plan_name
                        FROM production_processes pp
                        JOIN products p ON pp.product_id = p.product_id
                        LEFT JOIN users u ON pp.assigned_to = u.user_id
                        LEFT JOIN production_plans pp2 ON pp.plan_id = pp2.plan_id
                        ORDER BY pp.created_at DESC
                        LIMIT 10";
$result_latest = $conn->query($sql_latest_processes);
if ($result_latest) {
    while ($row = $result_latest->fetch_assoc()) { $latest_processes[] = $row; }
    $result_latest->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Proses Produksi - SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Detail Proses Produksi</h1>
                <?php echo $message; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Catat Proses Baru</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="product_id" class="block text-gray-700 text-sm font-bold mb-2">Produk <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="product_id" name="product_id" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo htmlspecialchars($product['product_id']); ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="batch_id" class="block text-gray-700 text-sm font-bold mb-2">ID Batch</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="batch_id" name="batch_id">
                            </div>
                            <div>
                                <label for="quantity_in_process" class="block text-gray-700 text-sm font-bold mb-2">Kuantitas <span class="text-red-500">*</span></label>
                                <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity_in_process" name="quantity_in_process" min="1" required>
                            </div>
                            <div>
                                <label for="current_stage" class="block text-gray-700 text-sm font-bold mb-2">Tahap Saat Ini <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="current_stage" name="current_stage" required>
                                    <option value="">-- Pilih Tahap --</option>
                                    <option value="Cutting">Cutting</option>
                                    <option value="Sewing">Sewing</option>
                                    <option value="Finishing">Finishing</option>
                                    <option value="Quality Control">Quality Control</option>
                                    <option value="Packaging">Packaging</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div>
                                <label for="assigned_to" class="block text-gray-700 text-sm font-bold mb-2">Ditugaskan Kepada</label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="assigned_to" name="assigned_to">
                                    <option value="">-- Pilih Karyawan/Tim --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo htmlspecialchars($user['user_id']); ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="plan_id" class="block text-gray-700 text-sm font-bold mb-2">Terhubung ke Rencana Produksi (Opsional)</label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="plan_id" name="plan_id">
                                    <option value="">-- Tidak Terhubung --</option>
                                    <?php foreach ($plans as $plan): ?>
                                        <option value="<?php echo htmlspecialchars($plan['plan_id']); ?>"><?php echo htmlspecialchars($plan['plan_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Catat Proses
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Proses Produksi Terbaru</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Proses</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produk</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Batch ID</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kuantitas</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tahap</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ditugaskan</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Mulai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($latest_processes)): ?>
                                    <?php foreach ($latest_processes as $process): ?>
                                        <tr>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($process['process_id']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($process['product_name']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($process['batch_id'] ?? '-'); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($process['quantity_in_process']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($process['current_stage']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php
                                                    switch ($process['status']) {
                                                        case 'Ongoing': echo 'text-blue-900 bg-blue-200'; break;
                                                        case 'On Hold': echo 'text-yellow-900 bg-yellow-200'; break;
                                                        case 'Finished': echo 'text-green-900 bg-green-200'; break;
                                                        case 'Aborted': echo 'text-red-900 bg-red-200'; break;
                                                        default: echo 'text-gray-900 bg-gray-200'; break;
                                                    }
                                                ?> rounded-full">
                                                    <?php echo htmlspecialchars($process['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($process['assigned_to_user'] ?? 'N/A'); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($process['start_time']))); ?></p></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Belum ada data proses produksi.</td></tr>
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