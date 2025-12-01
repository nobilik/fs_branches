// address.js
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        const autocompleteInputs = document.querySelectorAll('.address-autocomplete');
        // const createBranchForm = document.getElementById('create-branch-form'); // Убран ненужный функционал
        const SEARCH_URL = '/branches/addresses/search';
        const DEBOUNCE_DELAY = 300;

        autocompleteInputs.forEach(input => {
            let timer;
            
            // --- 1. Находим связанные скрытые поля ---
            const guidInputSelector = input.dataset.guidInput;
            const guidInput = document.querySelector(guidInputSelector);
            
            const metaInputSelector = input.dataset.metaInput; // Ожидает data-meta-input="#address_meta"
            const metaInput = document.querySelector(metaInputSelector);
            // ----------------------------------------
            
            // --- 2. Создаем контейнер для подсказок ---
            let suggestionsContainer = input.nextElementSibling;
            if (!suggestionsContainer || !suggestionsContainer.classList.contains('autocomplete-suggestions')) {
                suggestionsContainer = document.createElement('div');
                suggestionsContainer.className = 'autocomplete-suggestions list-group position-absolute mt-1 shadow';
                suggestionsContainer.style.cssText = 'z-index: 2000; display:none; max-width: 400px; overflow-y: auto;';
                input.parentNode.insertBefore(suggestionsContainer, input.nextElementSibling);
            }

            // --- 3. Обработчик ввода (Input) ---
            input.addEventListener('input', function() {
                const q = this.value.trim();

                // Очищаем GUID и META, если пользователь начал вводить новый адрес
                if (guidInput && guidInput.value && q !== guidInput.dataset.fullAddress) {
                    guidInput.value = '';
                }
                if (metaInput) { // Очищаем мета-поле
                    metaInput.value = '';
                }
                
                if (!q || q.length < 3) {
                    suggestionsContainer.style.display = "none";
                    clearTimeout(timer);
                    return;
                }

                clearTimeout(timer);
                // Передаем metaInput в fetchSuggestions
                timer = setTimeout(() => {
                    fetchSuggestions(q, suggestionsContainer, input, guidInput, metaInput); 
                }, DEBOUNCE_DELAY);
            });

            // --- 4. Обработчик потери фокуса (Blur) ---
            input.addEventListener('blur', function() {
                setTimeout(() => {
                    suggestionsContainer.style.display = 'none';
                }, 150);
            });
            
            // --- 5. Обработчик фокуса (Focus) ---
            input.addEventListener('focus', function() {
                if (suggestionsContainer.innerHTML.trim() !== '' && this.value.trim().length >= 3) {
                    suggestionsContainer.style.display = 'block';
                }
            });
        });

        // ------------------------------------------------------------------
        // Глобальные функции
        // ------------------------------------------------------------------

        /**
         * Выполняет запрос к API и отображает результаты.
         */
        function fetchSuggestions(query, container, inputField, guidInput, metaInput) {
            const url = `${SEARCH_URL}?q=${encodeURIComponent(query)}`;
            const token = document.querySelector('meta[name="csrf-token"]').content;

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': token, 
                    'X-Requested-With': 'XMLHttpRequest', 
                }
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('Ошибка сети или сервера');
                }
                return r.json();
            })
            .then(data => {
                // Объединяем локальные и удаленные результаты и сериализуем meta в строку JSON
                const results = [
                    // Локальные результаты: meta либо уже строка JSON (из БД), либо null
                    ...data.local.map(item => ({ 
                        ...item, 
                        isRemote: false, 
                        meta: item.meta ? (typeof item.meta === 'string' ? item.meta : JSON.stringify(item.meta)) : null 
                    })),
                    // Удаленные (DaData): meta - это объект/массив, который нужно сериализовать
                    ...data.remote.map(item => ({ 
                        ...item, 
                        isRemote: true, 
                        meta: item.meta ? JSON.stringify(item.meta) : null 
                    }))
                ];
                
                showSuggestions(results, container, inputField, guidInput, metaInput);
            })
            .catch(err => {
                console.error('Ошибка автокомплита адреса:', err);
                container.style.display = 'none';
            });
        }

        /**
         * Отображает список подсказок и привязывает обработчик клика.
         */
        function showSuggestions(items, container, inputField, guidInput, metaInput) {
            container.innerHTML = "";

            if (!items || items.length === 0) {
                container.style.display = "none";
                return;
            }

            items.forEach(address => {
                const div = document.createElement("div");
                div.classList.add("list-group-item", "list-group-item-action");
                div.textContent = address.address;
                
                if (address.isRemote) {
                    div.textContent += ' (API)'; 
                }

                // --- Ключевой момент: сохраняем данные в data-атрибутах div ---
                div.dataset.guid = address.guid || ''; 
                div.dataset.fullAddress = address.address; 
                // address.meta уже должна быть строкой JSON
                div.dataset.meta = address.meta || ''; 

                div.addEventListener("click", () => {
                    const selectedMeta = div.dataset.meta;
                    
                    // 1. Устанавливаем полное название адреса в поле ввода
                    inputField.value = address.address;
                    
                    // 2. Устанавливаем GUID в скрытое поле
                    if (guidInput) {
                        guidInput.value = address.guid || '';
                        guidInput.dataset.fullAddress = address.address; 
                    }
                    
                    // 3. Устанавливаем META (строку JSON) в скрытое поле
                    if (metaInput && selectedMeta) {
                        // Записываем строку JSON, которую мы получили из fetch
                        metaInput.value = selectedMeta;
                    } else if (metaInput) {
                        metaInput.value = ''; 
                    }
                    
                    // 4. Скрываем контейнер
                    container.style.display = "none";
                });

                container.appendChild(div);
            });

            container.style.display = "block";
        }
    });
})();