import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const memberId = button.dataset.memberId;
            const serverId = button.dataset.serverId;

            const frame = this.element.querySelector('#member-roles-frame');
            frame.src = `/servers/${serverId}/members/${memberId}/roles`;
        });
    }
}
