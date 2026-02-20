import React from 'react';
import { LineChart, Line, XAxis, YAxis, Tooltip, Legend, ResponsiveContainer } from 'recharts';

interface HistoricalComparisonChartProps {
  data: Array<{ date: string; userPosition: number; [competitor: string]: number }>;
}

const HistoricalComparisonChart: React.FC<HistoricalComparisonChartProps> = ({ data }) => {
  return (
    <ResponsiveContainer width="100%" height={500}>
      <LineChart data={data} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
        <XAxis dataKey="date" />
        <YAxis domain={[1, 100]} reversed />
        <Tooltip />
        <Legend />
        <Line type="monotone" dataKey="userPosition" stroke="#8884d8" name="Your Ranking" />
        {/* For competitors, dynamically render lines */}
        {Object.keys(data[0] || {}).filter(key => key !== 'date' && key !== 'userPosition').map(competitor => (
          <Line key={competitor} type="monotone" dataKey={competitor} stroke="#82ca9d" name={competitor} />
        ))}
      </LineChart>
    </ResponsiveContainer>
  );
};

export default HistoricalComparisonChart;