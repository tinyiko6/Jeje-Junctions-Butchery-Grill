<?php

session_start();
require_once "db_connect.php";

/* Load the email sending function */
require_once __DIR__ . "/send_email.php";

$error = "";
$success = "";


/* Check if registration form was submitted */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    /* Check required fields */
    if (
        $name === "" ||
        $email === "" ||
        $phone === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please complete all fields.";

    }


    /* Check email */
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }



/* Check password strength */
elseif (strlen($password) < 8) {

    $error = "Password must be at least 8 characters long.";

} elseif (!preg_match("/[A-Z]/", $password)) {

    $error = "Password must contain at least one uppercase letter.";

} elseif (!preg_match("/[a-z]/", $password)) {

    $error = "Password must contain at least one lowercase letter.";

} elseif (!preg_match("/[0-9]/", $password)) {

    $error = "Password must contain at least one number.";

} elseif (!preg_match("/[^a-zA-Z0-9]/", $password)) {

    $error = "Password must contain at least one special character.";

}


    /* Check passwords */
    elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    }


    else {

        /* Check whether email already exists */
        $sql = "SELECT customer_id
                FROM customers
                WHERE email = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows > 0) {

            $error = "An account with this email already exists.";

        } else {

            /* Securely hash password */
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            
/* Generate a secure email verification token */
$verification_token = bin2hex(random_bytes(32));

/* Store only the hash of the token */
$verification_token_hash = hash("sha256", $verification_token);

/* Verification link expires after 24 hours */
$verification_expires_at = date(
    "Y-m-d H:i:s",
    time() + 86400
);

/* Insert customer with email verification details */
$sql = "INSERT INTO customers
        (
            name,
            email,
            phone,
            password,
            verification_token_hash,
            verification_expires_at
        )
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssssss",
    $name,
    $email,
    $phone,
    $hashed_password,
    $verification_token_hash,
    $verification_expires_at
);


if ($stmt->execute()) {

/* Create the email verification link for our live website */
$verification_link =
    "https://jejejunctions-butchery-grill.free.nf/verify_email.php?token="
    . urlencode($verification_token);

    /* Escape the URL before inserting it into HTML */
    $safe_link = htmlspecialchars(
        $verification_link,
        ENT_QUOTES,
        "UTF-8"
    );

    /* Prepare the verification email */
    $subject = "Verify Your Jeje Junctions Account";

    $body = "
        <h2>Welcome to Jeje Junctions!</h2>

        <p>Thank you for creating an account with Jeje Junctions Butchery & Grill.</p>
        <p>Please verify your email address  by clicking the link below:</p>
        <p> <a href='{$safe_link}'> Verify My Email Address
            </a> </p>
        <p>This verification link expires in 24 hours.</p>
        <p>If you did not create this account, you can ignore this email.</p> ";

    /* Send the verification email */
    if (sendEmail($email, $subject, $body)) {

        /* Never expose the token on the website */
        unset($_SESSION["test_verification_link"]);

        header("Location: registration_pending.php");
        exit();

    } else {

        /* Account exists, but email delivery failed */
        $error = "Your account was created, but the verification email
                  could not be sent. Please contact support.";

    }

} else {

    $error = "Registration failed. Please try again.";
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
    <title>Register | Jeje Junctions</title>
    <link rel="stylesheet" href="css/shared.css?v=6">
    <link rel="stylesheet" href="css/register.css">

</head>
<body>

    <!-- Navigation -->
  <?php include "navbar.php"; ?>

    <!-- Registration -->

    <main class="register-page">

        <div class="register-box">

            <h1>Create Account</h1>

            <p class="register-intro">
                Create your Jeje Junctions customer account.
            </p>


            <?php if ($error !== "") { ?>

                <div class="register-error">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php } ?>

            <form method="POST" action="register.php">

                <!-- Name -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($_POST["name"] ?? ""); ?>"
                        required   >

                </div>

                <!-- Email -->

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                        required   >

                </div>

                <!-- Phone -->
                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?php echo htmlspecialchars($_POST["phone"] ?? ""); ?>"
                        required >

                </div>

<!-- Password -->
<div class="form-group">
    <label for="password">Password</label>

    <div class="password-field">
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            autocomplete="new-password"
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

    <small class="password-hint">
        Use at least 8 characters, including uppercase and
        lowercase letters, a number and a special character.
    </small>
</div>

<!-- Confirm Password -->
<div class="form-group">
    <label for="confirm_password">Confirm Password</label>

    <div class="password-field">
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            placeholder="Confirm your password"
            autocomplete="new-password"
            required
        >

        <button
            type="button"
            class="password-toggle"
            data-target="confirm_password"
            aria-label="Show confirm password"
            aria-pressed="false"
             >Show
          </button>
    </div>
    </div>

     <button
    type="submit"
    class="register-button">

    Create Account
     </button>

            </form>

            <p class="login-text"> Already have an account?

                <a href="login.php">  Login here
                </a>

            </p>
        </div>

    </main>

    <!-- Footer -->

    <footer>

        <h3>JEJE JUNCTIONS</h3>
        <p>Butchery & Grill</p>
        <p>Good Meat. Great Taste. Real Experience.  </p>
        <p> &copy; 2026 Jeje Junctions. All rights reserved.
        </p>

    </footer>
    <script src="js/script.js?v=6"></script>
</body>
</html>