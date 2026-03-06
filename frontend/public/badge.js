/**
 * badge.js - SEO Score Badge Embed Script
 * Task: TASK-33-35 - Performance Optimization and Launch
 * 
 * Lightweight embed script for displaying SEO score badges on third-party sites.
 * Size target: < 50KB gzip
 */

(function() {
  'use strict';
  
  var VERSION = '1.0.0';
  var BASE_URL = 'https://seoindicator.com';
  
  // Badge configuration defaults
  var defaultConfig = {
    size: 'medium',
    theme: 'light',
    refreshInterval: 86400000 // 24 hours
  };
  
  // Size dimensions
  var SIZES = {
    small: { width: 120, height: 40 },
    medium: { width: 150, height: 100 },
    large: { width: 300, height: 150 }
  };
  
  /**
   * Initialize all badges on the page
   */
  function init() {
    var containers = document.querySelectorAll('[data-seoindicator-badge]');
    for (var i = 0; i < containers.length; i++) {
      renderBadge(containers[i]);
    }
  }
  
  /**
   * Render a single badge in a container
   * @param {HTMLElement} container - The container element
   */
  function renderBadge(container) {
    var hash = container.getAttribute('data-seoindicator-badge');
    var size = container.getAttribute('data-size') || defaultConfig.size;
    var theme = container.getAttribute('data-theme') || defaultConfig.theme;
    
    // Validate size
    if (!SIZES[size]) {
      size = 'medium';
    }
    
    // Validate theme
    if (theme !== 'light' && theme !== 'dark') {
      theme = 'light';
    }
    
    // Create badge image
    var img = document.createElement('img');
    img.src = BASE_URL + '/badge/' + encodeURIComponent(hash) + '/' + size + '.svg?theme=' + theme;
    img.alt = 'SEO Score Badge';
    img.style.border = 'none';
    img.style.maxWidth = '100%';
    img.style.height = 'auto';
    
    // Track impression (async, non-blocking)
    trackImpression(hash);
    
    // Create link wrapper
    var link = document.createElement('a');
    link.href = BASE_URL + '/audit/' + encodeURIComponent(hash) + '/';
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.style.textDecoration = 'none';
    link.style.display = 'inline-block';
    
    // Track click on link
    link.addEventListener('click', function() {
      trackClick(hash, 'audit');
    });
    
    link.appendChild(img);
    
    // Clear container and append badge
    container.innerHTML = '';
    container.appendChild(link);
  }
  
  /**
   * Track badge impression
   * @param {string} hash - The public hash
   */
  function trackImpression(hash) {
    if (!navigator.sendBeacon) {
      return; // Beacon API not supported
    }
    
    var payload = {
      public_hash: hash,
      referrer_domain: getReferrerDomain(),
      timestamp: new Date().toISOString()
    };
    
    navigator.sendBeacon(
      BASE_URL + '/api/v1/badge/impression',
      JSON.stringify(payload)
    );
  }
  
  /**
   * Track badge click
   * @param {string} hash - The public hash
   * @param {string} target - Click target
   */
  function trackClick(hash, target) {
    if (!navigator.sendBeacon) {
      return;
    }
    
    var payload = {
      public_hash: hash,
      click_target: target,
      timestamp: new Date().toISOString()
    };
    
    navigator.sendBeacon(
      BASE_URL + '/api/v1/badge/click',
      JSON.stringify(payload)
    );
  }
  
  /**
   * Get the referrer domain
   * @returns {string|null} The referrer domain or null
   */
  function getReferrerDomain() {
    try {
      if (document.referrer) {
        var url = new URL(document.referrer);
        return url.hostname;
      }
    } catch (e) {
      // Invalid URL
    }
    return null;
  }
  
  /**
   * Get badge dimensions for a size
   * @param {string} size - Badge size
   * @returns {Object} Dimensions { width, height }
   */
  function getDimensions(size) {
    return SIZES[size] || SIZES.medium;
  }
  
  // Auto-initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
  // Expose public API
  window.SEOIndicatorBadge = {
    init: init,
    version: VERSION,
    render: renderBadge,
    getDimensions: getDimensions
  };
  
})();
