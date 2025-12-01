<div id="branchSelectModalOverlay" class="branch-modal-overlay">
    <div class="branch-modal">
        <div class="branch-modal__content">

            <!-- Заголовок -->
            <div class="branch-modal__header">
                <h3 class="branch-modal__title">Выбор филиала</h3>
                <button type="button" class="js-create-branch" id="js-create-branch-btn">Создать</button>
                <button type="button" class="branch-modal__close-btn js-close-branch-modal">&times;</button>
            </div>

            <!-- Тело -->
            <div class="branch-modal__body">
                <input type="text"
                       class="form-control mb-3"
                       id="branch-search-input"
                       placeholder="Поиск по названию или адресу…">

                <div id="branch-search-results" class="branch-modal__results">
                    {{-- AJAX сюда подгружает branch_card --}}
                </div>
            </div>

        </div>
    </div>
</div>
