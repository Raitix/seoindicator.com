/**
 * SocialMediaPreview Component Tests
 * Task: TASK-16-8 - Social Media Preview Tool
 *
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Import actual components
import {
  SocialMediaPreviewForm,
  type SocialMediaPreviewFormData,
} from '@/components/tools/SocialMediaPreviewForm';
import { PlatformTabs, type Platform } from '@/components/tools/PlatformTabs';
import { PreviewCard, type PlatformPreview } from '@/components/tools/PreviewCard';
import { MissingTagsList } from '@/components/tools/MissingTagsList';
import { WarningsList, type SocialWarning } from '@/components/tools/WarningsList';
import {
  SocialMediaPreviewResults,
  type SocialMediaPreviewData,
} from '@/components/tools/SocialMediaPreviewResults';

// =============================================================================
// TEST SUITE
// =============================================================================

describe('SocialMediaPreview Component', () => {
  // ===========================================================================
  // FORM TESTS (FM)
  // ===========================================================================

  describe('Form Component', () => {
    const mockOnAnalyze = jest.fn();

    beforeEach(() => {
      mockOnAnalyze.mockClear();
    });

    /**
     * FM-001: Form renders correctly
     */
    it('should render form with URL input and submit button', () => {
      render(<SocialMediaPreviewForm onAnalyze={mockOnAnalyze} />);

      expect(screen.getByTestId('social-media-form')).toBeInTheDocument();
      expect(screen.getByLabelText('Enter page URL')).toBeInTheDocument();
      expect(screen.getByTestId('analyze-button')).toBeInTheDocument();
    });

    /**
     * FM-002: URL input is required
     */
    it('should show error when URL is empty', async () => {
      render(<SocialMediaPreviewForm onAnalyze={mockOnAnalyze} />);

      const submitButton = screen.getByTestId('analyze-button');
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByRole('alert')).toHaveTextContent('URL is required');
      });
      expect(mockOnAnalyze).not.toHaveBeenCalled();
    });

    /**
     * FM-003: Invalid URL format shows error
     */
    it('should show error for invalid URL format', async () => {
      render(<SocialMediaPreviewForm onAnalyze={mockOnAnalyze} />);

      const input = screen.getByLabelText('Enter page URL');
      await userEvent.type(input, 'not-a-url');
      fireEvent.click(screen.getByTestId('analyze-button'));

      await waitFor(() => {
        expect(screen.getByRole('alert')).toHaveTextContent('valid URL');
      });
    });

    /**
     * FM-004: Valid URL submits form
     */
    it('should call onAnalyze with valid URL', async () => {
      render(<SocialMediaPreviewForm onAnalyze={mockOnAnalyze} />);

      const input = screen.getByLabelText('Enter page URL');
      await userEvent.type(input, 'https://example.com');
      fireEvent.click(screen.getByTestId('analyze-button'));

      await waitFor(() => {
        expect(mockOnAnalyze).toHaveBeenCalledWith({ url: 'https://example.com' });
      });
    });

    /**
     * FM-005: Loading state disables form
     */
    it('should disable inputs when loading', () => {
      render(<SocialMediaPreviewForm onAnalyze={mockOnAnalyze} isLoading={true} />);

      expect(screen.getByLabelText('Enter page URL')).toBeDisabled();
      expect(screen.getByTestId('analyze-button')).toBeDisabled();
      expect(screen.getByTestId('analyze-button')).toHaveTextContent('Analyzing...');
    });

    /**
     * FM-006: Initial URL populates input
     */
    it('should populate URL input with initial value', () => {
      render(
        <SocialMediaPreviewForm
          onAnalyze={mockOnAnalyze}
          initialUrl="https://example.com/initial"
        />
      );

      expect(screen.getByLabelText('Enter page URL')).toHaveValue('https://example.com/initial');
    });

    /**
     * FM-007: Protocol validation
     */
    it('should reject non-HTTP protocols', async () => {
      render(<SocialMediaPreviewForm onAnalyze={mockOnAnalyze} />);

      const input = screen.getByLabelText('Enter page URL');
      await userEvent.type(input, 'ftp://example.com');
      fireEvent.click(screen.getByTestId('analyze-button'));

      await waitFor(() => {
        expect(screen.getByRole('alert')).toHaveTextContent('HTTP or HTTPS');
      });
    });

    /**
     * FM-008: URL input clears error on change
     */
    it('should clear error when URL is changed', async () => {
      render(<SocialMediaPreviewForm onAnalyze={mockOnAnalyze} />);

      // Trigger error
      fireEvent.click(screen.getByTestId('analyze-button'));
      await waitFor(() => {
        expect(screen.getByRole('alert')).toBeInTheDocument();
      });

      // Type in input to clear error
      const input = screen.getByLabelText('Enter page URL');
      await userEvent.type(input, 'https://example.com');

      await waitFor(() => {
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
      });
    });
  });

  // ===========================================================================
  // PLATFORM TABS TESTS (PT)
  // ===========================================================================

  describe('Platform Tabs Component', () => {
    const mockOnTabChange = jest.fn();

    beforeEach(() => {
      mockOnTabChange.mockClear();
    });

    /**
     * PT-001: All three platform tabs render
     */
    it('should render Facebook, Twitter, and LinkedIn tabs', () => {
      render(<PlatformTabs activeTab="facebook" onTabChange={mockOnTabChange} />);

      expect(screen.getByTestId('tab-facebook')).toBeInTheDocument();
      expect(screen.getByTestId('tab-twitter')).toBeInTheDocument();
      expect(screen.getByTestId('tab-linkedin')).toBeInTheDocument();
    });

    /**
     * PT-002: Active tab is highlighted
     */
    it('should highlight the active tab', () => {
      render(<PlatformTabs activeTab="twitter" onTabChange={mockOnTabChange} />);

      expect(screen.getByTestId('tab-twitter')).toHaveAttribute('aria-selected', 'true');
      expect(screen.getByTestId('tab-facebook')).toHaveAttribute('aria-selected', 'false');
    });

    /**
     * PT-003: Tab click triggers callback
     */
    it('should call onTabChange when tab is clicked', () => {
      render(<PlatformTabs activeTab="facebook" onTabChange={mockOnTabChange} />);

      fireEvent.click(screen.getByTestId('tab-linkedin'));

      expect(mockOnTabChange).toHaveBeenCalledWith('linkedin');
    });

    /**
     * PT-005: ARIA attributes for accessibility
     */
    it('should have correct ARIA attributes', () => {
      render(<PlatformTabs activeTab="facebook" onTabChange={mockOnTabChange} />);

      const tablist = screen.getByRole('tablist');
      expect(tablist).toBeInTheDocument();

      const tabs = screen.getAllByRole('tab');
      expect(tabs).toHaveLength(3);

      tabs.forEach((tab) => {
        expect(tab).toHaveAttribute('aria-controls');
      });
    });
  });

  // ===========================================================================
  // PREVIEW CARD TESTS (PC)
  // ===========================================================================

  describe('Preview Card Component', () => {
    const mockData: PlatformPreview = {
      title: 'Test Page Title',
      description: 'This is a test description for the preview.',
      image: 'https://example.com/image.jpg',
      domain: 'example.com',
    };

    /**
     * PC-001: Preview card renders with all data
     */
    it('should render preview card with title, description, image, and domain', () => {
      render(<PreviewCard platform="facebook" data={mockData} />);

      expect(screen.getByTestId('preview-title')).toHaveTextContent('Test Page Title');
      expect(screen.getByTestId('preview-description')).toHaveTextContent('test description');
      expect(screen.getByTestId('preview-image')).toHaveAttribute('src', mockData.image);
      expect(screen.getByTestId('preview-domain')).toHaveTextContent('example.com');
    });

    /**
     * PC-002: Preview card handles missing image
     */
    it('should render without image when not provided', () => {
      const dataNoImage = { ...mockData, image: null };
      render(<PreviewCard platform="twitter" data={dataNoImage} />);

      expect(screen.queryByTestId('preview-image')).not.toBeInTheDocument();
    });

    /**
     * PC-003: Preview card handles missing title
     */
    it('should show fallback text for missing title', () => {
      const dataNoTitle = { ...mockData, title: null };
      render(<PreviewCard platform="linkedin" data={dataNoTitle} />);

      expect(screen.getByTestId('preview-title')).toHaveTextContent('No title');
    });

    /**
     * PC-004: Preview card handles missing description
     */
    it('should show fallback text for missing description', () => {
      const dataNoDesc = { ...mockData, description: null };
      render(<PreviewCard platform="facebook" data={dataNoDesc} />);

      expect(screen.getByTestId('preview-description')).toHaveTextContent('No description');
    });

    /**
     * PC-005: Different platform styling
     */
    it('should have platform-specific test id', () => {
      const { rerender } = render(<PreviewCard platform="facebook" data={mockData} />);
      expect(screen.getByTestId('facebook-preview-card')).toBeInTheDocument();

      rerender(<PreviewCard platform="twitter" data={mockData} />);
      expect(screen.getByTestId('twitter-preview-card')).toBeInTheDocument();

      rerender(<PreviewCard platform="linkedin" data={mockData} />);
      expect(screen.getByTestId('linkedin-preview-card')).toBeInTheDocument();
    });

    /**
     * PC-006: Long title truncation
     */
    it('should handle long titles appropriately', () => {
      const longTitle = 'A'.repeat(200);
      const dataLongTitle = { ...mockData, title: longTitle };

      render(<PreviewCard platform="facebook" data={dataLongTitle} />);

      const title = screen.getByTestId('preview-title');
      expect(title).toHaveTextContent('A');
    });

    /**
     * PC-007: Image alt text for accessibility
     */
    it('should have accessible alt text for image', () => {
      render(<PreviewCard platform="facebook" data={mockData} />);

      const image = screen.getByTestId('preview-image');
      expect(image).toHaveAttribute('alt', 'facebook preview');
    });
  });

  // ===========================================================================
  // MISSING TAGS LIST TESTS (MT)
  // ===========================================================================

  describe('Missing Tags List Component', () => {
    /**
     * MT-001: Renders list of missing tags
     */
    it('should render list of missing tags', () => {
      const missing = ['og:image', 'twitter:card', 'og:description'];
      render(<MissingTagsList missing={missing} />);

      expect(screen.getByTestId('missing-tags')).toBeInTheDocument();
      expect(screen.getByTestId('missing-og:image')).toBeInTheDocument();
      expect(screen.getByTestId('missing-twitter:card')).toBeInTheDocument();
    });

    /**
     * MT-002: Empty list shows nothing
     */
    it('should render nothing when no missing tags', () => {
      render(<MissingTagsList missing={[]} />);

      expect(screen.queryByTestId('missing-tags')).not.toBeInTheDocument();
    });

    /**
     * MT-003: Heading is accessible
     */
    it('should have accessible heading', () => {
      render(<MissingTagsList missing={['og:title']} />);

      expect(screen.getByRole('heading', { name: /missing/i })).toBeInTheDocument();
    });
  });

  // ===========================================================================
  // WARNINGS LIST TESTS (WL)
  // ===========================================================================

  describe('Warnings List Component', () => {
    /**
     * WL-001: Renders list of warnings
     */
    it('should render list of warnings with field and message', () => {
      const warnings: SocialWarning[] = [
        { field: 'og:image', message: 'Image URL should use HTTPS' },
        { field: 'twitter:image', message: 'Image is too small' },
      ];
      render(<WarningsList warnings={warnings} />);

      expect(screen.getByTestId('warnings-list')).toBeInTheDocument();
      expect(screen.getByTestId('warning-0')).toHaveTextContent('HTTPS');
      expect(screen.getByTestId('warning-1')).toHaveTextContent('too small');
    });

    /**
     * WL-002: Empty warnings shows nothing
     */
    it('should render nothing when no warnings', () => {
      render(<WarningsList warnings={[]} />);

      expect(screen.queryByTestId('warnings-list')).not.toBeInTheDocument();
    });

    /**
     * WL-003: Warning format includes field name
     */
    it('should display field name in warning', () => {
      const warnings: SocialWarning[] = [{ field: 'og:image', message: 'Warning message' }];
      render(<WarningsList warnings={warnings} />);

      const warning = screen.getByTestId('warning-0');
      expect(warning).toHaveTextContent('og:image');
    });
  });

  // ===========================================================================
  // RESULTS COMPONENT TESTS (RC)
  // ===========================================================================

  describe('Results Component', () => {
    const mockData: SocialMediaPreviewData = {
      openGraph: {
        title: 'OG Title',
        description: 'OG Description',
        image: 'https://example.com/og.jpg',
        url: 'https://example.com',
        type: 'website',
        siteName: 'Example Site',
      },
      twitterCard: {
        card: 'summary_large_image',
        site: '@example',
        creator: '@author',
        title: 'Twitter Title',
        description: 'Twitter Description',
        image: 'https://example.com/twitter.jpg',
      },
      previews: {
        facebook: {
          title: 'Facebook Title',
          description: 'Facebook Description',
          image: 'https://example.com/fb.jpg',
          domain: 'example.com',
        },
        twitter: {
          card: 'summary_large_image',
          title: 'Twitter Title',
          description: 'Twitter Description',
          image: 'https://example.com/twitter.jpg',
        },
        linkedin: {
          title: 'LinkedIn Title',
          description: 'LinkedIn Description',
          image: 'https://example.com/li.jpg',
        },
      },
      missing: ['og:image:width', 'og:image:height'],
      warnings: [{ field: 'og:image', message: 'Image should be at least 1200x630' }],
    };

    /**
     * RC-001: Results render with data
     */
    it('should render results with all components', () => {
      render(<SocialMediaPreviewResults data={mockData} />);

      expect(screen.getByTestId('social-media-results')).toBeInTheDocument();
      expect(screen.getByTestId('platform-tabs')).toBeInTheDocument();
      expect(screen.getByTestId('missing-tags')).toBeInTheDocument();
      expect(screen.getByTestId('warnings-list')).toBeInTheDocument();
    });

    /**
     * RC-002: Error state renders error message
     */
    it('should render error message when error provided', () => {
      render(<SocialMediaPreviewResults data={null} error="Failed to analyze URL" />);

      expect(screen.getByTestId('error-message')).toHaveTextContent('Failed to analyze URL');
    });

    /**
     * RC-003: Null data renders nothing
     */
    it('should render nothing when data is null', () => {
      const { container } = render(<SocialMediaPreviewResults data={null} />);

      expect(container.firstChild).toBeNull();
    });

    /**
     * RC-004: Tab switching changes preview
     */
    it('should switch preview when tab is clicked', async () => {
      render(<SocialMediaPreviewResults data={mockData} />);

      // Initially shows Facebook preview
      expect(screen.getByTestId('facebook-preview-card')).toBeInTheDocument();

      // Click Twitter tab
      fireEvent.click(screen.getByTestId('tab-twitter'));

      await waitFor(() => {
        expect(screen.getByTestId('twitter-preview-card')).toBeInTheDocument();
      });
    });

    /**
     * RC-005: Missing tags list is populated
     */
    it('should show missing tags from data', () => {
      render(<SocialMediaPreviewResults data={mockData} />);

      expect(screen.getByTestId('missing-og:image:width')).toBeInTheDocument();
      expect(screen.getByTestId('missing-og:image:height')).toBeInTheDocument();
    });

    /**
     * RC-006: Warnings list is populated
     */
    it('should show warnings from data', () => {
      render(<SocialMediaPreviewResults data={mockData} />);

      expect(screen.getByTestId('warning-0')).toHaveTextContent('1200x630');
    });

    /**
     * RC-007: Facebook tab is default active
     */
    it('should have Facebook tab active by default', () => {
      render(<SocialMediaPreviewResults data={mockData} />);

      expect(screen.getByTestId('tab-facebook')).toHaveAttribute('aria-selected', 'true');
    });

    /**
     * RC-008: Tab panel has correct ARIA attributes
     */
    it('should have correct ARIA attributes for tab panel', () => {
      render(<SocialMediaPreviewResults data={mockData} />);

      const panel = screen.getByRole('tabpanel');
      expect(panel).toHaveAttribute('id', 'facebook-panel');
      expect(panel).toHaveAttribute('aria-labelledby', 'tab-facebook');
    });
  });

  // ===========================================================================
  // ACCESSIBILITY TESTS (AC)
  // ===========================================================================

  describe('Accessibility', () => {
    /**
     * AC-001: Form is accessible
     */
    it('form should be accessible', () => {
      render(<SocialMediaPreviewForm onAnalyze={() => {}} />);

      // Input has label
      expect(screen.getByLabelText('Enter page URL')).toBeInTheDocument();
    });

    /**
     * AC-002: Tabs follow ARIA pattern
     */
    it('tabs should follow ARIA tab pattern', () => {
      render(<PlatformTabs activeTab="facebook" onTabChange={() => {}} />);

      const tablist = screen.getByRole('tablist');
      expect(tablist).toBeInTheDocument();

      const tabs = screen.getAllByRole('tab');
      expect(tabs).toHaveLength(3);

      // Each tab has aria-selected
      tabs.forEach((tab) => {
        expect(tab).toHaveAttribute('aria-selected');
      });
    });

    /**
     * AC-003: Images have alt text
     */
    it('preview images should have alt text', () => {
      const data: PlatformPreview = {
        title: 'Title',
        description: 'Desc',
        image: 'https://example.com/img.jpg',
        domain: 'example.com',
      };

      render(<PreviewCard platform="facebook" data={data} />);

      const img = screen.getByRole('img');
      expect(img).toHaveAttribute('alt');
    });

    /**
     * AC-004: Error messages are announced
     */
    it('error messages should have role="alert"', () => {
      render(<SocialMediaPreviewResults data={null} error="Test error" />);

      const error = screen.getByRole('alert');
      expect(error).toBeInTheDocument();
    });

    /**
     * AC-005: Interactive elements are focusable
     */
    it('interactive elements should be focusable', () => {
      render(<SocialMediaPreviewForm onAnalyze={() => {}} />);

      const input = screen.getByLabelText('Enter page URL');
      const button = screen.getByTestId('analyze-button');

      input.focus();
      expect(document.activeElement).toBe(input);

      button.focus();
      expect(document.activeElement).toBe(button);
    });
  });

  // ===========================================================================
  // EDGE CASE TESTS (EC)
  // ===========================================================================

  describe('Edge Cases', () => {
    /**
     * EC-001: Very long URL
     */
    it('should handle very long URLs', async () => {
      const mockOnAnalyze = jest.fn();
      render(<SocialMediaPreviewForm onAnalyze={mockOnAnalyze} />);

      const longUrl = 'https://example.com/' + 'a'.repeat(2000);
      const input = screen.getByLabelText('Enter page URL');
      await userEvent.type(input, longUrl);

      // Should still allow input
      expect(input).toHaveValue(longUrl);
    });

    /**
     * EC-003: Special characters in preview data
     */
    it('should render special characters in preview data', () => {
      const data: PlatformPreview = {
        title: 'Title with <script>alert(1)</script>',
        description: 'Desc & "quotes" & <tags>',
        image: null,
      };

      render(<PreviewCard platform="facebook" data={data} />);

      // Should render text, not execute script
      expect(screen.getByTestId('preview-title').textContent).toContain('Title');
      expect(screen.getByTestId('preview-title').textContent).not.toContain('<script>');
    });

    /**
     * EC-004: Empty preview data
     */
    it('should handle empty preview data gracefully', () => {
      const emptyData: PlatformPreview = {
        title: null,
        description: null,
        image: null,
      };

      render(<PreviewCard platform="twitter" data={emptyData} />);

      expect(screen.getByTestId('preview-title')).toHaveTextContent('No title');
      expect(screen.getByTestId('preview-description')).toHaveTextContent('No description');
    });

    /**
     * EC-005: Many missing tags
     */
    it('should display many missing tags', () => {
      const manyMissing = Array.from({ length: 20 }, (_, i) => `tag-${i}`);
      render(<MissingTagsList missing={manyMissing} />);

      expect(screen.getAllByRole('listitem')).toHaveLength(20);
    });

    /**
     * EC-006: Many warnings
     */
    it('should display many warnings', () => {
      const manyWarnings = Array.from({ length: 15 }, (_, i) => ({
        field: `field-${i}`,
        message: `Warning ${i}`,
      }));
      render(<WarningsList warnings={manyWarnings} />);

      expect(screen.getAllByRole('listitem')).toHaveLength(15);
    });
  });
});
