import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        uuid: String
    }

    static colors = ['blue', 'green', 'red', 'purple', 'orange'];

    connect() {
        const colorClass = this.getColorClass();
        this.element.classList.add(`avatar-gradient-${colorClass}`);
    }

    getColorClass() {
        if (!this.uuidValue) {
            return this.constructor.colors[0];
        }

        let hash = 0;
        for (let i = 0; i < this.uuidValue.length; i++) {
            hash += this.uuidValue.charCodeAt(i);
        }

        const index = hash % this.constructor.colors.length;

        return this.constructor.colors[index];
    }
}
