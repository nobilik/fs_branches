// module.js — общие утилиты
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        window.Branches = {};

        // --- debounce ---
        Branches.debounce = function (fn, delay = 250) {
            let t;
            return function (...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), delay);
            };
        };

        // --- universal AJAX fetch ---
        Branches.postJSON = async function (url, data = {}) {
            const token = document.querySelector('meta[name="csrf-token"]').content;

            return fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    "X-Requested-With": "XMLHttpRequest"
                },
                credentials: "same-origin",
                body: JSON.stringify(data)
            }).then(r => r.json());
        };
        Branches.deleteJSON = async function (url) {
            const token = document.querySelector('meta[name="csrf-token"]').content;

            return fetch(url, {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                credentials: "same-origin"
            }).then(r => r.json());
        };
    });
})();