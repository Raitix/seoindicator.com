# FEAT-034: AI Visibility Tracking (Generative Engine Optimization)

## STATUS
`New Feature`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Track brand mentions and citations in AI-generated answers from ChatGPT, Perplexity, Claude, Google AI Overview, and other generative search engines. As traditional SEO traffic declines due to AI answering queries directly, GEO (Generative Engine Optimization) becomes essential. This feature positions SEOIndicator as a forward-looking platform that helps users understand their visibility in the new AI search landscape.

## User Story
As an SEO professional, I want to track how often my brand/keywords are mentioned in AI-generated answers so that I can optimize for the emerging AI search ecosystem and understand my true organic visibility beyond traditional rankings.

## Business Impact

### Revenue
- **Market differentiator**: Most SEO tools don't offer GEO tracking yet
- **Premium feature**: AI visibility tracking commands premium pricing
- **Future-proof**: "By mid-2026, every marketing team will track AI mentions" (Yahoo Finance)
- **New market**: Captures users looking for AI-era SEO tools
- **Traffic explanation**: Users losing traffic can now see if AI is answering their queries

### User Behavior
- **GA insight**: Only 1 organic session in 30 days (AI may be answering queries users would have searched for)
- **Zero-click reality**: Users get answers without visiting websites
- **New success metric**: Citation frequency > ranking position
- **Query shift**: AI queries are conversational (avg 23 words vs 4 words for Google)
- **Sub-query targeting**: AI breaks questions into multiple sub-queries

### Retention
- **Essential tracking**: Users need this data to stay competitive
- **Regular monitoring**: Weekly/monthly AI visibility reports drive recurring engagement
- **Competitive intelligence**: Compare brand mention rates vs competitors
- **Trend analysis**: Track AI visibility trends over time

### Legal & Compliance
- **API terms**: Must comply with AI provider terms of service
- **Rate limiting**: Respect query limits to avoid blocking
- **Data privacy**: No personal data in AI queries
- **Attribution**: Clear methodology for citation tracking

### Complexity & Cost
- **AI API calls**: Cost per query to check AI responses
- **Multiple platforms**: ChatGPT, Perplexity, Claude, Google AI Overview
- **Query variations**: Same question yields different answers (non-deterministic)
- **Caching essential**: Cache results to minimize API costs
- **Medium effort**: 2-3 weeks for MVP tracking

## Analytics Context
- **Organic sessions (30 days)**: 1 (critical - AI may be answering user queries)
- **Homepage bounce**: 59.6% (users may find answers elsewhere)
- **Dashboard bounce**: 0% (users who engage stay)
- **Tools engagement**: 411s on meta-tag-analyzer (users seek tools)

**Hypothesis**: Traditional SEO traffic is declining because AI answers queries directly. Users need to track AI visibility to understand their true organic presence.

## Acceptance Criteria
- [ ] User can add brand names and keywords to track for AI visibility
- [ ] System queries major AI platforms: ChatGPT, Perplexity, Claude, Google AI Overview
- [ ] Track citation frequency: How often brand/keyword appears in AI responses
- [ ] Track mention context: Is brand mentioned positively, neutrally, or with competitors?
- [ ] Show competitor comparison: Brand mention rate vs top 3 competitors
- [ ] Track query categories: Which types of queries trigger brand mentions
- [ ] Weekly AI visibility report with trend analysis
- [ ] Alert when mention rate changes significantly (+/- 20%)
- [ ] Export AI visibility data as CSV/JSON
- [ ] API endpoint for programmatic access
- [ ] Historical tracking: 90-day AI visibility history
- [ ] Sub-query analysis: Show which sub-queries lead to citations

## AI Platforms to Track (MVP)

| Platform | Priority | API Availability | Notes |
|----------|----------|------------------|-------|
| ChatGPT (OpenAI) | High | ✅ Available | Largest user base |
| Perplexity | High | ✅ Available | AI-native search |
| Google AI Overview | Medium | ⚠️ Limited | Requires workarounds |
| Claude (Anthropic) | Medium | ✅ Available | Growing user base |
| Bing Chat/Copilot | Low | ⚠️ Limited | Lower adoption |

## Key Metrics

### Citation Metrics
| Metric | Description | Target |
|--------|-------------|--------|
| Mention Rate | % of relevant queries where brand is cited | >10% |
| Share of Voice | Brand mentions vs competitor mentions | >25% |
| Citation Sentiment | Positive/neutral/negative context | >80% positive |
| Query Coverage | % of tracked queries where brand appears | >30% |

### Comparison Metrics
| Metric | Description |
|--------|-------------|
| vs Competitors | Mention rate compared to top 3 competitors |
| vs Last Period | Week-over-week change in visibility |
| vs Industry | Average mention rate for industry keywords |

## Technical Implementation

### Database Schema
```sql
-- AI Visibility Projects
ai_visibility_projects: id, user_id, brand_name, keywords, competitors, created_at

-- AI Query Results
ai_query_results: id, project_id, platform, query, response_text, brand_mentioned, competitor_mentions, sentiment, citations, queried_at

-- Mention Analytics
ai_mention_analytics: id, project_id, platform, mention_rate, share_of_voice, sentiment_score, period_start, period_end
```

### Query Strategy
```
User Query → Generate variations → Query AI platforms → Parse responses → Extract mentions → Store results
     ↓
Track: brand name, keywords, competitors, sentiment, citation links
```

### Query Categories (Sample)
| Category | Example Query |
|----------|---------------|
| Direct comparison | "What's the best SEO tool for small business?" |
| Feature inquiry | "How does SEOIndicator compare to Ahrefs?" |
| Use case | "What tool should I use to track keyword rankings?" |
| Recommendation | "Can you recommend an affordable SEO platform?" |

## Free Tools Constraint Compliance

**Challenge**: This feature requires AI API calls which have costs.

**Mitigation Strategies**:
1. **Freemium model**: Free tier gets 5 queries/month, paid gets unlimited
2. **Caching**: Cache results for 7 days to reduce API calls
3. **Batch processing**: Run queries in off-peak hours
4. **Estimated cost**: ~$0.01-0.03 per query (OpenAI GPT-4o-mini)
5. **OpenRouter integration**: Use free-tier models where possible

**Alternative Free Approach (MVP)**:
- Use OpenRouter free tier (limited models)
- Manual refresh (no automatic polling)
- Weekly snapshot only (not daily)

## GEO Optimization Recommendations

Based on Princeton GEO research, provide actionable recommendations:

| Optimization | Impact | Implementation |
|--------------|--------|----------------|
| Add authoritative quotes | +40% citations | Include expert quotes in content |
| Use statistics/data | +35% citations | Add verifiable data points |
| Structure content clearly | +30% citations | Use headings, lists, tables |
| Include citations/sources | +25% citations | Reference authoritative sources |
| Answer "what is" queries | +20% citations | Define terms clearly |
| Use conversational language | +15% citations | Match natural language queries |

## Competitor Comparison

| Tool | GEO Tracking | AI Visibility | Citation Analytics |
|------|--------------|---------------|-------------------|
| Ahrefs | ❌ | ❌ | ❌ |
| SEMrush | ❌ | ❌ | ❌ |
| Moz | ❌ | ❌ | ❌ |
| Brandi AI | ✅ | ✅ | ✅ |
| LLMrefs | ✅ | ✅ | ❌ |
| SEOIndicator | **✅ (proposed)** | **✅ (proposed)** | **✅ (proposed)** |

**Competitive Advantage**: Most traditional SEO tools don't have GEO. Specialized GEO tools exist but don't have traditional SEO features. We combine both.

## MVP Scope

**Phase 1 (MVP):**
- Track brand mentions in ChatGPT and Perplexity
- Weekly mention rate report
- Competitor comparison (up to 3)
- Dashboard visualization

**Phase 2:**
- Add Claude and Google AI Overview
- Sentiment analysis
- Query category breakdown
- Alert notifications

**Phase 3:**
- GEO optimization recommendations
- Citation opportunity finder
- Share of voice trends
- API access

## Success Metrics

| Metric | Current | Target (30 days) | Target (90 days) |
|--------|---------|------------------|------------------|
| Projects created | 0 | 20 | 100 |
| Queries tracked | 0 | 200 | 1,000 |
| Mention events captured | 0 | 100 | 500 |
| Premium upgrades | 0 | 5 | 25 |

## Why This Feature Now

1. **Market timing**: GEO is trending in 2026, early adopter advantage
2. **Traffic decline explanation**: Users losing traffic need to understand AI's impact
3. **Differentiation**: Most SEO tools don't have this yet
4. **Future-proof**: "Every marketing team will track AI mentions by mid-2026"
5. **Complements existing features**: Rankings + AI visibility = complete picture

## Dependencies
- OpenAI API or OpenRouter integration
- Perplexity API access
- Existing user authentication system

## Related
- Complements: FEAT-001 (Keyword Rank Tracking)
- Complements: FEAT-002 (Ranking Change Alerts)
- Related: FEAT-020 (SEO Guides - add GEO guide)

## Notes

### Industry Context
Traditional SEO is not dead, but it's evolving. AI models use live web search, so traditional SEO still matters. But users now need to track their presence in AI-generated answers, not just Google rankings.

### Query Fan-Out Strategy
When AI receives a query, it breaks it into sub-queries. For example:
- User: "What's the best SEO tool for a remote team?"
- AI sub-queries: "best SEO tool 2026", "SEO tool remote team", "SEO tool collaboration features"

Optimizing for sub-queries increases citation probability.

### Non-Deterministic Nature
LLMs are non-deterministic - same question yields different answers. This means:
- No fixed "position #1" like Google
- Track mention frequency across multiple queries
- Report averages, not single data points
- Focus on trends, not snapshots

---

*Created by Innovator agent on 2026-03-07*
