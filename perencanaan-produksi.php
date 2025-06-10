<?php
session_start();
include 'includes/db_connect.php';

$message = "";
$submitted_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    if (empty($_POST['id_pesanan'])) $errors[] = "ID Pesanan tidak boleh kosong.";
    if (empty($_POST['jumlah_produksi']) || !is_numeric($_POST['jumlah_produksi']) || $_POST['jumlah_produksi'] <= 0) $errors[] = "Jumlah Produksi harus angka positif.";
    if (empty($_POST['sdm_tersedia'])) $errors[] = "Ketersediaan SDM harus dipilih.";
    if (empty($_POST['bahan_baku_tersedia'])) $errors[] = "Ketersediaan Bahan Baku harus dipilih.";
    if (empty($_POST['estimasi_selesai'])) $errors[] = "Estimasi Selesai tidak boleh kosong.";

    if (empty($errors)) {
        $id_pesanan = $conn->real_escape_string($_POST['id_pesanan']);
        $jumlah_produksi = $conn->real_escape_string($_POST['jumlah_produksi']);
        $sdm_tersedia = $conn->real_escape_string($_POST['sdm_tersedia']);
        $bahan_baku_tersedia = $conn->real_escape_string($_POST['bahan_baku_tersedia']);
        $estimasi_selesai = $conn->real_escape_string($_POST['estimasi_selesai']);

        $check_pesanan = $conn->query("SELECT id_pesanan FROM pemesanan WHERE id_pesanan = '$id_pesanan'");
        if ($check_pesanan->num_rows == 0) {
            $errors[] = "ID Pesanan tidak ditemukan di database pemesanan.";
        } else {
            $sql = "INSERT INTO perencanaan_produksi (id_pesanan, jumlah_produksi, sdm_tersedia, bahan_baku_tersedia, estimasi_selesai)
                    VALUES ('$id_pesanan', '$jumlah_produksi', '$sdm_tersedia', '$bahan_baku_tersedia', '$estimasi_selesai')";

            if ($conn->query($sql) === TRUE) {
                $message = "<div class='alert alert-success'>Perencanaan produksi berhasil disimpan!</div>";
                $submitted_data = [
                    'ID Pesanan' => $id_pesanan,
                    'Jumlah Produksi' => $jumlah_produksi,
                    'SDM Tersedia' => $sdm_tersedia,
                    'Bahan Baku Tersedia' => $bahan_baku_tersedia,
                    'Estimasi Selesai' => $estimasi_selesai
                ];
                $_SESSION['last_perencanaan_produksi'] = $submitted_data;
            } else {
                $message = "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    }
}

if (isset($_SESSION['last_perencanaan_produksi']) && $submitted_data === null) {
    $submitted_data = $_SESSION['last_perencanaan_produksi'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Perencanaan Produksi (PPIC)</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Form Perencanaan Produksi (PPIC)</h2>
        <?php echo $message; ?>
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" id="id_pesanan" name="id_pesanan" placeholder=" " required>
                <label for="id_pesanan">ID Pesanan:</label>
            </div>
            <div class="form-group">
                <input type="number" id="jumlah_produksi" name="jumlah_produksi" min="1" placeholder=" " required>
                <label for="jumlah_produksi">Jumlah Produksi:</label>
            </div>
            <div class="form-group">
                <label style="position: static; margin-bottom: 10px;">SDM Tersedia?</label>
                <div class="radio-group">
                    <input type="radio" id="sdm_ya" name="sdm_tersedia" value="Ya" required>
                    <label for="sdm_ya">Ya</label>
                    <input type="radio" id="sdm_tidak" name="sdm_tersedia" value="Tidak">
                    <label for="sdm_tidak">Tidak</label>
                </div>
            </div>
            <div class="form-group">
                <label style="position: static; margin-bottom: 10px;">Bahan Baku Tersedia?</label>
                <div class="radio-group">
                    <input type="radio" id="bahan_ya" name="bahan_baku_tersedia" value="Ya" required>
                    <label for="bahan_ya">Ya</label>
                    <input type="radio" id="bahan_tidak" name="bahan_baku_tersedia" value="Tidak">
                    <label for="bahan_tidak">Tidak</label>
                </div>
            </div>
            <div class="form-group">
                <input type="date" id="estimasi_selesai" name="estimasi_selesai" placeholder=" " required>
                <label for="estimasi_selesai">Estimasi Selesai:</label>
                <span class="date-icon material-icons">event</span>
            </div>
            <button type="submit">Setujui & Kirim ke Produksi</button>
        </form>

        <?php if ($submitted_data): ?>
            <h3>Data Perencanaan Terakhir</h3>
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
