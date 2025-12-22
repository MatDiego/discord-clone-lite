import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {

        this.timeout = setTimeout(() => {
            this.close();
        }, 3000);
    }

    close() {
        this.element.classList.add('toast-fade-out');
        setTimeout(() => {
            this.element.remove();
        });
    }

    disconnect() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }
}
