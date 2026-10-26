import './bootstrap.js';

const notificationDuration = 3000;
const notificationExitDuration = 180;
const notificationTimers = new WeakMap();

const initializeNotifications = () => {
    document.querySelectorAll('[data-notification]:not([data-notification-initialized])').forEach((notification) => {
        notification.dataset.notificationInitialized = 'true';
        let dismissed = false;

        const dismiss = () => {
            if (dismissed) {
                return;
            }

            dismissed = true;
            window.clearTimeout(notificationTimers.get(notification));
            notification.classList.add('notification-dismissed');
            window.setTimeout(() => notification.remove(), notificationExitDuration);
        };

        notification.querySelector('.notification-close')?.addEventListener('click', dismiss);
        notificationTimers.set(notification, window.setTimeout(dismiss, notificationDuration));
        notification.addEventListener('notification:dismiss', dismiss, {once: true});
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeNotifications, {once: true});
} else {
    initializeNotifications();
}

document.addEventListener('turbo:load', initializeNotifications);

const initializeTagInputs = () => {
    document.querySelectorAll('[data-tag-input]:not([data-tag-input-initialized])').forEach((input) => {
        input.dataset.tagInputInitialized = 'true';

        const container = document.createElement('div');
        container.className = 'tag-input-container';
        const badges = document.createElement('div');
        badges.className = 'tag-input-badges';
        const suggestions = document.createElement('div');
        suggestions.className = 'tag-suggestions';
        suggestions.id = `${input.id}-suggestions`;
        suggestions.setAttribute('role', 'listbox');
        suggestions.hidden = true;
        input.parentNode.insertBefore(container, input);
        container.append(badges, input, suggestions);
        input.classList.add('tag-input-control');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', suggestions.id);
        input.setAttribute('aria-expanded', 'false');

        const tags = [];
        const availableTags = [...input.closest('.tag-input-field').querySelectorAll('[data-tag-option]')]
            .map((option) => option.textContent.trim());
        let activeSuggestion = -1;
        let filteredSuggestions = [];

        const normalize = (value) => value
            .normalize('NFD')
            .replace(/\p{Diacritic}/gu, '')
            .toLocaleLowerCase();

        const addTag = (value) => {
            const tag = value.trim();

            if (tag && !tags.some((existing) => existing.localeCompare(tag, undefined, {sensitivity: 'accent'}) === 0)) {
                tags.push(tag);
            }
        };

        const renderTags = () => {
            badges.replaceChildren();

            tags.forEach((tag, index) => {
                const badge = document.createElement('span');
                badge.className = 'badge rounded-pill tag-badge';
                const label = document.createElement('span');
                label.textContent = tag;
                const remove = document.createElement('button');
                remove.className = 'btn-close tag-remove';
                remove.type = 'button';
                remove.setAttribute('aria-label', `Remover ${tag}`);
                remove.addEventListener('click', () => {
                    tags.splice(index, 1);
                    renderTags();
                });
                badge.append(label, remove);
                badges.append(badge);
            });
        };

        const closeSuggestions = () => {
            suggestions.hidden = true;
            suggestions.replaceChildren();
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeSuggestion = -1;
            filteredSuggestions = [];
        };

        const renderSuggestions = (query) => {
            const normalizedQuery = normalize(query.trim());
            filteredSuggestions = availableTags
                .filter((tag) => !tags.some((selected) => normalize(selected) === normalize(tag)))
                .filter((tag) => normalizedQuery === '' || normalize(tag).includes(normalizedQuery))
                .slice(0, 8);
            activeSuggestion = -1;
            suggestions.replaceChildren();

            filteredSuggestions.forEach((tag, index) => {
                const option = document.createElement('button');
                option.className = 'tag-suggestion';
                option.type = 'button';
                option.id = `${suggestions.id}-option-${index}`;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.textContent = tag;
                option.addEventListener('mousedown', (event) => event.preventDefault());
                option.addEventListener('click', () => {
                    addTag(tag);
                    input.value = '';
                    renderTags();
                    input.focus();
                    renderSuggestions('');
                });
                suggestions.append(option);
            });

            suggestions.hidden = filteredSuggestions.length === 0;
            input.setAttribute('aria-expanded', String(!suggestions.hidden));
        };

        const selectActiveSuggestion = () => {
            if (activeSuggestion < 0 || !filteredSuggestions[activeSuggestion]) {
                return false;
            }

            addTag(filteredSuggestions[activeSuggestion]);
            input.value = '';
            renderTags();
            renderSuggestions('');

            return true;
        };

        const addCurrentTag = () => {
            addTag(input.value);
            input.value = '';
            renderTags();
            renderSuggestions('');
        };

        const initialTags = input.value.split(';');
        initialTags.forEach(addTag);
        input.value = '';
        renderTags();

        input.addEventListener('input', () => {
            const parts = input.value.split(';');

            if (parts.length > 1) {
                parts.slice(0, -1).forEach(addTag);
                input.value = parts.at(-1).trimStart();
                renderTags();
            }

            if (input.value.trim()) {
                renderSuggestions(input.value);
            } else {
                renderSuggestions('');
            }
        });

        input.addEventListener('focus', () => renderSuggestions(input.value));

        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' && !suggestions.hidden) {
                event.preventDefault();
                activeSuggestion = (activeSuggestion + 1) % filteredSuggestions.length;
            } else if (event.key === 'ArrowUp' && !suggestions.hidden) {
                event.preventDefault();
                activeSuggestion = (activeSuggestion - 1 + filteredSuggestions.length) % filteredSuggestions.length;
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (!selectActiveSuggestion()) {
                    addCurrentTag();
                }
            } else if (event.key === ';') {
                event.preventDefault();
                addCurrentTag();
            } else if (event.key === 'Escape') {
                closeSuggestions();
            } else if (event.key === 'Backspace' && input.value === '' && tags.length > 0) {
                tags.pop();
                renderTags();
            }

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                suggestions.querySelectorAll('[role="option"]').forEach((option, index) => {
                    const isActive = index === activeSuggestion;
                    option.classList.toggle('tag-suggestion-active', isActive);
                    option.setAttribute('aria-selected', String(isActive));
                });

                if (activeSuggestion >= 0) {
                    input.setAttribute('aria-activedescendant', `${suggestions.id}-option-${activeSuggestion}`);
                }
            }
        });

        input.addEventListener('blur', () => {
            if (input.value.trim()) {
                addTag(input.value);
                input.value = '';
                renderTags();
            }

            closeSuggestions();
        });

        input.form?.addEventListener('submit', () => {
            if (input.value.trim()) {
                addTag(input.value);
            }

            input.value = tags.join('; ');
        }, {capture: true});
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTagInputs, {once: true});
} else {
    initializeTagInputs();
}

document.addEventListener('turbo:load', initializeTagInputs);

const formatCurrencyInput = (input) => {
    const digits = input.value.replace(/\D/g, '').slice(0, 10);

    if (digits === '') {
        input.value = '';

        if (document.activeElement === input) {
            input.setSelectionRange(0, 0);
        }

        return;
    }

    const normalizedDigits = digits.replace(/^0+(?=\d)/, '');
    const paddedDigits = normalizedDigits.padStart(3, '0');
    const integer = paddedDigits.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const cents = paddedDigits.slice(-2);

    input.value = `${integer},${cents}`;

    if (document.activeElement === input) {
        input.setSelectionRange(input.value.length, input.value.length);
    }
};

const initializeCurrencyInputs = () => {
    document.querySelectorAll('[data-currency-input]:not([data-currency-input-initialized])').forEach((input) => {
        input.dataset.currencyInputInitialized = 'true';
        formatCurrencyInput(input);
        input.addEventListener('input', () => formatCurrencyInput(input));
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCurrencyInputs, {once: true});
} else {
    initializeCurrencyInputs();
}

document.addEventListener('turbo:load', initializeCurrencyInputs);

const initializeReferenceSearch = () => {
    document.querySelectorAll('[data-reference-search]:not([data-reference-search-initialized])').forEach((input) => {
        const list = document.getElementById(input.getAttribute('aria-controls'));

        if (!list) {
            return;
        }

        input.dataset.referenceSearchInitialized = 'true';
        const items = [...list.querySelectorAll('[data-reference-item]')];
        const emptyState = list.querySelector('[data-reference-empty]');

        input.addEventListener('input', () => {
            const query = input.value
                .normalize('NFD')
                .replace(/\p{Diacritic}/gu, '')
                .toLocaleLowerCase();
            let visibleItems = 0;

            items.forEach((item) => {
                const label = item.querySelector('[data-reference-label]').textContent
                    .normalize('NFD')
                    .replace(/\p{Diacritic}/gu, '')
                    .toLocaleLowerCase();
                const matches = label.includes(query);

                item.hidden = !matches;
                visibleItems += Number(matches);
            });

            if (emptyState) {
                emptyState.hidden = query.length === 0 || visibleItems > 0;
            }
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeReferenceSearch, {once: true});
} else {
    initializeReferenceSearch();
}

document.addEventListener('turbo:load', initializeReferenceSearch);

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('[data-notification]').forEach((notification) =>
        notification.dispatchEvent(new Event('notification:dismiss')),
    );
});
