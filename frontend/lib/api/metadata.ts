import useSWR from "swr";

export interface SERPFeatureOption {
  value: string;
  label: string;
  icon: string;
  description: string;
  ctrImpact: string;
}

interface MetadataResponse {
  success: boolean;
  data: {
    serpFeatures: SERPFeatureOption[];
  };
}

const fetcher = async (url: string): Promise<MetadataResponse> => {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Failed to fetch metadata: ${response.statusText}`);
  }
  return response.json();
};

/**
 * Fetch SERP feature options for filter dropdowns.
 */
export function useSERPFeatureOptions() {
  const { data, error, isLoading } = useSWR<MetadataResponse>(
    "/api/v1/metadata/serp-features",
    fetcher
  );

  return {
    options: data?.data ?? [],
    isLoading,
    error,
  };
}

/**
 * Fallback SERP feature options if API fails.
 */
export const FALLBACK_SERP_FEATURES: SERPFeatureOption[] = [
  { value: "all", label: "All Features", icon: "filter", description: "Show all keywords", ctrImpact: "" },
  { value: "featured_snippet", label: "Featured Snippet", icon: "trophy", description: "Answer box at top of search results", ctrImpact: "+30-50%" },
  { value: "local_pack", label: "Local Pack", icon: "map-pin", description: "Map with 3 local business listings", ctrImpact: "High for local" },
  { value: "image_pack", label: "Image Pack", icon: "image", description: "Horizontal row of images in results", ctrImpact: "Visual attraction" },
  { value: "video", label: "Video Result", icon: "play-circle", description: "Video thumbnail in search results", ctrImpact: "High engagement" },
  { value: "people_also_ask", label: "People Also Ask", icon: "help-circle", description: "Expandable related questions", ctrImpact: "Secondary visibility" },
  { value: "knowledge_panel", label: "Knowledge Panel", icon: "info", description: "Entity information sidebar", ctrImpact: "Brand authority" },
  { value: "sitelinks", label: "Sitelinks", icon: "link", description: "Additional page links under result", ctrImpact: "+20-30%" },
  { value: "reviews", label: "Reviews/Ratings", icon: "star", description: "Star ratings displayed with result", ctrImpact: "+10-15%" },
  { value: "faq", label: "FAQ Rich Result", icon: "list", description: "FAQ accordion in search results", ctrImpact: "Increased visibility" },
  { value: "product", label: "Product Listing", icon: "shopping-cart", description: "Product rich result with price/availability", ctrImpact: "Shopping intent" },
];
