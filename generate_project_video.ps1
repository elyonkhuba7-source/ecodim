$ErrorActionPreference = 'Stop'

$projectRoot = 'C:\wamp64\www\ecodim'
$outputDir = Join-Path $projectRoot 'exports'
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

$pptxPath = Join-Path $outputDir 'presentation_ecodim.pptx'
$mp4Path = Join-Path $outputDir 'presentation_ecodim.mp4'
$logPath = Join-Path $outputDir 'presentation_ecodim.log'
$logoPath = Join-Path $projectRoot 'logo.jpg.jpg'

$slides = @(
    @{
        Title = 'Ecodim - Presentation generale'
        Bullets = @(
            'Plateforme de gestion pour l ecole du dimanche',
            'Administration des inscriptions, classes, moniteurs, presences, lecons, evaluations, finances et rapports',
            'Utilisable sur ordinateur et telephone dans le reseau local'
        )
        Duration = 8
    },
    @{
        Title = 'Connexion et acces'
        Bullets = @(
            'Connexion separee pour administrateur et moniteur',
            'Creation de compte moniteur directement depuis la page de login',
            'Mot de passe fort obligatoire et blocage possible des comptes'
        )
        Duration = 8
    },
    @{
        Title = 'Inscriptions des enfants'
        Bullets = @(
            'Ajout, modification et suppression des inscriptions',
            'Fiche d inscription avec Responsable 1 et Responsable 2',
            'Impression de la fiche au format propre'
        )
        Duration = 8
    },
    @{
        Title = 'Classes et synchronisation'
        Bullets = @(
            'Creation libre des classes par nom',
            'Affectation des moniteurs a une classe',
            'Les enfants visibles chez un moniteur dependent de sa classe'
        )
        Duration = 8
    },
    @{
        Title = 'Presences et appel'
        Bullets = @(
            'Appel par date pour la classe du moniteur',
            'Enregistrement des presences des enfants',
            'Consultation des presences et historiques par l administrateur'
        )
        Duration = 8
    },
    @{
        Title = 'Lecons et rapport prof'
        Bullets = @(
            'Lecon liee a une classe et assignable a un moniteur',
            'Theme, sous-theme, objectif pedagogique, passages bibliques, activites et observations',
            'Cloture de la lecon puis envoi dans les rapports'
        )
        Duration = 9
    },
    @{
        Title = 'Evaluations et suivi pedagogique'
        Bullets = @(
            'Fiches d evaluation pour prise de note, interrogation, devoirs, assiduite, TP et evaluations generales',
            'Impression pour le moniteur et consultation admin en format imprimable',
            'Archivage avec possibilite de suppression par l administrateur'
        )
        Duration = 9
    },
    @{
        Title = 'Moniteurs, securite et finances'
        Bullets = @(
            'Profil complet du moniteur avec contacts, fonction, dates et observations',
            'Blocage, deblocage, reinitialisation des mots de passe et suppression admin',
            'Gestion des finances et rapports journaliers, hebdomadaires, mensuels et annuels'
        )
        Duration = 9
    },
    @{
        Title = 'Conclusion'
        Bullets = @(
            'Ecodim couvre la gestion quotidienne de l ecole du dimanche',
            'Le projet est deja structure pour une utilisation locale claire et mobile',
            'La base peut ensuite etre preparee pour une future mise en ligne'
        )
        Duration = 8
    }
)

function Set-TextStyle($shape, $size, $bold, $rgb) {
    $shape.TextFrame2.TextRange.Font.Size = $size
    $shape.TextFrame2.TextRange.Font.Bold = [bool]$bold
    $shape.TextFrame2.TextRange.Font.Name = 'Trebuchet MS'
    $shape.TextFrame2.TextRange.Font.Fill.ForeColor.RGB = $rgb
}

$powerPoint = $null
$presentation = $null

function Log-Line($message) {
    $stamp = (Get-Date).ToString('yyyy-MM-dd HH:mm:ss')
    $line = "$stamp $message"
    Add-Content -Path $logPath -Value $line
    Write-Output $line
}

'' | Set-Content -Path $logPath

try {
    Log-Line 'Ouverture de PowerPoint'
    $powerPoint = New-Object -ComObject PowerPoint.Application
    $powerPoint.Visible = -1

    Log-Line 'Creation de la presentation'
    $powerPoint.Presentations.Add() | Out-Null
    $presentation = $powerPoint.ActivePresentation

    $slideIndex = 1
    foreach ($slideData in $slides) {
        Log-Line "Creation de la slide $slideIndex"
        $slide = $presentation.Slides.Add($slideIndex, 12)
        $slide.FollowMasterBackground = 0
        $slide.Background.Fill.ForeColor.RGB = 0xF4EDE2
        $slide.Background.Fill.BackColor.RGB = 0xFFFFFF
        $slide.Background.Fill.TwoColorGradient(1, 1)

        $title = $slide.Shapes.AddTextbox(1, 70, 55, 1140, 90)
        $title.TextFrame2.TextRange.Text = $slideData.Title
        Set-TextStyle $title 28 $true 0x2A1E10

        $subtitle = $slide.Shapes.AddTextbox(1, 72, 24, 500, 24)
        $subtitle.TextFrame2.TextRange.Text = 'Presentation du projet Ecodim'
        Set-TextStyle $subtitle 11 $true 0x7A674F

        $content = $slide.Shapes.AddTextbox(1, 85, 165, 690, 360)
        $content.TextFrame2.TextRange.Text = ($slideData.Bullets -join "`r")
        $content.TextFrame.TextRange.ParagraphFormat.Bullet.Visible = -1
        $content.TextFrame.TextRange.ParagraphFormat.Bullet.Character = 8226
        $content.TextFrame.TextRange.ParagraphFormat.SpaceAfter = 16
        Set-TextStyle $content 22 $false 0x4E443A

        $side = $slide.Shapes.AddShape(1, 830, 165, 360, 360)
        $side.Fill.ForeColor.RGB = 0xFFFDFC
        $side.Line.ForeColor.RGB = 0xD9C9AF
        $side.Shadow.Visible = -1

        $sideTitle = $slide.Shapes.AddTextbox(1, 860, 195, 300, 40)
        $sideTitle.TextFrame2.TextRange.Text = 'Points cles'
        Set-TextStyle $sideTitle 18 $true 0x1F8A70

        $boxTop = 250
        foreach ($bullet in $slideData.Bullets) {
            $mini = $slide.Shapes.AddShape(1, 860, $boxTop, 300, 60)
            $mini.Fill.ForeColor.RGB = 0xFAF5EC
            $mini.Line.ForeColor.RGB = 0xE3D4BC
            $mini.TextFrame2.TextRange.Text = $bullet
            $mini.TextFrame2.TextRange.Font.Name = 'Trebuchet MS'
            $mini.TextFrame2.TextRange.Font.Size = 14
            $mini.TextFrame2.TextRange.Font.Bold = $false
            $mini.TextFrame2.TextRange.Font.Fill.ForeColor.RGB = 0x40352B
            $mini.TextFrame.MarginLeft = 14
            $mini.TextFrame.MarginRight = 14
            $mini.TextFrame.MarginTop = 10
            $mini.TextFrame.MarginBottom = 8
            $boxTop += 78
        }

        $footer = $slide.Shapes.AddTextbox(1, 72, 648, 1130, 22)
        $footer.TextFrame2.TextRange.Text = 'Ecodim - Gestion de l ecole du dimanche'
        Set-TextStyle $footer 11 $false 0x7A674F

        if ((Test-Path $logoPath) -and $slideIndex -eq 1) {
            $slide.Shapes.AddPicture($logoPath, 0, -1, 1000, 40, 160, 95) | Out-Null
        }

        $slide.SlideShowTransition.AdvanceOnTime = -1
        $slide.SlideShowTransition.AdvanceTime = [double]$slideData.Duration
        $slideIndex++
    }

    if (Test-Path $pptxPath) {
        Remove-Item $pptxPath -Force
    }
    if (Test-Path $mp4Path) {
        Remove-Item $mp4Path -Force
    }

    Log-Line 'Sauvegarde du fichier PPTX'
    $presentation.SaveAs($pptxPath)
    Log-Line 'Demarrage de CreateVideo'
    $presentation.CreateVideo($mp4Path, $false, 8, 720, 24, 85)

    $maxWaitSeconds = 900
    $elapsed = 0
    do {
        Start-Sleep -Seconds 5
        $elapsed += 5
        $status = $presentation.CreateVideoStatus
        Log-Line ("Statut video=" + $status + " temps=" + $elapsed + "s")
    } while (($status -eq 1 -or $status -eq 2 -or -not (Test-Path $mp4Path)) -and $elapsed -lt $maxWaitSeconds)

    if (-not (Test-Path $mp4Path)) {
        throw 'Le fichier MP4 n a pas ete genere dans le delai prevu.'
    }

    Log-Line 'Generation terminee'
    Write-Output "PPTX=$pptxPath"
    Write-Output "MP4=$mp4Path"
}
catch {
    Log-Line ("ERREUR: " + $_.Exception.Message)
    throw
}
finally {
    if ($presentation -ne $null) {
        try {
            $presentation.Close()
        } catch {}
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($presentation)
    }
    if ($powerPoint -ne $null) {
        try {
            $powerPoint.Quit()
        } catch {}
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($powerPoint)
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
