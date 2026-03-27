document.addEventListener("DOMContentLoaded", () => {
    
    const registrationForm = document.getElementById("registrationForm");
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirmPassword");
    const emailField = document.getElementById("email");
    const submitButton = document.getElementById("submitBtn");

    registrationForm.addEventListener("submit", (event) => {
        event.preventDefault();

        const passwordValue = passwordField.value;
        const confirmPasswordValue = confirmPasswordField.value;
        const emailValue = emailField.value;

        if (!emailValue.includes('@') || !emailValue.includes('.')) {
            alert("Validation Error: Please enter a correctly formatted email address.");
            return;
        }

        if (passwordValue !== confirmPasswordValue) {
            alert("Security Error: Passwords do not match!");
            return; 
        }

        if (passwordValue.length < 8) {
            alert("Security Error: Password must be at least 8 characters long.");
            return; 
        }
        

        const selectedRoleElement = document.querySelector('input[name="role"]:checked');
        const selectedRole = selectedRoleElement ? selectedRoleElement.value : 'Student';
        const firstName = document.getElementById("firstName").value;
        const lastName = document.getElementById("lastName").value;
        const contact = document.getElementById("contact").value;

        const backendPayload = {
            firstName: firstName,
            lastName: lastName,
            email: emailValue,
            contactNumber: contact,
            role: selectedRole,
            password: passwordValue 
        };

        const originalBtnText = submitButton.innerText;
        submitButton.innerText = "Creating Account...";
        submitButton.style.opacity = "0.7";

        setTimeout(() => {
            console.log("Payload securely submitted to backend:", backendPayload);
            alert(`Registration Complete!\nWelcome ${firstName} ${lastName}! Your ${selectedRole} account is active.`);
            
            registrationForm.reset();
            submitButton.innerText = originalBtnText;
            submitButton.style.opacity = "1";
        }, 1200);

    });
});
