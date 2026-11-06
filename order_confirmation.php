<?php
session_start();

require_once "db_connect.php";

/* Customer must be logged in */
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

/* Check that an order ID was provided */
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$order_id = (int) $_GET["id"];
$customer_id = $_SESSION["customer_id"];

/* Get the order */
$sql = "SELECT
            order_id,
            order_type,
            total_amount,
            status,
            order_date
        FROM orders
        WHERE order_id = ?
        AND customer_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $order_id,
    $customer_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$order = $result->fetch_assoc();

/* Get payment information */
$sql = "SELECT
            method,
            payment_status
        FROM payments
        WHERE order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();


/* Get ordered products */
$sql = "SELECT
            order_items.quantity,
            order_items.price,
            products.name
        FROM order_items
        INNER JOIN products
            ON order_items.product_id = products.product_id
        WHERE order_items.order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css?v=5">
    <link rel="stylesheet" href="css/order_confirmation.css">

</head>
<body>

    <!-- Navigation -->
   <?php include "navbar.php"; ?>

    <!-- Confirmation -->

    <main class="confirmation-page">

        <div class="confirmation-box">

            <div class="success-icon">
                ✓
            </div>

            <h1>
                Order Confirmed!
            </h1>

            <p class="confirmation-message">

                Thank you for ordering from Jeje Junctions.
                Your order has been received and is currently
                being processed.

            </p>

            <div class="order-number">

                <span>Order Number</span>

                <strong>
                    #<?php echo $order["order_id"]; ?>
                </strong>

            </div>

            <!-- Order Information -->
            <div class="order-information">

                <div>
                    <span>Order Type</span>

                    <strong>
                        <?php echo htmlspecialchars($order["order_type"]); ?>
                    </strong>

                </div>
                <div>

                    <span>Payment</span>

                    <strong>
                        <?php echo htmlspecialchars($payment["method"]); ?>
                    </strong>

                </div>
                <div>

                    <span>Payment Status</span>

                    <strong>
                        <?php echo htmlspecialchars($payment["payment_status"]); ?>
                    </strong>

                </div>
                <div>

                    <span>Order Status</span>
                    <strong>
                        <?php echo htmlspecialchars($order["status"]); ?>
                    </strong>

                </div>
            </div>

            <!-- Ordered Items -->
            <div class="ordered-items">

                <h2>Your Order</h2>

                <?php while ($item = $items_result->fetch_assoc()) { ?>

                    <div class="ordered-item">

                        <div>
                            <strong>
                                <?php echo htmlspecialchars($item["name"]); ?>
                            </strong>

                            <span>
                                x<?php echo $item["quantity"]; ?>
                            </span>
                        </div>

                        <strong>

                            R<?php echo number_format(
                                $item["price"] * $item["quantity"],
                                2
                            ); ?>

                        </strong>

                    </div>

                <?php } ?>

                <div class="order-total">

                    <span>Total</span>

                    <strong>
                        R<?php echo number_format(
                            $order["total_amount"],
                            2
                        ); ?>
                    </strong>
                </div>
            </div>

          
<!-- Collection & Payment Message -->

<div class="collection-message">

    <h2>Collection & Payment</h2>

    <?php if ($order["status"] === "Cancelled"): ?>

        <p>
            This order has been cancelled.
            Please contact Jeje Junctions if you need assistance.
        </p>

    <?php elseif (($payment["payment_status"] ?? "") === "Paid"): ?>

        <p>
            Your payment has been received successfully.
            Thank you for your payment.

            <?php if ($order["status"] === "Completed"): ?>
                Your order is marked as completed.
            <?php else: ?>
                Your order is being processed.
                Please check My Orders for updates.
            <?php endif; ?>
        </p>

    <?php else: ?>

        <p>
            Your payment is still pending.
            Please pay at Jeje Junctions when collecting
            your order using your selected payment method.
        </p>

    <?php endif; ?>

</div>


            <!-- Buttons -->
            <div class="confirmation-actions">

                <a href="menu.php" class="continue-button">
                    Continue Shopping
                </a>

                <a href="my_orders.php" class="orders-button">
                    View My Orders
                </a>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer>

        <h3>JEJE JUNCTIONS</h3>
        <p>Butchery & Grill</p>
        <p>  Good Meat. Great Taste. Real Experience.</p>
        <p>  &copy; 2026 Jeje Junctions. All rights reserved.
        </p>
    </footer>

<script src="js/script.js?v=5"></script>
</body>
</html>