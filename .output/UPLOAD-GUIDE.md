# WordPress Plugin Upload Guide

## ZIP File Information
- **Plugin:** elementor-faq
- **Version:** 1.0.1
- **ZIP File:** elementor-faq-1.0.1.zip

## IMPORTANT: Folder Structure

The ZIP file contains the correct folder structure for WordPress:

~~~
elementor-faq-1.0.1.zip
â””â”€â”€ elementor-faq/          <-- Internal folder name (NO version suffix!)
    â”œâ”€â”€ elementor-faq.php
    â”œâ”€â”€ readme.txt
    â””â”€â”€ ...
~~~

## How WordPress Handles Plugin ZIPs

When you upload a ZIP via **WordPress Admin > Plugins > Add New > Upload Plugin**:

1. WordPress extracts the ZIP file
2. WordPress uses the **internal folder name** from the ZIP as the plugin directory
3. The ZIP filename is ignored - only the internal folder structure matters

## Correct Upload Method

### Option 1: WordPress Admin (Recommended)
1. Go to **WordPress Admin > Plugins > Add New**
2. Click **"Upload Plugin"**
3. Select the ZIP file: `elementor-faq-1.0.1.zip`
4. Click **"Install Now"**
5. WordPress will install to: `/wp-content/plugins/elementor-faq/`

### Option 2: FTP/SFTP (Manual)
1. Extract the ZIP locally
2. Upload the `elementor-faq` folder (NOT the ZIP itself) to `/wp-content/plugins/`
3. Final path should be: `/wp-content/plugins/elementor-faq/elementor-faq.php`

## Common Mistakes to Avoid

| Mistake | Result | Solution |
|---------|--------|----------|
| Renaming extracted folder to match ZIP name | Folder with version suffix | Don't rename - use folder as-is from ZIP |
| Uploading ZIP via FTP | Plugin not detected | Extract first, then upload folder |
| Extracting ZIP with "auto-create folder" option | Double-nested folders | Extract directly without extra folder |

## Verifying Correct Installation

After installation, the plugin should be at:
~~~
/wp-content/plugins/elementor-faq/elementor-faq.php
~~~

NOT at:
~~~
/wp-content/plugins/elementor-faq-1.0.1/elementor-faq.php  âŒ WRONG
/wp-content/plugins/elementor-faq-1.0.1.zip/elementor-faq.php              âŒ WRONG
~~~

## Version Update Detection

If WordPress doesn't detect an update from a previous version:
1. Ensure the previous plugin is in `/wp-content/plugins/elementor-faq/`
2. Deactivate the old version before uploading new version
3. Or use FTP to overwrite files directly

---
Generated: 2026-03-16 22:53:06
