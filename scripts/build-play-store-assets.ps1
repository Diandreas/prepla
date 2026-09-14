param(
    [string]$Workspace = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Drawing

$releaseRoot = Join-Path $Workspace 'release\google-play'
$graphicsRoot = Join-Path $releaseRoot 'graphics'
$screenshotsRoot = Join-Path $releaseRoot 'screenshots\phone'
$sourceRoot = Join-Path $releaseRoot 'source'

New-Item -ItemType Directory -Force -Path $graphicsRoot, $screenshotsRoot, $sourceRoot | Out-Null

function New-RoundedRectanglePath {
    param(
        [System.Drawing.RectangleF]$Rectangle,
        [float]$Radius
    )

    $diameter = $Radius * 2
    $path = [System.Drawing.Drawing2D.GraphicsPath]::new()
    $path.AddArc($Rectangle.X, $Rectangle.Y, $diameter, $diameter, 180, 90)
    $path.AddArc($Rectangle.Right - $diameter, $Rectangle.Y, $diameter, $diameter, 270, 90)
    $path.AddArc($Rectangle.Right - $diameter, $Rectangle.Bottom - $diameter, $diameter, $diameter, 0, 90)
    $path.AddArc($Rectangle.X, $Rectangle.Bottom - $diameter, $diameter, $diameter, 90, 90)
    $path.CloseFigure()

    return $path
}

function New-AppFont {
    param(
        [float]$Size,
        [System.Drawing.FontStyle]$Style = [System.Drawing.FontStyle]::Regular
    )

    return [System.Drawing.Font]::new('Segoe UI', $Size, $Style, [System.Drawing.GraphicsUnit]::Pixel)
}

function Convert-UnicodeText {
    param([string]$Text)

    return [System.Text.RegularExpressions.Regex]::Unescape($Text)
}

function Set-HighQualityGraphics {
    param([System.Drawing.Graphics]$Graphics)

    $Graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $Graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $Graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $Graphics.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
}

function Add-BrandedBackground {
    param(
        [System.Drawing.Graphics]$Graphics,
        [int]$Width,
        [int]$Height
    )

    $canvas = [System.Drawing.Rectangle]::new(0, 0, $Width, $Height)
    $gradient = [System.Drawing.Drawing2D.LinearGradientBrush]::new(
        $canvas,
        [System.Drawing.ColorTranslator]::FromHtml('#06172F'),
        [System.Drawing.ColorTranslator]::FromHtml('#0E3269'),
        35
    )
    $Graphics.FillRectangle($gradient, $canvas)
    $gradient.Dispose()

    $blueGlow = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::FromArgb(34, 62, 148, 255))
    $goldGlow = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::FromArgb(28, 245, 166, 35))
    $Graphics.FillEllipse($blueGlow, -190, 100, 620, 620)
    $Graphics.FillEllipse($goldGlow, $Width - 310, 40, 420, 420)
    $blueGlow.Dispose()
    $goldGlow.Dispose()
}

function Save-Png {
    param(
        [System.Drawing.Bitmap]$Bitmap,
        [string]$Path
    )

    $Bitmap.Save($Path, [System.Drawing.Imaging.ImageFormat]::Png)
}

function New-PhoneScreenshot {
    param(
        [string]$Source,
        [string]$Output,
        [string]$Number,
        [string]$Title,
        [string]$Subtitle
    )

    $width = 1080
    $height = 1920
    $bitmap = [System.Drawing.Bitmap]::new($width, $height, [System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    Set-HighQualityGraphics $graphics
    Add-BrandedBackground $graphics $width $height

    $labelFont = New-AppFont 24 ([System.Drawing.FontStyle]::Bold)
    $titleFont = New-AppFont 54 ([System.Drawing.FontStyle]::Bold)
    $subtitleFont = New-AppFont 29
    $white = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::White)
    $muted = [System.Drawing.SolidBrush]::new([System.Drawing.ColorTranslator]::FromHtml('#B9CCE8'))
    $accent = [System.Drawing.SolidBrush]::new([System.Drawing.ColorTranslator]::FromHtml('#F5A623'))

    $label = Convert-UnicodeText "PREPLA  \u00B7  $Number"
    $safeTitle = Convert-UnicodeText $Title
    $safeSubtitle = Convert-UnicodeText $Subtitle
    $graphics.DrawString($label, $labelFont, $accent, 72, 62)
    $titleRect = [System.Drawing.RectangleF]::new(68, 120, 944, 160)
    $graphics.DrawString($safeTitle, $titleFont, $white, $titleRect)
    $subtitleRect = [System.Drawing.RectangleF]::new(72, 292, 930, 92)
    $graphics.DrawString($safeSubtitle, $subtitleFont, $muted, $subtitleRect)

    $image = [System.Drawing.Image]::FromFile($Source)
    $maxWidth = 900.0
    $maxHeight = 1450.0
    $scale = [Math]::Min($maxWidth / $image.Width, $maxHeight / $image.Height)
    $renderWidth = [float]($image.Width * $scale)
    $renderHeight = [float]($image.Height * $scale)
    $x = [float](($width - $renderWidth) / 2)
    $y = [float](420 + (($maxHeight - $renderHeight) / 2))
    $screenRect = [System.Drawing.RectangleF]::new($x, $y, $renderWidth, $renderHeight)

    $shadowRect = [System.Drawing.RectangleF]::new($x - 12, $y + 14, $renderWidth + 24, $renderHeight + 24)
    $shadowPath = New-RoundedRectanglePath $shadowRect 44
    $shadow = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::FromArgb(85, 0, 0, 0))
    $graphics.FillPath($shadow, $shadowPath)

    $screenPath = New-RoundedRectanglePath $screenRect 38
    $previousClip = $graphics.Clip
    $graphics.SetClip($screenPath)
    $graphics.DrawImage($image, $screenRect)
    $graphics.Clip = $previousClip

    $border = [System.Drawing.Pen]::new([System.Drawing.Color]::FromArgb(150, 120, 178, 245), 3)
    $graphics.DrawPath($border, $screenPath)

    # Draw the small sequence label last so it always stays above the source image.
    $graphics.DrawString($label, $labelFont, $accent, 72, 62)

    Save-Png $bitmap $Output

    $border.Dispose()
    $screenPath.Dispose()
    $shadow.Dispose()
    $shadowPath.Dispose()
    $image.Dispose()
    $white.Dispose()
    $muted.Dispose()
    $accent.Dispose()
    $labelFont.Dispose()
    $titleFont.Dispose()
    $subtitleFont.Dispose()
    $graphics.Dispose()
    $bitmap.Dispose()
}

function New-PhoneAppScreenshot {
    param(
        [string]$Source,
        [string]$Output
    )

    # Google Play expects a phone-friendly portrait ratio. Crop only the thin
    # browser edges/scrollbar, then scale the real application capture to 9:16.
    $width = 1080
    $height = 1920
    $bitmap = [System.Drawing.Bitmap]::new($width, $height, [System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    Set-HighQualityGraphics $graphics

    $image = [System.Drawing.Image]::FromFile($Source)
    $sourceRatio = $image.Width / $image.Height
    $targetRatio = $width / $height

    if ($sourceRatio -gt $targetRatio) {
        $cropWidth = [int]([Math]::Round($image.Height * $targetRatio))
        $crop = [System.Drawing.Rectangle]::new([int](($image.Width - $cropWidth) / 2), 0, $cropWidth, $image.Height)
    } else {
        $cropHeight = [int]([Math]::Round($image.Width / $targetRatio))
        $crop = [System.Drawing.Rectangle]::new(0, [int](($image.Height - $cropHeight) / 2), $image.Width, $cropHeight)
    }

    $destination = [System.Drawing.Rectangle]::new(0, 0, $width, $height)
    $graphics.DrawImage($image, $destination, $crop, [System.Drawing.GraphicsUnit]::Pixel)
    Save-Png $bitmap $Output

    $image.Dispose()
    $graphics.Dispose()
    $bitmap.Dispose()
}

$featureSource = Join-Path $sourceRoot 'feature-graphic-ai-source.png'
if (-not (Test-Path -LiteralPath $featureSource)) {
    throw "Source manquante : $featureSource"
}

$feature = [System.Drawing.Bitmap]::new(1024, 500, [System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
$featureGraphics = [System.Drawing.Graphics]::FromImage($feature)
Set-HighQualityGraphics $featureGraphics
$generated = [System.Drawing.Image]::FromFile($featureSource)

$sourceRatio = $generated.Width / $generated.Height
$targetRatio = 1024 / 500
if ($sourceRatio -lt $targetRatio) {
    $cropHeight = [int]($generated.Width / $targetRatio)
    $crop = [System.Drawing.Rectangle]::new(0, [int](($generated.Height - $cropHeight) / 2), $generated.Width, $cropHeight)
} else {
    $cropWidth = [int]($generated.Height * $targetRatio)
    $crop = [System.Drawing.Rectangle]::new([int](($generated.Width - $cropWidth) / 2), 0, $cropWidth, $generated.Height)
}

$destination = [System.Drawing.Rectangle]::new(0, 0, 1024, 500)
$featureGraphics.DrawImage($generated, $destination, $crop, [System.Drawing.GraphicsUnit]::Pixel)

$leftShade = [System.Drawing.Drawing2D.LinearGradientBrush]::new(
    [System.Drawing.Rectangle]::new(0, 0, 650, 500),
    [System.Drawing.Color]::FromArgb(238, 4, 17, 39),
    [System.Drawing.Color]::FromArgb(16, 4, 17, 39),
    0
)
$featureGraphics.FillRectangle($leftShade, 0, 0, 650, 500)

$iconPath = Join-Path $Workspace 'public\icons\pwa-512-v4.png'
$icon = [System.Drawing.Image]::FromFile($iconPath)
$featureGraphics.DrawImage($icon, 48, 42, 74, 74)

$brandFont = New-AppFont 31 ([System.Drawing.FontStyle]::Bold)
$featureTitleFont = New-AppFont 50 ([System.Drawing.FontStyle]::Bold)
$featureSubtitleFont = New-AppFont 22
$featureWhite = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::White)
$featureMuted = [System.Drawing.SolidBrush]::new([System.Drawing.ColorTranslator]::FromHtml('#BDD2EE'))
$featureAccent = [System.Drawing.SolidBrush]::new([System.Drawing.ColorTranslator]::FromHtml('#F5A623'))

$featureGraphics.DrawString('Prep', $brandFont, $featureWhite, 136, 58)
$featureGraphics.DrawString('La', $brandFont, $featureAccent, 205, 58)
$featureTitle = Convert-UnicodeText "Ton entra\u00EEnement,`npropuls\u00E9 par l'IA"
$featureSubtitle = Convert-UnicodeText 'Exercices  \u2022  corrections  \u2022  progression'
$featureGraphics.DrawString($featureTitle, $featureTitleFont, $featureWhite, [System.Drawing.RectangleF]::new(48, 150, 500, 138))
$featureGraphics.DrawString($featureSubtitle, $featureSubtitleFont, $featureMuted, 52, 330)

Save-Png $feature (Join-Path $graphicsRoot 'feature-graphic-1024x500.png')

$featureAccent.Dispose()
$featureMuted.Dispose()
$featureWhite.Dispose()
$featureSubtitleFont.Dispose()
$featureTitleFont.Dispose()
$brandFont.Dispose()
$icon.Dispose()
$leftShade.Dispose()
$generated.Dispose()
$featureGraphics.Dispose()
$feature.Dispose()

$storeIcon = [System.Drawing.Bitmap]::new(512, 512, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$storeIconGraphics = [System.Drawing.Graphics]::FromImage($storeIcon)
Set-HighQualityGraphics $storeIconGraphics
$storeIconGraphics.Clear([System.Drawing.Color]::Transparent)
$storeIconSource = [System.Drawing.Image]::FromFile($iconPath)
$storeIconGraphics.DrawImage($storeIconSource, 0, 0, 512, 512)
Save-Png $storeIcon (Join-Path $graphicsRoot 'icon-512.png')
$storeIconSource.Dispose()
$storeIconGraphics.Dispose()
$storeIcon.Dispose()

$shots = @(
    @{
        Source = 'docs\resources\prod-welcome.png'
        Output = '01-accueil.png'
        Number = '01'
        Title = 'Ta pr\u00E9paration commence ici'
        Subtitle = 'Une exp\u00E9rience mobile claire, rapide et motivante'
    },
    @{
        Source = 'docs\resources\smoke-after-login.png'
        Output = '02-objectif-examen.png'
        Number = '02'
        Title = "Choisis ton objectif d'examen"
        Subtitle = 'Anglais, fran\u00E7ais ou allemand, du niveau A1 \u00E0 C2'
    },
    @{
        Source = 'docs\resources\smoke-exam-modal-bug-f-fix.png'
        Output = '03-simulation.png'
        Number = '03'
        Title = 'Lance une simulation cibl\u00E9e'
        Subtitle = 'S\u00E9lectionne la section et le nombre de questions'
    },
    @{
        Source = 'public\screenshots\practice-mobile.png'
        Output = '04-exercice.png'
        Number = '04'
        Title = 'Entra\u00EEne-toi comme le jour J'
        Subtitle = 'Des formats chronom\u00E9tr\u00E9s adapt\u00E9s \u00E0 chaque examen'
    },
    @{
        Source = 'public\screenshots\login-mobile.png'
        Output = '05-parcours.png'
        Number = '05'
        Title = 'Retrouve ton parcours partout'
        Subtitle = 'Connecte-toi et reprends exactement o\u00F9 tu en \u00E9tais'
    },
    @{
        Source = 'public\screenshots\ai-tools-mobile.png'
        Output = '06-outils-ia.png'
        Number = '06'
        Title = 'Cr\u00E9e un exercice sur mesure'
        Subtitle = "Type, th\u00E8me et difficult\u00E9 adapt\u00E9s par l'IA"
    }
)

foreach ($shot in $shots) {
    New-PhoneAppScreenshot `
        -Source (Join-Path $Workspace $shot.Source) `
        -Output (Join-Path $screenshotsRoot $shot.Output)
}

Write-Output "Google Play assets generated in $releaseRoot"
