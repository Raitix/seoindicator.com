/**
 * TASK-34-10: RecentMentionsList Component
 * 
 * Recent mentions list for the AI Visibility dashboard.
 * Displays brand mentions with platform badges, sentiment, and expandable context.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-10.md
 */

'use client';

import * as React from 'react';
import {
  ChevronRight,
  ChevronDown,
  ExternalLink,
  MessageSquare,
  ThumbsUp,
  ThumbsDown,
  Minus,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type {
  RecentMentionsListProps,
  RecentMention,
  SentimentType,
} from '@/lib/types/ai-visibility';

// ============================================
// Helper Functions
// ============================================

function formatDate(isoString: string): string {
  const date = new Date(isoString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function getSentimentIcon(sentiment: SentimentType) {
  switch (sentiment) {
    case 'positive':
      return <ThumbsUp className="h-3 w-3" data-testid="thumbs-up-icon" />;
    case 'negative':
      return <ThumbsDown className="h-3 w-3" data-testid="thumbs-down-icon" />;
    default:
      return <Minus className="h-3 w-3" data-testid="minus-icon" />;
  }
}

function getSentimentColor(sentiment: SentimentType): string {
  switch (sentiment) {
    case 'positive':
      return 'text-emerald-400';
    case 'negative':
      return 'text-red-400';
    case 'mixed':
      return 'text-yellow-400';
    default:
      return 'text-slate-400';
  }
}

function getSentimentBgColor(sentiment: SentimentType): string {
  switch (sentiment) {
    case 'positive':
      return 'bg-emerald-500/20';
    case 'negative':
      return 'bg-red-500/20';
    case 'mixed':
      return 'bg-yellow-500/20';
    default:
      return 'bg-slate-500/20';
  }
}

function getPlatformColor(platform: string): string {
  switch (platform) {
    case 'chatgpt':
      return 'bg-emerald-500/20 text-emerald-400';
    case 'perplexity':
      return 'bg-blue-500/20 text-blue-400';
    case 'claude':
      return 'bg-orange-500/20 text-orange-400';
    default:
      return 'bg-slate-500/20 text-slate-400';
  }
}

function truncateText(text: string, maxLength: number = 100): string {
  if (text.length <= maxLength) return text;
  return `${text.substring(0, maxLength)}...`;
}

// ============================================
// Mention Item Component
// ============================================

interface MentionItemProps {
  mention: RecentMention;
  isExpanded: boolean;
  onToggle: () => void;
  onClick?: () => void;
}

function MentionItem({ mention, isExpanded, onToggle, onClick }: MentionItemProps) {
  const sentimentColor = getSentimentColor(mention.sentiment);
  const sentimentBgColor = getSentimentBgColor(mention.sentiment);
  const platformColor = getPlatformColor(mention.platform);

  return (
    <li
      data-testid={`mention-item-${mention.id}`}
      data-expanded={isExpanded}
      className="rounded-lg bg-slate-700/50 p-4 transition-colors hover:bg-slate-700"
    >
      {/* Expand/Collapse Button */}
      <button
        data-testid={`expand-btn-${mention.id}`}
        onClick={onToggle}
        className="mb-2 flex items-center gap-2 text-slate-400 hover:text-white transition-colors"
        aria-expanded={isExpanded}
      >
        {isExpanded ? (
          <ChevronDown className="h-4 w-4" data-testid="chevron-down-icon" />
        ) : (
          <ChevronRight className="h-4 w-4" data-testid="chevron-icon" />
        )}
        <span className="text-xs font-medium uppercase tracking-wide">
          {isExpanded ? 'Collapse' : 'Expand'}
        </span>
      </button>

      {/* Brand Context */}
      <div
        data-testid={`context-${mention.id}`}
        className="text-sm text-slate-300 leading-relaxed"
      >
        {isExpanded ? mention.brandContext : truncateText(mention.brandContext)}
      </div>

      {/* Metadata Row */}
      <div
        data-testid={`meta-${mention.id}`}
        className="mt-3 flex flex-wrap items-center gap-3"
      >
        {/* Platform Badge */}
        <span
          data-testid={`platform-badge-${mention.id}`}
          data-platform={mention.platform}
          className={cn(
            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium capitalize',
            platformColor
          )}
        >
          {mention.platform}
        </span>

        {/* Sentiment Badge */}
        <span
          data-testid={`sentiment-badge-${mention.id}`}
          data-sentiment={mention.sentiment}
          data-color={mention.sentiment === 'positive' ? 'green' : mention.sentiment === 'negative' ? 'red' : mention.sentiment === 'mixed' ? 'yellow' : 'gray'}
          className={cn(
            'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium capitalize',
            sentimentBgColor,
            sentimentColor
          )}
        >
          {getSentimentIcon(mention.sentiment)}
          {mention.sentiment}
        </span>

        {/* Date */}
        <span
          data-testid={`date-${mention.id}`}
          className="text-xs text-slate-500"
        >
          {formatDate(mention.queriedAt)}
        </span>
      </div>

      {/* View Button (if onClick provided) */}
      {onClick && (
        <button
          data-testid={`view-btn-${mention.id}`}
          onClick={onClick}
          className="mt-3 flex items-center gap-1 text-xs text-blue-400 hover:text-blue-300 transition-colors"
        >
          <ExternalLink className="h-3 w-3" data-testid="external-link-icon" />
          View
        </button>
      )}
    </li>
  );
}

// ============================================
// Main Component
// ============================================

export function RecentMentionsList({
  mentions,
  maxVisible = 10,
  viewAllHref,
  onMentionClick,
  className,
}: RecentMentionsListProps) {
  const [expandedId, setExpandedId] = React.useState<string | null>(null);

  const visibleMentions = mentions.slice(0, maxVisible);

  const toggleExpand = (id: string) => {
    setExpandedId(expandedId === id ? null : id);
  };

  // Empty state
  if (mentions.length === 0) {
    return (
      <div
        data-testid="empty-mentions"
        className={cn('rounded-lg bg-slate-800 p-8 text-center', className)}
      >
        <MessageSquare
          className="mx-auto h-12 w-12 text-slate-500"
          data-testid="message-icon"
        />
        <p className="mt-4 text-slate-400">
          No mentions yet. Run a query to start tracking.
        </p>
      </div>
    );
  }

  return (
    <div data-testid="mentions-list" className={className}>
      {/* Header */}
      <div
        data-testid="mentions-header"
        className="mb-4 flex items-center justify-between"
      >
        <h3 className="text-lg font-semibold text-white">Recent Mentions</h3>
        <span
          data-testid="mentions-count"
          className="rounded-full bg-slate-700 px-2 py-1 text-xs font-medium text-slate-400"
        >
          {mentions.length}
        </span>
      </div>

      {/* Mentions List */}
      <ul
        data-testid="mentions-items"
        role="list"
        className="space-y-3"
      >
        {visibleMentions.map((mention) => (
          <MentionItem
            key={mention.id}
            mention={mention}
            isExpanded={expandedId === mention.id}
            onToggle={() => toggleExpand(mention.id)}
            onClick={onMentionClick ? () => onMentionClick(mention) : undefined}
          />
        ))}
      </ul>

      {/* View All Link */}
      {viewAllHref && mentions.length > maxVisible && (
        <a
          data-testid="view-all-link"
          href={viewAllHref}
          className="mt-4 flex items-center gap-2 text-sm text-blue-400 hover:text-blue-300 transition-colors"
        >
          View All ({mentions.length})
          <ChevronRight className="h-4 w-4" data-testid="chevron-icon" />
        </a>
      )}
    </div>
  );
}

export default RecentMentionsList;
