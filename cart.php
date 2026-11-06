<?php
session_start();
require_once "db_connect.php";

/* Normal products are stored by product ID and quantity. */
$_SESSION["cart"] ??= [];

/* Keep customised combos and meals as separate cart entries.
   Each entry has its own quantity and selected instructions. */
$_SESSION["combo_cart"] ??= [];
$cart_message = "";

/* Check that customers selected only the allowed combo options.
   Bestie (7) has one side; Couples (8) has two sides. */
function comboChoicesValid(int $id, array $options): bool
{
    $allowed = [
        "meat_1" => ["Grilled Chicken", "Boerewors", "Beef Steak", "Beef Ribs"],
        "meat_2" => ["Grilled Chicken", "Boerewors", "Beef Steak", "Beef Ribs"],
        "starch_1" => ["Pap", "Fries", "Garlic Bread"],
        "starch_2" => ["Pap", "Fries", "Garlic Bread"],
        "side_1" => ["Coleslaw", "Green Salad", "Greek Salad", "Pasta Salad"],
        "combo_drink" => ["Coke 1.5L", "Fanta 1.5L", "Sprite 1.5L"],
        "combo_sauce" => ["BBQ Sauce", "Peri-Peri Sauce", "Chilli Sauce", "No Sauce"],
        "spice_level" => [  "Mild","Medium","Hot"]
  ];

    /* Couples Combo requires a second side. */
    if ($id === 8) {
        $allowed["side_2"] = $allowed["side_1"];
    }

    /* Reject missing or unrecognised choices. */
    foreach ($allowed as $field => $choices) {
        if (!in_array($options[$field] ?? "", $choices, true)) return false;
    }
    if ($id === 7 && !empty($options["side_2"])) return false;
    return mb_strlen($options["special_instructions"] ?? "") <= 250;
}

/* Validate customisation for other menu products. */

function mealChoicesValid(
    string $category,
    string $product_name,
    string $description,
    array $options
): bool {
    $category = strtolower(trim($category));

    $instructions = $options["special_instructions"] ?? "";

    if (!is_string($instructions) ||
        mb_strlen($instructions) > 250) {
        return false;
    }

    if (str_contains($category, "grill")) {

        $sauces = [
            "BBQ Sauce",
            "Peri-Peri Sauce",
            "Chilli Sauce",
            "No Sauce"
        ];

        $spices = ["Mild", "Medium", "Hot"];

        $meal_text = strtolower($product_name . " " . $description);

      $already_has_starch =
    str_contains($meal_text, "pap") ||
    str_contains($meal_text, "fries");

        $starch = $options["meal_starch"] ?? "";

        if ($already_has_starch) {
            if ($starch !== "") {
                return false;
            }
        } elseif (!in_array($starch, ["Pap", "Fries"], true)) {
            return false;
        }

        return in_array(
            $options["meal_sauce"] ?? null,
            $sauces,
            true
        ) && in_array(
            $options["spice_level"] ?? null,
            $spices,
            true
        );
    }

    return empty($options["meal_starch"])
        && empty($options["meal_sauce"])
        && empty($options["spice_level"]);
}


/* Count a product in both carts so combined quantities cannot exceed stock. */
function quantityInCart(int $id): int
{
    $count = (int) ($_SESSION["cart"][$id] ?? 0);
    foreach ($_SESSION["combo_cart"] as $item) {
        if ((int) $item["product_id"] === $id) $count += (int) $item["quantity"];
    }
    return $count;
}

/* Read the current stock of an available product from the database. */
function availableStock(mysqli $conn, int $id): ?int
{
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ? AND availability = 'Available'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row["stock_quantity"] : null;
}

/* Add an ordinary product or a customised meal to the shopping cart. */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["product_id"])) {
    $id = (int) $_POST["product_id"];
    $quantity = filter_var($_POST["quantity"] ?? 1, FILTER_VALIDATE_INT);
    $is_combo = in_array($id, [7, 8], true);
    $options = [];

    /* Identify category from database, not from customer-submitted values. */
    $category = "";
    if ($id > 0) {
        $category_stmt = $conn->prepare(
            "SELECT categories.name AS category_name,
        products.name AS product_name,
        products.description AS product_description
        FROM products
        INNER JOIN categories
        ON products.category_id = categories.category_id
        WHERE products.product_id = ?"
        );
        $category_stmt->bind_param("i", $id);
        $category_stmt->execute();
        $category_row = $category_stmt->get_result()->fetch_assoc();
        $category = $category_row["category_name"] ?? "";
        $product_name = $category_row["product_name"] ?? "";
        $product_description = $category_row["product_description"] ?? "";
        $category_stmt->close();
    }

    /* Keep the existing combo fields separate from other meal fields. */
    if ($is_combo) {

        /* Existing Bestie and Couples customisations. */
        $fields = ["meat_1", "meat_2", "starch_1", "starch_2", "side_1",
                   "side_2", "combo_drink", "combo_sauce","spice_level", "special_instructions"];
    } else {
       $fields = ["meal_starch","meal_sauce", "spice_level", "special_instructions"];
    }

    /* Remove extra spaces from each submitted selection. */
    foreach ($fields as $field) {
        $value = $_POST[$field] ?? "";
        $options[$field] = is_string($value) ? trim($value) : "";
    }

    /* Drinks only need quantity; other meals may have customisations. */
    $is_drink = str_contains(strtolower($category), "drink");
    $has_customisation = !$is_combo && !$is_drink &&
        (str_contains(strtolower($category), "grill") ||
         $options["special_instructions"] !== "");

    /* Check quantity, selected options, availability and remaining stock. */
    $stock = $id > 0 ? availableStock($conn, $id) : null;
    if ($quantity === false || $quantity < 1) {
        $cart_message = "Please select a valid quantity.";
    

} elseif ($is_combo && !comboChoicesValid($id, $options)) {
    $cart_message = "Please select valid options for your combo.";

} elseif (
    !$is_combo &&
    !$is_drink &&
    !mealChoicesValid(
        $category,
        $product_name,
        $product_description,
        $options
    )
) {
    $cart_message = "Please select valid options for your meal.";

} elseif (
    !$is_combo &&
    $is_drink &&
    array_filter($options, fn($v) => $v !== "")
) {
    $cart_message = "Please select a valid drink quantity.";

} elseif ($stock === null || $stock < 1) {
    $cart_message = "This product is currently unavailable.";

} elseif (quantityInCart($id) + $quantity > $stock) {
    $cart_message = "Only $stock portions are available. Your cart has not been changed.";

} else {


        /* Save customised products separately so their instructions never mix.
           Reuse combo_cart because checkout already supports this structure. */
        if ($is_combo || $has_customisation) {
            $saved_options = array_filter($options, fn($value) => $value !== "");
            $_SESSION["combo_cart"][bin2hex(random_bytes(16))] = [
                "product_id" => $id,
                "quantity" => $quantity,
                "customisation" => $saved_options
            ];
        } else {
            $_SESSION["cart"][$id] = quantityInCart($id) + $quantity;
        }
        $cart_message = "Product added to your cart successfully.";
    }
}

/* Remove a normal product or one specific customised combo. */
if (isset($_POST["remove_product"])) {
    unset($_SESSION["cart"][(int) $_POST["remove_product"]]);
}
if (isset($_POST["remove_combo"]) && is_string($_POST["remove_combo"])) {
    unset($_SESSION["combo_cart"][$_POST["remove_combo"]]);
}

/* Update quantities only if ALL proposed quantities are valid.
   This prevents partially updating the cart when one item exceeds stock. */
if (isset($_POST["update_cart"])) {
    $normal = $_POST["quantities"] ?? [];
    $combos = $_POST["combo_quantities"] ?? [];
    $proposed_normal = $_SESSION["cart"];
    $proposed_combos = $_SESSION["combo_cart"];
    $valid = is_array($normal) && is_array($combos);

    /* Prepare normal product quantity changes. */
    if ($valid) {
        foreach ($normal as $id => $value) {
            if (!ctype_digit((string) $id) || !array_key_exists((int) $id, $proposed_normal)) continue;
            $qty = filter_var($value, FILTER_VALIDATE_INT);
            if ($qty === false || $qty < 1) { $valid = false; break; }
            $proposed_normal[(int) $id] = $qty;
        }
    }

    /* Prepare customised combo quantity changes. */
    if ($valid) {
        foreach ($combos as $key => $value) {
            if (!isset($proposed_combos[$key])) continue;
            $qty = filter_var($value, FILTER_VALIDATE_INT);
            if ($qty === false || $qty < 1) { $valid = false; break; }
            $proposed_combos[$key]["quantity"] = $qty;
        }
    }

    /* Combine both carts and check stock for every product. */
    if ($valid) {
        $totals = $proposed_normal;
        foreach ($proposed_combos as $item) {
            $id = (int) $item["product_id"];
            $totals[$id] = ($totals[$id] ?? 0) + (int) $item["quantity"];
        }
        foreach ($totals as $id => $qty) {
            $stock = availableStock($conn, (int) $id);
            if ($stock === null || $qty > $stock) { $valid = false; break; }
        }
    }

    /* Save all changes together only after validation succeeds. */
    if ($valid) {
        $_SESSION["cart"] = $proposed_normal;
        $_SESSION["combo_cart"] = $proposed_combos;
        $cart_message = "Cart quantities updated successfully.";
    } else {
        $cart_message = "Please enter valid quantities within the available stock.";
    }
}

/* Collect product IDs from ordinary and customised cart entries. */
$ids = array_merge(
    array_map("intval", array_keys($_SESSION["cart"])),
    array_map(
        fn($item) => (int) $item["product_id"],
        $_SESSION["combo_cart"]
    )
);

/* Remove duplicate IDs and reset array keys */
$ids = array_values(array_unique($ids));

$products = [];
if ($ids) {
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $conn->prepare("SELECT product_id, name, description, price, image FROM products WHERE product_id IN ($placeholders)");
    $stmt->bind_param(str_repeat("i", count($ids)), ...$ids);
    $stmt->execute();
    foreach ($stmt->get_result() as $product) $products[(int) $product["product_id"]] = $product;
    $stmt->close();
}

/* Build the list shown on the page and calculate the grand total. */
$cart_products = [];
$total = 0;

/* Add normal products to the display list. */
foreach ($_SESSION["cart"] as $id => $quantity) {
    if (!isset($products[$id])) continue;
    $item = $products[$id];
    $item["quantity"] = (int) $quantity;
    $item["subtotal"] = (float) $item["price"] * $quantity;
    $item["combo_key"] = null;
    $item["customisation"] = [];
    $cart_products[] = $item;
    $total += $item["subtotal"];
}

/* Display each customised combo or meal with its own selections. */
foreach ($_SESSION["combo_cart"] as $key => $combo) {
    $id = (int) $combo["product_id"];
    if (!isset($products[$id])) continue;
    $item = $products[$id];
    $item["quantity"] = (int) $combo["quantity"];
    $item["subtotal"] = (float) $item["price"] * $item["quantity"];
    $item["combo_key"] = $key;
    $item["customisation"] = $combo["customisation"];
    $cart_products[] = $item;
    $total += $item["subtotal"];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css?v=7">
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>

<!-- Website navigation -->
<?php include "navbar.php"; ?>

<!-- Shopping cart page -->
<main class="cart-page">
    <h1>Your Shopping Cart</h1>
    <p class="cart-intro">Review your items before checking out.</p>

    <!-- Display success or error messages after cart actions -->
    <?php if ($cart_message !== ""): ?>
        <p class="<?= str_contains($cart_message, 'successfully') ? 'form-success' : 'form-error' ?>">
            <?= htmlspecialchars($cart_message) ?>
        </p>
    <?php endif; ?>

    <!-- Show an empty message when no products are in either cart -->
    <?php if (!$cart_products): ?>
        <div class="empty-cart">
            <h2>Your cart is empty</h2>
            <p>You have not added any items to your cart yet.</p>
            <a href="menu.php" class="shop-button">Browse Menu</a>
        </div>
    <?php else: ?>

        <!-- Form for changing quantities or removing products -->
        <form method="POST" action="cart.php">
            <div class="cart-items">
                <?php foreach ($cart_products as $product): ?>
                    <div class="cart-item">
                        <div class="cart-image">
                            <?php if ($product["image"]): ?>
                                <img src="images/<?= htmlspecialchars($product["image"]) ?>" alt="<?= htmlspecialchars($product["name"]) ?>">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>

        <!-- Product details and selected customisation options -->
        <div class="cart-info">
               <h2><?= htmlspecialchars($product["name"]) ?></h2>
                  <p><?= htmlspecialchars($product["description"]) ?></p>
                      <?php if ($product["customisation"]): ?>
                          <div class="combo-details">
                   <?php foreach ($product["customisation"] as $field => $value): ?>
                           <?php if ($value !== ""): ?>
                   <p><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $field))) ?>:</strong> <?= htmlspecialchars($value) ?></p>
                         <?php endif; ?>
                 <?php endforeach; ?>

               </div>
        <?php endif; ?>
       <strong>R<?= number_format($product["price"], 2) ?></strong>
          </div>

        <!-- Different input names distinguish customised and ordinary products -->
        <div class="cart-quantity">
               <label>Quantity</label>
                  <?php if ($product["combo_key"] !== null): ?>
                      <input type="number" min="1" name="combo_quantities[<?= htmlspecialchars($product["combo_key"]) ?>]" value="<?= $product["quantity"] ?>">
                       <?php else: ?>
                  <input type="number" min="1" name="quantities[<?= (int) $product["product_id"] ?>]" value="<?= $product["quantity"] ?>">
                    <?php endif; ?>
                   </div>

        <!-- Price multiplied by quantity -->
         <div class="cart-subtotal">

              <span> Subtotal
              </span>
                <strong>R<?= number_format($product["subtotal"], 2) ?>
                </strong>
                  </div>
              <?php if ($product["combo_key"] !== null): ?>
              <button type="submit" name="remove_combo" value="<?= htmlspecialchars($product["combo_key"]) ?>" class="remove-button">Remove</button>
             <?php else: ?>

              <button type="submit" name="remove_product" value="<?= (int) $product["product_id"] ?>" class="remove-button">Remove</button>
                <?php endif; ?>

                </div>
               
               <?php endforeach; ?>
            </div>

        <!-- Grand total and cart action buttons -->
          <div class="cart-summary">
            <h2>Order Summary</h2>
             <div class="summary-row">
             <span>Total
             </span>
             
             <strong>R<?= number_format($total, 2) ?>
             </strong>
             </div>
               
            
     <!-- Update the quantities in the cart -->
     <button
    type="submit"
    name="update_cart"
    value="1"
    class="update-button">
    Update Cart
    </button>

      <!-- Allow customers to add more meals -->
     <a href="menu.php" class="continue-shopping-button">
    Continue Shopping
    </a>

      <!-- Proceed when the customer is ready -->
     <a href="checkout.php" class="checkout-button">
    Proceed to Checkout
      </a>

</div>
</form>

<?php endif; ?>

</main>


<!-- Website footer -->
<footer>
    <h3>JEJE JUNCTIONS</h3>
    <p>Butchery &amp; Grill</p>
    <p>Good Meat. Great Taste. Real Experience.</p>
    <p>&copy; 2026 Jeje Junctions. All rights reserved.</p>
</footer>
<script src="js/script.js?v=5"></script>
</body>
</html>