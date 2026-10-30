/**
 * SEO Tools Components
 * Tasks: TASK-16-2, TASK-16-4, TASK-16-8 - Keyword Density Checker, Tool Embedding System & Social Media Preview
 */

export { KeywordDensityForm, type KeywordDensityFormData } from "./KeywordDensityForm";
export { KeywordDensityResults, type KeywordDensityResult, type KeywordWarning } from "./KeywordDensityResults";
export { KeywordDensityWarning, KeywordDensityWarningList, DensityStatusBadge, HeadingKeywordIndicator, type WarningData } from "./KeywordDensityWarning";
export { KeywordDensityChecker } from "./KeywordDensityChecker";

// Tool Embedding System (TASK-16-4)
export { ToolWidget, type ToolWidgetProps } from "./ToolWidget";

// Social Media Preview Tool (TASK-16-8)
export { SocialMediaPreviewForm, type SocialMediaPreviewFormData } from "./SocialMediaPreviewForm";
export { PlatformTabs, type Platform } from "./PlatformTabs";
export { PreviewCard, type PlatformPreview } from "./PreviewCard";
export { MissingTagsList } from "./MissingTagsList";
export { WarningsList, type SocialWarning } from "./WarningsList";
export { SocialMediaPreviewResults, type SocialMediaPreviewData } from "./SocialMediaPreviewResults";
export { SocialMediaPreview } from "./SocialMediaPreview";
