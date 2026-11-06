<?php
session_start();

/* Connect to the database from the main project folder */
require_once __DIR__ . "/db_connect.php";

$error = "";

/* Check if the login form was submitted */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* Accept either a customer email or an admin username */
    $login = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";

    /* Check that both fields were entered */
    if ($login === "" || $password === "") {

        $error = "Please enter your email or username and password.";

    } else {

        /*  STEP 1: CHECK CUSTOMER ACCOUNT */

       
/* Check customer account and email verification status */
$sql = "SELECT customer_id, name, email, password,
               email_verified_at
        FROM customers
        WHERE email = ?";


        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();

       
if ($customer && password_verify($password, $customer["password"])) {

    /* Block customers who have not verified their email */
   
if ($customer["email_verified_at"] === null) {

    $error = "Please verify your email address before logging in.";

} else {


        /* Create a fresh session ID after login */
        session_regenerate_id(true);

        /* Remove any previous admin login */
        unset(
            $_SESSION["admin_id"],
            $_SESSION["admin_username"],
            $_SESSION["admin_name"]
        );

        /* Save customer information */
        $_SESSION["customer_id"] = $customer["customer_id"];
        $_SESSION["customer_name"] = $customer["name"];
        $_SESSION["customer_email"] = $customer["email"];

        /* Redirect customer to the homepage */
        header("Location: index.php");
        exit();
    }
}

/* Only check admin login if there is no customer verification error */
if ($error === "") {

        /*  STEP 2: CHECK ADMIN ACCOUNT */

        $sql = "SELECT admin_id, username, name, password
                FROM admins
                WHERE username = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $login);

        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin["password"])) {

            /* Create a fresh session ID after login */
            session_regenerate_id(true);

            /* Remove any previous customer login */
            unset(
                $_SESSION["customer_id"],
                $_SESSION["customer_name"],
                $_SESSION["customer_email"]
            );

            /* Save administrator information */
            $_SESSION["admin_id"] = $admin["admin_id"];
            $_SESSION["admin_username"] = $admin["username"];
            $_SESSION["admin_name"] = $admin["name"];


            /* Redirect admin to the dashboard */
            header("Location: Admin/dashboard.php");
            exit();

        }

        /* Neither account matched */
        $error = "Incorrect login details or password.";

    }
}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css?v=5">
    <link rel="stylesheet" href="css/login.css">

</head>

<body>

    <!-- Navigation -->
   
<?php include "navbar.php"; ?>
    <!-- Login -->

    <main class="login-page">

        <div class="login-box">

            <h1>Welcome Back</h1>
              <p class="login-intro">
                Login to your Jeje Junctions account.
            </p>


            <?php if ($error !== "") { ?>

                <div class="login-error">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php } ?>

            
<?php if ($error === "Please verify your email address before logging in."): ?>

    <p class="register-text">
        Didn't receive the email?
        <a href="resend_verification.php">
            Resend Verification Email
        </a>
    </p>

<?php endif; ?>

            <form method="POST" action="login.php">

                <div class="form-group">

                    <label for="email">
                       Email Address or Admin Username
                      </label>

                    <input
                      type="text"
                      id="email"
                      name="email"
                      placeholder="Enter your email or username"
                      autocomplete="username"
                      required >
                </div>

                <div class="form-group"> 
                    
               <div class="form-group">

    <label for="password">Password</label>

    <div class="password-field">
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
        >

        <button
            type="button"
            class="password-toggle"
            data-target="password"
            aria-label="Show password"
            aria-pressed="false"
        >Show</button>
    </div>
</div>

<!-- Login Button -->
<button type="submit" class="login-button">
    Login
</button>

</form>
            <p class="register-text"> Don't have an account?
       <a href="register.php">   Create an account</a>

        </p>
        </div>

    </main>

    <!-- Footer -->

    <footer>

        <h3>JEJE JUNCTIONS</h3>
        <p>Butchery & Grill</p>
        <p> Good Meat. Great Taste. Real Experience. </p>

        <p>   &copy; 2026 Jeje Junctions. All rights reserved.
        </p>

    </footer>
    <script src="js/script.js?v=5"></script>
</body>
</html>