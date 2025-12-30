<div class="branch-modal-overlay" id="branch-edit-modal-overlay">
    <div class="branch-modal">
        <div class="branch-modal__content">
            <div class="branch-modal__header">
                <h5 class="branch-modal__title">Редактировать объект</h5>
                <button class="branch-modal__close-btn js-close-modal">&times;</button>
            </div>
            <div class="branch-modal__body">
                <form id="branch-edit-form">
                    @csrf
                    @method('PUT')
                    @include('nobilikbranches::partials.form', ['branch' => $branch])
                    <input type="hidden" name="conversation_id" id="branch-conversation-id">
                    <button type="submit" class="branch-modal__submit-btn mt-3">Сохранить и привязать</button>
                </form>
            </div>
        </div>
    </div>
</div>
