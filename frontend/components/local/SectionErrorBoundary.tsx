"use client";

import * as React from "react";
import { AlertTriangle } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface SectionErrorBoundaryProps {
  title: string;
  onRetry?: () => void;
  children: React.ReactNode;
}

interface SectionErrorBoundaryState {
  hasError: boolean;
  message: string;
}

export class SectionErrorBoundary extends React.Component<
  SectionErrorBoundaryProps,
  SectionErrorBoundaryState
> {
  state: SectionErrorBoundaryState = {
    hasError: false,
    message: "",
  };

  static getDerivedStateFromError(error: Error): SectionErrorBoundaryState {
    return {
      hasError: true,
      message: error.message || "Something went wrong while rendering this section.",
    };
  }

  componentDidCatch(error: Error) {
    console.error("Dashboard section render error:", error);
  }

  handleRetry = () => {
    this.setState({ hasError: false, message: "" });
    this.props.onRetry?.();
  };

  render() {
    if (this.state.hasError) {
      return (
        <Card className="border-red-500/20 bg-red-500/10 text-red-50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-red-50">
              <AlertTriangle className="h-5 w-5" aria-hidden="true" />
              {this.props.title}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <p className="text-sm text-red-100/90">{this.state.message || "Unable to render this section."}</p>
            {this.props.onRetry ? (
              <Button type="button" variant="outline" onClick={this.handleRetry}>
                Try again
              </Button>
            ) : null}
          </CardContent>
        </Card>
      );
    }

    return this.props.children;
  }
}

export default SectionErrorBoundary;
