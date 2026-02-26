"use client";

import * as React from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";

export interface KeywordDensityFormData {
  url?: string;
  html?: string;
  keyword: string;
}

interface KeywordDensityFormProps {
  onAnalyze: (data: KeywordDensityFormData) => void;
  isLoading?: boolean;
  initialUrl?: string;
  initialHtml?: string;
  initialKeyword?: string;
  className?: string;
}

export function KeywordDensityForm({
  onAnalyze,
  isLoading = false,
  initialUrl = "",
  initialHtml = "",
  initialKeyword = "",
  className,
}: KeywordDensityFormProps) {
  const [url, setUrl] = React.useState(initialUrl);
  const [html, setHtml] = React.useState(initialHtml);
  const [keyword, setKeyword] = React.useState(initialKeyword);
  const [urlError, setUrlError] = React.useState<string | null>(null);
  const [keywordError, setKeywordError] = React.useState<string | null>(null);
  const [activeInput, setActiveInput] = React.useState<"url" | "html">(
    initialUrl ? "url" : initialHtml ? "html" : "url"
  );

  const validateUrl = (value: string): string | null => {
    if (!value) return "URL is required";
    try {
      new URL(value);
      if (value.length > 2083) return "URL is too long (max 2083 characters)";
      return null;
    } catch {
      return "Please enter a valid URL";
    }
  };

  const validateKeyword = (value: string): string | null => {
    if (!value || value.trim() === "") return "Keyword is required";
    if (value.length > 100) return "Keyword is too long (max 100 characters)";
    return null;
  };

  const handleUrlChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setUrl(value);
    if (value) {
      setHtml(""); // Clear HTML when URL is entered
      setActiveInput("url");
      setUrlError(validateUrl(value));
    } else {
      setUrlError(null);
    }
  };

  const handleHtmlChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const value = e.target.value;
    setHtml(value);
    if (value) {
      setUrl(""); // Clear URL when HTML is entered
      setActiveInput("html");
    }
  };

  const handleKeywordChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setKeyword(value);
    if (keywordError) {
      setKeywordError(null);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Validate keyword
    const kwError = validateKeyword(keyword);
    if (kwError) {
      setKeywordError(kwError);
      return;
    }

    if (activeInput === "url") {
      const error = validateUrl(url);
      if (error) {
        setUrlError(error);
        return;
      }
      onAnalyze({ url, keyword });
    } else {
      onAnalyze({ html, keyword });
    }
  };

  const handleReset = () => {
    setUrl("");
    setHtml("");
    setKeyword("");
    setUrlError(null);
    setKeywordError(null);
    setActiveInput("url");
  };

  return (
    <form
      onSubmit={handleSubmit}
      data-testid="keyword-density-form"
      className={cn("space-y-6", className)}
    >
      {/* URL Input */}
      <div className="space-y-2">
        <Label htmlFor="url-input">Enter page URL</Label>
        <Input
          id="url-input"
          type="url"
          placeholder="Enter page URL"
          value={url}
          onChange={handleUrlChange}
          disabled={isLoading}
          aria-describedby={urlError ? "url-error" : undefined}
          aria-invalid={!!urlError}
          className={cn(urlError && "border-red-500")}
        />
        {urlError && (
          <span
            id="url-error"
            role="alert"
            className="text-sm text-red-500"
          >
            {urlError}
          </span>
        )}
      </div>

      {/* Divider */}
      <div className="relative">
        <div className="absolute inset-0 flex items-center">
          <span className="w-full border-t border-slate-700" />
        </div>
        <div className="relative flex justify-center text-xs uppercase">
          <span className="bg-slate-900 px-2 text-slate-500">Or</span>
        </div>
      </div>

      {/* HTML Input */}
      <div className="space-y-2">
        <Label htmlFor="html-input">Paste HTML</Label>
        <Textarea
          id="html-input"
          placeholder="Or paste HTML here"
          value={html}
          onChange={handleHtmlChange}
          disabled={isLoading}
          rows={10}
          className="font-mono text-sm"
        />
        <p className="text-xs text-slate-500">
          Paste your HTML content for instant keyword analysis
        </p>
      </div>

      {/* Keyword Input */}
      <div className="space-y-2">
        <Label htmlFor="keyword-input">Target Keyword</Label>
        <Input
          id="keyword-input"
          type="text"
          placeholder="Enter target keyword"
          value={keyword}
          onChange={handleKeywordChange}
          disabled={isLoading}
          aria-describedby={keywordError ? "keyword-error" : undefined}
          aria-invalid={!!keywordError}
          className={cn(keywordError && "border-red-500")}
        />
        {keywordError && (
          <span
            id="keyword-error"
            role="alert"
            className="text-sm text-red-500"
          >
            {keywordError}
          </span>
        )}
      </div>

      {/* Buttons */}
      <div className="flex gap-3">
        <Button
          type="submit"
          disabled={isLoading}
          className="flex-1"
        >
          {isLoading ? (
            <>
              <span className="animate-spin mr-2">⏳</span>
              Analyzing...
            </>
          ) : (
            "Analyze"
          )}
        </Button>
        <Button
          type="button"
          variant="outline"
          onClick={handleReset}
          disabled={isLoading}
        >
          Reset
        </Button>
      </div>

      {/* Active input indicator */}
      <p className="text-xs text-slate-500 text-center">
        {activeInput === "url" && url
          ? "Will analyze URL"
          : activeInput === "html" && html
          ? "Will analyze pasted HTML"
          : "Enter a URL or paste HTML to analyze"}
      </p>
    </form>
  );
}

export default KeywordDensityForm;
