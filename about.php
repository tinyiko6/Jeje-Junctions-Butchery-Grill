<?php
session_start();

require_once "db_connect.php";

/* ==========================================
   GET ABOUT US CONTENT FROM THE DATABASE
========================================== */

/* Retrieve the business story saved by the admin */
$sql = "SELECT content
        FROM site_content
        WHERE section = 'About Us'
        AND title = 'About Jeje Junctions'
        LIMIT 1";

/* Run the SQL query */
$result = $conn->query($sql);

/* Store the retrieved story */
$about = $result ? $result->fetch_assoc() : null;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us | Jeje Junctions</title>

    <link rel="stylesheet" href="css/shared.css?v=5">
    <link rel="stylesheet" href="css/about.css">

</head>
<body>

<!-- ==========================================
     WEBSITE NAVIGATION
========================================== -->

<?php include "navbar.php"; ?>

<main class="about-page">


    <!-- ==========================================
         ABOUT US BANNER
    ========================================== -->

    <!-- The grilled-meat background image is added in CSS -->
    <section class="about-banner">

        <div class="about-banner-content">

            <p class="about-label">ABOUT US</p>

            <h1>
                About <span>Jeje Junctions</span>
            </h1>

            <p class="about-tagline">
                Great food. Good people. A place for everyone.
            </p>

            <p class="about-description">
                Get to know more about our story, our passion
                for great food and what makes Jeje Junctions special.
            </p>

        </div>

    </section>

    <!-- ==========================================
         OUR STORY SECTION
    ========================================== -->

    <section class="about-story">

        <div class="about-story-content">

            <p class="about-label">OUR STORY</p>

            <h2>A Taste of Home in Soweto</h2>

            <!-- Display the story retrieved from the database -->
            <?php if (!empty($about["content"])): ?>

                <p class="story-text">
                    <?php
                        echo nl2br(
                            htmlspecialchars($about["content"])
                        );
                    ?>
                </p>

            <?php else: ?>

                <!-- Message displayed when no story is available -->
                <p class="story-text">
                    Our story will be available soon.
                </p>

            <?php endif; ?>

        </div>

    </section>



    <!-- ==========================================
         WHAT WE OFFER SECTION
    ========================================== -->

    <section class="about-offers">

        <div class="about-offers-heading">

            <p class="about-label">WHAT WE OFFER</p>

            <h2>More Than <span>Just a Meal</span></h2>

            <p>
                From freshly grilled meals to convenient online ordering,
                Jeje Junctions offers something for everyone.
            </p>

        </div>


        <!-- Cards describing the main services -->
        <div class="about-offers-grid">


            <!-- Grilled Meals -->
            <div class="about-offer-card">

                <span class="offer-icon" aria-hidden="true">♨</span>

                <h3>Grilled Meals</h3>

                <p>
                    Enjoy freshly grilled meats prepared
                    with delicious flavours.
                </p>

            </div>


            <!-- Combos -->
            <div class="about-offer-card">

                <span class="offer-icon" aria-hidden="true">★</span>

                <h3>Tasty Combos</h3>

                <p>
                    Discover meal combinations perfect
                    for sharing or enjoying on your own.
                </p>

            </div>


            <!-- Online Ordering -->
            <div class="about-offer-card">

                <span class="offer-icon" aria-hidden="true">🛒</span>

                <h3>Online Ordering</h3>

                <p>
                    Browse the menu and place your order
                    online quickly and conveniently.
                </p>

            </div>


            <!-- Eat-In and Takeaway -->
            <div class="about-offer-card">

                <span class="offer-icon" aria-hidden="true">♜</span>

                <h3>Eat-In or Takeaway</h3>

                <p>
                    Enjoy your meal at Jeje Junctions
                    or order ahead for collection.
                </p>

            </div>

        </div>


        <!-- Button directing customers to the menu -->
        <div class="about-menu-action">

            <a href="menu.php?category=grilled" class="about-menu-btn">
                Explore Our Menu →
            </a>

        </div>

    </section>


</main>

<!-- ==========================================
     WEBSITE FOOTER
========================================== -->

<footer>

    <h3>JEJE JUNCTIONS</h3>
    <p>Butchery & Grill</p>
    <p>Good Meat. Great Taste. Real Experience.</p>
    <p>&copy; 2026 Jeje Junctions. All rights reserved.</p>

</footer>

<script src="js/script.js?v=5"></script>
</body>
</html>
