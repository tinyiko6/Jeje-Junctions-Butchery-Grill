<?php
session_start();
require_once "db_connect.php";

// Make sure the customer is logged in
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION["customer_id"];

// Get the customer's previous orders
$sql = "SELECT 
            orders.order_id,
            orders.order_type,
            orders.total_amount,
            orders.status,
            orders.order_date,
            payments.method,
            payments.payment_status
        FROM orders
        LEFT JOIN payments
            ON orders.order_id = payments.order_id
        WHERE orders.customer_id = ?
        ORDER BY orders.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css?v=5">
    <link rel="stylesheet" href="css/my_orders.css">

</head>

<body>

<?php include "navbar.php"; ?>

<main class="orders-page">

    <h1>My Orders</h1>

    <p class="orders-intro">
        View your previous orders and check their current status.
    </p>


    <?php if ($result->num_rows === 0) { ?>

        <section class="no-orders">

            <h2>No Orders Yet</h2>

            <p>
                You have not placed any orders yet.
                Browse our menu and place your first order.
            </p>

            <a href="menu.php" class="shop-button">
                Browse Menu
            </a>

        </section>

    <?php } else { ?>

        <section class="orders-list">

            <?php while ($order = $result->fetch_assoc()) { ?>

                <article class="order-card">

                    <div class="order-header">

                        <div>

                            <span class="order-label">
                                Order Number
                            </span>

                            <h2>
                                #<?php echo $order["order_id"]; ?>
                            </h2>

                        </div>

                        <span class="order-status">
                            <?php echo htmlspecialchars($order["status"]); ?>
                        </span>

                    </div>


                    <div class="order-details">

                        <div>

                            <span>Order Date</span>

                            <strong>
                                <?php
                                echo date(
                                    "d M Y, H:i",
                                    strtotime($order["order_date"])
                                );
                                ?>
                            </strong>

                        </div>


                        <div>

                            <span>Order Type</span>

                            <strong>
                                <?php echo htmlspecialchars($order["order_type"]); ?>
                            </strong>

                        </div>


                        <div>

                            <span>Total</span>

                            <strong>
                                R<?php echo number_format($order["total_amount"], 2); ?>
                            </strong>

                        </div>


                        <div>

                            <span>Payment Method</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $order["method"] ?? "Not available"
                                );
                                ?>
                            </strong>

                        </div>


                        <div>

                            <span>Payment Status</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $order["payment_status"] ?? "Pending"
                                );
                                ?>
                            </strong>

                        </div>

                    </div>


                    <div class="order-actions">

                        <a
                            href="order_confirmation.php?id=<?php echo $order["order_id"]; ?>"
                            class="view-button"
                        >
                            View Details
                        </a>

                    </div>

                </article>

            <?php } ?>

        </section>

    <?php } ?>

</main>


<footer>

    <h3>JEJE JUNCTIONS</h3>
    <p>Butchery & Grill</p>
    <p>Good Meat. Great Taste. Real Experience.</p>
    <p>&copy; 2026 Jeje Junctions. All rights reserved.</p>

</footer>

<script src="js/script.js?v=5"></script>
</body>

</html>