# Hostinger Automated Deployment Setup

This guide explains how to set up automated deployment from GitHub Actions to your Hostinger shared hosting account.

## 🎯 **What This Provides**

- ✅ **Automatic deployment** on every push to main branch
- ✅ **Production testing** after deployment
- ✅ **Failed test logs** for debugging
- ✅ **Secure FTP deployment** to Hostinger
- ✅ **File filtering** (excludes development files)

## 🔧 **Setup Instructions**

### 1. Get Hostinger FTP Credentials

1. **Login to Hostinger Control Panel**
2. **Go to File Manager** or **FTP Accounts**
3. **Note down these details:**
   - FTP Host (usually: `ftp.yourdomain.com` or IP address)
   - FTP Username (usually: `yourdomain.com` or custom)
   - FTP Password

### 2. Add GitHub Secrets

1. **Go to your GitHub repository**
2. **Click Settings** → **Secrets and variables** → **Actions**
3. **Add these repository secrets:**

```
HOSTINGER_FTP_HOST = ftp.yourdomain.com
HOSTINGER_FTP_USERNAME = yourdomain.com
HOSTINGER_FTP_PASSWORD = your_ftp_password
```

### 3. File Structure on Hostinger

Your files will be deployed to `/public_html/` directory:

```
public_html/
├── index.php
├── style.css
├── api/
├── seo-guides/
├── config.php
├── db_config.php
└── ... (other PHP files)
```

**Excluded files** (won't be deployed):
- `.git/` and `.github/`
- `node_modules/`
- `tests/` and `test-logs/`
- `package.json` and `playwright.config.js`
- Development files

## 🚀 **Workflow Overview**

### **Deploy and Test Workflow** (`.github/workflows/deploy-and-test.yml`)

1. **Deploy Phase:**
   - Checkout code
   - Install dependencies
   - Deploy to Hostinger via FTP
   - Wait for deployment propagation

2. **Test Phase:**
   - Install Playwright browsers
   - Run tests against production site
   - Generate failure logs if tests fail
   - Commit logs to repository

### **Separate Test Workflow** (`.github/workflows/playwright.yml`)

- Runs tests against production site
- No deployment (for testing only)
- Useful for testing without deploying

## 📋 **Deployment Process**

### **Automatic Deployment:**
```bash
git add .
git commit -m "Update website"
git push origin main
```

**What happens:**
1. ✅ **Code deploys** to Hostinger
2. ✅ **Tests run** against production
3. ✅ **Success/ failure** reported in GitHub Actions
4. ✅ **Logs created** if tests fail

### **Manual Deployment:**
- Go to **Actions** tab in GitHub
- Click **Deploy and Test Production**
- Click **Run workflow**

## 🔒 **Security Features**

- ✅ **FTP credentials** stored as encrypted secrets
- ✅ **No sensitive files** deployed (config.php, .env)
- ✅ **Development files** excluded
- ✅ **Secure SFTP** connection

## 🐛 **Troubleshooting**

### **Deployment Fails:**
1. **Check FTP credentials** in GitHub secrets
2. **Verify FTP host** is correct
3. **Check Hostinger** file permissions
4. **Review deployment logs** in GitHub Actions

### **Tests Fail After Deployment:**
1. **Check production site** manually: https://seoindicator.com
2. **Review test logs** in `test-logs/` directory
3. **Verify file upload** was successful
4. **Check for PHP errors** in Hostinger logs

### **Site Not Accessible:**
1. **Check file permissions** on Hostinger
2. **Verify index.php** was uploaded
3. **Check .htaccess** file (if any)
4. **Review Hostinger** error logs

## 📊 **Monitoring**

### **Success Indicators:**
- ✅ **Green checkmark** in GitHub Actions
- ✅ **Site accessible** at https://seoindicator.com
- ✅ **Tests passing** in production

### **Failure Indicators:**
- ❌ **Red X** in GitHub Actions
- ❌ **Failed test logs** in repository
- ❌ **Site not accessible** or errors

## 🎉 **Benefits**

- ✅ **Zero-downtime deployment** (files upload directly)
- ✅ **Automatic testing** after deployment
- ✅ **Version control** integration
- ✅ **Rollback capability** (git revert)
- ✅ **Team collaboration** (multiple developers)
- ✅ **Production monitoring** (automated tests)

## 🔄 **Workflow Customization**

### **Change Deployment Branch:**
Edit `.github/workflows/deploy-and-test.yml`:
```yaml
on:
  push:
    branches: [ main, develop ]  # Add your branches
```

### **Exclude More Files:**
Add to the `exclude` section:
```yaml
exclude: |
  **/.git*
  **/your-custom-file.txt
  **/temp/**
```

### **Add Build Step:**
If you need to build assets:
```yaml
- name: Build assets
  run: |
    npm run build
    # Add your build commands
```

## 📞 **Support**

If you encounter issues:
1. **Check GitHub Actions logs** for detailed error messages
2. **Verify Hostinger** FTP settings
3. **Test FTP connection** manually
4. **Review this guide** for common solutions
