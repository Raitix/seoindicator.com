import React from 'react';
import HistoricalComparisonChart from '../../components/charts/HistoricalComparisonChart';

const mockData = [
  {
    date: '2026-02-14',
    userPosition: 3,
    'CompetitorA.com': 5,
    'CompetitorB.com': 8,
  },
  {
    date: '2026-02-15',
    userPosition: 2,
    'CompetitorA.com': 4,
    'CompetitorB.com': 6,
  },
  {
    date: '2026-02-16',
    userPosition: 1,
    'CompetitorA.com': 3,
    'CompetitorB.com': 4,
  },
];

const HistoricalComparisonTestPage: React.FC = () => {
  return (
    <div style={{ padding: '20px' }}>
      <h1>Historical Comparison Chart - Test Page</h1>
      <HistoricalComparisonChart data={mockData} />
    </div>
  );
};

export default HistoricalComparisonTestPage;