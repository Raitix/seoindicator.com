// Test actual implementation
const escapeRegex = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

function checkKeywordInHeading(headingText, keyword) {
  if (!headingText || !keyword) return false;
  
  const normalizedHeading = headingText.toLowerCase().trim();
  const normalizedKeyword = keyword.toLowerCase().trim();
  
  // Escape special regex characters
  const escapedKeyword = escapeRegex(normalizedKeyword);
  
  // Use a pattern that matches the keyword as a standalone word
  // This works for both regular keywords (SEO) and special char keywords (C++)
  const regex = new RegExp(`(^|\\s|[^a-z0-9])${escapedKeyword}($|\\s|[^a-z0-9])`, 'i');
  return regex.test(normalizedHeading);
}

console.log('Test 1 (C++ in Learn C++ Today):', checkKeywordInHeading('Learn C++ Today', 'C++'));
console.log('Test 2 (SEO in SEO Guide):', checkKeywordInHeading('SEO Guide', 'SEO'));
console.log('Test 3 (SEO in Welcome):', checkKeywordInHeading('Welcome', 'SEO'));
console.log('Test 4 (seo in SEO Guide):', checkKeywordInHeading('SEO Guide', 'seo'));
