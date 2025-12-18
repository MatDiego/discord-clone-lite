import { Controller } from '@hotwired/stimulus';
import { Dropdown } from 'bootstrap';

export default class extends Controller {
    static targets = ['trigger', 'menu'];

    connect() {
        this.boundOnRightClick = this.onRightClick.bind(this);
        this.boundClose = this.close.bind(this);
        this.boundOnMouseLeave = this.startCloseTimer.bind(this);
        this.boundOnMouseEnter = this.stopCloseTimer.bind(this);

        // 1. Otwieranie prawym przyciskiem
        this.triggerTarget.addEventListener('contextmenu', this.boundOnRightClick);

        // 2. Obsługa wyjechania myszką (zarówno z Triggera jak i z Menu)
        // Jeśli wyjedziesz z triggera, zaczynamy odliczanie do zamknięcia
        this.triggerTarget.addEventListener('mouseleave', this.boundOnMouseLeave);
        // Jeśli jednak wrócisz na trigger (np. przypadkiem), anulujemy zamykanie
        this.triggerTarget.addEventListener('mouseenter', this.boundOnMouseEnter);

        if (this.hasMenuTarget) {
            // Jeśli wyjedziesz z menu, zamykamy
            this.menuTarget.addEventListener('mouseleave', this.boundOnMouseLeave);
            // Jeśli wjedziesz na menu (z triggera), anulujemy zamykanie
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
        // Sprzątanie listenerów
        this.triggerTarget.removeEventListener('contextmenu', this.boundOnRightClick);
        this.triggerTarget.removeEventListener('mouseleave', this.boundOnMouseLeave);
        this.triggerTarget.removeEventListener('mouseenter', this.boundOnMouseEnter);

        if (this.hasMenuTarget) {
            this.menuTarget.removeEventListener('mouseleave', this.boundOnMouseLeave);
            this.menuTarget.removeEventListener('mouseenter', this.boundOnMouseEnter);
        }

        // Zdejmujemy globalny listener, jeśli akurat był założony
        document.removeEventListener('click', this.boundClose);
        document.removeEventListener('contextmenu', this.boundClose);

        if (this.dropdown) {
            this.dropdown.dispose();
        }
    }

    onRightClick(event) {
        event.preventDefault();

        // Zamykamy inne otwarte
        document.querySelectorAll('.dropdown-menu.show').forEach(el => el.classList.remove('show'));

        this.dropdown.show();

        // 3. Dodajemy GLOBALNY listener zamknięcia na kliknięcie
        // Dodajemy go dopiero po otwarciu, żeby nie obciążać przeglądarki
        // setTimeout 0 przesuwa to na koniec kolejki zdarzeń, żeby bieżący klik nie zamknął menu od razu
        setTimeout(() => {
            document.addEventListener('click', this.boundClose);
            document.addEventListener('contextmenu', this.boundClose); // Prawy klik gdzie indziej też zamyka
        }, 0);
    }

    /*
     * Metoda wywoływana, gdy myszka ucieka z obszaru (Triggera LUB Menu)
     */
    startCloseTimer() {
        // Dajemy 150ms czasu na przejście z triggera na menu.
        // Jeśli w tym czasie user nie najedzie na żaden z nich -> zamykamy.
        this.closeTimeout = setTimeout(() => {
            this.close();
        }, 150);
    }

    /*
     * Metoda wywoływana, gdy myszka wchodzi na obszar (Triggera LUB Menu)
     */
    stopCloseTimer() {
        if (this.closeTimeout) {
            clearTimeout(this.closeTimeout);
            this.closeTimeout = null;
        }
    }

    /*
     * Główna metoda zamykająca
     */
    close(event) {
        // Jeśli to kliknięcie, sprawdzamy czy nie kliknęliśmy W menu (wtedy nie zamykamy, chyba że to link)
        if (event && this.hasMenuTarget && this.menuTarget.contains(event.target)) {
            return;
        }

        this.dropdown.hide();

        // Po zamknięciu zdejmujemy globalne nasłuchiwanie, żeby nie śmiecić
        document.removeEventListener('click', this.boundClose);
        document.removeEventListener('contextmenu', this.boundClose);
    }
}
