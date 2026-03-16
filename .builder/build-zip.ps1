#Requires -Version 5.1

<#
.SYNOPSIS
    Build ZIP Package for Elementor FAQ Plugin
    
.DESCRIPTION
    Creates a release-ready ZIP package with:
    - Proper folder structure for WordPress.org
    - MD5 and SHA256 checksums
    - Build log with version, timestamp, and git info
    
.PARAMETER Version
    Override version (auto-detected from plugin file if not specified)
    
.PARAMETER SkipChecksums
    Skip generating checksum files
    
.PARAMETER Verbose
    Show detailed output
    
.EXAMPLE
    .\build-zip.ps1
    Creates ZIP with auto-detected version
    
.EXAMPLE
    .\build-zip.ps1 -Version 1.1.0
    Creates ZIP with specified version
    
.OUTPUTS
    .output/elementor-faq-{version}.zip
    .output/elementor-faq-{version}.zip.md5
    .output/elementor-faq-{version}.zip.sha256
    .output/build-log.json
#>

param(
    [string]$Version,
    [switch]$SkipChecksums,
    [switch]$VerboseOutput
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$ConfigFile = Join-Path $ScriptDir "zip-config.json"
$PluginDir = Join-Path $ProjectRoot "plugin builder\plugin\Elementor-FAQ"
$OutputDir = Join-Path $ProjectRoot ".output"

function Write-Header {
    param([string]$Message)
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host " $Message" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
}

function Write-Success {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Write-Fail {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Yellow
}

function Get-PluginVersion {
    param([string]$PluginFile)
    
    $content = Get-Content $PluginFile -Raw
    if ($content -match '\*\s*Version:\s*([0-9.]+(-[a-zA-Z0-9]+)?)') {
        return $matches[1].Trim()
    }
    return $null
}

function Test-CleanSemver {
    param([string]$Version)
    
    if ($Version -match '-(dev|beta|alpha|rc|preview|pre|test|snapshot|build)') {
        return @{
            IsValid = $false
            Reason = "Development version suffix detected: '$Version'. Production releases require clean semver (e.g., 1.4.0, not 1.4.0-dev)."
            Suffix = $matches[1]
        }
    }
    
    if ($Version -notmatch '^[0-9]+\.[0-9]+\.[0-9]+$') {
        return @{
            IsValid = $false
            Reason = "Invalid version format: '$Version'. Expected format: X.Y.Z (e.g., 1.4.0)"
            Suffix = $null
        }
    }
    
    return @{
        IsValid = $true
        Reason = "Valid clean semver"
        Suffix = $null
    }
}

function New-UploadGuide {
    param(
        [string]$OutputDir,
        [string]$PluginSlug,
        [string]$Version,
        [string]$ZipName
    )
    
    $guideContent = @"
# WordPress Plugin Upload Guide

## ZIP File Information
- **Plugin:** $PluginSlug
- **Version:** $Version
- **ZIP File:** $ZipName

## IMPORTANT: Folder Structure

The ZIP file contains the correct folder structure for WordPress:

~~~
$ZipName
└── $PluginSlug/          <-- Internal folder name (NO version suffix!)
    ├── $PluginSlug.php
    ├── readme.txt
    └── ...
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
3. Select the ZIP file: ``$ZipName``
4. Click **"Install Now"**
5. WordPress will install to: ``/wp-content/plugins/$PluginSlug/``

### Option 2: FTP/SFTP (Manual)
1. Extract the ZIP locally
2. Upload the ``$PluginSlug`` folder (NOT the ZIP itself) to ``/wp-content/plugins/``
3. Final path should be: ``/wp-content/plugins/$PluginSlug/$PluginSlug.php``

## Common Mistakes to Avoid

| Mistake | Result | Solution |
|---------|--------|----------|
| Renaming extracted folder to match ZIP name | Folder with version suffix | Don't rename - use folder as-is from ZIP |
| Uploading ZIP via FTP | Plugin not detected | Extract first, then upload folder |
| Extracting ZIP with "auto-create folder" option | Double-nested folders | Extract directly without extra folder |

## Verifying Correct Installation

After installation, the plugin should be at:
~~~
/wp-content/plugins/$PluginSlug/$PluginSlug.php
~~~

NOT at:
~~~
/wp-content/plugins/$PluginSlug-$Version/$PluginSlug.php  ❌ WRONG
/wp-content/plugins/$ZipName/$PluginSlug.php              ❌ WRONG
~~~

## Version Update Detection

If WordPress doesn't detect an update from a previous version:
1. Ensure the previous plugin is in ``/wp-content/plugins/$PluginSlug/``
2. Deactivate the old version before uploading new version
3. Or use FTP to overwrite files directly

---
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
"@
    
    $guidePath = Join-Path $OutputDir "UPLOAD-GUIDE.md"
    $guideContent | Out-File -FilePath $guidePath -Encoding UTF8
    return $guidePath
}

function Get-FileHashCustom {
    param(
        [string]$FilePath,
        [string]$Algorithm
    )
    
    $hash = Get-FileHash -Path $FilePath -Algorithm $Algorithm
    return $hash.Hash.ToLower()
}

function Test-RequiredFiles {
    param(
        [string]$PluginDir,
        [array]$RequiredFiles
    )
    
    $missing = @()
    foreach ($file in $RequiredFiles) {
        $fullPath = Join-Path $PluginDir $file
        if (-not (Test-Path $fullPath)) {
            $missing += $file
        }
    }
    return $missing
}

Write-Header "ZIP Package Builder"

# Load configuration
if (-not (Test-Path $ConfigFile)) {
    Write-Fail "Configuration file not found: $ConfigFile"
    exit 1
}

$Config = Get-Content $ConfigFile | ConvertFrom-Json
$PluginSlug = $Config.plugin_slug

# Get version
if ([string]::IsNullOrEmpty($Version)) {
    $PluginFile = Join-Path $PluginDir "$PluginSlug.php"
    $Version = Get-PluginVersion -PluginFile $PluginFile
    
    if ([string]::IsNullOrEmpty($Version)) {
        Write-Fail "Could not detect version from plugin file"
        exit 1
    }
}

Write-Info "Plugin Version: $Version"
Write-Info "Plugin Slug: $PluginSlug"
Write-Info "Source: $PluginDir"
Write-Info "Output: $OutputDir"

# Validate clean semver for production releases
$ConfigRejectSuffixes = $Config.reject_version_suffixes
if ($ConfigRejectSuffixes -eq $true -or $null -eq $ConfigRejectSuffixes) {
    $VersionCheck = Test-CleanSemver -Version $Version
    
    if (-not $VersionCheck.IsValid) {
        Write-Fail "PRODUCTION RELEASE REJECTED"
        Write-Host ""
        Write-Host "Reason: $($VersionCheck.Reason)" -ForegroundColor Red
        Write-Host ""
        Write-Host "To release development versions, either:" -ForegroundColor Yellow
        Write-Host "  1. Update version to clean semver (e.g., 1.4.0 instead of 1.4.0-dev)" -ForegroundColor Yellow
        Write-Host "  2. Set 'reject_version_suffixes: false' in zip-config.json" -ForegroundColor Yellow
        Write-Host ""
        exit 1
    }
    
    Write-Success "Version validation passed: Clean semver detected"
}

# Check required files
Write-Header "Validating Required Files"
$missingFiles = Test-RequiredFiles -PluginDir $PluginDir -RequiredFiles $Config.required_files

if ($missingFiles.Count -gt 0) {
    Write-Fail "Missing required files:"
    foreach ($file in $missingFiles) {
        Write-Host "  - $file" -ForegroundColor Red
    }
    exit 1
}

Write-Success "All required files present"

# Create output directory if needed
if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
    Write-Info "Created output directory: $OutputDir"
}

# Define output files
$ZipName = "$PluginSlug-$Version.zip"
$ZipPath = Join-Path $OutputDir $ZipName
$Md5Path = "$ZipPath.md5"
$Sha256Path = "$ZipPath.sha256"
$BuildLogPath = Join-Path $OutputDir "build-log.json"

# Remove existing ZIP if present
if (Test-Path $ZipPath) {
    Remove-Item $ZipPath -Force
    Write-Info "Removed existing ZIP: $ZipName"
}

# Get git info
$GitCommit = "unknown"
$GitBranch = "unknown"
$GitTag = $null

try {
    Push-Location $PluginDir
    
    $GitCommit = git rev-parse HEAD 2>$null
    if ($LASTEXITCODE -ne 0) { $GitCommit = "unknown" }
    
    $GitBranch = git rev-parse --abbrev-ref HEAD 2>$null
    if ($LASTEXITCODE -ne 0) { $GitBranch = "unknown" }
    
    $GitTag = git describe --tags --exact-match 2>$null
    if ($LASTEXITCODE -ne 0) { $GitTag = $null }
    
    Pop-Location
}
catch {
    Write-Info "Git info unavailable: $_"
}

# Build exclusion list
$ExcludeArgs = @()
foreach ($pattern in $Config.exclude_patterns) {
    $ExcludeArgs += "-x"
    $ExcludeArgs += "*$pattern*"
}

# Create ZIP
Write-Header "Creating ZIP Package"

try {
    # Use Compress-Archive with proper structure
    $TempDir = Join-Path $env:TEMP "wc-cgmp-build-$(Get-Random)"
    $StagingDir = Join-Path $TempDir $PluginSlug
    
    # Create staging directory
    New-Item -ItemType Directory -Path $StagingDir -Force | Out-Null
    
    # Copy files to staging (excluding patterns)
    $ExcludePatterns = $Config.exclude_patterns
    
    # Get all files
    $AllFiles = Get-ChildItem -Path $PluginDir -Recurse -File
    
    $FilesCopied = 0
    foreach ($file in $AllFiles) {
        $relativePath = $file.FullName.Substring($PluginDir.Length + 1)
        $exclude = $false
        
        # Check exclusion patterns
        foreach ($pattern in $ExcludePatterns) {
            if ($pattern -eq ".git" -and $relativePath -like ".git*") {
                $exclude = $true
                break
            }
            if ($pattern -eq ".github" -and $relativePath -like ".github*") {
                $exclude = $true
                break
            }
            if ($pattern -eq "node_modules" -and $relativePath -like "*node_modules*") {
                $exclude = $true
                break
            }
            if ($pattern -eq "vendor" -and $relativePath -like "*vendor*") {
                $exclude = $true
                break
            }
            if ($pattern -eq "README.md" -and $relativePath -eq "README.md") {
                $exclude = $true
                break
            }
            if ($pattern -eq "CHANGELOG.md" -and $relativePath -eq "CHANGELOG.md") {
                $exclude = $true
                break
            }
            if ($pattern -eq "composer.json" -and $relativePath -eq "composer.json") {
                $exclude = $true
                break
            }
            if ($pattern -eq "composer.lock" -and $relativePath -eq "composer.lock") {
                $exclude = $true
                break
            }
            if ($pattern -eq "package.json" -and $relativePath -eq "package.json") {
                $exclude = $true
                break
            }
            if ($pattern -eq "package-lock.json" -and $relativePath -eq "package-lock.json") {
                $exclude = $true
                break
            }
            if ($pattern -eq ".gitignore" -and $relativePath -eq ".gitignore") {
                $exclude = $true
                break
            }
            if ($pattern -eq ".env" -and $relativePath -eq ".env") {
                $exclude = $true
                break
            }
            if ($pattern -like ".env.*" -and $relativePath -like ".env.*") {
                $exclude = $true
                break
            }
            if ($pattern -eq "phpcs.xml" -and $relativePath -eq "phpcs.xml") {
                $exclude = $true
                break
            }
            if ($pattern -eq "phpstan.neon" -and $relativePath -eq "phpstan.neon") {
                $exclude = $true
                break
            }
            if ($pattern -eq ".phpunit.cache" -and $relativePath -like ".phpunit.cache*") {
                $exclude = $true
                break
            }
            if ($pattern -eq "phpunit.xml" -and $relativePath -eq "phpunit.xml") {
                $exclude = $true
                break
            }
            if ($pattern -eq ".editorconfig" -and $relativePath -eq ".editorconfig") {
                $exclude = $true
                break
            }
            if ($pattern -eq ".distignore" -and $relativePath -eq ".distignore") {
                $exclude = $true
                break
            }
            if ($pattern -eq ".wp-env.json" -and $relativePath -eq ".wp-env.json") {
                $exclude = $true
                break
            }
            if ($pattern -eq "*.map" -and $relativePath -like "*.map") {
                $exclude = $true
                break
            }
        }
        
        if (-not $exclude) {
            $destPath = Join-Path $StagingDir $relativePath
            $destDir = Split-Path -Parent $destPath
            
            if (-not (Test-Path $destDir)) {
                New-Item -ItemType Directory -Path $destDir -Force | Out-Null
            }
            
            Copy-Item -Path $file.FullName -Destination $destPath -Force
            $FilesCopied++
            
            if ($VerboseOutput -and $FilesCopied % 100 -eq 0) {
                Write-Info "Copied $FilesCopied files..."
            }
        }
    }
    
    Write-Info "Copied $FilesCopied files to staging"
    
    # Create ZIP with forward-slash paths (cross-platform compatible)
    # Compress-Archive uses backslashes which break on Linux/WordPress
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    
    try {
        # Use FileShare.None to ensure exclusive access
        $ZipStream = [System.IO.FileStream]::new($ZipPath, [System.IO.FileMode]::Create, [System.IO.FileAccess]::ReadWrite, [System.IO.FileShare]::None)
        $ZipArchive = [System.IO.Compression.ZipArchive]::new($ZipStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
        
        $StagingFiles = Get-ChildItem -Path $StagingDir -Recurse -File
        
        foreach ($stagedFile in $StagingFiles) {
            $relativePath = $stagedFile.FullName.Substring($StagingDir.Length + 1)
            
            # CRITICAL: Use forward slashes for cross-platform compatibility
            $entryName = $PluginSlug + "/" + $relativePath.Replace('\', '/')
            
            $entry = $ZipArchive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
            
            $writer = $entry.Open()
            $reader = [System.IO.File]::OpenRead($stagedFile.FullName)
            $reader.CopyTo($writer)
            $reader.Dispose()
            $writer.Dispose()
        }
        
        # Dispose archive BEFORE stream to ensure proper ZIP central directory
        $ZipArchive.Dispose()
        $ZipStream.Dispose()
        
        Write-Info "Created ZIP with forward-slash paths for cross-platform compatibility"
    }
    catch {
        if ($ZipArchive) { $ZipArchive.Dispose() }
        if ($ZipStream) { $ZipStream.Dispose() }
        throw
    }
    
    # Cleanup temp directory
    Remove-Item -Path $TempDir -Recurse -Force
    
    Write-Success "Created: $ZipName"
}
catch {
    Write-Fail "Failed to create ZIP: $_"
    if (Test-Path $TempDir) {
        Remove-Item -Path $TempDir -Recurse -Force -ErrorAction SilentlyContinue
    }
    exit 1
}

# Get ZIP file info
$ZipInfo = Get-Item $ZipPath
$ZipSizeKB = [math]::Round($ZipInfo.Length / 1KB, 2)

# Generate checksums
if (-not $SkipChecksums) {
    Write-Header "Generating Checksums"
    
    # MD5
    $Md5Hash = Get-FileHashCustom -FilePath $ZipPath -Algorithm "MD5"
    "$Md5Hash  $ZipName" | Out-File -FilePath $Md5Path -Encoding ascii -NoNewline
    Write-Success "MD5: $Md5Hash"
    
    # SHA256
    $Sha256Hash = Get-FileHashCustom -FilePath $ZipPath -Algorithm "SHA256"
    "$Sha256Hash  $ZipName" | Out-File -FilePath $Sha256Path -Encoding ascii -NoNewline
    Write-Success "SHA256: $Sha256Hash"
}

# Post-Build Verification
Write-Header "Verifying ZIP Structure"

$RootFolder = "unknown"

try {
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    
    $ZipArchive = [System.IO.Compression.ZipFile]::OpenRead($ZipPath)
    $Entries = $ZipArchive.Entries
    
    # Get root folder name from first entry
    # Normalize path separators to forward slash
    $FirstEntry = $Entries[0].FullName.Replace('\', '/')
    $RootFolder = $FirstEntry.Split('/')[0]
    
    # Verify root folder matches expected
    $ExpectedRoot = $Config.expected_root_folder
    if ([string]::IsNullOrEmpty($ExpectedRoot)) {
        $ExpectedRoot = $PluginSlug
    }
    
    if ($RootFolder -ne $ExpectedRoot) {
        Write-Fail "Root folder mismatch!"
        Write-Host "  Expected: $ExpectedRoot" -ForegroundColor Red
        Write-Host "  Got:      $RootFolder" -ForegroundColor Red
        $ZipArchive.Dispose()
        exit 1
    }
    
    # Verify root folder doesn't contain version
    if ($RootFolder -match '-[0-9]+\.[0-9]+') {
        Write-Fail "Root folder contains version number!"
        Write-Host "  Folder: $RootFolder" -ForegroundColor Red
        Write-Host "  Root folder should be: $ExpectedRoot" -ForegroundColor Yellow
        $ZipArchive.Dispose()
        exit 1
    }
    
    # Verify required files exist
    $RequiredFiles = $Config.required_files
    $MissingFiles = @()
    
    foreach ($file in $RequiredFiles) {
        $ExpectedPath = "$RootFolder/$file"
        $Found = $false
        foreach ($entry in $Entries) {
            # Normalize entry path to forward slash for comparison
            $NormalizedEntry = $entry.FullName.Replace('\', '/')
            if ($NormalizedEntry -eq $ExpectedPath) {
                $Found = $true
                break
            }
        }
        if (-not $Found) {
            $MissingFiles += $file
        }
    }
    
    if ($MissingFiles.Count -gt 0) {
        Write-Fail "Missing required files in ZIP:"
        foreach ($file in $MissingFiles) {
            Write-Host "  - $file" -ForegroundColor Red
        }
        $ZipArchive.Dispose()
        exit 1
    }
    
$ZipArchive.Dispose()
    
Write-Success "Root folder: $RootFolder"
Write-Success "All required files present"
Write-Success "ZIP structure verified"
}
catch {
    Write-Fail "ZIP verification failed: $_"
    exit 1
}

# Create build log
Write-Header "Creating Build Log"

$BuildLog = @{
    version = $Version
    plugin_slug = $PluginSlug
    zip_file = $ZipName
    zip_size_kb = $ZipSizeKB
    files_count = $FilesCopied
    checksums = @{
        md5 = $Md5Hash
        sha256 = $Sha256Hash
    }
    git = @{
        commit = $GitCommit.Trim()
        branch = $GitBranch.Trim()
        tag = $GitTag
    }
    build_info = @{
        timestamp = (Get-Date -Format "yyyy-MM-ddTHH:mm:ssZ")
        builder = "build-zip.ps1"
        platform = $env:OS
        powershell_version = $PSVersionTable.PSVersion.ToString()
    }
    verification = @{
        root_folder = $RootFolder
        status = "verified"
    }
}

$BuildLog | ConvertTo-Json -Depth 10 | Out-File -FilePath $BuildLogPath -Encoding UTF8
Write-Success "Created: build-log.json"

# Create Upload Guide
$UploadGuidePath = New-UploadGuide -OutputDir $OutputDir -PluginSlug $PluginSlug -Version $Version -ZipName $ZipName
Write-Success "Created: UPLOAD-GUIDE.md"

# Summary
Write-Header "Build Complete"

Write-Host "Output Files:" -ForegroundColor White
Write-Host "  ZIP:      $ZipPath" -ForegroundColor Gray
Write-Host "  Size:     $ZipSizeKB KB" -ForegroundColor Gray
if (-not $SkipChecksums) {
    Write-Host "  MD5:      $Md5Path" -ForegroundColor Gray
    Write-Host "  SHA256:   $Sha256Path" -ForegroundColor Gray
}
Write-Host "  Log:      $BuildLogPath" -ForegroundColor Gray
Write-Host "  Guide:    $UploadGuidePath" -ForegroundColor Gray

Write-Host ""
Write-Success "Package ready for release!"

exit 0
