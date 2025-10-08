// public/js/home/sidebar.js

export function init() {
    const sidebar = document.getElementById('details-sidebar');
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.className = 'close-sidebar-btn';
    closeBtn.onclick = closeDetails;
    sidebar.appendChild(closeBtn);
}

export async function openDetails(docId) {
    const sidebar = document.getElementById('details-sidebar');
    try {
        const response = await fetch(`/api/document/details/${docId}`);
        if (!response.ok) {
            throw new Error('Failed to fetch document details');
        }
        const data = await response.json();

        let content = `<h2>${data.filename}</h2>`;
        content += `<p><strong>Taille:</strong> ${data.size} bytes</p>`;
        content += `<p><strong>Créé le:</strong> ${new Date(data.created_at).toLocaleString()}</p>`;
        content += `<p><strong>Modifié le:</strong> ${new Date(data.updated_at).toLocaleString()}</p>`;

        sidebar.innerHTML = content;
        sidebar.classList.add('open');

    } catch (error) {
        sidebar.innerHTML = `<p class="error">${error.message}</p>`;
        sidebar.classList.add('open');
    }
}

export function closeDetails() {
    const sidebar = document.getElementById('details-sidebar');
    sidebar.classList.remove('open');
}
