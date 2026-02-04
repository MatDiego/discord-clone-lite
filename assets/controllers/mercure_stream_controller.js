import { Controller } from '@hotwired/stimulus';
import { connectStreamSource, disconnectStreamSource } from '@hotwired/turbo';

/**
 * Mercure SSE Controller with CORS credentials support.
 * Replaces <turbo-stream-source> which doesn't support withCredentials.
 */
export default class extends Controller {
    static values = {
        url: String
    }

    connect() {
        if (!this.urlValue) {
            console.warn('Mercure URL not provided');
            return;
        }

        this.source = new EventSource(this.urlValue, {
            withCredentials: true
        });

        connectStreamSource(this.source);

        this.source.onerror = (error) => {
            console.error('Mercure SSE error:', error);
        };
    }

    disconnect() {
        if (this.source) {
            disconnectStreamSource(this.source);
            this.source.close();
            this.source = null;
        }
    }
}
