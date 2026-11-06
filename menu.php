<?php
session_start();
require_once "db_connect.php";

$category = $_GET["category"] ?? "";

$category_map = [
    "grilled" => "Grilled Meals",
    "combos" => "Combos",
    "sides" => "Sides & Salads",
    "drinks" => "Drinks"
];

$page_title = "Our Menu";
$page_description = "Choose from our grilled favourites, signature combos, sides, salads and refreshing drinks.";

if (isset($category_map[$category])) {
    $page_title = $category_map[$category];

    if ($category === "grilled") {
        $page_description = "Freshly grilled meals prepared with generous portions and great flavour.";
    } elseif ($category === "combos") {
        $page_description = "Signature Jeje Junctions combos made for sharing, dates, families and weekends.";
    } elseif ($category === "sides") {
        $page_description = "Complete your meal with our selection of sides and fresh salads.";
    } elseif ($category === "drinks") {
        $page_description = "Refresh yourself with a selection of cold drinks and beverages.";
    }
}

if ($category !== "" && isset($category_map[$category])) {
    $selected_category = $category_map[$category];

    $sql = "SELECT
                products.product_id,
                products.name,
                products.description,
                products.price,
                products.image,
                categories.name AS category_name
            FROM products
            INNER JOIN categories
                ON products.category_id = categories.category_id
           WHERE products.availability = 'Available'
           AND products.stock_quantity > 0
           AND categories.name = ?
            ORDER BY products.name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_category);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT
                products.product_id,
                products.name,
                products.description,
                products.price,
                products.image,
                categories.name AS category_name
            FROM products
            INNER JOIN categories
                ON products.category_id = categories.category_id
            WHERE products.availability = 'Available'
            AND products.stock_quantity > 0
            ORDER BY categories.category_id, products.name";

    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($page_title); ?> | Jeje Junctions</title>

   <link rel="stylesheet" href="css/shared.css?v=4">
<link rel="stylesheet" href="css/menu.css?v=4">
</head>

<body>

<!-- Main navigation -->
<header class="navbar">

    <div class="logo">
        <a href="index.php">
            <img src="images/jeje-logo.jpeg"
                 alt="Jeje Junctions">
        </a>
    </div>

    <!-- Hamburger menu for mobile -->
    <button
        type="button"
        class="mobile-menu-toggle"
        id="mobile-menu-toggle"
        aria-label="Open navigation menu"
        aria-controls="mobile-navigation"
        aria-expanded="false">

        <span></span>
        <span></span>
        <span></span>

    </button>

    <div class="navigation-wrapper"
         id="mobile-navigation">

        <nav class="main-navigation"
             aria-label="Main navigation">

            <a href="index.php">Home</a>
            <a href="menu.php?category=grilled">Grilled Meals</a>
            <a href="menu.php?category=combos">Combos</a>
            <a href="menu.php?category=sides">Sides &amp; Salads</a>
            <a href="menu.php?category=drinks">Drinks</a>
            <a href="about.php">About Us</a>
            <a href="faq.php">FAQs</a>

        </nav>

        <div class="nav-actions">

            <a href="cart.php">Cart</a>

            <?php if (isset($_SESSION["customer_id"])): ?>

                <a href="my_orders.php">My Orders</a>
                <a href="logout.php">Logout</a>

            <?php else: ?>

                <a href="login.php">Login</a>

            <?php endif; ?>

        </div>

    </div>

</header>


<section class="page-heading">
    <p class="section-label">JEJE JUNCTIONS</p>

    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <p>
        <?php echo htmlspecialchars($page_description); ?>
    </p>
</section>

<!-- Search products using JavaScript -->
<div class="menu-search">
    <input
        type="text"
        id="productSearch"
        placeholder="Search for a meal..."
        aria-label="Search products"
    >
    <p id="searchMessage" aria-live="polite"></p>
</div>


<section class="products-section">

    <?php if ($result && $result->num_rows > 0) { ?>

        <?php while ($product = $result->fetch_assoc()) { ?>

            <div class="product-card">

                <div class="product-image">
                    <?php if (!empty($product["image"])) { ?>

                        <img
                            src="images/<?php echo htmlspecialchars($product["image"]); ?>"
                            alt="<?php echo htmlspecialchars($product["name"]); ?>"
                        >

                    <?php } else { ?>

                        <div class="no-image">No Image</div>

                    <?php } ?>
                </div>

                <div class="product-info">

                    <p class="product-category">
                        <?php echo htmlspecialchars($product["category_name"]); ?>
                    </p>

                    <h2>
                        <?php echo htmlspecialchars($product["name"]); ?>
                    </h2>

                    <p>
                        <?php echo htmlspecialchars($product["description"]); ?>
                    </p>

                    <div class="product-bottom">

                        <strong>
                            R<?php echo number_format($product["price"], 2); ?>
                        </strong>

                        <a
                            href="product.php?id=<?php echo $product["product_id"]; ?>"
                            class="product-button"
                        >
                            View
                        </a>

                    </div>

                </div>

            </div>

        <?php } ?>

    <?php } else { ?>

        <p>No products are currently available in this category.</p>

    <?php } ?>

</section>

<footer>
    <h3>JEJE JUNCTIONS</h3>
    <p>Butchery & Grill</p>
    <p>Good Meat. Great Taste. Real Experience.</p>
    <p>&copy; 2026 Jeje Junctions. All rights reserved.</p>
</footer>
<script src="js/script.js">
</script>
</body>
</html>