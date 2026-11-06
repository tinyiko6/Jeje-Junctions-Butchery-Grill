<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

$product_id = $_GET["id"] ?? "";

if ($product_id === "" || !is_numeric($product_id)) {
    header("Location: products.php");
    exit();
}

$product_id = (int) $product_id;

$sql = "SELECT * FROM products WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

$error = "";

$categories = $conn->query(
    "SELECT category_id, name
     FROM categories
     ORDER BY name ASC"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $stock_quantity = trim($_POST["stock_quantity"] ?? "");
    $availability = $_POST["availability"] ?? "Available";

   if (
    $name === "" ||
    $description === "" ||
    $price === "" ||
    $category_id === "" ||
    $stock_quantity === ""
) {
    $error = "Please complete all required fields.";

} elseif (!is_numeric($price) || $price < 0) {
    $error = "Please enter a valid price.";

} elseif (
    filter_var($stock_quantity, FILTER_VALIDATE_INT) === false ||
    (int) $stock_quantity < 0
) {
    $error = "Please enter a valid stock quantity (a whole number of zero or more).";

} else {

        
/* Keep the existing image unless a new one is uploaded */
$image_name = $product["image"];

/* Check whether a replacement image was selected */
if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

        $error = "Image upload failed. Please try again.";

    } elseif ($_FILES["image"]["size"] > 2 * 1024 * 1024) {

        $error = "Image must be smaller than 2 MB.";

    } else {

        $image_tmp = $_FILES["image"]["tmp_name"];

        /* Check the actual image format */
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $file_type = $finfo->file($image_tmp);

        $allowed_types = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        if (
            !isset($allowed_types[$file_type]) ||
            @getimagesize($image_tmp) === false
        ) {

            $error = "Please upload a valid JPG, PNG or WEBP image.";

        } else {

            /* Generate a unique filename */
            $extension = $allowed_types[$file_type];

            $new_image_name =
                bin2hex(random_bytes(16)) . "." . $extension;

            $image_folder = __DIR__ . "/../images/";

            /* Save the replacement image */
            if (move_uploaded_file(
                $image_tmp,
                $image_folder . $new_image_name
            )) {

                $image_name = $new_image_name;

            } else {

                $error = "Unable to save the product image.";
            }
        }
    }
}


        if ($error === "") {

          $sql = "UPDATE products
        SET category_id = ?,
            name = ?,
            description = ?,
            price = ?,
            image = ?,
            availability = ?,
            stock_quantity = ?
        WHERE product_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
    "issdssii",
    $category_id,
    $name,
    $description,
    $price,
    $image_name,
    $availability,
    $stock_quantity,
    $product_id
);

            if ($stmt->execute()) {
                header("Location: products.php");
                exit();
            } else {
                $error = "Something went wrong while updating the product.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | Jeje Junctions Admin</title>

    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/admin-shared.css">

</head>

<body>

<div class="admin-layout">

    
<?php require __DIR__ . "/sidebar.php"; ?>

<main class="main-content">

    <?php require __DIR__ . "/topbar.php"; ?>


        <section class="products-content">

            <div class="products-card">

                <?php if ($error !== ""): ?>
                    <div class="form-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form
                    action=""
                    method="POST"
                    enctype="multipart/form-data"
                    class="product-form"
                >

                    <div class="form-group">
                        <label for="name">Product Name</label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo htmlspecialchars($product["name"]); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>

                        <select id="category_id" name="category_id" required>

                            <?php while ($category = $categories->fetch_assoc()): ?>

                                <option
                                    value="<?php echo $category["category_id"]; ?>"
                                    <?php echo $category["category_id"] == $product["category_id"] ? "selected" : ""; ?>
                                >
                                    <?php echo htmlspecialchars($category["name"]); ?>
                                </option>

                            <?php endwhile; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>

                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            required
                        ><?php echo htmlspecialchars($product["description"]); ?></textarea>
                    </div>

                    <div class="form-row">

                        <div class="form-group">
                            <label for="price">Price</label>

                            <input
                                type="number"
                                id="price"
                                name="price"
                                step="0.01"
                                min="0"
                                value="<?php echo htmlspecialchars($product["price"]); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="availability">Availability</label>

                            <select id="availability" name="availability">

                                <option
                                    value="Available"
                                    <?php echo $product["availability"] === "Available" ? "selected" : ""; ?>
                                >
                                    Available
                                </option>

                                <option
                                    value="Unavailable"
                                    <?php echo $product["availability"] === "Unavailable" ? "selected" : ""; ?>
                                >
                                    Unavailable
                                </option>

                            </select>
                        </div>
                        <div class="form-group">
    <label for="stock_quantity">Stock Quantity</label>

    <input
        type="number"
        id="stock_quantity"
        name="stock_quantity"
        min="0"
        step="1"
        value="<?php echo (int) $product["stock_quantity"]; ?>"
        required
    >
</div>
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <?php if (!empty($product["image"])): ?>
                            <small style="display:block; margin-top:8px; color:#888;">
                                Current image: <?php echo htmlspecialchars($product["image"]); ?>
                            </small>
                        <?php endif; ?>

                    </div>

                    <div class="form-actions">

                        <a href="products.php" class="cancel-btn">
                            Cancel
                        </a>

                        <button type="submit" class="save-btn">
                            Save Changes
                        </button>

                    </div>

    </form>

    </div>

    </section>
    </main>

</div>
</body>
</html>