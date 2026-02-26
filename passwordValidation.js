/**
 * @file passwordValidation.js
 * @brief Client-side validation for password confirmation.
 *
 * Prevents form submission if the new password
 * and its confirmation do not match.
*/
document.addEventListener('DOMContentLoaded', function () {
    var newPasswordInput = document.getElementById('new_password');
    var confirmPasswordInput = document.getElementById('confirm_password');
    var form = document.querySelector('form');
    /**
     * Password input fields.
     *
     * @type {HTMLInputElement}
     */
    form.addEventListener('submit', function(event) {
        if (newPasswordInput.value !== confirmPasswordInput.value) {
            event.preventDefault();
            alert('New password and its confirmation are not identical.');
        }
    });
});