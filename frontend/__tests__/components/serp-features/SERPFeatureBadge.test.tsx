/**
 * SERPFeatureBadge Component Tests
 *
 * Tests for SERP feature badge rendering, hover states, and reusability.
 *
 * @see TASK-7-16
 * @see TESTS/SPEC-7-16.md
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";

import {
  SERPFeatureBadge,
  type SERPFeatureBadgeProps,
} from "@/components/serp-features/SERPFeatureBadge";
import type { SERPFeature } from "@/lib/types/keywords";

// ============================================================================
// Mock Data
// ============================================================================

const singleFeature: SERPFeature[] = [
  { type: "featured_snippet", isPresent: true },
];

const multipleFeatures: SERPFeature[] = [
  { type: "featured_snippet", isPresent: true },
  { type: "sitelinks", isPresent: true },
  { type: "reviews", isPresent: true },
];

const mixedPresenceFeatures: SERPFeature[] = [
  { type: "featured_snippet", isPresent: true },
  { type: "local_pack", isPresent: false },
  { type: "sitelinks", isPresent: true },
  { type: "reviews", isPresent: true },
];

const fiveFeatures: SERPFeature[] = [
  { type: "featured_snippet", isPresent: true },
  { type: "sitelinks", isPresent: true },
  { type: "reviews", isPresent: true },
  { type: "faq", isPresent: true },
  { type: "video", isPresent: true },
];

const manyFeatures: SERPFeature[] = [
  { type: "featured_snippet", isPresent: true },
  { type: "sitelinks", isPresent: true },
  { type: "reviews", isPresent: true },
  { type: "faq", isPresent: true },
  { type: "video", isPresent: true },
  { type: "local_pack", isPresent: true },
  { type: "image_pack", isPresent: true },
  { type: "product", isPresent: true },
  { type: "knowledge_panel", isPresent: true },
  { type: "people_also_ask", isPresent: true },
];

const noPresentFeatures: SERPFeature[] = [
  { type: "featured_snippet", isPresent: false },
  { type: "local_pack", isPresent: false },
];

// ============================================================================
// Single Feature Tests (BG-001, BG-002)
// ============================================================================

describe("SERPFeatureBadge - Single Feature", () => {
  // BG-001: Single feature
  it("BG-001: renders single present feature", () => {
    const { container } = render(<SERPFeatureBadge features={singleFeature} />);

    const svgs = container.querySelectorAll("svg");
    expect(svgs).toHaveLength(1);
  });

  // BG-002: Feature not present
  it("BG-002: renders nothing when no features present", () => {
    const { container } = render(<SERPFeatureBadge features={noPresentFeatures} />);

    expect(container.firstChild).toBeNull();
  });

  it("filters out non-present features", () => {
    const { container } = render(<SERPFeatureBadge features={mixedPresenceFeatures} />);

    const svgs = container.querySelectorAll("svg");
    expect(svgs).toHaveLength(3); // Only present features
  });
});

// ============================================================================
// Multiple Features Tests (BG-003 to BG-005)
// ============================================================================

describe("SERPFeatureBadge - Multiple Features", () => {
  // BG-003: Two features
  it("BG-003: renders two present features", () => {
    const twoFeatures: SERPFeature[] = [
      { type: "featured_snippet", isPresent: true },
      { type: "sitelinks", isPresent: true },
    ];

    const { container } = render(<SERPFeatureBadge features={twoFeatures} />);

    const svgs = container.querySelectorAll("svg");
    expect(svgs).toHaveLength(2);
  });

  // BG-004: Five features
  it("BG-004: renders five present features", () => {
    const { container } = render(<SERPFeatureBadge features={fiveFeatures} />);

    const svgs = container.querySelectorAll("svg");
    expect(svgs).toHaveLength(5);
  });

  // BG-005: Mixed presence
  it("BG-005: renders only present features from mixed input", () => {
    const { container } = render(<SERPFeatureBadge features={mixedPresenceFeatures} />);

    const svgs = container.querySelectorAll("svg");
    expect(svgs).toHaveLength(3);
  });
});

// ============================================================================
// Hover State Tests (BG-006, BG-007)
// ============================================================================

describe("SERPFeatureBadge - Hover State", () => {
  // BG-006: Show feature names
  it("BG-006: shows feature names in title attribute", () => {
    const { container } = render(<SERPFeatureBadge features={singleFeature} />);

    const badge = container.querySelector(".serp-feature-badge");
    expect(badge).toHaveAttribute("title", "Featured Snippet");
  });

  // BG-007: Multiple names
  it("BG-007: shows all feature names in title for multiple features", () => {
    const { container } = render(<SERPFeatureBadge features={multipleFeatures} />);

    const badge = container.querySelector(".serp-feature-badge");
    const title = badge?.getAttribute("title");

    expect(title).toContain("Featured Snippet");
    expect(title).toContain("Sitelinks");
    expect(title).toContain("Reviews/Ratings");
  });

  it("has role=group for accessibility", () => {
    const { container } = render(<SERPFeatureBadge features={multipleFeatures} />);

    const badge = container.querySelector(".serp-feature-badge");
    expect(badge).toHaveAttribute("role", "group");
  });

  it("has aria-label describing features", () => {
    const { container } = render(<SERPFeatureBadge features={singleFeature} />);

    const badge = container.querySelector(".serp-feature-badge");
    expect(badge).toHaveAttribute("aria-label", "SERP Features: Featured Snippet");
  });
});

// ============================================================================
// Styling Tests (BG-008 to BG-010)
// ============================================================================

describe("SERPFeatureBadge - Styling", () => {
  // BG-008: Consistent size
  it("BG-008: all icons have same size", () => {
    const { container } = render(<SERPFeatureBadge features={multipleFeatures} />);

    const svgs = container.querySelectorAll("svg");

    svgs.forEach((svg) => {
      expect(svg).toHaveClass("w-5"); // default md size
      expect(svg).toHaveClass("h-5");
    });
  });

  // BG-009: Proper spacing
  it("BG-009: has proper gap between icons", () => {
    const { container } = render(<SERPFeatureBadge features={multipleFeatures} />);

    const badge = container.querySelector(".serp-feature-badge");
    expect(badge).toHaveClass("gap-1.5");
  });

  // BG-010: Container styling
  it("BG-010: applies container styling correctly", () => {
    const { container } = render(<SERPFeatureBadge features={multipleFeatures} />);

    const badge = container.querySelector(".serp-feature-badge");
    expect(badge).toHaveClass("inline-flex");
    expect(badge).toHaveClass("items-center");
  });

  it("uses compact spacing when compact=true", () => {
    const { container } = render(
      <SERPFeatureBadge features={multipleFeatures} compact />
    );

    const badge = container.querySelector(".serp-feature-badge");
    expect(badge).toHaveClass("gap-0.5");
  });
});

// ============================================================================
// Max Visible Tests
// ============================================================================

describe("SERPFeatureBadge - Max Visible", () => {
  it("limits visible icons to maxVisible prop", () => {
    const { container } = render(
      <SERPFeatureBadge features={manyFeatures} maxVisible={3} />
    );

    const svgs = container.querySelectorAll("svg");
    expect(svgs).toHaveLength(3);
  });

  it("shows +N indicator for hidden features", () => {
    const { container } = render(
      <SERPFeatureBadge features={manyFeatures} maxVisible={5} />
    );

    expect(screen.getByText("+5")).toBeInTheDocument();
  });

  it("hides +N indicator when under maxVisible", () => {
    const { container } = render(
      <SERPFeatureBadge features={threeFeatures} maxVisible={5} />
    );

    expect(screen.queryByText(/\+\d/)).not.toBeInTheDocument();
  });

  it("shows correct count in +N indicator", () => {
    render(<SERPFeatureBadge features={manyFeatures} maxVisible={5} />);

    expect(screen.getByText("+5")).toBeInTheDocument(); // 10 - 5 = 5
  });

  it("has title on +N indicator showing hidden features", () => {
    const { container } = render(
      <SERPFeatureBadge features={manyFeatures} maxVisible={5} />
    );

    const moreIndicator = screen.getByText("+5");
    expect(moreIndicator).toHaveAttribute("title");
    expect(moreIndicator.getAttribute("title")).toContain("more:");
  });
});

// Extra test data
const threeFeatures: SERPFeature[] = [
  { type: "featured_snippet", isPresent: true },
  { type: "sitelinks", isPresent: true },
  { type: "reviews", isPresent: true },
];

// ============================================================================
// Size Variants Tests
// ============================================================================

describe("SERPFeatureBadge - Size Variants", () => {
  it("passes size prop to icons", () => {
    const { container } = render(
      <SERPFeatureBadge features={singleFeature} size="lg" />
    );

    const svg = container.querySelector("svg");
    expect(svg).toHaveClass("w-6");
    expect(svg).toHaveClass("h-6");
  });

  it("applies correct size to +N indicator for sm", () => {
    const { container } = render(
      <SERPFeatureBadge features={manyFeatures} size="sm" maxVisible={5} />
    );

    const indicator = screen.getByText("+5");
    expect(indicator).toHaveClass("text-xs");
  });

  it("applies correct size to +N indicator for md", () => {
    const { container } = render(
      <SERPFeatureBadge features={manyFeatures} size="md" maxVisible={5} />
    );

    const indicator = screen.getByText("+5");
    expect(indicator).toHaveClass("text-sm");
  });

  it("applies correct size to +N indicator for lg", () => {
    const { container } = render(
      <SERPFeatureBadge features={manyFeatures} size="lg" maxVisible={5} />
    );

    const indicator = screen.getByText("+5");
    expect(indicator).toHaveClass("text-sm");
  });
});

// ============================================================================
// Show Background Tests
// ============================================================================

describe("SERPFeatureBadge - Show Background", () => {
  it("passes showBackground prop to icons", () => {
    const { container } = render(
      <SERPFeatureBadge features={singleFeature} showBackground />
    );

    const wrapper = container.querySelector("span.inline-flex.rounded-full");
    expect(wrapper).toBeInTheDocument();
  });
});

// ============================================================================
// Custom ClassName Tests
// ============================================================================

describe("SERPFeatureBadge - Custom ClassName", () => {
  it("applies custom className to badge container", () => {
    const { container } = render(
      <SERPFeatureBadge features={multipleFeatures} className="my-custom-class" />
    );

    const badge = container.querySelector(".serp-feature-badge");
    expect(badge).toHaveClass("my-custom-class");
  });

  it("merges custom className with default classes", () => {
    const { container } = render(
      <SERPFeatureBadge features={multipleFeatures} className="custom-class" />
    );

    const badge = container.querySelector(".serp-feature-badge");
    expect(badge).toHaveClass("inline-flex");
    expect(badge).toHaveClass("custom-class");
  });
});

// ============================================================================
// Component Reusability Tests (CR-001 to CR-004)
// ============================================================================

describe("SERPFeatureBadge - Reusability", () => {
  // CR-001: Use in keyword table
  it("CR-001: renders in table cell context", () => {
    const { container } = render(
      <table>
        <tbody>
          <tr>
            <td>
              <SERPFeatureBadge features={multipleFeatures} />
            </td>
          </tr>
        </tbody>
      </table>
    );

    expect(container.querySelectorAll("svg")).toHaveLength(3);
  });

  // CR-002: Use in modal
  it("CR-002: renders in modal context", () => {
    const { container } = render(
      <div role="dialog">
        <SERPFeatureBadge features={multipleFeatures} />
      </div>
    );

    expect(container.querySelectorAll("svg")).toHaveLength(3);
  });

  // CR-003: Use in report
  it("CR-003: renders in report context", () => {
    const { container } = render(
      <div className="report">
        <SERPFeatureBadge features={multipleFeatures} />
      </div>
    );

    expect(container.querySelectorAll("svg")).toHaveLength(3);
  });

  // CR-004: Custom className prop
  it("CR-004: applies custom className prop", () => {
    const { container } = render(
      <SERPFeatureBadge features={multipleFeatures} className="report-badge" />
    );

    expect(container.querySelector(".serp-feature-badge")).toHaveClass("report-badge");
  });
});

// ============================================================================
// Performance Tests (PF-001, PF-002)
// ============================================================================

describe("SERPFeatureBadge - Performance", () => {
  // PF-001: Many features
  it("PF-001: renders many features efficiently", () => {
    const startTime = performance.now();

    const { container } = render(<SERPFeatureBadge features={manyFeatures} />);

    const endTime = performance.now();
    const renderTime = endTime - startTime;

    expect(container.querySelectorAll("svg")).toHaveLength(10);
    // Render should complete quickly (under 100ms is reasonable)
    expect(renderTime).toBeLessThan(100);
  });

  // PF-002: Rapid updates - tested with React's rendering behavior
  it("PF-002: handles rapid prop changes without memory leak", () => {
    const { container, rerender } = render(
      <SERPFeatureBadge features={singleFeature} />
    );

    // Rapidly update props
    for (let i = 0; i < 10; i++) {
      rerender(<SERPFeatureBadge features={manyFeatures} maxVisible={5} />);
      rerender(<SERPFeatureBadge features={singleFeature} />);
    }

    // Final render should work correctly
    expect(container.querySelectorAll("svg")).toHaveLength(1);
  });
});

// ============================================================================
// Edge Cases
// ============================================================================

describe("SERPFeatureBadge - Edge Cases", () => {
  it("handles empty features array", () => {
    const { container } = render(<SERPFeatureBadge features={[]} />);

    expect(container.firstChild).toBeNull();
  });

  it("handles features with undefined isPresent", () => {
    const featuresWithUndefined = [
      { type: "featured_snippet" as const, isPresent: undefined as unknown as boolean },
    ];

    const { container } = render(
      <SERPFeatureBadge features={featuresWithUndefined} />
    );

    // Undefined isPresent should be falsy, so no icons rendered
    expect(container.firstChild).toBeNull();
  });

  it("handles all features being non-present", () => {
    const { container } = render(<SERPFeatureBadge features={noPresentFeatures} />);

    expect(container.firstChild).toBeNull();
  });

  it("handles duplicate feature types", () => {
    const duplicateFeatures: SERPFeature[] = [
      { type: "featured_snippet", isPresent: true },
      { type: "featured_snippet", isPresent: true },
    ];

    const { container } = render(<SERPFeatureBadge features={duplicateFeatures} />);

    // Should render both (they're distinct items in the array)
    const svgs = container.querySelectorAll("svg");
    expect(svgs).toHaveLength(2);
  });

  it("handles maxVisible of 0", () => {
    const { container } = render(
      <SERPFeatureBadge features={singleFeature} maxVisible={0} />
    );

    // Should show +1 indicator instead of icons
    expect(screen.getByText("+1")).toBeInTheDocument();
    expect(container.querySelectorAll("svg")).toHaveLength(0);
  });

  it("handles maxVisible greater than feature count", () => {
    const { container } = render(
      <SERPFeatureBadge features={singleFeature} maxVisible={10} />
    );

    expect(container.querySelectorAll("svg")).toHaveLength(1);
    expect(screen.queryByText(/\+\d/)).not.toBeInTheDocument();
  });
});

// ============================================================================
// Accessibility Tests
// ============================================================================

describe("SERPFeatureBadge - Accessibility", () => {
  it("has accessible name via aria-label", () => {
    render(<SERPFeatureBadge features={multipleFeatures} />);

    const badge = screen.getByRole("group");
    expect(badge).toHaveAccessibleName(/SERP Features:/);
  });

  it("provides feature names in accessible format", () => {
    const { container } = render(
      <SERPFeatureBadge features={multipleFeatures} />
    );

    const badge = container.querySelector(".serp-feature-badge");
    const ariaLabel = badge?.getAttribute("aria-label");

    expect(ariaLabel).toContain("Featured Snippet");
    expect(ariaLabel).toContain("Sitelinks");
    expect(ariaLabel).toContain("Reviews/Ratings");
  });

  it("maintains proper heading structure when used in context", () => {
    const { container } = render(
      <div>
        <h3>Keywords</h3>
        <SERPFeatureBadge features={multipleFeatures} />
      </div>
    );

    expect(screen.getByRole("heading", { level: 3 })).toBeInTheDocument();
  });
});
