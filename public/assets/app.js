// Light/dark toggle. The server already rendered the cookie value into
// data-bs-theme, so this only flips it and remembers the choice.
(() => {
    const root = document.documentElement;
    const button = document.querySelector('[data-theme-toggle]');
    if (!button) {
        return;
    }

    button.addEventListener('click', () => {
        const next = root.dataset.bsTheme === 'dark' ? 'light' : 'dark';
        root.dataset.bsTheme = next;
        document.cookie = `kanban_theme=${next};path=/;max-age=31536000;samesite=lax`;
    });
})();
