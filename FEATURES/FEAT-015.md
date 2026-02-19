# FEAT-015: White-Label Customization

## STATUS
`New Feature`

## PRIORITY
`Low`

## RISK
`Low`

## Description
Allow agencies and resellers to customize SEOIndicator with their own branding, colors, domain, and logo. This transforms SEOIndicator into a white-label platform that agencies can offer as their own SEO tool to clients, creating additional revenue streams and professional presentation.

## User Story
As an agency owner, I want to customize SEOIndicator with my agency's branding so that I can offer it to clients as my own SEO platform, strengthening client relationships and creating an additional revenue stream.

## Business Impact
### Revenue
- **Enterprise tier exclusive**: White-label is typically the highest-tier feature ($$$)
- **Reseller opportunity**: Agencies can resell access at their own price points
- **Value multiplier**: Agencies pay premium for brandable tools

### User Behavior
- **Brand consistency**: Clients see agency branding, not SEOIndicator branding
- **Professional presentation**: Custom domain (seo.agencyname.com) builds trust
- **Revenue stream**: Agencies can charge clients for access

### Retention
- **Agency lock-in**: Once branded and client-facing, switching is extremely disruptive
- **Client-facing value**: Becomes part of agency's service offering
- **Brand investment**: Custom branding creates emotional ownership

### Legal & Compliance
- **Branding rights**: Users must own rights to uploaded logos and brand assets
- **Terms update**: White-label terms must clarify responsibilities
- **Data ownership**: Clear policies on who owns client data

### Complexity & Cost
- **Multi-tenancy**: Support multiple brand configurations per account
- **Custom domains**: DNS configuration, SSL certificates (can use Let's Encrypt)
- **Email customization**: Custom sender domain for alerts/reports
- **Low-medium effort**: 1-2 weeks for core white-label features

## Acceptance Criteria
- [ ] User can upload custom logo (replaces SEOIndicator logo)
- [ ] User can set custom brand name (replaces "SEOIndicator" text)
- [ ] User can customize primary/secondary brand colors
- [ ] User can set custom domain (e.g., seo.agencyname.com)
- [ ] System provisions SSL for custom domain automatically
- [ ] Login page shows user's branding (not SEOIndicator)
- [ ] Email notifications sent from user's domain (not seoindicator.com)
- [ ] PDF reports use user's branding (logo, colors, name)
- [ ] User can set custom footer text and links
- [ ] User can hide "Powered by SEOIndicator" attribution
- [ ] User can customize email templates (welcome, alert, report)
- [ ] Preview mode shows how branding will appear
- [ ] User can reset to default SEOIndicator branding

## Related
- Specification: (To be created by Architect)
- Tasks: (To be created by Developer)
- Enhances: FEAT-004 (branded reports), FEAT-011 (team access)

## Notes
### White-Label Customization Options
| Element | Customizable | Notes |
|---------|--------------|-------|
| Logo | ✅ | PNG/SVG, max 500KB |
| Brand Name | ✅ | Replaces all "SEOIndicator" text |
| Primary Color | ✅ | Buttons, links, highlights |
| Secondary Color | ✅ | Accents, backgrounds |
| Favicon | ✅ | Browser tab icon |
| Login Background | ✅ | Custom image or color |
| Custom Domain | ✅ | Requires DNS configuration |
| Email Sender | ✅ | alerts@yourdomain.com |
| Footer Text | ✅ | Custom links, copyright |
| "Powered by" | ✅ | Can be hidden |

### Technical Implementation
```
Custom Domain Setup:
1. User enters domain (seo.agency.com)
2. System generates DNS instructions
3. User adds CNAME record
4. System verifies DNS
5. System provisions SSL (Let's Encrypt)
6. Domain goes live with user's branding
```

### Database Schema
```sql
-- Brand configurations
brand_configs: id, account_id, logo_url, brand_name, primary_color, secondary_color, favicon_url, login_background_url, footer_text, hide_powered_by, custom_email_from, created_at, updated_at

-- Custom domains
custom_domains: id, account_id, domain, dns_verified, ssl_provisioned, created_at, verified_at
```

### Subscription Tier Access
| Plan | White-Label | Custom Domain | Hide Attribution |
|------|-------------|---------------|------------------|
| Free | ❌ | ❌ | ❌ |
| Starter | ❌ | ❌ | ❌ |
| Pro | Logo + Colors only | ❌ | ❌ |
| Enterprise | ✅ Full | ✅ | ✅ |

### Email Branding
```
Default: alerts@seoindicator.com
Custom:  alerts@agency.com

Subject: [BrandName] Keyword Alert: "keyword" dropped 5 positions
From:    Agency Name <alerts@agency.com>
```

### MVP Scope
- Custom logo upload
- Custom brand name
- Custom primary/secondary colors
- Basic preview mode
- No custom domain (Phase 2)
- No custom email sender (Phase 2)
- No hide attribution (Phase 2)

---

## Comments
_Agent communication log_

### [2026-02-19 23:59] Agent: Innovator
Created as fifteenth feature. White-label customization is the ultimate agency feature - it allows agencies to present SEOIndicator as their own tool, creating brand consistency and potential resale revenue. This is a significant revenue multiplier for the enterprise tier.

### [2026-02-19 23:59] Agent: Innovator
Priority Low because it's an enterprise-focused enhancement, not essential for core functionality. Risk Low due to straightforward implementation (asset uploads, configuration storage, template rendering). The ROI on this feature is high for agency acquisition and retention.
