/**
 * AI Model Types for SEOIndicator
 *
 * Types for model selection, pricing, and preferences.
 *
 * @see SPEC-026
 * @see TASK-26-22
 */

// ==================== Model Types ====================

export type ModelCapability = 'tools' | 'vision' | 'fast' | 'advanced' | 'streaming';

export interface AIModel {
  id: number;
  provider: string;
  model_id: string;
  display_name: string;
  cost_per_1k_input: number;
  cost_per_1k_output: number;
  estimated_cost_per_analysis: number;
  context_window: number;
  is_free_tier: boolean;
  capabilities: ModelCapability[];
  is_user_default: boolean;
  is_active?: boolean;
  description?: string;
  max_output_tokens?: number;
  free_tier_limit?: number;
}

// ==================== API Response Types ====================

export interface ModelsListResponse {
  success: boolean;
  data: AIModel[];
  meta: {
    total: number;
    free_tier_count: number;
  };
}

export interface ModelPricingResponse {
  success: boolean;
  data: {
    model: {
      id: number;
      display_name: string;
      provider: string;
      model_id: string;
    };
    pricing: {
      cost_per_1m_input: number;
      cost_per_1m_output: number;
      cost_per_1k_input: number;
      cost_per_1k_output: number;
    };
    estimates: {
      quick_analysis: { tokens: number; cost: number };
      standard_analysis: { tokens: number; cost: number };
      deep_analysis: { tokens: number; cost: number };
    };
    free_tier: {
      available: boolean;
      limit: number | null;
    };
  };
}

export interface ModelPreferencesResponse {
  success: boolean;
  data: {
    default_model: {
      id: number;
      display_name: string;
      provider: string;
    } | null;
  };
}

// ==================== Request Types ====================

export interface SetDefaultModelRequest {
  model_id: number;
}

export interface ModelsQueryParams {
  provider?: string;
  free_only?: boolean;
  capability?: string;
  search?: string;
  sort?: 'name' | 'price' | 'context_window';
}

// ==================== Component Props Types ====================

export interface ModelPickerProps {
  onSelect: (model: AIModel) => void;
  selectedModelId?: number | null;
  analysisType?: 'quick' | 'standard' | 'deep';
  showSetDefault?: boolean;
  userBalance?: number;
  className?: string;
}

export interface ModelCardProps {
  model: AIModel;
  isSelected: boolean;
  isDefault: boolean;
  onSelect: () => void;
  onSetDefault?: () => void;
  showSetDefault?: boolean;
  className?: string;
}

export interface ModelFilterProps {
  onSearchChange: (search: string) => void;
  onProviderChange: (provider: string | null) => void;
  onCapabilityChange: (capability: string | null) => void;
  onFreeOnlyChange: (freeOnly: boolean) => void;
  providers: string[];
  capabilities: ModelCapability[];
  className?: string;
}

export interface CostEstimateProps {
  modelId: number;
  analysisType?: 'quick' | 'standard' | 'deep';
  userBalance?: number;
  onAddCredits?: () => void;
  className?: string;
}

// ==================== Error Types ====================

export class ModelsError extends Error {
  public status?: number;
  public code?: string;

  constructor(message: string, status?: number, code?: string) {
    super(message);
    this.name = 'ModelsError';
    this.status = status;
    this.code = code;
  }
}
