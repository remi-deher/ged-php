<?php
// templates/parts/sidebar-left.php
?>
<nav class="sidebar-nav">
    <ul>
        <li class="active">
            <a href="/">
                <i class="fas fa-folder"></i>
                <span>Mes documents</span>
            </a>
        </li>
        <li>
            <a href="/trash">
                <i class="fas fa-trash"></i>
                <span>Corbeille</span>
            </a>
        </li>
    </ul>
</nav>

<hr class="sidebar-separator">

<div class="folder-tree" id="folder-tree">
    <p style="padding: 10px; color: #6c757d;">Chargement des dossiers...</p>
</div>

<hr class="sidebar-separator">

<nav class="sidebar-nav">
    <ul>
        <li>
            <a href="/settings">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
        </li>
    </ul>
</nav>
