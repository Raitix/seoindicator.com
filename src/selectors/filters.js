// Mocked selector logic for Date and Competitor Filters
export const getFilteredData = (data, { dateRange, competitors }) => {
    return data.filter(item =>
        dateRange.includes(item.date) &&
        competitors.some(comp => item[comp] !== undefined));
};