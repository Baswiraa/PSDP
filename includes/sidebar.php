<div class="w-64 bg-gray-100 shadow-lg min-h-screen p-4 border-r border-gray-200">
    <ul class="space-y-2">
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="../index.php">
                <i class="fas fa-fw fa-tachometer-alt mr-3 text-lg"></i> Dashboard
            </a>
        </li>
        <?php if (has_access(['manager', 'karyawan'])): ?>
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="pages/pesanan-sales.php">
                <i class="fas fa-fw fa-shopping-cart mr-3 text-lg"></i> Pesanan Sales
            </a>
        </li>
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="../pages/penerimaan-bahanbaku.php">
                <i class="fas fa-fw fa-truck-loading mr-3 text-lg"></i> Penerimaan Bahan Baku
            </a>
        </li>
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="../pages/perencanaan-produksi.php">
                <i class="fas fa-fw fa-clipboard-list mr-3 text-lg"></i> Perencanaan Produksi
            </a>
        </li>
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="../pages/detail-proses-produksi.php">
                <i class="fas fa-fw fa-industry mr-3 text-lg"></i> Detail Proses Produksi
            </a>
        </li>
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="../pages/pemeriksaan-kualitas.php">
                <i class="fas fa-fw fa-check-circle mr-3 text-lg"></i> Pemeriksaan Kualitas
            </a>
        </li>
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="../pages/pemesanan-produk.php">
                <i class="fas fa-fw fa-box mr-3 text-lg"></i> Pemesanan Produk Jadi
            </a>
        </li>
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="../pages/proses-pengiriman.php">
                <i class="fas fa-fw fa-shipping-fast mr-3 text-lg"></i> Proses Pengiriman
            </a>
        </li>
        <?php endif; ?>
        <?php if (has_access(['manager'])): ?>
        <li>
            <a class="flex items-center p-2 text-gray-700 hover:bg-gray-200 rounded-md transition duration-150 ease-in-out" href="../pages/pelaporan-analisis.php">
                <i class="fas fa-fw fa-chart-line mr-3 text-lg"></i> Pelaporan & Analisis
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>