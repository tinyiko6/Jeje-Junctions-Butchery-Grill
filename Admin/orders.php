<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}


/* Handle order status updates */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $order_id = filter_input(INPUT_POST, "order_id", FILTER_VALIDATE_INT);
    $status = $_POST["status"] ?? "";

    $allowed_statuses = [
        "Pending",
        "Preparing",
        "Completed",
        "Cancelled"
    ];

    if (!$order_id || !in_array($status, $allowed_statuses, true)) {
        header("Location: orders.php");
        exit();
    }

    try {

        /* Start a transaction so stock and status stay consistent */
        $conn->begin_transaction();

        /* Lock the order while checking its current status */
        $sql = "SELECT status, stock_restored
                FROM orders
                WHERE order_id = ?
                FOR UPDATE";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();

        if (!$order) {
            throw new Exception("Order not found.");
        }
        
/* Prevent cancelled orders from being reactivated */
if (
    $order["status"] === "Cancelled" &&
    $status !== "Cancelled"
) {

    throw new Exception(
        "Cancelled orders cannot be reactivated."
    );

}

/* Prevent cancellation of orders that have already been paid */
if (
    $status === "Cancelled" &&
    $order["status"] !== "Cancelled"
) {

    /* Check and lock the payment record */
    $sql = "SELECT payment_status
            FROM payments
            WHERE order_id = ?
            FOR UPDATE";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    /* Paid orders must not be cancelled */
    if (
        $payment &&
        $payment["payment_status"] === "Paid"
    ) {

        throw new Exception(
            "Paid orders cannot be cancelled. A refund must be handled first."
        );

    }
}

        /* Restore stock only when cancelling for the first time */
        if (
            $status === "Cancelled" &&
            $order["status"] !== "Cancelled" &&
            (int) $order["stock_restored"] === 0
        ) {

            /* Get the products and quantities in this order */
            $sql = "SELECT product_id, quantity
                    FROM order_items
                    WHERE order_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            $items = $stmt->get_result();

            while ($item = $items->fetch_assoc()) {

                $product_id = (int) $item["product_id"];
                $quantity = (int) $item["quantity"];

                /* Return the ordered quantity to inventory */
                $stock_sql = "UPDATE products
                              SET stock_quantity = stock_quantity + ?
                              WHERE product_id = ?";

                $stock_stmt = $conn->prepare($stock_sql);
                $stock_stmt->bind_param(
                    "ii",
                    $quantity,
                    $product_id
                );
                $stock_stmt->execute();
                $stock_stmt->close();
            }

            $stmt->close();

            /* Record that stock has been restored */
            $sql = "UPDATE orders
                    SET stock_restored = 1
                    WHERE order_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
        }

        /* Update the order status */
        $sql = "UPDATE orders
                SET status = ?
                WHERE order_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        $stmt->close();

        /* Save all changes together */
        $conn->commit();

        /* Show a success message */
    $_SESSION["order_message"] =
    "Order status updated successfully.";

    $_SESSION["order_message_type"] = "success";

   } catch (Throwable $e) {

    /* Undo partial changes if anything fails */
    $conn->rollback();

    error_log("Order status update failed: " . $e->getMessage());

    /* Display a helpful error message */
    if ($e->getMessage() ===
        "Paid orders cannot be cancelled. A refund must be handled first.") {

        $_SESSION["order_message"] =
            "This order has already been paid for and cannot be cancelled.";

    } else {

        $_SESSION["order_message"] =
            "Unable to update the order. Please try again.";

    }

    $_SESSION["order_message_type"] = "error";
}

    header("Location: orders.php");
    exit();
}

$sql = "SELECT
            orders.order_id,
            orders.order_type,
            orders.total_amount,
            orders.status,
            orders.order_date,
            customers.name AS customer_name,
            payments.method AS payment_method,
            payments.payment_status
        FROM orders
        INNER JOIN customers
            ON orders.customer_id = customers.customer_id
        LEFT JOIN payments
            ON orders.order_id = payments.order_id
        ORDER BY orders.order_date DESC";

$orders = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Jeje Junctions Admin</title>

    <!-- Orders page styling -->
    <link rel="stylesheet" href="css/orders.css">

    <!-- Shared sidebar and top bar styling -->
    <link rel="stylesheet" href="css/admin-shared.css">
</head>

<body>

<div class="admin-layout">

    <!-- Shared admin sidebar -->
    <?php require __DIR__ . "/sidebar.php"; ?>

    <!-- Main content -->
    <main class="main-content">

        <!-- Shared admin top bar -->
        <?php require __DIR__ . "/topbar.php"; ?>

  <!-- Orders management section -->

<section class="orders-content">

    <!-- Order feedback message -->
    <?php if (isset($_SESSION["order_message"])): ?>

        <div class="order-alert <?php
            echo ($_SESSION["order_message_type"] ?? "") === "success"
                ? "alert-success"
                : "alert-error";
        ?>">

            <?php
                echo htmlspecialchars($_SESSION["order_message"]);
            ?>

        </div>

        <?php
            /* Remove message after displaying it */
            unset(
                $_SESSION["order_message"],
                $_SESSION["order_message_type"]
            );
        ?>

    <?php endif; ?>

    <div class="orders-card">


                <?php if ($orders && $orders->num_rows > 0): ?>

                    <div class="orders-table-wrapper">
                        <table class="orders-table">

        <thead>
         <tr>
          <th>ORDER</th>
          <th>CUSTOMER</th>
          <th>TYPE</th>
          <th>TOTAL</th>
          <th>PAYMENT</th>
          <th>STATUS</th>
          <th>DATE</th>
          <th>DETAILS</th>
          <th>UPDATE</th>
          </tr>
     </thead>

       <tbody>

<?php while ($order = $orders->fetch_assoc()): ?>

    <tr>
        <td><strong>#<?php echo $order["order_id"]; ?></strong></td>

        <td><?php echo htmlspecialchars($order["customer_name"]); ?></td>

        <td><?php echo htmlspecialchars($order["order_type"]); ?></td>

        <td>R<?php echo number_format($order["total_amount"], 2); ?></td>

        <td>
            <?php if ($order["payment_method"]): ?>
                <?php echo htmlspecialchars($order["payment_method"]); ?>
                <small class="payment-status">
                    <?php echo htmlspecialchars($order["payment_status"]); ?>
                </small>
            <?php else: ?>
                <span class="not-available">N/A</span>
            <?php endif; ?>
        </td>

        <td>
            <span class="status <?php echo strtolower($order["status"]); ?>">
                <?php echo htmlspecialchars($order["status"]); ?>
            </span>
        </td>

        <td>
            <?php echo date("d M Y", strtotime($order["order_date"])); ?>
        </td>
        
    <!-- Open the selected order to view its products -->
     <td>
    <a
        href="order_details.php?id=<?php echo (int) $order["order_id"]; ?>"
        class="view-details-btn"
    >
        View Details
    </a>
     </td>


        <td>
       
<?php if ($order["status"] === "Cancelled"): ?>

    <!-- Cancelled orders cannot be updated -->
    <span class="cancelled-label">
        Order Cancelled
    </span>

<?php else: ?>

    <!-- Allow status updates for active orders -->
    <form method="POST" class="status-form">

        <input
            type="hidden"
            name="order_id"
            value="<?php echo (int) $order["order_id"]; ?>"
        >

        <select name="status">

           
<?php

/* Paid orders cannot be cancelled */
$status_options = ["Pending","Preparing","Completed"];

/* Allow cancellation only when payment is not Paid */
if ($order["payment_status"] !== "Paid") {
    $status_options[] = "Cancelled";
}

foreach ($status_options as $option):

?>


                <option
                    value="<?php echo htmlspecialchars($option); ?>"
                    <?php echo $order["status"] === $option ? "selected" : ""; ?>
                >
                    <?php echo htmlspecialchars($option); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <button type="submit">Update</button>

    </form>

<?php endif; ?>

        </td>
    </tr>

<?php endwhile; ?>

</tbody>

                        </table>
                    </div>

                <?php else: ?>

                    <div class="no-orders">
                        <p>No orders have been placed yet.</p>
                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>
</html>