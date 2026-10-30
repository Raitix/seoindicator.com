/**
 * Video Detector
 * TASK-7-7: Detects video results in SERP
 * 
 * Video results appear as video thumbnails in search results.
 * Supports multiple platforms: YouTube, Vimeo, self-hosted, etc.
 */

import { extractHostname, normalizeHostname, isValidSerpObject } from './url-utils.js';

/**
 * Detect video results in SERP.
 * @param {Object} serp - SERP result object
 * @param {string} targetUrl - The target URL to match against
 * @returns {Object} SERPFeature object with type, isPresent, and optional fields
 */
export function detectVideo(serp, targetUrl) {
  // Default return structure
  const defaultResult = {
    type: 'video',
    isPresent: false
  };
  
  // Validate SERP object
  if (!isValidSerpObject(serp)) {
    return defaultResult;
  }
  
  // Check for videos data
  const videos = serp.videos;
  
  // Validate videos is an array with content
  if (!videos || !Array.isArray(videos) || videos.length === 0) {
    return defaultResult;
  }
  
  // Extract target domain for matching
  const targetHostname = extractHostname(targetUrl);
  if (!targetHostname) {
    return defaultResult;
  }
  
  const normalizedTarget = normalizeHostname(targetHostname);
  
  // Search for target URL in video entries
  for (let i = 0; i < videos.length; i++) {
    const video = videos[i];
    
    // Skip entries without valid object
    if (!video || typeof video !== 'object') {
      continue;
    }
    
    const videoUrl = video.url;
    if (!videoUrl || typeof videoUrl !== 'string' || videoUrl.trim() === '') {
      continue;
    }
    
    // Extract and compare domains
    const videoHostname = extractHostname(videoUrl);
    if (videoHostname && normalizeHostname(videoHostname) === normalizedTarget) {
      // Found match! Position is 1-indexed
      const result = {
        type: 'video',
        isPresent: true,
        position: i + 1
      };
      
      // Include optional fields if present
      if (video.title && typeof video.title === 'string') {
        result.title = video.title;
      }
      
      if (video.duration && typeof video.duration === 'string') {
        result.duration = video.duration;
      }
      
      if (video.thumbnail && typeof video.thumbnail === 'string') {
        result.thumbnail = video.thumbnail;
      }
      
      if (video.platform && typeof video.platform === 'string') {
        result.platform = video.platform;
      }
      
      result.url = videoUrl;
      
      return result;
    }
  }
  
  // Target URL not found in videos
  return defaultResult;
}

/**
 * Extract all video entries with their data.
 * Useful for competitor analysis.
 * @param {Object} serp - SERP result object
 * @returns {Array} Array of video entries
 */
export function getVideoEntries(serp) {
  if (!isValidSerpObject(serp)) {
    return [];
  }
  
  const videos = serp.videos;
  
  if (!videos || !Array.isArray(videos)) {
    return [];
  }
  
  return videos
    .filter(video => video && typeof video === 'object')
    .map((video, index) => ({
      position: index + 1,
      url: video.url || null,
      title: video.title || null,
      duration: video.duration || null,
      thumbnail: video.thumbnail || null,
      platform: video.platform || detectPlatform(video.url)
    }));
}

/**
 * Detect video platform from URL.
 * @param {string} url - Video URL
 * @returns {string|null} Platform name or null
 */
export function detectPlatform(url) {
  if (!url || typeof url !== 'string') {
    return null;
  }
  
  const hostname = extractHostname(url);
  if (!hostname) {
    return null;
  }
  
  const normalized = normalizeHostname(hostname);
  
  if (normalized.includes('youtube.com') || normalized.includes('youtu.be')) {
    return 'youtube';
  }
  
  if (normalized.includes('vimeo.com')) {
    return 'vimeo';
  }
  
  if (normalized.includes('dailymotion.com')) {
    return 'dailymotion';
  }
  
  if (normalized.includes('twitch.tv')) {
    return 'twitch';
  }
  
  return 'self';
}

// Re-export utilities for tests
export { extractHostname, normalizeHostname } from './url-utils.js';
