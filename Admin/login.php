<?php
session_start();
require_once __DIR__ . "/../db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {

        $sql = "SELECT admin_id, username, name, password
                FROM admins
                WHERE username = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $admin = $result->fetch_assoc();

            
if (password_verify($password, $admin["password"])) {

    /* Generate a fresh session ID after successful login */
    session_regenerate_id(true);

    /* Remove any previous customer session */
    unset(
        $_SESSION["customer_id"],
        $_SESSION["customer_name"],
        $_SESSION["customer_email"]
    );

    $_SESSION["admin_id"] = $admin["admin_id"];

                $_SESSION["admin_username"] = $admin["username"];
                $_SESSION["admin_name"] = $admin["name"];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Incorrect username or password.";
            }

        } else {
            $error = "Incorrect username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login - Jeje Junctions</title>

    <link rel="stylesheet" href="css/login.css">
</head>

<body>

<div class="login-page">

    <div class="login-box">

        <h1>Jeje Junctions</h1>

        <p class="login-subtitle">Admin Portal</p>

        <?php if ($error !== "") { ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <form method="POST">

            <label for="username">Username</label>

            <input
                type="text"
                id="username"
                name="username"
                required
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <button type="submit">Login</button>

        </form>

        <a href="../index.php" class="back-link">Back to Website</a>

    </div>

</div>

</body>

</html>