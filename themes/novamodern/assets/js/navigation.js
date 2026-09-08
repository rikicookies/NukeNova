(() => {
    const body = document.body;
    const toggles = [...document.querySelectorAll('[data-nav-toggle]')];
    const overlay = document.querySelector('[data-nav-overlay]');
    const mobile = () => window.matchMedia('(max-width: 900px)').matches;

    const sync = () => {
        const open = mobile() ? body.classList.contains('nav-open') : !body.classList.contains('nav-collapsed');
        toggles.forEach((button) => button.setAttribute('aria-expanded', String(open)));
        if (overlay) overlay.hidden = !mobile() || !body.classList.contains('nav-open');
    };

    if (!mobile() && localStorage.getItem('novamodern.navigation') === 'collapsed') {
        body.classList.add('nav-collapsed');
    }

    toggles.forEach((button) => button.addEventListener('click', () => {
        if (mobile()) {
            body.classList.toggle('nav-open');
        } else {
            body.classList.toggle('nav-collapsed');
            localStorage.setItem('novamodern.navigation', body.classList.contains('nav-collapsed') ? 'collapsed' : 'open');
        }
        sync();
    }));

    overlay?.addEventListener('click', () => {
        body.classList.remove('nav-open');
        sync();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && body.classList.contains('nav-open')) {
            body.classList.remove('nav-open');
            sync();
            toggles[0]?.focus();
        }
    });
    window.addEventListener('resize', () => {
        if (!mobile()) body.classList.remove('nav-open');
        sync();
    });
    sync();
})();

