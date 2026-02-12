class AIProvider {
  constructor() {
    this.providers = {
      vercel: 'Vercel AI Gateway',
      openrouter: 'OpenRouter',
    };
    this.currentProvider = null;
  }

  setProvider(providerKey) {
    if (!this.providers[providerKey]) {
      throw new Error('Invalid provider');
    }
    this.currentProvider = this.providers[providerKey];
  }

  getCurrentProvider() {
    return this.currentProvider;
  }
}

module.exports = { AIProvider };