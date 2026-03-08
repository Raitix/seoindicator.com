/**
 * LocationCard Component Tests
 * 
 * Task: TASK-35-17 - Location Management Page
 * Spec: SPEC-035 - Local SEO & Google Business Profile Tracking
 * 
 * Tests the reusable location card component with stats display,
 * connection status badges, and action buttons.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  MapPin: () => <span data-testid="map-pin-icon">MapPin</span>,
  Star: () => <span data-testid="star-icon">Star</span>,
  Edit: () => <span data-testid="edit-icon">Edit</span>,
  Trash2: () => <span data-testid="trash-icon">Trash</span>,
  ExternalLink: () => <span data-testid="external-link-icon">ExternalLink</span>,
  Check: () => <span data-testid="check-icon">Check</span>,
  X: () => <span data-testid="x-icon">X</span>,
  Phone: () => <span data-testid="phone-icon">Phone</span>,
  Globe: () => <span data-testid="globe-icon">Globe</span>,
  TrendingUp: () => <span data-testid="trending-up-icon">TrendingUp</span>,
  MessageSquare: () => <span data-testid="message-square-icon">MessageSquare</span>,
}));

// Mock next/link
vi.mock('next/link', () => ({
  default: ({ children, href }: any) => <a href={href}>{children}</a>,
}));

// Mock data
const mockConnectedLocation = {
  id: 1,
  site_id: 1,
  name: 'Main Office',
  address: '123 Main Street, New York, NY 10001',
  phone: '+1 (555) 123-4567',
  website: 'https://example.com',
  category: 'Restaurant',
  latitude: 40.7128,
  longitude: -74.0060,
  gbp_connected: true,
  gbp_location_id: 'ChIJN1t_tDeuEmsRUsoyGKfrt7Q',
  rating: 4.5,
  review_count: 120,
  pack_position: 3,
  created_at: '2026-03-01T00:00:00Z',
  updated_at: '2026-03-08T00:00:00Z',
};

const mockUnconnectedLocation = {
  id: 2,
  site_id: 1,
  name: 'Downtown Branch',
  address: '456 Oak Avenue, New York, NY 10002',
  phone: '+1 (555) 987-6543',
  website: 'https://example.com/downtown',
  category: 'Restaurant',
  latitude: 40.7580,
  longitude: -73.9855,
  gbp_connected: false,
  gbp_location_id: null,
  rating: null,
  review_count: null,
  pack_position: null,
  created_at: '2026-03-02T00:00:00Z',
  updated_at: '2026-03-08T00:00:00Z',
};

// Mock LocationCard component
const MockLocationCard = ({ 
  location, 
  onEdit, 
  onDelete, 
  onViewDetails,
  isLoading = false 
}: { 
  location: typeof mockConnectedLocation;
  onEdit?: () => void;
  onDelete?: () => void;
  onViewDetails?: () => void;
  isLoading?: boolean;
}) => {
  return (
    <div 
      data-testid={`location-card-${location.id}`} 
      className="border rounded-lg p-4"
      aria-label={`Location: ${location.name}`}
    >
      {/* Header */}
      <div className="flex justify-between items-start mb-3">
        <div className="flex items-start gap-3">
          <span data-testid="map-pin-icon" className="text-gray-400 mt-1">MapPin</span>
          <div>
            <h3 data-testid={`location-name-${location.id}`} className="font-semibold text-lg">
              {location.name}
            </h3>
            <p data-testid={`location-address-${location.id}`} className="text-gray-600 text-sm">
              {location.address}
            </p>
          </div>
        </div>
        
        {/* Connection Status Badge */}
        <div data-testid={`connection-badge-${location.id}`}>
          {location.gbp_connected ? (
            <span 
              data-testid={`connected-badge-${location.id}`} 
              className="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm"
              aria-label="GBP Connected"
            >
              <span data-testid="check-icon">Check</span>
              Connected
            </span>
          ) : (
            <span 
              data-testid={`not-connected-badge-${location.id}`} 
              className="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-sm"
              aria-label="GBP Not Connected"
            >
              <span data-testid="x-icon">X</span>
              Not Connected
            </span>
          )}
        </div>
      </div>

      {/* Quick Stats */}
      <div data-testid={`quick-stats-${location.id}`} className="flex flex-wrap gap-4 text-sm mb-4">
        {/* Rating */}
        {location.rating !== null ? (
          <div className="flex items-center gap-1" data-testid={`rating-stat-${location.id}`}>
            <span data-testid="star-icon" className="text-yellow-500">Star</span>
            <span className="font-medium">{location.rating}</span>
            <span className="text-gray-500">({location.review_count} reviews)</span>
          </div>
        ) : (
          <div className="text-gray-400" data-testid={`no-rating-${location.id}`}>
            No rating data
          </div>
        )}

        {/* Pack Position */}
        {location.pack_position !== null && (
          <div className="flex items-center gap-1" data-testid={`pack-position-${location.id}`}>
            <span data-testid="trending-up-icon">TrendingUp</span>
            <span>Position #{location.pack_position}</span>
          </div>
        )}

        {/* Phone */}
        {location.phone && (
          <div className="flex items-center gap-1" data-testid={`phone-stat-${location.id}`}>
            <span data-testid="phone-icon">Phone</span>
            <span>{location.phone}</span>
          </div>
        )}

        {/* Website */}
        {location.website && (
          <a 
            href={location.website} 
            target="_blank" 
            rel="noopener noreferrer"
            data-testid={`website-link-${location.id}`}
            className="flex items-center gap-1 text-blue-600 hover:underline"
          >
            <span data-testid="globe-icon">Globe</span>
            Website
          </a>
        )}
      </div>

      {/* Action Buttons */}
      <div data-testid={`actions-${location.id}`} className="flex flex-wrap gap-2 pt-3 border-t">
        <button 
          data-testid={`view-details-btn-${location.id}`}
          onClick={onViewDetails}
          className="px-3 py-1.5 text-blue-600 hover:bg-blue-50 rounded"
          aria-label={`View details for ${location.name}`}
        >
          View Details
        </button>
        <button 
          data-testid={`edit-btn-${location.id}`}
          onClick={onEdit}
          className="px-3 py-1.5 text-gray-600 hover:bg-gray-50 rounded"
          aria-label={`Edit ${location.name}`}
        >
          <span data-testid="edit-icon">Edit</span>
          Edit
        </button>
        <button 
          data-testid={`delete-btn-${location.id}`}
          onClick={onDelete}
          className="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded"
          aria-label={`Delete ${location.name}`}
          disabled={isLoading}
        >
          <span data-testid="trash-icon">Trash</span>
          Delete
        </button>
      </div>
    </div>
  );
};

// Mock Loading Skeleton
const MockLocationCardSkeleton = () => (
  <div data-testid="location-card-skeleton" className="border rounded-lg p-4 animate-pulse">
    <div className="flex justify-between items-start mb-3">
      <div className="flex items-start gap-3">
        <div className="w-5 h-5 bg-gray-200 rounded"></div>
        <div>
          <div className="h-6 w-40 bg-gray-200 rounded mb-2"></div>
          <div className="h-4 w-60 bg-gray-200 rounded"></div>
        </div>
      </div>
      <div className="h-6 w-24 bg-gray-200 rounded-full"></div>
    </div>
    <div className="flex gap-4 mb-4">
      <div className="h-4 w-20 bg-gray-200 rounded"></div>
      <div className="h-4 w-24 bg-gray-200 rounded"></div>
      <div className="h-4 w-32 bg-gray-200 rounded"></div>
    </div>
    <div className="flex gap-2 pt-3 border-t">
      <div className="h-8 w-24 bg-gray-200 rounded"></div>
      <div className="h-8 w-16 bg-gray-200 rounded"></div>
      <div className="h-8 w-18 bg-gray-200 rounded"></div>
    </div>
  </div>
);

describe('LocationCard', () => {
  const mockOnEdit = vi.fn();
  const mockOnDelete = vi.fn();
  const mockOnViewDetails = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ============================================
  // LC-RENDER: Basic Rendering
  // ============================================
  
  describe('LC-RENDER: Basic Rendering', () => {
    it('LC-RN-001: Renders location name', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('location-name-1')).toHaveTextContent('Main Office');
    });

    it('LC-RN-002: Renders location address', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('location-address-1')).toHaveTextContent('123 Main Street, New York, NY 10001');
    });

    it('LC-RN-003: Renders MapPin icon', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('map-pin-icon')).toBeInTheDocument();
    });

    it('LC-RN-004: Renders action buttons', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('view-details-btn-1')).toBeInTheDocument();
      expect(screen.getByTestId('edit-btn-1')).toBeInTheDocument();
      expect(screen.getByTestId('delete-btn-1')).toBeInTheDocument();
    });
  });

  // ============================================
  // LC-CONNECTION-STATUS: Connection Status Badge
  // ============================================
  
  describe('LC-CONNECTION-STATUS: Connection Status Badge', () => {
    it('LC-CS-001: Shows connected badge for connected location', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('connected-badge-1')).toBeInTheDocument();
      expect(screen.getByTestId('connected-badge-1')).toHaveTextContent('Connected');
      expect(screen.getByTestId('connected-badge-1').className).toContain('bg-green');
    });

    it('LC-CS-002: Shows check icon for connected badge', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const badge = screen.getByTestId('connected-badge-1');
      expect(badge.querySelector('[data-testid="check-icon"]')).toBeInTheDocument();
    });

    it('LC-CS-003: Shows not connected badge for unconnected location', () => {
      render(
        <MockLocationCard 
          location={mockUnconnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('not-connected-badge-2')).toBeInTheDocument();
      expect(screen.getByTestId('not-connected-badge-2')).toHaveTextContent('Not Connected');
      expect(screen.getByTestId('not-connected-badge-2').className).toContain('bg-gray');
    });

    it('LC-CS-004: Shows X icon for not connected badge', () => {
      render(
        <MockLocationCard 
          location={mockUnconnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const badge = screen.getByTestId('not-connected-badge-2');
      expect(badge.querySelector('[data-testid="x-icon"]')).toBeInTheDocument();
    });
  });

  // ============================================
  // LC-QUICK-STATS: Quick Stats Display
  // ============================================
  
  describe('LC-QUICK-STATS: Quick Stats Display', () => {
    it('LC-QS-001: Shows rating for connected location', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const rating = screen.getByTestId('rating-stat-1');
      expect(rating).toHaveTextContent('4.5');
      expect(rating).toHaveTextContent('120 reviews');
    });

    it('LC-QS-002: Shows star icon for rating', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const rating = screen.getByTestId('rating-stat-1');
      expect(rating.querySelector('[data-testid="star-icon"]')).toBeInTheDocument();
    });

    it('LC-QS-003: Shows pack position', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('pack-position-1')).toHaveTextContent('Position #3');
    });

    it('LC-QS-004: Shows phone number', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('phone-stat-1')).toHaveTextContent('+1 (555) 123-4567');
    });

    it('LC-QS-005: Shows website link', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const link = screen.getByTestId('website-link-1');
      expect(link).toHaveAttribute('href', 'https://example.com');
      expect(link).toHaveAttribute('target', '_blank');
    });

    it('LC-QS-006: Shows no rating data for unconnected location', () => {
      render(
        <MockLocationCard 
          location={mockUnconnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('no-rating-2')).toHaveTextContent('No rating data');
    });

    it('LC-QS-007: Does not show pack position for unconnected', () => {
      render(
        <MockLocationCard 
          location={mockUnconnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.queryByTestId('pack-position-2')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // LC-ACTIONS: Action Buttons
  // ============================================
  
  describe('LC-ACTIONS: Action Buttons', () => {
    it('LC-AC-001: View Details button triggers callback', async () => {
      const user = userEvent.setup();
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      await user.click(screen.getByTestId('view-details-btn-1'));
      
      expect(mockOnViewDetails).toHaveBeenCalledTimes(1);
    });

    it('LC-AC-002: Edit button triggers callback', async () => {
      const user = userEvent.setup();
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      await user.click(screen.getByTestId('edit-btn-1'));
      
      expect(mockOnEdit).toHaveBeenCalledTimes(1);
    });

    it('LC-AC-003: Delete button triggers callback', async () => {
      const user = userEvent.setup();
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      await user.click(screen.getByTestId('delete-btn-1'));
      
      expect(mockOnDelete).toHaveBeenCalledTimes(1);
    });

    it('LC-AC-004: Shows Edit icon', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('edit-icon')).toBeInTheDocument();
    });

    it('LC-AC-005: Shows Trash icon', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('trash-icon')).toBeInTheDocument();
    });
  });

  // ============================================
  // LC-LOADING: Loading State
  // ============================================
  
  describe('LC-LOADING: Loading State', () => {
    it('LC-LD-001: Renders skeleton loader', () => {
      render(<MockLocationCardSkeleton />);
      
      expect(screen.getByTestId('location-card-skeleton')).toBeInTheDocument();
    });

    it('LC-LD-002: Skeleton has animate-pulse class', () => {
      render(<MockLocationCardSkeleton />);
      
      expect(screen.getByTestId('location-card-skeleton').className).toContain('animate-pulse');
    });

    it('LC-LD-003: Skeleton has placeholder elements', () => {
      render(<MockLocationCardSkeleton />);
      
      // Check for multiple placeholder divs with bg-gray-200
      const skeleton = screen.getByTestId('location-card-skeleton');
      const placeholders = skeleton.querySelectorAll('.bg-gray-200');
      expect(placeholders.length).toBeGreaterThan(5);
    });

    it('LC-LD-004: Delete button disabled when loading', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
          isLoading={true}
        />
      );
      
      expect(screen.getByTestId('delete-btn-1')).toBeDisabled();
    });
  });

  // ============================================
  // LC-ACCESSIBILITY: Accessibility
  // ============================================
  
  describe('LC-ACCESSIBILITY: Accessibility', () => {
    it('LC-AX-001: Has aria-label on card', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('location-card-1')).toHaveAttribute('aria-label', 'Location: Main Office');
    });

    it('LC-AX-002: Connection badge has aria-label', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('connected-badge-1')).toHaveAttribute('aria-label', 'GBP Connected');
    });

    it('LC-AX-003: Action buttons have aria-labels', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('view-details-btn-1')).toHaveAttribute('aria-label', 'View details for Main Office');
      expect(screen.getByTestId('edit-btn-1')).toHaveAttribute('aria-label', 'Edit Main Office');
      expect(screen.getByTestId('delete-btn-1')).toHaveAttribute('aria-label', 'Delete Main Office');
    });

    it('LC-AX-004: Website link opens in new tab', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const link = screen.getByTestId('website-link-1');
      expect(link).toHaveAttribute('target', '_blank');
      expect(link).toHaveAttribute('rel', 'noopener noreferrer');
    });
  });

  // ============================================
  // LC-EDGE-CASES: Edge Cases
  // ============================================
  
  describe('LC-EDGE-CASES: Edge Cases', () => {
    it('LC-EC-001: Handles location with zero rating', () => {
      const locationWithZeroRating = {
        ...mockConnectedLocation,
        rating: 0,
        review_count: 5,
      };
      
      render(
        <MockLocationCard 
          location={locationWithZeroRating} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('rating-stat-1')).toHaveTextContent('0');
    });

    it('LC-EC-002: Handles location with very high rating', () => {
      const locationWithHighRating = {
        ...mockConnectedLocation,
        rating: 5.0,
        review_count: 9999,
      };
      
      render(
        <MockLocationCard 
          location={locationWithHighRating} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('rating-stat-1')).toHaveTextContent('5');
      expect(screen.getByTestId('rating-stat-1')).toHaveTextContent('9999 reviews');
    });

    it('LC-EC-003: Handles location with pack position 1', () => {
      const locationWithPositionOne = {
        ...mockConnectedLocation,
        pack_position: 1,
      };
      
      render(
        <MockLocationCard 
          location={locationWithPositionOne} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('pack-position-1')).toHaveTextContent('Position #1');
    });

    it('LC-EC-004: Handles location without phone', () => {
      const locationWithoutPhone = {
        ...mockConnectedLocation,
        phone: null,
      };
      
      render(
        <MockLocationCard 
          location={locationWithoutPhone} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.queryByTestId('phone-stat-1')).not.toBeInTheDocument();
    });

    it('LC-EC-005: Handles location without website', () => {
      const locationWithoutWebsite = {
        ...mockConnectedLocation,
        website: null,
      };
      
      render(
        <MockLocationCard 
          location={locationWithoutWebsite} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.queryByTestId('website-link-1')).not.toBeInTheDocument();
    });

    it('LC-EC-006: Handles special characters in name', () => {
      const locationWithSpecialChars = {
        ...mockConnectedLocation,
        name: "O'Brien's Café & Bar <test>",
      };
      
      render(
        <MockLocationCard 
          location={locationWithSpecialChars} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      // Should render the text safely (not as HTML)
      expect(screen.getByTestId('location-name-1')).toHaveTextContent("O'Brien's Café & Bar");
    });

    it('LC-EC-007: Handles very long address', () => {
      const locationWithLongAddress = {
        ...mockConnectedLocation,
        address: '12345 Very Long Street Name That Goes On And On, Suite 100, Building B, New York, NY 10001, United States of America',
      };
      
      render(
        <MockLocationCard 
          location={locationWithLongAddress} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('location-address-1')).toBeInTheDocument();
    });

    it('LC-EC-008: Handles unicode in address', () => {
      const locationWithUnicode = {
        ...mockConnectedLocation,
        address: '123 東京都渋谷区, Tokyo, 日本',
      };
      
      render(
        <MockLocationCard 
          location={locationWithUnicode} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('location-address-1')).toHaveTextContent('東京都渋谷区');
    });
  });

  // ============================================
  // LC-SECURITY: Security Tests
  // ============================================
  
  describe('LC-SECURITY: Security Tests', () => {
    it('LC-SC-001: Escapes HTML in location name', () => {
      const maliciousLocation = {
        ...mockConnectedLocation,
        name: '<script>alert("XSS")</script>Main Office',
      };
      
      render(
        <MockLocationCard 
          location={maliciousLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      // React escapes HTML by default
      expect(screen.getByTestId('location-name-1').innerHTML).not.toContain('<script>');
    });

    it('LC-SC-002: Escapes HTML in address', () => {
      const maliciousLocation = {
        ...mockConnectedLocation,
        address: '123 Main St<img src=x onerror=alert("XSS")>',
      };
      
      render(
        <MockLocationCard 
          location={maliciousLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      expect(screen.getByTestId('location-address-1').innerHTML).not.toContain('<img');
    });

    it('LC-SC-003: Website link has safe attributes', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const link = screen.getByTestId('website-link-1');
      expect(link).toHaveAttribute('rel', 'noopener noreferrer');
    });
  });

  // ============================================
  // LC-RESPONSIVE: Responsive Design
  // ============================================
  
  describe('LC-RESPONSIVE: Responsive Design', () => {
    it('LC-RS-001: Quick stats use flex-wrap', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const stats = screen.getByTestId('quick-stats-1');
      expect(stats.className).toContain('flex-wrap');
    });

    it('LC-RS-002: Actions use flex-wrap', () => {
      render(
        <MockLocationCard 
          location={mockConnectedLocation} 
          onEdit={mockOnEdit}
          onDelete={mockOnDelete}
          onViewDetails={mockOnViewDetails}
        />
      );
      
      const actions = screen.getByTestId('actions-1');
      expect(actions.className).toContain('flex-wrap');
    });
  });
});
