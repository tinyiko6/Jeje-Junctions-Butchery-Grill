
<?php

/* =========================================
   JEJE JUNCTIONS - SHARED ADMIN SIDEBAR
   ========================================= */

/* Identify which admin page is currently open */
$current_page = basename($_SERVER["SCRIPT_NAME"]);

/* Get the administrator's name from the session */
$admin_name = $_SESSION["admin_name"] ?? "Administrator";

/* Get the first letter of the administrator's name */
$admin_initial = strtoupper(substr($admin_name, 0, 1));

?>

<!-- =====================================
     SHARED ADMIN SIDEBAR
     ===================================== -->

<aside class="sidebar">

    <!-- BUSINESS LOGO AND NAME -->

    <div class="sidebar-brand">

        <img
            src="../images/jeje-logo.jpeg"
            alt="Jeje Junctions Logo"
        >

        <div>

            <h2>JEJE JUNCTIONS</h2>

            <p>ADMIN PORTAL</p>

        </div>

    </div>


    <!-- ADMINISTRATOR PROFILE -->

    <div class="sidebar-admin">

        <div class="admin-avatar">

            <?php echo htmlspecialchars($admin_initial); ?>

        </div>

        <div>

            <strong>
                <?php echo htmlspecialchars($admin_name); ?>
            </strong>

            <small>Administrator</small>

        </div>

    </div>


    <!-- =====================================
         SIDEBAR NAVIGATION
         ===================================== -->

    <nav class="sidebar-nav">

        <!-- OVERVIEW -->

        <p class="nav-heading">
            OVERVIEW
        </p>

        <a
            href="dashboard.php"
            class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
        >
            Dashboard
        </a>


        <!-- STORE MANAGEMENT -->

        <p class="nav-heading">
            STORE MANAGEMENT
        </p>

        <a
            href="products.php"
            class="<?php
                echo in_array($current_page, [
                    'products.php',
                    'add_product.php',
                    'edit_product.php'
                ]) ? 'active' : '';
            ?>"
        >
            Products
        </a>


        <a
            href="orders.php"
            class="<?php
                echo in_array($current_page, [
                    'orders.php',
                    'order_details.php'
                ]) ? 'active' : '';
            ?>"
        >
            Orders
        </a>

        <!-- PAYMENT MANAGEMENT -->

     <a
    href="payments.php"
    class="<?php echo $current_page === 'payments.php' ? 'active' : ''; ?>"
          >
           Payments
          </a>
        <a
            href="customers.php"
            class="<?php echo $current_page === 'customers.php' ? 'active' : ''; ?>"
        >
            Customers
        </a>


        <a
            href="site_content.php"
            class="<?php echo $current_page === 'site_content.php' ? 'active' : ''; ?>"
        >
            Site Content
        </a>


        <!-- WEBSITE LINKS -->

        <p class="nav-heading">
            WEBSITE
        </p>

        <a href="../index.php">
            View Website
        </a>


        <a
            href="logout.php"
            class="logout-link"
        >
            Logout
        </a>

    </nav>


    <!-- =====================================
         SIDEBAR FOOTER
         ===================================== -->

    <div class="sidebar-footer">

        <strong>
            Jeje Junctions
        </strong>

        <p>
            Butchery & Grill
        </p>

        <small>
            Admin Management System
        </small>

    </div>

</aside>
