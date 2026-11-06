<?php

session_start();

require_once "db_connect.php";

/* Customer must be logged in */
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

/* Check whether the customer has products in either cart */

$normal_cart = $_SESSION["cart"] ?? [];

$combo_cart = $_SESSION["combo_cart"] ?? [];


/* Redirect if both carts are empty */

if (empty($normal_cart) && empty($combo_cart)) {

    header("Location: cart.php");
    exit();

}
$customer_id = $_SESSION["customer_id"];

$error = "";

/* Get customer details */
$sql = "SELECT name, email, phone
        FROM customers
        WHERE customer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$customer = $result->fetch_assoc();

/* Get products from cart */
$cart_products = [];
$total = 0;


/* Get product IDs from the normal shopping cart */
$product_ids = array_keys($normal_cart);


/* Include product IDs from customised combos */
foreach ($combo_cart as $combo) {

    $product_ids[] = (int) $combo["product_id"];

}


/* Remove duplicate IDs */
$product_ids = array_values(array_unique($product_ids));


$placeholders = implode(",", array_fill(0, count($product_ids), "?"));

$types = str_repeat("i", count($product_ids));

/* Get products from cart, including their available stock */
$sql = "SELECT product_id, name, price, image, stock_quantity
        FROM products
        WHERE product_id IN ($placeholders)
        AND availability = 'Available'
        AND stock_quantity > 0";

$stmt = $conn->prepare($sql);

$stmt->bind_param($types, ...$product_ids);

$stmt->execute();

$result = $stmt->get_result();


/* Get each product from the database */
while ($product = $result->fetch_assoc()) {

    $product_id = (int) $product["product_id"];


    /* Add normal products to checkout */
    if (isset($normal_cart[$product_id])) {

        $quantity = (int) $normal_cart[$product_id];

        $item = $product;
        $item["quantity"] = $quantity;
        $item["subtotal"] = $product["price"] * $quantity;
        $item["customisation"] = null;

        $cart_products[] = $item;

        $total += $item["subtotal"];
    }


    /* Add customised combos to checkout */
    foreach ($combo_cart as $combo) {

        if ((int) $combo["product_id"] !== $product_id) {
            continue;
        }

        $quantity = (int) $combo["quantity"];

        $item = $product;
        $item["quantity"] = $quantity;
        $item["subtotal"] = $product["price"] * $quantity;
        $item["customisation"] = $combo["customisation"];

        $cart_products[] = $item;

        $total += $item["subtotal"];
    }
}


/* Place order */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $order_type = $_POST["order_type"] ?? "";
    $payment_method = $_POST["payment_method"] ?? "";

    /* Check order type */
    if (!in_array($order_type, ["Eat-in", "Takeaway"])) {

        $error = "Please select an order type.";

    }

    /* Check payment method */
    elseif (!in_array(
        $payment_method,
        ["Cash on Collection", "Card on Collection"]
    )) {

        $error = "Please select a payment method.";

    }

    /* Make sure cart still has products */
    
 elseif (empty($cart_products)) {

    $error = "Your cart is empty.";

} elseif (
    count($cart_products) !==
    count($normal_cart) + count($combo_cart)
) {

    /* Stop checkout if a cart product is unavailable */
    $error = "Some products in your cart are no longer available. Please review your cart.";

}

    if ($error === "") {

        /* Start database transaction */
        $conn->begin_transaction();

        try {

   
/* Combine quantities of products with the same ID */
$stock_quantities = [];

foreach ($cart_products as $product) {

    $product_id = (int) $product["product_id"];

    $quantity = (int) $product["quantity"];
    
/* Reject invalid product quantities before placing the order */
if ($product_id < 1 || $quantity < 1) {

    throw new Exception(
        "Invalid product or quantity in the shopping cart."
    );

}


    if (!isset($stock_quantities[$product_id])) {
        $stock_quantities[$product_id] = 0;
    }

    $stock_quantities[$product_id] += $quantity;
}


/* Check the latest available stock for each product */
ksort($stock_quantities);

foreach ($stock_quantities as $product_id => $quantity) {

    /* Lock the product while checking stock */
    $sql = "SELECT stock_quantity, availability
            FROM products
            WHERE product_id = ?
            FOR UPDATE";

    $stock_stmt = $conn->prepare($sql);

    $stock_stmt->bind_param("i", $product_id);

    $stock_stmt->execute();

    $stock_result = $stock_stmt->get_result();

    if ($stock_result->num_rows === 0) {

        throw new Exception("A product is no longer available.");

    }

    $stock = $stock_result->fetch_assoc();

    $stock_stmt->close();


    /* Stop if the combined quantity exceeds stock */
    if (
        $stock["availability"] !== "Available" ||
        (int) $stock["stock_quantity"] < $quantity
    ) {

        throw new Exception(
            "Not enough stock is available for this product."
        );

    }
}


            /* Create order */
            $sql = "INSERT INTO orders
                    (customer_id, order_type, total_amount, status)
                    VALUES (?, ?, ?, 'Pending')";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "isd",
                $customer_id,
                $order_type,
                $total
            );

            $stmt->execute();


            /* Get new order ID */
            $order_id = $conn->insert_id;

/* Add products to order_items and reduce stock */
foreach ($cart_products as $product) {

    $product_id = (int) $product["product_id"];
    $quantity = (int) $product["quantity"];
    $price = (float) $product["price"];

    
/* Convert combo selections into JSON for database storage */
$customisation = null;

if (!empty($product["customisation"])) {

    $customisation = json_encode(
        $product["customisation"],
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );

}


/* Save the ordered product and its customisation */
$sql = "INSERT INTO order_items
        (order_id, product_id, quantity, price, customisation)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iiids",
    $order_id,
    $product_id,
    $quantity,
    $price,
    $customisation
);

$stmt->execute();


    /* Deduct the ordered quantity from stock */
    $stock_sql = "UPDATE products
                  SET stock_quantity = stock_quantity - ?
                  WHERE product_id = ?
                  AND availability = 'Available'
                  AND stock_quantity >= ?";

    $stock_stmt = $conn->prepare($stock_sql);

    $stock_stmt->bind_param(
        "iii",
        $quantity,
        $product_id,
        $quantity
    );

    $stock_stmt->execute();

    /* Stop if stock could not be updated */
    if ($stock_stmt->affected_rows !== 1) {
        $stock_stmt->close();

        throw new Exception(
            "Stock could not be updated. Please try again."
        );
    }

    $stock_stmt->close();
}

            /* Create payment record */
            $sql = "INSERT INTO payments
                    (order_id, method, payment_status)
                    VALUES (?, ?, 'Pending')";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "is",
                $order_id,
                $payment_method
            );

            $stmt->execute();

 
/* Save the order, payment and stock changes */
$conn->commit();


/* Empty both shopping carts after successful checkout */
$_SESSION["cart"] = [];

$_SESSION["combo_cart"] = [];


/* Redirect customer to the order confirmation page */
header("Location: order_confirmation.php?id=" . $order_id);
exit();


        } catch (Exception $e) {

    $conn->rollback();

   $error = "There is not enough stock available for your order. Please review your cart and try again.";

}
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css?v=5">
    <link rel="stylesheet" href="css/checkout.css">

</head>
<body>
    <!-- Navigation -->

    
<?php include "navbar.php"; ?>
    <!-- Checkout -->

    <main class="checkout-page">
        <h1>Checkout</h1>

        <p class="checkout-intro">
            Review your order and choose how you would like to collect and pay for it.
        </p>

        <?php if ($error !== "") { ?>

            <div class="checkout-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>

        <div class="checkout-layout">

            <!-- Customer and Order Details -->

            <section class="checkout-form">

                <h2>Your Details</h2>


                <div class="customer-details">

                    <p>
                        <strong>Name:</strong>
                        <?php echo htmlspecialchars($customer["name"]); ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?php echo htmlspecialchars($customer["email"]); ?>
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        <?php echo htmlspecialchars($customer["phone"]); ?>
                    </p>

                </div>

                <form method="POST" action="checkout.php">

                    <!-- Order Type -->

                    <div class="form-section">

                        <h2>Order Type</h2>

                        <label>
                            <input
                                type="radio"
                                name="order_type"
                                value="Takeaway"
                                required>

                            Takeaway
                        </label>
                        <label>
                            <input
                                type="radio"
                                name="order_type"
                                value="Eat-in"
                                required >

                          Eat-in
                        </label>
                    </div>

                    <!-- Payment -->

                    <div class="form-section">

                        <h2>Payment Method</h2>

                        <p class="payment-note">
                            Payment is made at the store when you collect your order.
                        </p>

                        <label>
                            <input
                                type="radio"
                                name="payment_method"
                                value="Cash on Collection"
                                required>

                            Cash on Collection
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="payment_method"
                                value="Card on Collection"
                                required
                            >

                            Card on Collection
                        </label>

                    </div>

                    <button
                        type="submit"
                        class="place-order-button"
                    >
                        Place Order
                    </button>


                </form>
            </section>

            <!-- Order Summary -->
            <aside class="order-summary">

                <h2>Your Order</h2>
                <?php foreach ($cart_products as $product) { ?>

                    <div class="summary-item">

                        <div>

                          
<strong>
    <?php echo htmlspecialchars($product["name"]); ?>
</strong>

<span>
    x<?php echo (int) $product["quantity"]; ?>
</span>


<!-- Display the selected customisation options for meals and combos. -->
<?php if (!empty($product["customisation"])): ?>

    <div class="combo-summary-details">

        <?php foreach ($product["customisation"] as $field => $value): ?>

            <?php if ($value !== ""): ?>

                <p>
                    <strong>
                        <?php echo htmlspecialchars(
                            ucwords(str_replace("_", " ", $field))
                        ); ?>:
                    </strong>

                    <?php echo htmlspecialchars((string) $value); ?>
                </p>

            <?php endif; ?>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

                        </div>

                        <strong>
                            R<?php echo number_format($product["subtotal"], 2); ?>
                        </strong>

                    </div>

                <?php } ?>

                <div class="summary-total">

                    <span>
                        Total
                    </span>

                    <strong>
                        R<?php echo number_format($total, 2); ?>
                    </strong>

                </div>
            </aside>
        </div>
    </main>
    <!-- Footer -->

    <footer>

        <h3>JEJE JUNCTIONS</h3>
        <p>Butchery & Grill</p>
        <p>Good Meat. Great Taste. Real Experience. </p>
        <p>  &copy; 2026 Jeje Junctions. All rights reserved.
        </p>

    </footer>

    <script src="js/script.js?v=5"></script>
</body>
</html>