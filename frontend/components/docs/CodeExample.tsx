'use client';

import * as React from 'react';
import { Copy, Check } from 'lucide-react';
import { cn } from '@/lib/utils';

interface CodeExampleProps {
  language: 'curl' | 'javascript' | 'python' | 'json' | 'bash';
  code: string;
  baseUrl?: string;
  placeholders?: Record<string, string>;
  showLineNumbers?: boolean;
  title?: string;
  className?: string;
}

const languageLabels: Record<string, string> = {
  curl: 'cURL',
  javascript: 'JavaScript',
  python: 'Python',
  json: 'JSON',
  bash: 'Bash',
};

// Simple syntax highlighting using CSS classes
function highlightCode(code: string, language: string): React.ReactNode {
  // Basic keyword highlighting for better readability
  const keywords: Record<string, RegExp> = {
    curl: /(curl|GET|POST|PUT|DELETE|PATCH|-X|-H|-d|http|https)/g,
    javascript: /(const|let|var|function|return|await|async|fetch|import|export|from|new|if|else|for|while)/g,
    python: /(import|from|def|class|return|if|else|for|while|with|as|async|await|try|except|raise|True|False|None)/g,
    json: /(true|false|null)/g,
    bash: /(curl|echo|export|if|then|else|fi|for|do|done|while|case|esac)/g,
  };

  if (!keywords[language]) {
    return code;
  }

  // Split by the keyword pattern and wrap keywords in spans
  const parts = code.split(keywords[language]);
  const matches = code.match(keywords[language]) || [];

  const result: React.ReactNode[] = [];
  parts.forEach((part, index) => {
    result.push(<span key={`text-${index}`}>{part}</span>);
    if (matches[index]) {
      result.push(
        <span key={`keyword-${index}`} className="text-purple-600 dark:text-purple-400 font-medium">
          {matches[index]}
        </span>
      );
    }
  });

  return result;
}

export function CodeExample({
  language,
  code,
  baseUrl = 'https://api.seoindicator.com/api/v1',
  placeholders = {},
  showLineNumbers = false,
  title,
  className = '',
}: CodeExampleProps) {
  const [copied, setCopied] = React.useState(false);

  // Replace placeholders in code
  const processedCode = React.useMemo(() => {
    let result = code;
    for (const [key, value] of Object.entries(placeholders)) {
      result = result.replace(new RegExp(`\\{${key}\\}`, 'g'), value);
    }
    result = result.replace(/{base_url}/g, baseUrl);
    return result;
  }, [code, placeholders, baseUrl]);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(processedCode);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  // Split code into lines for line numbers
  const lines = processedCode.split('\n');

  return (
    <div data-testid="code-example" className={cn('rounded-lg border overflow-hidden', className)}>
      {/* Header */}
      <div
        data-testid="code-header"
        className="flex items-center justify-between bg-gray-100 dark:bg-gray-800 px-4 py-2"
      >
        <div className="flex items-center gap-2">
          {title && (
            <span data-testid="code-title" className="font-medium text-gray-700 dark:text-gray-300">
              {title}
            </span>
          )}
          <span
            data-testid="language-label"
            className="text-xs bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-600 dark:text-gray-400"
          >
            {languageLabels[language] || language}
          </span>
        </div>
        <button
          data-testid="copy-btn"
          onClick={handleCopy}
          aria-label="Copy code to clipboard"
          className="flex items-center gap-1 px-2 py-1 rounded text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
        >
          {copied ? (
            <>
              <Check data-testid="check-icon" className="h-4 w-4 text-green-500" />
              <span className="text-green-600 dark:text-green-400">Copied!</span>
            </>
          ) : (
            <>
              <Copy data-testid="copy-icon" className="h-4 w-4" />
              <span className="text-gray-600 dark:text-gray-400">Copy</span>
            </>
          )}
        </button>
      </div>

      {/* Code block */}
      <div
        data-testid="code-container"
        data-language={language}
        data-line-numbers={showLineNumbers}
        className="overflow-auto bg-gray-900 text-gray-100"
      >
        <pre className="p-4 text-sm leading-relaxed">
          <code data-testid="code-highlight">
            {showLineNumbers
              ? lines.map((line, index) => (
                  <div key={index} className="flex">
                    <span className="select-none text-gray-500 mr-4 w-8 text-right">
                      {index + 1}
                    </span>
                    <span>{highlightCode(line, language)}</span>
                  </div>
                ))
              : highlightCode(processedCode, language)}
          </code>
        </pre>
      </div>
    </div>
  );
}

// Pre-built code examples for common operations
export const codeExamples = {
  listKeywords: {
    curl: `curl -X GET "{base_url}/keywords?site_id={site_id}" \\
  -H "Authorization: Bearer {token}" \\
  -H "Content-Type: application/json"`,
    javascript: `const response = await fetch('{base_url}/keywords?site_id={site_id}', {
  headers: {
    'Authorization': 'Bearer {token}',
    'Content-Type': 'application/json'
  }
});
const data = await response.json();
console.log(data);`,
    python: `import requests

response = requests.get(
    '{base_url}/keywords',
    params={'site_id': '{site_id}'},
    headers={
        'Authorization': 'Bearer {token}',
        'Content-Type': 'application/json'
    }
)
data = response.json()
print(data)`,
  },
  createKeyword: {
    curl: `curl -X POST "{base_url}/keywords" \\
  -H "Authorization: Bearer {token}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "keyword": "seo tools",
    "site_id": {site_id}
  }'`,
    javascript: `const response = await fetch('{base_url}/keywords', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer {token}',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    keyword: 'seo tools',
    site_id: {site_id}
  })
});
const data = await response.json();`,
    python: `import requests

response = requests.post(
    '{base_url}/keywords',
    headers={
        'Authorization': 'Bearer {token}',
        'Content-Type': 'application/json'
    },
    json={
        'keyword': 'seo tools',
        'site_id': {site_id}
    }
)
data = response.json()`,
  },
  getKeyword: {
    curl: `curl -X GET "{base_url}/keywords/{keyword_id}" \\
  -H "Authorization: Bearer {token}"`,
    javascript: `const response = await fetch('{base_url}/keywords/{keyword_id}', {
  headers: {
    'Authorization': 'Bearer {token}'
  }
});
const data = await response.json();`,
    python: `import requests

response = requests.get(
    '{base_url}/keywords/{keyword_id}',
    headers={
        'Authorization': 'Bearer {token}'
    }
)
data = response.json()`,
  },
  authExample: {
    curl: `curl -X GET "{base_url}/keywords" \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -H "Content-Type: application/json"`,
    javascript: `// Using fetch with Bearer token
const response = await fetch('{base_url}/keywords', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  }
});`,
    python: `import requests

# Using Bearer token
headers = {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
}
response = requests.get('{base_url}/keywords', headers=headers)`,
  },
  webhookPayload: `{
  "event": "keyword.position_changed",
  "timestamp": "2026-03-12T10:30:00Z",
  "data": {
    "id": 123,
    "keyword": "seo tools",
    "old_position": 15,
    "new_position": 8,
    "url": "https://example.com/seo-tools",
    "site_id": 1
  }
}`,
};
