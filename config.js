// ====== КОНФИГУРАЦИЯ ======
const CONFIG = {
  SAVE_INTERVAL: 3000,           // Интервал сохранения (мс)
  CLICK_COST_MULT: 1.8,          // Множитель стоимости клика
  AUTO_COST_MULT: 2.0,           // Множитель стоимости дрона
  API_URL: 'save.php',           // URL для сохранения
  MAX_RESOURCES: 1e12,           // Максимальное количество ресурсов
  FLOAT_DURATION: 1000,          // Длительность всплывающего текста
  ANIMATION_DELAY: 100,          // Задержка анимации
  MAX_PARTICLES: 8,              // Максимальное количество частиц
  COMBO_WINDOW: 1000,            // Окно для комбо (мс)
  PRESTIGE_REQUIREMENT: 1e6,     // Ресурсы для престижа
  ACHIEVEMENTS: {                // Достижения
    FIRST_CLICK: { id: 'first_click', name: 'Первый шаг', icon: '👆', requirement: s => s.totalClicks >= 1 },
    CLICK_100: { id: 'click_100', name: 'Кликер', icon: '💪', requirement: s => s.totalClicks >= 100 },
    // ... остальные достижения
  }
};
