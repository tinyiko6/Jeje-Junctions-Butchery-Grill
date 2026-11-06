<?php
session_start();
require_once __DIR__ . "/../db_connect.php";

/* Only administrators can access this page */
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit();
}

/*  1. DASHBOARD STATISTICS */

/* Count all orders */
$orders_result = $conn->query(
    "SELECT COUNT(*) AS total FROM orders"
);

$total_orders = $orders_result->fetch_assoc()["total"];


/* Count pending orders */
$pending_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'Pending'"
);

$pending_orders = $pending_result->fetch_assoc()["total"];


/* Count completed orders */
$completed_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'Completed'"
);

$completed_orders = $completed_result->fetch_assoc()["total"];


/* Count products */
$products_result = $conn->query(
    "SELECT COUNT(*) AS total FROM products"
);

$total_products = $products_result->fetch_assoc()["total"];


/* 2. RECENT CUSTOMER ORDERS */

$recent_orders_sql = "
    SELECT
        orders.order_id,
        orders.total_amount,
        orders.status,
        orders.order_date,
        customers.name AS customer_name

    FROM orders

    INNER JOIN customers
        ON orders.customer_id = customers.customer_id

    ORDER BY orders.order_date DESC, orders.order_id DESC

    LIMIT 5
";

$recent_orders = $conn->query($recent_orders_sql);


/* Administrator name from login session */
$admin_name = $_SESSION["admin_name"] ?? "Administrator";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Jeje Junctions</title>

    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin-shared.css">

</head>

<body>

<div class="admin-layout">

<!-- Shared admin sidebar -->
<?php require __DIR__ . "/sidebar.php"; ?>

    <main class="main-content">
 <!-- Shared admin top bar -->
<?php require __DIR__ . "/topbar.php"; ?>

        <div class="dashboard-content">

            <!--  5. DASHBOARD SUMMARY CARDS -->

            <section class="summary-cards">

                <div class="summary-card">

                    <p>Total Orders</p>

                    <h2>
                        <?php echo $total_orders; ?>
                    </h2>

                    <small>All customer orders</small>

                </div>


                <div class="summary-card">

                    <p>Pending Orders</p>

                    <h2>
                        <?php echo $pending_orders; ?>
                    </h2>

                    <small>Awaiting preparation</small>

                </div>


                <div class="summary-card">

                    <p>Completed Orders</p>

                    <h2>
                        <?php echo $completed_orders; ?>
                    </h2>

                    <small>Successfully completed</small>

                </div>


                <div class="summary-card">

                    <p>Total Products</p>

                    <h2>
                        <?php echo $total_products; ?>
                    </h2>

                    <small>Products in the database</small>

                </div>

            </section>


            <!--  6. RECENT ORDERS TABLE -->

            <section class="recent-orders">

                <div class="recent-orders-header">

                    <div>

                        <h2>Recent Orders</h2>

                        <p>
                            Latest orders placed by customers
                        </p>

                    </div>

                    <a href="orders.php" class="view-orders">
                        View All Orders →
                    </a>

                </div>


        <?php if (
             $recent_orders &&
             $recent_orders->num_rows > 0
                ): ?>
 
       <div class="orders-table-wrapper">

            <table class="orders-table">

               <thead>
                  <tr>
                       <th>ORDER</th>
                       <th>CUSTOMER</th>
                       <th>TOTAL</th>
                       <th>STATUS</th>
                       <th>DATE</th>
               </tr>

               </thead>
           <tbody>

             <?php while (
                      $order = $recent_orders->fetch_assoc()
                           ): ?>

                 <tr>

                    <td>
                      <strong>
                           #<?php echo (int)$order["order_id"]; ?>
                        </strong>
                     </td>

                    <td>
                       <?php
                            echo htmlspecialchars(
                             $order["customer_name"]
                             );
                            ?>
                       </td>

                     <td>
                        R<?php
                            echo number_format(
                              $order["total_amount"],
                                    2
                               );
                   ?>
                              </td>
             <td>
          <span class="status <?php
                               echo htmlspecialchars(
                                     strtolower($order["status"])
                         );
              ?>">

                   <?php
                          echo htmlspecialchars(
                                 $order["status"]
                         );
                       ?>

           </span>

             </td>

                <td>
                    <?php
                       echo date(
                          "d M Y",
                      strtotime($order["order_date"])
                       );
                     ?>
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

            </section>

            <!--   7. QUICK ACTIONS -->

            <section class="quick-actions">

                <div class="quick-actions-heading">

                    <h2>Quick Actions</h2>

                    <p>
                        Easily manage your store from one place
                    </p>

                </div>

                <div class="quick-actions-links">

                    <a href="add_product.php" class="action-primary">
                        + Add Product
                    </a>

                    <a href="orders.php" class="action-secondary">
                        Manage Orders
                    </a>

                    <a href="../index.php" class="action-secondary">
                        View Website ↗
                    </a>

                </div>

            </section>

        </div>

    </main>

</div>

</body>

</html>
