document.addEventListener("DOMContentLoaded", () => {
    const forms = document.querySelectorAll("form[data-validate]");
    forms.forEach((form) => {
        form.addEventListener("submit", (event) => {
            const invalid = [...form.querySelectorAll("[required]")].find((field) => !field.value.trim());
            if (invalid) {
                event.preventDefault();
                invalid.focus();
                alert("Please complete all required fields before submitting.");
            }
        });
    });

    const password = document.querySelector("#password");
    const confirm = document.querySelector("#password_confirmation");
    if (password && confirm) {
        confirm.addEventListener("input", () => {
            confirm.setCustomValidity(confirm.value === password.value ? "" : "Passwords must match.");
        });
    }

    const email = document.querySelector("#email[data-check-url]");
    const feedback = document.querySelector("#email-feedback");
    if (email && feedback) {
        let timer;
        email.addEventListener("input", () => {
            clearTimeout(timer);
            if (!email.validity.valid) {
                feedback.textContent = "";
                return;
            }
            timer = setTimeout(async () => {
                const url = `${email.dataset.checkUrl}?email=${encodeURIComponent(email.value)}`;
                const response = await fetch(url);
                const data = await response.json();
                feedback.textContent = data.available ? "Email is available." : "Email is already registered.";
                feedback.className = data.available ? "small success-text" : "small error-text";
            }, 350);
        });
    }
});
