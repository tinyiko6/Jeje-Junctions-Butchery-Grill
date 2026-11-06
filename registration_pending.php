<?php
session_start();

/* Remove any old local testing verification link */
unset($_SESSION["test_verification_link"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Check Your Email | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css">
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="css/verification.css">
</head>

<body class="verification-body">

    <main class="verification-page">

        <div class="verification-card">

            <div class="verification-icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="34" height="34"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     aria-hidden="true">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="m22 7-10 6L2 7"/>
                    <path d="m8 16 2 2 5-5"/>
                </svg>
            </div>

            <p class="verification-label">
                ACCOUNT VERIFICATION
            </p>

            <h1>Check Your Email</h1>

            <p class="verification-intro">
                You're one step away from enjoying
                Jeje Junctions online ordering.
            </p>

            <div class="verification-message">
                <p>
                    If your registration was successful,
                    we've sent a verification link
                    to your email address.
                </p>

                <p>
                    Open your inbox and click the link
                    to activate your account.
                </p>
            </div>

            <div class="verification-note">
                <strong>Didn't see the email?</strong>
                <p>
                    Check your Spam or Junk folder.
                    Your verification link expires after 24 hours.
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

        </div>

    </main>

</body>
</html>
