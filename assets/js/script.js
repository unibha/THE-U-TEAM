document.addEventListener("DOMContentLoaded", () => {
    
    const registrationForm = document.getElementById("registrationForm");
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirmPassword");
    const emailField = document.getElementById("email");
    const submitButton = document.getElementById("submitBtn");

    registrationForm.addEventListener("submit", (event) => {
        event.preventDefault();

        const firstName = document.getElementById("firstName").value.trim();
        const lastName = document.getElementById("lastName").value.trim();
        const contact = document.getElementById("contact").value.trim();
        const emailValue = emailField.value.trim();
        const passwordValue = passwordField.value;
        const confirmPasswordValue = confirmPasswordField.value;

        if (firstName === "") {
            alert("Validation Error: First Name is required.");
            return;
        }

        if (lastName === "") {
            alert("Validation Error: Last Name is required.");
            return;
        }

        if (emailValue === "") {
            alert("Validation Error: Email Address is required.");
            return;
        }

        if (!emailValue.includes('@') || !emailValue.includes('.')) {
            alert("Validation Error: Please enter a correctly formatted email address.");
            return;
        }

        if (contact === "") {
            alert("Validation Error: Contact Number is required.");
            return;
        }

        if (!/^\d{7,15}$/.test(contact)) {
            alert("Validation Error: Please enter a valid contact number (7 to 15 digits).");
            return;
        }

        if (passwordValue === "") {
            alert("Validation Error: Password is required.");
            return;
        }

        if (passwordValue !== confirmPasswordValue) {
            alert("Security Error: Passwords do not match!");
            return; 
        }

        if (passwordValue.length < 6) {
            alert("Security Error: Password must be at least 6 characters long.");
            return; 
        }

        const selectedRoleElement = document.querySelector('input[name="role"]:checked');
        const selectedRole = selectedRoleElement ? selectedRoleElement.value : 'Student';
        const originalBtnText = submitButton.innerText;
        submitButton.innerText = "Submitting to PHP server...";
        submitButton.style.opacity = "0.7";
        registrationForm.submit();
    });
});
