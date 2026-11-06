<?php

session_start();
require_once "../db_connect.php";


/* Allow only logged-in administrators */
if (!isset($_SESSION["admin_id"])) {

    header("Location: login.php");
    exit();
}

/* Check that a valid order ID was provided */
$order_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$order_id || $order_id < 1) {

    header("Location: orders.php");
    exit();
}


/* Get the customer's order information */
$sql = "SELECT
            orders.order_id,
            orders.order_type,
            orders.total_amount,
            orders.status,
            orders.order_date,
            customers.name AS customer_name
        FROM orders
        INNER JOIN customers
            ON orders.customer_id = customers.customer_id
        WHERE orders.order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();
$stmt->close();


/* Return to orders if the order does not exist */
if (!$order) {

    header("Location: orders.php");
    exit();
}


/* Get all products belonging to the order */
$sql = "SELECT
            products.name,
            order_items.quantity,
            order_items.price,
            order_items.customisation
        FROM order_items
        INNER JOIN products
            ON order_items.product_id = products.product_id
        WHERE order_items.order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();

$items = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Details | Jeje Junctions</title>

    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="css/order_details.css">

</head>

<body>

<div class="admin-layout">

    <!-- Admin navigation -->
    <aside class="sidebar">

        <div class="sidebar-logo">
            <img src="../images/jeje-logo.jpeg" alt="Jeje Junctions">
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="orders.php" class="active">Orders</a>
            <a href="customers.php">Customers</a>
            <a href="site_content.php">Site Content</a>
            <a href="logout.php" class="logout-link">Logout</a>
        </nav>

    </aside>


    <main class="main-content">

        <!-- Page heading -->
        <header class="top-bar">

            <div class="welcome-text">
                <h2>Order #<?php echo (int) $order["order_id"]; ?></h2>
                <p>View customer order information.</p>
            </div>

            <div class="admin-role">Administrator</div>

        </header>


        <section class="orders-content">

            <div class="page-header">
                <div>
                    <h1>Order Details</h1>
                    <p>
                        Customer:
                        <?php echo htmlspecialchars($order["customer_name"]); ?>
                    </p>
                </div>

                <a href="orders.php" class="back-link">
                ← Back to Orders

               </a>
            </div>


            <!-- Order summary -->
            <div class="orders-card">

                <h2>Order Information</h2>

                <p>
                    <strong>Order Type:</strong>
                    <?php echo htmlspecialchars($order["order_type"]); ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?php echo htmlspecialchars($order["status"]); ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?php echo date("d M Y", strtotime($order["order_date"])); ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    R<?php echo number_format($order["total_amount"], 2); ?>
                </p>

            </div>


            <!-- Products and customisation choices -->
            <div class="orders-card">

                <h2>Ordered Products</h2>

                <?php while ($item = $items->fetch_assoc()): ?>

                    <div class="order-product">

                        <h3>
                            <?php echo htmlspecialchars($item["name"]); ?>
                        </h3>

                        <p>
                            Quantity:
                            <?php echo (int) $item["quantity"]; ?>
                        </p>

                        <p>
                            Price:
                            R<?php echo number_format($item["price"], 2); ?>
                        </p>

                        <?php

    /* Display the customer's saved meal customisation choices. */
           $choices = json_decode(
                $item["customisation"] ?? "",
                true
         );
       ?>

  <?php if (is_array($choices) && !empty($choices)): ?>
     <h4>Meal Customisation</h4>

     <?php foreach ($choices as $field => $value): ?>
     <?php if ($value !== ""): ?>

       <p>  <strong>
       <?php echo htmlspecialchars( ucwords(str_replace("_", " ", $field))
            ); ?>:
         </strong>

           <?php echo htmlspecialchars((string) $value); ?>
                 </p>

           <?php endif; ?>

               <?php endforeach; ?>

               <?php endif; ?>
               </div>

       <hr>

          <?php endwhile; ?>

            </div>

        </section>

    </main>

</div>

</body>
</html>
