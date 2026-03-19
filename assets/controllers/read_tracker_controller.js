import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String, latestReadId: String };
    static targets = ["message"];

    connect() {
        this.highestReadId = this.latestReadIdValue || "0";
        this.scrollHandler = this.handleScroll.bind(this);

        this.initTimeout = setTimeout(() => {
            this.ready = true;
            this.element.addEventListener('scroll', this.scrollHandler, { passive: true });
        }, 1500);
    }

    disconnect() {
        this.element.removeEventListener('scroll', this.scrollHandler);
        clearTimeout(this.initTimeout);
        clearTimeout(this.checkTimeout);
    }

    handleScroll() {
        if (!this.ready || this.element.dataset.bulkLoading) return;
        clearTimeout(this.checkTimeout);
        this.checkTimeout = setTimeout(() => this.checkVisibleMessages(), 2000);
    }

    checkVisibleMessages() {
        if (this.element.dataset.bulkLoading) return;

        const rect = this.element.getBoundingClientRect();
        let maxId = "";

        this.messageTargets.forEach(el => {
            const id = el.dataset.messageId;
            if (!id) return;
            const r = el.getBoundingClientRect();
            if (r.top < rect.bottom - 20 && r.bottom > rect.top + 20 && id > maxId) {
                maxId = id;
            }
        });

        if (maxId && maxId > this.highestReadId) {
            this.highestReadId = maxId;
            this.reportToBackend(maxId);
        }
    }

    async reportToBackend(readId) {
        if (!this.urlValue) return;
        try {
            await fetch(this.urlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ lastReadId: readId }),
            });
        } catch (e) {
            console.error('[ReadTracker] Error', e);
        }
    }
}
