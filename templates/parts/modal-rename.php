<div class="modal-overlay" id="rename-modal" style="display: none;">
    <div class="modal-content" style="width: 400px; height: auto;">
        <div class="modal-header">
            <h2>Renommer</h2>
            <button class="modal-close" data-dismiss="rename-modal">&times;</button>
        </div>
        <form id="rename-form">
            <div class="modal-body">
                <input type="hidden" id="rename-id" name="id">
                <input type="hidden" id="rename-type" name="type">
                <div class="form-group">
                    <label for="new-name">Nouveau nom</label>
                    <input type="text" id="new-name" name="newName" required>
                </div>
            </div>
            <div class="modal-footer" style="text-align: right; padding: 1rem;">
                <button type="submit" class="button">Renommer</button>
            </div>
        </form>
    </div>
</div>
