#Requires -Version 5.1

param(
    [string]$Version,
    [switch]$SkipChecksums,
    [switch]$VerboseOutput
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$ConfigFile = Join-Path $ScriptDir "zip-config.json"
$OutputDir = Join-Path $ProjectRoot ".output"

$PluginSlug = "elementor-faq"
$PluginDir = $ProjectRoot

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

function Get-FileHashCustom {
    param(
        [string]$FilePath,
        [string]$Algorithm
    )
    
    $hash = Get-FileHash -Path $FilePath -Algorithm $Algorithm
    return $hash.Hash.ToLower()
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
        exit 1
    }
    
    Write-Success "Version validation passed: Clean semver detected"
}

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

# Create ZIP
Write-Header "Creating ZIP Package"

try {
    # Use Compress-Archive with proper structure
    $TempDir = Join-Path $env:TEMP "elementor-faq-build-$(Get-Random)"
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
            if ($pattern -eq ".git" -and $relativePath -like ".git*") { $exclude = $true; break }
            if ($pattern -eq ".github" -and $relativePath -like ".github*") { $exclude = $true; break }
            if ($pattern -eq ".builder" -and $relativePath -like ".builder*") { $exclude = $true; break }
            if ($pattern -eq ".output" -and $relativePath -like ".output*") { $exclude = $true; break }
            if ($pattern -eq ".ref" -and $relativePath -like ".ref*") { $exclude = $true; break }
            if ($pattern -eq ".opencode" -and $relativePath -like ".opencode*") { $exclude = $true; break }
            if ($pattern -eq "node_modules" -and $relativePath -like "*node_modules*") { $exclude = $true; break }
            if ($pattern -eq "vendor" -and $relativePath -like "*vendor*") { $exclude = $true; break }
            if ($pattern -eq "README.md" -and $relativePath -eq "README.md") { $exclude = $true; break }
            if ($pattern -eq "CHANGELOG.md" -and $relativePath -eq "CHANGELOG.md") { $exclude = $true; break }
            if ($pattern -eq "composer.json" -and $relativePath -eq "composer.json") { $exclude = $true; break }
            if ($pattern -eq "composer.lock" -and $relativePath -eq "composer.lock") { $exclude = $true; break }
            if ($pattern -eq "package.json" -and $relativePath -eq "package.json") { $exclude = $true; break }
            if ($pattern -eq "package-lock.json" -and $relativePath -eq "package-lock.json") { $exclude = $true; break }
            if ($pattern -eq ".gitignore" -and $relativePath -eq ".gitignore") { $exclude = $true; break }
            if ($pattern -eq ".env" -and $relativePath -eq ".env") { $exclude = $true; break }
            if ($pattern -like ".env.*" -and $relativePath -like ".env.*") { $exclude = $true; break }
            if ($pattern -eq "*.map" -and $relativePath -like "*.map") { $exclude = $true; break }
            if ($pattern -eq "plugin builder" -and $relativePath -like "plugin builder*") { $exclude = $true; break }
            if ($pattern -eq "AGENTS.md" -and $relativePath -eq "AGENTS.md") { $exclude = $true; break }
        }
        
        if (-not $exclude) {
            $destPath = Join-Path $StagingDir $relativePath
            $destDir = Split-Path -Parent $destPath
            
            if (-not (Test-Path $destDir)) {
                New-Item -ItemType Directory -Path $destDir -Force | Out-Null
            }
            
            Copy-Item -Path $file.FullName -Destination $destPath -Force
            $FilesCopied++
        }
    }
    
    Write-Info "Copied $FilesCopied files to staging"
    
    # Create ZIP with forward-slash paths
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    
    $ZipStream = [System.IO.FileStream]::new($ZipPath, [System.IO.FileMode]::Create, [System.IO.FileAccess]::ReadWrite, [System.IO.FileShare]::None)
    $ZipArchive = [System.IO.Compression.ZipArchive]::new($ZipStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
    
    $StagingFiles = Get-ChildItem -Path $StagingDir -Recurse -File
    
    foreach ($stagedFile in $StagingFiles) {
        $relativePath = $stagedFile.FullName.Substring($StagingDir.Length + 1)
        $entryName = $PluginSlug + "/" + $relativePath.Replace('\', '/')
        
        $entry = $ZipArchive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        
        $writer = $entry.Open()
        $reader = [System.IO.File]::OpenRead($stagedFile.FullName)
        $reader.CopyTo($writer)
        $reader.Dispose()
        $writer.Dispose()
    }
    
    $ZipArchive.Dispose()
    $ZipStream.Dispose()
    
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
    
    $Md5Hash = Get-FileHashCustom -FilePath $ZipPath -Algorithm "MD5"
    "$Md5Hash  $ZipName" | Out-File -FilePath $Md5Path -Encoding ascii -NoNewline
    Write-Success "MD5: $Md5Hash"
    
    $Sha256Hash = Get-FileHashCustom -FilePath $ZipPath -Algorithm "SHA256"
    "$Sha256Hash  $ZipName" | Out-File -FilePath $Sha256Path -Encoding ascii -NoNewline
    Write-Success "SHA256: $Sha256Hash"
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
    }
}

$BuildLog | ConvertTo-Json -Depth 10 | Out-File -FilePath $BuildLogPath -Encoding UTF8
Write-Success "Created: build-log.json"

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

Write-Host ""
Write-Success "Package ready for release!"

exit 0
