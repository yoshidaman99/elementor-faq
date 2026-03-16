# Contributing to Elementor FAQ

## Repository Structure

This repository uses a **flat structure** where plugin files are at the root level:

```
elementor-faq/
├── .gitignore
├── README.md
├── elementor-faq.php      # Main plugin file (DO NOT MOVE)
├── readme.txt
├── assets/
│   ├── css/
│   └── js/
└── src/
    ├── Core/
    ├── Elementor/
    ├── PostTypes/
    └── Taxonomies/
```

## Important Rules

1. **Plugin files MUST remain at root level** - Do not create subfolders for the plugin
2. **Do not commit dev folders** - These are excluded via `.gitignore`:
   - `.builder/` - Build scripts
   - `.output/` - Build artifacts
   - `.ref/` - Reference plugins
   - `.opencode/` - IDE settings
   - `AGENTS.md` - Development notes

## Development Workflow

1. Make changes to plugin files at root
2. Update version in `elementor-faq.php` header and constant
3. Test locally
4. Commit and push
5. Create GitHub release with ZIP artifact

## Building Release ZIP

```powershell
powershell -ExecutionPolicy Bypass -File ".builder/build-zip.ps1"
```

The ZIP will be created in `.output/` folder.
