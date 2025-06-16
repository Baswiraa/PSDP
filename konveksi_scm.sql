-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2025 at 08:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `konveksi_scm`
--

-- --------------------------------------------------------

--
-- Table structure for table `finished_goods_movement`
--

CREATE TABLE `finished_goods_movement` (
  `movement_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('Inbound from Production','Outbound to Shipping','Adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `movement_date` datetime DEFAULT current_timestamp(),
  `source_location` varchar(100) DEFAULT NULL,
  `destination_location` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finished_goods_movement`
--

INSERT INTO `finished_goods_movement` (`movement_id`, `product_id`, `movement_type`, `quantity`, `movement_date`, `source_location`, `destination_location`, `notes`, `processed_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Inbound from Production', 48, '2025-06-17 00:56:23', 'Produksi', 'Gudang Utama', 'Penerimaan T-Shirt hasil QC Lulus dari Batch TS-PROD-B001', 4, '2025-06-16 17:56:23', '2025-06-16 17:56:23');

--
-- Triggers `finished_goods_movement`
--
DELIMITER $$
CREATE TRIGGER `trg_update_product_stock_on_movement` AFTER INSERT ON `finished_goods_movement` FOR EACH ROW BEGIN
    IF NEW.movement_type = 'Inbound from Production' THEN
        UPDATE products
        SET stock_qty = stock_qty + NEW.quantity
        WHERE product_id = NEW.product_id;
    ELSEIF NEW.movement_type = 'Outbound to Shipping' THEN
        UPDATE products
        SET stock_qty = stock_qty - NEW.quantity
        WHERE product_id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `material_receipts`
--

CREATE TABLE `material_receipts` (
  `receipt_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `quantity_received` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `batch_number` varchar(100) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `material_receipts`
--

INSERT INTO `material_receipts` (`receipt_id`, `supplier_id`, `material_id`, `receipt_date`, `quantity_received`, `unit_cost`, `batch_number`, `received_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-06-10', 200.00, 25000.00, 'RM-BAT-K001', 2, '2025-06-16 17:56:23', '2025-06-16 17:56:23');

--
-- Triggers `material_receipts`
--
DELIMITER $$
CREATE TRIGGER `trg_update_raw_material_stock_on_receipt` AFTER INSERT ON `material_receipts` FOR EACH ROW BEGIN
    UPDATE raw_materials
    SET current_stock_qty = current_stock_qty + NEW.quantity_received
    WHERE material_id = NEW.material_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `production_plans`
--

CREATE TABLE `production_plans` (
  `plan_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `planned_product_id` int(11) DEFAULT NULL,
  `planned_quantity` int(11) NOT NULL,
  `status` enum('Planned','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_plans`
--

INSERT INTO `production_plans` (`plan_id`, `plan_name`, `start_date`, `end_date`, `planned_product_id`, `planned_quantity`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Produksi T-Shirt Batch Juni 1', '2025-06-16', '2025-06-20', 1, 100, 'In Progress', 'Untuk memenuhi order bulan Juni', 1, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(2, 'Produksi Kemeja Batch Juli', '2025-07-01', '2025-07-07', 2, 50, 'Planned', 'Rencana produksi kemeja pria', 1, '2025-06-16 17:56:23', '2025-06-16 17:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `production_processes`
--

CREATE TABLE `production_processes` (
  `process_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `batch_id` varchar(100) DEFAULT NULL,
  `quantity_in_process` int(11) NOT NULL,
  `current_stage` enum('Cutting','Sewing','Finishing','Quality Control','Packaging','Completed') NOT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('Ongoing','On Hold','Finished','Aborted') NOT NULL DEFAULT 'Ongoing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_processes`
--

INSERT INTO `production_processes` (`process_id`, `plan_id`, `product_id`, `batch_id`, `quantity_in_process`, `current_stage`, `start_time`, `end_time`, `assigned_to`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'TS-PROD-B001-CUT', 50, 'Cutting', '2025-06-17 00:56:23', NULL, 2, 'Ongoing', '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(2, 1, 1, 'TS-PROD-B001-SEW', 50, 'Sewing', '2025-06-17 00:56:23', NULL, 2, 'Ongoing', '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(3, 1, 1, 'TS-PROD-B001-FIN', 50, 'Finishing', '2025-06-17 00:56:23', NULL, 2, 'Ongoing', '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(4, 2, 1, 'TS-PROD-B002-CUT', 75, 'Cutting', '2025-06-17 00:56:23', NULL, 2, 'Ongoing', '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(5, NULL, 2, 'KM-PROD-B001-CUT', 30, 'Cutting', '2025-06-17 00:56:23', NULL, 2, 'Ongoing', '2025-06-16 17:56:23', '2025-06-16 17:56:23');

--
-- Triggers `production_processes`
--
DELIMITER $$
CREATE TRIGGER `trg_update_production_plan_status` AFTER UPDATE ON `production_processes` FOR EACH ROW BEGIN
    IF NEW.current_stage = 'Completed' AND NEW.plan_id IS NOT NULL THEN
        -- Check if all processes for this plan are completed
        SET @total_processes_for_plan = (SELECT COUNT(*) FROM production_processes WHERE plan_id = NEW.plan_id);
        SET @completed_processes_for_plan = (SELECT COUNT(*) FROM production_processes WHERE plan_id = NEW.plan_id AND current_stage = 'Completed');

        IF @total_processes_for_plan > 0 AND @total_processes_for_plan = @completed_processes_for_plan THEN
            UPDATE production_plans SET status = 'Completed' WHERE plan_id = NEW.plan_id;
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit_of_measure` varchar(50) DEFAULT 'Pcs',
  `stock_qty` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `sku`, `description`, `unit_of_measure`, `stock_qty`, `price`, `created_at`, `updated_at`) VALUES
(1, 'T-Shirt Polos Merah', 'TS-M-001', 'T-Shirt pria lengan pendek warna merah', 'Pcs', 246, 45000.00, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(2, 'Kemeja Pria Biru', 'KM-P-002', 'Kemeja pria lengan panjang bahan katun biru', 'Pcs', 80, 95000.00, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(3, 'Celana Jeans Wanita', 'CJ-W-003', 'Celana jeans wanita slim fit', 'Pcs', 40, 180000.00, '2025-06-16 17:56:23', '2025-06-16 17:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `quality_inspections`
--

CREATE TABLE `quality_inspections` (
  `inspection_id` int(11) NOT NULL,
  `process_id` int(11) NOT NULL,
  `inspection_date` datetime DEFAULT current_timestamp(),
  `inspector_id` int(11) DEFAULT NULL,
  `quantity_inspected` int(11) NOT NULL,
  `quantity_defective` int(11) DEFAULT 0,
  `defect_description` text DEFAULT NULL,
  `overall_result` enum('Passed','Failed','Rework Required') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quality_inspections`
--

INSERT INTO `quality_inspections` (`inspection_id`, `process_id`, `inspection_date`, `inspector_id`, `quantity_inspected`, `quantity_defective`, `defect_description`, `overall_result`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, '2025-06-18 10:00:00', 3, 50, 2, 'Jahitan kurang rapi pada leher', 'Passed', '2 pcs direject, lainnya OK. Proses ID 3, batch TS-PROD-B001-FIN', '2025-06-16 17:56:23', '2025-06-16 17:56:23');

--
-- Triggers `quality_inspections`
--
DELIMITER $$
CREATE TRIGGER `trg_update_product_stock_on_qc_pass` AFTER INSERT ON `quality_inspections` FOR EACH ROW BEGIN
    -- Hanya update jika hasil = Passed
    IF NEW.overall_result = 'Passed' THEN
        -- Dapatkan product_id dari production_processes terkait
        SET @product_id_from_process = (SELECT product_id FROM production_processes WHERE process_id = NEW.process_id);

        -- Update stok produk jadi
        UPDATE products
        SET stock_qty = stock_qty + (NEW.quantity_inspected - NEW.quantity_defective)
        WHERE product_id = @product_id_from_process;

        -- Opsional: Update status production_processes ke 'Finished' jika ini adalah QC terakhir
        -- Ini mengasumsikan bahwa setelah QC Passed, proses produksi untuk batch ini selesai.
        UPDATE production_processes
        SET status = 'Finished', end_time = CURRENT_TIMESTAMP
        WHERE process_id = NEW.process_id
        AND current_stage = 'Quality Control'; -- Hanya jika QC adalah tahap terakhir sebelum Finished
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `raw_materials`
--

CREATE TABLE `raw_materials` (
  `material_id` int(11) NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit_of_measure` varchar(50) NOT NULL,
  `current_stock_qty` decimal(10,2) DEFAULT 0.00,
  `min_stock_level` decimal(10,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `raw_materials`
--

INSERT INTO `raw_materials` (`material_id`, `material_name`, `description`, `unit_of_measure`, `current_stock_qty`, `min_stock_level`, `supplier_id`, `created_at`, `updated_at`) VALUES
(1, 'Katun Combed 30s Merah', 'Kain Katun Combed 30s warna Merah', 'Meter', 700.00, 100.00, 1, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(2, 'Katun Combed 30s Biru', 'Kain Katun Combed 30s warna Biru', 'Meter', 30.00, 50.00, 1, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(3, 'Benang Polyester Hitam', 'Benang jahit Polyester warna Hitam', 'Roll', 200.00, 50.00, 2, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(4, 'Kancing Plastik 1.5cm', 'Kancing plastik standar 1.5cm', 'Pcs', 10000.00, 2000.00, 3, '2025-06-16 17:56:23', '2025-06-16 17:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `order_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `order_date` date NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Confirmed','In Production','Ready for Shipping','Shipped','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`order_id`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `delivery_address`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Pelanggan A', 'pelanggan.a@email.com', '081122334455', '2025-06-15', 'Jl. Contoh No. 1, Jakarta', 90000.00, 'Pending', '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(2, 'Pelanggan B', 'pelanggan.b@email.com', '081234567890', '2025-06-14', 'Jl. Kenangan Indah No. 5, Bandung', 95000.00, 'Shipped', '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(3, 'Pelanggan C', 'pelanggan.c@email.com', '087654321098', '2025-06-12', 'Jl. Veteran No. 7, Surabaya', 180000.00, 'Completed', '2025-06-16 17:56:23', '2025-06-16 17:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_items`
--

CREATE TABLE `sales_order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_order_items`
--

INSERT INTO `sales_order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 2, 45000.00),
(2, 2, 2, 1, 95000.00),
(3, 3, 3, 1, 180000.00);

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `shipment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `shipping_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `status` enum('Pending','In Transit','Delivered','Failed','Returned') NOT NULL DEFAULT 'Pending',
  `shipped_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`shipment_id`, `order_id`, `shipping_date`, `delivery_date`, `carrier`, `tracking_number`, `status`, `shipped_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, '2025-06-14', '2025-06-16', 'JNE', 'JNE123456789', 'Delivered', 4, 'Paket Kemeja Pria Biru untuk Pelanggan B', '2025-06-16 17:56:23', '2025-06-16 17:56:23');

--
-- Triggers `shipments`
--
DELIMITER $$
CREATE TRIGGER `trg_update_sales_order_status_on_shipment` AFTER UPDATE ON `shipments` FOR EACH ROW BEGIN
    IF NEW.status = 'In Transit' THEN
        UPDATE sales_orders SET status = 'Shipped' WHERE order_id = NEW.order_id;
    ELSEIF NEW.status = 'Delivered' THEN
        UPDATE sales_orders SET status = 'Completed' WHERE order_id = NEW.order_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `phone_number`, `email`, `address`, `created_at`, `updated_at`) VALUES
(1, 'PT. Indotextile Jaya', 'Bapak Adi', '081234567890', 'info@indotextile.com', 'Jl. Merdeka No. 10, Bandung', '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(2, 'CV. Benang Makmur', 'Ibu Sita', '085678901234', 'sita@benangmakmur.co.id', 'Jl. Raya Industri Km. 5, Jakarta', '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(3, 'Kancing Mutiara Abadi', 'Pak Joko', '087812345678', 'joko@kancingmutiara.com', 'Jl. Melati No. 25, Surabaya', '2025-06-16 17:56:23', '2025-06-16 17:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('manager','karyawan') NOT NULL DEFAULT 'karyawan',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'manager1', 'HASH_MANAGER1_PLACEHOLDER', 'Budi Santoso', 'budi.santoso@konveksi.com', 'manager', 1, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(2, 'karyawan1', 'HASH_KARYAWAN1_PLACEHOLDER', 'Ani Wijaya', 'ani.wijaya@konveksi.com', 'karyawan', 1, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(3, 'qcstaff', 'HASH_QCSTAFF_PLACEHOLDER', 'Doni Pratama', 'doni.pratama@konveksi.com', 'karyawan', 1, '2025-06-16 17:56:23', '2025-06-16 17:56:23'),
(4, 'gudang1', 'HASH_GUDANG1_PLACEHOLDER', 'Fitri Cahaya', 'fitri.cahaya@konveksi.com', 'karyawan', 1, '2025-06-16 17:56:23', '2025-06-16 17:56:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `finished_goods_movement`
--
ALTER TABLE `finished_goods_movement`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `material_receipts`
--
ALTER TABLE `material_receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `production_plans`
--
ALTER TABLE `production_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `planned_product_id` (`planned_product_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `production_processes`
--
ALTER TABLE `production_processes`
  ADD PRIMARY KEY (`process_id`),
  ADD UNIQUE KEY `batch_id` (`batch_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `quality_inspections`
--
ALTER TABLE `quality_inspections`
  ADD PRIMARY KEY (`inspection_id`),
  ADD KEY `process_id` (`process_id`),
  ADD KEY `inspector_id` (`inspector_id`);

--
-- Indexes for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`shipment_id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `shipped_by` (`shipped_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `finished_goods_movement`
--
ALTER TABLE `finished_goods_movement`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `material_receipts`
--
ALTER TABLE `material_receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `production_plans`
--
ALTER TABLE `production_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `production_processes`
--
ALTER TABLE `production_processes`
  MODIFY `process_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quality_inspections`
--
ALTER TABLE `quality_inspections`
  MODIFY `inspection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `shipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `finished_goods_movement`
--
ALTER TABLE `finished_goods_movement`
  ADD CONSTRAINT `finished_goods_movement_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `finished_goods_movement_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `material_receipts`
--
ALTER TABLE `material_receipts`
  ADD CONSTRAINT `material_receipts_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `material_receipts_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`material_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `material_receipts_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `production_plans`
--
ALTER TABLE `production_plans`
  ADD CONSTRAINT `production_plans_ibfk_1` FOREIGN KEY (`planned_product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_plans_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `production_processes`
--
ALTER TABLE `production_processes`
  ADD CONSTRAINT `production_processes_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `production_plans` (`plan_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_processes_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_processes_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `quality_inspections`
--
ALTER TABLE `quality_inspections`
  ADD CONSTRAINT `quality_inspections_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `production_processes` (`process_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quality_inspections_ibfk_2` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD CONSTRAINT `fk_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD CONSTRAINT `sales_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipments_ibfk_2` FOREIGN KEY (`shipped_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
