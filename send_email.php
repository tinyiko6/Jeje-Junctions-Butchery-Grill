
<?php

/* Send transactional emails using the Brevo HTTPS API */

function sendEmail($recipient, $subject, $body) {

    /* Load protected email settings */
    $config = require __DIR__ . "/private_config/email_config.php";

    /* Check email address */
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    /* Prepare the email data */
    $emailData = [
        "sender" => [
            "name" => $config["from_name"],
            "email" => $config["from_email"]
        ],
        "to" => [
            ["email" => $recipient]
        ],
        "subject" => $subject,
        "htmlContent" => $body,
        "textContent" => trim(strip_tags($body))
    ];

    $jsonData = json_encode($emailData);

    if ($jsonData === false || !function_exists("curl_init")) {
        error_log("Jeje email: unable to prepare API request.");
        return false;
    }

    /* Connect to Brevo */
    $ch = curl_init("https://api.brevo.com/v3/smtp/email");

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json",
            "api-key: " . $config["api_key"]
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20
    ]);

    /* Send the request */
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    /* Brevo accepts a successful send request with HTTP 201 */
    if ($response !== false && $httpCode === 201) {
        return true;
    }

    /* Log failure without exposing the API key */
    error_log(
        "Jeje Brevo email failed. HTTP status: " .
        $httpCode .
        ($curlError !== "" ? " Connection error: " . $curlError : "")
    );

    return false;
}
