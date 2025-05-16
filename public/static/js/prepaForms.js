/**
 * Generic form submission handler
 * @param {string} formId - ID of the form to handle
 * @param {Object} actionMap - Mapping of form fields to API action and parameters
 * @param {function} [preprocessData] - Optional function to preprocess form data
 */
function setupFormSubmission(formId, actionMap, preprocessData) {
    const form = document.getElementById(formId);
    const errorElement = document.getElementById(`${formId}Error`);

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        // Collecting form data
        const data = {};
        actionMap.fields.forEach(field => {
            const input = document.getElementById(field.id);
            data[field.key] = input.value.trim();
        });

        // Optional preprocessing of form data
        if (preprocessData) {
            preprocessData(data);
        }

        // Construct the request payload
        const context = {
            service: actionMap.service,
            action: actionMap.action,
            data: data
        };

        if (errorElement) {
            errorElement.textContent = "";
        }

        try {
            const result = await postData(context);
            
            if (result.status === 'error') {
                if (errorElement) {
                    errorElement.textContent = result.message;
                }
                return;
            }

            if (result.status === 'success' || result.status === 'pending') {
                form.reset();
                // La redirection sera gérée par postData
                return;
            }
                
        } catch (error) {
            console.error("Submission error:", error);
            if (errorElement) {
                errorElement.textContent = `Error: ${error.message}`;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof formConfigurations === 'object' && formConfigurations !== null) {
        Object.keys(formConfigurations).forEach(formId => {
            const config = formConfigurations[formId];
            const form = document.getElementById(formId);
            if (form) {
                setupFormSubmission(formId, config, config.preprocessData);
            } else {
                console.warn(`Formulaire avec ID "${formId}" non trouvé dans le DOM.`);
            }
        });
    }
});

