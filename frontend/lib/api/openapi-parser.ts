/**
 * OpenAPI Parser Utility
 * Parses OpenAPI 3.0 specifications for API documentation
 */

export interface OpenApiSpec {
  openapi: string;
  info: {
    title: string;
    version: string;
    description?: string;
  };
  servers?: Array<{ url: string; description?: string }>;
  security?: Record<string, string[]>;
  paths: Record<string, PathItem>;
  components?: {
    securitySchemes?: Record<string, SecurityScheme>;
    schemas?: Record<string, Schema>;
  };
}

export interface PathItem {
  get?: Operation;
  post?: Operation;
  put?: Operation;
  patch?: Operation;
  delete?: Operation;
  parameters?: Parameter[];
}

export interface Operation {
  summary?: string;
  description?: string;
  tags?: string[];
  parameters?: Parameter[];
  requestBody?: RequestBody;
  responses?: Record<string, Response>;
  security?: SecurityRequirement[];
}

export interface Parameter {
  name: string;
  in: 'path' | 'query' | 'header' | 'cookie';
  required?: boolean;
  schema?: { type: string };
  description?: string;
  default?: string;
}

export interface RequestBody {
  required?: boolean;
  content?: Record<string, { schema?: { $ref?: string; type?: string; properties?: Record<string, unknown> } }>;
}

export interface Response {
  description: string;
  content?: Record<string, { schema?: { $ref?: string } }>;
}

export interface SecurityScheme {
  type: string;
  scheme?: string;
  in?: string;
  name?: string;
  description?: string;
}

export interface SecurityRequirement {
  [name: string]: string[];
}

export interface Schema {
  type: string;
  properties?: Record<string, { type: string; description?: string }>;
  required?: string[];
  description?: string;
}

export interface ParsedEndpoint {
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  path: string;
  summary?: string;
  description?: string;
  tags?: string[];
  parameters: ParsedParameter[];
  responses: ParsedResponse[];
  requiresAuth: boolean;
  requestBody?: {
    required: boolean;
    schema?: string;
  };
}

export interface ParsedParameter {
  name: string;
  in: 'path' | 'query' | 'header' | 'cookie';
  required: boolean;
  type: string;
  description?: string;
  default?: string;
}

export interface ParsedResponse {
  status: string;
  description: string;
}

export interface ParsedSecurityScheme {
  name: string;
  type: string;
  scheme?: string;
  in?: string;
  headerName?: string;
  description?: string;
}

// Cache for parsed specs
const specCache = new Map<string, { spec: OpenApiSpec; timestamp: number }>();
const CACHE_TTL = 5 * 60 * 1000; // 5 minutes

/**
 * Parse OpenAPI specification from URL
 */
export async function parseOpenApiSpec(url: string): Promise<OpenApiSpec | null> {
  // Check cache
  const cached = specCache.get(url);
  if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
    return cached.spec;
  }

  try {
    const response = await fetch(url);
    if (!response.ok) {
      console.error(`Failed to fetch OpenAPI spec: ${response.status}`);
      return null;
    }

    const text = await response.text();
    let spec: OpenApiSpec;

    if (text.trim().startsWith('{')) {
      // JSON format
      spec = JSON.parse(text);
    } else {
      // YAML format - simple parsing for basic YAML
      // For production, use a proper YAML parser like 'js-yaml'
      spec = parseSimpleYaml(text);
    }

    // Validate OpenAPI version
    if (!spec.openapi?.startsWith('3.')) {
      console.error('Invalid OpenAPI version. Expected 3.x');
      return null;
    }

    // Cache the result
    specCache.set(url, { spec, timestamp: Date.now() });

    return spec;
  } catch (error) {
    console.error('Error parsing OpenAPI spec:', error);
    return null;
  }
}

/**
 * Simple YAML parser for basic OpenAPI specs
 * Note: For production, use a proper YAML parser library
 */
function parseSimpleYaml(text: string): OpenApiSpec {
  // This is a minimal implementation for basic YAML support
  // In production, use 'js-yaml' library for full YAML parsing
  const lines = text.split('\n');
  const result: Partial<OpenApiSpec> = {
    info: { title: 'API', version: '1.0.0' },
    paths: {},
  };
  
  let currentPath = '';
  let currentIndent = 0;
  
  for (const line of lines) {
    const indent = line.search(/\S|$/);
    const trimmed = line.trim();
    
    if (trimmed.startsWith('openapi:')) {
      result.openapi = trimmed.split(':')[1].trim().replace(/['"]/g, '');
    } else if (trimmed.startsWith('title:')) {
      result.info!.title = trimmed.split(':').slice(1).join(':').trim().replace(/['"]/g, '');
    } else if (trimmed.startsWith('version:')) {
      result.info!.version = trimmed.split(':')[1].trim().replace(/['"]/g, '');
    }
  }
  
  return result as OpenApiSpec;
}

/**
 * Extract all endpoints from OpenAPI spec
 */
export function extractEndpoints(spec: OpenApiSpec): ParsedEndpoint[] {
  const endpoints: ParsedEndpoint[] = [];
  const methods: Array<'get' | 'post' | 'put' | 'patch' | 'delete'> = ['get', 'post', 'put', 'patch', 'delete'];

  for (const [path, pathItem] of Object.entries(spec.paths)) {
    for (const method of methods) {
      const operation = pathItem[method];
      if (!operation) continue;

      // Merge path-level and operation-level parameters
      const pathParams = pathItem.parameters || [];
      const opParams = operation.parameters || [];
      const allParams = [...pathParams, ...opParams];

      const parameters: ParsedParameter[] = allParams.map(p => ({
        name: p.name,
        in: p.in,
        required: p.required ?? false,
        type: p.schema?.type ?? 'string',
        description: p.description,
        default: p.default,
      }));

      const responses: ParsedResponse[] = operation.responses
        ? Object.entries(operation.responses).map(([status, resp]) => ({
            status,
            description: resp.description,
          }))
        : [];

      // Check if auth is required
      const hasGlobalSecurity = spec.security !== undefined && Object.keys(spec.security).length > 0;
      const hasOperationSecurity = operation.security !== undefined && operation.security.length > 0;
      const requiresAuth: boolean = hasGlobalSecurity || hasOperationSecurity;

      endpoints.push({
        method: method.toUpperCase() as 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
        path,
        summary: operation.summary,
        description: operation.description,
        tags: operation.tags,
        parameters,
        responses,
        requiresAuth,
        requestBody: operation.requestBody ? {
          required: operation.requestBody.required ?? false,
          schema: extractSchemaRef(operation.requestBody),
        } : undefined,
      });
    }
  }

  return endpoints;
}

/**
 * Extract schema reference from request body
 */
function extractSchemaRef(requestBody: RequestBody): string | undefined {
  const content = requestBody.content?.['application/json'];
  if (content?.schema?.$ref) {
    const ref = content.schema.$ref;
    const parts = ref.split('/');
    return parts[parts.length - 1];
  }
  return undefined;
}

/**
 * Extract security schemes from OpenAPI spec
 */
export function extractSecuritySchemes(spec: OpenApiSpec): ParsedSecurityScheme[] {
  if (!spec.components?.securitySchemes) return [];

  return Object.entries(spec.components.securitySchemes).map(([name, scheme]) => ({
    name,
    type: scheme.type,
    scheme: scheme.scheme,
    in: scheme.in,
    headerName: scheme.name,
    description: scheme.description,
  }));
}

/**
 * Extract component schemas
 */
export function extractSchemas(spec: OpenApiSpec): Record<string, Schema> {
  return spec.components?.schemas || {};
}

/**
 * Get API info from spec
 */
export function getApiInfo(spec: OpenApiSpec) {
  return {
    title: spec.info.title,
    version: spec.info.version,
    description: spec.info.description,
    servers: spec.servers || [],
  };
}

/**
 * Validate OpenAPI version
 */
export function validateOpenApiVersion(spec: OpenApiSpec): boolean {
  return spec.openapi.startsWith('3.0') || spec.openapi.startsWith('3.1');
}

/**
 * Group endpoints by tag
 */
export function groupEndpointsByTag(endpoints: ParsedEndpoint[]): Record<string, ParsedEndpoint[]> {
  const groups: Record<string, ParsedEndpoint[]> = {};

  for (const endpoint of endpoints) {
    const tags = endpoint.tags?.length ? endpoint.tags : ['Other'];
    for (const tag of tags) {
      if (!groups[tag]) {
        groups[tag] = [];
      }
      groups[tag].push(endpoint);
    }
  }

  return groups;
}

/**
 * Clear the spec cache
 */
export function clearSpecCache(): void {
  specCache.clear();
}
