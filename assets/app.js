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
        input.parentNode.insertBefore(container, input);
        container.append(badges, input);

        const tags = [];
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

        const initialTags = input.value.split(';');
        initialTags.forEach(addTag);
        input.value = '';
        renderTags();

        input.addEventListener('input', () => {
            const parts = input.value.split(';');

            if (parts.length < 2) {
                return;
            }

            parts.slice(0, -1).forEach(addTag);
            input.value = parts.at(-1).trimStart();
            renderTags();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addTag(input.value);
                input.value = '';
                renderTags();
            } else if (event.key === 'Backspace' && input.value === '' && tags.length > 0) {
                tags.pop();
                renderTags();
            }
        });

        input.addEventListener('blur', () => {
            if (input.value.trim()) {
                addTag(input.value);
                input.value = '';
                renderTags();
            }
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

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('[data-notification]').forEach((notification) =>
        notification.dispatchEvent(new Event('notification:dismiss')),
    );
});
