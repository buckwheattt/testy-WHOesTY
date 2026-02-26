/**
 * @file ajax-script.js
 * @brief AJAX username availability check.
 *
 * Sends the entered username to the server on input blur
 * and displays availability status.
*/
document.addEventListener("DOMContentLoaded", () => {

    /** @type {HTMLInputElement|null} Username input field */
    const usernameInput = document.getElementById("username");

    /** @type {HTMLElement|null} Container for result message */
    const resultBox = document.getElementById("usernameResult");

    /**
    * Username input field.
    * @type {HTMLInputElement|null}
    */
    if (!usernameInput || !resultBox) return;
    usernameInput.addEventListener("blur", async () => {
        const username = usernameInput.value.trim();
        /**
        * Result output container.
        * @type {HTMLElement|null}
        */
        if (username.length < 3) {
            resultBox.textContent = "";
            return;
        }
        const formData = new FormData();
        formData.append("user_name", username);

        try {
            // Send AJAX request to backend
            const res = await fetch("check_username.php", {
                method: "POST",
                body: formData
            });
            // Display server response
            const text = await res.text();
            resultBox.textContent = text;
        } catch (e) {
            // Network or server error
            resultBox.textContent = "Server error";
        }
    });
});
