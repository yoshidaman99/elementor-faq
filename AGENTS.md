# AGENTS.md - Elementor FAQ Plugin

## Project Structure

### Local Development Environment
```
FAQ/
├── .builder/                  # Build scripts (local only)
│   ├── build-zip.ps1          # PowerShell script for creating release ZIPs
│   └── zip-config.json        # Build configuration (exclusions, required files)
├── .git/                      # Git repository
├── .opencode/                 # OpenCode development environment (local only)
├── .output/                   # Build output directory (local only)
│   ├── build-log.json         # Build metadata
│   ├── elementor-faq-X.Y.Z.zip
│   ├── elementor-faq-X.Y.Z.zip.md5
│   └── elementor-faq-X.Y.Z.zip.sha256
├── .ref/                      # Reference plugins for development (local only)
├── AGENTS.md                  # This file (local only)
├── plugin builder/            # Legacy plugin folder (local only)
├── elementor-faq.php          # Main plugin file (entry point, autoloader)
├── README.md                  # Developer documentation
├── readme.txt                 # WordPress.org plugin repository readme
├── assets/
│   ├── css/
│   │   ├── admin.css          # Admin area styling (meta boxes, columns)
│   │   └── faq.css            # Frontend widget styling (accordion, tabs, search)
│   └── js/
│       ├── admin.js           # Admin functionality (Q&A repeater, shortcode copy)
│       └── faq.js             # Frontend functionality (accordion, search, filtering)
└── src/                       # PHP classes (PSR-4, namespace: Elementor_FAQ)
    ├── Core/
    │   ├── Debug_Logger.php   # Debugging utility (log, error, warning, info)
    │   └── Plugin.php         # Main controller (services, hooks, init)
    ├── Elementor/
    │   ├── Elementor_Integration.php  # Registers widgets/styles/scripts
    │   └── Widgets/
    │       └── FAQ_Widget.php # Main Elementor widget (controls, render)
    ├── PostTypes/
    │   └── FAQ_Post_Type.php  # Custom post type 'faq-item' with meta boxes
    └── Taxonomies/
        └── FAQ_Category.php   # Taxonomy 'faq-category' for organizing FAQs
```

### GitHub Repository (Tracked Files)
```
elementor-faq/
├── README.md
├── elementor-faq.php
├── readme.txt
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── faq.css
│   └── js/
│       ├── admin.js
│       └── faq.js
└── src/
    ├── Core/
    │   ├── Debug_Logger.php
    │   └── Plugin.php
    ├── Elementor/
    │   ├── Elementor_Integration.php
    │   └── Widgets/
    │       └── FAQ_Widget.php
    ├── PostTypes/
    │   └── FAQ_Post_Type.php
    └── Taxonomies/
        └── FAQ_Category.php
```

## Architecture

### Class Hierarchy
```
Elementor_FAQ\Core\Plugin (singleton)
├── Elementor_FAQ\Core\Debug_Logger
├── Elementor_FAQ\PostTypes\FAQ_Post_Type
├── Elementor_FAQ\Taxonomies\FAQ_Category
└── Elementor_FAQ\Elementor\Elementor_Integration
    └── Elementor_FAQ\Elementor\Widgets\FAQ_Widget
```

### Key Classes

| Class | File | Purpose |
|-------|------|---------|
| `Plugin` | `src/Core/Plugin.php` | Main controller, registers services/hooks |
| `Debug_Logger` | `src/Core/Debug_Logger.php` | Logging utility for development |
| `FAQ_Post_Type` | `src/PostTypes/FAQ_Post_Type.php` | 'faq-item' post type with Q&A meta boxes |
| `FAQ_Category` | `src/Taxonomies/FAQ_Category.php` | 'faq-category' taxonomy |
| `Elementor_Integration` | `src/Elementor/Elementor_Integration.php` | Bridge to Elementor, registers widget |
| `FAQ_Widget` | `src/Elementor/Widgets/FAQ_Widget.php` | Elementor widget with controls/render |

### Autoloader
PSR-4 autoloader in `elementor-faq.php`:
- Namespace: `Elementor_FAQ`
- Maps to `src/` directory

## Plugin Features

1. **Custom Post Type**: 'FAQ Item' for managing FAQs with Q&A repeater fields
2. **Taxonomy**: 'FAQ Category' for organizing FAQs
3. **Elementor Widget**: Native integration with content/style controls
4. **Accordion**: Smooth expand/collapse with customizable animations
5. **Search**: Real-time filtering through FAQ content
6. **Category Tabs**: Horizontal/vertical layout options
7. **Responsive**: Mobile-optimized with breakpoints
8. **RTL Support**: Right-to-left language support

## Build & Release Process

### Building a Release ZIP

Use the PowerShell build script in `.builder/`:

```powershell
powershell -ExecutionPolicy Bypass -File ".builder/build-zip.ps1"
```

This will:
1. Read version from `elementor-faq.php` header
2. Create a ZIP in `.output/elementor-faq-{version}.zip`
3. Generate MD5 and SHA256 checksums
4. Create a `build-log.json` with build metadata

### Release Workflow

1. Update version in `elementor-faq.php` (header and constant)
2. Run build script: `powershell -ExecutionPolicy Bypass -File ".builder/build-zip.ps1"`
3. Commit changes: `git add -A && git commit -m "Release vX.Y.Z"`
4. Tag: `git tag vX.Y.Z`
5. Push: `git push origin master --tags`
6. Create GitHub release: `gh release create vX.Y.Z ".output/elementor-faq-X.Y.Z.zip" --title "vX.Y.Z" --notes "Release notes"
