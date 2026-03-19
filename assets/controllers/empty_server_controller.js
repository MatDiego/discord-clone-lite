import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const channelList = document.getElementById('channelListItems');
        if (!channelList) return;

        this.observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType !== Node.ELEMENT_NODE) continue;

                    const link = node.querySelector('a[href]') || (node.matches('a[href]') ? node : null);
                    if (link) {
                        window.location.href = link.href;
                        return;
                    }
                }
            }
        });

        this.observer.observe(channelList, { childList: true });
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }
    }
}
