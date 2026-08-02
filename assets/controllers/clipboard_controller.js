import { Controller } from '@hotwired/stimulus';

/**
 * Copie une valeur dans le presse-papier (ex. IBAN sans espaces).
 *
 * data-controller="clipboard"
 * data-clipboard-text-value="FR76 …"
 * data-action="clipboard#copy"
 * data-clipboard-target="label"
 */
export default class extends Controller {
    static values = {
        text: String,
        successLabel: { type: String, default: 'Copié !' },
    };

    static targets = ['label'];

    async copy() {
        const text = (this.textValue || '').replace(/\s+/g, '');
        if (!text) {
            return;
        }

        try {
            await navigator.clipboard.writeText(text);
            this.flashSuccess();
        } catch {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            this.flashSuccess();
        }
    }

    flashSuccess() {
        if (!this.hasLabelTarget) {
            return;
        }

        const original = this.labelTarget.textContent;
        this.labelTarget.textContent = this.successLabelValue;
        clearTimeout(this._timer);
        this._timer = setTimeout(() => {
            this.labelTarget.textContent = original;
        }, 2000);
    }
}
