-- Migration script to add meta description analysis results to the database

ALTER TABLE seo_analysis_results
ADD COLUMN meta_description TEXT;

-- Ensure that column update is backward compatible