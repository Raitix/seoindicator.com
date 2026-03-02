/**
 * PricingFAQ Component Tests - TASK-26-20
 *
 * Tests for the FAQ accordion section that displays common questions
 * about credits, payments, and AI models.
 *
 * @see SPEC-026
 * @see TASK-26-20
 */

import { render, screen, within, fireEvent } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import PricingFAQ from "@/components/pricing/PricingFAQ";

// ==================== Mock Data ====================

const mockFAQs = [
  {
    id: "faq-1",
    question: "What are credits?",
    answer: "Credits are used to run SEO analyses. Each analysis costs credits based on the AI model used.",
  },
  {
    id: "faq-2",
    question: "Do credits expire?",
    answer: "No, purchased credits never expire. They remain in your account until used.",
  },
  {
    id: "faq-3",
    question: "Can I get a refund?",
    answer: "We offer a 30-day money-back guarantee for unused credits. Contact support for assistance.",
  },
  {
    id: "faq-4",
    question: "Which AI model should I use?",
    answer: "GPT-4o Mini is great for most tasks. Use Claude or Gemini for longer content or specific needs.",
  },
  {
    id: "faq-5",
    question: "How do I purchase credits?",
    answer: "Select a credit package above and click 'Buy Now'. You'll be redirected to our secure payment processor.",
  },
];

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== FAQ-001: Rendering ====================

describe("Rendering", () => {
  it("renders without crashing", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    expect(screen.getByTestId("pricing-faq")).toBeInTheDocument();
  });

  it("displays section heading", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    expect(screen.getByRole("heading", { level: 2 })).toHaveTextContent(/frequently asked|faq/i);
  });

  it("displays all FAQ questions", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    expect(screen.getByText("What are credits?")).toBeInTheDocument();
    expect(screen.getByText("Do credits expire?")).toBeInTheDocument();
    expect(screen.getByText("Can I get a refund?")).toBeInTheDocument();
    expect(screen.getByText("Which AI model should I use?")).toBeInTheDocument();
    expect(screen.getByText("How do I purchase credits?")).toBeInTheDocument();
  });

  it("displays accordion items for each FAQ", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    const accordionItems = screen.getAllByRole("button");
    expect(accordionItems.length).toBe(5);
  });
});

// ==================== FAQ-002: Collapsed State ====================

describe("Collapsed State (Default)", () => {
  it("all answers are hidden by default", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    // Answers should not be visible initially
    const answer1 = screen.getByText(/Credits are used to run SEO analyses/);
    const answer2 = screen.getByText(/purchased credits never expire/);

    expect(answer1).not.toBeVisible();
    expect(answer2).not.toBeVisible();
  });

  it("expand indicators show correct state", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    const buttons = screen.getAllByRole("button");
    buttons.forEach((button) => {
      expect(button).toHaveAttribute("aria-expanded", "false");
    });
  });
});

// ==================== FAQ-003: Expand/Collapse ====================

describe("Expand and Collapse", () => {
  it("expands answer when question clicked", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    const question1 = screen.getByText("What are credits?");
    await user.click(question1);

    // Answer should now be visible
    expect(screen.getByText(/Credits are used to run SEO analyses/)).toBeVisible();
  });

  it("sets aria-expanded to true when expanded", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    const button = screen.getByRole("button", { name: /what are credits/i });
    await user.click(button);

    expect(button).toHaveAttribute("aria-expanded", "true");
  });

  it("collapses answer when clicked again", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    const question1 = screen.getByText("What are credits?");
    
    // Expand
    await user.click(question1);
    expect(screen.getByText(/Credits are used to run SEO analyses/)).toBeVisible();

    // Collapse
    await user.click(question1);
    expect(screen.getByText(/Credits are used to run SEO analyses/)).not.toBeVisible();
  });

  it("sets aria-expanded to false when collapsed", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    const button = screen.getByRole("button", { name: /what are credits/i });
    
    // Expand
    await user.click(button);
    expect(button).toHaveAttribute("aria-expanded", "true");
    
    // Collapse
    await user.click(button);
    expect(button).toHaveAttribute("aria-expanded", "false");
  });
});

// ==================== FAQ-004: Single Expansion ====================

describe("Single Expansion Mode", () => {
  it("collapses previous when new question expanded", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    // Expand first question
    const question1 = screen.getByText("What are credits?");
    await user.click(question1);

    // Expand second question
    const question2 = screen.getByText("Do credits expire?");
    await user.click(question2);

    // First answer should be hidden, second visible
    expect(screen.getByText(/Credits are used to run SEO analyses/)).not.toBeVisible();
    expect(screen.getByText(/purchased credits never expire/)).toBeVisible();
  });

  it("updates aria-expanded for both buttons", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    const button1 = screen.getByRole("button", { name: /what are credits/i });
    const button2 = screen.getByRole("button", { name: /do credits expire/i });

    // Expand first
    await user.click(button1);
    expect(button1).toHaveAttribute("aria-expanded", "true");
    expect(button2).toHaveAttribute("aria-expanded", "false");

    // Expand second
    await user.click(button2);
    expect(button1).toHaveAttribute("aria-expanded", "false");
    expect(button2).toHaveAttribute("aria-expanded", "true");
  });
});

// ==================== FAQ-005: Animation ====================

describe("Expand/Collapse Animation", () => {
  it("has animation class on accordion content", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    const accordionContents = document.querySelectorAll('[data-state]');
    accordionContents.forEach((content) => {
      expect(content.className).toMatch(/transition|animate|duration/);
    });
  });

  it("animation duration is reasonable", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    // Animation should complete quickly
    const animatedElements = document.querySelectorAll('[class*="duration"]');
    animatedElements.forEach((el) => {
      const duration = el.className.match(/duration-(\d+)/);
      if (duration) {
        const ms = parseInt(duration[1]);
        expect(ms).toBeLessThanOrEqual(300); // Max 300ms
      }
    });
  });
});

// ==================== FAQ-006: Keyboard Navigation ====================

describe("Keyboard Navigation", () => {
  it("can expand with Enter key", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    const button = screen.getByRole("button", { name: /what are credits/i });
    button.focus();

    await user.keyboard("{Enter}");

    expect(button).toHaveAttribute("aria-expanded", "true");
  });

  it("can expand with Space key", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    const button = screen.getByRole("button", { name: /what are credits/i });
    button.focus();

    await user.keyboard(" ");

    expect(button).toHaveAttribute("aria-expanded", "true");
  });

  it("can navigate between questions with Tab", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    // Tab through all questions
    await user.tab();

    const button1 = screen.getByRole("button", { name: /what are credits/i });
    expect(button1).toHaveFocus();

    await user.tab();

    const button2 = screen.getByRole("button", { name: /do credits expire/i });
    expect(button2).toHaveFocus();
  });
});

// ==================== FAQ-007: ARIA Attributes ====================

describe("ARIA Attributes", () => {
  it("has aria-expanded on each button", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    const buttons = screen.getAllByRole("button");
    buttons.forEach((button) => {
      expect(button).toHaveAttribute("aria-expanded");
    });
  });

  it("has aria-controls linking to content", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    const buttons = screen.getAllByRole("button");
    buttons.forEach((button) => {
      const controlsId = button.getAttribute("aria-controls");
      expect(controlsId).toBeTruthy();
      
      // Find the controlled element
      const controlled = document.getElementById(controlsId!);
      expect(controlled).toBeInTheDocument();
    });
  });

  it("has correct role on accordion", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    // Radix accordion uses specific roles
    const accordion = screen.getByTestId("pricing-faq");
    expect(accordion).toBeInTheDocument();
  });
});

// ==================== FAQ-008: Empty State ====================

describe("Empty State", () => {
  it("shows empty message when no FAQs provided", () => {
    render(<PricingFAQ faqs={[]} />);

    expect(screen.getByText(/no frequently asked questions/i)).toBeInTheDocument();
  });

  it("does not render accordion when empty", () => {
    render(<PricingFAQ faqs={[]} />);

    expect(screen.queryByRole("button")).not.toBeInTheDocument();
  });
});

// ==================== FAQ-009: Long Content ====================

describe("Long Content Handling", () => {
  it("handles long questions gracefully", () => {
    const longFAQs = [
      {
        id: "faq-long",
        question: "This is a very long question that might wrap to multiple lines on smaller screens and we need to ensure it displays correctly?",
        answer: "Answer here",
      },
    ];

    render(<PricingFAQ faqs={longFAQs} />);

    const button = screen.getByRole("button");
    expect(button).toHaveTextContent(/This is a very long question/);
  });

  it("handles long answers gracefully", () => {
    const longAnswerFAQs = [
      {
        id: "faq-long-answer",
        question: "Short question?",
        answer: "This is a very long answer that contains multiple paragraphs and detailed information about the topic. It might wrap and we need to ensure it displays correctly when expanded. The user should be able to read all the content without issues. This tests the expand/collapse functionality with substantial content.",
      },
    ];

    render(<PricingFAQ faqs={longAnswerFAQs} />);

    const button = screen.getByRole("button");
    fireEvent.click(button);

    expect(screen.getByText(/This is a very long answer/)).toBeVisible();
  });
});

// ==================== FAQ-010: Accessibility ====================

describe("Accessibility", () => {
  it("has proper heading level", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    const heading = screen.getByRole("heading", { level: 2 });
    expect(heading).toBeInTheDocument();
  });

  it("focus indicator is visible", () => {
    render(<PricingFAQ faqs={mockFAQs} />);

    const button = screen.getByRole("button", { name: /what are credits/i });
    button.focus();

    expect(button).toHaveFocus();
    expect(button.className).toMatch(/focus|ring/);
  });

  it("supports screen reader announcements", async () => {
    const user = userEvent.setup();

    render(<PricingFAQ faqs={mockFAQs} />);

    const button = screen.getByRole("button", { name: /what are credits/i });
    await user.click(button);

    // aria-expanded should announce state change
    expect(button).toHaveAttribute("aria-expanded", "true");
  });
});

// ==================== FAQ-011: Security ====================

describe("Security", () => {
  it("sanitizes XSS in question text", () => {
    const xssFAQs = [
      {
        id: "faq-xss",
        question: '<script>alert("XSS")</script>What are credits?',
        answer: "Answer",
      },
    ];

    render(<PricingFAQ faqs={xssFAQs} />);

    // Script tag should not be in DOM as executable
    const scripts = document.querySelectorAll('script:not([src])');
    scripts.forEach((script) => {
      expect(script.textContent).not.toContain("alert");
    });
  });

  it("sanitizes XSS in answer text", () => {
    const xssFAQs = [
      {
        id: "faq-xss-answer",
        question: "Question?",
        answer: '<img src="x" onerror="alert(1)">Answer text',
      },
    ];

    render(<PricingFAQ faqs={xssFAQs} />);

    // Expand to show answer
    const button = screen.getByRole("button");
    fireEvent.click(button);

    // No img with onerror should exist
    const imgs = document.querySelectorAll('img[onerror*="alert"]');
    expect(imgs).toHaveLength(0);
  });
});

// ==================== FAQ-012: Performance ====================

describe("Performance", () => {
  it("renders quickly with many FAQs", () => {
    const manyFAQs = Array.from({ length: 20 }, (_, i) => ({
      id: `faq-${i}`,
      question: `Question ${i}?`,
      answer: `Answer ${i}`,
    }));

    const startTime = performance.now();
    render(<PricingFAQ faqs={manyFAQs} />);
    const renderTime = performance.now() - startTime;

    expect(renderTime).toBeLessThan(100);
  });
});
