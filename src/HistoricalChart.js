import React from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

default function HistoricalChart({ data }) {
    return (
        <ResponsiveContainer width="100%" height={400}>
            <LineChart data={data} margin={{ top: 5, right: 20, left: 10, bottom: 5 }}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis reversed />
                <Tooltip />
                <Legend />
                <Line type="monotone" dataKey="user" stroke="#8884d8" activeDot={{ r: 8 }} />
                <Line type="monotone" dataKey="competitor1" stroke="#82ca9d" />
                <Line type="monotone" dataKey="competitor2" stroke="#ffc658" />
            </LineChart>
        </ResponsiveContainer>
    );
}