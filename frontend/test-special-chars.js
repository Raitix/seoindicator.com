// Test special character matching
const text = 'Learn C++ Today';
const keyword = 'C++';

const normalizedText = text.toLowerCase().trim();
const normalizedKeyword = keyword.toLowerCase().trim();

console.log('Normalized text:', normalizedText);
console.log('Normalized keyword:', normalizedKeyword);

// Escape special regex characters - need to escape: . * + ? ^ $ { } ( ) | [ ] \
const escapeRegex = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const escapedKeyword = escapeRegex(normalizedKeyword);
console.log('Escaped keyword:', escapedKeyword);

// Pattern that matches keyword as standalone word
const pattern = `(^|\\s|[^a-z0-9])${escapedKeyword}($|\\s|[^a-z0-9])`;
console.log('Pattern:', pattern);

const regex = new RegExp(pattern, 'i');
console.log('Regex:', regex);
console.log('Test result:', regex.test(normalizedText));

// Try matching
const match = normalizedText.match(regex);
console.log('Match:', match);

// Alternative: just check if the keyword exists surrounded by non-word chars or boundaries
const index = normalizedText.indexOf(normalizedKeyword);
console.log('Index of keyword:', index);

if (index >= 0) {
  const before = index === 0 ? '' : normalizedText[index - 1];
  const after = index + normalizedKeyword.length >= normalizedText.length ? '' : normalizedText[index + normalizedKeyword.length];
  console.log('Before char:', before, 'is whitespace/non-alnum:', !before || /[^a-z0-9]/.test(before));
  console.log('After char:', after, 'is whitespace/non-alnum:', !after || /[^a-z0-9]/.test(after));
}
