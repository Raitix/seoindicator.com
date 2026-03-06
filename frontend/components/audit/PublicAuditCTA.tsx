"use client";

import * as React from "react";
import { Mail, Loader2, Check, AlertCircle, Twitter, Linkedin, Facebook, Copy } from "lucide-react";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { trackEvent } from "@/lib/analytics";

/**
 * PublicAuditCTA Component Props
 */
export interface PublicAuditCTAProps {
  auditHash: string;
  siteUrl: string;
  score: number;
  className?: string;
}

/**
 * Lead form data interface
 */
interface LeadFormData {
  email: string;
}

/**
 * Form state type
 */
type FormState = "idle" | "submitting" | "success" | "error";

/**
 * PublicAuditCTA Component
 * 
 * Conversion component for public audit pages.
 * Provides email capture for anonymous users and social sharing options.
 */
export function PublicAuditCTA({
  auditHash,
  siteUrl,
  score,
  className,
}: PublicAuditCTAProps) {
  // Form state
  const [email, setEmail] = React.useState("");
  const [validationError, setValidationError] = React.useState<string | null>(null);
  const [formState, setFormState] = React.useState<FormState>("idle");
  const [submitError, setSubmitError] = React.useState<string | null>(null);
  
  // Copy state for embed code
  const [copied, setCopied] = React.useState(false);

  /**
   * Validate email address
   */
  const validateEmail = React.useCallback((value: string): boolean => {
    const trimmed = value.trim();
    
    if (!trimmed) {
      setValidationError("Email is required");
      return false;
    }
    
    // Basic email regex pattern
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(trimmed)) {
      setValidationError("Invalid email format");
      return false;
    }
    
    if (trimmed.length > 255) {
      setValidationError("Email too long");
      return false;
    }
    
    setValidationError(null);
    return true;
  }, []);

  /**
   * Handle email input change
   */
  const handleEmailChange = React.useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setEmail(value);
    // Clear validation error on change if there was one
    if (validationError) {
      validateEmail(value);
    }
  }, [validationError, validateEmail]);

  /**
   * Handle email input blur
   */
  const handleEmailBlur = React.useCallback(() => {
    if (email) {
      validateEmail(email);
    }
  }, [email, validateEmail]);

  /**
   * Handle form submission
   */
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate email before submission
    if (!validateEmail(email)) {
      return;
    }
    
    setFormState("submitting");
    setSubmitError(null);
    
    try {
      const response = await fetch(`/api/v1/public-audit/${auditHash}/lead`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email.trim() }),
      });
      
      if (!response.ok) {
        // Handle specific error codes
        if (response.status === 429) {
          throw new Error("Too many requests, try later");
        }
        if (response.status === 404) {
          throw new Error("Audit not found");
        }
        throw new Error("Failed to submit");
      }
      
      // Success
      setFormState("success");
      trackEvent("public_audit_lead_submit", { hash: auditHash, score });
    } catch (error) {
      setFormState("error");
      setSubmitError(error instanceof Error ? error.message : "An error occurred");
    }
  };

  /**
   * Handle retry button click
   */
  const handleRetry = () => {
    setFormState("idle");
    setSubmitError(null);
  };

  /**
   * Handle social share button click
   */
  const handleShare = (platform: "twitter" | "linkedin" | "facebook") => {
    const shareUrl = `https://seoindicator.com/audit/?h=${auditHash}`;
    const shareText = `Check out this SEO score: ${score}/100 for ${siteUrl}`;
    
    let shareLink: string;
    
    switch (platform) {
      case "twitter":
        shareLink = `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(shareText)}`;
        break;
      case "linkedin":
        shareLink = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`;
        break;
      case "facebook":
        shareLink = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
        break;
    }
    
    window.open(shareLink, "_blank", "width=550,height=420");
    trackEvent("public_audit_share", { hash: auditHash, platform });
  };

  /**
   * Handle copy embed code
   */
  const handleCopyEmbed = async () => {
    const embedCode = getEmbedCode();
    
    try {
      await navigator.clipboard.writeText(embedCode);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
      trackEvent("public_audit_embed_copy", { hash: auditHash });
    } catch (error) {
      console.error("Failed to copy embed code:", error);
    }
  };

  /**
   * Generate embed code for badge
   */
  const getEmbedCode = (): string => {
    return `<a href="https://seoindicator.com/audit/?h=${auditHash}">
  <img src="https://seoindicator.com/badge/${auditHash}/small.svg" alt="SEO Score for ${siteUrl}: ${score}/100" />
</a>`;
  };

  /**
   * Render success state
   */
  if (formState === "success") {
    return (
      <div
        data-testid="success-state"
        role="status"
        aria-live="polite"
        className={cn(
          "rounded-lg border border-green-800 bg-green-900/20 p-6 text-center",
          className
        )}
      >
        <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-600/20">
          <Check className="h-6 w-6 text-green-500" data-testid="check-icon" />
        </div>
        <h3 className="text-lg font-semibold text-white">Thank you!</h3>
        <p className="mt-2 text-slate-300">Check your email for the full report.</p>
        <p className="mt-2 text-sm text-slate-400">
          Your SEO score: <span className="font-medium text-white">{score}/100</span>
        </p>
        <a
          href={`https://seoindicator.com/audit/?h=${auditHash}`}
          className="mt-4 inline-block text-emerald-400 hover:text-emerald-300 hover:underline"
        >
          View your audit
        </a>
      </div>
    );
  }

  const embedCode = getEmbedCode();

  return (
    <div
      data-testid="public-audit-cta"
      className={cn("space-y-6", className)}
    >
      {/* Email Capture Form */}
      <form
        onSubmit={handleSubmit}
        role="form"
        aria-label="Get your free audit"
        data-testid="lead-form"
        className="space-y-4"
      >
        <h2 className="text-xl font-semibold text-white">
          Get your free SEO audit for <span className="text-emerald-400">{siteUrl || "your site"}</span>
        </h2>
        
        <div className="space-y-2">
          <Label htmlFor="email" className="text-slate-300">
            Email address
          </Label>
          <Input
            id="email"
            type="email"
            name="email"
            value={email}
            onChange={handleEmailChange}
            onBlur={handleEmailBlur}
            placeholder="Enter your email"
            aria-invalid={!!validationError}
            aria-describedby={validationError ? "email-error" : undefined}
            disabled={formState === "submitting"}
            data-testid="email-input"
            className="border-slate-700 bg-slate-800/50 text-white placeholder:text-slate-500 focus-visible:ring-emerald-500"
          />
          {validationError && (
            <p
              id="email-error"
              role="alert"
              className="text-sm text-red-400"
              data-testid="validation-error"
            >
              {validationError}
            </p>
          )}
        </div>

        <Button
          type="submit"
          disabled={formState === "submitting"}
          aria-busy={formState === "submitting"}
          aria-label={`Get your free audit - Score: ${score}/100`}
          data-testid="submit-button"
          className="w-full bg-emerald-600 hover:bg-emerald-700 text-white"
        >
          {formState === "submitting" ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" data-testid="loader-icon" />
              Submitting...
            </>
          ) : (
            <>
              <Mail className="h-4 w-4" />
              Get your free audit (Score: {score}/100)
            </>
          )}
        </Button>

        {/* Submit Error */}
        {formState === "error" && submitError && (
          <div
            role="alert"
            className="rounded-lg border border-red-800 bg-red-900/20 p-4"
            data-testid="submit-error"
          >
            <div className="flex items-start gap-3">
              <AlertCircle className="h-5 w-5 text-red-400 shrink-0 mt-0.5" />
              <div className="flex-1">
                <p className="text-red-300">{submitError}</p>
                <button
                  type="button"
                  onClick={handleRetry}
                  className="mt-2 text-sm text-emerald-400 hover:text-emerald-300 hover:underline"
                  data-testid="retry-button"
                >
                  Try again
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Privacy Notice */}
        <p
          className="text-xs text-slate-500"
          data-testid="privacy-notice"
        >
          We respect your privacy. Your email will only be used to send your audit report.
        </p>
      </form>

      {/* Social Sharing */}
      <div
        data-testid="social-sharing"
        className="border-t border-slate-800 pt-6"
      >
        <h3 className="mb-3 text-sm font-medium text-slate-300">Share this audit</h3>
        <div className="flex flex-wrap gap-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => handleShare("twitter")}
            aria-label="Share on Twitter"
            data-testid="twitter-share"
            className="border-slate-700 bg-slate-800/50 text-slate-300 hover:bg-slate-700 hover:text-white"
          >
            <Twitter className="h-4 w-4 text-sky-400" />
            Twitter
          </Button>
          <Button
            type="button"
            variant="outline"
            onClick={() => handleShare("linkedin")}
            aria-label="Share on LinkedIn"
            data-testid="linkedin-share"
            className="border-slate-700 bg-slate-800/50 text-slate-300 hover:bg-slate-700 hover:text-white"
          >
            <Linkedin className="h-4 w-4 text-blue-500" />
            LinkedIn
          </Button>
          <Button
            type="button"
            variant="outline"
            onClick={() => handleShare("facebook")}
            aria-label="Share on Facebook"
            data-testid="facebook-share"
            className="border-slate-700 bg-slate-800/50 text-slate-300 hover:bg-slate-700 hover:text-white"
          >
            <Facebook className="h-4 w-4 text-blue-600" />
            Facebook
          </Button>
        </div>
      </div>

      {/* Embed Code */}
      <div
        data-testid="embed-code-section"
        className="border-t border-slate-800 pt-6"
      >
        <h3 className="mb-3 text-sm font-medium text-slate-300">Embed this badge</h3>
        <div className="relative">
          <Textarea
            readOnly
            value={embedCode}
            aria-label="Badge embed code"
            data-testid="embed-code"
            rows={4}
            className="resize-none border-slate-700 bg-slate-800/50 font-mono text-xs text-slate-300"
          />
          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={handleCopyEmbed}
            aria-label="Copy embed code"
            data-testid="copy-button"
            className="absolute right-2 top-2 h-7 px-2 text-xs text-slate-400 hover:bg-slate-700 hover:text-white"
          >
            {copied ? (
              <>
                <Check className="h-3 w-3 text-green-400" data-testid="check-icon" />
                Copied!
              </>
            ) : (
              <>
                <Copy className="h-3 w-3" data-testid="copy-icon" />
                Copy
              </>
            )}
          </Button>
        </div>
      </div>
    </div>
  );
}

export default PublicAuditCTA;
