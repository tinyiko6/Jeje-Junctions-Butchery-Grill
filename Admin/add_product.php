<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

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

/* Handle product image upload */
$image_name = "";

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

        if (!isset($allowed_types[$file_type]) ||
            @getimagesize($image_tmp) === false) {

            $error = "Please upload a valid JPG, PNG or WEBP image.";

        } else {

            /* Generate a unique, safe filename */
            $extension = $allowed_types[$file_type];

            $image_name = bin2hex(random_bytes(16))
                        . "." . $extension;

            $image_folder = __DIR__ . "/../images/";

            /* Save the uploaded image */
            if (!move_uploaded_file(
                $image_tmp,
                $image_folder . $image_name
            )) {

                $error = "Unable to save the product image.";
            }
        }
    }
}

        }

        if ($error === "") {

            $sql = "INSERT INTO products
                    (category_id, name, description, price, image, availability, stock_quantity)
                    VALUES (?, ?, ?, ?, ?, ?,?)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
    "issdssi",
    $category_id,
    $name,
    $description,
    $price,
    $image_name,
    $availability,
    $stock_quantity
);
            if ($stmt->execute()) {

                header("Location: products.php");
                exit();

            } else {

                $error = "Something went wrong while adding the product.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Product | Jeje Junctions Admin</title>

    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/admin-shared.css">

</head>

<body>

<div class="admin-layout">

   
<?php require __DIR__ . "/sidebar.php"; ?>

<main class="main-content">

    <?php require __DIR__ . "/topbar.php"; ?>

        </header>

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

                        <label for="name">
                            Product Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="category_id">
                            Category
                        </label>

                        <select
                            id="category_id"
                            name="category_id"
                            required
                        >

                            <option value="">
                                Select a category
                            </option>

                            <?php while ($category = $categories->fetch_assoc()): ?>

                                <option
                                    value="<?php echo $category["category_id"]; ?>"
                                >
                                    <?php echo htmlspecialchars($category["name"]); ?>
                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            required
                        ></textarea>

                    </div>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="price">
                                Price
                            </label>

                            <input
                                type="number"
                                id="price"
                                name="price"
                                step="0.01"
                                min="0"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="availability">
                                Availability
                            </label>

                            <select
                                id="availability"
                                name="availability"
                            >

                                <option value="Available">
                                    Available
                                </option>

                                <option value="Unavailable">
                                    Unavailable
                                </option>

                            </select>

                        </div>
                        <div class="form-group">

                    <label for="stock_quantity">
                        Stock Quantity
                     </label>

                          <input
                        type="number"
                        id="stock_quantity"
                        name="stock_quantity"
                        min="0"
                        step="1"
                        value="0"
                        required
                        >

                    </div>
                    </div>

                    <div class="form-group">

                        <label for="image">
                            Product Image
                        </label>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                    </div>

                    <div class="form-actions">

                        <a
                            href="products.php"
                            class="cancel-btn"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="save-btn"
                        >
                            Add Product
      </button>
        </div>

     </form>

   </div>

 </section>

   </main>

</div>

</body>
</html>