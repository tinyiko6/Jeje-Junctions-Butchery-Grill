<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

/* Create a security token for product deletion */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$sql = "SELECT 
            products.product_id,
            products.name,
            products.description,
            products.price,
            products.image,
            products.availability,
            products.stock_quantity,
            categories.name AS category_name
        FROM products
        INNER JOIN categories
        ON products.category_id = categories.category_id
        ORDER BY categories.name ASC, products.name ASC";

$products = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Products | Jeje Junctions Admin</title>

    <link rel="stylesheet" href="css/products.css">
    <!-- Shared admin sidebar styling -->
<link rel="stylesheet" href="css/admin-shared.css">
</head>

<body>

<div class="admin-layout">

  <!-- Shared admin sidebar -->
<?php require __DIR__ . "/sidebar.php"; ?>

  <!-- MAIN CONTENT -->
<main class="main-content">

<!-- Shared admin top bar -->
<?php require __DIR__ . "/topbar.php"; ?>

<!-- Add Product button -->
<div class="product-action-row">
    <a href="add_product.php" class="add-product-btn">
        + Add Product
    </a>
</div>

 <!-- PAGE CONTENT -->

  <section class="products-content">

 <!-- PRODUCTS TABLE -->

  <div class="products-card">

     <?php if ($products && $products->num_rows > 0): ?>

       <div class="products-table-wrapper">
       <table class="products-table">

      <thead>

   <tr>
          <th>PRODUCT</th>
          <th>CATEGORY</th>
          <th>PRICE</th>
          <th>STOCK</th>
          <th>AVAILABILITY</th>
          <th>ACTIONS</th>
   </tr>

   </thead>

   <tbody>

       <?php while ($product = $products->fetch_assoc()): ?>

       <tr>

      <td>

    <div class="product-info">

          <?php if (!empty($product["image"])): ?>

          <img
                 src="../images/<?php echo htmlspecialchars($product["image"]); ?>"
                      alt="<?php echo htmlspecialchars($product["name"]); ?>"  >

          <?php else: ?>

                    <div class="no-image">
                               No image
                      </div>

                           <?php endif; ?>

                       <div>
                  <strong>
                 <?php echo htmlspecialchars($product["name"]); ?>
                    </strong>

              <small>
                <?php echo htmlspecialchars($product["description"]); ?>
                   </small>
                </div>

              </div>

             </td>


             <td>
                    <span class="category">
                     <?php echo htmlspecialchars($product["category_name"]); ?>
                     </span>
             </td>


                   <td class="price">
                           R<?php echo number_format($product["price"], 2); ?>
                           </td>

           
             <td>
                <?php
                  $stock = (int) $product["stock_quantity"];
                   ?>

              <?php if ($stock === 0): ?>

                 <span class="stock-status out-of-stock">
                    Out of Stock
                </span>

           <?php elseif ($stock <= 5): ?>
 
          <span class="stock-status low-stock">
            Low Stock (<?php echo $stock; ?>)
              </span>

           <?php else: ?>

           <span class="stock-status in-stock">
            <?php echo $stock; ?> units
            </span>

           <?php endif; ?>
               </td>
              <td>

                      <span class="availability
                       <?php echo strtolower($product["availability"]); ?>">

                       <?php echo htmlspecialchars($product["availability"]); ?>

                    </span>
 
                    </td>


         <td>

        
<div class="action-buttons">

    <!-- Edit Product -->
    <a
        href="edit_product.php?id=<?php echo (int) $product["product_id"]; ?>"
        class="edit-btn"
    >
        Edit
    </a>

    <!-- Delete Product -->
    <form
        action="delete_product.php"
        method="POST"
        class="delete-form"
        onsubmit="return confirm('Are you sure you want to delete this product?');"
    >

        <input
            type="hidden"
            name="id"
            value="<?php echo (int) $product["product_id"]; ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(
                $_SESSION["csrf_token"],
                ENT_QUOTES,
                "UTF-8"
            ); ?>"
        >

        <button type="submit" class="delete-btn">
            Delete
        </button>

    </form>

</div>

</div>

      </td>

         </tr>

         <?php endwhile; ?>

       </tbody>

         </table>

          </div>

        <?php else: ?>

        <div class="no-products">
       <p>No products have been added yet.</p>
       </div>

      <?php endif; ?>

     </div>
    </section>
    </main>

</div>
</body>
</html>