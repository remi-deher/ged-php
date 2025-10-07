<div class="modal-overlay" id="document-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-document-title">Prévisualisation</h2>
            <button class="modal-close" id="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body email-layout" id="modal-body">
            <div class="attachments-panel-top" id="modal-attachments-panel">
                <div class="attachments-header">
                     <h3><i class="fas fa-paperclip"></i> Pièces jointes (<span id="modal-attachment-count">0</span>)</h3>
                     <button id="modal-attachments-toggle-btn"><i class="fas fa-chevron-down"></i></button>
                </div>
                <ul id="modal-attachments-list">
                    </ul>
            </div>
            <div class="preview-container">
                 <iframe id="modal-preview-iframe" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</div>
