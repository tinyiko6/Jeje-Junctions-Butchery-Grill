
<!-- Shared Customer Navigation -->

<header class="navbar">

    <!-- Website logo -->
    <div class="logo">
        <a href="index.php">
            <img
                src="images/jeje-logo.jpeg"
                alt="Jeje Junctions">
        </a>
    </div>

    <!-- Mobile hamburger button -->
    <button
        type="button"
        class="mobile-menu-toggle"
        id="mobile-menu-toggle"
        aria-label="Open navigation menu"
        aria-controls="mobile-navigation"
        aria-expanded="false">

        <span></span>
        <span></span>
        <span></span>

    </button>

    <!-- Navigation links -->
    <div class="navigation-wrapper"
         id="mobile-navigation">

        <nav class="main-navigation"
             aria-label="Main navigation">

            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="menu.php?category=grilled"> Grilled Meals </a>
            <a href="menu.php?category=combos">Combos </a>
            <a href="menu.php?category=sides"> Sides &amp; Salads</a>
            <a href="menu.php?category=drinks"> Drinks </a>
            <a href="faq.php">FAQs</a>

        </nav>

        <!-- Customer account links -->
        <div class="nav-actions">

            <a href="cart.php">Cart</a>

            <?php if (isset($_SESSION["customer_id"])): ?>

                <a href="my_orders.php">
                    My Orders
                </a>

                <a href="logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">
                    Login
                </a>

            <?php endif; ?>

        </div>

    </div>

</header>
