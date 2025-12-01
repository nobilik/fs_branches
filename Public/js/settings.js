// settings.js — логика списка филиалов в разделе настроек

(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        // пример: AJAX-поиск филиалов в списке
        const searchInput = document.querySelector('#settings-branch-search');
        const list = document.querySelector('#settings-branch-list');

        if (searchInput && list) {
            searchInput.addEventListener('input', Branches.debounce(function () {
                const q = searchInput.value;

                fetch(`/branches/search?q=${encodeURIComponent(q)}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                })
                    .then(r => r.text())
                    .then(html => list.innerHTML = html)
                    .catch(() => list.innerHTML = '<div class="text-danger p-3">Ошибка загрузки</div>');
            }, 300));
        }

        // DELETE
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-branch-delete');
            if (!btn) return;

            if (!confirm("Удалить филиал?")) return;

            const id = btn.dataset.id;

            Branches.deleteJSON(`/branches/${id}`)
                .then(res => {
                    if (res.status === 'success') {
                        const card = btn.closest('.branch-card');
                        if (card && card.parentElement) {
                            card.parentElement.remove();
                        }
                    } else {
                        alert(res.message || 'Ошибка удаления');
                    }
                });
        });
    });
})();
