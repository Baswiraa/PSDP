<?php
session_start();
include 'includes/db_connect.php';

$message = "";
$submitted_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    if (empty($_POST['id_produksi'])) $errors[] = "ID Produksi tidak boleh kosong.";
    if (empty($_POST['nama_produk'])) $errors[] = "Nama Produk tidak boleh kosong.";
    if (empty($_POST['tahapan'])) $errors[] = "Setidaknya satu Tahapan harus dipilih.";
    if (empty($_POST['status_real_time']) || !is_numeric($_POST['status_real_time']) || $_POST['status_real_time'] < 0 || $_POST['status_real_time'] > 100) $errors[] = "Status Real-Time harus antara 0-100.";

    if (empty($errors)) {
        $id_produksi = $conn->real_escape_string($_POST['id_produksi']);
        $nama_produk = $conn->real_escape_string($_POST['nama_produk']);
        $tahapan = $conn->real_escape_string(implode(',', $_POST['tahapan']));
        $status_real_time = $conn->real_escape_string($_POST['status_real_time']);
        $catatan = $conn->real_escape_string($_POST['catatan']);

        // Check if ID_Produksi exists, if so, update; otherwise, insert.
        // For simplicity, we'll always insert if not exists, or update if exists.
        // A more robust system would involve checking if it's linked to an order, etc.
        $check_prod = $conn->query("SELECT * FROM produksi WHERE id_produksi = '$id_produksi'");
        if ($check_prod->num_rows > 0) {
            $sql = "UPDATE produksi SET
                    nama_produk = '$nama_produk',
                    tahapan = '$tahapan',
                    status_real_time = '$status_real_time',
                    catatan = '$catatan',
                    tanggal_selesai_produksi = CASE WHEN status_real_time = 100 THEN NOW() ELSE NULL END
                    WHERE id_produksi = '$id_produksi'";
        } else {
            $sql = "INSERT INTO produksi (id_produksi, nama_produk, tahapan, status_real_time, catatan, tanggal_mulai_produksi)
                    VALUES ('$id_produksi', '$nama_produk', '$tahapan', '$status_real_time', '$catatan', NOW())";
        }


        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'>Proses produksi berhasil diperbarui!</div>";
            $submitted_data = [
                'ID Produksi' => $id_produksi,
                'Nama Produk' => $nama_produk,
                'Tahapan' => str_replace(',', ', ', $tahapan), // Display nicely
                'Status Real-Time' => $status_real_time . '%',
                'Catatan' => $catatan
            ];
            $_SESSION['last_detail_proses_produksi'] = $submitted_data;
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    }
}

if (isset($_SESSION['last_detail_proses_produksi']) && $submitted_data === null) {
    $submitted_data = $_SESSION['last_detail_proses_produksi'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Produksi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Proses Produksi</h2>
        <?php echo $message; ?>
        <form action="" method="POST">
            <div class="form-group">
                <input type="text" id="id_produksi" name="id_produksi" placeholder=" " required>
                <label for="id_produksi">ID Produksi:</label>
            </div>
            <div class="form-group">
                <input type="text" id="nama_produk" name="nama_produk" placeholder=" " required>
                <label for="nama_produk">Nama Produk:</label>
            </div>
            <div class="form-group">
                <label style="position: static; margin-bottom: 10px;">Tahapan:</label>
                <div class="checkbox-group">
                    <input type="checkbox" id="potong" name="tahapan[]" value="Potong">
                    <label for="potong">Potong</label>
                    <input type="checkbox" id="bordir" name="tahapan[]" value="Bordir">
                    <label for="bordir">Bordir</label>
                    <input type="checkbox" id="jahit" name="tahapan[]" value="Jahit">
                    <label for="jahit">Jahit</label>
                </div>
            </div>
            <div class="form-group">
                <label for="status_real_time" style="position: static; margin-bottom: 10px;">Status Real-Time:</label>
                <input type="range" id="status_real_time" name="status_real_time" min="0" max="100" value="0" oninput="this.nextElementSibling.value = this.value + '%'">
                <output>0%</output>
            </div>
            <div class="form-group">
                <textarea id="catatan" name="catatan" rows="4" placeholder=" "></textarea>
                <label for="catatan">Catatan:</label>
            </div>
            <button type="submit">Selesai Produksi</button>
        </form>

        <?php if ($submitted_data): ?>
            <h3>Data Proses Produksi Terakhir</h3>
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
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const statusRange = document.getElementById('status_real_time');
            const output = statusRange.nextElementSibling;
            output.value = statusRange.value + '%';

            // Function to update input label position
            document.querySelectorAll('.form-group input, .form-group textarea, .form-group select').forEach(input => {
                function checkAndMoveLabel() {
                    if (input.value !== '' || input.hasAttribute('placeholder') && input.getAttribute('placeholder') !== '') {
                        input.classList.add('has-value');
                    } else {
                        input.classList.remove('has-value');
                    }
                }
                input.addEventListener('input', checkAndMoveLabel);
                input.addEventListener('focus', checkAndMoveLabel);
                input.addEventListener('blur', checkAndMoveLabel);
                checkAndMoveLabel(); // Initial check on load
            });
        });
    </script>
</body>
</html>
