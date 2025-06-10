<?php
session_start();
include 'includes/db_connect.php';

$message = "";
$submitted_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    if (empty($_POST['nama_pelanggan'])) $errors[] = "Nama Pelanggan tidak boleh kosong.";
    if (empty($_POST['jenis_kain'])) $errors[] = "Jenis Kain harus dipilih.";
    if (empty($_POST['ukuran'])) $errors[] = "Ukuran tidak boleh kosong.";
    if (empty($_POST['warna'])) $errors[] = "Warna tidak boleh kosong.";
    // desain_kustom_text can be empty
    if (empty($_POST['jumlah']) || !is_numeric($_POST['jumlah']) || $_POST['jumlah'] <= 0) $errors[] = "Jumlah harus angka positif.";
    if (empty($_POST['tenggat_waktu'])) $errors[] = "Tenggat Waktu tidak boleh kosong.";

    if (empty($errors)) {
        $nama_pelanggan = $conn->real_escape_string($_POST['nama_pelanggan']);
        $jenis_kain = $conn->real_escape_string($_POST['jenis_kain']);
        $ukuran = $conn->real_escape_string($_POST['ukuran']);
        $warna = $conn->real_escape_string($_POST['warna']);
        $desain_kustom_text = $conn->real_escape_string($_POST['desain_kustom_text']);
        $jumlah = $conn->real_escape_string($_POST['jumlah']);
        $tenggat_waktu = $conn->real_escape_string($_POST['tenggat_waktu']);

        // Generate a unique ID for the order
        $id_pesanan = 'ORD-' . uniqid();
        $desain_kustom_flag = !empty($desain_kustom_text) ? 'Ya' : 'Tidak'; // Determine based on text input
        $status_desain = "Menunggu Persetujuan"; // Default status for new order

        $sql = "INSERT INTO pemesanan (id_pesanan, nama_pelanggan, jenis_kain, ukuran, warna, desain_kustom, status_desain, catatan_kustomisasi, jumlah, tenggat_waktu)
                VALUES ('$id_pesanan', '$nama_pelanggan', '$jenis_kain', '$ukuran', '$warna', '$desain_kustom_flag', '$status_desain', '$desain_kustom_text', '$jumlah', '$tenggat_waktu')";

        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Pemesanan produk berhasil dikirim! ID Pesanan Anda: <strong>" . htmlspecialchars($id_pesanan) . "</strong></div>";
            $submitted_data = [
                'ID Pesanan' => $id_pesanan,
                'Nama Pelanggan' => $nama_pelanggan,
                'Jenis Kain' => $jenis_kain,
                'Ukuran' => $ukuran,
                'Warna' => $warna,
                'Desain Kustom' => $desain_kustom_text,
                'Jumlah' => $jumlah,
                'Tenggat Waktu' => $tenggat_waktu
            ];
            $_SESSION['last_pemesanan_produk'] = $submitted_data;
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    }
}

if (isset($_SESSION['last_pemesanan_produk']) && $submitted_data === null) {
    $submitted_data = $_SESSION['last_pemesanan_produk'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pemesanan Produk</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Form Pemesanan Produk</h2>
        <?php echo $message; ?>
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" id="nama_pelanggan" name="nama_pelanggan" placeholder=" " required>
                <label for="nama_pelanggan">Nama Pelanggan:</label>
            </div>
            <div class="form-group">
                <select id="jenis_kain" name="jenis_kain" required>
                    <option value="" disabled selected>Pilih Jenis Kain</option>
                    <option value="Katun">Katun</option>
                    <option value="Polyester">Polyester</option>
                    <option value="Sutra">Sutra</option>
                </select>
                <label for="jenis_kain">Jenis Kain:</label>
            </div>
            <div class="form-group">
                <input type="text" id="ukuran" name="ukuran" placeholder=" " required>
                <label for="ukuran">Ukuran:</label>
            </div>
            <div class="form-group">
                <input type="text" id="warna" name="warna" placeholder=" " required>
                <label for="warna">Warna:</label>
            </div>
            <div class="form-group">
                <textarea id="desain_kustom_text" name="desain_kustom_text" rows="4" placeholder=" "></textarea>
                <label for="desain_kustom_text">Detail Desain Kustom:</label>
            </div>
            <div class="form-group">
                <input type="number" id="jumlah" name="jumlah" min="1" placeholder=" " required>
                <label for="jumlah">Jumlah:</label>
            </div>
            <div class="form-group">
                <input type="date" id="tenggat_waktu" name="tenggat_waktu" placeholder=" " required>
                <label for="tenggat_waktu">Tenggat Waktu:</label>
                <span class="date-icon material-icons">event</span>
            </div>
            <button type="submit">Kirim</button>
        </form>

        <?php if ($submitted_data): ?>
            <h3>Data Pemesanan Terakhir</h3>
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
