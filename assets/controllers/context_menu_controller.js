import { Controller } from '@hotwired/stimulus';
import { Dropdown } from 'bootstrap';

export default class extends Controller {
    static targets = ['trigger', 'menu'];

    connect() {
        this.boundOnRightClick = this.onRightClick.bind(this);
        this.boundClose = this.close.bind(this);
        this.boundOnMouseLeave = this.startCloseTimer.bind(this);
        this.boundOnMouseEnter = this.stopCloseTimer.bind(this);

        this.triggerTarget.addEventListener('contextmenu', this.boundOnRightClick);
        this.triggerTarget.addEventListener('mouseleave', this.boundOnMouseLeave);
        this.triggerTarget.addEventListener('mouseenter', this.boundOnMouseEnter);

        if (this.hasMenuTarget) {
            this.menuTarget.addEventListener('mouseleave', this.boundOnMouseLeave);
            this.menuTarget.addEventListener('mouseenter', this.boundOnMouseEnter);
        }

        this.dropdown = new Dropdown(this.triggerTarget, {
            boundary: document.body,
            reference: 'toggle',
            display: 'dynamic',
            popperConfig: (defaultConfig) => {
                defaultConfig.strategy = 'fixed';
                return defaultConfig;
            }
        });
    }

    disconnect() {
        this.triggerTarget.removeEventListener('contextmenu', this.boundOnRightClick);
        this.triggerTarget.removeEventListener('mouseleave', this.boundOnMouseLeave);
        this.triggerTarget.removeEventListener('mouseenter', this.boundOnMouseEnter);

        if (this.hasMenuTarget) {
            this.menuTarget.removeEventListener('mouseleave', this.boundOnMouseLeave);
            this.menuTarget.removeEventListener('mouseenter', this.boundOnMouseEnter);
        }

        document.removeEventListener('click', this.boundClose);
        document.removeEventListener('contextmenu', this.boundClose);

        if (this.dropdown) {
            this.dropdown.dispose();
        }
    }

    onRightClick(event) {
        event.preventDefault();

        document.querySelectorAll('.dropdown-menu.show').forEach(el => el.classList.remove('show'));

        this.dropdown.show();

        setTimeout(() => {
            document.addEventListener('click', this.boundClose);
            document.addEventListener('contextmenu', this.boundClose);
        }, 0);
    }

    startCloseTimer() {
        this.closeTimeout = setTimeout(() => {
            this.close();
        }, 150);
    }

    stopCloseTimer() {
        if (this.closeTimeout) {
            clearTimeout(this.closeTimeout);
            this.closeTimeout = null;
        }
    }

    close(event) {
        if (event && this.hasMenuTarget && this.menuTarget.contains(event.target)) {
            return;
        }

        this.dropdown.hide();

        document.removeEventListener('click', this.boundClose);
        document.removeEventListener('contextmenu', this.boundClose);
    }
}
