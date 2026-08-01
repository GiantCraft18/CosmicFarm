// ====== СОСТОЯНИЕ ИГРЫ ======
class GameState {
  constructor() {
    this.resources = 0;
    this.clickPower = 1;
    this.autoPower = 0;
    this.clickCost = 10;
    this.autoCost = 20;
    this.totalClicks = 0;
    this.lastSave = Date.now();
    this.clickMultiplier = 1;
    this.autoMultiplier = 1;
    this.prestigeLevel = 0;
    this.prestigeBonus = 1;
  }

  load(data) {
    Object.assign(this, data);
  }

  reset() {
    Object.assign(this, new GameState());
  }

  serialize() {
    return {
      resources: this.resources,
      clickPower: this.clickPower,
      autoPower: this.autoPower,
      clickCost: this.clickCost,
      autoCost: this.autoCost,
      totalClicks: this.totalClicks,
      clickMultiplier: this.clickMultiplier,
      autoMultiplier: this.autoMultiplier,
      prestigeLevel: this.prestigeLevel,
      prestigeBonus: this.prestigeBonus
    };
  }

  getClickPower() {
    return this.clickPower * this.clickMultiplier * this.prestigeBonus;
  }

  getAutoPower() {
    return this.autoPower * this.autoMultiplier * this.prestigeBonus;
  }
}
