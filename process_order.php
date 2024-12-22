<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'terangbulanmini';
$username = 'root';
$password = '';

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Test connection (optional)
    $stmt = $pdo->query("SELECT 1");

    // If the form is submitted (POST request)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate input
        $required_fields = ['customer_name', 'customer_phone', 'product_id', 'quantity', 'total_price'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Semua field harus diisi");
            }
        }

        // Sanitize input
        $customer_name = htmlspecialchars(trim($_POST['customer_name']));
        $customer_phone = htmlspecialchars(trim($_POST['customer_phone']));
        $product_id = (int) $_POST['product_id'];  // Ensure product_id is an integer
        $quantity = (int) $_POST['quantity'];  // Ensure quantity is an integer
        $total_price = (float) $_POST['total_price'];  // Ensure total_price is a float

        // Prepare statement for insert into orders
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                customer_name, 
                customer_phone, 
                product_id, 
                quantity, 
                total_price,
                order_date
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        // Execute with parameters
        $stmt->execute([$customer_name, $customer_phone, $product_id, $quantity, $total_price]);

        // Get the ID of the newly created order
        $orderId = $pdo->lastInsertId();

        // Get order details for display, including product name
        $stmt = $pdo->prepare("
            SELECT o.*, p.product_name, p.price
            FROM orders o
            JOIN products p ON o.product_id = p.product_id
            WHERE o.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Gagal mengambil detail pesanan");
        }

        // Store order data in session or display it to the user
        $_SESSION['order'] = $order;

    } else {
        // If not a POST request, redirect to the order page
        header("Location: order.html");
        exit();
    }

} catch (Exception $e) {
    // Set error message in session and redirect
    $_SESSION['errors'] = [$e->getMessage()];
    header("Location: order.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Terang Bulan Mini</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .success-icon {
            background: #4CAF50;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -40px auto 20px;
            border: 6px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .success-icon i {
            font-size: 36px;
            color: white;
        }

        .order-details {
            padding: 30px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .label {
            color: #666;
            font-weight: 500;
        }

        .value {
            color: #333;
            font-weight: 600;
            text-align: right;
        }

        .total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .total .label, .total .value {
            font-size: 1.2em;
            color: #333;
            font-weight: 700;
        }

        .buttons {
            padding: 20px 30px;
            background: #f8f9fa;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
        }

        .btn-outline {
            border: 2px solid #667eea;
            color: #667eea;
            background: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 12px;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }

            .value {
                text-align: left;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Terang Bulan Mini</h1>
        </div>
        
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <div class="order-details">
            <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Pesanan Berhasil!</h2>
            
            <div class="detail-row">
                <span class="label">Nama Pelanggan</span>
                <span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Nomor Telepon</span>
                <span class="value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Produk</span>
                <span class="value"><?php echo htmlspecialchars($order['product_name']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Harga Satuan</span>
                <span class="value">Rp <?php echo number_format($order['price'], 2, ',', '.'); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Jumlah</span>
                <span class="value"><?php echo htmlspecialchars($order['quantity']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Tanggal Pesanan</span>
                <span class="value"><?php echo date("d-m-Y H:i:s", strtotime($order['order_date'])); ?></span>
            </div>
            
            <div class="detail-row total">
                <span class="label">Total Pembayaran</span>
                <span class="value">Rp <?php echo number_format($order['total_price'], 2, ',', '.'); ?></span>
            </div>
        </div>
        
        <div class="buttons">
            <a href="order.html" class="btn btn-outline">Pesan Lagi</a>
            <a href="#" class="btn btn-primary" onclick="window.print()">Cetak Bukti</a>
        </div>
    </div>
</body>
</html>