<?php
session_start();
include 'includes/db_connect.php';

$message = "";
$submitted_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    if (empty($_POST['id_produksi'])) $errors[] = "ID Produksi tidak boleh kosong.";
    if (empty($_POST['cek_jahitan'])) $errors[] = "Pilihan Cek Jahitan harus dipilih.";
    if (empty($_POST['cek_desain'])) $errors[] = "Pilihan Cek Desain harus dipilih.";
    if (empty($_POST['status_produk'])) $errors[] = "Status Produk harus dipilih.";

    if (empty($errors)) {
        $id_produksi = $conn->real_escape_string($_POST['id_produksi']);
        $cek_jahitan = $conn->real_escape_string($_POST['cek_jahitan']);
        $cek_desain = $conn->real_escape_string($_POST['cek_desain']);
        $status_produk = $conn->real_escape_string($_POST['status_produk']);

        $check_produksi = $conn->query("SELECT id_produksi FROM produksi WHERE id_produksi = '$id_produksi'");
        if ($check_produksi->num_rows == 0) {
            $errors[] = "ID Produksi tidak ditemukan di database produksi.";
        } else {
            $sql = "INSERT INTO pemeriksaan_kualitas (id_produksi, cek_jahitan, cek_desain, status_produk)
                    VALUES ('$id_produksi', '$cek_jahitan', '$cek_desain', '$status_produk')";

            if ($conn->query($sql) === TRUE) {
                $message = "<div class='alert alert-success'>Pemeriksaan kualitas berhasil disubmit!</div>";
                $submitted_data = [
                    'ID Produksi' => $id_produksi,
                    'Cek Jahitan' => $cek_jahitan,
                    'Cek Desain' => $cek_desain,
                    'Status Produk' => $status_produk,
                    'Tanggal Pemeriksaan' => date('Y-m-d H:i:s')
                ];
                $_SESSION['last_pemeriksaan_kualitas'] = $submitted_data;
            } else {
                $message = "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    }
}

if (isset($_SESSION['last_pemeriksaan_kualitas']) && $submitted_data === null) {
    $submitted_data = $_SESSION['last_pemeriksaan_kualitas'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pemeriksaan Kualitas</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Form Pemeriksaan Kualitas</h2>
        <?php echo $message; ?>
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" id="id_produksi" name="id_produksi" placeholder=" " required>
                <label for="id_produksi">ID Produksi:</label>
            </div>
            <div class="form-group">
                <label style="position: static; margin-bottom: 10px;">Cek Jahitan:</label>
                <div class="radio-group">
                    <input type="radio" id="jahitan_ya" name="cek_jahitan" value="Ya" required>
                    <label for="jahitan_ya">Ya</label>
                    <input type="radio" id="jahitan_tidak" name="cek_jahitan" value="Tidak">
                    <label for="jahitan_tidak">Tidak</label>
                </div>
            </div>
            <div class="form-group">
                <label style="position: static; margin-bottom: 10px;">Cek Desain:</label>
                <div class="radio-group">
                    <input type="radio" id="desain_sesuai" name="cek_desain" value="Sesuai" required>
                    <label for="desain_sesuai">Sesuai</label>
                    <input type="radio" id="desain_tidak_sesuai" name="cek_desain" value="Tidak">
                    <label for="desain_tidak_sesuai">Tidak</label>
                </div>
            </div>
            <div class="form-group">
                <label style="position: static; margin-bottom: 10px;">Status Produk:</label>
                <div class="radio-group">
                    <input type="radio" id="lolos" name="status_produk" value="Lolos" required>
                    <label for="lolos">Lolos</label>
                    <input type="radio" id="perlu_revisi" name="status_produk" value="Perlu revisi">
                    <label for="perlu_revisi">Perlu revisi</label>
                </div>
            </div>
            <button type="submit">Submit Pemeriksaan</button>
        </form>

        <?php if ($submitted_data): ?>
            <h3>Data Pemeriksaan Terakhir</h3>
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
