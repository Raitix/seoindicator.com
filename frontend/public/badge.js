/**
 * badge.js - SEO Score Badge Embed Script
 * Version: 0.1.0
 * (c) SEOIndicator
 * Released under the MIT License
 */
"use strict";
var SEOIndicatorBadge = (() => {
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

  // lib/scripts/badge.ts
  var badge_exports = {};
  __export(badge_exports, {
    VERSION: () => VERSION,
    default: () => badge_default,
    destroy: () => destroy,
    init: () => init,
    noConflict: () => noConflict,
    refresh: () => refresh,
    setTheme: () => setTheme,
    version: () => version
  });
  var VERSION = "1.0.0";
  var DEFAULT_BASE_URL = "https://seoindicator.com";
  var DEFAULT_REFRESH_INTERVAL = 864e5;
  var MAX_RETRY_ATTEMPTS = 3;
  var RETRY_DELAYS = [1e3, 2e3, 4e3];
  var STORAGE_PREFIX = "seoindicator_";
  var SIZES = {
    small: { width: 120, height: 40 },
    medium: { width: 150, height: 100 },
    large: { width: 300, height: 150 }
  };
  var THEMES = ["light", "dark"];
  var SCORE_COLORS = {
    excellent: { light: "#22c55e", dark: "#16a34a" },
    // 90-100 green
    good: { light: "#3b82f6", dark: "#2563eb" },
    // 70-89 blue
    average: { light: "#eab308", dark: "#ca8a04" },
    // 50-69 yellow
    poor: { light: "#f97316", dark: "#ea580c" },
    // 30-49 orange
    bad: { light: "#ef4444", dark: "#dc2626" }
    // 0-29 red
  };
  function getScoreCategory(score) {
    if (score >= 90) return "excellent";
    if (score >= 70) return "good";
    if (score >= 50) return "average";
    if (score >= 30) return "poor";
    return "bad";
  }
  function getScoreColor(score, theme) {
    var _a;
    const category = getScoreCategory(score);
    return ((_a = SCORE_COLORS[category]) == null ? void 0 : _a[theme]) || SCORE_COLORS.average[theme];
  }
  function getReferrerDomain() {
    try {
      if (typeof document !== "undefined" && document.referrer) {
        const url = new URL(document.referrer);
        return url.hostname;
      }
    } catch (e) {
    }
    return null;
  }
  function sanitizeForDisplay(str) {
    if (!str || typeof str !== "string") return "";
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#x27;");
  }
  function validateAuditId(id) {
    if (!id || typeof id !== "string") return false;
    const trimmed = id.trim();
    return trimmed.length > 0 && trimmed.length <= 256;
  }
  function normalizeSize(size) {
    if (!size || typeof size !== "string") return "medium";
    const trimmed = size.trim().toLowerCase();
    if (trimmed in SIZES) return trimmed;
    return "medium";
  }
  function normalizeTheme(theme) {
    if (!theme || typeof theme !== "string") return "light";
    const trimmed = theme.trim().toLowerCase();
    if (THEMES.includes(trimmed)) return trimmed;
    return "light";
  }
  function truncateUrl(url, maxLength = 40) {
    if (!url || typeof url !== "string") return "";
    if (url.length <= maxLength) return url;
    return url.substring(0, maxLength - 3) + "...";
  }
  function isLocalStorageAvailable() {
    try {
      if (typeof localStorage === "undefined") return false;
      const testKey = "__seoindicator_test__";
      localStorage.setItem(testKey, "1");
      localStorage.removeItem(testKey);
      return true;
    } catch (e) {
      return false;
    }
  }
  function getLastCheckKey(auditId) {
    return `${STORAGE_PREFIX}${auditId}_lastCheck`;
  }
  function getLastScoreKey(auditId) {
    return `${STORAGE_PREFIX}${auditId}_lastScore`;
  }
  function storeLastCheckTime(auditId) {
    try {
      if (!isLocalStorageAvailable()) return;
      localStorage.setItem(getLastCheckKey(auditId), Date.now().toString());
    } catch (e) {
    }
  }
  function storeLastScore(auditId, score) {
    try {
      if (!isLocalStorageAvailable()) return;
      localStorage.setItem(getLastScoreKey(auditId), score.toString());
    } catch (e) {
    }
  }
  function getLastCheckTime(auditId) {
    try {
      if (!isLocalStorageAvailable()) return null;
      const stored = localStorage.getItem(getLastCheckKey(auditId));
      return stored ? parseInt(stored, 10) : null;
    } catch (e) {
      return null;
    }
  }
  function getLastScore(auditId) {
    try {
      if (!isLocalStorageAvailable()) return null;
      const stored = localStorage.getItem(getLastScoreKey(auditId));
      return stored ? parseInt(stored, 10) : null;
    } catch (e) {
      return null;
    }
  }
  function shouldRefresh(auditId, refreshInterval) {
    if (refreshInterval <= 0) return false;
    const lastCheck = getLastCheckTime(auditId);
    if (lastCheck === null) return true;
    const elapsed = Date.now() - lastCheck;
    return elapsed >= refreshInterval;
  }
  function hasScoreChanged(auditId, newScore) {
    const lastScore = getLastScore(auditId);
    if (lastScore === null) return true;
    return lastScore !== newScore;
  }
  function prefersReducedMotion() {
    if (typeof window === "undefined" || !window.matchMedia) return false;
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }
  async function fetchBadgeConfig(auditId, baseUrl, retryCount = 0) {
    const url = `${baseUrl}/api/v1/public-audit/${encodeURIComponent(auditId)}/badge`;
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 1e4);
      const response = await fetch(url, {
        method: "GET",
        headers: {
          "Accept": "application/json"
        },
        signal: controller.signal,
        credentials: "omit"
        // No cookies
      });
      clearTimeout(timeoutId);
      if (!response.ok) {
        if (response.status === 404) {
          return { success: false, error: "Audit not found" };
        }
        throw new Error(`HTTP ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      if (retryCount < MAX_RETRY_ATTEMPTS) {
        await new Promise((resolve) => setTimeout(resolve, RETRY_DELAYS[retryCount]));
        return fetchBadgeConfig(auditId, baseUrl, retryCount + 1);
      }
      return {
        success: false,
        error: error instanceof Error ? error.message : "Network error"
      };
    }
  }
  function trackImpression(instance) {
    var _a;
    if (instance.config.noTracking || instance.impressionTracked || instance.destroyed) {
      return;
    }
    instance.impressionTracked = true;
    const endpoint = ((_a = instance.data) == null ? void 0 : _a.impressions_endpoint) || `${instance.config.apiBaseUrl}/api/v1/public-audit/${instance.config.auditId}/impression`;
    const payload = {
      public_hash: instance.config.auditId,
      referrer_domain: getReferrerDomain(),
      timestamp: (/* @__PURE__ */ new Date()).toISOString()
    };
    if (typeof navigator !== "undefined" && navigator.sendBeacon) {
      navigator.sendBeacon(endpoint, JSON.stringify(payload));
    }
  }
  function trackClick(instance, target) {
    var _a;
    if (instance.config.noTracking || instance.destroyed) {
      return;
    }
    const endpoint = ((_a = instance.data) == null ? void 0 : _a.clicks_endpoint) || `${instance.config.apiBaseUrl}/api/v1/public-audit/${instance.config.auditId}/click`;
    const payload = {
      public_hash: instance.config.auditId,
      click_target: target,
      timestamp: (/* @__PURE__ */ new Date()).toISOString()
    };
    if (typeof navigator !== "undefined" && navigator.sendBeacon) {
      navigator.sendBeacon(endpoint, JSON.stringify(payload));
    }
  }
  function renderLoadingState(container, size) {
    const dims = SIZES[size];
    container.innerHTML = `
    <div style="
      width: ${dims.width}px;
      height: ${dims.height}px;
      background: #f3f4f6;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    ">
      <div style="
        width: 24px;
        height: 24px;
        border: 2px solid #e5e7eb;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: seoindicator-spin 1s linear infinite;
      "></div>
    </div>
    <style>
      @keyframes seoindicator-spin {
        to { transform: rotate(360deg); }
      }
    </style>
  `;
  }
  function renderErrorState(container, size, theme) {
    const dims = SIZES[size];
    const bgColor = theme === "dark" ? "#1f2937" : "#fef2f2";
    const textColor = theme === "dark" ? "#9ca3af" : "#6b7280";
    container.innerHTML = `
    <div style="
      width: ${dims.width}px;
      height: ${dims.height}px;
      background: ${bgColor};
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: ${textColor};
      font-family: system-ui, -apple-system, sans-serif;
      font-size: 12px;
    ">
      Badge unavailable
    </div>
  `;
  }
  function renderBadge(instance, previousScore) {
    const { container, data, config } = instance;
    if (!data) return;
    const size = config.size;
    const theme = config.theme;
    const dims = SIZES[size];
    const scoreColor = getScoreColor(data.score, theme);
    const bgColor = theme === "dark" ? "#1a1a1a" : "#ffffff";
    const textColor = theme === "dark" ? "#ffffff" : "#1f2937";
    const subtextColor = theme === "dark" ? "#9ca3af" : "#6b7280";
    const borderColor = theme === "dark" ? "#374151" : "#e5e7eb";
    const displayUrl = truncateUrl(sanitizeForDisplay(data.site_url));
    const shouldAnimate = previousScore !== void 0 && !prefersReducedMotion();
    const badgeHtml = `
    <a href="${sanitizeForDisplay(data.audit_url)}"
       target="_blank"
       rel="noopener noreferrer"
       style="
         display: inline-block;
         text-decoration: none;
         font-family: system-ui, -apple-system, sans-serif;
       "
       data-seoindicator-link="true"
    >
      <div style="
        width: ${dims.width}px;
        height: ${dims.height}px;
        background: ${bgColor};
        border: 1px solid ${borderColor};
        border-radius: 8px;
        padding: 8px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        transition: box-shadow 0.2s;
      " onmouseover="this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
        <div id="seoindicator-score-${config.auditId}" style="
          font-size: ${size === "large" ? "32px" : size === "medium" ? "24px" : "18px"};
          font-weight: 700;
          color: ${scoreColor};
          ${shouldAnimate ? "transition: transform 0.3s ease-out, opacity 0.3s ease-out;" : ""}
        ">
          ${data.score}
        </div>
        ${size !== "small" ? `
          <div style="
            font-size: 11px;
            color: ${subtextColor};
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          ">
            ${displayUrl}
          </div>
        ` : ""}
        ${size === "large" ? `
          <div style="
            font-size: 10px;
            color: ${subtextColor};
          ">
            SEO Score
          </div>
        ` : ""}
      </div>
    </a>
    <div style="
      font-size: 10px;
      color: ${subtextColor};
      text-align: center;
      margin-top: 4px;
    ">
      Powered by <a href="${config.apiBaseUrl}" target="_blank" rel="noopener" style="color: ${scoreColor}">SEOIndicator</a>
    </div>
  `;
    container.innerHTML = badgeHtml;
    if (shouldAnimate) {
      const scoreEl = document.getElementById(`seoindicator-score-${config.auditId}`);
      if (scoreEl) {
        requestAnimationFrame(() => {
          scoreEl.style.transform = "scale(1.2)";
          scoreEl.style.opacity = "0.8";
          requestAnimationFrame(() => {
            setTimeout(() => {
              scoreEl.style.transform = "scale(1)";
              scoreEl.style.opacity = "1";
            }, 150);
          });
        });
      }
    }
    const link = container.querySelector("[data-seoindicator-link]");
    if (link) {
      link.addEventListener("click", () => {
        trackClick(instance, config.onClickTarget || "audit");
      });
    }
  }
  var instances = /* @__PURE__ */ new Map();
  var autoInitialized = false;
  function createInstance(config) {
    var _a, _b, _c;
    if (!validateAuditId(config.auditId)) {
      console.error("[SEOIndicatorBadge] Invalid audit ID");
      return null;
    }
    let container = null;
    if (config.container) {
      if (typeof config.container === "string") {
        container = document.querySelector(config.container);
      } else {
        container = config.container;
      }
    } else {
      container = document.querySelector(`[data-seoindicator-badge="${config.auditId}"]`);
    }
    if (!container) {
      console.error("[SEOIndicatorBadge] Container not found");
      return null;
    }
    const existing = instances.get(config.auditId);
    if (existing && !existing.destroyed) {
      return existing;
    }
    const normalizedConfig = {
      auditId: config.auditId.trim(),
      size: normalizeSize(config.size),
      theme: normalizeTheme(config.theme),
      container: config.container || "",
      refreshInterval: (_a = config.refreshInterval) != null ? _a : DEFAULT_REFRESH_INTERVAL,
      apiBaseUrl: config.apiBaseUrl || DEFAULT_BASE_URL,
      noTracking: (_b = config.noTracking) != null ? _b : false,
      onClickTarget: (_c = config.onClickTarget) != null ? _c : "audit"
    };
    const instance = {
      config: normalizedConfig,
      data: null,
      container,
      refreshTimer: null,
      observer: null,
      impressionTracked: false,
      destroyed: false
    };
    instances.set(instance.config.auditId, instance);
    return instance;
  }
  function setupIntersectionObserver(instance) {
    if (typeof IntersectionObserver === "undefined") {
      trackImpression(instance);
      return;
    }
    instance.observer = new IntersectionObserver(
      (entries) => {
        var _a;
        for (const entry of entries) {
          if (entry.isIntersecting && !instance.impressionTracked) {
            trackImpression(instance);
            (_a = instance.observer) == null ? void 0 : _a.disconnect();
          }
        }
      },
      { threshold: 0.5 }
    );
    instance.observer.observe(instance.container);
  }
  function setupAutoRefresh(instance) {
    if (instance.config.refreshInterval <= 0 || instance.destroyed) {
      return;
    }
    if (instance.refreshTimer) {
      clearTimeout(instance.refreshTimer);
    }
    storeLastCheckTime(instance.config.auditId);
    instance.refreshTimer = window.setTimeout(() => {
      if (!instance.destroyed) {
        if (shouldRefresh(instance.config.auditId, instance.config.refreshInterval)) {
          loadBadge(instance.config.auditId);
        } else {
          setupAutoRefresh(instance);
        }
      }
    }, instance.config.refreshInterval);
  }
  async function loadBadge(auditId) {
    var _a, _b;
    const instance = instances.get(auditId);
    if (!instance || instance.destroyed) return;
    const { config, container } = instance;
    const previousScore = (_b = (_a = instance.data) == null ? void 0 : _a.score) != null ? _b : getLastScore(auditId);
    renderLoadingState(container, config.size);
    const response = await fetchBadgeConfig(auditId, config.apiBaseUrl);
    if (instance.destroyed) return;
    if (!response.success || !response.data) {
      renderErrorState(container, config.size, config.theme);
      return;
    }
    const scoreChanged = hasScoreChanged(auditId, response.data.score);
    const shouldAnimate = scoreChanged && previousScore !== null && !prefersReducedMotion();
    instance.data = response.data;
    storeLastScore(auditId, response.data.score);
    storeLastCheckTime(auditId);
    renderBadge(instance, shouldAnimate ? previousScore : void 0);
    setupIntersectionObserver(instance);
    setupAutoRefresh(instance);
  }
  function destroyInstance(auditId) {
    const instance = instances.get(auditId);
    if (!instance) return;
    instance.destroyed = true;
    if (instance.refreshTimer) {
      clearTimeout(instance.refreshTimer);
      instance.refreshTimer = null;
    }
    if (instance.observer) {
      instance.observer.disconnect();
      instance.observer = null;
    }
    instance.container.innerHTML = "";
    instances.delete(auditId);
  }
  function init(config) {
    const instance = createInstance(config);
    if (instance) {
      loadBadge(instance.config.auditId);
    }
  }
  function refresh(auditId) {
    if (auditId) {
      loadBadge(auditId);
    } else {
      for (const id of instances.keys()) {
        loadBadge(id);
      }
    }
  }
  function setTheme(theme, auditId) {
    const normalizedTheme = normalizeTheme(theme);
    if (auditId) {
      const instance = instances.get(auditId);
      if (instance && instance.data) {
        instance.config.theme = normalizedTheme;
        renderBadge(instance);
      }
    } else {
      for (const instance of instances.values()) {
        if (instance.data) {
          instance.config.theme = normalizedTheme;
          renderBadge(instance);
        }
      }
    }
  }
  function destroy() {
    for (const auditId of instances.keys()) {
      destroyInstance(auditId);
    }
    autoInitialized = false;
  }
  var _previousSEOIndicatorBadge = null;
  function noConflict() {
    if (typeof window !== "undefined") {
      window.SEOIndicatorBadge = _previousSEOIndicatorBadge;
    }
    return api;
  }
  function autoInit() {
    if (typeof window !== "undefined" && window.SEOINDICATOR_NO_AUTO_INIT) {
      return;
    }
    if (autoInitialized) return;
    autoInitialized = true;
    const containers = document.querySelectorAll("[data-seoindicator-badge]");
    for (let i = 0; i < containers.length; i++) {
      const container = containers[i];
      const auditId = container.getAttribute("data-seoindicator-badge");
      if (!auditId) continue;
      const size = container.getAttribute("data-size") || void 0;
      const theme = container.getAttribute("data-theme") || void 0;
      const refreshAttr = container.getAttribute("data-refresh");
      const refreshInterval = refreshAttr ? parseInt(refreshAttr, 10) * 1e3 : void 0;
      init({
        auditId,
        size,
        theme,
        refreshInterval,
        container
      });
    }
  }
  var api = {
    init,
    refresh,
    setTheme,
    destroy,
    noConflict,
    version: VERSION
  };
  if (typeof window !== "undefined") {
    _previousSEOIndicatorBadge = window.SEOIndicatorBadge;
    window.SEOIndicatorBadge = api;
    if (typeof document !== "undefined") {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", autoInit);
      } else {
        autoInit();
      }
    }
  }
  var version = VERSION;
  var badge_default = api;
  return __toCommonJS(badge_exports);
})();
// Auto-init on DOM ready
if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      if (window.SEOIndicatorBadge && window.SEOIndicatorBadge.init) {
        window.SEOIndicatorBadge.init();
      }
    });
  }
}
