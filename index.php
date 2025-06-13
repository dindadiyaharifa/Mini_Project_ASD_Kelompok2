<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$comparison_count = 0;
$swap_count = 0;
$shift_count = 0;
$search_comparison_count = 0;
$isSorted = false;
$sortType = "";
$sortedProducts = array();
$cpu_time_used = 0;
$sortingProcess = array();
$sortingSteps = array();

function resetOperationCounts() {
    global $comparison_count, $swap_count, $shift_count;
    $comparison_count = 0;
    $swap_count = 0;
    $shift_count = 0;
}

function printProcessState($products, $message) {
    $prices = array();
    foreach ($products as $product) {
        $prices[] = $product['price'];
    }
    return $message . ": [" . implode(", ", $prices) . "]";
}

function bubbleSort($products) {
    global $comparison_count, $swap_count, $sortingProcess, $sortingSteps;
    $n = count($products);
    resetOperationCounts();
    $sortingProcess = array();
    $sortingSteps = array();
    
    $initialState = array();
    foreach ($products as $product) {
        $initialState[] = $product['price'];
    }
    $sortingSteps[] = $initialState;
    $sortingProcess[] = printProcessState($products, "Array Awal");
    
    for ($i = 0; $i < $n - 1; $i++) {
        $swapped = false;
        for ($j = 0; $j < $n - $i - 1; $j++) {
            $comparison_count++;
            if ($products[$j]['price'] > $products[$j + 1]['price']) {
                $temp = $products[$j];
                $products[$j] = $products[$j + 1];
                $products[$j + 1] = $temp;
                $swap_count++;
                $swapped = true;
                
                $currentState = array();
                foreach ($products as $product) {
                    $currentState[] = $product['price'];
                }
                $sortingSteps[] = $currentState;
            }
        }
        if ($swapped) {
            $sortingProcess[] = printProcessState($products, "Setelah Pass " . ($i + 1));
        } else {
            $sortingProcess[] = "Pass " . ($i + 1) . ": Tidak ada pertukaran. Array sudah terurut berdasarkan harga.";
            break;
        }
    }
    return $products;
}

function shellSort($products) {
    global $comparison_count, $shift_count, $sortingProcess, $sortingSteps;
    $n = count($products);
    resetOperationCounts();
    $sortingProcess = array();
    $sortingSteps = array();
    
    $initialState = array();
    foreach ($products as $product) {
        $initialState[] = $product['price'];
    }
    $sortingSteps[] = $initialState;
    $sortingProcess[] = printProcessState($products, "Array Awal");
    
    for ($gap = floor($n / 2); $gap > 0; $gap = floor($gap / 2)) {
        for ($i = $gap; $i < $n; $i++) {
            $temp = $products[$i];
            $j = $i;
            while ($j >= $gap && $products[$j - $gap]['price'] > $temp['price']) {
                $comparison_count++;
                $products[$j] = $products[$j - $gap];
                $shift_count++;
                $j -= $gap;
                
                $currentState = array();
                foreach ($products as $product) {
                    $currentState[] = $product['price'];
                }
                $sortingSteps[] = $currentState;
            }
            $products[$j] = $temp;
            
            $currentState = array();
            foreach ($products as $product) {
                $currentState[] = $product['price'];
            }
            $sortingSteps[] = $currentState;
        }
        $sortingProcess[] = printProcessState($products, "Setelah Gap = " . $gap);
    }
    return $products;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['product_name'];
        $price = $_POST['product_price'];
        
        $stmt = $conn->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
        $stmt->bind_param("sd", $name, $price);
        $stmt->execute();
        $stmt->close();
        $isSorted = false;
    }
    
    if (isset($_FILES['product_file']) && $_FILES['product_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['product_file']['tmp_name'];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) == 2) {
                $name = trim($parts[0]);
                $price = trim($parts[1]);
                
                if (is_numeric($price)) {
                    $stmt = $conn->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
                    $stmt->bind_param("sd", $name, $price);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        $isSorted = false;
    }
    
    if (isset($_POST['bubble_sort'])) {
        $result = $conn->query("SELECT * FROM products");
        $products = $result->fetch_all(MYSQLI_ASSOC);
        
        $start_time = microtime(true);
        $sortedProducts = bubbleSort($products);
        $end_time = microtime(true);
        
        $cpu_time_used = $end_time - $start_time;
        $isSorted = true;
        $sortType = "Bubble Sort";
    }
    
    if (isset($_POST['shell_sort'])) {
        $result = $conn->query("SELECT * FROM products");
        $products = $result->fetch_all(MYSQLI_ASSOC);
        
        $start_time = microtime(true);
        $sortedProducts = shellSort($products);
        $end_time = microtime(true);
        
        $cpu_time_used = $end_time - $start_time;
        $isSorted = true;
        $sortType = "Shell Sort";
    }
    
    if (isset($_POST['search_price'])) {
        $targetPrice = $_POST['search_price'];
        $result = $conn->query("SELECT * FROM products WHERE price = $targetPrice");
        $priceResults = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    if (isset($_POST['search_name'])) {
        $targetName = $_POST['search_name'];
        $result = $conn->query("SELECT * FROM products WHERE name LIKE '%$targetName%'");
        $nameResults = $result->fetch_all(MYSQLI_ASSOC);
    }
}

$result = $conn->query("SELECT * FROM products");
$allProducts = $result->fetch_all(MYSQLI_ASSOC);
$productCount = count($allProducts);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
        }
        
        body {
            background-color: #f5f7ff;
            font-family: 'Poppins', sans-serif;
            color: var(--dark-color);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            font-size: 1.5rem;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 24px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 16px 24px;
            font-weight: 600;
        }
        
        .stats-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .search-result {
            background-color: white;
            border-left: 4px solid var(--primary-color);
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        
        .time-badge {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--accent-color);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
        
        .process-step {
            background-color: white;
            border-left: 4px solid var(--accent-color);
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .alert-status {
            border-left: 4px solid var(--warning-color);
        }
        
        .alert-status.alert-success {
            border-left-color: var(--success-color);
        }
        
        .product-count-badge {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 16px;
            border: 1px solid #e9ecef;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        
        .process-diagram {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .process-diagram h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 20px;
        }
        
        .animation-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .animation-btn {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            border: none;
            background-color: var(--primary-color);
            color: white;
            cursor: pointer;
        }
        
        .animation-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .step-indicator {
            text-align: center;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .upload-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .speed-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .speed-control label {
            margin-bottom: 0;
            font-weight: 500;
        }
        
        .speed-control input {
            width: 100px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">E-Commerce Product Manager</a>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="section-title">Manajemen Produk</h2>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">Tambah Produk Baru</h4>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <span class="product-count-badge">
                                        Jumlah Produk: <?php echo $productCount; ?>/50
                                    </span>
                                </div>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Nama Produk</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="product_price" class="form-label">Harga</label>
                                        <input type="number" step="0.01" class="form-control" id="product_price" name="product_price" required>
                                    </div>
                                    <button type="submit" name="add_product" class="btn btn-primary w-100 mb-3">Tambah Produk</button>
                                    
                                    <div class="upload-area" onclick="document.getElementById('file-upload').click()">
                                        <div class="upload-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                                            </svg>
                                        </div>
                                        <h5>Atau unggah file teks</h5>
                                        <p class="text-muted">Format: namaproduk|harga (satu produk per baris)</p>
                                        <input type="file" id="file-upload" name="product_file" accept=".txt" style="display: none;" onchange="this.form.submit()">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">Daftar Semua Produk</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nama Produk</th>
                                                <th>Harga</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allProducts as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td>Rp<?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h2 class="section-title mt-5">Pengurutan Produk</h2>
                
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-status <?php echo $isSorted ? 'alert-success' : 'alert-warning'; ?> mb-4">
                            <strong>Status data:</strong> 
                            <?php echo $isSorted ? 'TERURUT (berdasarkan Harga)' : 'BELUM TERURUT (Harap urutkan terlebih dahulu!)'; ?>
                        </div>
                        
                        <div class="d-flex gap-3 mb-4">
                            <form method="POST" class="flex-grow-1">
                                <button type="submit" name="bubble_sort" class="btn btn-primary w-100 py-3">
                                    Bubble Sort (Harga)
                                </button>
                            </form>
                            <form method="POST" class="flex-grow-1">
                                <button type="submit" name="shell_sort" class="btn btn-primary w-100 py-3">
                                    Shell Sort (Harga)
                                </button>
                            </form>
                        </div>
                        
                        <?php if ($isSorted): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Hasil <?php echo $sortType; ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <div class="stat-label">Perbandingan</div>
                                            <div class="stat-value"><?php echo $comparison_count; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <div class="stat-label">Pertukaran</div>
                                            <div class="stat-value"><?php echo $swap_count; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <div class="stat-label">Pergeseran</div>
                                            <div class="stat-value"><?php echo $shift_count; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <div class="stat-label">Waktu Eksekusi</div>
                                            <div class="stat-value"><?php echo number_format($cpu_time_used, 6); ?>s</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="process-diagram mb-4">
                                    <h5>Visualisasi Proses <?php echo $sortType; ?></h5>
                                    <div class="step-indicator">
                                        Langkah: <span id="current-step">0</span>/<span id="total-steps"><?php echo count($sortingSteps) - 1; ?></span>
                                    </div>
                                    <div class="animation-controls">
                                        <button class="animation-btn" id="prev-step">Sebelumnya</button>
                                        <button class="animation-btn" id="play-pause">Mainkan</button>
                                        <button class="animation-btn" id="next-step">Selanjutnya</button>
                                        <button class="animation-btn" id="reset-animation">Reset</button>
                                    </div>
                                    <div class="speed-control">
                                        <label for="animation-speed">Kecepatan:</label>
                                        <input type="range" id="animation-speed" min="100" max="2000" step="100" value="1000">
                                        <span id="speed-value">1.0x</span>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="sorting-chart"></canvas>
                                    </div>
                                </div>
                                
                                <h5 class="mb-3">Proses Pengurutan:</h5>
                                <div class="mb-4">
                                    <?php foreach ($sortingProcess as $step): ?>
                                        <div class="process-step"><?php echo $step; ?></div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <h5 class="mb-3">Hasil Akhir:</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nama Produk</th>
                                                <th>Harga</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sortedProducts as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td>Rp<?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h2 class="section-title mt-5">Pencarian Produk</h2>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Pencarian Harga (Binary Search)</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="search_price" class="form-label">Cari berdasarkan Harga</label>
                                        <input type="number" step="0.01" class="form-control" id="search_price" name="search_price" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                                </form>
                                
                                <?php if (isset($priceResults)): ?>
                                <div class="mt-4">
                                    <h6>Hasil Pencarian</h6>
                                    <?php if (count($priceResults) > 0): ?>
                                        <?php foreach ($priceResults as $result): ?>
                                        <div class="search-result">
                                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($result['name']); ?></p>
                                            <p><strong>Harga:</strong> Rp<?php echo number_format($result['price'], 0, ',', '.'); ?></p>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">Tidak ditemukan produk dengan harga tersebut.</div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Pencarian Nama (Linear Search)</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="search_name" class="form-label">Cari berdasarkan Nama</label>
                                        <input type="text" class="form-control" id="search_name" name="search_name" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                                </form>
                                
                                <?php if (isset($nameResults)): ?>
                                <div class="mt-4">
                                    <h6>Hasil Pencarian</h6>
                                    <?php if (count($nameResults) > 0): ?>
                                        <?php foreach ($nameResults as $result): ?>
                                        <div class="search-result">
                                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($result['name']); ?></p>
                                            <p><strong>Harga:</strong> Rp<?php echo number_format($result['price'], 0, ',', '.'); ?></p>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">Tidak ditemukan produk dengan nama tersebut.</div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if ($isSorted && !empty($sortingSteps)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const steps = <?php echo json_encode($sortingSteps); ?>;
            const ctx = document.getElementById('sorting-chart').getContext('2d');
            const labels = steps[0].map((_, index) => `Item ${index + 1}`);
            
            let currentStep = 0;
            let animationInterval = null;
            let isPlaying = false;
            let animationSpeed = 1000;
            
            document.getElementById('total-steps').textContent = steps.length - 1;
            
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Harga Produk',
                        data: steps[0],
                        backgroundColor: 'rgba(67, 97, 238, 0.7)',
                        borderColor: 'rgba(67, 97, 238, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Harga'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Produk'
                            }
                        }
                    },
                    animation: {
                        duration: 0
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Harga: Rp' + context.raw.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
            
            function updateChart(stepIndex) {
                if (stepIndex >= 0 && stepIndex < steps.length) {
                    currentStep = stepIndex;
                    chart.data.datasets[0].data = steps[currentStep];
                    chart.update();
                    document.getElementById('current-step').textContent = currentStep;
                    
                    if (currentStep > 0) {
                        const prevData = steps[currentStep - 1];
                        const currentData = steps[currentStep];
                        const changedIndices = [];
                        
                        for (let i = 0; i < prevData.length; i++) {
                            if (prevData[i] !== currentData[i]) {
                                changedIndices.push(i);
                            }
                        }
                        
                        chart.data.datasets[0].backgroundColor = steps[currentStep].map((_, index) => {
                            return changedIndices.includes(index) ? 'rgba(248, 150, 30, 0.7)' : 'rgba(67, 97, 238, 0.7)';
                        });
                    } else {
                        chart.data.datasets[0].backgroundColor = 'rgba(67, 97, 238, 0.7)';
                    }
                    
                    chart.update();
                }
            }
            
            document.getElementById('prev-step').addEventListener('click', function() {
                if (currentStep > 0) {
                    updateChart(currentStep - 1);
                }
                if (isPlaying) {
                    clearInterval(animationInterval);
                    isPlaying = false;
                    document.getElementById('play-pause').textContent = 'Mainkan';
                }
            });
            
            document.getElementById('next-step').addEventListener('click', function() {
                if (currentStep < steps.length - 1) {
                    updateChart(currentStep + 1);
                }
                if (isPlaying) {
                    clearInterval(animationInterval);
                    isPlaying = false;
                    document.getElementById('play-pause').textContent = 'Mainkan';
                }
            });
            
            document.getElementById('play-pause').addEventListener('click', function() {
                if (isPlaying) {
                    clearInterval(animationInterval);
                    isPlaying = false;
                    this.textContent = 'Mainkan';
                } else {
                    isPlaying = true;
                    this.textContent = 'Jeda';
                    
                    if (currentStep >= steps.length - 1) {
                        currentStep = -1;
                    }
                    
                    animationInterval = setInterval(function() {
                        if (currentStep < steps.length - 1) {
                            updateChart(currentStep + 1);
                        } else {
                            clearInterval(animationInterval);
                            isPlaying = false;
                            document.getElementById('play-pause').textContent = 'Mainkan';
                        }
                    }, animationSpeed);
                }
            });
            
            document.getElementById('reset-animation').addEventListener('click', function() {
                clearInterval(animationInterval);
                isPlaying = false;
                document.getElementById('play-pause').textContent = 'Mainkan';
                updateChart(0);
            });
            
            document.getElementById('animation-speed').addEventListener('input', function() {
                animationSpeed = 2100 - this.value;
                const speedMultiplier = (2000 - (this.value - 100)) / 1000;
                document.getElementById('speed-value').textContent = speedMultiplier.toFixed(1) + 'x';
                
                if (isPlaying) {
                    clearInterval(animationInterval);
                    animationInterval = setInterval(function() {
                        if (currentStep < steps.length - 1) {
                            updateChart(currentStep + 1);
                        } else {
                            clearInterval(animationInterval);
                            isPlaying = false;
                            document.getElementById('play-pause').textContent = 'Mainkan';
                        }
                    }, animationSpeed);
                }
            });
            
            updateChart(0);
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
$conn->close();
?>