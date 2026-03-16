#Requires -Version 5.1

<#
.SYNOPSIS
    Pre-ZIP Test Runner for Elementor FAQ Plugin
    
.DESCRIPTION
    Runs PHPUnit tests before creating a release ZIP.
    Validates plugin structure and ZIP integrity.
    
.PARAMETER Full
    Run the complete test suite
    
.PARAMETER Verbose
    Show detailed test output
    
.PARAMETER IncludeZipStructure
    Include ZIP structure validation tests
    
.EXAMPLE
    .\pre-zip-tests.ps1
    Runs basic tests
    
.EXAMPLE
    .\pre-zip-tests.ps1 -Full -IncludeZipStructure
    Runs complete test suite with ZIP validation
    
.EXITCODES
    0 - All tests passed
    1 - Tests failed or errors occurred
#>

param(
    [switch]$Full,
    [switch]$VerboseOutput,
    [switch]$IncludeZipStructure
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$TestDir = Join-Path $ProjectRoot ".test"
$PhpunitBin = Join-Path $TestDir "vendor\bin\phpunit.bat"
$PhpunitConfig = Join-Path $TestDir "config\phpunit.xml"

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
    Write-Host "[PASS] $Message" -ForegroundColor Green
}

function Write-Fail {
    param([string]$Message)
    Write-Host "[FAIL] $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Yellow
}

Write-Header "Pre-ZIP Test Runner"

# Check PHPUnit exists
if (-not (Test-Path $PhpunitBin)) {
    Write-Fail "PHPUnit not found at: $PhpunitBin"
    Write-Info "Run 'cd .test && composer install' to install dependencies"
    exit 1
}

# Check test config exists
if (-not (Test-Path $PhpunitConfig)) {
    Write-Fail "PHPUnit config not found at: $PhpunitConfig"
    exit 1
}

# Change to test directory
Set-Location $TestDir

$TestsPassed = $true
$StartTime = Get-Date

if ($Full) {
    Write-Header "Running Full Test Suite"
    
    $Arguments = @(
        "--configuration", $PhpunitConfig,
        "--testsuite", "All",
        "--colors=always"
    )
    
    if ($VerboseOutput) {
        $Arguments += "--verbose"
    }
    
    try {
        & $PhpunitBin @Arguments
        if ($LASTEXITCODE -ne 0) {
            $TestsPassed = $false
        }
    }
    catch {
        Write-Fail "Test execution failed: $_"
        $TestsPassed = $false
    }
}
else {
    Write-Header "Running Activator Tests"
    
    # Run Activator-specific tests
    $Arguments = @(
        "--configuration", $PhpunitConfig,
        "--filter", "Activator",
        "--colors=always"
    )
    
    if ($VerboseOutput) {
        $Arguments += "--verbose"
    }
    
    try {
        & $PhpunitBin @Arguments
        if ($LASTEXITCODE -ne 0) {
            $TestsPassed = $false
        }
    }
    catch {
        Write-Fail "Test execution failed: $_"
        $TestsPassed = $false
    }
}

$EndTime = Get-Date
$Duration = $EndTime - $StartTime

# Run ZIP Structure Tests if requested
if ($IncludeZipStructure -or $Full) {
    Write-Header "Running ZIP Structure Tests"
    
    $ZipTestArguments = @(
        "--configuration", $PhpunitConfig,
        "--filter", "ZIP_Structure_Test",
        "--colors=always"
    )
    
    if ($VerboseOutput) {
        $ZipTestArguments += "--verbose"
    }
    
    try {
        & $PhpunitBin @ZipTestArguments
        if ($LASTEXITCODE -ne 0) {
            $TestsPassed = $false
            Write-Fail "ZIP structure tests failed!"
            Write-Host ""
            Write-Host "Common issues:" -ForegroundColor Yellow
            Write-Host "  - Root folder contains version suffix (e.g., plugin-1.4.0 instead of plugin)" -ForegroundColor Yellow
            Write-Host "  - Root folder name doesn't match plugin slug exactly" -ForegroundColor Yellow
            Write-Host "  - Required files missing from ZIP" -ForegroundColor Yellow
            Write-Host ""
            Write-Host "Run '.builder\build-zip.ps1' to rebuild the ZIP with correct structure." -ForegroundColor Yellow
        } else {
            Write-Success "ZIP structure tests passed!"
        }
    }
    catch {
        Write-Fail "ZIP structure test execution failed: $_"
        $TestsPassed = $false
    }
    
    # Additional ZIP validation using PowerShell
    Write-Header "ZIP Structure Validation"
    
    $OutputDir = Join-Path $ProjectRoot ".output"
    $PluginSlug = "elementor-faq"
    $ZipFiles = Get-ChildItem -Path $OutputDir -Filter "$PluginSlug-*.zip" -File | Sort-Object LastWriteTime -Descending
    
    if ($ZipFiles.Count -eq 0) {
        Write-Info "No ZIP file found in .output directory - skipping PowerShell validation"
        Write-Info "Build ZIP first with: .builder\build-zip.ps1"
    }
    else {
        $LatestZip = $ZipFiles[0]
        Write-Info "Validating: $($LatestZip.Name)"
        
        try {
            Add-Type -AssemblyName System.IO.Compression.FileSystem
            $Zip = [System.IO.Compression.ZipFile]::OpenRead($LatestZip.FullName)
            
            # Get root folder
            $FirstEntry = $Zip.Entries[0].FullName.Replace('\', '/')
            $RootFolder = $FirstEntry.Split('/')[0]
            
            # Validate root folder
            $ZipValid = $true
            
            if ($RootFolder -ne $PluginSlug) {
                Write-Fail "Root folder mismatch!"
                Write-Host "  Expected: $PluginSlug" -ForegroundColor Red
                Write-Host "  Got:      $RootFolder" -ForegroundColor Red
                $ZipValid = $false
            }
            
            if ($RootFolder -match '-[0-9]+\.[0-9]+') {
                Write-Fail "Root folder contains version suffix!"
                Write-Host "  This will cause WordPress to NOT detect previous version!" -ForegroundColor Red
                $ZipValid = $false
            }
            
            # Check for main plugin file
            $MainPluginFile = "$PluginSlug/$PluginSlug.php"
            $Found = $false
            foreach ($entry in $Zip.Entries) {
                if ($entry.FullName.Replace('\', '/') -eq $MainPluginFile) {
                    $Found = $true
                    break
                }
            }
            
            if (-not $Found) {
                Write-Fail "Main plugin file not found at root level!"
                Write-Host "  Expected: $MainPluginFile" -ForegroundColor Red
                $ZipValid = $false
            }
            
            $Zip.Dispose()
            
            if ($ZipValid) {
                Write-Success "ZIP structure validation passed"
                Write-Info "Root folder: $RootFolder"
            }
            else {
                $TestsPassed = $false
                Write-Fail "ZIP structure validation failed!"
                Write-Host ""
                Write-Host "Rebuild ZIP with: .builder\build-zip.ps1" -ForegroundColor Yellow
            }
        }
        catch {
            Write-Fail "Failed to validate ZIP: $_"
            $TestsPassed = $false
        }
    }
}

Write-Header "Test Results"

if ($TestsPassed) {
    Write-Success "All tests passed!"
    Write-Info "Duration: $($Duration.TotalSeconds.ToString('F2')) seconds"
    Write-Host ""
    Write-Host "Ready to create ZIP package." -ForegroundColor Green
    exit 0
}
else {
    Write-Fail "Tests failed!"
    Write-Info "Duration: $($Duration.TotalSeconds.ToString('F2')) seconds"
    Write-Host ""
    Write-Host "Fix failing tests before creating ZIP package." -ForegroundColor Red
    exit 1
}
