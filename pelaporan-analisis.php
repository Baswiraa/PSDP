<?php
session_start();
include 'includes/db_connect.php';

$message = "";
$report_data = []; // Will store fetched report data
$report_headers = []; // To store table headers dynamically

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    if (empty($_POST['periode_laporan'])) $errors[] = "Periode Laporan tidak boleh kosong.";
    if (empty($_POST['jenis_laporan'])) $errors[] = "Jenis Laporan harus dipilih.";

    if (empty($errors)) {
        $periode_laporan = $conn->real_escape_string($_POST['periode_laporan']);
        $jenis_laporan = $conn->real_escape_string($_POST['jenis_laporan']);
        $sql = "";

        // Determine SQL based on report type
        if ($jenis_laporan == 'Pesanan') {
            // Filter by year/month from tenggat_waktu
            $sql = "SELECT id_pesanan, nama_pelanggan, jenis_kain, jumlah, tenggat_waktu, status_desain FROM pemesanan WHERE tenggat_waktu LIKE '$periode_laporan%'";
        } elseif ($jenis_laporan == 'Produksi') {
            // Filter by year/month from tanggal_mulai_produksi
            $sql = "SELECT id_produksi, nama_produk, tahapan, status_real_time, tanggal_mulai_produksi, tanggal_selesai_produksi FROM produksi WHERE tanggal_mulai_produksi LIKE '$periode_laporan%'";
        } elseif ($jenis_laporan == 'Pengiriman') {
            // Filter by year/month from jadwal_pengiriman
            $sql = "SELECT id_pengiriman, id_pesanan, jumlah_produk_siap_kirim, kurir_jasa_ekspedisi, jadwal_pengiriman, status_pembayaran FROM pengiriman WHERE jadwal_pengiriman LIKE '$periode_laporan%'";
        }

        if (!empty($sql)) {
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
                // Get headers from the first row keys
                $report_headers = array_keys($report_data[0]);
                $message = "<div class='alert alert-success'>Laporan " . htmlspecialchars($jenis_laporan) . " untuk periode " . htmlspecialchars($periode_laporan) . " berhasil ditampilkan.</div>";
            } else {
                $message = "<div class='alert alert-info'>Tidak ada data untuk laporan ini pada periode yang dipilih.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Jenis laporan tidak valid atau query belum ditentukan.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pelaporan & Analisis</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .button-group {
            display: flex;
            gap: 15px; /* Spacing between buttons */
            margin-top: 35px;
        }
        .button-group button {
            flex: 1; /* Make buttons take equal width */
            margin-top: 0; /* Override default button margin-top */
        }
        .button-group .download-button {
            background-color: #8B4513; /* Darker brown for download */
        }
        .button-group .download-button:hover {
            background-color: #6C3511; /* Even darker brown on hover */
        }
        .report-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #FFC400; /* Dashed line for separation */
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Form Pelaporan & Analisis</h2>
        <?php echo $message; ?>
        <form action="" method="POST">
            <div class="form-group">
                <select id="periode_laporan" name="periode_laporan" required>
                    <option value="" disabled selected>Pilih Periode</option>
                    <?php
                        $current_year = date('Y');
                        for ($y = $current_year; $y >= $current_year - 5; $y--) {
                            echo "<option value='{$y}'>Tahun {$y}</option>";
                            for ($m = 1; $m <= 12; $m++) {
                                $month_padded = str_pad($m, 2, '0', STR_PAD_LEFT);
                                echo "<option value='{$y}-{$month_padded}'>" . date('F Y', mktime(0, 0, 0, $m, 1, $y)) . "</option>";
                            }
                        }
                    ?>
                </select>
                <label for="periode_laporan">Periode Laporan:</label>
            </div>
            <div class="form-group">
                <select id="jenis_laporan" name="jenis_laporan" required>
                    <option value="" disabled selected>Pilih Jenis Laporan</option>
                    <option value="Pesanan">Laporan Pesanan</option>
                    <option value="Produksi">Laporan Produksi</option>
                    <option value="Pengiriman">Laporan Pengiriman</option>
                </select>
                <label for="jenis_laporan">Jenis Laporan:</label>
            </div>
            <div class="button-group">
                <button type="submit">Tampilkan Laporan</button>
                <button type="button" class="download-button" onclick="alert('Fungsi unduh PDF akan diimplementasikan di sini menggunakan library PHP seperti FPDF/Dompdf.')">Unduh PDF</button>
            </div>
        </form>

        <?php if (!empty($report_data)): ?>
            <div class="report-section">
                <h3>Hasil Laporan <?php echo htmlspecialchars($jenis_laporan); ?></h3>
                <table class="data-display-table">
                    <thead>
                        <tr>
                            <?php foreach ($report_headers as $header): ?>
                                <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $header))); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            // Function to update input label position for all relevant inputs
            document.querySelectorAll('.form-group input, .form-group textarea, .form-group select').forEach(input => {
                function checkAndMoveLabel() {
                    // Check if input has value, or if it's a select and an option is selected (not disabled selected)
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
                checkAndMoveLabel(); // Initial check on load

                // For select elements, handle initial state if a default value is set (e.g., from DB)
                if (input.tagName === 'SELECT' && input.value !== '') {
                    input.classList.add('has-value');
                }
            });
        });
    </script>
</body>
</html>