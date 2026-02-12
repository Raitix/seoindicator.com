const DEFAULT_LENGTH_RULES = { min: 50, max: 160, idealMin: 120, idealMax: 160 };
const CJK_LENGTH_RULES = { min: 40, max: 120, idealMin: 60, idealMax: 110 };
const THAI_LENGTH_RULES = { min: 40, max: 120, idealMin: 60, idealMax: 110 };

const STOPWORDS = new Set([
  'the', 'and', 'for', 'to', 'of', 'in', 'a', 'an', 'is', 'with', 'on', 'your', 'our',
  'you', 'this', 'that', 'from', 'by', 'be', 'or', 'as', 'at', 'it', 'its', 'are',
  'about', 'into', 'over', 'under', 'within', 'without', 'best', 'guide'
]);

const normalizeWhitespace = (text) => text.replace(/\s+/g, ' ').trim();

const decodeHtmlEntities = (text) => {
  if (!text) return '';
  return text
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'")
    .replace(/&#x([0-9a-f]+);/gi, (_, hex) => String.fromCodePoint(parseInt(hex, 16)))
    .replace(/&#(\d+);/g, (_, num) => String.fromCodePoint(parseInt(num, 10)));
};

const stripTags = (html) => html.replace(/<[^>]*>/g, ' ');

const parseMetaTags = (html) => {
  const tags = [];
  const metaRegex = /<meta\b[^>]*>/gi;
  let match;
  while ((match = metaRegex.exec(html)) !== null) {
    const tag = match[0];
    const attrs = {};
    const attrRegex = /([^\s=]+)\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'=<>`]+))/g;
    let attrMatch;
    while ((attrMatch = attrRegex.exec(tag)) !== null) {
      const key = attrMatch[1].toLowerCase();
      const value = attrMatch[2] ?? attrMatch[3] ?? attrMatch[4] ?? '';
      attrs[key] = value;
    }
    tags.push(attrs);
  }
  return tags;
};

const extractMetaDescription = (html) => {
  const metaTags = parseMetaTags(html);
  const candidates = [];

  metaTags.forEach((attrs, index) => {
    const name = (attrs.name || '').toLowerCase();
    const property = (attrs.property || '').toLowerCase();
    const itemprop = (attrs.itemprop || '').toLowerCase();
    const content = attrs.content ?? '';

    const matches = [
      { match: name === 'description', source: 'meta[name=description]', priority: 1 },
      { match: itemprop === 'description', source: 'meta[itemprop=description]', priority: 2 },
      { match: property === 'description', source: 'meta[property=description]', priority: 3 },
      { match: property === 'og:description', source: 'meta[property=og:description]', priority: 4 },
      { match: name === 'twitter:description', source: 'meta[name=twitter:description]', priority: 5 },
    ];

    for (const entry of matches) {
      if (entry.match) {
        candidates.push({
          content,
          source: entry.source,
          priority: entry.priority,
          index,
        });
        break;
      }
    }
  });

  if (!candidates.length) {
    return { found: false, content: '', source: null, candidates: [] };
  }

  candidates.sort((a, b) => a.priority - b.priority || a.index - b.index);
  const chosen = candidates[0];

  return {
    found: true,
    content: chosen.content ?? '',
    source: chosen.source,
    candidates: candidates.map((candidate) => ({
      content: candidate.content ?? '',
      source: candidate.source,
    })),
  };
};

const extractTitle = (html) => {
  const match = html.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
  if (!match) return '';
  return normalizeWhitespace(decodeHtmlEntities(stripTags(match[1])));
};

const extractH1 = (html) => {
  const match = html.match(/<h1[^>]*>([\s\S]*?)<\/h1>/i);
  if (!match) return '';
  return normalizeWhitespace(decodeHtmlEntities(stripTags(match[1])));
};

const extractBodyText = (html) => {
  const cleaned = html
    .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, ' ');
  return normalizeWhitespace(decodeHtmlEntities(stripTags(cleaned)));
};

const extractLanguage = (html, metaTags) => {
  const htmlLang = html.match(/<html[^>]*\blang=["']?([^"'\s>]+)/i);
  if (htmlLang && htmlLang[1]) {
    return { lang: htmlLang[1].toLowerCase(), source: 'html' };
  }

  for (const attrs of metaTags) {
    const httpEquiv = (attrs['http-equiv'] || '').toLowerCase();
    const name = (attrs.name || '').toLowerCase();
    if (httpEquiv === 'content-language' || httpEquiv === 'contentlanguage' || name === 'language' || name === 'content-language') {
      const content = (attrs.content || '').split(/[,;]+/)[0].trim();
      if (content) {
        return { lang: content.toLowerCase(), source: 'meta' };
      }
    }
  }

  return { lang: 'und', source: 'unknown' };
};

const detectScript = (text) => {
  const hasCJK = /[\u3040-\u30ff\u3400-\u9fff\uac00-\ud7af]/.test(text);
  if (hasCJK) return 'cjk';
  if (/[\u0e00-\u0e7f]/.test(text)) return 'thai';
  if (/[\u0600-\u06ff]/.test(text)) return 'arabic';
  if (/[\u0400-\u04ff]/.test(text)) return 'cyrillic';
  return 'latin';
};

const countGraphemes = (text) => {
  if (typeof Intl !== 'undefined' && Intl.Segmenter) {
    const segmenter = new Intl.Segmenter('und', { granularity: 'grapheme' });
    return Array.from(segmenter.segment(text)).length;
  }
  return Array.from(text).length;
};

const getLengthRules = ({ isCJK, script }) => {
  if (isCJK) return CJK_LENGTH_RULES;
  if (script === 'thai') return THAI_LENGTH_RULES;
  return DEFAULT_LENGTH_RULES;
};

const evaluateLength = (description, rules) => {
  const length = countGraphemes(description);
  if (length === 0) {
    return { value: length, ...rules, status: 'empty' };
  }
  if (length < rules.min) {
    return { value: length, ...rules, status: 'too_short' };
  }
  if (length > rules.max) {
    return { value: length, ...rules, status: 'too_long' };
  }
  if (length >= rules.idealMin && length <= rules.idealMax) {
    return { value: length, ...rules, status: 'optimal' };
  }
  return { value: length, ...rules, status: 'acceptable' };
};

const normalizeForCompare = (text) =>
  normalizeWhitespace(text.toLowerCase().replace(/[\p{P}\p{S}]+/gu, ' '));

const extractTokens = (text, { isCJK }) => {
  if (!text) return [];
  const normalized = text.toLowerCase();

  if (isCJK) {
    const segmenter = typeof Intl !== 'undefined' && Intl.Segmenter
      ? new Intl.Segmenter('und', { granularity: 'grapheme' })
      : null;
    const graphemes = segmenter
      ? Array.from(segmenter.segment(normalized), (seg) => seg.segment)
      : Array.from(normalized);
    const cleaned = graphemes.filter((char) => /[\p{L}\p{N}]/u.test(char));
    if (cleaned.length < 2) return cleaned;
    const bigrams = [];
    for (let i = 0; i < cleaned.length - 1; i += 1) {
      bigrams.push(cleaned[i] + cleaned[i + 1]);
    }
    return bigrams;
  }

  const matches = normalized.match(/[\p{L}\p{N}]{2,}/gu) || [];
  return matches.filter((token) => !STOPWORDS.has(token));
};

const computeJaccard = (aTokens, bTokens) => {
  const aSet = new Set(aTokens);
  const bSet = new Set(bTokens);
  if (!aSet.size || !bSet.size) return 0;
  let intersection = 0;
  for (const token of aSet) {
    if (bSet.has(token)) intersection += 1;
  }
  const union = aSet.size + bSet.size - intersection;
  return union === 0 ? 0 : intersection / union;
};

const getTopTokens = (tokens, max = 5) => {
  const counts = tokens.reduce((acc, token) => {
    acc[token] = (acc[token] || 0) + 1;
    return acc;
  }, {});

  return Object.entries(counts)
    .sort((a, b) => b[1] - a[1])
    .slice(0, max)
    .map(([token]) => token);
};

const computeRelevancy = (description, contextText, isCJK) => {
  const descTokens = extractTokens(description, { isCJK });
  const contextTokens = extractTokens(contextText, { isCJK });

  if (!descTokens.length || !contextTokens.length) {
    return {
      score: 0,
      status: 'low',
      overlap: [],
      contextTopTokens: getTopTokens(contextTokens, 5),
    };
  }

  const contextSet = new Set(contextTokens);
  const overlap = descTokens.filter((token) => contextSet.has(token));
  const score = overlap.length / descTokens.length;

  let status = 'low';
  if (score >= 0.4) status = 'good';
  else if (score >= 0.2) status = 'medium';

  return {
    score,
    status,
    overlap,
    contextTopTokens: getTopTokens(contextTokens, 5),
  };
};

const analyzeMetaDescription = (pageHTML = '') => {
  const html = typeof pageHTML === 'string' ? pageHTML : '';
  const metaTags = parseMetaTags(html);
  const extraction = extractMetaDescription(html);

  if (!extraction.found) {
    return {
      success: false,
      error: 'Meta description not found',
      analysis: {
        issues: ['missing'],
        length: evaluateLength('', DEFAULT_LENGTH_RULES),
        language: { lang: 'und', script: 'latin', isCJK: false },
        relevancy: { score: 0, status: 'low', overlap: [], contextTopTokens: [] },
        source: null,
        candidates: [],
        suggestions: ['Add a unique meta description that summarizes the page value in 50-160 characters.'],
        aiSummary: 'AI Analysis: Meta description missing. Add a concise summary that matches the page content.',
      },
    };
  }

  const title = extractTitle(html);
  const h1 = extractH1(html);
  const bodyText = extractBodyText(html);
  const language = extractLanguage(html, metaTags);

  const rawDescription = extraction.content ?? '';
  const cleanedDescription = normalizeWhitespace(decodeHtmlEntities(rawDescription));

  const script = detectScript(cleanedDescription || title || bodyText);
  const isCJK = script === 'cjk' || ['zh', 'ja', 'ko'].some((prefix) => language.lang.startsWith(prefix));
  const lengthRules = getLengthRules({ isCJK, script });
  const lengthInfo = evaluateLength(cleanedDescription, lengthRules);

  const contextText = normalizeWhitespace([title, h1, bodyText].filter(Boolean).join(' '));
  const relevancy = computeRelevancy(cleanedDescription, contextText, isCJK);

  const issues = [];
  if (lengthInfo.status === 'empty') issues.push('empty');
  if (lengthInfo.status === 'too_short') issues.push('too_short');
  if (lengthInfo.status === 'too_long') issues.push('too_long');
  if (relevancy.status === 'low') issues.push('low_relevance');

  const normalizedDesc = normalizeForCompare(cleanedDescription);
  const normalizedTitle = normalizeForCompare(title);
  const duplicateTitle = normalizedDesc && normalizedTitle
    ? normalizedDesc === normalizedTitle
    : false;
  if (duplicateTitle) issues.push('duplicate_title');

  const descTokens = extractTokens(cleanedDescription, { isCJK });
  const uniqueTokens = new Set(descTokens);
  if (descTokens.length >= 6) {
    const topToken = getTopTokens(descTokens, 1)[0];
    const topTokenCount = descTokens.filter((token) => token === topToken).length;
    if (topTokenCount / descTokens.length > 0.3 || uniqueTokens.size / descTokens.length < 0.6) {
      issues.push('keyword_stuffing');
    }
  }

  const suggestions = [];
  if (issues.includes('empty') || issues.includes('too_short')) {
    suggestions.push(`Expand the description to at least ${lengthRules.min} characters and highlight the main benefit.`);
  }
  if (issues.includes('too_long')) {
    suggestions.push(`Trim the description to ${lengthRules.max} characters or fewer to avoid truncation.`);
  }
  if (issues.includes('duplicate_title')) {
    suggestions.push('Avoid duplicating the title. Add a unique value proposition or call-to-action.');
  }
  if (issues.includes('low_relevance') && relevancy.contextTopTokens.length) {
    suggestions.push(`Align the description with the page topic. Consider including: ${relevancy.contextTopTokens.join(', ')}.`);
  } else if (issues.includes('low_relevance')) {
    suggestions.push('Align the description with the primary topic of the page content.');
  }
  if (issues.includes('keyword_stuffing')) {
    suggestions.push('Reduce repeated terms and keep the phrasing natural.');
  }
  if (!suggestions.length && lengthInfo.status === 'acceptable') {
    suggestions.push(`Aim for the ideal range of ${lengthRules.idealMin}-${lengthRules.idealMax} characters for stronger SERP visibility.`);
  }

  const lengthSummary = lengthInfo.status === 'optimal'
    ? `Length is optimal (${lengthInfo.value} chars; target ${lengthRules.min}-${lengthRules.max}).`
    : `Length is ${lengthInfo.status.replace('_', ' ')} (${lengthInfo.value} chars; target ${lengthRules.min}-${lengthRules.max}).`;
  const relevanceSummary = `Relevance is ${relevancy.status} (score ${(relevancy.score * 100).toFixed(0)}%).`;
  const issueSummary = issues.length ? `Issues: ${issues.join(', ')}.` : 'No critical issues found.';

  return {
    success: true,
    metaDescription: cleanedDescription,
    analysis: {
      length: lengthInfo,
      language: {
        lang: language.lang,
        script,
        isCJK,
        source: language.source,
      },
      relevancy,
      issues,
      suggestions,
      source: extraction.source,
      candidates: extraction.candidates,
      aiSummary: `AI Analysis: ${lengthSummary} ${relevanceSummary} ${issueSummary}`,
    },
  };
};

module.exports = analyzeMetaDescription;
