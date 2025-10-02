<style>
    .navbar {
        background-color: #fff;
        border-radius: 8px;
        margin-bottom: 2rem;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }
    .navbar a {
        float: left;
        color: #333;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
        font-size: 16px;
        transition: background-color 0.3s, color 0.3s;
    }
    .navbar a:hover {
        background-color: #f8f9fa;
    }
    .navbar a.active {
        background-color: #007bff;
        color: white;
        font-weight: bold;
    }
</style>

<?php $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>

<nav class="navbar">
    <a href="/" class="<?= $currentUri === '/' ? 'active' : '' ?>">📄 Documents</a>
    <a href="/trash" class="<?= $currentUri === '/trash' ? 'active' : '' ?>">🗑️ Corbeille</a>
    <a href="/settings" class="<?= $currentUri === '/settings' ? 'active' : '' ?>">⚙️ Réglages</a>
</nav>
