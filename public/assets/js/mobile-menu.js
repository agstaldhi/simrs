/**
 * SIMRS - Mobile Menu Handler
 * Handles hamburger menu for mobile/tablet devices
 */

(function () {
  "use strict";

  // DOM Elements
  const mobileMenuToggle = document.getElementById("mobileMenuToggle");
  const mobileMenuClose = document.getElementById("mobileMenuClose");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("mobileMenuOverlay");

  // Check if elements exist
  if (!mobileMenuToggle || !sidebar || !overlay) {
    console.warn("Mobile menu elements not found");
    return;
  }

  /**
   * Open mobile menu
   */
  function openMenu() {
    sidebar.classList.add("active");
    overlay.classList.add("active");
    document.body.style.overflow = "hidden"; // Prevent scroll
  }

  /**
   * Close mobile menu
   */
  function closeMenu() {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    document.body.style.overflow = ""; // Restore scroll
  }

  /**
   * Toggle mobile menu
   */
  function toggleMenu() {
    if (sidebar.classList.contains("active")) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  // Event Listeners
  mobileMenuToggle.addEventListener("click", toggleMenu);

  if (mobileMenuClose) {
    mobileMenuClose.addEventListener("click", closeMenu);
  }

  overlay.addEventListener("click", closeMenu);

  // Handle submenu toggle
  const menuItems = document.querySelectorAll(".menu-item.has-submenu");

  menuItems.forEach((item) => {
    const menuLink = item.querySelector(".menu-link");

    if (menuLink) {
      menuLink.addEventListener("click", function (e) {
        e.preventDefault();
        item.classList.toggle("open");
      });
    }
  });

  // Close menu on window resize (desktop)
  let resizeTimer;
  window.addEventListener("resize", function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      if (window.innerWidth >= 1024) {
        closeMenu();
      }
    }, 250);
  });

  // Close menu when clicking on menu links (mobile only)
  const menuLinks = sidebar.querySelectorAll(".submenu a");
  menuLinks.forEach((link) => {
    link.addEventListener("click", function () {
      if (window.innerWidth < 1024) {
        closeMenu();
      }
    });
  });

  // Handle escape key to close menu
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && sidebar.classList.contains("active")) {
      closeMenu();
    }
  });

  // Trap focus inside menu when open (accessibility)
  sidebar.addEventListener("keydown", function (e) {
    if (e.key === "Tab") {
      const focusableElements = sidebar.querySelectorAll(
        'button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );

      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];

      if (e.shiftKey && document.activeElement === firstElement) {
        lastElement.focus();
        e.preventDefault();
      } else if (!e.shiftKey && document.activeElement === lastElement) {
        firstElement.focus();
        e.preventDefault();
      }
    }
  });
})();
