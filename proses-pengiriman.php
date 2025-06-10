<?php
session_start();
include 'includes/db_connect.php';

$message = "";
$submitted_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    if (empty($_POST['id_pesanan'])) $errors[] = "ID Pesanan tidak boleh kosong.";
    if (empty($_POST['jumlah_produk_siap_kirim']) || !is_numeric($_POST['jumlah_produk_siap_kirim']) || $_POST['jumlah_produk_siap_kirim'] <= 0) $errors[] = "Jumlah produk siap kirim harus angka positif.";
    if (empty($_POST['kurir_jasa_ekspedisi'])) $errors[] = "Kurir/Jasa Ekspedisi tidak boleh kosong.";
    if (empty($_POST['jadwal_pengiriman'])) $errors[] = "Jadwal Pengiriman tidak boleh kosong.";
    if (empty($_POST['status_pembayaran'])) $errors[] = "Status Pembayaran harus dipilih.";

    if (empty($errors)) {
        $id_pesanan = $conn->real_escape_string($_POST['id_pesanan']);
        $jumlah_produk_siap_kirim = $conn->real_escape_string($_POST['jumlah_produk_siap_kirim']);
        $kurir_jasa_ekspedisi = $conn->real_escape_string($_POST['kurir_jasa_ekspedisi']);
        $jadwal_pengiriman = $conn->real_escape_string($_POST['jadwal_pengiriman']);
        $status_pembayaran = $conn->real_escape_string($_POST['status_pembayaran']);

        // *** PENAMBAHAN/PERUBAHAN DI SINI UNTUK id_pengiriman ***
        $id_pengiriman = 'DEL-' . uniqid(); // Generate a unique ID for pengiriman

        $check_pesanan = $conn->query("SELECT id_pesanan FROM pemesanan WHERE id_pesanan = '$id_pesanan'");
        if ($check_pesanan->num_rows == 0) {
            $errors[] = "ID Pesanan tidak ditemukan di database pemesanan. Harap pastikan pesanan sudah terdaftar.";
        } else {
            // Modified SQL query to include id_pengiriman
            $sql = "INSERT INTO pengiriman (id_pengiriman, id_pesanan, jumlah_produk_siap_kirim, kurir_jasa_ekspedisi, jadwal_pengiriman, status_pembayaran, tanggal_dikirim)
                    VALUES ('$id_pengiriman', '$id_pesanan', '$jumlah_produk_siap_kirim', '$kurir_jasa_ekspedisi', '$jadwal_pengiriman', '$status_pembayaran', NOW())";

            if ($conn->query($sql) === TRUE) {
                $message = "<div class='alert alert-success'>Proses pengiriman berhasil dicatat! ID Pengiriman: <strong>" . htmlspecialchars($id_pengiriman) . "</strong></div>";
                $submitted_data = [
                    'ID Pengiriman' => $id_pengiriman, // Include in submitted data
                    'ID Pesanan' => $id_pesanan,
                    'Jumlah Siap Kirim' => $jumlah_produk_siap_kirim,
                    'Kurir' => $kurir_jasa_ekspedisi,
                    'Jadwal Kirim' => $jadwal_pengiriman,
                    'Status Pembayaran' => $status_pembayaran,
                    'Tanggal Dicatat' => date('Y-m-d H:i:s')
                ];
                $_SESSION['last_proses_pengiriman'] = $submitted_data;
            } else {
                $message = "<div class='alert alert-danger'>Error saat menyimpan data: " . $sql . "<br>" . $conn->error . "</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    }
}

if (isset($_SESSION['last_proses_pengiriman']) && $submitted_data === null) {
    $submitted_data = $_SESSION['last_proses_pengiriman'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pengiriman Produk</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Proses Produksi - Pengiriman</h2>
        <?php echo $message; ?>
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" id="id_pesanan" name="id_pesanan" placeholder=" " required>
                <label for="id_pesanan">ID Pesanan:</label>
            </div>
            <div class="form-group">
                <input type="number" id="jumlah_produk_siap_kirim" name="jumlah_produk_siap_kirim" min="1" placeholder=" " required>
                <label for="jumlah_produk_siap_kirim">Jumlah produk siap kirim:</label>
            </div>
            <div class="form-group">
                <input type="text" id="kurir_jasa_ekspedisi" name="kurir_jasa_ekspedisi" placeholder=" " required>
                <label for="kurir_jasa_ekspedisi">Kurir / jasa ekspedisi:</label>
            </div>
            <div class="form-group">
                <input type="date" id="jadwal_pengiriman" name="jadwal_pengiriman" placeholder=" " required>
                <label for="jadwal_pengiriman">Jadwal Pengiriman:</label>
                <span class="date-icon material-icons">event</span>
            </div>
            <div class="form-group">
                <label style="position: static; margin-bottom: 10px;">Status Pembayaran:</label>
                <div class="radio-group">
                    <input type="radio" id="lunas" name="status_pembayaran" value="Lunas" required>
                    <label for="lunas">Lunas</label>
                    <input type="radio" id="kredit" name="status_pembayaran" value="Kredit">
                    <label for="kredit">Kredit</label>
                </div>
            </div>
            <button type="submit">Proses kirim</button>
        </form>

        <?php if ($submitted_data): ?>
            <h3>Data Pengiriman Terakhir</h3>
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
        <?php endif; ?>
    </div>
</body>
</html>
