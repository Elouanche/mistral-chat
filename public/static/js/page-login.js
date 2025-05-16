const formConfigurations = {
    "loginForm": {
        service: "Auth",
        action: "Login",
        fields: [
            { id: "loginEmail", key: "email" },
            { id: "loginPassword", key: "password" }
        ]
    },
    "registerForm": {
        service: "Auth",
        action: "Register",
        fields: [
            { id: "username", key: "username" },
            { id: "email", key: "email" },
            { id: "phone", key: "phone" },
            { id: "password", key: "password" }
        ]
    }
};

document.getElementById('switch_forms').addEventListener("click", function () {
    const inscription = document.getElementById('inscription');
    const connexion = document.getElementById('connexion');
    const paragraph = document.getElementById('switch_forms');

    if (inscription.style.display === "none") {
        inscription.style.display = "flex";
        connexion.style.display = "none";
        paragraph.innerHTML = 'Connexion';
    } else {
        inscription.style.display = "none";
        connexion.style.display = "flex";
        paragraph.innerHTML = 'S\'inscrire';
    }
});