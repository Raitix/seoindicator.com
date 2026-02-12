const analyzeMetaDescription = require('../src/meta-description-analyzer');

describe('Meta Description Analyzer', () => {
  test('should return error for empty description', () => {
    expect(analyzeMetaDescription('')).toBe('Description is empty. Please provide a meta description.');
  });

  test('should return warning for short description', () => {
    expect(analyzeMetaDescription('Short')).toBe('Meta description is too short. Add more content for better SEO.');
  });

  test('should return warning for long description', () => {
    const longDescription = 'a'.repeat(161);
    expect(analyzeMetaDescription(longDescription)).toBe('Meta description is too long. Reduce the length to be within 50-160 characters.');
  });

  test('should return success for optimal description', () => {
    expect(analyzeMetaDescription('This description is exactly 50 characters long, perfect!')).toBe('Your meta description length is perfect. Ensure it aligns with your page content.');
  });
});