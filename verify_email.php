<?php
session_start();

require_once "db_connect.php";

$message = "Invalid verification link.";
$verified = false;

$token = $_GET["token"] ?? "";

if (is_string($token) &&
    preg_match("/^[a-f0-9]{64}$/", $token)) {

    $token_hash = hash("sha256", $token);

    $sql = "SELECT customer_id
            FROM customers
            WHERE verification_token_hash = ?
            AND verification_expires_at > NOW()
            AND email_verified_at IS NULL
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $customer = $result->fetch_assoc();

        $update_sql = "UPDATE customers
                       SET email_verified_at = NOW(),
                           verification_token_hash = NULL,
                           verification_expires_at = NULL
                       WHERE customer_id = ?
                       AND verification_token_hash = ?
                       AND email_verified_at IS NULL";

        $update_stmt = $conn->prepare($update_sql);

        $update_stmt->bind_param(
            "is",
            $customer["customer_id"],
            $token_hash
        );

        $update_stmt->execute();

        if ($update_stmt->affected_rows === 1) {
            $verified = true;
            $message = "Your email has been verified successfully!";
            unset($_SESSION["test_verification_link"]);
        }

        $update_stmt->close();
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Email Verification | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css">
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="css/verification.css">
</head>

<body class="verification-body">

    <main class="verification-page">

        <div class="verification-card">

            <?php if ($verified): ?>

                <!-- Successful verification -->
                <div class="verification-icon verification-success">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="38" height="38"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="m8 12 3 3 5-6"/>
                    </svg>
                </div>

                <p class="verification-label success-label">
                    VERIFICATION SUCCESSFUL
                </p>

                <h1>Email Verified!</h1>

                <p class="verification-intro">
                    Your email address has been verified
                    successfully.
                </p>

                <div class="verification-message">
                    <p>
                        Welcome to Jeje Junctions!
                        Your account is now active.
                    </p>

                    <p>
                        You can log in, browse our menu,
                        add meals to your cart and place orders.
                    </p>
                </div>

                <a href="login.php"
                   class="verification-button">
                    Continue to Login
                </a>

            <?php else: ?>

                <!-- Invalid or expired verification link -->
                <div class="verification-icon verification-error">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="38" height="38"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>
                </div>

                <p class="verification-label">
                    VERIFICATION FAILED
                </p>

                <h1>Link Not Valid</h1>

                <p class="verification-intro">
                    <?php echo htmlspecialchars($message); ?>
                </p>

                <div class="verification-note">
                    <strong>Having trouble?</strong>
                    <p>
                        Your verification link may have
                        expired, already been used or be invalid.
                        If you have already verified your email,
                        try logging in.
                    </p>
                </div>

                <a href="resend_verification.php"
                   class="verification-button">
                    Resend Verification Email
                </a>

                <a href="login.php"
                   class="verification-back">
                    ← Back to Login
                </a>

            <?php endif; ?>

        </div>

    </main>

</body>
</html>
