class HintEngine {
    constructor() {
        this.hints = [];
        this.currentHintIndex = 0;
        this.maxHints = 3;
        this.hintCost = 10;
        this.isEnabled = false;
        this.lessonId = 1;
        
        this.cacheElement = document.getElementById('hints-container');
        this.buttonElement = document.getElementById('get-hint-btn');
        
        this.init();
    }

    init() {
        if (this.buttonElement) {
            this.buttonElement.addEventListener('click', () => this.requestHint());
        }
    }

    setLessonId(lessonId) {
        this.lessonId = lessonId || 1;
    }

    setHints(hints) {
        this.hints = hints || [];
        this.currentHintIndex = 0;
        this.updateHintDisplay();
    }

    enable(isEnabled) {
        this.isEnabled = isEnabled;
        if (this.buttonElement) {
            this.buttonElement.disabled = !isEnabled || this.currentHintIndex >= this.hints.length;
        }
    }

    async requestHint() {
        if (!this.isEnabled || this.currentHintIndex >= this.hints.length) {
            return;
        }

        const hint = this.hints[this.currentHintIndex];
        
        if (this.buttonElement) {
            this.buttonElement.textContent = 'Loading...';
            this.buttonElement.disabled = true;
        }

        try {
            await this.fetchHint(hint);
        } catch (error) {
            console.error('Error fetching hint:', error);
            this.showLocalHint(hint);
        }

        this.currentHintIndex++;
        this.updateHintDisplay();
        
        const event = new CustomEvent('hint:used', {
            detail: { cost: this.hintCost }
        });
        document.dispatchEvent(event);
    }

    async fetchHint(hint) {
        const hintId = hint.id || (hint.hintOrder) || (this.currentHintIndex + 1);
        const response = await fetch(`/api/get-hint.php?hint_id=${hintId}&lesson_id=${this.lessonId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch hint');
        }

        const data = await response.json();
        this.displayHint(data.hint);
    }

    showLocalHint(hint) {
        this.displayHint(hint.text || hint.content);
    }

    displayHint(text) {
        if (!this.cacheElement) return;

        const placeholder = this.cacheElement.querySelector('.hint-placeholder');
        if (placeholder) {
            placeholder.remove();
        }

        const hintDiv = document.createElement('div');
        hintDiv.className = 'hint-item';
        hintDiv.textContent = text;

        this.cacheElement.appendChild(hintDiv);
        
        hintDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    updateHintDisplay() {
        if (!this.cacheElement) return;

        if (this.hints.length === 0) {
            this.cacheElement.innerHTML = '<p class="hint-placeholder">Complete the lesson to earn hints.</p>';
            if (this.buttonElement) {
                this.buttonElement.textContent = 'Get Hint';
                this.buttonElement.disabled = true;
            }
            return;
        }

        const hintsShown = this.cacheElement.querySelectorAll('.hint-item');
        
        if (this.currentHintIndex >= this.hints.length) {
            if (this.buttonElement) {
                this.buttonElement.textContent = 'No More Hints';
                this.buttonElement.disabled = true;
            }
        } else {
            if (this.buttonElement) {
                this.buttonElement.textContent = `Get Hint (${this.hints.length - this.currentHintIndex} remaining)`;
                this.buttonElement.disabled = !this.isEnabled;
            }
        }
    }

    reset() {
        this.hints = [];
        this.currentHintIndex = 0;
        this.isEnabled = false;
        
        if (this.cacheElement) {
            this.cacheElement.innerHTML = '<p class="hint-placeholder">Complete the lesson to earn hints.</p>';
        }
        
        if (this.buttonElement) {
            this.buttonElement.textContent = 'Get Hint';
            this.buttonElement.disabled = true;
        }
    }

    getHintsRemaining() {
        return Math.max(0, this.hints.length - this.currentHintIndex);
    }

    getHintsUsed() {
        return this.currentHintIndex;
    }

    setMaxHints(max) {
        this.maxHints = max;
    }

    setHintCost(cost) {
        this.hintCost = cost;
    }
}

window.HintEngine = HintEngine;
