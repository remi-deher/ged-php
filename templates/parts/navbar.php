<?php $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>

<nav class="navbar">
    <div class="nav-links">
        <a href="/" class="<?= $currentUri === '/' ? 'active' : '' ?>">📄 Documents</a>
        <a href="/trash" class="<?= $currentUri === '/trash' ? 'active' : '' ?>">🗑️ Corbeille</a>
        <a href="/settings" class="<?= $currentUri === '/settings' ? 'active' : '' ?>">⚙️ Réglages</a>
    </div>
    <div class="nav-actions">
        <div class="search-container">
            <form action="/" method="GET">
                <input type="search" id="search-input" name="q" placeholder="Rechercher des documents..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
        </div>
        <div class="view-switcher">
            <button id="list-view-btn" class="button-icon active" title="Vue liste">☰</button>
            <button id="grid-view-btn" class="button-icon" title="Vue grille">⊞</button>
        </div>
    </div>
</nav>
