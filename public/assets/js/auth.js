document.addEventListener("DOMContentLoaded", function () {
    // 1. Password Toggle Implementation
    const togglePassword = document.querySelector("#togglePassword");
    const passwordInput = document.querySelector("#password");

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener("click", function () {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            this.querySelector("i").classList.toggle("fa-eye");
            this.querySelector("i").classList.toggle("fa-eye-slash");
        });
    }

    // 2. Realtime Connectivity Monitoring Engine
    const badge = document.getElementById("connectivity-badge");
    const badgeText = document.getElementById("connectivity-text");

    function updateNetworkStatus() {
        if (navigator.onLine) {
            badge.className = "online";
            badgeText.textContent = "Online";
        } else {
            badge.className = "offline";
            badgeText.textContent = "Offline Connection Interrupted";
        }
    }
    window.addEventListener("online", updateNetworkStatus);
    window.addEventListener("offline", updateNetworkStatus);

    // 3. Login Submit UX Handler (no AJAX to avoid plugin/network interference)
    const loginForm = document.getElementById("hrgoto-login-form") || document.getElementById("btu-login-form");
    const feedback = document.getElementById("toast-wrapper-feedback");
    const submitBtn = document.getElementById("submit-btn-element");
    const btnText = document.getElementById("btn-text-default");
    const btnSpinner = document.getElementById("btn-spinner-element");
    const btnVerifying = document.getElementById("btn-text-verifying");

    if (loginForm) {
        loginForm.addEventListener("submit", function (event) {
            const identifierInput = document.getElementById("email");
            const identifier = identifierInput ? identifierInput.value.trim() : "";
            const password = passwordInput ? passwordInput.value : "";

            if (!identifier || !password) {
                if (feedback) {
                    feedback.classList.remove("d-none", "alert-success");
                    feedback.classList.add("alert-danger");
                    feedback.textContent = "Username/email and password are required.";
                }
                if (event) {
                    event.preventDefault();
                }
                return;
            }

            if (feedback) {
                feedback.className = "alert d-none";
            }

            submitBtn.disabled = true;
            btnText.classList.add("d-none");
            btnSpinner.classList.remove("d-none");
            btnVerifying.classList.remove("d-none");
        });
    }
});