// branch-tags.js

(function() {
    'use strict';
    document.addEventListener("DOMContentLoaded", () => {

        const tagsHiddenInput = document.getElementById("selected-tags-input");
        
        if (!tagsHiddenInput) {
            return;
        }

        const input = document.getElementById("branch-tag-input");
        const suggestions = document.getElementById("tag-suggestions");
        const tagsBox = document.getElementById("branch-tags");
        const form = document.querySelector(".branch-create-form-wrapper form"); // Найдем главную форму
        
        
        // Массив для хранения ID выбранных тегов
        let selectedTagIds = [];
        
        let timer = null;
        
        // Инициализация: загружаем существующие теги из шаблона, если они есть
        document.querySelectorAll(".branch-tag-badge").forEach(badge => {
            const id = parseInt(badge.dataset.id);
            if (id) {
                selectedTagIds.push(id);
            }
        });
        // Обновляем скрытое поле при загрузке страницы
        updateHiddenInput();


        // ------- Автокомплит -------
        input.addEventListener("input", function () {
            // ... (логика debounce и fetch остается прежней)
            const q = this.value.trim();
            if (!q) {
                suggestions.style.display = "none";
                return;
            }

            clearTimeout(timer);
            timer = setTimeout(() => {
                fetch(`/branches/tags/search?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => showSuggestions(data.results));
            }, 200);
        });

        // ... (функция showSuggestions остается прежней)
        function showSuggestions(items) {
            suggestions.innerHTML = "";
            if (!items.length) {
                suggestions.style.display = "none";
                return;
            }

            items.forEach(tag => {
                const div = document.createElement("div");
                div.classList.add("list-group-item", "list-group-item-action");
                div.textContent = tag.name;
                div.dataset.id = tag.id;

                div.addEventListener("click", () => {
                    attachTag(tag.id, tag.name); // Используем существующий тег
                    suggestions.style.display = "none";
                    input.value = "";
                });

                suggestions.appendChild(div);
            });

            suggestions.style.display = "block";
        }

        // ------- Нажатие Enter = создать НОВЫЙ тег (AJAX) -------
        // Нам все равно нужен AJAX, чтобы создать тег на сервере и получить его ID,
        // но мы не будем привязывать его к объекту (branchId).
        input.addEventListener("keydown", e => {
            if (e.key === "Enter") {
                e.preventDefault();
                const name = input.value.trim();
                if (!name) return;

                // Запрос на создание тега
                fetch(`/branches/tags/create`, { // <--- Убедитесь, что у вас есть этот маршрут!
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ name }) 
                })
                .then(r => {
                    if (!r.ok) throw new Error('Ошибка создания тега');
                    return r.json();
                })
                .then(tag => {
                    attachTag(tag.id, tag.name); // Прикрепляем созданный тег
                    input.value = "";
                    suggestions.style.display = "none";
                })
                .catch(err => console.error("Ошибка при создании тега:", err));
            }
        });
        
        // ------- Функция обновления скрытого поля -------
        function updateHiddenInput() {
            // Преобразуем массив ID в JSON строку для передачи на сервер
            tagsHiddenInput.value = JSON.stringify(selectedTagIds);
        }


        // ------- attach в UI и JS массив -------
        function attachTag(id, name) {
            // 1. Проверка на дублирование
            if (document.querySelector(`.branch-tag-badge[data-id="${id}"]`)) return;
            if (selectedTagIds.includes(parseInt(id))) return;
            
            // 2. Добавление ID в массив
            selectedTagIds.push(parseInt(id));
            updateHiddenInput(); // Обновляем скрытое поле

            // 3. Создание значка в UI
            const badge = document.createElement("span");
            badge.classList.add("badge", "bg-primary", "branch-tag-badge", "me-1");
            badge.dataset.id = id;
            badge.style.cursor = "pointer";
            badge.innerHTML = `${name} <span class="ms-1 text-white remove-tag" data-id="${id}">×</span>`;

            tagsBox.appendChild(badge);
            
            // ⚠️ УДАЛЕН AJAX-запрос на attach, так как мы только собираем ID!
        }

        // ------- Удаление тега -------
        tagsBox.addEventListener("click", e => {
            if (e.target.classList.contains("remove-tag")) {
                const idToRemove = parseInt(e.target.dataset.id);

                // 1. Удаление ID из массива
                selectedTagIds = selectedTagIds.filter(id => id !== idToRemove);
                updateHiddenInput(); // Обновляем скрытое поле

                // 2. Удаление значка из UI
                e.target.closest(".branch-tag-badge").remove();
                
                // ⚠️ УДАЛЕН AJAX-запрос на detach
            }
        });

        // ------- Клик по тегу = переход на страницу списка веток -------
        tagsBox.addEventListener("click", e => {
            if (e.target.classList.contains("branch-tag-badge") && !e.target.classList.contains("remove-tag")) {
                const id = e.target.dataset.id;
                window.location.href = `/branches/by-tag/${id}`;
            }
        });

    });
})();