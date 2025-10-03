// public/js/home.js

document.addEventListener('DOMContentLoaded', () => {
    
    // Initialisation de tous les modules
    initClickAndDoubleClick();
    initDetailsSidebar();
    initDocumentModal();
    initBulkSelection();
    initViewSwitcher();
    initContextMenu();
    initDragAndDrop();
    initClientSideSearch();
    initPrintQueueDashboard();

    // --- Gestionnaire central pour clic et double-clic ---
    function initClickAndDoubleClick() {
        const rows = document.querySelectorAll('.document-row');
        rows.forEach(row => {
            let clickTimer = null;

            row.addEventListener('click', (e) => {
                // Ignorer si on clique sur un élément interactif
                if (e.target.closest('button, a, input[type="checkbox"], form')) return;

                if (clickTimer === null) {
                    // Démarrer un timer. Si rien ne se passe, c'est un simple clic.
                    clickTimer = setTimeout(() => {
                        clickTimer = null; // Réinitialiser le timer
                        const docId = row.dataset.docId;
                        window.openSidebarForDocument(docId);
                    }, 250); // Délai de 250ms pour détecter un double-clic
                } else {
                    // Si un timer existe déjà, c'est un double-clic
                    clearTimeout(clickTimer);
                    clickTimer = null; // Réinitialiser le timer
                    const docId = row.dataset.docId;
                    window.openModalForDocument(docId);
                }
            });
        });
    }

    // --- Gestion de la BARRE LATERALE ---
    function initDetailsSidebar() {
        const sidebar = document.getElementById('details-sidebar');
        const mainContent = document.getElementById('main-content');
        if (!sidebar) return;

        const title = document.getElementById('sidebar-title');
        const infoList = document.getElementById('sidebar-info-list');
        const attachmentsList = document.getElementById('sidebar-attachments-list');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const attachmentsToggleBtn = document.getElementById('sidebar-attachments-toggle-btn');
        
        window.openSidebarForDocument = async (docId) => {
            if (!docId) return;
            sidebar.classList.add('open');
            mainContent.classList.add('sidebar-open');
            
            title.textContent = 'Chargement...';
            infoList.innerHTML = '<li>Chargement...</li>';
            attachmentsList.innerHTML = '';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé.');
                const data = await response.json();
                
                title.textContent = data.main_document.original_filename;
                 infoList.innerHTML = `
                    <li><strong>Taille:</strong> <span>${data.main_document.size_formatted}</span></li>
                    <li><strong>Type:</strong> <span>${data.main_document.mime_type}</span></li>
                    <li><strong>Ajouté le:</strong> <span>${new Date(data.main_document.created_at).toLocaleString('fr-FR')}</span></li>
                    <li><strong>Source:</strong> <span>${data.main_document.source_account_id ? 'E-mail' : 'Manuel'}</span></li>
                 `;

                const attachmentsContainer = document.getElementById('sidebar-attachments');
                if (data.attachments && data.attachments.length > 0) {
                    attachmentsContainer.style.display = 'block';
                    data.attachments.forEach(att => {
                        attachmentsList.innerHTML += `<li><a href="/document/download?id=${att.id}" target="_blank">${att.original_filename}</a></li>`;
                    });
                } else {
                    attachmentsContainer.style.display = 'none';
                }

            } catch (error) {
                title.textContent = 'Erreur';
                infoList.innerHTML = `<li>${error.message}</li>`;
            }
        };
        
        const closeSidebar = () => {
             sidebar.classList.remove('open');
             mainContent.classList.remove('sidebar-open');
        };

        closeBtn.addEventListener('click', closeSidebar);
        attachmentsToggleBtn.addEventListener('click', () => {
            attachmentsToggleBtn.classList.toggle('collapsed');
            attachmentsList.classList.toggle('collapsed');
        });
    }

    // --- Gestion de la MODALE (CORRIGÉ) ---
    function initDocumentModal() {
        const modal = document.getElementById('document-modal');
        if (!modal) return;

        const modalTitle = document.getElementById('modal-title');
        const attachmentsList = document.getElementById('modal-attachments-list');
        const previewIframe = document.getElementById('modal-preview-iframe');
        const closeBtn = modal.querySelector('.modal-close');
        const attachmentsToggleBtn = document.getElementById('modal-attachments-toggle-btn');
        const attachmentsPanel = document.getElementById('modal-attachments');

        window.openModalForDocument = async (docId) => {
            if (!docId) return;
            
            // Réinitialiser l'état du panneau à l'ouverture
            attachmentsPanel.classList.remove('collapsed');
            attachmentsToggleBtn.innerHTML = '‹';
            attachmentsToggleBtn.title = 'Masquer les pièces jointes';
            
            modal.style.display = 'flex';
            modalTitle.textContent = 'Chargement...';
            attachmentsList.innerHTML = '<li>Chargement...</li>';
            previewIframe.src = 'about:blank';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé.');
                const data = await response.json();
                
                modalTitle.textContent = data.main_document.original_filename;
                previewIframe.src = `/document/download?id=${data.main_document.id}`;

                if (data.attachments && data.attachments.length > 0) {
                    attachmentsPanel.style.display = 'block';
                    attachmentsToggleBtn.style.display = 'block'; // Assurez-vous que le bouton est visible
                    attachmentsList.innerHTML = '';
                    data.attachments.forEach(attachment => {
                        const li = document.createElement('li');
                        li.innerHTML = `📄 <a href="/document/download?id=${attachment.id}" target="_blank" title="${attachment.original_filename}">${attachment.original_filename}</a>`;
                        attachmentsList.appendChild(li);
                    });
                } else {
                     attachmentsPanel.style.display = 'none';
                     attachmentsToggleBtn.style.display = 'none'; // Cachez le bouton s'il n'y a rien à cacher
                }
            } catch (error) {
                console.error('Erreur:', error);
                modalTitle.textContent = 'Erreur';
                attachmentsList.innerHTML = `<li>Impossible de charger les informations.</li>`;
            }
        };
        
        const closeModal = () => {
            modal.style.display = 'none';
            previewIframe.src = 'about:blank';
        };

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        attachmentsToggleBtn.addEventListener('click', () => {
            attachmentsPanel.classList.toggle('collapsed');
            const isCollapsed = attachmentsPanel.classList.contains('collapsed');
            // Mettre à jour le bouton
            attachmentsToggleBtn.innerHTML = isCollapsed ? '›' : '‹';
            attachmentsToggleBtn.title = isCollapsed ? 'Afficher les pièces jointes' : 'Masquer les pièces jointes';
        });
    }

    // --- MENU CONTEXTUEL ---
    function initContextMenu() {
        const contextMenu = document.getElementById('context-menu');
        let currentDocId = null;

        document.addEventListener('contextmenu', e => {
            const targetRow = e.target.closest('.document-row');
            if (targetRow) {
                e.preventDefault();
                currentDocId = targetRow.dataset.docId;
                contextMenu.style.top = `${e.clientY}px`;
                contextMenu.style.left = `${e.clientX}px`;
                contextMenu.style.display = 'block';
            } else {
                contextMenu.style.display = 'none';
            }
        });
        document.addEventListener('click', () => contextMenu.style.display = 'none');

        contextMenu.addEventListener('click', e => {
            const action = e.target.dataset.action;
            if (!action || !currentDocId) return;
            switch (action) {
                case 'preview_sidebar': window.openSidebarForDocument(currentDocId); break;
                case 'preview_modal': window.openModalForDocument(currentDocId); break;
                case 'print':
                    const printForm = document.createElement('form');
                    printForm.method = 'POST'; printForm.action = '/document/print';
                    printForm.innerHTML = `<input type="hidden" name="doc_id" value="${currentDocId}">`;
                    document.body.appendChild(printForm); printForm.submit();
                    break;
                case 'download': window.open(`/document/download?id=${currentDocId}`, '_blank'); break;
                case 'delete':
                     if (confirm('Confirmer la mise à la corbeille ?')) {
                        const deleteForm = document.createElement('form');
                        deleteForm.method = 'POST'; deleteForm.action = '/document/delete';
                        deleteForm.innerHTML = `<input type="hidden" name="doc_ids[]" value="${currentDocId}">`;
                        document.body.appendChild(deleteForm); deleteForm.submit();
                    }
                    break;
            }
        });
    }

    // --- AUTRES FONCTIONS (INCHANGEES) ---
    function initBulkSelection(){const mainCheckbox=document.getElementById("select-all-checkbox"),docCheckboxes=document.querySelectorAll(".doc-checkbox"),bulkDeleteButton=document.getElementById("bulk-delete-button"),bulkPrintButton=document.getElementById("bulk-print-button");function e(){const e=Array.from(docCheckboxes).some(e=>e.checked);bulkDeleteButton&&(bulkDeleteButton.style.display=e?"inline-flex":"none"),bulkPrintButton&&(bulkPrintButton.style.display=e?"inline-flex":"none")}mainCheckbox&&mainCheckbox.addEventListener("change",t=>{docCheckboxes.forEach(o=>{o.closest(".document-row").style.display!=="none"&&(o.checked=t.target.checked)}),e()}),docCheckboxes.forEach(t=>t.addEventListener("change",e)),e()}
    function initViewSwitcher(){const e=document.getElementById("list-view-btn"),t=document.getElementById("grid-view-btn"),o=document.getElementById("document-list-view"),n=document.getElementById("document-grid-view");if(!e||!t||!o||!n)return;const d=localStorage.getItem("ged_view_mode")||"list";function c(c){"grid"===c?(o.style.display="none",n.style.display="grid",e.classList.remove("active"),t.classList.add("active"),localStorage.setItem("ged_view_mode","grid")):(n.style.display="none",o.style.display="block",t.classList.remove("active"),e.classList.add("active"),localStorage.setItem("ged_view_mode","list"))}e.addEventListener("click",()=>c("list")),t.addEventListener("click",()=>c("grid")),c(d)}
    function initClientSideSearch(){const e=document.getElementById("search-input");e&&(e.closest("form").addEventListener("submit",e=>e.preventDefault()),e.addEventListener("input",()=>{const t=e.value.toLowerCase().trim();document.querySelectorAll(".document-row").forEach(e=>{const o=e.querySelector(".col-name strong, .grid-item-name"),n=o?o.textContent.toLowerCase():"",d="TR"===e.tagName?"table-row":"flex";e.style.display=n.includes(t)?d:"none"})}))}
    function initDragAndDrop(){const e=document.querySelectorAll('.document-row[draggable="true"]'),t=document.querySelectorAll(".dropzone, #root-dropzone");e.forEach(e=>{e.addEventListener("dragstart",t=>{t.dataTransfer.setData("text/plain",e.dataset.docId),setTimeout(()=>e.classList.add("dragging"),0)}),e.addEventListener("dragend",()=>e.classList.remove("dragging"))}),t.forEach(e=>{e.addEventListener("dragover",e=>{e.preventDefault(),e.currentTarget.classList.add("drag-over")}),e.addEventListener("dragleave",t=>t.currentTarget.classList.remove("drag-over")),e.addEventListener("drop",async t=>{t.preventDefault(),t.currentTarget.classList.remove("drag-over");const o=t.dataTransfer.getData("text/plain"),n=t.currentTarget.dataset.folderId;if(!o||!n)return;const d=new FormData;d.append("doc_id",o),d.append("folder_id",n);try{const t=await fetch("/document/move",{method:"POST",headers:{"X-Requested-With":"XMLHttpRequest"},body:d}),o=await t.json();if(!t.ok||!o.success)throw new Error(o.message||"Erreur.");showToast(o.message,"📁");const c=document.querySelector(`[data-doc-id="${n}"]`);c&&(c.style.transition="opacity 0.5s ease",c.style.opacity="0",setTimeout(()=>c.remove(),500))}catch(e){showToast(`Erreur : ${e.message}`,"⚠️")}})})}
    function showToast(e,t="ℹ️"){const o=document.getElementById("toast-container");if(!o)return;const n=document.createElement("div");n.className="toast",n.innerHTML=`<span style="margin-right: 8px;">${t}</span> ${e}`,o.appendChild(n),setTimeout(()=>n.classList.add("show"),100),setTimeout(()=>{n.classList.remove("show"),n.addEventListener("transitionend",()=>n.remove())},5e3)}
    function initPrintQueueDashboard(){const e=document.getElementById("print-queue-body"),t=document.getElementById("print-queue-dashboard");if(!e||!t)return;async function o(t,o,n,d,c){n.disabled=!0,n.textContent="...";const a=new FormData;a.append("doc_id",o);try{const o=await fetch(t,{method:"POST",body:a}),i=await o.json();if(!o.ok)throw new Error(i.error||"Erreur inconnue");showToast(i.message,d),l()}catch(e){showToast(`Erreur : ${e.message}`,"⚠️"),n.disabled=!1,n.textContent=c}}async function l(){try{const o=await fetch("/print-queue/status");if(!o.ok)throw new Error("Impossible de récupérer le statut.");const n=await o.json(),d=n.filter(e=>"Terminé"!==e.status);d.length>0?(t.style.display="block",e.innerHTML="",d.forEach(t=>{const o=document.createElement("tr");o.dataset.docId=t.id;let n='<span>⏳ En attente</span>',d='<button class="button-icon button-delete btn-cancel-print" title="Annuler">❌</button>';"En cours d'impression"===t.status?n=`<span>🖨️ ${t.status}</span>`:"Erreur"===t.status&&(n=`<span style="color: var(--danger-color); font-weight: bold;">⚠️ ${t.status}</span>`,d='<button class="button-icon btn-clear-error" title="Effacer">🗑️</button>'),o.innerHTML=`<td>${t.filename||"N/A"}</td><td>${t.job_id||"N/A"}</td><td>${statusHtml}</td><td>${t.error||"Aucun détail"}</td><td>${d}</td>`,e.appendChild(o)}),e.querySelectorAll(".btn-cancel-print").forEach(e=>e.addEventListener("click",t=>o("/document/cancel-print",t.currentTarget.closest("tr").dataset.docId,t.currentTarget,"🗑️","❌"))),e.querySelectorAll(".btn-clear-error").forEach(e=>e.addEventListener("click",t=>o("/document/clear-print-error",t.currentTarget.closest("tr").dataset.docId,t.currentTarget,"✨","🗑️")))):t.style.display="none"}catch(o){t.style.display="block",e.innerHTML=`<tr><td colspan="5" class="empty-state" style="color: var(--danger-color);">${o.message}</td></tr>`}}function n(){const e="https:"===window.location.protocol?"wss":"ws",t=`${e}://${window.location.host}/ws`;try{const e=new WebSocket(t);e.onopen=()=>console.log("WebSocket connecté."),e.onmessage=e=>{try{const t=JSON.parse(e.data);["new_document","document_deleted","print_cancelled","print_error_cleared"].includes(t.action)?(showToast("Mise à jour du serveur reçue, actualisation...","🔄"),setTimeout(()=>window.location.reload(),1500)):"print_sent"===t.action&&(showToast(t.data.message,"🖨️"),l(),(e=>{const o=document.querySelector(`[data-doc-id="${t.data.doc_id}"] .status-dot`);o&&(o.style.backgroundColor="#ffc107",o.title="À imprimer")})())}catch(e){console.error("Erreur parsing WebSocket",e)}},e.onclose=()=>{console.log("WebSocket déconnecté, reconnexion..."),setTimeout(n,5e3)},e.onerror=t=>{console.error("Erreur WebSocket:",t),e.close()}}catch(e){console.error("Impossible de créer la connexion WebSocket.",e)}}l(),setInterval(l,7e3),n()}
});
