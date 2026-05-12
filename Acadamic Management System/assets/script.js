document.addEventListener("DOMContentLoaded", () => {

    const registrationForm = document.getElementById("registrationForm");
    const resetPasswordForm = document.getElementById("resetPasswordForm"); // Reset Request (Email)
    const updatePasswordForm = document.getElementById("updatePasswordForm"); // Update Password (New)

    // Signup Form Validation
    if (registrationForm) {
        registrationForm.addEventListener("submit", (event) => {
            event.preventDefault();

            const firstName = document.getElementById("firstName") ? document.getElementById("firstName").value.trim() : "";
            const lastName = document.getElementById("lastName") ? document.getElementById("lastName").value.trim() : "";
            const dobDay = document.getElementById("dobDay") ? document.getElementById("dobDay").value : "";
            const dobMonth = document.getElementById("dobMonth") ? document.getElementById("dobMonth").value : "";
            const dobYear = document.getElementById("dobYear") ? document.getElementById("dobYear").value : "";
            const address = document.getElementById("address") ? document.getElementById("address").value.trim() : "";
            const emailField = document.getElementById("email");
            const emailValue = emailField ? emailField.value.trim() : "";
            const contact = document.getElementById("contact") ? document.getElementById("contact").value.trim() : "";

            const motherName = document.getElementById("motherName") ? document.getElementById("motherName").value.trim() : "";
            const fatherName = document.getElementById("fatherName") ? document.getElementById("fatherName").value.trim() : "";
            const guardianContact = document.getElementById("guardianContact") ? document.getElementById("guardianContact").value.trim() : "";

            const passwordField = document.getElementById("password");
            const confirmPasswordField = document.getElementById("confirmPassword");
            const passwordValue = passwordField ? passwordField.value : "";
            const confirmPasswordValue = confirmPasswordField ? confirmPasswordField.value : "";

            const submitButton = document.getElementById("submitBtn");

            if (firstName === "") {
                alert("Validation Error: First Name is required.");
                return;
            }

            if (lastName === "") {
                alert("Validation Error: Last Name is required.");
                return;
            }

            if (dobDay === "" || dobMonth === "" || dobYear === "") {
                alert("Validation Error: Please select a complete Date of Birth (Day, Month, Year).");
                return;
            }

            if (address === "") {
                alert("Validation Error: Address is required.");
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

            if (!/^\+?\d{10,15}$/.test(contact)) {
                alert("Validation Error: Please enter a valid contact number (10 to 15 digits).");
                return;
            }

            if (motherName === "") {
                alert("Validation Error: Mother's Name is required.");
                return;
            }

            if (fatherName === "") {
                alert("Validation Error: Father's Name is required.");
                return;
            }

            if (guardianContact === "") {
                alert("Validation Error: Guardian Contact Number is required.");
                return;
            }

            if (!/^\+?\d{10,15}$/.test(guardianContact)) {
                alert("Validation Error: Please enter a valid guardian contact number (10 to 15 digits,).");
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

            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&]).{6,}$/;
            if (!passwordRegex.test(passwordValue)) {
                alert("Security Error: Password must be at least 6 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.");
                return;
            }

            submitButton.innerText = "Submitting to PHP server...";
            submitButton.style.opacity = "0.7";
            registrationForm.submit();
        });
    }

    // Reset Password Form Validation (Email Request)
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener("submit", (event) => {
            const emailField = document.getElementById("email");
            const emailValue = emailField ? emailField.value.trim() : "";

            if (emailValue === "") {
                event.preventDefault();
                alert("Validation Error: Email Address is required.");
                return;
            }
            
            const submitButton = document.getElementById("submitBtn");
            if (submitButton) {
                submitButton.innerText = "Sending Link...";
                submitButton.style.opacity = "0.7";
            }
        });
    }

    // Update Password Form Validation (New Password)
    if (updatePasswordForm) {
        updatePasswordForm.addEventListener("submit", (event) => {
            const passwordField = document.getElementById("password");
            const confirmPasswordField = document.getElementById("confirmPassword");
            const passwordValue = passwordField ? passwordField.value : "";
            const confirmPasswordValue = confirmPasswordField ? confirmPasswordField.value : "";

            if (passwordValue === "") {
                event.preventDefault();
                alert("Validation Error: New Password is required.");
                return;
            }

            if (passwordValue !== confirmPasswordValue) {
                event.preventDefault();
                alert("Security Error: Passwords do not match!");
                return;
            }

            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&]).{6,}$/;
            if (!passwordRegex.test(passwordValue)) {
                event.preventDefault();
                alert("Security Error: Password must be at least 6 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.");
                return;
            }

            const submitButton = document.getElementById("submitBtn");
            if (submitButton) {
                submitButton.innerText = "Updating...";
                submitButton.style.opacity = "0.7";
            }
        });
    }

    // OTP Digit Input Handlers
    const otpDigits = document.querySelectorAll('.otp-digit');
    const fullOtpInput = document.getElementById('full_otp');
    const verifyBtn = document.getElementById('verifyBtn');

    if (otpDigits.length > 0) {
        otpDigits.forEach((digit, index) => {
            // Move to next input on type
            digit.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < otpDigits.length - 1) {
                    otpDigits[index + 1].focus();
                }
                updateFullOtp();
            });

            // Handle Backspace
            digit.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    otpDigits[index - 1].focus();
                }
            });

            // Handle Paste
            digit.addEventListener('paste', (e) => {
                const data = e.clipboardData.getData('text').trim();
                if (data.length === otpDigits.length && /^\d+$/.test(data)) {
                    data.split('').forEach((char, i) => {
                        otpDigits[i].value = char;
                    });
                    otpDigits[otpDigits.length - 1].focus();
                    updateFullOtp();
                }
                e.preventDefault();
            });
        });

        function updateFullOtp() {
            let combined = "";
            otpDigits.forEach(d => combined += d.value);
            fullOtpInput.value = combined;
        }

        const verifyForm = verifyBtn.closest('form');
        if (verifyForm) {
            verifyForm.addEventListener('submit', (e) => {
                updateFullOtp();
                if (fullOtpInput.value.length !== 6) {
                    alert("Please enter a complete 6-digit OTP.");
                    e.preventDefault();
                } else {
                    verifyBtn.innerText = "Verifying...";
                    verifyBtn.style.opacity = "0.7";
                }
            });
        }
    }

    // Student Dashboard: Real-time Search Filtering
    const dashboardSearch = document.getElementById('dashboardSearch');
    const tasksTableBody = document.getElementById('studentTasksBody');
    const noResultsRow = document.getElementById('noResults');

    if (dashboardSearch && tasksTableBody) {
        dashboardSearch.addEventListener('input', (e) => {
            const keyword = e.target.value.toLowerCase().trim();
            const items = tasksTableBody.querySelectorAll('.searchable-item');
            let hasMatches = false;

            items.forEach(item => {
                const text = item.innerText.toLowerCase();
                if (text.includes(keyword)) {
                    item.style.display = '';
                    hasMatches = true;
                } else {
                    item.style.display = 'none';
                }
            });

            // Show "No Results" row if nothing matches
            if (noResultsRow) {
                noResultsRow.style.display = hasMatches ? 'none' : 'table-row';
            }
        });
    }
});
