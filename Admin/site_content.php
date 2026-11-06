<?php
session_start();
require_once "../db_connect.php";

/* Only logged-in administrators can manage site content */
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* Save Our Story */
    if (isset($_POST["save_about"])) {
        $about_content = trim($_POST["about_content"] ?? "");

        $sql = "UPDATE site_content
                SET content = ?
                WHERE section = 'About Us'
                AND title = 'About Jeje Junctions'
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $about_content);
            $stmt->execute();
            $stmt->close();
        }
    }
    /* Save Operating Hours */
    if (isset($_POST["save_hours"])) {
        $hour_ids = $_POST["hour_id"] ?? [];
        $hour_titles = $_POST["hour_title"] ?? [];
        $hour_contents = $_POST["hour_content"] ?? [];

        $sql = "UPDATE site_content
                SET title = ?, content = ?
                WHERE content_id = ?
                AND section = 'Operating Hours'";

        $stmt = $conn->prepare($sql);

        foreach ($hour_ids as $index => $id) {
            $title = trim($hour_titles[$index] ?? "");
            $content = trim($hour_contents[$index] ?? "");
            $id = (int) $id;

            $stmt->bind_param("ssi", $title, $content, $id);
            $stmt->execute();
        }
    }
    /* Add FAQ */
if (isset($_POST["add_faq"])) {
    $question = trim($_POST["faq_question"] ?? "");
    $answer = trim($_POST["faq_answer"] ?? "");

    if ($question !== "" && $answer !== "") {
        $sql = "INSERT INTO site_content (section, title, content)
                VALUES ('FAQ', ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $question, $answer);
        $stmt->execute();
    }
}


/* Edit FAQ */
if (isset($_POST["edit_faq"])) {
    $faq_id = (int) ($_POST["faq_id"] ?? 0);
    $question = trim($_POST["faq_question"] ?? "");
    $answer = trim($_POST["faq_answer"] ?? "");

    if ($faq_id > 0 && $question !== "" && $answer !== "") {
        $sql = "UPDATE site_content
                SET title = ?, content = ?
                WHERE content_id = ? AND section = 'FAQ'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $question, $answer, $faq_id);
        $stmt->execute();
    }
}


/* Delete FAQ */
if (isset($_POST["delete_faq"])) {
    $faq_id = (int) ($_POST["faq_id"] ?? 0);

    if ($faq_id > 0) {
        $sql = "DELETE FROM site_content
                WHERE content_id = ? AND section = 'FAQ'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $faq_id);
        $stmt->execute();
    }
}
    header("Location: site_content.php");
    exit();
}

/* About */
$about_sql = "SELECT content_id, title, content
              FROM site_content
              WHERE section = 'About Us'
              LIMIT 1";

$about_result = $conn->query($about_sql);
$about = $about_result->fetch_assoc();

/* Operating hours */
$hours_sql = "SELECT content_id, title, content
              FROM site_content
              WHERE section = 'Operating Hours'
              ORDER BY content_id ASC";

$hours = $conn->query($hours_sql);

/* FAQs */
$faq_sql = "SELECT content_id, title, content
            FROM site_content
            WHERE section = 'FAQ'
            ORDER BY content_id ASC";

$faqs = $conn->query($faq_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Content - Jeje Junctions</title>

    <!-- Site Content page styling -->
<link rel="stylesheet" href="css/site_content.css">

<!-- Shared sidebar and top bar styling -->
<link rel="stylesheet" href="css/admin-shared.css">

</head>

<body>

<div class="admin-layout">

    <!-- Shared admin sidebar -->
    <?php require __DIR__ . "/sidebar.php"; ?>

    <!-- Main content -->
    <main class="main-content">

        <!-- Shared admin top bar -->
        <?php require __DIR__ . "/topbar.php"; ?>

        <!-- Website content management -->
    <section class="site-content">

    <div class="content-grid">
                <!-- About Page Content -->

                <div class="content-card about-card">

                    <h2>About Page Content</h2>

                    <label>Our Story</label>

                    <form method="POST">

    <textarea name="about_content"><?php echo htmlspecialchars($about["content"] ?? ""); ?></textarea>

 <button type="submit" name="save_about" class="save-btn">Save Changes</button>

</form>

          <h3>Operating Hours</h3>

<form method="POST">

    <?php while ($hour = $hours->fetch_assoc()): ?>

        <div class="hours-row">
            <input type="hidden" name="hour_id[]" value="<?php echo $hour["content_id"]; ?>">

            <input type="text" name="hour_title[]" value="<?php echo htmlspecialchars($hour["title"]); ?>" required>

            <input type="text" name="hour_content[]" value="<?php echo htmlspecialchars($hour["content"]); ?>" required>
        </div>

    <?php endwhile; ?>

    <button type="submit" name="save_hours" class="save-btn">Save Changes</button>

</form>

</div>

   <!-- FAQs -->

      <div class="content-card faq-card">

           <div class="faq-header">
              <h2>Manage FAQs</h2>
             <button type="button" class="add-faq-btn" onclick="document.getElementById('addFaqForm').style.display = 'block';">
    + Add FAQ
</button>
              </div>
<div id="addFaqForm" class="add-faq-form" style="display: none;">
    <form method="POST">
        <label>Question</label>
        <input type="text" name="faq_question" required>

        <label>Answer</label>
        <textarea name="faq_answer" required></textarea>

        <button type="submit" name="add_faq" class="save-btn">Save FAQ</button>
        <button type="button" class="cancel-btn" onclick="document.getElementById('addFaqForm').style.display = 'none';">Cancel</button>
    </form>
</div>
      <div class="faq-table-wrapper">

         <table class="faq-table">

          <thead>
    <tr>
          <th>Question</th>
          <th>Answer</th>
          <th>Actions</th>
     </tr>
        </thead>

         <tbody>

       <?php if ($faqs->num_rows > 0): ?>

       <?php while ($faq = $faqs->fetch_assoc()): ?>

           <tr>
        <td><?php echo htmlspecialchars($faq["title"]); ?></td>

       <td><?php echo htmlspecialchars($faq["content"]); ?></td>

                   <td>
            <div class="faq-actions">
    <button
        type="button"
        class="edit-btn"
        onclick="document.getElementById('editFaq<?php echo $faq['content_id']; ?>').style.display = 'block';">
        Edit
    </button>

  
<form method="POST" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
    <input type="hidden" name="faq_id"
           value="<?php echo $faq['content_id']; ?>">

    <button type="submit" name="delete_faq" class="delete-btn">
        Delete
    </button>
</form>  
</div>
                 </td>
              </tr>
<tr id="editFaq<?php echo $faq['content_id']; ?>" style="display: none;">
    <td colspan="3">
        <form method="POST" class="add-faq-form">
            <input type="hidden" name="faq_id"
                   value="<?php echo $faq['content_id']; ?>">

            <label>Question</label>
            <input type="text" name="faq_question"
                   value="<?php echo htmlspecialchars($faq['title']); ?>" required>

            <label>Answer</label>
            <textarea name="faq_answer" required><?php echo htmlspecialchars($faq['content']); ?></textarea>

            <button type="submit" name="edit_faq" class="edit-btn">Save Changes</button>

            <button type="button" class="cancel-btn"
                    onclick="document.getElementById('editFaq<?php echo $faq['content_id']; ?>').style.display = 'none';">
                Cancel
            </button>
        </form>
    </td>
</tr>
          <?php endwhile; ?>

         <?php else: ?>

        <tr>
        <td colspan="3">No FAQs have been added yet.</td>
               </tr>

      <?php endif; ?>

    </tbody>
   </table>

        </div>
    </div>
   </div>

   </section>

   </main>

</div>

</body>
</html>