 <?php
session_start();

require_once "db_connect.php";

/* Retrieve operating hours maintained in the admin portal. */
$hours_sql = "SELECT title, content
              FROM site_content
              WHERE section = 'Operating Hours'
              ORDER BY content_id ASC";

/* Run the query once and use its results in the Operating Hours section. */
$hours_result = $conn->query($hours_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeje Junctions Butchery &amp; Grill</title>

<link rel="stylesheet" href="css/shared.css?v=7">
<link rel="stylesheet" href="css/home.css">
</head>
<body>

   
<!-- Main navigation -->
<?php include "navbar.php"; ?>

    <main>
        <!-- Hero Section -->
        <!-- The photo is loaded from CSS, with a dark gradient for readable text. -->
        <section class="hero">
            <div class="hero-content">
                <p class="hero-label">BUTCHERY &amp; GRILL</p>
                <h1>Good Meat.<br>Great Taste.<br>Real Experience.</h1><p class="hero-description">
                    Freshly grilled favourites, delicious combos and
                    sides made for every occasion.
                </p>

                <!-- Buttons lead customers to the menu and ordering pages. -->
                <div class="hero-buttons">
                    <a href="menu.php" class="btn btn-primary">ORDER NOW</a>
                    <a href="menu.php" class="btn btn-secondary">VIEW MENU</a>
                </div>
            </div>
        </section>

        <!-- Welcome Section -->
        <!-- Introduce the restaurant and its main food offerings. -->
        <section class="intro">
            <div class="section-container">
                <p class="section-label">WELCOME TO JEJE JUNCTIONS</p>
                <h2>From Our Grill To You</h2>
                <p>
                    Jeje Junctions Butchery &amp; Grill brings together freshly
                    grilled meals, signature combos, tasty sides and refreshing
                    drinks. Whether you're grabbing a quick meal or ordering
                    for family and friends, we've got something for you.
                </p>
            </div>
        </section>

        <!-- Why Choose Jeje Junctions Section -->
        <!-- Four cards explain the main benefits without repeating Eat-In and Takeaway elsewhere. -->
        <section class="home-benefits">
            <div class="section-container">
                <h2>Why Choose Jeje Junctions?</h2>

        <!-- Responsive card grid: four across on large screens. -->
                <div class="home-benefits-grid">


        <!-- Individual benefit card -->
                    <article class="home-benefit-card">
                        <i data-lucide="flame" class="benefit-icon" aria-hidden="true"></i>
                        <h3>Freshly Grilled</h3>
                        <p>Enjoy delicious grilled meals prepared with great flavour.</p>
                    </article>

        <!-- Individual benefit card -->
                    <article class="home-benefit-card">
                       <i data-lucide="shopping-cart" class="benefit-icon" aria-hidden="true"></i>
                        <h3>Easy Ordering</h3>
                        <p>Browse our menu and place your order online with ease.</p>
                    </article>

        <!-- Individual benefit card -->
                    <article class="home-benefit-card">
                       <i data-lucide="utensils" class="benefit-icon" aria-hidden="true"></i>
                        <h3>Eat-In</h3>
                        <p>Visit Jeje Junctions and enjoy your freshly prepared meal.</p>
                    </article>

        <!-- Individual benefit card -->
                    <article class="home-benefit-card">
                        <i data-lucide="shopping-bag" class="benefit-icon" aria-hidden="true"></i>
                        <h3>Takeaway</h3>
                        <p>Order ahead and collect your meal without unnecessary waiting.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Business Information Section -->
        <!-- Operating Hours and Contact Us sit side by side on large screens. -->
        <div class="home-information">


      <!-- Operating Hours: the admin portal controls the displayed values. -->
            <section class="home-hours">
                <div class="home-hours-content">
                    <h2>Our Operating Hours</h2>
                    <p class="hours-intro">Plan your next visit to Jeje Junctions.</p>
                    <div class="home-hours-list">

      <!-- Show the saved hours, or a fallback message if none exist. -->
                        <?php if ($hours_result && $hours_result->num_rows > 0): ?>
                            <?php while ($hour = $hours_result->fetch_assoc()): ?>
                                <div class="home-hours-row">

     <!-- Escape database text before displaying it in HTML. -->
                                    <span><?php echo htmlspecialchars($hour["title"]); ?></span>
                                    <span><?php echo htmlspecialchars($hour["content"]); ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Our operating hours will be available soon.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Contact Us Section -->
            <!-- Replace the phone and email placeholders with confirmed business details. -->
            <section class="home-contact">
                <div class="home-contact-content">
                    <h2>Contact Us</h2>
                    <p class="contact-intro">
                        Have a question about our menu or your order?
                        Get in touch with Jeje Junctions Butchery &amp; Grill.
                    </p>


            <!-- Three cards: location, telephone and email. -->
                    <div class="contact-details">
                        <div class="contact-item">
                            <h3>Visit Us</h3>
                            <p>Soweto, Gauteng, South Africa</p>
                        </div>
                        <div class="contact-item">
                            <h3>Call Us</h3>
                            <p>011s5637896</p>
                        </div>
                        <div class="contact-item">
                            <h3>Email Us</h3>
                            <p>jejejunctions@gmail.com</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <h3>JEJE JUNCTIONS</h3>
        <p>Butchery &amp; Grill</p>
        <p>Good Meat. Great Taste. Real Experience.</p>
        <p>&copy; 2026 Jeje Junctions. All rights reserved.</p>
    </footer>

    
<script src="js/script.js?v=7"></script>

<!-- Lucide icons -->
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>

</body>
</html>
