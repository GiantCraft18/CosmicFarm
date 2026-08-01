// ====== СИСТЕМА КОМБО ======
class ComboSystem {
  constructor(windowMs = 1000) {
    this.combo = 0;
    this.maxCombo = 0;
    this.lastClickTime = 0;
    this.windowMs = windowMs;
    this.timeout = null;
  }

  addClick() {
    const now = Date.now();
    if (now - this.lastClickTime < this.windowMs) {
      this.combo++;
      if (this.combo > this.maxCombo) {
        this.maxCombo = this.combo;
      }
    } else {
      this.combo = 1;
    }
    this.lastClickTime = now;

    clearTimeout(this.timeout);
    this.timeout = setTimeout(() => {
      this.combo = 0;
    }, this.windowMs);

    return this.combo;
  }

  getCombo() {
    return this.combo;
  }

  getMaxCombo() {
    return this.maxCombo;
  }

  getBonus() {
    // Бонус: до +100% (1 комбо = 1%)
    return Math.min(this.combo * 0.01, 1);
  }

  getComboText() {
    if (this.combo < 2) return '';
    const bonus = Math.round(this.getBonus() * 100);
    return `🔥 x${this.combo} (+${bonus}%)`;
  }

  reset() {
    this.combo = 0;
    clearTimeout(this.timeout);
  }
}
