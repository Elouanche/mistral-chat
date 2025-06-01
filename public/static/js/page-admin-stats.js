document.addEventListener('DOMContentLoaded', function() {
    getAdminStats();
});

async function getAdminStats() {
    const context = {
        service: 'Analytics',
        action: 'getStatistics'
    };

    try {
        const stats = await postData(context);

        if (!stats) throw new Error("Réponse vide");

        renderStatistics(stats);
    } catch (error) {
        console.error("Erreur lors du chargement des stats :", error);
        document.getElementById('stats-root').innerHTML = '<p>Erreur lors du chargement des statistiques.</p>';
    }
}

function renderStatistics(stats) {
    const {
        total_users = 0,
        total_products = 0,
        total_orders = 0,
        total_revenue = 0,
        orders_by_status = {},
        top_products = [],
        top_users = [],
        sales_by_month = {}
    } = stats;

    const html = `
        <div class="stats-summary">
            ${statCard("Utilisateurs", total_users)}
            ${statCard("Produits", total_products)}
            ${statCard("Commandes", total_orders)}
            ${statCard("Chiffre d'affaires", total_revenue.toFixed(2) + " €")}
        </div>

        <div class="stats-details">
            ${renderTableSection("Commandes par statut", orders_by_status, "Statut", "Nombre")}
            ${renderProductTable("Produits les plus vendus", top_products)}
            ${renderUserTable("Utilisateurs les plus actifs", top_users)}
            ${renderRevenueByMonth("Ventes des 6 derniers mois", sales_by_month)}
        </div>
    `;

    document.getElementById('stats-root').innerHTML = html;
}

function statCard(title, value) {
    return `
        <div class="stat-card">
            <h3>${title}</h3>
            <p class="stat-number">${Intl.NumberFormat().format(value)}</p>
        </div>
    `;
}

function renderTableSection(title, data, col1, col2) {
    const rows = Object.entries(data).map(([key, val]) =>
        `<tr><td>${key}</td><td>${val}</td></tr>`
    ).join('');
    
    return `
        <div class="stat-section">
            <h2>${title}</h2>
            <table class="stats-table">
                <thead><tr><th>${col1}</th><th>${col2}</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function renderProductTable(title, products) {
    const rows = products.map(p =>
        `<tr><td>${p.name}</td><td>${p.sales_count}</td></tr>`
    ).join('');

    return `
        <div class="stat-section">
            <h2>${title}</h2>
            <table class="stats-table">
                <thead><tr><th>Produit</th><th>Ventes</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function renderUserTable(title, users) {
    const rows = users.map(u =>
        `<tr><td>${u.username}</td><td>${u.order_count}</td></tr>`
    ).join('');

    return `
        <div class="stat-section">
            <h2>${title}</h2>
            <table class="stats-table">
                <thead><tr><th>Utilisateur</th><th>Commandes</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function renderRevenueByMonth(title, data) {
    const rows = Object.entries(data).map(([month, revenue]) =>
        `<tr><td>${month}</td><td>${revenue.toFixed(2)} €</td></tr>`
    ).join('');

    return `
        <div class="stat-section">
            <h2>${title}</h2>
            <table class="stats-table">
                <thead><tr><th>Mois</th><th>Chiffre d'affaires</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}