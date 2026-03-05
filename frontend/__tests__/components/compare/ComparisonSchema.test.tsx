/**
 * ComparisonSchema Component Tests
 * Tests for JSON-LD structured data generation (BreadcrumbList, FAQPage, HowTo)
 * 
 * @see TASK-32-10
 * @see TESTS/TEST-32-10.md
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Types from TASK-32-1
interface ComparisonData {
  slug: string;
  title: string;
  metaDescription: string;
  competitors: [string, string];
  summary: string;
  featureComparison: FeatureCategory[];
  pricingComparison: {
    seoindicator: PricingTier[];
    competitor: PricingTier[];
  };
  prosCons: {
    seoindicator: { pros: string[]; cons: string[] };
    competitor: { pros: string[]; cons: string[] };
  };
  useCases: {
    seoindicator: UseCase[];
    competitor: UseCase[];
  };
  verdict: string;
  faqs: FAQ[];
  relatedComparisons: string[];
}

interface PricingTier {
  name: string;
  monthlyPrice: number | 'Free';
  annualPrice: number | 'Free';
  features: string[];
  highlighted?: boolean;
}

interface FeatureCategory {
  name: string;
  features: Feature[];
}

interface Feature {
  name: string;
  seoindicator: boolean | string;
  competitor: boolean | string;
  tooltip?: string;
}

interface UseCase {
  title: string;
  description: string;
  audience: 'solo' | 'small-team' | 'enterprise' | 'agency';
  budget: 'free' | 'budget' | 'mid-range' | 'premium';
}

interface FAQ {
  question: string;
  answer: string;
}

interface ComparisonSchemaProps {
  comparison: ComparisonData;
  faqs: FAQ[];
}

// Mock ComparisonSchema component (TDD - will be implemented by Developer)
const ComparisonSchema: React.FC<ComparisonSchemaProps> = ({ comparison, faqs }) => {
  const breadcrumbSchema = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "https://seoindicator.com/"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "Compare",
        "item": "https://seoindicator.com/compare/"
      },
      {
        "@type": "ListItem",
        "position": 3,
        "name": comparison.title,
        "item": `https://seoindicator.com/compare/${comparison.slug}/`
      }
    ]
  };

  const faqSchema = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": faqs.map((faq) => ({
      "@type": "Question",
      "name": faq.question,
      "acceptedAnswer": {
        "@type": "Answer",
        "text": faq.answer
      }
    }))
  };

  const howToSchema = {
    "@context": "https://schema.org",
    "@type": "HowTo",
    "name": `How to Choose Between ${comparison.title}`,
    "step": [
      {
        "@type": "HowToStep",
        "name": "Compare Features",
        "text": "Review the feature comparison to see which tool offers what you need."
      },
      {
        "@type": "HowToStep",
        "name": "Compare Pricing",
        "text": "Evaluate pricing tiers to find the best fit for your budget."
      },
      {
        "@type": "HowToStep",
        "name": "Consider Your Use Case",
        "text": "Match your specific needs (solo, team, agency) to the recommended use cases."
      },
      {
        "@type": "HowToStep",
        "name": "Try Free Tier",
        "text": "Start with the free tier to test the tool before committing."
      }
    ]
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbSchema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(faqSchema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(howToSchema) }}
      />
    </>
  );
};

// Mock data factory
const createMockComparisonData = (overrides: Partial<ComparisonData> = {}): ComparisonData => ({
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs: Complete Comparison',
  metaDescription: 'Compare SEOIndicator and Ahrefs features, pricing, and use cases.',
  competitors: ['seoindicator', 'ahrefs'],
  summary: 'Both tools offer SEO analysis...',
  featureComparison: [],
  pricingComparison: {
    seoindicator: [{ name: 'Free', monthlyPrice: 'Free', annualPrice: 'Free', features: [] }],
    competitor: [{ name: 'Lite', monthlyPrice: 99, annualPrice: 82, features: [] }]
  },
  prosCons: {
    seoindicator: { pros: ['Free tier'], cons: ['Limited data'] },
    competitor: { pros: ['Comprehensive'], cons: ['Expensive'] }
  },
  useCases: {
    seoindicator: [{ title: 'Solo Blogger', description: 'For individuals', audience: 'solo', budget: 'free' }],
    competitor: [{ title: 'SEO Agency', description: 'For agencies', audience: 'agency', budget: 'premium' }]
  },
  verdict: 'Choose SEOIndicator for budget-conscious users...',
  faqs: [],
  relatedComparisons: ['seoindicator-vs-semrush'],
  ...overrides
});

const createMockFAQs = (): FAQ[] => [
  { question: 'What is the difference between SEOIndicator and Ahrefs?', answer: 'SEOIndicator focuses on free tools while Ahrefs offers comprehensive paid features.' },
  { question: 'Is SEOIndicator completely free?', answer: 'SEOIndicator offers a free tier with 5 analyses per day.' },
  { question: 'Which tool is better for beginners?', answer: 'SEOIndicator is better for beginners due to its simple interface.' }
];

describe('ComparisonSchema Component', () => {
  // ============================================
  // SCHEMA-001: BreadcrumbList Schema Generation
  // ============================================
  describe('BreadcrumbList Schema', () => {
    it('SCHEMA-001-001: generates valid BreadcrumbList with 3 levels', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try {
          const data = JSON.parse(s.innerHTML);
          return data['@type'] === 'BreadcrumbList';
        } catch {
          return false;
        }
      });
      
      expect(breadcrumbScript).toBeDefined();
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('BreadcrumbList');
      expect(schema.itemListElement).toHaveLength(3);
    });

    it('SCHEMA-001-002: has correct @context', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try {
          return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList';
        } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      expect(schema['@context']).toBe('https://schema.org');
    });

    it('SCHEMA-001-003: has correct @type', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try {
          return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList';
        } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      expect(schema['@type']).toBe('BreadcrumbList');
    });

    it('SCHEMA-001-004: position 1 is Home', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      const homeItem = schema.itemListElement[0];
      
      expect(homeItem['@type']).toBe('ListItem');
      expect(homeItem.position).toBe(1);
      expect(homeItem.name).toBe('Home');
      expect(homeItem.item).toBe('https://seoindicator.com/');
    });

    it('SCHEMA-001-005: position 2 is Compare', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      const compareItem = schema.itemListElement[1];
      
      expect(compareItem['@type']).toBe('ListItem');
      expect(compareItem.position).toBe(2);
      expect(compareItem.name).toBe('Compare');
      expect(compareItem.item).toBe('https://seoindicator.com/compare/');
    });

    it('SCHEMA-001-006: position 3 uses comparison title and slug', () => {
      const comparison = createMockComparisonData({
        title: 'Custom Comparison Title',
        slug: 'custom-slug'
      });
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      const comparisonItem = schema.itemListElement[2];
      
      expect(comparisonItem.position).toBe(3);
      expect(comparisonItem.name).toBe('Custom Comparison Title');
      expect(comparisonItem.item).toBe('https://seoindicator.com/compare/custom-slug/');
    });

    it('SCHEMA-001-007: itemListElement has exactly 3 items', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      expect(schema.itemListElement).toHaveLength(3);
    });

    it('SCHEMA-001-008: each item is ListItem type', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      schema.itemListElement.forEach((item: any) => {
        expect(item['@type']).toBe('ListItem');
      });
    });
  });

  // ============================================
  // SCHEMA-002: FAQPage Schema Generation
  // ============================================
  describe('FAQPage Schema', () => {
    it('SCHEMA-002-001: generates valid FAQPage with FAQs', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      expect(faqScript).toBeDefined();
      const schema = JSON.parse(faqScript!.innerHTML);
      
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('FAQPage');
    });

    it('SCHEMA-002-002: has correct @context', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      expect(schema['@context']).toBe('https://schema.org');
    });

    it('SCHEMA-002-003: has correct @type', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      expect(schema['@type']).toBe('FAQPage');
    });

    it('SCHEMA-002-004: mainEntity array matches FAQs length', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      expect(schema.mainEntity).toHaveLength(faqs.length);
    });

    it('SCHEMA-002-005: Question structure is correct', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      const question = schema.mainEntity[0];
      
      expect(question['@type']).toBe('Question');
      expect(question.name).toBe(faqs[0].question);
    });

    it('SCHEMA-002-006: Answer structure is correct', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      const answer = schema.mainEntity[0].acceptedAnswer;
      
      expect(answer['@type']).toBe('Answer');
      expect(answer.text).toBe(faqs[0].answer);
    });

    it('SCHEMA-002-007: empty FAQs array produces empty mainEntity', () => {
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = [];
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      expect(schema.mainEntity).toEqual([]);
    });

    it('SCHEMA-002-008: FAQ with special characters is properly escaped', () => {
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = [
        { question: 'What about "quotes" & <angles>?', answer: 'It\'s safe & works!' }
      ];
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      
      // Verify JSON was parsed successfully (meaning it's valid)
      expect(schema.mainEntity[0].question).toBe('What about "quotes" & <angles>?');
      expect(schema.mainEntity[0].acceptedAnswer.text).toBe('It\'s safe & works!');
    });
  });

  // ============================================
  // SCHEMA-003: HowTo Schema Generation
  // ============================================
  describe('HowTo Schema', () => {
    it('SCHEMA-003-001: generates valid HowTo with steps', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      expect(howToScript).toBeDefined();
      const schema = JSON.parse(howToScript!.innerHTML);
      
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('HowTo');
    });

    it('SCHEMA-003-002: has correct @context', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      expect(schema['@context']).toBe('https://schema.org');
    });

    it('SCHEMA-003-003: has correct @type', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      expect(schema['@type']).toBe('HowTo');
    });

    it('SCHEMA-003-004: name includes comparison title', () => {
      const comparison = createMockComparisonData({ title: 'My Comparison' });
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      expect(schema.name).toBe('How to Choose Between My Comparison');
    });

    it('SCHEMA-003-005: has exactly 4 steps', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      expect(schema.step).toHaveLength(4);
    });

    it('SCHEMA-003-006: step 1 is Compare Features', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      const step = schema.step[0];
      
      expect(step['@type']).toBe('HowToStep');
      expect(step.name).toBe('Compare Features');
      expect(step.text).toContain('feature comparison');
    });

    it('SCHEMA-003-007: step 2 is Compare Pricing', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      const step = schema.step[1];
      
      expect(step.name).toBe('Compare Pricing');
      expect(step.text).toContain('pricing tiers');
    });

    it('SCHEMA-003-008: step 3 is Consider Use Case', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      const step = schema.step[2];
      
      expect(step.name).toBe('Consider Your Use Case');
      expect(step.text).toContain('specific needs');
    });

    it('SCHEMA-003-009: step 4 is Try Free Tier', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      const step = schema.step[3];
      
      expect(step.name).toBe('Try Free Tier');
      expect(step.text).toContain('free tier');
    });

    it('SCHEMA-003-010: each step is HowToStep type', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      schema.step.forEach((step: any) => {
        expect(step['@type']).toBe('HowToStep');
      });
    });
  });

  // ============================================
  // SCHEMA-004: JSON-LD Script Tag Rendering
  // ============================================
  describe('JSON-LD Rendering', () => {
    it('SCHEMA-004-001: renders three script tags', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      expect(scripts).toHaveLength(3);
    });

    it('SCHEMA-004-002: all scripts have application/ld+json type', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      scripts.forEach(script => {
        expect(script).toHaveAttribute('type', 'application/ld+json');
      });
    });

    it('SCHEMA-004-003: uses dangerouslySetInnerHTML', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      // Each script should have innerHTML (from dangerouslySetInnerHTML)
      scripts.forEach(script => {
        expect(script.innerHTML).toBeTruthy();
      });
    });

    it('SCHEMA-004-004: each script contains valid JSON', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      scripts.forEach(script => {
        expect(() => JSON.parse(script.innerHTML)).not.toThrow();
      });
    });

    it('SCHEMA-004-005: no duplicate schemas when rendered multiple times', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      const { unmount, rerender } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      // Count schemas in first render
      const scripts1 = screen.getAllByRole('script');
      expect(scripts1).toHaveLength(3);
      
      // Re-render with same props
      rerender(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      // Should still have exactly 3 scripts
      const scripts2 = screen.getAllByRole('script');
      expect(scripts2).toHaveLength(3);
    });
  });

  // ============================================
  // SCHEMA-005: XSS Prevention
  // ============================================
  describe('XSS Prevention', () => {
    it('SCHEMA-005-001: script tag in title is escaped', () => {
      const comparison = createMockComparisonData({
        title: '<script>alert("xss")</script>Test'
      });
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const html = breadcrumbScript!.innerHTML;
      // JSON.stringify escapes < and > characters
      expect(html).not.toContain('<script>alert');
      expect(html).toContain('\\u003cscript\\u003e');
    });

    it('SCHEMA-005-002: script tag in FAQ question is escaped', () => {
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = [
        { question: '<script>evil()</script>Question', answer: 'Answer' }
      ];
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const html = faqScript!.innerHTML;
      expect(html).not.toContain('<script>evil');
    });

    it('SCHEMA-005-003: script tag in FAQ answer is escaped', () => {
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = [
        { question: 'Question', answer: '<script>evil()</script>Answer' }
      ];
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const html = faqScript!.innerHTML;
      expect(html).not.toContain('<script>evil');
    });

    it('SCHEMA-005-004: HTML entities are preserved in JSON', () => {
      const comparison = createMockComparisonData({
        title: 'A & B < C > D'
      });
      const faqs: FAQ[] = [
        { question: 'Test & more', answer: 'Answer with <entity>' }
      ];
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      
      // Parse and verify content is preserved
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      const breadcrumb = JSON.parse(breadcrumbScript!.innerHTML);
      
      expect(breadcrumb.itemListElement[2].name).toBe('A & B < C > D');
    });

    it('SCHEMA-005-005: unicode characters are preserved', () => {
      const comparison = createMockComparisonData({
        title: '测试工具 🚀 Comparison'
      });
      const faqs: FAQ[] = [
        { question: '问题? 🎯', answer: '答案! ✅' }
      ];
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const breadcrumb = JSON.parse(breadcrumbScript!.innerHTML);
      expect(breadcrumb.itemListElement[2].name).toBe('测试工具 🚀 Comparison');
    });

    it('SCHEMA-005-006: newlines are properly escaped', () => {
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = [
        { question: 'Question?', answer: 'Line1\nLine2\nLine3' }
      ];
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      // Should parse successfully
      const faq = JSON.parse(faqScript!.innerHTML);
      expect(faq.mainEntity[0].acceptedAnswer.text).toBe('Line1\nLine2\nLine3');
    });

    it('SCHEMA-005-007: quotes in content are escaped', () => {
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = [
        { question: 'He said "Hello"', answer: 'She said \'Hi\'' }
      ];
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const faq = JSON.parse(faqScript!.innerHTML);
      expect(faq.mainEntity[0].question).toBe('He said "Hello"');
      expect(faq.mainEntity[0].acceptedAnswer.text).toBe('She said \'Hi\'');
    });
  });

  // ============================================
  // SCHEMA-006: Component Props Validation
  // ============================================
  describe('Props Validation', () => {
    it('SCHEMA-006-001: renders with valid props', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
    });

    it('SCHEMA-006-002: renders with empty FAQs array', () => {
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = [];
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
      
      // FAQPage should have empty mainEntity
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const faqSchema = JSON.parse(faqScript!.innerHTML);
      expect(faqSchema.mainEntity).toEqual([]);
    });

    it('SCHEMA-006-003: handles empty title gracefully', () => {
      const comparison = createMockComparisonData({ title: '', slug: 'test' });
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      // Should render without crashing
      expect(container.querySelectorAll('script')).toHaveLength(3);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const breadcrumb = JSON.parse(breadcrumbScript!.innerHTML);
      expect(breadcrumb.itemListElement[2].name).toBe('');
    });

    it('SCHEMA-006-004: handles empty slug gracefully', () => {
      const comparison = createMockComparisonData({ title: 'Test', slug: '' });
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const breadcrumb = JSON.parse(breadcrumbScript!.innerHTML);
      expect(breadcrumb.itemListElement[2].item).toBe('https://seoindicator.com/compare//');
    });

    it('SCHEMA-006-005: ignores extra properties on comparison', () => {
      const comparison = {
        ...createMockComparisonData(),
        extraProp: 'ignored',
        anotherProp: 123
      } as any;
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      // Should render without using extra props
      expect(container.querySelectorAll('script')).toHaveLength(3);
    });
  });

  // ============================================
  // SCHEMA-007: Schema.org Compliance
  // ============================================
  describe('Schema.org Compliance', () => {
    it('SCHEMA-007-001: BreadcrumbList has all required fields', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      
      // Required fields per schema.org
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('BreadcrumbList');
      expect(schema.itemListElement).toBeDefined();
      expect(Array.isArray(schema.itemListElement)).toBe(true);
    });

    it('SCHEMA-007-002: FAQPage has all required fields', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      
      // Required fields per schema.org
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('FAQPage');
      expect(schema.mainEntity).toBeDefined();
      expect(Array.isArray(schema.mainEntity)).toBe(true);
    });

    it('SCHEMA-007-003: HowTo has all required fields', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      
      // Required fields per schema.org
      expect(schema['@context']).toBe('https://schema.org');
      expect(schema['@type']).toBe('HowTo');
      expect(schema.name).toBeDefined();
      expect(schema.step).toBeDefined();
      expect(Array.isArray(schema.step)).toBe(true);
    });

    it('SCHEMA-007-004: ListItem has all required fields', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      
      schema.itemListElement.forEach((item: any) => {
        expect(item['@type']).toBe('ListItem');
        expect(item.position).toBeDefined();
        expect(item.name).toBeDefined();
        expect(item.item).toBeDefined();
      });
    });

    it('SCHEMA-007-005: Question has all required fields', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      
      schema.mainEntity.forEach((question: any) => {
        expect(question['@type']).toBe('Question');
        expect(question.name).toBeDefined();
      });
    });

    it('SCHEMA-007-006: Answer has all required fields', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      
      schema.mainEntity.forEach((question: any) => {
        expect(question.acceptedAnswer['@type']).toBe('Answer');
        expect(question.acceptedAnswer.text).toBeDefined();
      });
    });

    it('SCHEMA-007-007: HowToStep has all required fields', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      const howToScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'HowTo'; } catch { return false; }
      });
      
      const schema = JSON.parse(howToScript!.innerHTML);
      
      schema.step.forEach((step: any) => {
        expect(step['@type']).toBe('HowToStep');
        expect(step.name).toBeDefined();
        expect(step.text).toBeDefined();
      });
    });
  });

  // ============================================
  // SCHEMA-008: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('SCHEMA-008-001: handles very long title', () => {
      const longTitle = 'A'.repeat(1000);
      const comparison = createMockComparisonData({ title: longTitle, slug: 'test' });
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      expect(schema.itemListElement[2].name).toBe(longTitle);
    });

    it('SCHEMA-008-002: handles very long FAQ answer', () => {
      const longAnswer = 'A'.repeat(10000);
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = [
        { question: 'Long answer?', answer: longAnswer }
      ];
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      expect(schema.mainEntity[0].acceptedAnswer.text).toBe(longAnswer);
    });

    it('SCHEMA-008-003: handles empty title', () => {
      const comparison = createMockComparisonData({ title: '' });
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
    });

    it('SCHEMA-008-004: handles empty slug', () => {
      const comparison = createMockComparisonData({ slug: '' });
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
    });

    it('SCHEMA-008-005: handles special URL characters in slug', () => {
      const comparison = createMockComparisonData({ slug: 'tool-with-dashes' });
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      expect(schema.itemListElement[2].item).toBe('https://seoindicator.com/compare/tool-with-dashes/');
    });

    it('SCHEMA-008-006: handles unicode in slug', () => {
      const comparison = createMockComparisonData({ slug: '测试' });
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
      
      const scripts = screen.getAllByRole('script');
      const breadcrumbScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'BreadcrumbList'; } catch { return false; }
      });
      
      const schema = JSON.parse(breadcrumbScript!.innerHTML);
      expect(schema.itemListElement[2].item).toBe('https://seoindicator.com/compare/测试/');
    });

    it('SCHEMA-008-007: handles large FAQ array (100 items)', () => {
      const comparison = createMockComparisonData();
      const faqs: FAQ[] = Array.from({ length: 100 }, (_, i) => ({
        question: `Question ${i + 1}?`,
        answer: `Answer ${i + 1}.`
      }));
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container.querySelectorAll('script')).toHaveLength(3);
      
      const scripts = screen.getAllByRole('script');
      const faqScript = scripts.find(s => {
        try { return JSON.parse(s.innerHTML)['@type'] === 'FAQPage'; } catch { return false; }
      });
      
      const schema = JSON.parse(faqScript!.innerHTML);
      expect(schema.mainEntity).toHaveLength(100);
    });
  });

  // ============================================
  // SCHEMA-010: Integration Tests
  // ============================================
  describe('Integration', () => {
    it('SCHEMA-010-001: renders all three schemas', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = container.querySelectorAll('script[type="application/ld+json"]');
      expect(scripts).toHaveLength(3);
      
      const types = Array.from(scripts).map(s => {
        try { return JSON.parse(s.innerHTML)['@type']; } catch { return null; }
      });
      
      expect(types).toContain('BreadcrumbList');
      expect(types).toContain('FAQPage');
      expect(types).toContain('HowTo');
    });

    it('SCHEMA-010-002: schemas are in correct order', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = container.querySelectorAll('script[type="application/ld+json"]');
      const types = Array.from(scripts).map(s => {
        try { return JSON.parse(s.innerHTML)['@type']; } catch { return null; }
      });
      
      expect(types[0]).toBe('BreadcrumbList');
      expect(types[1]).toBe('FAQPage');
      expect(types[2]).toBe('HowTo');
    });

    it('SCHEMA-010-003: matches snapshot', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      const { container } = render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      expect(container).toMatchSnapshot();
    });

    it('SCHEMA-010-004: valid for Google Rich Results', () => {
      const comparison = createMockComparisonData();
      const faqs = createMockFAQs();
      
      render(<ComparisonSchema comparison={comparison} faqs={faqs} />);
      
      const scripts = screen.getAllByRole('script');
      
      // Verify all schemas are parseable and have required structure
      scripts.forEach(script => {
        const schema = JSON.parse(script.innerHTML);
        
        // All schemas must have @context and @type
        expect(schema['@context']).toBe('https://schema.org');
        expect(schema['@type']).toBeDefined();
        
        // Type-specific validation
        if (schema['@type'] === 'BreadcrumbList') {
          expect(Array.isArray(schema.itemListElement)).toBe(true);
          expect(schema.itemListElement.length).toBeGreaterThan(0);
        }
        
        if (schema['@type'] === 'FAQPage') {
          expect(Array.isArray(schema.mainEntity)).toBe(true);
        }
        
        if (schema['@type'] === 'HowTo') {
          expect(schema.name).toBeDefined();
          expect(Array.isArray(schema.step)).toBe(true);
        }
      });
    });
  });
});
