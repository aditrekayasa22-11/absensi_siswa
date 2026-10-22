(function () {
    "use strict";

    /* Sidebar mobile */
    const body = document.body;
    document.querySelectorAll("[data-sidebar-toggle]").forEach(function (button) {
        button.addEventListener("click", function () {
            body.classList.toggle("sidebar-open");
        });
    });
    document.querySelectorAll("[data-sidebar-close], .sidebar-overlay").forEach(function (element) {
        element.addEventListener("click", function () {
            body.classList.remove("sidebar-open");
        });
    });

    /* Alert close + auto dismiss */
    document.querySelectorAll(".alert").forEach(function (alert) {
        const close = alert.querySelector(".alert__close");
        if (close) {
            close.addEventListener("click", function () {
                alert.remove();
            });
        }
        const delay = parseInt(alert.getAttribute("data-auto-dismiss") || "0", 10);
        if (delay > 0) {
            window.setTimeout(function () {
                alert.style.opacity = "0";
                alert.style.transform = "translateY(-5px)";
                window.setTimeout(function () {
                    alert.remove();
                }, 250);
            }, delay);
        }
    });

    /* Password visibility */
    document.querySelectorAll("[data-toggle-password]").forEach(function (button) {
        button.addEventListener("click", function () {
            const target = document.getElementById(button.getAttribute("data-toggle-password"));
            if (!target) return;

            const show = target.type === "password";
            target.type = show ? "text" : "password";
            button.setAttribute("aria-label", show ? "Sembunyikan password" : "Tampilkan password");
            button.innerHTML = show
                ? '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6"/><path d="M9.9 5.2A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4.2M6.3 6.3A18 18 0 0 0 2 12s3.5 7 10 7a10.7 10.7 0 0 0 4.2-.8"/></svg>'
                : '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
        });
    });

    /* Password strength */
    document.querySelectorAll("[data-password-strength]").forEach(function (input) {
        const target = document.querySelector(input.getAttribute("data-password-strength"));
        if (!target) return;

        const label = target.querySelector(".strength__label");
        const labels = { weak: "Lemah", medium: "Cukup", strong: "Kuat" };

        function update() {
            const value = input.value;
            let score = 0;
            if (value.length >= 8) score++;
            if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;

            target.classList.remove("is-weak", "is-medium", "is-strong");
            if (value.length === 0) {
                if (label) label.textContent = "Kekuatan";
                return;
            }

            let level = "weak";
            if (score >= 4 || (score === 3 && value.length >= 10)) level = "strong";
            else if (score >= 2) level = "medium";

            target.classList.add("is-" + level);
            if (label) label.textContent = labels[level];
        }

        input.addEventListener("input", update);
        update();
    });

    /* Live clock */
    const clock = document.querySelector("[data-clock]");
    if (clock) {
        const dateTarget = document.querySelector("[data-clock-date]");
        const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

        function tick() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");
            const seconds = String(now.getSeconds()).padStart(2, "0");
            clock.textContent = hours + ":" + minutes + ":" + seconds;

            if (dateTarget) {
                dateTarget.textContent = days[now.getDay()] + ", " + now.getDate() + " " + months[now.getMonth()] + " " + now.getFullYear();
            }
        }

        tick();
        window.setInterval(tick, 1000);
    }

    /* Modal */
    let lastFocused = null;

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        lastFocused = document.activeElement;
        modal.hidden = false;
        body.style.overflow = "hidden";
        const focusTarget = modal.querySelector("input:not([type=hidden]), select, textarea, button");
        if (focusTarget) window.setTimeout(function () { focusTarget.focus(); }, 50);
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        body.style.overflow = "";
        if (lastFocused && typeof lastFocused.focus === "function") lastFocused.focus();
    }

    document.querySelectorAll("[data-open-modal]").forEach(function (trigger) {
        trigger.addEventListener("click", function (event) {
            event.preventDefault();
            openModal(trigger.getAttribute("data-open-modal"));
        });
    });

    document.querySelectorAll(".modal").forEach(function (modal) {
        modal.querySelectorAll("[data-close-modal], .modal__backdrop").forEach(function (closer) {
            closer.addEventListener("click", function () {
                closeModal(modal);
            });
        });
    });

    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") return;
        document.querySelectorAll(".modal:not([hidden])").forEach(closeModal);
        body.classList.remove("sidebar-open");
    });

    /* Delete confirmation */
    const deleteForm = document.getElementById("deleteForm");
    document.querySelectorAll("[data-delete-user]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!deleteForm) return;
            const id = button.getAttribute("data-user-id") || "";
            const name = button.getAttribute("data-user-name") || "";
            const nik = button.getAttribute("data-user-nik") || "";
            const action = button.getAttribute("data-action") || "";

            deleteForm.setAttribute("action", action);
            const idInput = deleteForm.querySelector("[name=id]");
            if (idInput) idInput.value = id;

            const nameTarget = document.querySelector("[data-delete-name]");
            const nikTarget = document.querySelector("[data-delete-nik]");
            if (nameTarget) nameTarget.textContent = name;
            if (nikTarget) nikTarget.textContent = nik;
        });
    });

    /* Leave modal status presets */
    document.querySelectorAll("[data-leave-status]").forEach(function (button) {
        button.addEventListener("click", function () {
            const select = document.getElementById("leaveStatus");
            if (select) select.value = button.getAttribute("data-leave-status");
        });
    });

    /* Tabs */
    document.querySelectorAll("[data-tabs]").forEach(function (group) {
        const buttons = group.querySelectorAll("[data-tab]");
        const scope = document.getElementById(group.getAttribute("data-tabs")) || document;

        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                const name = button.getAttribute("data-tab");
                buttons.forEach(function (item) { item.classList.remove("is-active"); });
                button.classList.add("is-active");

                scope.querySelectorAll("[data-tab-panel]").forEach(function (panel) {
                    panel.hidden = panel.getAttribute("data-tab-panel") !== name;
                });
            });
        });
    });

    /* Client-side table search */
    document.querySelectorAll("[data-table-search]").forEach(function (input) {
        const table = document.querySelector(input.getAttribute("data-table-search"));
        if (!table) return;
        const rows = table.querySelectorAll("tbody tr");

        input.addEventListener("input", function () {
            const query = input.value.toLowerCase().trim();
            rows.forEach(function (row) {
                if (row.hasAttribute("data-empty-row")) return;
                row.style.display = row.textContent.toLowerCase().indexOf(query) !== -1 ? "" : "none";
            });
        });
    });

    /* Copy to clipboard */
    document.querySelectorAll("[data-copy]").forEach(function (button) {
        button.addEventListener("click", function () {
            const value = button.getAttribute("data-copy") || "";
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(value).then(function () {
                const original = button.textContent;
                button.textContent = "Tersalin!";
                window.setTimeout(function () { button.textContent = original; }, 1600);
            });
        });
    });

    /* Textarea character counter */
    document.querySelectorAll("[data-counter]").forEach(function (field) {
        const target = document.querySelector(field.getAttribute("data-counter"));
        if (!target) return;

        function update() {
            target.textContent = field.value.length + " karakter";
        }

        field.addEventListener("input", update);
        update();
    });

    /* Rotating headline words (auth pages) */
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    document.querySelectorAll("[data-rotate-words]").forEach(function (element) {
        const words = (element.getAttribute("data-rotate-words") || "")
            .split("|")
            .map(function (word) { return word.trim(); })
            .filter(Boolean);
        const wordElement = element.querySelector(".auth-rotate__word");

        if (!wordElement || words.length < 2 || reducedMotion.matches) return;

        let index = 0;
        window.setInterval(function () {
            wordElement.classList.add("is-leaving");

            window.setTimeout(function () {
                index = (index + 1) % words.length;
                wordElement.textContent = words[index];
                wordElement.classList.remove("is-leaving");
                wordElement.classList.add("is-entering");

                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () {
                        wordElement.classList.remove("is-entering");
                    });
                });
            }, 310);
        }, 3000);
    });

    /* Prevent double submit */
    document.querySelectorAll("form[data-single-submit]").forEach(function (form) {
        form.addEventListener("submit", function () {
            const button = form.querySelector("button[type=submit]");
            if (!button) return;
            window.setTimeout(function () {
                button.disabled = true;
                button.classList.add("is-disabled");
            }, 10);
        });
    });
})();
