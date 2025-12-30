// conversation.js — управление объектами в карточке заявки с подсветкой и дебаунсом
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('branchSelectModalOverlay');
        
        if (!overlay) {
            return; 
        }
        // ========================
        // Элементы модалки выбора
        // ========================
        const body = document.body;
        const searchInput = document.getElementById('branch-search-input');
        const searchResults = document.getElementById('branch-search-results');
        const createBranchBtn = document.getElementById('js-create-branch-btn');

        // ========================
        // Элементы модалки создания
        // ========================
        const createBranchOverlay = document.getElementById('branch-create-modal-overlay');
        const createBranchForm = document.getElementById('branch-create-form');

        // ========================
        // Открытие модалки выбора объекта
        // ========================
        document.querySelectorAll('.js-open-branch-modal').forEach(btn => {
            btn.addEventListener('click', function() {
                overlay.classList.add('open');
                body.classList.add('modal-open');
                overlay.dataset.conversationId = this.dataset.conversationId;

                searchInput.value = '';
                searchResults.innerHTML = '';
                if (createBranchBtn) createBranchBtn.disabled = false;
            });
        });

        // ========================
        // Закрытие модалки выбора
        // ========================
        document.querySelectorAll('.js-close-branch-modal').forEach(btn => {
            btn.addEventListener('click', function() {
                overlay.classList.remove('open');
                body.classList.remove('modal-open');
            });
        });

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });

        // ========================
        // Поиск объектов с debounce
        // ========================
        let debounceTimer = null;
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            searchResults.innerHTML = '';
            if (query.length < 3) return;

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchBranches(query), 300);
        });

        function fetchBranches(query) {
            const token = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`/branches/search?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => renderSearchResults(data, query))
            .catch(err => {
                console.error(err);
                searchResults.innerHTML = '<div class="text-danger">Ошибка поиска</div>';
            });
        }

        function renderSearchResults(branches, query) {
            searchResults.innerHTML = '';
            if (!branches) return;

            const items = branches.data || branches;
            if (!items || !items.length) {
                searchResults.innerHTML = '<div><em>Ничего не найдено</em></div>';
                return;
            }

            const qRegex = new RegExp(escapeRegExp(query), 'gi');

            items.forEach(branch => {
                const div = document.createElement('div');
                div.classList.add('branch-card-select');

                const highlightedName = branch.name.replace(qRegex, match => `<mark>${match}</mark>`);
                const highlightedAddress = (branch.full_address || '').replace(qRegex, match => `<mark>${match}</mark>`);

                const tagsHtml = (branch.tags || []).map(t =>
                    `<span class="badge tag-c-${t.color}" style="margin-right:4px;">${escapeHtml(t.name)}</span>`
                ).join('');

                div.innerHTML = `
                    <div>
                        <strong>${highlightedName}</strong>
                    </div>
                    <div class="text-muted small">${highlightedAddress}</div>
                    <div class="mt-2">${tagsHtml}</div>
                    <div class="mt-2 branch-card__button-container">
                        <button class="branch-modal__submit-btn js-attach-branch" data-branch-id="${branch.id}">Выбрать</button>
                    </div>
                `;

                searchResults.appendChild(div);
            });
        }

        // ========================
        // Привязка выбранного объекта
        // ========================
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-attach-branch');
            if (!btn) return;

            const branchId = btn.dataset.branchId;
            const conversationId = overlay.dataset.conversationId;

            if (!branchId || !conversationId) {
                alert('Не выбран объект или не найдена заявка');
                return;
            }

            btn.disabled = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`/branches/${branchId}/attach`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ conversation_id: conversationId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    overlay.classList.remove('open');
                    window.location.reload();
                } else {
                    alert(data.message || 'Ошибка');
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Ошибка на сервере');
                btn.disabled = false;
            });
        });

        // ========================
        // Отвязка объекта
        // ========================
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-branch');
            if (!btn) return;

            e.preventDefault();
            if (!confirm('Отвязать объект от заявки?')) return;

            const conversationId = btn.dataset.conversationId;
            const token = document.querySelector('meta[name="csrf-token"]').content;

            fetch('/branches/attach', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ conversation_id: conversationId, branch_id: null, _method: 'DELETE' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) window.location.reload();
                else alert(data.message || 'Ошибка при отвязке');
            })
            .catch(err => {
                console.error(err);
                alert('Ошибка на сервере при отвязке объекта');
            });
        });

        // ========================
        // Создание нового объекта
        // ========================
        if (createBranchBtn && createBranchOverlay && createBranchForm) {
            createBranchBtn.addEventListener('click', function() {
                createBranchOverlay.classList.add('open');
                createBranchForm.querySelector('#branch-conversation-id').value =
                    overlay.dataset.conversationId;
                createBranchForm.reset();
            });

            createBranchOverlay.querySelectorAll('.js-close-modal').forEach(btn => {
                btn.addEventListener('click', function() {
                    createBranchOverlay.classList.remove('open');
                });
            });

            createBranchOverlay.addEventListener('click', function(e) {
                if (e.target === createBranchOverlay) createBranchOverlay.classList.remove('open');
            });

            createBranchForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const token = document.querySelector('meta[name="csrf-token"]').content;
                const formData = new FormData(createBranchForm);
                const conversationId = formData.get('conversation_id');

                fetch('/branches', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        // Необязательно, но может быть полезно:
                        'Accept': 'application/json' 
                    },
                    credentials: 'same-origin',
                    body: formData
                })
                .then(res => {
                    // Проверяем, был ли ответ успешным (2xx статус)
                    if (!res.ok) {
                        // Если статус 422 (Validation Error) или другой 4xx/5xx
                        // Мы все равно пытаемся получить тело JSON для ошибки
                        return res.json().then(errorData => {
                            // Бросаем ошибку, используя сообщение из ответа сервера
                            throw new Error(errorData.message || 'Ошибка сервера при создании объекта');
                        });
                    }
                    return res.json();
                })
                .then(data => {
                    // Теперь data содержит: { message: '...', branch: {...} }
                    
                    // Проверка успешности больше не нужна, т.к. она выполнена в .then(res => ...)
                    createBranchOverlay.classList.remove('open');
                    
                    // **Используем data.branch, как определено в контроллере**
                    const createdBranch = data.branch; 

                    // привязка к заявке
                    return fetch('/branches/' + createdBranch.id + '/attach', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ conversation_id: conversationId })
                    });
                })
                .then(() => window.location.reload())
                .catch(err => {
                    console.error(err);
                    // err.message будет содержать сообщение об ошибке, выброшенное выше
                    alert(err.message); 
                });
            });
        }

        // ========================
        // Хелперы
        // ========================
        function escapeHtml(s) {
            if (!s) return '';
            return String(s).replace(/[&<>"']/g, function(m) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
            });
        }

        function escapeRegExp(s) {
            return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
    });
})();
