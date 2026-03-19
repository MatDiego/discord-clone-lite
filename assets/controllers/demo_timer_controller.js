import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
    }

    static targets = ['time', 'bar']

    connect() {
        this.fetchTimer();
    }

    disconnect() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
    }

    async fetchTimer() {
        try {
            const response = await fetch(this.urlValue);
            const data = await response.json();

            this.secondsLeft = data.secondsLeft;
            this.totalSeconds = data.totalSeconds;

            this.updateDisplay();
            this.startCountdown();
        } catch {
            this.timeTarget.textContent = '--:--';
        }
    }

    startCountdown() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }

        this.intervalId = setInterval(() => {
            this.secondsLeft--;

            if (this.secondsLeft <= 0) {
                clearInterval(this.intervalId);
                this.timeTarget.textContent = '0:00';
                this.barTarget.style.width = '0%';
                return;
            }

            this.updateDisplay();
        }, 1000);
    }

    updateDisplay() {
        const minutes = Math.floor(this.secondsLeft / 60);
        const seconds = this.secondsLeft % 60;
        this.timeTarget.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        const percentage = (this.secondsLeft / this.totalSeconds) * 100;
        this.barTarget.style.width = `${percentage}%`;

        if (this.secondsLeft <= 60) {
            this.barTarget.classList.remove('bg-info');
            this.barTarget.classList.add('bg-danger');
        }
    }
}
