import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['submit'];

    connect() {
        this.validate();
    }

    validate() {
        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = this.isEmpty();
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
