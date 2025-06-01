document.addEventListener('DOMContentLoaded', function() {
    const formConfigurations = {
        "adminVerifyForm": {
            service: "Auth",
            action: "verifyAdminCode",
            fields: [
                { id: "verification_code", key: "verification_code" },
                { id: "loginEmail", key: "email" },
                { id: "loginPassword", key: "password" }
            ]
        }
    };

    // Initialiser les formulaires s'ils existent
    if (typeof setupFormSubmission === 'function' && formConfigurations) {
        Object.keys(formConfigurations).forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                setupFormSubmission(formId, formConfigurations[formId]);
            }
        });
    }
});