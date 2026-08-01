// ====== СИСТЕМА ДОСТИЖЕНИЙ ======
class Achievements {
  constructor(config) {
    this.config = config;
    this.unlocked = new Set();
    this.showing = false;
  }

  checkAll(state) {
    for (const [key, achievement] of Object.entries(this.config)) {
      if (!this.unlocked.has(key) && achievement.requirement(state)) {
        this.unlocked.add(key);
        this.showNotification(achievement);
      }
    }
  }

  showNotification(achievement) {
    if (this.showing) return;
    this.showing = true;

    const el = document.createElement('div');
    el.style.cssText = `
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      color: #1a1a2e;
      padding: 16px 32px;
      border-radius: 16px;
      font-weight: 700;
      z-index: 9999;
      box-shadow: 0 10px 40px rgba(251, 191, 36, 0.4);
      animation: slideDown 0.5s ease-out;
      text-align: center;
      min-width: 200px;
      pointer-events: none;
    `;
    el.innerHTML = `
      <div style="font-size: 2.5rem;">${achievement.icon}</div>
      <div style="font-size: 1.2rem;">${achievement.name}</div>
      <div style="font-size: 0.8rem; font-weight: 400; opacity: 0.8;">${achievement.desc || ''}</div>
    `;
    document.body.appendChild(el);

    setTimeout(() => {
      el.style.transition = 'opacity 0.5s, transform 0.5s';
      el.style.opacity = '0';
      el.style.transform = 'translateX(-50%) translateY(-20px)';
      setTimeout(() => {
        el.remove();
        this.showing = false;
      }, 500);
    }, 3000);
  }

  getUnlocked() {
    return Array.from(this.unlocked);
  }

  getProgress() {
    const total = Object.keys(this.config).length;
    return { unlocked: this.unlocked.size, total };
  }
  }
