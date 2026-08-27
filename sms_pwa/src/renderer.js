// renderer.js
document.addEventListener("DOMContentLoaded", () => {
  const setupForm = document.getElementById("setupForm");
  const setupMessage = document.getElementById("setupMessage");

  setupForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const websiteUrl = document.getElementById("websiteUrl").value.trim();
    const authCode = document.getElementById("authCode").value.trim();

    try {
      const result = await window.api.validateToken(authCode, websiteUrl);

      if (result && result.valid) {
        setupMessage.textContent = "Validation successful!";
        setupMessage.className = "text-green-600";
      } else {
        setupMessage.textContent = "Invalid code or URL.";
        setupMessage.className = "text-red-600";
      }
    } catch (err) {
      setupMessage.textContent = "Error validating: " + err.message;
      setupMessage.className = "text-red-600";
    }
  });
});