<?php
session_start();
require_once "db_connect.php";

// Check that a valid product ID was provided.
if (!isset($_GET["id"]) || !ctype_digit((string) $_GET["id"])) {
    header("Location: menu.php");
    exit();
}

// Retrieve the selected available product from the database.
$product_id = (int) $_GET["id"];

$sql = "SELECT products.product_id, products.name, products.description,
               products.price, products.image, products.stock_quantity,
               categories.name AS category_name
        FROM products
        INNER JOIN categories
        ON products.category_id = categories.category_id
        WHERE products.product_id = ?
        AND products.availability = 'Available'
        AND products.stock_quantity > 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect customers when the selected product is unavailable.
if (!$product) {
    header("Location: menu.php");
    exit();
}

// Identify the product category and available customisation options.
$is_bestie_combo = $product_id === 7;
$is_couples_combo = $product_id === 8;
$category = strtolower(trim($product["category_name"]));
$is_grilled_meal = str_contains($category, "grill");
$is_drink = str_contains($category, "drink");
$is_sharing_combo = $is_bestie_combo || $is_couples_combo;

// Check whether the meal already includes Pap or Fries.
$meal_text = strtolower(
    $product["name"] . " " . $product["description"]
);

$already_has_starch =
    str_contains($meal_text, "pap") ||
    str_contains($meal_text, "fries");

// Allow eligible grilled meals to offer a choice between Pap and Fries.
$can_choose_starch =
    $is_grilled_meal &&
    !$is_sharing_combo &&
    !$already_has_starch;

// Display radio buttons for the available meal choices.
function renderComboOptions(
    string $heading,
    string $field_name,
    array $options
): void {
    ?>
    <fieldset class="combo-choice-group">
        <legend><?= htmlspecialchars($heading) ?></legend>
        <p>Select one option.</p>

        <div class="combo-radio-options">
            <?php foreach ($options as $option): ?>
                <label class="combo-radio-option">
                    <input
                        type="radio"
                        name="<?= htmlspecialchars($field_name) ?>"
                        value="<?= htmlspecialchars($option) ?>"
                        required
                    >
                    <span><?= htmlspecialchars($option) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

// Display an optional field for customer special instructions.
function renderSpecialInstructions(): void {
    ?>
    <div class="combo-instructions">
        <h3>Special Instructions</h3>
        <p>Have any special requests? Let us know below.</p>

        <label for="special_instructions">
            Additional Instructions (Optional)
        </label>

        <textarea
            id="special_instructions"
            name="special_instructions"
            rows="4"
            maxlength="250"
            placeholder="E.g. Extra spicy, no onions, sauce on the side..."
        ></textarea>

        <small>Maximum 250 characters.</small>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($product["name"]) ?> | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css?v=5">
    <link rel="stylesheet" href="css/product.css">
</head>

<body>

<!-- Display the shared website navigation. -->
<?php include "navbar.php"; ?>

<!-- Display the selected product and its ordering options. -->
<main class="product-page">

    <a href="menu.php" class="back-link">← Back to Menu</a>

    <div class="product-details">

        <!-- Display the product image when one is available. -->
        <div class="product-detail-image">
            <?php if (!empty($product["image"])): ?>
                <img
                    src="images/<?= htmlspecialchars($product["image"]) ?>"
                    alt="<?= htmlspecialchars($product["name"]) ?>"
                >
            <?php else: ?>
                <div class="no-image">No Image Available</div>
            <?php endif; ?>
        </div>

        <!-- Display the product name, description and ordering form. -->
        <div class="product-detail-info">

            <p class="product-category">
                <?= htmlspecialchars($product["category_name"]) ?>
            </p>

            <h1><?= htmlspecialchars($product["name"]) ?></h1>

            <p class="product-description">
                <?= htmlspecialchars($product["description"]) ?>
            </p>

            <!-- Send the selected product and customisation options to the cart. -->
            <form action="cart.php" method="POST">

                <?php if ($is_sharing_combo): ?>

                    <!-- Display meat, starch, side, drink and sauce choices for sharing combos. -->
                    <div class="combo-customisation">

                        <h2>Build Your Own Combo</h2>
                        <p>Choose your two favourite meats.</p>

                        <?php
                        $meats = [
                            "Grilled Chicken",
                            "Boerewors",
                            "Beef Steak",
                            "Beef Ribs"
                        ];

                        renderComboOptions(
                            "Choose Your First Meat",
                            "meat_1",
                            $meats
                        );

                        renderComboOptions(
                            "Choose Your Second Meat",
                            "meat_2",
                            $meats
                        );

                        // Allow customers to choose a starch of their choice.
                        $starches = ["Pap", "Fries","Garlic Bread"];

                        renderComboOptions(
                            "Choose Your First Starch",
                            "starch_1",
                            $starches
                        );

                        renderComboOptions(
                            "Choose Your Second Starch",
                            "starch_2",
                            $starches
                        );

                        // Display the available salad and side choices.
                        $sides = [
                            "Coleslaw",
                            "Green Salad",
                            "Greek Salad",
                            "Pasta Salad"
                        ];

                        renderComboOptions(
                            $is_couples_combo
                                ? "Choose Your First Side"
                                : "Choose Your Side",
                            "side_1",
                            $sides
                        );

                        if ($is_couples_combo) {
                            renderComboOptions(
                                "Choose Your Second Side",
                                "side_2",
                                $sides
                            );
                        }

                        // Allow customers to select a cold drink for the combo.
                        renderComboOptions(
                            "Choose Your 1.5L Cold Drink",
                            "combo_drink",
                            [
                                "Coke 1.5L",
                                "Fanta 1.5L",
                                "Sprite 1.5L"
                            ]
                        );

                        // Allow customers to select their preferred sauce.
                        renderComboOptions(
                            "Choose Your Sauce",
                            "combo_sauce",
                            [
                                "BBQ Sauce",
                                "Peri-Peri Sauce",
                                "Chilli Sauce",
                                "No Sauce"
                            ]
                        );

                        // Allow customers to select their preferred spice level.
                        renderComboOptions(
                            "Spice Level",
                            "spice_level",
                            ["Mild", "Medium", "Hot"]
                        );

                        renderSpecialInstructions();
                        ?>

                    </div>

                <?php elseif ($is_grilled_meal): ?>

                    <!-- Display portion, sauce and spice choices for grilled meals. -->
                    <div class="combo-customisation">

                        <h2>Customise Your Meal</h2>

                        <?php
                        // Offer Pap or Fries when the meal does not include a starch.
                        if ($can_choose_starch) {
                            renderComboOptions(
                                "Choose Your Portion",
                                "meal_starch",
                                ["Pap", "Fries"]
                            );
                        }

                        // Display the available sauces for grilled meals.
                        renderComboOptions(
                            "Choose Your Sauce",
                            "meal_sauce",
                            [
                                "BBQ Sauce",
                                "Peri-Peri Sauce",
                                "Chilli Sauce",
                                "No Sauce"
                            ]
                        );

                        // Display the available spice levels for grilled meals.
                        renderComboOptions(
                            "Spice Level",
                            "spice_level",
                            ["Mild", "Medium", "Hot"]
                        );

                        renderSpecialInstructions();
                        ?>

                    </div>

                <?php elseif (!$is_drink): ?>

                    <!-- Allow customers to add instructions for other food products. -->
                    <div class="combo-customisation">
                        <?php renderSpecialInstructions(); ?>
                    </div>

                <?php endif; ?>

                <!-- Allow customers to select a quantity within the available stock. -->
                <div class="quantity-section">

                    <label for="quantity">Quantity</label>

                    <div class="quantity-controls">

                        <button
                            type="button"
                            class="quantity-btn"
                            id="decrease-quantity"
                            aria-label="Decrease quantity"
                        >−</button>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            value="1"
                            min="1"
                            max="<?= (int) $product["stock_quantity"] ?>"
                            required
                        >

                        <button
                            type="button"
                            class="quantity-btn"
                            id="increase-quantity"
                            aria-label="Increase quantity"
                        >+</button>

                    </div>

                    <small>
                        <?= (int) $product["stock_quantity"] ?>
                        portions available
                    </small>

                    <small id="quantity-feedback" aria-live="polite">
                        Selected: 1 item
                    </small>

                    <p id="quantity-warning" role="alert"></p>

                </div>

                <!-- Include the selected product ID in the cart request. -->
                <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int) $product["product_id"] ?>"
                >

                <!-- Display the Add to Cart button with the calculated price. -->
                <button
                    type="submit"
                    class="add-cart-button"
                    id="add-cart-button"
                    data-price="<?= htmlspecialchars((string) $product["price"]) ?>"
                >
                    Add to Cart — R<span id="cart-total-price"><?= number_format((float) $product["price"], 2) ?></span>
                </button>

            </form>

        </div>
    </div>

</main>

<!-- Display the website footer. -->
<footer>
    <h3>JEJE JUNCTIONS</h3>
    <p>Butchery &amp; Grill</p>
    <p>Good Meat. Great Taste. Real Experience.</p>
    <p>&copy; 2026 Jeje Junctions. All rights reserved.</p>
</footer>

<!-- Load JavaScript for quantity controls and page interactions. -->
<script src="js/script.js?v=5"></script>

</body>
</html>
