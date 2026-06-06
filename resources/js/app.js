const confirmDialog = document.getElementById('confirm-dialog');
const confirmMessage = document.getElementById('confirm-dialog-message');
const confirmCancel = confirmDialog?.querySelector('[data-confirm-cancel]');
const confirmAccept = confirmDialog?.querySelector('[data-confirm-accept]');

let pendingConfirmForm = null;
let pendingConfirmTrigger = null;

const closeConfirmDialog = () => {
    if (confirmDialog instanceof HTMLDialogElement && confirmDialog.open) {
        confirmDialog.close('cancel');
    }
};

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const message = form.dataset.confirm;

    if (!message || form.dataset.confirmAccepted === 'true') {
        delete form.dataset.confirmAccepted;
        return;
    }

    if (!(confirmDialog instanceof HTMLDialogElement) || confirmMessage === null) {
        event.preventDefault();
        return;
    }

    event.preventDefault();
    pendingConfirmForm = form;
    pendingConfirmTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    confirmMessage.textContent = message;
    confirmDialog.showModal();
    confirmCancel?.focus();
});

confirmCancel?.addEventListener('click', closeConfirmDialog);

confirmAccept?.addEventListener('click', () => {
    if (pendingConfirmForm === null) {
        closeConfirmDialog();
        return;
    }

    const form = pendingConfirmForm;
    form.dataset.confirmAccepted = 'true';
    closeConfirmDialog();
    form.requestSubmit();
});

confirmDialog?.addEventListener('close', () => {
    pendingConfirmForm = null;
    pendingConfirmTrigger?.focus();
    pendingConfirmTrigger = null;
});

const copyResetTimers = new WeakMap();

const writeClipboardText = async (text) => {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.setAttribute('readonly', '');
    textArea.style.position = 'fixed';
    textArea.style.inset = '-1000px auto auto -1000px';
    document.body.appendChild(textArea);
    textArea.select();

    try {
        document.execCommand('copy');
    } finally {
        textArea.remove();
    }
};

document.addEventListener('click', async (event) => {
    const trigger = event.target instanceof Element ? event.target.closest('[data-copy-text]') : null;

    if (!(trigger instanceof HTMLButtonElement)) {
        return;
    }

    const text = trigger.dataset.copyText;

    if (!text) {
        return;
    }

    const status = trigger.getAttribute('aria-describedby')
        ? document.getElementById(trigger.getAttribute('aria-describedby'))
        : null;
    const defaultLabel = trigger.dataset.copyDefaultLabel ?? trigger.textContent?.trim() ?? 'Copiar';

    trigger.dataset.copyDefaultLabel = defaultLabel;

    try {
        await writeClipboardText(text);
        trigger.textContent = trigger.dataset.copySuccess ?? 'Copiado';
        if (status) {
            status.textContent = `Clave ${text} copiada.`;
        }
    } catch {
        trigger.textContent = defaultLabel;
        if (status) {
            status.textContent = trigger.dataset.copyError ?? 'No se pudo copiar.';
        }
        return;
    }

    window.clearTimeout(copyResetTimers.get(trigger));
    copyResetTimers.set(trigger, window.setTimeout(() => {
        trigger.textContent = defaultLabel;
        if (status) {
            status.textContent = '';
        }
    }, 2000));
});
