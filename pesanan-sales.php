<?php
session_start();
include 'includes/db_connect.php';

$message = "";
$submitted_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    if (empty($_POST['id_pesanan'])) $errors[] = "ID Pesanan tidak boleh kosong.";
    if (empty($_POST['nama_pelanggan'])) $errors[] = "Nama Pelanggan tidak boleh kosong.";
    if (empty($_POST['jenis_kain'])) $errors[] = "Jenis Kain harus dipilih.";
    if (empty($_POST['desain_kustom'])) $errors[] = "Desain Kustom harus dipilih.";
    if (empty($_POST['status_desain'])) $errors[] = "Status Desain harus dipilih.";
    // For this form, we'll assume ukuran, warna, jumlah, tenggat_waktu are not directly entered here,
    // but handled by form_pemesanan_produk. So, we'll assign default/empty values.
    // If you intend for this form to fully capture it, uncomment and validate these fields.

    if (empty($errors)) {
        $id_pesanan = $conn->real_escape_string($_POST['id_pesanan']);
        $nama_pelanggan = $conn->real_escape_string($_POST['nama_pelanggan']);
        $jenis_kain = $conn->real_escape_string($_POST['jenis_kain']);
        $desain_kustom = $conn->real_escape_string($_POST['desain_kustom']);
        $status_desain = $conn->real_escape_string($_POST['status_desain']);
        $catatan_kustomisasi = $conn->real_escape_string($_POST['catatan_kustomisasi']);
        $ukuran = ""; // Placeholder
        $warna = ""; // Placeholder
        $jumlah = 0; // Placeholder
        $tenggat_waktu = "2000-01-01"; // Placeholder

        $sql = "INSERT INTO pemesanan (id_pesanan, nama_pelanggan, jenis_kain, desain_kustom, status_desain, catatan_kustomisasi, ukuran, warna, jumlah, tenggat_waktu)
                VALUES ('$id_pesanan', '$nama_pelanggan', '$jenis_kain', '$desain_kustom', '$status_desain', '$catatan_kustomisasi', '$ukuran', '$warna', '$jumlah', '$tenggat_waktu')";

        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Pesanan berhasil disimpan!</div>";
            $submitted_data = [
                'ID Pesanan' => $id_pesanan,
                'Nama Pelanggan' => $nama_pelanggan,
                'Jenis Kain' => $jenis_kain,
                'Desain Kustom' => $desain_kustom,
                'Status Desain' => $status_desain,
                'Catatan Kustomisasi' => $catatan_kustomisasi
            ];
            $_SESSION['last_input_pesanan_sales'] = $submitted_data;
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    }
}

if (isset($_SESSION['last_input_pesanan_sales']) && $submitted_data === null) {
    $submitted_data = $_SESSION['last_input_pesanan_sales'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Input Pesanan oleh Sales</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Form Input Pesanan oleh Sales</h2>
        <?php echo $message; ?>
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" id="id_pesanan" name="id_pesanan" placeholder=" " required>
                <label for="id_pesanan">ID Pesanan:</label>
            </div>
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
                <label style="position: static; margin-bottom: 10px;">Desain Kustom?</label>
                <div class="radio-group">
                    <input type="radio" id="desain_ya" name="desain_kustom" value="Ya" required>
                    <label for="desain_ya">Ya</label>
                    <input type="radio" id="desain_tidak" name="desain_kustom" value="Tidak">
                    <label for="desain_tidak">Tidak</label>
                </div>
            </div>
            <div class="form-group">
                <select id="status_desain" name="status_desain" required>
                    <option value="" disabled selected>Pilih Status Desain</option>
                    <option value="Menunggu Persetujuan">Menunggu Persetujuan</option>
                    <option value="Disetujui">Disetujui</option>
                    <option value="Revisi">Revisi</option>
                    <option value="Selesai">Selesai</option>
                </select>
                <label for="status_desain">Status Desain:</label>
            </div>
            <div class="form-group">
                <textarea id="catatan_kustomisasi" name="catatan_kustomisasi" rows="4" placeholder=" "></textarea>
                <label for="catatan_kustomisasi">Catatan Kustomisasi:</label>
            </div>
            <button type="submit">Simpan Pesanan</button>
        </form>

        <?php if ($submitted_data): ?>
            <h3>Data Pesanan Terakhir</h3>
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
