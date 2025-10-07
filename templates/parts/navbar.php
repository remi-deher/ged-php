<?php $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>

<header class="app-header">
    <div class="header-left">
        <a href="/" class="header-logo">
            <img src="/img/logo.svg" alt="Logo GED-PHP" class="logo-icon"> <span class="logo-text">GED-PHP</span>
        </a>
    </div>

    <div class="header-center">
        <div class="search-container">
            <form action="/" method="GET">
                <button type="submit" class="search-button" aria-label="Rechercher">
                    <i class="fas fa-search"></i>
                </button>
                <input type="search" id="search-input" name="q" placeholder="Rechercher des fichiers, des dossiers..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
        </div>
    </div>

    <div class="header-right">
        <div class="header-actions">
            <div class="view-switcher">
                <button id="list-view-btn" class="button-icon active" title="Vue liste"><i class="fas fa-bars"></i></button>
                <button id="grid-view-btn" class="button-icon" title="Vue grille"><i class="fas fa-th-large"></i></button>
            </div>
             <button class="button-icon" title="Ajouter un document">
                <i class="fas fa-plus"></i>
            </button>
            <button class="button-icon" title="Notifications">
                <i class="fas fa-bell"></i>
            </button>
             <a href="/settings" class="button-icon" title="Paramètres">
                <i class="fas fa-cog"></i>
            </a>
        </div>
        
        <div class="user-profile">
             <button class="button-icon user-avatar-button" title="Profil">
                <span>U</span> </button>
        </div>
    </div>
</header>
