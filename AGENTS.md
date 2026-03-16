# AGENTS.md - Elementor FAQ Plugin

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
6. Create GitHub release: `gh release create vX.Y.Z ".output/elementor-faq-X.Y.Z.zip" --title "vX.Y.Z" --notes "Release notes"`

### Project Structure

```
├── .builder/           # Build scripts
│   ├── build-zip.ps1   # Main build script
│   └── zip-config.json # Build configuration
├── .output/            # Built ZIP packages
├── assets/
│   ├── css/            # Frontend & admin styles
│   └── js/             # Frontend & admin scripts
├── src/
│   ├── Core/           # Plugin class, debug logger
│   ├── Elementor/      # Elementor integration & widgets
│   ├── PostTypes/      # FAQ post type
│   └── Taxonomies/     # FAQ category taxonomy
├── elementor-faq.php   # Main plugin file
└── readme.txt          # WordPress.org readme
```

### Key Files

- `src/Core/Plugin.php` - Main plugin class, registers all hooks including script/style registration
- `src/Elementor/Elementor_Integration.php` - Registers widgets, styles, and scripts with Elementor
- `src/Elementor/Widgets/FAQ_Widget.php` - The FAQ Elementor widget
- `assets/css/faq.css` - Frontend styles (uses em units for icon sizing)
- `assets/js/faq.js` - Frontend toggle functionality
