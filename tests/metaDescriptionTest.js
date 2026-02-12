const { expect } = require('chai');
const metaDescriptionAnalyzer = require('../src/modules/metaDescriptionAnalyzer'); // Hypothetical module path

describe('Meta Description Analysis', function() {
    it('should correctly analyze the presence of a meta description', function() {
        const htmlSample = `<html><head><meta name='description' content='Sample meta description'></head><body></body></html>`;
        const result = metaDescriptionAnalyzer.analyzeMetaDescription(htmlSample);

        expect(result).to.have.property('hasMetaDescription', true);
        expect(result).to.have.property('content', 'Sample meta description');
    });

    it('should return false if meta description is missing', function() {
        const htmlSample = `<html><head></head><body></body></html>`;
        const result = metaDescriptionAnalyzer.analyzeMetaDescription(htmlSample);

        expect(result).to.have.property('hasMetaDescription', false);
    });

    it('should handle edge cases with empty content in meta description', function() {
        const htmlSample = `<html><head><meta name='description' content=''></head><body></body></html>`;
        const result = metaDescriptionAnalyzer.analyzeMetaDescription(htmlSample);

        expect(result).to.have.property('hasMetaDescription', true);
        expect(result).to.have.property('content', '');
    });

    it('should handle multiple meta descriptions with priority to the first', function() {
        const htmlSample = `<html><head><meta name='description' content='First'><meta name='description' content='Second'></head><body></body></html>`;
        const result = metaDescriptionAnalyzer.analyzeMetaDescription(htmlSample);

        expect(result).to.have.property('hasMetaDescription', true);
        expect(result).to.have.property('content', 'First');
    });
});