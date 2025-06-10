<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'includes/db_connect.php';

$message = "";
$submitted_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    // Validate inputs
    if (empty($_POST['id_pesanan'])) $errors[] = "ID Pesanan tidak boleh kosong.";
    if (empty($_POST['nama_bahan_baku'])) $errors[] = "Nama Bahan Baku tidak boleh kosong.";
    if (empty($_POST['jumlah_masuk']) || !is_numeric($_POST['jumlah_masuk']) || $_POST['jumlah_masuk'] <= 0) $errors[] = "Jumlah Masuk harus angka positif.";
    if (empty($_POST['pemasok'])) $errors[] = "Pemasok tidak boleh kosong.";
    if (empty($_POST['status_bahan'])) $errors[] = "Status Bahan harus dipilih.";

    if (empty($errors)) {
        // Sanitize inputs
        $id_pesanan = $conn->real_escape_string($_POST['id_pesanan']);
        $nama_bahan_baku = $conn->real_escape_string($_POST['nama_bahan_baku']);
        $jumlah_masuk = $conn->real_escape_string($_POST['jumlah_masuk']);
        $pemasok = $conn->real_escape_string($_POST['pemasok']);
        $status_bahan = $conn->real_escape_string($_POST['status_bahan']);

        // Generate unique ID for id_penerimaan
        $id_penerimaan = 'BBM-' . uniqid();

        // Check if id_pesanan exists in pemesanan table (crucial for Foreign Key)
        $check_pesanan_sql = "SELECT id_pesanan FROM pemesanan WHERE id_pesanan = '$id_pesanan'";
        $check_pesanan_result = $conn->query($check_pesanan_sql);

        if ($check_pesanan_result->num_rows == 0) {
            $errors[] = "ID Pesanan '" . htmlspecialchars($id_pesanan) . "' tidak ditemukan di database pemesanan. Harap pastikan pesanan sudah terdaftar.";
        } else {
            // All good, proceed with insert
            $sql = "INSERT INTO penerimaan_bahan_baku (id_penerimaan, id_pesanan, nama_bahan_baku, jumlah_masuk, pemasok, status_bahan)
                    VALUES ('$id_penerimaan', '$id_pesanan', '$nama_bahan_baku', '$jumlah_masuk', '$pemasok', '$status_bahan')";

            if ($conn->query($sql) === TRUE) {
                $message = "<div class='alert alert-success'>Penerimaan bahan baku berhasil dikonfirmasi! ID Penerimaan: <strong>" . htmlspecialchars($id_penerimaan) . "</strong></div>";
                $submitted_data = [
                    'ID Penerimaan' => $id_penerimaan,
                    'ID Pesanan' => $id_pesanan,
                    'Nama Bahan Baku' => $nama_bahan_baku,
                    'Jumlah Masuk' => $jumlah_masuk,
                    'Pemasok' => $pemasok,
                    'Status Bahan' => $status_bahan,
                    'Tanggal Penerimaan' => date('Y-m-d H:i:s')
                ];
                $_SESSION['last_penerimaan_bahan_baku'] = $submitted_data;

            } else {
                $message = "<div class='alert alert-danger'>Error saat menyimpan data: " . $sql . "<br>" . $conn->error . "</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    }
}

if (isset($_SESSION['last_penerimaan_bahan_baku']) && $submitted_data === null) {
    $submitted_data = $_SESSION['last_penerimaan_bahan_baku'];
    // unset($_SESSION['last_penerimaan_bahan_baku']); // Uncomment if you want to clear after one display
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Penerimaan Bahan Baku</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Form Penerimaan Bahan Baku (Gudang/Procurement)</h2>
        <?php echo $message; ?>
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" id="id_pesanan" name="id_pesanan" placeholder=" " required>
                <label for="id_pesanan">ID Pesanan:</label>
            </div>
            <div class="form-group">
                <input type="text" id="nama_bahan_baku" name="nama_bahan_baku" placeholder=" " required>
                <label for="nama_bahan_baku">Nama Bahan Baku:</label>
            </div>
            <div class="form-group">
                <input type="number" id="jumlah_masuk" name="jumlah_masuk" min="1" placeholder=" " required>
                <label for="jumlah_masuk">Jumlah Masuk:</label>
            </div>
            <div class="form-group">
                <input type="text" id="pemasok" name="pemasok" placeholder=" " required>
                <label for="pemasok">Pemasok:</label>
            </div>
            <div class="form-group">
                <label style="position: static; margin-bottom: 10px;">Status Bahan:</label>
                <div class="radio-group">
                    <input type="radio" id="layak" name="status_bahan" value="Layak" required>
                    <label for="layak">Layak</label>
                    <input type="radio" id="tidak_layak" name="status_bahan" value="Tidak Layak">
                    <label for="tidak_layak">Tidak Layak</label>
                </div>
            </div>
            <button type="submit">Konfirmasi Penerimaan</button>
        </form>

        <?php if ($submitted_data): ?>
            <div class="report-section">
                <h3>Data Penerimaan Terakhir</h3>
                <table class="data-display-table">
                    <thead>
                        <tr>
                            <?php foreach ($submitted_data as $key => $value): ?>
                                <th><?php echo htmlspecialchars($key); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($submitted_data as $value): ?>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('.form-group input, .form-group textarea, .form-group select').forEach(input => {
                function checkAndMoveLabel() {
                    const hasValue = input.value !== '';
                    const isSelectAndSelected = input.tagName === 'SELECT' && input.value !== '';

                    if (hasValue || isSelectAndSelected) {
                        input.classList.add('has-value');
                    } else {
                        input.classList.remove('has-value');
                    }
                }
                input.addEventListener('input', checkAndMoveLabel);
                input.addEventListener('focus', checkAndMoveLabel);
                input.addEventListener('blur', checkAndMoveLabel);
                checkAndMoveLabel();
            });
        });
    </script>
</body>
</html>
