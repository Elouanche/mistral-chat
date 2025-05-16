document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById("header");
    const urlParams = new URLSearchParams(window.location.search);
    const isHomePage = window.location.pathname === '/' || window.location.pathname === '/index.php';
    const shouldApplyClasses = urlParams.has('Projet_Pro') || urlParams.has('projet_id');

    let lastScrollY = 0;
    const shrinkThreshold = 30;  // Seuil pour réduire le header
    const expandThreshold = 1;  // Seuil plus bas pour éviter un retour trop rapide

    const updateHeaderClasses = () => {
        const currentScrollY = window.scrollY;

        if (!isHomePage || currentScrollY > shrinkThreshold) {
            header.classList.add("small", "small-image");
        } else if (currentScrollY < expandThreshold && !shouldApplyClasses && isHomePage) {
            header.classList.remove("small", "small-image");
        }

        lastScrollY = currentScrollY;
    };

    updateHeaderClasses();
    window.addEventListener('scroll', updateHeaderClasses);
});
