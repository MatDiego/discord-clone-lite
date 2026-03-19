import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;

            const memberId   = button.dataset.memberId;
            const serverId   = button.dataset.serverId;
            const memberName = button.dataset.memberUsername;

            this.element.querySelector('[data-member-name-placeholder]').textContent = memberName;
            this.element.querySelector('form').action = `/servers/${serverId}/members/${memberId}/ban`;

            const permanentRadio = this.element.querySelector('#banPerm');
            if (permanentRadio) {
                permanentRadio.checked = true;
            }
        });
    }
}
