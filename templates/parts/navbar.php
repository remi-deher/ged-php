<?php $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>

<nav class="navbar">
    <a href="/" class="<?= $currentUri === '/' ? 'active' : '' ?>">📄 Documents</a>
    <a href="/trash" class="<?= $currentUri === '/trash' ? 'active' : '' ?>">🗑️ Corbeille</a>
    <a href="/settings" class="<?= $currentUri === '/settings' ? 'active' : '' ?>">⚙️ Réglages</a>
</nav>
