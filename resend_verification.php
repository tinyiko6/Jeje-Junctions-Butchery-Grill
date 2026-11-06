<?php
session_start();

require_once __DIR__ . "/db_connect.php";
require_once __DIR__ . "/send_email.php";

$message = "";
$error = "";

/* Process resend verification request */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* Look for an unverified customer */
        $sql = "SELECT customer_id, name,
                       verification_last_sent_at
                FROM customers
                WHERE email = ?
                AND email_verified_at IS NULL
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        /* Same response whether the account exists or not */
        $message = "If your email belongs to an unverified account,
                    a verification email will be sent if eligible.
                    Please check your inbox and Spam folder.";

        if ($customer) {

            $last_sent = $customer["verification_last_sent_at"];

            /* Allow one request every 60 seconds */
            $can_send = (
                $last_sent === null ||
                strtotime($last_sent) <= time() - 60
            );

            if ($can_send) {

                /* Generate a new secure token */
                $token = bin2hex(random_bytes(32));
                $token_hash = hash("sha256", $token);

                /* Link expires in 24 hours */
                $expires_at = date(
                    "Y-m-d H:i:s",
                    time() + 86400
                );

                /* Save new verification token */
                $update_sql = "UPDATE customers
                               SET verification_token_hash = ?,
                                   verification_expires_at = ?,
                                   verification_last_sent_at = NOW()
                               WHERE customer_id = ?
                               AND email_verified_at IS NULL
                               AND (
                                   verification_last_sent_at IS NULL
                                   OR verification_last_sent_at
                                      <= NOW() - INTERVAL 60 SECOND
                               )";

                $update_stmt = $conn->prepare($update_sql);

                $update_stmt->bind_param(
                    "ssi",
                    $token_hash,
                    $expires_at,
                    $customer["customer_id"]
                );

                $update_stmt->execute();

                $updated = $update_stmt->affected_rows === 1;
                $update_stmt->close();

                if ($updated) {

                    
/* Live website verification link */
$verification_link =
    "https://jejejunctions-butchery-grill.free.nf/verify_email.php?token="
    . urlencode($token);


                    $safe_link = htmlspecialchars(
                        $verification_link,
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    $subject = "Verify Your Jeje Junctions Account";

                    $body = "
                        <h2>Jeje Junctions Butchery & Grill</h2>

                        <p>You requested a new email
                        verification link.</p>

                        <p>
                            <a href='{$safe_link}'>
                                Verify My Email Address
                            </a>
                        </p>

                        <p>This link expires in 24 hours.</p>

                        <p>If you did not request this,
                        you can ignore this email.</p>
                    ";

                    /* Send verification email */
                    if (!sendEmail($email, $subject, $body)) {

                        error_log(
                            "Jeje Junctions verification resend failed."
                        );
                    }
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Resend Verification | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css">
    <link rel="stylesheet" href="css/verification.css">
</head>

<body class="verification-body">

    <main class="verification-page">

        <div class="verification-card">

            <!-- Email icon -->
            <div class="verification-icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="36" height="36"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     aria-hidden="true">
                    <rect x="2" y="4"
                          width="20" height="16" rx="2"/>
                    <path d="m22 7-10 6L2 7"/>
                    <path d="M12 16v2"/>
                </svg>
            </div>

            <p class="verification-label">
                ACCOUNT VERIFICATION
            </p>

            <h1>Resend Verification Email</h1>

            <p class="verification-intro">
                Didn't receive your verification email?
                Enter your email address below and
                we'll help you request a new link.
            </p>

            <?php if ($error !== ""): ?>

                <div class="verification-alert error-alert"
                     role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <?php if ($message !== ""): ?>

                <div class="verification-alert info-alert"
                     role="status">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>

            <form method="POST"
                  action="resend_verification.php"
                  class="verification-form">

                <div class="verification-field">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        autocomplete="email"
                        maxlength="254"
                        required
                        value="<?php
                            echo htmlspecialchars(
                                $email ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            );
                        ?>"
                    >

                </div>

                <button type="submit"
                        class="verification-button">
                    Resend Verification Email
                </button>

            </form>

            <div class="verification-note resend-note">
                <strong>Before requesting another email</strong>
                <p>
                    Check your Spam or Junk folder.
                    You can request another verification
                    email after 60 seconds.
                </p>
            </div>

            <a href="login.php"
               class="verification-back">
                ← Back to Login
            </a>

        </div>

    </main>

</body>
</html>
