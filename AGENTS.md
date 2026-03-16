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

