# AGENTS.md - Elementor FAQ Plugin

## Repository Structure Rules

### CRITICAL: GitHub Repository Structure

The GitHub repository MUST always have **plugin files at the root level**:

```
elementor-faq/
├── .gitignore
├── .github/
│   └── CONTRIBUTING.md
├── README.md
├── elementor-faq.php      # Main plugin file - MUST be at root
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
    ├── Elementor/
    ├── PostTypes/
    └── Taxonomies/
```

### NEVER commit these folders (gitignored):
- `.builder/` - Build scripts
- `.opencode/` - IDE settings
- `.output/` - Build artifacts
- `.ref/` - Reference plugins
- `AGENTS.md` - This file

## Local Development Environment

```
FAQ/
├── .builder/                  # Build scripts (local only - gitignored)
│   ├── build-zip.ps1          # PowerShell script for creating release ZIPs
│   └── zip-config.json        # Build configuration (exclusions, required files)
├── .git/                      # Git repository
├── .github/                   # GitHub configs (tracked)
│   └── CONTRIBUTING.md
├── .opencode/                 # OpenCode development environment (local only - gitignored)
│   ├── .gitignore
│   ├── bun.lock
│   ├── node_modules/
│   └── package.json
├── .output/                   # Build output directory (local only - gitignored)
│   ├── build-log.json
│   ├── elementor-faq-X.Y.Z.zip
│   ├── elementor-faq-X.Y.Z.zip.md5
│   └── elementor-faq-X.Y.Z.zip.sha256
├── .ref/                      # Reference plugins (local only - gitignored)
│   └── wc-carousel-grid-marketplace-and-pricing/
├── .gitignore                 # Excludes local dev files from git
├── AGENTS.md                  # This file (local only - gitignored)
└── plugin builder/            # Plugin builder directory
    └── plugin/
        └── Elementor-FAQ/     # Main plugin files
            ├── elementor-faq.php      # Main plugin file (entry point, autoloader)
            ├── README.md              # Developer documentation
            ├── readme.txt             # WordPress.org plugin repository readme
            ├── assets/
            │   ├── css/
            │   │   ├── admin.css
            │   │   └── faq.css
            │   └── js/
            │       ├── admin.js
            │       └── faq.js
            └── src/                   # PHP classes (PSR-4, namespace: Elementor_FAQ)
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
