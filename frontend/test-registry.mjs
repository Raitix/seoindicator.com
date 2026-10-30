import { COMPARISON_SLUGS, COMPETITOR_REGISTRY, getComparisonBySlug, parseSlug } from './lib/compare/registry.ts';

console.log('=== COMPETITOR_REGISTRY Keys ===');
console.log(Object.keys(COMPETITOR_REGISTRY));

console.log('\n=== COMPARISON_SLUGS ===');
console.log(COMPARISON_SLUGS);

console.log('\n=== Testing parseSlug ===');
const result = parseSlug('ahrefs-vs-semrush');
console.log('parseSlug("ahrefs-vs-semrush"):', result);

console.log('\n=== Testing getComparisonBySlug ===');
const comparison = getComparisonBySlug('ahrefs-vs-semrush');
console.log('getComparisonBySlug result:', comparison ? 'Found' : 'NULL');
if (comparison) {
    console.log('Title:', comparison.title);
}
