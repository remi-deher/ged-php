// public/js/home/sidebar.js
import { showToast } from '../utils.js';

let sidebar;

export function init() {
    sidebar = document.getElementById('details-sidebar');
    if (!sidebar) return;

    // Fermeture de la sidebar
    sidebar.querySelector('#close-sidebar-btn')?.addEventListener('click', closeDetails);
    
    // Ajout dynamique du bouton de fermeture s'il n'existe pas
    if (!sidebar.querySelector('#close-sidebar-btn')) {
        const header = sidebar.querySelector('.sidebar-header');
        if (header) {
            const closeBtn = document.createElement('button');
            closeBtn.id = 'close-sidebar-btn';
            closeBtn.className = 'button-icon';
            closeBtn.innerHTML = '<i class="fas fa-times"></i>';
            closeBtn.addEventListener('click', closeDetails);
            header.appendChild(closeBtn);
        }
    }
}

export async function openDetails(docId) {
    if (!sidebar) return;
    
    // Ajoute un état de chargement
    sidebar.innerHTML = '<div class="sidebar-loading" style="padding: 2rem; text-align: center;">Chargement...</div>';
    sidebar.classList.add('open');

    try {
        const response = await fetch(`/api/document/details?id=${docId}`);
        if (!response.ok) throw new Error('Document non trouvé.');
        
        const data = await response.json();
        renderSidebarContent(data);

    } catch (error) {
        console.error(error);
        showToast(error.message, 'error');
        closeDetails();
    }
}

export function closeDetails() {
    if (sidebar) {
        sidebar.classList.remove('open');
    }
}

function renderSidebarContent(data) {
    const doc = data.document;
    const attachments = data.attachments;

    sidebar.innerHTML = `
        <div class="sidebar-header">
            <h2 title="${doc.filename}">${doc.filename}</h2>
            <button id="close-sidebar-btn" class="button-icon"><i class="fas fa-times"></i></button>
        </div>
        <div class="sidebar-body">
            <div class="sidebar-preview">
                <i class="fas fa-file-alt" style="font-size: 4rem; color: #757575;"></i>
            </div>
            <div class="sidebar-actions">
                <button class="button" onclick="window.open('/document/download?id=${doc.id}')"><i class="fas fa-download"></i> Télécharger</button>
                <button class="button button-secondary" onclick="document.dispatchEvent(new CustomEvent('openDocumentModal', { detail: { docId: ${doc.id} } }))"><i class="fas fa-eye"></i> Prévisualiser</button>
            </div>
            <div class="sidebar-section">
                <h3>Détails</h3>
                <ul class="sidebar-info-list">
                    <li>Type <span>${doc.mime_type || 'N/A'}</span></li>
                    <li>Taille <span>${doc.size ? GED.App.formatBytes(doc.size) : 'N/A'}</span></li>
                    <li>Créé le <span>${new Date(doc.created_at).toLocaleDateString('fr-FR')}</span></li>
                    <li>Modifié le <span>${new Date(doc.updated_at).toLocaleDateString('fr-FR')}</span></li>
                </ul>
            </div>
            ${attachments && attachments.length > 0 ? `
            <div class="sidebar-section">
                <h3>Pièces Jointes (${attachments.length})</h3>
                <ul class="attachments-list">
                    ${attachments.map(att => `
                        <li class="attachment-item">
                            <div class="attachment-item-icon"><i class="fas fa-paperclip"></i></div>
                            <div class="attachment-item-info">
                                <strong>${att.filename}</strong>
                                <span>${GED.App.formatBytes(att.size)}</span>
                            </div>
                            <a href="/document/download?id=${att.id}" class="button-icon" title="Télécharger"><i class="fas fa-download"></i></a>
                        </li>
                    `).join('')}
                </ul>
            </div>
            ` : ''}
        </div>
    `;
    
    // Rattache l'événement de fermeture car on a remplacé le contenu
    sidebar.querySelector('#close-sidebar-btn').addEventListener('click', closeDetails);
}
