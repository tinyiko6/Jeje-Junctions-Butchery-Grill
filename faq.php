
<?php
session_start();
require_once "db_connect.php";

/* Get FAQs from the admin content section */
$sql = "SELECT title, content
        FROM site_content
        WHERE section = 'FAQ'
        ORDER BY content_id ASC";

$faqs = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css?v=5">
    <link rel="stylesheet" href="css/faq.css">
</head>
<body>

<?php include "navbar.php"; ?>

<main class="faq-page">
    <section class="faq-heading">
        <h1>Frequently Asked Questions</h1>
        <p>Find answers to common questions about Jeje Junctions.</p>
    </section>

    <section class="faq-list">
        <?php if ($faqs && $faqs->num_rows > 0): ?>
            <?php while ($faq = $faqs->fetch_assoc()): ?>
                <details class="faq-item" name="faq-questions">
                    <summary>
                        <?php echo htmlspecialchars($faq["title"]); ?>
                    </summary>
                    <p><?php echo nl2br(htmlspecialchars($faq["content"])); ?></p>
                </details>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No FAQs are available at the moment.</p>
        <?php endif; ?>
    </section>
</main>

</body>
<script src="js/script.js?v=5">
</script>
</html>