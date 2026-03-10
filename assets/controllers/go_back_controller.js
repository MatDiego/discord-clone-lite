import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    back(event) {
        event.preventDefault();

        document.querySelectorAll('.dropdown-menu.show, .modal.show').forEach((el) => {
            el.classList.remove('show');
            if (!el.classList.contains('modal')) return;
                
            el.style.display = 'none';
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
        });

        if (window.history.length > 2) {
            window.history.back();
            return;
        } 
        
        if (this.element.hasAttribute('href')) {
            window.location.href = this.element.href;
        }
    }
}
