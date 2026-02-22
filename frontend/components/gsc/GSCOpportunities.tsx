'use client';

import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { GSCOpportunityKeyword } from '@/lib/types/gsc';
import { formatCTR, formatNumber, formatPosition } from '@/lib/api/gsc';
import { Lightbulb, TrendingUp, Eye } from 'lucide-react';

interface GSCOpportunitiesProps {
  data: GSCOpportunityKeyword[];
  isLoading?: boolean;
}

const GSCOpportunities: React.FC<GSCOpportunitiesProps> = ({ data, isLoading = false }) => {
  // Calculate opportunity level based on score
  const getOpportunityLevel = (score: number): { label: string; className: string } => {
    if (score >= 75) return { label: 'High', className: 'bg-green-100 text-green-800' };
    if (score >= 50) return { label: 'Medium', className: 'bg-yellow-100 text-yellow-800' };
    return { label: 'Low', className: 'bg-gray-100 text-gray-800' };
  };

  // Calculate CTR gap (potential improvement)
  const getCTRGap = (current: number, expected: number): number => {
    return Math.max(0, expected - current);
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg flex items-center gap-2">
          <Lightbulb className="h-5 w-5 text-yellow-500" />
          Opportunity Keywords
        </CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="flex items-center justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>
        ) : data.length === 0 ? (
          <div className="flex items-center justify-center py-8 text-muted-foreground">
            No opportunity keywords found.
          </div>
        ) : (
          <div className="space-y-3">
            {data.slice(0, 10).map((item, index) => {
              const opportunity = getOpportunityLevel(item.opportunity_score);
              const ctrGap = getCTRGap(item.ctr, item.expected_ctr);

              return (
                <div
                  key={`${item.query}-${index}`}
                  className="p-4 border rounded-lg hover:bg-muted/30 transition-colors"
                >
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-2">
                        <span className="font-medium truncate">{item.query}</span>
                        <span
                          className={`px-2 py-0.5 text-xs rounded-full ${opportunity.className}`}
                        >
                          {opportunity.label}
                        </span>
                      </div>

                      <div className="grid grid-cols-4 gap-3 text-sm">
                        <div>
                          <div className="text-muted-foreground">Clicks</div>
                          <div className="font-medium">{formatNumber(item.clicks)}</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">Impressions</div>
                          <div className="font-medium">{formatNumber(item.impressions)}</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">Position</div>
                          <div className="font-medium">{formatPosition(item.avg_position)}</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">CTR Gap</div>
                          <div className="font-medium text-green-600">
                            +{formatCTR(ctrGap)}
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="flex flex-col items-end">
                      <div className="flex items-center gap-1 text-lg font-semibold text-primary">
                        <TrendingUp className="h-4 w-4" />
                        {item.opportunity_score.toFixed(0)}
                      </div>
                      <div className="text-xs text-muted-foreground">Opportunity Score</div>
                    </div>
                  </div>

                  {/* Opportunity insight */}
                  <div className="mt-3 pt-3 border-t text-sm text-muted-foreground">
                    <Eye className="h-3 w-3 inline mr-1" />
                    {item.avg_position <= 10 ? (
                      <>
                        Position {formatPosition(item.avg_position)} - potential to improve CTR by{' '}
                        <span className="font-medium text-foreground">
                          +{formatCTR(ctrGap)}
                        </span>
                      </>
                    ) : (
                      <>
                        Position {formatPosition(item.avg_position)} - potential to reach page 1
                      </>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {!isLoading && data.length > 10 && (
          <div className="mt-4 text-sm text-muted-foreground text-center">
            Showing top 10 of {data.length} opportunities
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default GSCOpportunities;
