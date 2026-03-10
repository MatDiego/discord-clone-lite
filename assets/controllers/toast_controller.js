import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.timeout = setTimeout(() => {
            this.close();
        }, 2000);
    }

    close() {
        this.element.classList.remove('show');
        this.element.classList.add('hide');

        setTimeout(() => {
            this.element.remove();
        }, 300);
    }

    disconnect() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }
}
