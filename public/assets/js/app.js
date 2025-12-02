/**
 * SIMRS - Main Application JavaScript
 * Common functions and utilities
 */

(function () {
  "use strict";

  /**
   * CSRF Token Handler
   */
  const CSRF = {
    token: document.querySelector('meta[name="csrf-token"]')?.content || "",

    getToken() {
      return this.token;
    },

    setHeader(xhr) {
      xhr.setRequestHeader("X-CSRF-Token", this.token);
    },
  };

  /**
   * Alert Handler
   */
  const Alert = {
    show(message, type = "info", duration = 5000) {
      const alertDiv = document.createElement("div");
      alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
      alertDiv.setAttribute("role", "alert");
      alertDiv.innerHTML = `
                ${message}
                <button type="button" class="close" onclick="this.parentElement.remove()">
                    <span>&times;</span>
                </button>
            `;

      // Insert at top of main content
      const mainContent = document.querySelector(".main-content");
      if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
      } else {
        document.body.insertBefore(alertDiv, document.body.firstChild);
      }

      // Auto dismiss
      if (duration > 0) {
        setTimeout(() => {
          alertDiv.remove();
        }, duration);
      }
    },

    success(message, duration) {
      this.show(message, "success", duration);
    },

    error(message, duration) {
      this.show(message, "danger", duration);
    },

    warning(message, duration) {
      this.show(message, "warning", duration);
    },

    info(message, duration) {
      this.show(message, "info", duration);
    },
  };

  /**
   * AJAX Helper
   */
  const Ajax = {
    request(url, options = {}) {
      const defaults = {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
      };

      const config = { ...defaults, ...options };

      // Add CSRF token for non-GET requests
      if (config.method !== "GET") {
        config.headers["X-CSRF-Token"] = CSRF.getToken();
      }

      return fetch(url, config).then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      });
    },

    get(url) {
      return this.request(url);
    },

    post(url, data) {
      return this.request(url, {
        method: "POST",
        body: JSON.stringify(data),
      });
    },

    put(url, data) {
      return this.request(url, {
        method: "PUT",
        body: JSON.stringify(data),
      });
    },

    delete(url) {
      return this.request(url, {
        method: "DELETE",
      });
    },
  };

  /**
   * Form Validation Helper
   */
  const FormValidator = {
    validate(form) {
      const inputs = form.querySelectorAll(
        "input[required], textarea[required], select[required]"
      );
      let isValid = true;

      inputs.forEach((input) => {
        if (!input.value.trim()) {
          this.showError(input, "Field ini wajib diisi");
          isValid = false;
        } else {
          this.clearError(input);
        }
      });

      return isValid;
    },

    showError(input, message) {
      input.classList.add("is-invalid");

      let errorDiv = input.nextElementSibling;
      if (!errorDiv || !errorDiv.classList.contains("invalid-feedback")) {
        errorDiv = document.createElement("div");
        errorDiv.className = "invalid-feedback";
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
      }
      errorDiv.textContent = message;
      errorDiv.style.display = "block";
    },

    clearError(input) {
      input.classList.remove("is-invalid");
      const errorDiv = input.nextElementSibling;
      if (errorDiv && errorDiv.classList.contains("invalid-feedback")) {
        errorDiv.style.display = "none";
      }
    },
  };

  /**
   * Confirmation Dialog
   */
  function confirm(message, callback) {
    if (window.confirm(message)) {
      if (typeof callback === "function") {
        callback();
      }
      return true;
    }
    return false;
  }

  /**
   * Loading Overlay
   */
  const Loading = {
    show() {
      let overlay = document.getElementById("loadingOverlay");
      if (!overlay) {
        overlay = document.createElement("div");
        overlay.id = "loadingOverlay";
        overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                `;
        overlay.innerHTML =
          '<div style="color: white; font-size: 20px;">Loading...</div>';
        document.body.appendChild(overlay);
      }
      overlay.style.display = "flex";
    },

    hide() {
      const overlay = document.getElementById("loadingOverlay");
      if (overlay) {
        overlay.style.display = "none";
      }
    },
  };

  /**
   * Auto-dismiss alerts
   */
  document.addEventListener("DOMContentLoaded", function () {
    const alerts = document.querySelectorAll(".alert:not(.alert-permanent)");
    alerts.forEach((alert) => {
      setTimeout(() => {
        alert.style.opacity = "0";
        alert.style.transition = "opacity 0.5s";
        setTimeout(() => alert.remove(), 500);
      }, 5000);
    });
  });

  /**
   * Confirm delete actions
   */
  document.addEventListener("click", function (e) {
    if (
      e.target.classList.contains("btn-delete") ||
      e.target.closest(".btn-delete")
    ) {
      const btn = e.target.classList.contains("btn-delete")
        ? e.target
        : e.target.closest(".btn-delete");

      const confirmMsg =
        btn.dataset.confirm || "Apakah Anda yakin ingin menghapus data ini?";

      if (!window.confirm(confirmMsg)) {
        e.preventDefault();
        return false;
      }
    }
  });

  /**
   * Format currency input
   */
  document.querySelectorAll("input[data-currency]").forEach((input) => {
    input.addEventListener("input", function (e) {
      let value = e.target.value.replace(/[^\d]/g, "");
      e.target.value = formatRupiah(value);
    });
  });

  /**
   * Format rupiah
   */
  function formatRupiah(number) {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }).format(number);
  }

  /**
   * Date picker initialization (if library exists)
   */
  if (typeof flatpickr !== "undefined") {
    flatpickr('input[type="date"]', {
      dateFormat: "d-m-Y",
      locale: "id",
    });
  }

  /**
   * Character counter for textarea
   */
  document.querySelectorAll("textarea[maxlength]").forEach((textarea) => {
    const maxLength = textarea.getAttribute("maxlength");
    const counter = document.createElement("small");
    counter.className = "text-muted";
    counter.textContent = `0 / ${maxLength}`;
    textarea.parentNode.appendChild(counter);

    textarea.addEventListener("input", function () {
      counter.textContent = `${this.value.length} / ${maxLength}`;
    });
  });

  /**
   * Back button handler
   */
  const backButtons = document.querySelectorAll(".btn-back");
  backButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      window.history.back();
    });
  });

  /**
   * Print handler
   */
  const printButtons = document.querySelectorAll(".btn-print");
  printButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      window.print();
    });
  });

  // Export to global scope
  window.SIMRS = {
    CSRF,
    Alert,
    Ajax,
    FormValidator,
    Loading,
    confirm,
    formatRupiah,
  };
})();
