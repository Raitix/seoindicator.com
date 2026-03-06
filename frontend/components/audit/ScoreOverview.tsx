/**
 * ScoreOverview Component
 * TASK-33-9: Create ScoreOverview Component
 *
 * Hero visual for public audit pages with circular score indicator.
 * Displays a large circular progress indicator with color coding and status text.
 */

import React from 'react';
import Link from 'next/link';
import { Globe, Calendar, ExternalLink } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

// Types
interface ScoreOverviewProps {
  score: number;
  siteUrl: string;
  auditDate: string;
  className?: string;
  animate?: boolean;
  showCta?: boolean;
  size?: 'sm' | 'md' | 'lg';
}

// Color mapping based on score range
type ScoreColorInfo = {
  color: string;
  hex: string;
  status: string;
};

const getScoreColor = (score: number): ScoreColorInfo => {
  if (score >= 90) return { color: 'green', hex: '#22c55e', status: 'Excellent' };
  if (score >= 70) return { color: 'blue', hex: '#3b82f6', status: 'Good' };
  if (score >= 50) return { color: 'yellow', hex: '#eab308', status: 'Needs Work' };
  if (score >= 30) return { color: 'orange', hex: '#f97316', status: 'Poor' };
  return { color: 'red', hex: '#ef4444', status: 'Critical Issues' };
};

// Size mapping
type SizeConfig = {
  width: number;
  height: number;
  fontSize: string;
};

const sizeConfigs: Record<'sm' | 'md' | 'lg', SizeConfig> = {
  sm: { width: 120, height: 120, fontSize: 'text-2xl' },
  md: { width: 180, height: 180, fontSize: 'text-4xl' },
  lg: { width: 240, height: 240, fontSize: 'text-5xl' },
};

/**
 * ScoreOverview Component
 *
 * Displays a circular progress indicator with color-coded score and status text.
 * Used as the hero visual for public audit pages.
 *
 * @param score - SEO score (0-100, clamped automatically)
 * @param siteUrl - URL of the audited site
 * @param auditDate - Date of the audit
 * @param className - Optional CSS classes
 * @param animate - Enable animation (default: true)
 * @param showCta - Show CTA button (default: true)
 * @param size - Component size: 'sm' | 'md' | 'lg' (default: 'md')
 */
const ScoreOverview: React.FC<ScoreOverviewProps> = ({
  score,
  siteUrl,
  auditDate,
  className,
  animate = true,
  showCta = true,
  size = 'md',
}) => {
  // Score clamping to valid range
  const clampedScore = Math.max(0, Math.min(100, score));

  // Get color info
  const { color, hex, status } = getScoreColor(clampedScore);

  // Size configuration
  const { width, height, fontSize } = sizeConfigs[size];

  // SVG circle calculations
  const radius = (Math.min(width, height) / 2) - 10;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (clampedScore / 100) * circumference;

  return (
    <Card data-testid="card" className="overflow-hidden">
      <CardContent data-testid="card-content" className="p-6">
        <div
          data-testid="score-overview"
          className={cn('flex flex-col items-center gap-4', className)}
        >
          {/* Circular Progress Indicator */}
          <div
            data-testid="circular-progress"
            data-score={clampedScore}
            className="relative flex items-center justify-center"
          >
            <svg
              width={width}
              height={height}
              viewBox={`0 0 ${width} ${height}`}
              data-testid="score-svg"
              role="img"
              aria-label={`SEO Score: ${clampedScore} out of 100`}
            >
              {/* Background circle (track) */}
              <circle
                cx={width / 2}
                cy={height / 2}
                r={radius}
                fill="none"
                stroke="currentColor"
                strokeWidth="8"
                className="text-gray-200 dark:text-gray-700"
                data-testid="background-circle"
              />
              {/* Progress circle */}
              <circle
                cx={width / 2}
                cy={height / 2}
                r={radius}
                fill="none"
                stroke={hex}
                strokeWidth="8"
                strokeLinecap="round"
                strokeDasharray={circumference}
                strokeDashoffset={strokeDashoffset}
                transform={`rotate(-90 ${width / 2} ${height / 2})`}
                className={animate ? 'transition-all duration-1000 ease-out' : ''}
                data-testid="progress-circle"
                data-color={color}
                data-animate={animate}
              />
            </svg>

            {/* Score Display */}
            <div
              data-testid="score-display"
              className={cn('absolute font-bold text-gray-900 dark:text-white', fontSize)}
            >
              <span data-testid="score-number">{clampedScore}</span>
            </div>
          </div>

          {/* Status Text */}
          <div data-testid="status-container" className="text-center">
            <span
              data-testid="status-text"
              data-status={status}
              style={{ color: hex }}
              className="text-lg font-semibold"
            >
              {status}
            </span>
          </div>

          {/* Site URL */}
          <div
            data-testid="site-url-container"
            className="flex items-center gap-2 text-gray-600 dark:text-gray-400"
          >
            <Globe data-testid="globe-icon" className="w-4 h-4" />
            <span
              data-testid="site-url"
              className="text-sm truncate max-w-[200px]"
              title={siteUrl}
            >
              {siteUrl}
            </span>
          </div>

          {/* Audit Date */}
          <div
            data-testid="audit-date-container"
            className="flex items-center gap-2 text-gray-600 dark:text-gray-400"
          >
            <Calendar data-testid="calendar-icon" className="w-4 h-4" />
            <span data-testid="audit-date" className="text-sm">
              {auditDate}
            </span>
          </div>

          {/* CTA Button */}
          {showCta && (
            <Link
              href="/audit"
              data-testid="cta-button"
              className={cn(
                'inline-flex items-center gap-2 px-4 py-2 rounded-lg',
                'bg-emerald-500 hover:bg-emerald-600 text-white',
                'font-medium text-sm transition-colors',
                'focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2'
              )}
            >
              Get your free audit
              <ExternalLink data-testid="external-link-icon" className="w-4 h-4" />
            </Link>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

export default ScoreOverview;
export type { ScoreOverviewProps };
