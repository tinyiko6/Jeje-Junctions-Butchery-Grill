
<?php

/* =========================================
   JEJE JUNCTIONS - SHARED ADMIN TOP BAR
   ========================================= */

/* Identify the current page */
$current_page = basename($_SERVER["SCRIPT_NAME"]);

/* Get the logged-in administrator's name */
$admin_name = $_SESSION["admin_name"] ?? "Administrator";

/* Get the first letter for the profile icon */
$admin_initial = strtoupper(substr($admin_name, 0, 1));


/* =========================================
   PAGE HEADINGS AND DESCRIPTIONS
   ========================================= */

/* Default page information */
$page_title = "Admin Dashboard";
$page_label = "ADMIN PORTAL";
$page_description = "Manage your Jeje Junctions business.";


/* Dashboard */
if ($current_page === "dashboard.php") {

    $page_title = "Welcome," . $admin_name ."👋";
    $page_label = "ADMIN PORTAL";

    $page_description =
        "Here's what's happening at Jeje Junctions today.";

}


/* Products */
elseif ($current_page === "products.php") {

    $page_title = "Products";

    $page_label = "STORE MANAGEMENT";

    $page_description =
        "Manage your meals, combos, sides and drinks.";

}


/* Add Product */
elseif ($current_page === "add_product.php") {

    $page_title = "Add New Product";

    $page_label = "STORE MANAGEMENT";

    $page_description =
        "Add a new product to your Jeje Junctions menu.";

}


/* Edit Product */
elseif ($current_page === "edit_product.php") {

    $page_title = "Edit Product";

    $page_label = "STORE MANAGEMENT";

    $page_description =
        "Update product information, prices and stock.";

}


/* Orders */
elseif ($current_page === "orders.php") {

    $page_title = "Orders";

    $page_label = "ORDER MANAGEMENT";

    $page_description =
        "View and manage customer orders.";

}


/* Order Details */
elseif ($current_page === "order_details.php") {

    $page_title = "Order Details";

    $page_label = "ORDER MANAGEMENT";

    $page_description =
        "View the details of a customer order.";

}

/* Payments */
elseif ($current_page === "payments.php") {

    $page_title = "Payments";

    $page_label = "PAYMENT MANAGEMENT";

    $page_description =
        "View and manage customer payments on collection.";

}
/* Customers */
elseif ($current_page === "customers.php") {

    $page_title = "Customers";

    $page_label = "CUSTOMER MANAGEMENT";

    $page_description =
        "View your registered Jeje Junctions customers.";

}


/* Site Content */
elseif ($current_page === "site_content.php") {

    $page_title = "Site Content";

    $page_label = "WEBSITE MANAGEMENT";

    $page_description =
        "Manage information displayed on the customer website.";

}

?>

<!-- =====================================
     SHARED ADMIN TOP BAR
     ===================================== -->

<header class="shared-topbar">

    <!-- Top navigation row -->
    <div class="shared-topbar-navigation">

        <div class="shared-breadcrumb">

            Jeje Junctions /

            <strong>
                <?php
                echo htmlspecialchars(
                    $current_page === "dashboard.php"
                        ? "Admin Dashboard"
                        : $page_title
                );
                ?>
            </strong>

        </div>


        <div class="shared-topbar-actions">

            <!-- View customer website -->
            <a href="../index.php">
                View Website ↗
            </a>


            <!-- Administrator profile icon -->
            <span class="shared-admin-avatar">
                <?php echo htmlspecialchars($admin_initial); ?>
            </span>

        </div>

    </div>


    <!-- Page heading -->
    <div class="shared-page-heading">

        <p class="shared-page-label">
            <?php echo htmlspecialchars($page_label); ?>
        </p>

        <h1>
            <?php echo htmlspecialchars($page_title); ?>
        </h1>

        <p class="shared-page-description">
            <?php echo htmlspecialchars($page_description); ?>
        </p>

    </div>

</header>
