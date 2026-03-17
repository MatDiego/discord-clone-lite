import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;

            const invitationId = button.dataset.invitationId;
            const friendName   = button.dataset.friendName;
            const csrfToken    = button.dataset.csrfToken;

            this.element.querySelector('[data-friend-name-placeholder]').textContent = friendName;
            this.element.querySelector('form').action = `/friends/${invitationId}/remove`;
            this.element.querySelector('[name="_csrf_token"]').value = csrfToken;
        });
    }
}
