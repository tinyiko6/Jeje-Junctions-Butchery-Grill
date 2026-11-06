<?php
session_start();

require_once __DIR__ . "/../db_connect.php";

/* Only administrators can delete products */
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

/* Only allow POST requests */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit();
}

/* Verify the security token */
$submitted_token = $_POST["csrf_token"] ?? "";
$saved_token = $_SESSION["csrf_token"] ?? "";

if (
    !is_string($submitted_token) ||
    !is_string($saved_token) ||
    $saved_token === "" ||
    !hash_equals($saved_token, $submitted_token)
) {
    http_response_code(403);
    exit("Invalid request.");
}

/* Validate the product ID */
$product_id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if ($product_id === false || $product_id === null || $product_id < 1) {
    header("Location: products.php");
    exit();
}

/* Delete the selected product */
$sql = "DELETE FROM products WHERE product_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    header("Location: products.php");
    exit();
}

error_log("Jeje Junctions: Product deletion failed.");
http_response_code(500);
exit("Unable to delete product.");
?>
