import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.hideOwnActions();
        this.injectCsrfTokens();
    }

    hideOwnActions() {
        const currentUserId = document.body.dataset.currentUserId;
        if (!currentUserId) return;

        this.element.querySelectorAll('[data-member-user-id]').forEach((memberEl) => {
            const isSelf = memberEl.dataset.memberUserId === currentUserId;
            memberEl.querySelectorAll('.member-action-not-self').forEach((action) => {
                action.style.display = isSelf ? 'none' : '';
            });
        });
    }

    injectCsrfTokens() {
        const token = document.body.dataset.csrfFriendInvite;
        if (!token) return;

        this.element.querySelectorAll('[data-csrf-friend-invite]').forEach((input) => {
            input.value = token;
        });
    }
}
