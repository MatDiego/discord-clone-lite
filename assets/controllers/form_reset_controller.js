import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    reset() {
        this.element.reset();

        const input = this.element.querySelector('input, textarea');
        if (input) {
            input.focus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
}
