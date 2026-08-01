// ====== ОСНОВНОЙ КЛАСС ======
class Game {
  constructor() {
    this.state = new GameState();
    this.dom = new DOMController();
    this.achievements = new Achievements(CONFIG.ACHIEVEMENTS);
    this.combo = new ComboSystem(CONFIG.COMBO_WINDOW);
    this.prestige = new PrestigeSystem(CONFIG.PRESTIGE_REQUIREMENT);
    this.effects = new VisualEffects();
    this.isSaving = false;
    this.isMobile = Utils.isMobile();
    
    this.init();
  }

  async init() {
    // Загружаем сохранение
    try {
      const saved = await ApiClient.load();
      if (saved.success && saved.data) {
        this.state.load(saved.data);
      }
    } catch (e) {
      console.warn('Failed to load save:', e);
    }

    // Настраиваем обработчики
    this.setupEventListeners();
    
    // Запускаем циклы
    this.startAutoTick();
    this.startAutoSave();
    
    // Проверяем достижения
    this.achievements.checkAll(this.state);
    
    // Обновляем UI
    this.dom.update(this.state);
    
    console.log('🚀 Game started!', this.isMobile ? '📱 Mobile' : '💻 Desktop');
  }

  handleClick(e) {
    // Добавляем комбо бонус
    const comboBonus = this.combo.addClick();
    const power = this.state.getClickPower() * (1 + comboBonus);
    
    // Добавляем ресурсы
    this.state.resources = Utils.clamp(
      this.state.resources + power,
      0,
      CONFIG.MAX_RESOURCES
    );
    this.state.totalClicks++;
    
    // Обновляем UI
    this.dom.update(this.state);
    this.dom.animatePlanet();
    this.dom.highlightCounter();
    
    // Эффекты
    const pos = Utils.getPointerPosition(e);
    this.effects.showFloatingText(pos.x, pos.y, power);
    this.effects.createParticles(pos.x, pos.y, this.isMobile ? 4 : 8);
    
    // Проверяем достижения
    this.achievements.checkAll(this.state);
    
    // Проверяем возможность престижа
    if (this.prestige.canPrestige(this.state)) {
      this.dom.showPrestigeButton();
    }
  }

  buyClickUpgrade() {
    if (this.state.resources < this.state.clickCost) return;
    
    this.state.resources -= this.state.clickCost;
    this.state.clickPower++;
    this.state.clickCost = Math.floor(this.state.clickCost * CONFIG.CLICK_COST_MULT);
    
    this.dom.update(this.state);
    this.dom.glowPlanet('#3b6eff');
    this.achievements.checkAll(this.state);
  }

  buyAutoUpgrade() {
    if (this.state.resources < this.state.autoCost) return;
    
    this.state.resources -= this.state.autoCost;
    this.state.autoPower++;
    this.state.autoCost = Math.floor(this.state.autoCost * CONFIG.AUTO_COST_MULT);
    
    this.dom.update(this.state);
    this.dom.glowPlanet('#aaff88');
    this.achievements.checkAll(this.state);
  }

  prestige() {
    if (!this.prestige.canPrestige(this.state)) return;
    
    if (confirm(`Престиж до уровня ${this.state.prestigeLevel + 1}?`)) {
      this.prestige.doPrestige(this.state);
      this.dom.update(this.state);
      this.saveGame();
      this.dom.setSaveStatus('🌟 Престиж!');
    }
  }

  resetGame() {
    if (confirm('Сбросить всё? Прогресс будет потерян.')) {
      this.state.reset();
      this.dom.update(this.state);
      this.saveGame();
      this.dom.setSaveStatus('🔄 сброшено');
    }
  }

  startAutoTick() {
    setInterval(() => {
      const power = this.state.getAutoPower();
      if (power > 0) {
        this.state.resources = Utils.clamp(
          this.state.resources + power,
          0,
          CONFIG.MAX_RESOURCES
        );
        this.dom.update(this.state);
        this.dom.glowPlanet('#44ff88');
        this.achievements.checkAll(this.state);
      }
    }, 1000);
  }

  startAutoSave() {
    setInterval(() => {
      this.saveGame();
    }, CONFIG.SAVE_INTERVAL);
  }

  async saveGame() {
    if (this.isSaving) return;
    this.isSaving = true;
    
    try {
      const result = await ApiClient.save(this.state.serialize());
      if (result.success) {
        this.dom.setSaveStatus('✅ сохранено');
      } else {
        this.dom.setSaveStatus('❌ ошибка', true);
      }
    } catch (error) {
      this.dom.setSaveStatus('❌ сеть', true);
    } finally {
      this.isSaving = false;
    }
  }

  setupEventListeners() {
    // Клик по планете
    const clickArea = document.getElementById('clickArea');
    const handler = (e) => { e.preventDefault(); this.handleClick(e); };
    clickArea.addEventListener('click', handler);
    clickArea.addEventListener('touchstart', handler, { passive: false });

    // Кнопки улучшений
    document.getElementById('buyClick').addEventListener('click', () => this.buyClickUpgrade());
    document.getElementById('buyAuto').addEventListener('click', () => this.buyAutoUpgrade());

    // Сброс
    document.getElementById('reset').addEventListener('click', () => this.resetGame());

    // Сохранение при закрытии
    window.addEventListener('beforeunload', () => this.saveGame());

    // Клавиатура (только ПК)
    if (!this.isMobile) {
      document.addEventListener('keydown', (e) => {
        if (e.key === ' ') { e.preventDefault(); this.handleClick(e); }
        if (e.key === '1') this.buyClickUpgrade();
        if (e.key === '2') this.buyAutoUpgrade();
        if (e.key === 'p') this.prestige();
        if (e.key === 'r' && e.ctrlKey) { e.preventDefault(); this.resetGame(); }
      });
    }
  }
}

// ====== ЗАПУСК ======
document.addEventListener('DOMContentLoaded', () => {
  window.game = new Game();
});
