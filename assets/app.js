import './stimulus_bootstrap.js';
import * as bootstrap from 'bootstrap';
import { StreamActions } from '@hotwired/turbo';

StreamActions['close-modal'] = function () {
    const el = document.getElementById(this.getAttribute('target'));
    if (!el) return;
    const modal = bootstrap.Modal.getInstance(el);
    if (modal) modal.hide();
};
