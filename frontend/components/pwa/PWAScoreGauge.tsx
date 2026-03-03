"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import type { ReadinessLevel } from "@/lib/types/pwa";

export interface PWAScoreGaugeProps {
  score: number;
  readinessLevel: ReadinessLevel;
  className?: string;
  animate?: boolean;
}

/**
 * Get color class based on score
 */
function getScoreColorClass(score: number): string {
  if (score >= 90) return "text-emerald-400";
  if (score >= 70) return "text-green-400";
  if (score >= 40) return "text-yellow-400";
  return "text-red-400";
}

/**
 * Get gauge stroke color based on score
 */
function getStrokeColor(score: number): string {
  if (score >= 90) return "#34d399"; // emerald-400
  if (score >= 70) return "#4ade80"; // green-400
  if (score >= 40) return "#facc15"; // yellow-400
  return "#f87171"; // red-400
}

/**
 * Get readiness level display text
 */
function getReadinessText(level: ReadinessLevel): string {
  switch (level) {
    case "full":
      return "Installable";
    case "enhanced":
      return "Enhanced";
    case "basic":
      return "Basic";
    case "none":
    default:
      return "Not Ready";
  }
}

/**
 * Get readiness level badge color class
 */
function getReadinessBadgeClass(level: ReadinessLevel): string {
  switch (level) {
    case "full":
      return "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
    case "enhanced":
      return "bg-green-500/10 text-green-400 border-green-500/20";
    case "basic":
      return "bg-yellow-500/10 text-yellow-400 border-yellow-500/20";
    case "none":
    default:
      return "bg-red-500/10 text-red-400 border-red-500/20";
  }
}

export function PWAScoreGauge({
  score,
  readinessLevel,
  className,
  animate = true,
}: PWAScoreGaugeProps) {
  // Clamp score to 0-100
  const clampedScore = Math.max(0, Math.min(100, score));
  const roundedScore = Math.round(clampedScore);
  
  // Calculate stroke properties
  const radius = 45;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (roundedScore / 100) * circumference;
  
  // Animation state
  const [animatedScore, setAnimatedScore] = React.useState(animate ? 0 : roundedScore);
  const [animatedOffset, setAnimatedOffset] = React.useState(
    animate ? circumference : strokeDashoffset
  );
  
  // Handle animation
  React.useEffect(() => {
    if (!animate) {
      setAnimatedScore(roundedScore);
      setAnimatedOffset(strokeDashoffset);
      return;
    }
    
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    
    if (prefersReducedMotion) {
      setAnimatedScore(roundedScore);
      setAnimatedOffset(strokeDashoffset);
      return;
    }
    
    // Animate over 1 second
    const duration = 1000;
    const startTime = performance.now();
    const startScore = animatedScore;
    const startOffset = animatedOffset;
    
    function animateFrame(currentTime: number) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Easing function (ease-out cubic)
      const eased = 1 - Math.pow(1 - progress, 3);
      
      setAnimatedScore(Math.round(startScore + (roundedScore - startScore) * eased));
      setAnimatedOffset(startOffset + (strokeDashoffset - startOffset) * eased);
      
      if (progress < 1) {
        requestAnimationFrame(animateFrame);
      }
    }
    
    requestAnimationFrame(animateFrame);
  }, [roundedScore, animate, strokeDashoffset]);
  
  const scoreColorClass = getScoreColorClass(roundedScore);
  const strokeColor = getStrokeColor(roundedScore);
  const readinessText = getReadinessText(readinessLevel);
  const readinessBadgeClass = getReadinessBadgeClass(readinessLevel);
  
  return (
    <div
      className={cn("flex flex-col items-center", className)}
      role="meter"
      aria-valuenow={roundedScore}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-label={`PWA Score: ${roundedScore} out of 100`}
    >
      {/* Circular Gauge */}
      <div className="relative w-32 h-32">
        <svg
          className="w-full h-full transform -rotate-90"
          viewBox="0 0 100 100"
          aria-hidden="true"
        >
          {/* Background circle */}
          <circle
            cx="50"
            cy="50"
            r={radius}
            fill="none"
            stroke="currentColor"
            strokeWidth="8"
            className="text-slate-700"
          />
          {/* Progress circle */}
          <circle
            cx="50"
            cy="50"
            r={radius}
            fill="none"
            stroke={strokeColor}
            strokeWidth="8"
            strokeLinecap="round"
            strokeDasharray={circumference}
            strokeDashoffset={animatedOffset}
            className="transition-all duration-100"
          />
        </svg>
        
        {/* Score display in center */}
        <div className="absolute inset-0 flex flex-col items-center justify-center">
          <span className={cn("text-3xl font-bold", scoreColorClass)}>
            {animatedScore}
          </span>
          <span className="text-xs text-slate-400">/ 100</span>
        </div>
      </div>
      
      {/* Label */}
      <span className="mt-2 text-sm font-medium text-slate-300">
        PWA Score
      </span>
      
      {/* Readiness Level Badge */}
      <span
        className={cn(
          "mt-2 px-3 py-1 text-xs font-medium rounded-full border",
          readinessBadgeClass
        )}
      >
        {readinessText}
      </span>
    </div>
  );
}

export function PWAScoreGaugeSkeleton() {
  return (
    <div className="flex flex-col items-center">
      <div className="relative w-32 h-32">
        <div className="w-full h-full rounded-full border-8 border-slate-700 animate-pulse" />
        <div className="absolute inset-0 flex flex-col items-center justify-center">
          <div className="h-8 w-12 animate-pulse rounded bg-slate-700" />
          <div className="mt-1 h-3 w-8 animate-pulse rounded bg-slate-700" />
        </div>
      </div>
      <div className="mt-2 h-4 w-16 animate-pulse rounded bg-slate-700" />
      <div className="mt-2 h-6 w-20 animate-pulse rounded bg-slate-700" />
    </div>
  );
}

export default PWAScoreGauge;
