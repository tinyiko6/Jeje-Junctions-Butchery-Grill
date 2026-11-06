<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT customer_id, name, email, phone, created_at
        FROM customers
        ORDER BY created_at DESC";

$customers = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers | Jeje Junctions Admin</title>

 <!-- Customers page styling -->
<link rel="stylesheet" href="css/customers.css">

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

    <!-- Registered customers -->
<section class="customers-content">

    <div class="customers-card">

    <?php if ($customers && $customers->num_rows > 0): ?>

        <div class="customers-table-wrapper">
            <table class="customers-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>CUSTOMER</th>
                        <th>EMAIL</th>
                        <th>PHONE</th>
                        <th>REGISTERED</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($customer = $customers->fetch_assoc()): ?>

                        <tr>
                            <td>#<?php echo $customer["customer_id"]; ?></td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($customer["name"]); ?>
                                </strong>
                            </td>

                            <td><?php echo htmlspecialchars($customer["email"]); ?></td>

                            <td><?php echo htmlspecialchars($customer["phone"]); ?></td>

                            <td>
                                <?php echo date("d M Y", strtotime($customer["created_at"])); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

      </tbody>

            </table>
        </div>

    <?php else: ?>

        <div class="no-customers">
            <p>No customers have registered yet.</p>
        </div>

    <?php endif; ?>

</div>

</section>
</main>

</div>

</body>
</html>