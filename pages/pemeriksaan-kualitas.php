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
    $process_id = $_POST['process_id'] ?? '';
    $quantity_inspected = $_POST['quantity_inspected'] ?? 0;
    $quantity_defective = $_POST['quantity_defective'] ?? 0;
    $defect_description = trim($_POST['defect_description'] ?? '');
    $overall_result = $_POST['overall_result'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($process_id) || $quantity_inspected <= 0 || empty($overall_result)) {
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4' role='alert'>Harap lengkapi semua field yang wajib diisi.</div>";
    } else {
        try {
            // Periksa apakah jumlah cacat tidak melebihi jumlah yang diperiksa
            if ($quantity_defective > $quantity_inspected) {
                throw new Exception("Jumlah cacat tidak boleh melebihi jumlah yang diperiksa.");
            }

            // MySQLi does not have a begin_transaction() equivalent in older versions,
            // but newer versions (PHP 5.5+ and MySQL 5.5+) support it.
            // If you face issues, you might need to use $conn->autocommit(FALSE); and $conn->commit();
            $conn->begin_transaction();

            $stmt = $conn->prepare("INSERT INTO quality_inspections (process_id, inspector_id, quantity_inspected, quantity_defective, defect_description, overall_result, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Error mempersiapkan statement: " . $conn->error);
            }
            $stmt->bind_param("iiiisss", $process_id, $current_user_id, $quantity_inspected, $quantity_defective, $defect_description, $overall_result, $notes);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaksi
            $conn->commit();
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Pemeriksaan kualitas berhasil dicatat!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Data untuk dropdown (proses produksi yang Ongoing/Finishing)
$production_processes = [];
$sql_processes = "SELECT pp.process_id, p.product_name, pp.batch_id, pp.current_stage, pp.quantity_in_process
                 FROM production_processes pp
                 JOIN products p ON pp.product_id = p.product_id
                 WHERE pp.status IN ('Ongoing', 'Finished') AND pp.current_stage IN ('Finishing', 'Quality Control', 'Packaging')
                 ORDER BY pp.created_at DESC";
$result_processes = $conn->query($sql_processes);
if ($result_processes) {
    while ($row = $result_processes->fetch_assoc()) { $production_processes[] = $row; }
    $result_processes->free();
}

// Data untuk tabel
$latest_inspections = [];
$sql_latest_inspections = "SELECT qi.*, pp.batch_id, p.product_name, u.full_name as inspector_name
                          FROM quality_inspections qi
                          JOIN production_processes pp ON qi.process_id = pp.process_id
                          JOIN products p ON pp.product_id = p.product_id
                          LEFT JOIN users u ON qi.inspector_id = u.user_id
                          ORDER BY qi.inspection_date DESC
                          LIMIT 10";
$result_latest = $conn->query($sql_latest_inspections);
if ($result_latest) {
    while ($row = $result_latest->fetch_assoc()) { $latest_inspections[] = $row; }
    $result_latest->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemeriksaan Kualitas - SCM Konveksi Kain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Pemeriksaan Kualitas</h1>
                <?php echo $message; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Form Pemeriksaan Kualitas</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="process_id" class="block text-gray-700 text-sm font-bold mb-2">Pilih Proses Produksi <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="process_id" name="process_id" required>
                                    <option value="">-- Pilih Proses --</option>
                                    <?php foreach ($production_processes as $process): ?>
                                        <option value="<?php echo htmlspecialchars($process['process_id']); ?>">
                                            <?php echo htmlspecialchars($process['product_name']); ?> (Batch: <?php echo htmlspecialchars($process['batch_id'] ?? '-'); ?> - Tahap: <?php echo htmlspecialchars($process['current_stage']); ?> - Qty: <?php echo htmlspecialchars($process['quantity_in_process']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="quantity_inspected" class="block text-gray-700 text-sm font-bold mb-2">Kuantitas Diperiksa <span class="text-red-500">*</span></label>
                                <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity_inspected" name="quantity_inspected" min="1" required>
                            </div>
                            <div>
                                <label for="quantity_defective" class="block text-gray-700 text-sm font-bold mb-2">Kuantitas Cacat</label>
                                <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity_defective" name="quantity_defective" min="0" value="0">
                            </div>
                            <div>
                                <label for="overall_result" class="block text-gray-700 text-sm font-bold mb-2">Hasil Keseluruhan <span class="text-red-500">*</span></label>
                                <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="overall_result" name="overall_result" required>
                                    <option value="">-- Pilih Hasil --</option>
                                    <option value="Passed">Lulus</option>
                                    <option value="Failed">Gagal</option>
                                    <option value="Rework Required">Perlu Perbaikan</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label for="defect_description" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Cacat (Jika ada)</label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="defect_description" name="defect_description" rows="2"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Catatan Tambahan</label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Simpan Hasil Kualitas
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Pemeriksaan Kualitas Terbaru</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Inspeksi</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produk (Batch)</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Diperiksa</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cacat</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Hasil</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Inspektur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($latest_inspections)): ?>
                                    <?php foreach ($latest_inspections as $inspection): ?>
                                        <tr>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($inspection['inspection_id']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($inspection['product_name']); ?> (<?php echo htmlspecialchars($inspection['batch_id'] ?? '-'); ?>)</p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($inspection['quantity_inspected']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($inspection['quantity_defective']); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php
                                                    switch ($inspection['overall_result']) {
                                                        case 'Passed': echo 'text-green-900 bg-green-200'; break;
                                                        case 'Failed': echo 'text-red-900 bg-red-200'; break;
                                                        case 'Rework Required': echo 'text-orange-900 bg-orange-200'; break;
                                                        default: echo 'text-gray-900 bg-gray-200'; break;
                                                    }
                                                ?> rounded-full">
                                                    <?php echo htmlspecialchars($inspection['overall_result']); ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($inspection['inspection_date']))); ?></p></td>
                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($inspection['inspector_name'] ?? 'N/A'); ?></p></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">Belum ada data pemeriksaan kualitas.</td></tr>
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