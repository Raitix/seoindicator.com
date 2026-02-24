const { DataTypes, Model } = require('sequelize');
const sequelize = require('../config/database');

class Report extends Model {}

Report.init({
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true,
  },
  site_id: {
    type: DataTypes.INTEGER,
    allowNull: false,
  },
  name: {
    type: DataTypes.STRING,
    allowNull: false,
  },
  type: {
    type: DataTypes.ENUM('daily', 'weekly', 'monthly', 'custom', 'one-time'),
    allowNull: false,
  },
  format: {
    type: DataTypes.ENUM('pdf', 'csv'),
    defaultValue: 'pdf',
  },
  status: {
    type: DataTypes.ENUM('pending', 'generating', 'failed', 'completed'),
    defaultValue: 'pending',
  },
  date_range_start: {
    type: DataTypes.DATEONLY,
    allowNull: false,
  },
  date_range_end: {
    type: DataTypes.DATEONLY,
    allowNull: false,
  },
  compare_range_start: {
    type: DataTypes.DATEONLY,
  },
  compare_range_end: {
    type: DataTypes.DATEONLY,
  },
  sections: {
    type: DataTypes.JSON,
  },
  generated_at: {
    type: DataTypes.DATE,
  },
  file_path: {
    type: DataTypes.STRING,
  },
  file_size: {
    type: DataTypes.INTEGER,
  },
  error: {
    type: DataTypes.TEXT,
  },
}, {
  sequelize,
  modelName: 'Report',
  tableName: 'reports',
  timestamps: true,
  createdAt: 'created_at',
  updatedAt: false,
});

module.exports = Report;