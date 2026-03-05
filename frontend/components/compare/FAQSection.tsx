/**
 * FAQSection Component
 * Task: TASK-32-10 - FAQ Section with Accordion
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Displays FAQs in an accordion format.
 */

'use client';

import React, { useState } from 'react';
import type { FAQ } from '@/lib/compare/types';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';

export interface FAQSectionProps {
  faqs: FAQ[];
  className?: string;
}

export function FAQSection({ faqs, className }: FAQSectionProps) {
  if (!faqs || faqs.length === 0) {
    return (
      <div
        className={`text-slate-400 text-center py-8 ${className || ''}`}
        data-testid="empty-faqs"
      >
        No frequently asked questions available.
      </div>
    );
  }

  return (
    <div
      className={className}
      data-testid="faq-section"
      data-count={faqs.length}
    >
      <Accordion type="single" collapsible className="w-full space-y-2">
        {faqs.map((faq, index) => (
          <AccordionItem
            key={index}
            value={`faq-${index}`}
            className="border border-slate-700 rounded-lg px-4 bg-slate-900/30"
            data-testid={`faq-${index}`}
          >
            <AccordionTrigger className="text-left text-white hover:text-emerald-400 hover:no-underline">
              <span className="font-medium">{faq.question}</span>
            </AccordionTrigger>
            <AccordionContent className="text-slate-300">
              {faq.answer}
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>
    </div>
  );
}

export default FAQSection;
