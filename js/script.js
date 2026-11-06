const quantityInput = document.getElementById("quantity");
const cartQuantity = document.getElementById("cart-quantity");

if (quantityInput && cartQuantity) {

    quantityInput.addEventListener("input", function () {

        cartQuantity.value = quantityInput.value;

    });

}


/* FAQ interaction */
document.addEventListener("DOMContentLoaded", function () {
    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(function (item) {
        item.addEventListener("toggle", function () {
            if (item.open) {
                faqItems.forEach(function (otherItem) {
                    if (otherItem !== item) { 
                        otherItem.open = false;
                    }
                });
            }
        });
    });
});


/* Live product search on the menu page */
document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("productSearch");
    const searchMessage = document.getElementById("searchMessage");
    const productCards = document.querySelectorAll(".product-card");

    if (!searchInput || !searchMessage) {
        return;
    }

    searchInput.addEventListener("input", function () {

        const searchText = searchInput.value.toLowerCase().trim();
        let visibleProducts = 0;

        productCards.forEach(function (card) {

            // Get the product details safely
            const productName =
                card.querySelector("h2")?.textContent.toLowerCase() || "";

            const productInfo = card.querySelector(".product-info");
            const productCategory =
                card.querySelector(".product-category")?.textContent.toLowerCase() || "";

            const descriptionElement = productInfo
                ? Array.from(productInfo.querySelectorAll("p"))
                    .find(function (paragraph) {
                        return !paragraph.classList.contains("product-category");
                    })
                : null;

            const productDescription =
                descriptionElement?.textContent.toLowerCase() || "";

            // Check whether the product matches the search
            const matches =
                productName.includes(searchText) ||
                productDescription.includes(searchText) ||
                productCategory.includes(searchText);

            card.style.display = matches ? "" : "none";

            if (matches) {
                visibleProducts++;
            }

        });

        // Display feedback below the search field
        searchMessage.textContent = visibleProducts === 0
            ? "No matching products found."
            : "";

    });

});


/* Product quantity feedback */
document.addEventListener("DOMContentLoaded", function () {

    const quantityInput = document.getElementById("quantity");
    const quantityFeedback = document.getElementById("quantity-feedback");
    const quantityWarning = document.getElementById("quantity-warning");

    // Run only on pages that contain the product quantity input
    if (!quantityInput || !quantityFeedback || !quantityWarning) {
        return;
    }

    function updateQuantityFeedback() {

        const quantity = Number(quantityInput.value);
        const maxQuantity = Number(quantityInput.max);

        // Check that the quantity is valid
        if (
            quantityInput.value === "" ||
            !Number.isInteger(quantity) ||
            quantity < 1
        ) {
            quantityFeedback.textContent = "";
            quantityWarning.textContent = "Please select at least 1 item.";
            return;
        }

        // Warn customers if they select more than the available stock
        if (quantity > maxQuantity) {
            quantityFeedback.textContent = "";
            quantityWarning.textContent =
                "Only " + maxQuantity + " portions are available.";
            return;
        }

        // Display the selected quantity
        quantityWarning.textContent = "";
        quantityFeedback.textContent =
            "Selected: " + quantity + (quantity === 1 ? " item" : " items");
    }

    // Update feedback whenever the customer changes the quantity
    quantityInput.addEventListener("input", updateQuantityFeedback);
    quantityInput.addEventListener("change", updateQuantityFeedback);

    // Display the initial quantity
    updateQuantityFeedback();

});


/* Plus and minus quantity controls */
document.addEventListener("DOMContentLoaded", function () {

    const quantityInput = document.getElementById("quantity");
    const decreaseButton = document.getElementById("decrease-quantity");
    const increaseButton = document.getElementById("increase-quantity");

    /* Only run on pages with quantity controls */
    if (!quantityInput || !decreaseButton || !increaseButton) {
        return;
    }

    const minimum = Number(quantityInput.min) || 1;
    const maximum = Number(quantityInput.max);

    /* Update quantity while respecting stock limits */
    function changeQuantity(amount) {

        let current = Number(quantityInput.value) || minimum;

        let newQuantity = current + amount;

        /* Prevent quantity from going below 1 */
        newQuantity = Math.max(minimum, newQuantity);

        /* Prevent quantity from exceeding stock */
        if (Number.isFinite(maximum) && maximum >= minimum) {
            newQuantity = Math.min(maximum, newQuantity);
        }

        quantityInput.value = newQuantity;

        /* Notify existing quantity feedback code */
        quantityInput.dispatchEvent(
            new Event("input", { bubbles: true })
        );

        quantityInput.dispatchEvent(
            new Event("change", { bubbles: true })
        );
    }

    /* Decrease quantity */
    decreaseButton.addEventListener("click", function () {
        changeQuantity(-1);
    });

    /* Increase quantity */
    increaseButton.addEventListener("click", function () {
        changeQuantity(1);
    });

});

/* Update Add to Cart price when quantity changes */
document.addEventListener("DOMContentLoaded", function () {

    const quantityInput = document.getElementById("quantity");
    const addCartButton = document.getElementById("add-cart-button");
    const totalPrice = document.getElementById("cart-total-price");

    if (!quantityInput || !addCartButton || !totalPrice) {
        return;
    }

    const productPrice = Number(addCartButton.dataset.price);

    function updateCartTotal() {

        const quantity = Number(quantityInput.value);

        if (!Number.isFinite(quantity) || quantity < 1) {
            return;
        }

        const total = productPrice * quantity;

        totalPrice.textContent = total.toFixed(2);
    }

    quantityInput.addEventListener("input", updateCartTotal);
    quantityInput.addEventListener("change", updateCartTotal);

    updateCartTotal();

});

/* =========================================
   MOBILE NAVIGATION
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const menuButton =
        document.getElementById("mobile-menu-toggle");

    const mobileNavigation =
        document.getElementById("mobile-navigation");

    if (!menuButton || !mobileNavigation) {
        return;
    }

    menuButton.addEventListener("click", function () {

        const isOpen =
            mobileNavigation.classList.toggle("active");

        menuButton.setAttribute(
            "aria-expanded",
            String(isOpen)
        );

        menuButton.setAttribute(
            "aria-label",
            isOpen ? "Close navigation menu" : "Open navigation menu"
        );
    });

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            mobileNavigation.classList.remove("active");

            menuButton.setAttribute("aria-expanded", "false");
            menuButton.setAttribute(
                "aria-label",
                "Open navigation menu"
            );
        }
    });

});


/* Show and hide registration passwords */
document.addEventListener("DOMContentLoaded", function () {

    const toggleButtons =
        document.querySelectorAll(".password-toggle");

    toggleButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const passwordInput =
                document.getElementById(button.dataset.target);

            if (!passwordInput) {
                return;
            }

            const isHidden = passwordInput.type === "password";

            passwordInput.type = isHidden ? "text" : "password";

            button.textContent = isHidden ? "Hide" : "Show";

            button.setAttribute(
                "aria-pressed",
                String(isHidden)
            );

            button.setAttribute(
                "aria-label",
                (isHidden ? "Hide " : "Show ") +
                (button.dataset.target === "password"
                    ? "password"
                    : "confirm password")
            );

        });

    });

});

