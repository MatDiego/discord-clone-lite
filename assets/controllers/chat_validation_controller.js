import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['submit'];

    connect() {
        this.validate();
    }

    validate() {
        if (this.hasSubmitTarget) {
            const empty = this.isEmpty();
            this.submitTarget.disabled = empty;
            this.submitTarget.classList.toggle('chat-send-btn--disabled', empty);
        }
    }

    preventSubmit(event) {
        if (this.isEmpty()) {
            event.preventDefault();
        }
    }

    isEmpty() {
        const input = this.element.querySelector('input, textarea');
        return !input || input.value.trim() === '';
    }
}
