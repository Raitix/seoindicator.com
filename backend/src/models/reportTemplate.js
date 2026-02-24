const { DataTypes, Model } = require('sequelize');
const sequelize = require('../config/database');

class ReportTemplate extends Model {}

ReportTemplate.init({
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true,
  },
  site_id: {
    type: DataTypes.INTEGER,
  },
  name: {
    type: DataTypes.STRING,
    allowNull: false,
  },
  is_default: {
    type: DataTypes.BOOLEAN,
    defaultValue: false,
  },
  sections: {
    type: DataTypes.JSON,
    allowNull: false,
  },
  branding: {
    type: DataTypes.JSON,
  },
  cover_page_text: {
    type: DataTypes.TEXT,
  },
  executive_summary: {
    type: DataTypes.TEXT,
  },
}, {
  sequelize,
  modelName: 'ReportTemplate',
  tableName: 'report_templates',
  timestamps: true,
  createdAt: 'created_at',
  updatedAt: 'updated_at',
});

module.exports = ReportTemplate;