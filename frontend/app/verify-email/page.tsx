"use client";

import * as React from "react";
import { Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Mail, Loader2, CheckCircle, RefreshCw } from "lucide-react";

import { useAuth } from "@/lib/contexts/AuthContext";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

function VerifyEmailContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { user, resendVerification, isLoading, error, clearError, isAuthenticated } = useAuth();

  const [submitError, setSubmitError] = React.useState<string | null>(null);
  const [isResending, setIsResending] = React.useState(false);
  const [resendSuccess, setResendSuccess] = React.useState(false);
  const [cooldown, setCooldown] = React.useState(0);

  // Check for verification success from URL params
  const verified = searchParams.get("verified");
  const verificationError = searchParams.get("error");

  // Redirect if already verified and authenticated
  React.useEffect(() => {
    if (isAuthenticated && user?.email_verified) {
      router.push("/dashboard");
    }
  }, [isAuthenticated, user, router]);

  // Clear errors on mount
  React.useEffect(() => {
    clearError();
  }, [clearError]);

  // Cooldown timer
  React.useEffect(() => {
    if (cooldown > 0) {
      const timer = setTimeout(() => setCooldown(cooldown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [cooldown]);

  const handleResend = async () => {
    if (cooldown > 0) return;

    setSubmitError(null);
    setResendSuccess(false);
    setIsResending(true);

    try {
      await resendVerification();
      setResendSuccess(true);
      setCooldown(60); // 60 second cooldown
    } catch (err) {
      const message = err instanceof Error ? err.message : "Failed to resend verification email";
      setSubmitError(message);
    } finally {
      setIsResending(false);
    }
  };

  // Mask email for privacy
  const maskedEmail = React.useMemo(() => {
    if (!user?.email) return "your email";

    const [localPart, domain] = user.email.split("@");
    if (!domain) return "your email";

    const maskedLocal = localPart.length > 2
      ? `${localPart[0]}${"*".repeat(Math.min(localPart.length - 2, 5))}${localPart[localPart.length - 1]}`
      : localPart;

    return `${maskedLocal}@${domain}`;
  }, [user?.email]);

  // Verification success state
  if (verified === "true") {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4 py-8">
        <Card className="w-full max-w-md">
          <CardHeader className="space-y-1 text-center">
            <div className="mx-auto mb-4 h-12 w-12 rounded-full bg-green-500/10 flex items-center justify-center">
              <CheckCircle className="h-6 w-6 text-green-500" />
            </div>
            <CardTitle className="text-2xl font-bold">Email Verified</CardTitle>
            <CardDescription>
              Your email has been successfully verified.
            </CardDescription>
          </CardHeader>

          <CardFooter className="flex justify-center">
            <Button onClick={() => router.push("/dashboard")}>
              Continue to Dashboard
            </Button>
          </CardFooter>
        </Card>
      </div>
    );
  }

  // Verification error state
  if (verificationError) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4 py-8">
        <Card className="w-full max-w-md">
          <CardHeader className="text-center">
            <div className="mx-auto mb-4 h-12 w-12 rounded-full bg-destructive/10 flex items-center justify-center">
              <Mail className="h-6 w-6 text-destructive" />
            </div>
            <CardTitle className="text-2xl font-bold">Verification Failed</CardTitle>
            <CardDescription>
              {verificationError === "expired"
                ? "This verification link has expired. Please request a new one."
                : "This verification link is invalid. Please request a new one."}
            </CardDescription>
          </CardHeader>

          <CardContent className="space-y-4">
            <Button
              className="w-full"
              onClick={handleResend}
              disabled={isResending || cooldown > 0}
            >
              {isResending ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Sending...
                </>
              ) : cooldown > 0 ? (
                <>
                  <RefreshCw className="h-4 w-4" />
                  Resend in {cooldown}s
                </>
              ) : (
                "Resend Verification Email"
              )}
            </Button>
          </CardContent>

          <CardFooter className="flex justify-center">
            <Button
              type="button"
              variant="link"
              className="text-sm"
              onClick={() => router.push("/login")}
            >
              Back to login
            </Button>
          </CardFooter>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4 py-8">
      <Card className="w-full max-w-md">
        <CardHeader className="space-y-1 text-center">
          <div className="mx-auto mb-4 h-12 w-12 rounded-full bg-blue-500/10 flex items-center justify-center">
            <Mail className="h-6 w-6 text-blue-500" />
          </div>
          <CardTitle className="text-2xl font-bold">Verify Your Email</CardTitle>
          <CardDescription>
            We&apos;ve sent a verification link to{" "}
            <span className="font-medium text-foreground">{maskedEmail}</span>
          </CardDescription>
        </CardHeader>

        <CardContent className="space-y-4">
          {/* Error Message */}
          {(submitError || error) && (
            <div
              role="alert"
              className="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-md text-sm"
            >
              {submitError || error?.message}
            </div>
          )}

          {/* Resend Success */}
          {resendSuccess && (
            <div
              role="alert"
              className="bg-green-500/10 border border-green-500/20 text-green-500 px-4 py-3 rounded-md text-sm"
            >
              Verification email sent! Check your inbox.
            </div>
          )}

          <p className="text-sm text-muted-foreground text-center">
            Click the link in the email to verify your account. If you don&apos;t see it, check your
            spam folder.
          </p>

          <Button
            variant="outline"
            className="w-full"
            onClick={handleResend}
            disabled={isResending || isLoading || cooldown > 0}
          >
            {isResending ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" />
                Sending...
              </>
            ) : cooldown > 0 ? (
              <>
                <RefreshCw className="h-4 w-4" />
                Resend in {cooldown}s
              </>
            ) : (
              "Resend Verification Email"
            )}
          </Button>
        </CardContent>

        <CardFooter className="flex justify-center">
          <Button
            type="button"
            variant="link"
            className="text-sm"
            onClick={() => router.push("/login")}
          >
            Back to login
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
}

function VerifyEmailLoading() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4 py-8">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl font-bold">Loading...</CardTitle>
        </CardHeader>
      </Card>
    </div>
  );
}

export default function VerifyEmailPage() {
  return (
    <Suspense fallback={<VerifyEmailLoading />}>
      <VerifyEmailContent />
    </Suspense>
  );
}
