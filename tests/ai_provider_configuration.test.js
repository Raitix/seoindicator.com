const { AIProvider } = require('../src/config');

describe('AI Provider Configuration Tests', () => {
  test('should switch to Vercel AI Gateway', () => {
    const config = new AIProvider();
    config.setProvider('vercel');
    expect(config.getCurrentProvider()).toBe('Vercel AI Gateway');
  });

  test('should switch to OpenRouter', () => {
    const config = new AIProvider();
    config.setProvider('openrouter');
    expect(config.getCurrentProvider()).toBe('OpenRouter');
  });

  test('should handle invalid provider gracefully', () => {
    const config = new AIProvider();
    expect(() => config.setProvider('invalid')).toThrow('Invalid provider');
  });
});