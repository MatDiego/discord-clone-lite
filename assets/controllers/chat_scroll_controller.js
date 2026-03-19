import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["messagesList", "topSentinel", "bottomSentinel", "olderLoader", "newerLoader"];
    static values = {
        currentUser: String,
        olderUrl: String,
        newerUrl: String,
    };

    connect() {
        this.isLoading = false;
        this.ready = false;

        this.scrollTimeout = setTimeout(() => this.scrollToInitial(), 50);

        this.initTimeout = setTimeout(() => {
            this.ready = true;
            this.setupMutationObserver();
            this.setupIntersectionObservers();
        }, 600);
    }

    disconnect() {
        this.mutationObserver?.disconnect();
        this.intersectionObserver?.disconnect();
        this.unreadDividerObserver?.disconnect();
        clearTimeout(this.scrollTimeout);
        clearTimeout(this.initTimeout);
    }

    scrollToInitial() {
        const anchor = this.element.querySelector('#unread-anchor');

        if (anchor) {
            const anchorRect = anchor.getBoundingClientRect();
            const containerRect = this.element.getBoundingClientRect();
            this.element.scrollTop += anchorRect.top - containerRect.top;

            this.setupUnreadDividerObserver(anchor);
        } else {
            this.scrollToBottom(true);
        }
    }


    setupUnreadDividerObserver(divider) {
        this.unreadDividerObserver = new IntersectionObserver((entries) => {
            const entry = entries[0];
            if (entry && !entry.isIntersecting) {
                this.unreadDividerObserver.disconnect();
                divider.classList.add('unread-divider--fade-out');
                divider.addEventListener('animationend', () => divider.remove(), { once: true });
            }
        }, { root: this.element });

        this.unreadDividerObserver.observe(divider);
    }

    scrollToBottom(instant = false) {
        this.element.scrollTo({
            top: this.element.scrollHeight,
            behavior: instant ? 'instant' : 'smooth',
        });
    }


    setupMutationObserver() {
        this.mutationObserver = new MutationObserver((mutations) => this.handleNewMessages(mutations));
        if (this.hasMessagesListTarget) {
            this.mutationObserver.observe(this.messagesListTarget, { childList: true, subtree: true });
        }
    }

    handleNewMessages(mutations) {
        if (!this.ready) return;

        const isNearBottom = this.element.scrollHeight - this.element.scrollTop - this.element.clientHeight < 300;

        const shouldScroll = mutations.some(m => {
            if (m.type !== 'childList') return false;
            return Array.from(m.addedNodes).some(node => {
                if (node.nodeType !== Node.ELEMENT_NODE) return false;
                const msg = node.matches?.('.message-item') ? node : node.querySelector?.('.message-item');
                if (!msg) return false;
                return msg.dataset.authorId === this.currentUserValue || isNearBottom;
            });
        });

        if (shouldScroll) this.scrollToBottom(false);
    }


    setupIntersectionObservers() {
        this.intersectionObserver = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting || this.isLoading) continue;
                if (entry.target === this.topSentinelTarget) this.loadMessages('older');
                else if (entry.target === this.bottomSentinelTarget) this.loadMessages('newer');
            }
        }, { root: this.element, rootMargin: '300px' });

        if (this.hasTopSentinelTarget) this.intersectionObserver.observe(this.topSentinelTarget);
        if (this.hasBottomSentinelTarget) this.intersectionObserver.observe(this.bottomSentinelTarget);
    }

    async loadMessages(direction) {
        this.isLoading = true;

        const nodes = this.messagesListTarget.querySelectorAll('[data-message-id]');
        if (nodes.length === 0) {
            this.isLoading = false;
            return;
        }

        const isOlder = direction === 'older';
        const refId = isOlder ? nodes[0].dataset.messageId : nodes[nodes.length - 1].dataset.messageId;
        const url = isOlder ? this.olderUrlValue : this.newerUrlValue;
        const query = isOlder ? `?before=${refId}` : `?after=${refId}`;
        const loader = isOlder
            ? (this.hasOlderLoaderTarget ? this.olderLoaderTarget : null)
            : (this.hasNewerLoaderTarget ? this.newerLoaderTarget : null);

        loader?.classList.remove('d-none');

        try {
            const response = await fetch(`${url}${query}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            if (!html.trim()) {
                const sentinel = isOlder ? this.topSentinelTarget : this.bottomSentinelTarget;
                this.intersectionObserver.unobserve(sentinel);
                return;
            }

            this.insertMessages(direction, html);
        } catch (e) {
            console.error('[ChatScroll] Load failed', e);
        } finally {
            loader?.classList.add('d-none');
            this.isLoading = false;
        }
    }

    insertMessages(direction, html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const fragment = document.createDocumentFragment();

        doc.body.querySelectorAll('.message-item').forEach(msg => {
            fragment.appendChild(msg);
        });

        this.mutationObserver?.disconnect();

        if (direction === 'older') {
            const anchor = this.messagesListTarget.firstElementChild;
            const anchorTopBefore = anchor ? anchor.getBoundingClientRect().top : null;

            this.messagesListTarget.insertBefore(fragment, this.messagesListTarget.firstChild);

            if (anchor && anchorTopBefore !== null) {
                const anchorTopAfter = anchor.getBoundingClientRect().top;
                this.element.scrollTop += anchorTopAfter - anchorTopBefore;
            }
        } else {
            this.messagesListTarget.appendChild(fragment);
        }

        requestAnimationFrame(() => {
            if (this.hasMessagesListTarget) {
                this.mutationObserver.observe(this.messagesListTarget, { childList: true, subtree: true });
            }
        });
    }
}
