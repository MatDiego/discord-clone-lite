import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        class: { type: String, default: 'reveal-active' }
    }

    connect() {
        setTimeout(() => {
            this.element.classList.add(this.classValue);
        }, 50);

        setTimeout(() => {
            this.element.classList.remove(this.classValue);
        }, 2000);
    }
}
