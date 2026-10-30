/**
 * SERPFeatureIcon Component Tests
 *
 * Tests for SERP feature icon rendering, accessibility, and edge cases.
 *
 * @see TASK-7-16
 * @see TESTS/SPEC-7-16.md
 */

import React from "react";
import { render, screen } from "@testing-library/react";

import {
  SERPFeatureIcon,
  SERP_FEATURE_NAMES,
  type SERPFeatureIconProps,
} from "@/components/serp-features/SERPFeatureIcon";
import type { SERPFeatureType } from "@/lib/types/keywords";

// ============================================================================
// Mock Data
// ============================================================================

const VALID_FEATURE_TYPES: SERPFeatureType[] = [
  "featured_snippet",
  "local_pack",
  "image_pack",
  "video",
  "people_also_ask",
  "knowledge_panel",
  "sitelinks",
  "reviews",
  "faq",
  "product",
];

// ============================================================================
// Rendering Tests (IC-001 to IC-010)
// ============================================================================

describe("SERPFeatureIcon - Rendering", () => {
  // IC-001: Featured snippet icon
  it("IC-001: renders featured_snippet icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "Featured Snippet");
    // SVG uses <title> child element for accessibility, not title attribute
    const titleElement = svg?.querySelector("title");
    expect(titleElement).toBeInTheDocument();
    expect(titleElement).toHaveTextContent("Featured Snippet");
  });

  // IC-002: Local pack icon
  it("IC-002: renders local_pack icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="local_pack" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "Local Pack");
  });

  // IC-003: Image pack icon
  it("IC-003: renders image_pack icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="image_pack" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "Image Pack");
  });

  // IC-004: Video icon
  it("IC-004: renders video icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="video" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "Video Result");
  });

  // IC-005: People also ask icon
  it("IC-005: renders people_also_ask icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="people_also_ask" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "People Also Ask");
  });

  // IC-006: Knowledge panel icon
  it("IC-006: renders knowledge_panel icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="knowledge_panel" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "Knowledge Panel");
  });

  // IC-007: Sitelinks icon
  it("IC-007: renders sitelinks icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="sitelinks" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "Sitelinks");
  });

  // IC-008: Reviews icon
  it("IC-008: renders reviews icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="reviews" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "Reviews/Ratings");
  });

  // IC-009: FAQ icon
  it("IC-009: renders faq icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="faq" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "FAQ Rich Result");
  });

  // IC-010: Product icon
  it("IC-010: renders product icon correctly", () => {
    const { container } = render(<SERPFeatureIcon feature="product" />);
    const svg = container.querySelector("svg");

    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute("aria-label", "Product Listing");
  });
});

// ============================================================================
// Invalid Input Tests (IC-011 to IC-013)
// ============================================================================

describe("SERPFeatureIcon - Invalid Input", () => {
  // IC-011: Invalid feature type - TypeScript prevents this at compile time
  // but we test runtime behavior with type assertion
  it("IC-011: renders fallback for invalid feature type", () => {
    const { container } = render(
      <SERPFeatureIcon feature={"invalid_type" as SERPFeatureType} />
    );
    const svg = container.querySelector("svg");

    // Should render fallback icon
    expect(svg).toBeInTheDocument();
  });

  // IC-012: Missing feature prop - TypeScript prevents this
  // Skip test as TypeScript enforces required prop

  // IC-013: Null feature - TypeScript prevents this
  // Skip test as TypeScript enforces non-null
});

// ============================================================================
// Accessibility Tests (IC-014, IC-015)
// ============================================================================

describe("SERPFeatureIcon - Accessibility", () => {
  // IC-014: Has aria-label
  it("IC-014: has aria-label attribute", () => {
    VALID_FEATURE_TYPES.forEach((feature) => {
      const { container } = render(<SERPFeatureIcon feature={feature} />);
      const svg = container.querySelector("svg");

      expect(svg).toHaveAttribute("aria-label");
      expect(svg?.getAttribute("aria-label")?.length).toBeGreaterThan(0);
    });
  });

  // IC-015: Has title element for tooltip (SVG uses <title> child element)
  it("IC-015: has title element for tooltip", () => {
    VALID_FEATURE_TYPES.forEach((feature) => {
      const { container } = render(<SERPFeatureIcon feature={feature} />);
      const svg = container.querySelector("svg");
      const titleElement = svg?.querySelector("title");

      expect(titleElement).toBeInTheDocument();
      expect(titleElement?.textContent?.length).toBeGreaterThan(0);
    });
  });

  // Additional: aria-label matches title element content
  it("aria-label matches title element content", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" />);
    const svg = container.querySelector("svg");
    const titleElement = svg?.querySelector("title");

    expect(svg?.getAttribute("aria-label")).toBe(titleElement?.textContent);
  });

  // Additional: has role="img"
  it("has role=img for accessibility", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" />);
    const svg = container.querySelector("svg");

    expect(svg).toHaveAttribute("role", "img");
  });
});

// ============================================================================
// Size Variants Tests
// ============================================================================

describe("SERPFeatureIcon - Size Variants", () => {
  it("applies sm size class (w-4 h-4)", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" size="sm" />);
    const svg = container.querySelector("svg");

    expect(svg).toHaveClass("w-4");
    expect(svg).toHaveClass("h-4");
  });

  it("applies md size class (w-5 h-5) as default", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" />);
    const svg = container.querySelector("svg");

    expect(svg).toHaveClass("w-5");
    expect(svg).toHaveClass("h-5");
  });

  it("applies lg size class (w-6 h-6)", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" size="lg" />);
    const svg = container.querySelector("svg");

    expect(svg).toHaveClass("w-6");
    expect(svg).toHaveClass("h-6");
  });
});

// ============================================================================
// Show Background Tests
// ============================================================================

describe("SERPFeatureIcon - Show Background", () => {
  it("renders with background when showBackground=true", () => {
    const { container } = render(
      <SERPFeatureIcon feature="featured_snippet" showBackground />
    );

    const wrapper = container.querySelector("span");

    expect(wrapper).toBeInTheDocument();
    expect(wrapper).toHaveClass("inline-flex");
    expect(wrapper).toHaveClass("rounded-full");
    expect(wrapper).toHaveAttribute("role", "img");
  });

  it("applies correct wrapper size for sm", () => {
    const { container } = render(
      <SERPFeatureIcon feature="featured_snippet" size="sm" showBackground />
    );

    const wrapper = container.querySelector("span");

    expect(wrapper).toHaveClass("w-6");
    expect(wrapper).toHaveClass("h-6");
  });

  it("applies correct wrapper size for md", () => {
    const { container } = render(
      <SERPFeatureIcon feature="featured_snippet" size="md" showBackground />
    );

    const wrapper = container.querySelector("span");

    expect(wrapper).toHaveClass("w-8");
    expect(wrapper).toHaveClass("h-8");
  });

  it("applies correct wrapper size for lg", () => {
    const { container } = render(
      <SERPFeatureIcon feature="featured_snippet" size="lg" showBackground />
    );

    const wrapper = container.querySelector("span");

    expect(wrapper).toHaveClass("w-10");
    expect(wrapper).toHaveClass("h-10");
  });
});

// ============================================================================
// Custom ClassName Tests
// ============================================================================

describe("SERPFeatureIcon - Custom ClassName", () => {
  it("applies custom className to SVG", () => {
    const { container } = render(
      <SERPFeatureIcon feature="featured_snippet" className="custom-class" />
    );
    const svg = container.querySelector("svg");

    expect(svg).toHaveClass("custom-class");
  });

  it("merges custom className with size class", () => {
    const { container } = render(
      <SERPFeatureIcon
        feature="featured_snippet"
        size="lg"
        className="custom-class"
      />
    );
    const svg = container.querySelector("svg");

    expect(svg).toHaveClass("w-6");
    expect(svg).toHaveClass("h-6");
    expect(svg).toHaveClass("custom-class");
  });

  it("applies custom className to wrapper when showBackground=true", () => {
    const { container } = render(
      <SERPFeatureIcon
        feature="featured_snippet"
        showBackground
        className="custom-wrapper-class"
      />
    );
    const wrapper = container.querySelector("span");

    expect(wrapper).toHaveClass("custom-wrapper-class");
  });
});

// ============================================================================
// SERP_FEATURE_NAMES Export Tests
// ============================================================================

describe("SERP_FEATURE_NAMES", () => {
  it("contains all feature types", () => {
    VALID_FEATURE_TYPES.forEach((feature) => {
      expect(SERP_FEATURE_NAMES[feature]).toBeDefined();
    });
  });

  it("has readable display names", () => {
    expect(SERP_FEATURE_NAMES.featured_snippet).toBe("Featured Snippet");
    expect(SERP_FEATURE_NAMES.local_pack).toBe("Local Pack");
    expect(SERP_FEATURE_NAMES.people_also_ask).toBe("People Also Ask");
  });
});

// ============================================================================
// Reusability Tests (CR-001 to CR-004)
// ============================================================================

describe("SERPFeatureIcon - Reusability", () => {
  // CR-001: Use in keyword table
  it("CR-001: renders in table cell context", () => {
    const { container } = render(
      <table>
        <tbody>
          <tr>
            <td>
              <SERPFeatureIcon feature="featured_snippet" />
            </td>
          </tr>
        </tbody>
      </table>
    );

    expect(container.querySelector("svg")).toBeInTheDocument();
  });

  // CR-002: Use in modal
  it("CR-002: renders in modal context", () => {
    const { container } = render(
      <div role="dialog">
        <SERPFeatureIcon feature="local_pack" />
      </div>
    );

    expect(container.querySelector("svg")).toBeInTheDocument();
  });

  // CR-003: Use in report
  it("CR-003: renders in report context", () => {
    const { container } = render(
      <div className="report">
        <SERPFeatureIcon feature="reviews" />
      </div>
    );

    expect(container.querySelector("svg")).toBeInTheDocument();
  });

  // CR-004: Custom className prop
  it("CR-004: applies custom className prop", () => {
    const { container } = render(
      <SERPFeatureIcon feature="sitelinks" className="my-custom-class" />
    );

    expect(container.querySelector("svg")).toHaveClass("my-custom-class");
  });
});

// ============================================================================
// SVG Structure Tests
// ============================================================================

describe("SERPFeatureIcon - SVG Structure", () => {
  it("has viewBox attribute", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" />);
    const svg = container.querySelector("svg");

    expect(svg).toHaveAttribute("viewBox", "0 0 24 24");
  });

  it("uses currentColor for stroke", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" />);
    const svg = container.querySelector("svg");

    expect(svg).toHaveAttribute("stroke", "currentColor");
  });

  it("has proper stroke attributes", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" />);
    const svg = container.querySelector("svg");

    expect(svg).toHaveAttribute("stroke-width", "2");
    expect(svg).toHaveAttribute("stroke-linecap", "round");
    expect(svg).toHaveAttribute("stroke-linejoin", "round");
  });

  it("has aria-hidden on inner path elements", () => {
    const { container } = render(<SERPFeatureIcon feature="featured_snippet" />);
    const paths = container.querySelectorAll("path");

    paths.forEach((path) => {
      expect(path).toHaveAttribute("aria-hidden", "true");
    });
  });
});
