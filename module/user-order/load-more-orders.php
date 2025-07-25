<?php
require_once __DIR__ . '/../../db/connect.php';
session_start();

if (!isset($_SESSION['username'])) exit;

$username = $_SESSION['username'];
$stmtUser = $conn->prepare("SELECT id FROM site_user WHERE username = ?");
$stmtUser->bind_param("s", $username);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
if (!$user) exit;

$userId = $user['id'];
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 3;

// Bước 1: Lấy danh sách bill_id với phân trang chính xác
$sqlBills = "SELECT id FROM bill WHERE user_id = ? ORDER BY order_date DESC LIMIT ? OFFSET ?";
$stmtBills = $conn->prepare($sqlBills);
$stmtBills->bind_param("iii", $userId, $limit, $offset);
$stmtBills->execute();
$resultBills = $stmtBills->get_result();

$billIds = [];
while ($row = $resultBills->fetch_assoc()) {
    $billIds[] = $row['id'];
}

// Nếu không có đơn hàng nào, trả về empty
if (empty($billIds)) {
    if ($offset === 0) {
        echo '<div class="container-fluid vh-50 d-flex align-items-center justify-content-center" data-empty="true">
                <div class="text-center py-5">
                    <div class="mb-4"><i class="fas fa-search fa-3x text-muted"></i></div>
                    <h4 class="text-muted mb-3">No orders yet !</h4>
                    <p class="text-muted mb-4">Please place an order to see it here.</p>
                    <a href="#" onclick="loadPage(\'module/product/product.php\', this, \'products\'); return false;" class="btn btn-dark rounded-pill px-4">Shop now</a>
                </div>
              </div>';
    } else {
        echo '';
    }
    exit;
}

// Bước 2: Lấy chi tiết đơn hàng cho các bill_id đã chọn
$placeholders = str_repeat('?,', count($billIds) - 1) . '?';
$sql = "SELECT b.id AS bill_id, b.order_date, b.order_total, b.order_status, b.order_name, b.order_phone, b.order_address, b.order_paymethod,
               c.product_name, c.product_image, c.price, c.quantity, c.total
        FROM bill b
        JOIN checkout_cart c ON b.id = c.bill_id
        WHERE b.id IN ($placeholders)
        ORDER BY b.order_date DESC, c.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($billIds)), ...$billIds);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $billId = $row['bill_id'];
    if (!isset($orders[$billId])) {
        $orders[$billId] = [
            'order_date' => $row['order_date'],
            'order_total' => $row['order_total'],
            'order_status' => $row['order_status'],
            'order_name' => $row['order_name'],
            'order_phone' => $row['order_phone'],
            'order_address' => $row['order_address'],
            'order_paymethod' => $row['order_paymethod'],
            'items' => []
        ];
    }
    $orders[$billId]['items'][] = [
        'product_name' => $row['product_name'],
        'product_image' => $row['product_image'],
        'price' => $row['price'],
        'quantity' => $row['quantity'],
        'total' => $row['total']
    ];
}

foreach ($orders as $billId => $order): ?>
    <div class="border rounded-4 mb-5 p-4 shadow-sm">
        <h5 class="mb-3 text-success">Order #<?= $billId ?> <span class="text-muted">(<?= $order['order_date'] ?>)</span></h5>
        <ul class="list-group mb-4">
            <li class="list-group-item"><strong>Recipient:</strong> <?= htmlspecialchars($order['order_name']) ?></li>
            <li class="list-group-item"><strong>Phone:</strong> <?= htmlspecialchars($order['order_phone']) ?></li>
            <li class="list-group-item"><strong>Address:</strong> <?= htmlspecialchars($order['order_address']) ?></li>
            <li class="list-group-item"><strong>Payment Method:</strong> <?= $order['order_paymethod'] == 1 ? 'Online by PayOS' : 'Cash on Delivery' ?></li>
            <li class="list-group-item"><strong>Status:</strong>
                <?php
                $status = $order['order_status'];
                $status_text = 'Pending';
                $status_class = 'warning';

                // Handle both string and numeric status values
                if ($status === 'Completed' || $status == 1) {
                    $status_text = 'Completed';
                    $status_class = 'success';
                } elseif ($status === 'Shipping') {
                    $status_text = 'Shipping';
                    $status_class = 'info';
                } elseif ($status === 'Cancelled' || $status == 2) {
                    $status_text = 'Cancelled';
                    $status_class = 'danger';
                }
                ?>
                <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
            </li>
        </ul>

        <h6 class="mb-3 fw-semibold">Products</h6>
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th class="text-center" style="width: 100px;">Qty</th>
                    <th class="text-end" style="width: 120px;">Price</th>
                    <th class="text-end" style="width: 120px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($item['product_name']) ?><br>
                            <img src="img/<?= htmlspecialchars($item['product_image']) ?>" alt="" width="80">
                        </td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end">$<?= number_format($item['price'], 2) ?></td>
                        <td class="text-end">$<?= number_format($item['total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <?php if ($status === 'Pending' || $status === null || $status === ''): ?>
                    <button class="btn btn-outline-dark btn-md rounded-pill" onclick="cancelOrder(<?= $billId ?>)" 
                            title="Cancel this order">Cancel Order
                    </button>
                <?php endif; ?>
            </div>
            <div>
                <strong>Total:</strong> <span class="fs-5 text-success fw-bold">$<?= number_format($order['order_total'], 2) ?></span>
            </div>
        </div>
    </div>
<?php endforeach; ?>