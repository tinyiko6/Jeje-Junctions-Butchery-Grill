<?php
session_start();
require_once "../db_connect.php";

/* Only administrators can access payments */
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

/*  CONFIRM CUSTOMER PAYMENT */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["confirm_payment"])) {

    $payment_id = (int) ($_POST["payment_id"] ?? 0);

    if ($payment_id > 0) {

        try {
            $conn->begin_transaction();

            /* Lock the payment and its order */
            $sql = "SELECT p.payment_status, o.status AS order_status
                    FROM payments p
                    INNER JOIN orders o ON p.order_id = o.order_id
                    WHERE p.payment_id = ?
                    FOR UPDATE";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();

            $payment = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            /* Only confirm pending, non-cancelled payments */
            if ($payment
                && $payment["payment_status"] === "Pending"
                && $payment["order_status"] !== "Cancelled") {

                $sql = "UPDATE payments
                        SET payment_status = 'Paid',
                            payment_date = NOW()
                        WHERE payment_id = ?
                        AND payment_status = 'Pending'";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $payment_id);
                $stmt->execute();

             if ($stmt->affected_rows !== 1) {
            throw new Exception("Payment was not updated.");
             }

            
                $stmt->close();

                /* Payment was updated successfully */
                $conn->commit();

                $_SESSION["payment_message"] =
                    "Payment confirmed successfully.";

                $_SESSION["payment_message_type"] = "success";

            } else {

                /* Payment is already paid, cancelled or unavailable */
                $conn->rollback();

                $_SESSION["payment_message"] =
                    "This payment cannot be confirmed.";

                $_SESSION["payment_message_type"] = "error";
            }

        } catch (Throwable $e) {


          $conn->rollback();

         error_log("Payment confirmation failed: " . $e->getMessage());

        $_SESSION["payment_message"] =
        "Something went wrong while confirming the payment.";

        $_SESSION["payment_message_type"] = "error";
        }
    }

    header("Location: payments.php");
    exit();
}


/* Retrieve customer payments and their orders */
$sql = "SELECT
            p.payment_id,
            p.order_id,
            p.method,
            p.payment_status,
            p.payment_date,
            o.total_amount,
            o.status AS order_status,
            c.name AS customer_name
        FROM payments p
        INNER JOIN orders o ON p.order_id = o.order_id
        INNER JOIN customers c ON o.customer_id = c.customer_id
        ORDER BY p.payment_id DESC";

$payments = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payments | Jeje Junctions Admin</title>

    <link rel="stylesheet" href="css/payments.css">
    <link rel="stylesheet" href="css/admin-shared.css">
</head>

<body>

<div class="admin-layout">

    <!-- Shared sidebar -->
    <?php require __DIR__ . "/sidebar.php"; ?>

    <main class="main-content">

        <!-- Shared top bar -->
        <?php require __DIR__ . "/topbar.php"; ?>

        <section class="payments-content">

        
       <!-- Payment feedback message -->

     <?php if (isset($_SESSION["payment_message"])): ?>

    <div class="payment-alert <?php
        echo ($_SESSION["payment_message_type"] ?? "") === "success"
            ? "alert-success"
            : "alert-error";
    ?>">

        <?php echo htmlspecialchars($_SESSION["payment_message"]); ?>

    </div>

    <?php
        unset(
            $_SESSION["payment_message"],
            $_SESSION["payment_message_type"]
        );
    ?>

<?php endif; ?>

            <div class="payments-card">

                <h2>Customer Payments</h2>

                <div class="payments-table-wrapper">

                    <table class="payments-table">

               <thead>
           <tr>
              <th>ORDER</th>
              <th>CUSTOMER</th>
              <th>AMOUNT</th>
              <th>METHOD</th>
              <th>PAYMENT</th>
              <th>ORDER STATUS</th>
              <th>ACTION</th>
              </tr>
             </thead>

              <tbody>

             <?php if ($payments && $payments->num_rows > 0): ?>

               <?php while ($payment = $payments->fetch_assoc()): ?>

             <tr>
              <td>
              #<?php echo (int) $payment["order_id"]; ?>
                   </td>

                  <td>
             <?php echo htmlspecialchars($payment["customer_name"]); ?>
             </td>

                   <td>
                  R<?php echo number_format($payment["total_amount"], 2); ?>
             </td>

                  <td>
                 <?php echo htmlspecialchars($payment["method"]); ?>
                 </td>

                   <td>
               <?php echo htmlspecialchars($payment["payment_status"]); ?>
             </td>

             <td>
             <?php echo htmlspecialchars($payment["order_status"]); ?>
                </td>

              
<td>

    <?php if ($payment["payment_status"] === "Paid"): ?>

        <span class="payment-paid">Paid</span>

    <?php elseif ($payment["order_status"] === "Cancelled"): ?>

        <span class="payment-cancelled">Cancelled</span>

    <?php else: ?>

        <form method="POST"
              onsubmit="return confirm('Has the customer paid the full amount?');">

            <input
                type="hidden"
                name="payment_id"
                value="<?php echo (int) $payment["payment_id"]; ?>"
            >

            <button
                type="submit"
                name="confirm_payment"
                class="confirm-payment-btn"
            >
                Confirm Payment
            </button>

        </form>

    <?php endif; ?>

</td>

            </tr>

             <?php endwhile; ?>

         <?php else: ?>

           <tr>
     <td colspan="7">
              No payments found.
     </td>
 </tr>

     <?php endif; ?>

     </tbody>

   </table>

   </div>

   </div>
   </section>

    </main>

</div>

</body>
</html>
