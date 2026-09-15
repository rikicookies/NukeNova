(() => {
    const parser = new DOMParser();

    const status = (() => {
        let node = document.querySelector('[data-ajax-status]');
        if (node) return node;
        node = document.createElement('div');
        node.className = 'ajax-action-status';
        node.dataset.ajaxStatus = '';
        node.setAttribute('role', 'status');
        node.setAttribute('aria-live', 'polite');
        node.hidden = true;
        document.body.appendChild(node);
        return node;
    })();

    let statusTimer = null;
    const announce = (message, error = false) => {
        if (!message) return;
        window.clearTimeout(statusTimer);
        status.textContent = message;
        status.classList.toggle('is-error', error);
        status.hidden = false;
        statusTimer = window.setTimeout(() => {
            status.hidden = true;
        }, 3200);
    };

    const selectorFor = (form) => form.dataset.ajaxReplace || '';
    const busy = (form, active) => {
        form.setAttribute('aria-busy', String(active));
        form.querySelectorAll('button, input[type="submit"]').forEach((button) => {
            button.disabled = active;
        });
    };

    const parseResponse = (html) => parser.parseFromString(html, 'text/html');

    const textFrom = (doc) => (doc.body?.textContent || '')
        .trim()
        .replace(/\s+/g, ' ')
        .slice(0, 300);

    const feedbackFrom = (doc) => {
        const error = doc.querySelector('.form-errors, .alert-error');
        if (error?.textContent.trim()) return {message: error.textContent.trim(), error: true};
        const success = doc.querySelector('.success-message, .alert-success');
        if (success?.textContent.trim()) return {message: success.textContent.trim(), error: false};
        return null;
    };

    const replaceFromDocument = (doc, selector) => {
        if (!selector) return false;
        const incoming = doc.querySelector(selector);
        const current = document.querySelector(selector);
        if (!incoming || !current) return false;
        const scrollX = window.scrollX;
        const scrollY = window.scrollY;
        current.replaceWith(incoming);
        window.scrollTo(scrollX, scrollY);
        return true;
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-ajax-action]');
        if (!form || event.defaultPrevented) return;

        event.preventDefault();
        if (form.dataset.ajaxPending === 'true') return;

        form.dataset.ajaxPending = 'true';
        const selector = selectorFor(form);
        const submitter = event.submitter;
        const formData = new FormData(form);
        if (submitter?.name) formData.append(submitter.name, submitter.value);

        busy(form, true);
        try {
            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                body: formData,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
            });

            const contentType = response.headers.get('content-type') || '';
            const body = await response.text();

            if (!contentType.includes('text/html')) {
                announce(body || 'Action failed.', true);
                return;
            }

            const doc = parseResponse(body);
            const feedback = feedbackFrom(doc);
            const replaced = replaceFromDocument(doc, selector);

            if (!replaced) {
                announce(
                    feedback?.message || textFrom(doc) || 'Action failed because the page could not be refreshed.',
                    true,
                );
                return;
            }

            announce(
                feedback?.message || form.dataset.ajaxSuccess || (response.ok ? 'Action completed.' : 'Action needs attention.'),
                feedback?.error ?? !response.ok,
            );
        } catch (error) {
            announce('Network error. The page was not changed.', true);
        } finally {
            delete form.dataset.ajaxPending;
            busy(form, false);
        }
    });
})();
