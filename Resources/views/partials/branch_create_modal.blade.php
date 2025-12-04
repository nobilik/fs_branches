<div class="branch-modal-overlay" id="branch-create-modal-overlay">
    <div class="branch-modal">
        <div class="branch-modal__content">
            <div class="branch-modal__header">
                <h5 class="branch-modal__title">Создать филиал</h5>
                <button type="button" class="branch-modal__close-btn js-close-modal">&times;</button>
            </div>
            <div class="branch-modal__body">
                <form id="branch-create-form">
                    @include('nobilikbranches::partials.form')
                    <input type="hidden" name="conversation_id" id="branch-conversation-id">
                    <button type="submit" class="branch-modal__submit-btn branch-btn btn-success w-100 mt-3" id="branch-create-submit">Создать и привязать</button>
                </form>
            </div>
        </div>
    </div>
</div>
