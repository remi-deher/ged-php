<?php $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>

<header class="app-header">
    <div class="header-logo">
        <a href="/">
            <h3>GED-PHP</h3>
        </a>
    </div>
    <div class="header-actions">
        <div class="search-container">
            <form action="/" method="GET">
                <i class="fas fa-search"></i>
                <input type="search" id="search-input" name="q" placeholder="Rechercher des documents..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
        </div>
        <div class="view-switcher">
            <button id="list-view-btn" class="button-icon active" title="Vue liste"><i class="fas fa-bars"></i></button>
            <button id="grid-view-btn" class="button-icon" title="Vue grille"><i class="fas fa-th-large"></i></button>
        </div>
        </div>
</header>
