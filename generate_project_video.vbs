Option Explicit

Dim projectRoot, outputDir, pptxPath, mp4Path, logPath, logoPath, captureDir
projectRoot = "C:\wamp64\www\ecodim"
outputDir = projectRoot & "\exports"
pptxPath = outputDir & "\presentation_ecodim_interfaces_images.pptx"
mp4Path = outputDir & "\presentation_ecodim_interfaces_images.mp4"
logPath = outputDir & "\presentation_ecodim_interfaces_images.log"
logoPath = projectRoot & "\logo.jpg.jpg"
captureDir = outputDir & "\captures"

Dim fso
Set fso = CreateObject("Scripting.FileSystemObject")
If Not fso.FolderExists(outputDir) Then
    fso.CreateFolder outputDir
End If

Dim logFile
Set logFile = fso.OpenTextFile(logPath, 2, True)

Sub LogLine(message)
    logFile.WriteLine Now & " " & message
    WScript.Echo Now & " " & message
End Sub

Dim titles(14), bullets(14), durations(14), images(14)

titles(0) = "Ecodim - Presentation generale"
bullets(0) = Array( _
    "Plateforme de gestion pour l ecole du dimanche", _
    "Toutes les interfaces importantes sont presentees dans cette video", _
    "Utilisable sur ordinateur et telephone dans le reseau local" _
)
durations(0) = 8
images(0) = captureDir & "\02_dashboard_admin.png"

titles(1) = "Connexion et acces"
bullets(1) = Array( _
    "Page de login avec connexion securisee", _
    "Creation de compte moniteur directement depuis le login", _
    "Mot de passe fort et blocage des comptes" _
)
durations(1) = 8
images(1) = captureDir & "\01_login.png"

titles(2) = "Tableau de bord administrateur"
bullets(2) = Array( _
    "Vue d ensemble du systeme pour l administrateur", _
    "Acces rapide vers inscriptions, presences, lecons, moniteurs, rapports et finances", _
    "Navigation centrale pour piloter toute l application" _
)
durations(2) = 8
images(2) = captureDir & "\02_dashboard_admin.png"

titles(3) = "Inscriptions des enfants"
bullets(3) = Array( _
    "Ajout, modification et suppression des inscriptions", _
    "Fiche avec Responsable 1 et Responsable 2", _
    "Liste des enfants avec acces aux actions" _
)
durations(3) = 8
images(3) = captureDir & "\03_inscriptions.png"

titles(4) = "Fiche d inscription imprimable"
bullets(4) = Array( _
    "Mise en page proche du document papier", _
    "Informations de l enfant et des deux responsables", _
    "Impression directe depuis l application" _
)
durations(4) = 8
images(4) = captureDir & "\03_inscriptions.png"

titles(5) = "Classes"
bullets(5) = Array( _
    "Creation libre des classes par nom", _
    "Vue de synthese des classes existantes", _
    "Lien direct entre classes, enfants et moniteurs" _
)
durations(5) = 9
images(5) = captureDir & "\04_classes.png"

titles(6) = "Moniteurs"
bullets(6) = Array( _
    "Profil complet du moniteur avec contacts et fonction", _
    "Affectation a une classe et mise a jour des informations", _
    "Suppression reservee a l administrateur" _
)
durations(6) = 9
images(6) = captureDir & "\05_moniteurs.png"

titles(7) = "Securite"
bullets(7) = Array( _
    "Gestion des mots de passe", _
    "Blocage et deblocage des moniteurs", _
    "Protection des acces selon le role" _
)
durations(7) = 9
images(7) = captureDir & "\06_securite.png"

titles(8) = "Presences"
bullets(8) = Array( _
    "Appel par date avec affichage de la liste", _
    "Gestion des presences des enfants", _
    "Consultation et suivi par l administrateur" _
)
durations(8) = 8
images(8) = captureDir & "\07_presences.png"

titles(9) = "Tableau de bord moniteur"
bullets(9) = Array( _
    "Espace simplifie pour le moniteur", _
    "Acces rapide a l appel, a la lecon et a la fiche d evaluation", _
    "Affichage centre sur sa classe et ses donnees" _
)
durations(9) = 8
images(9) = captureDir & "\08_dashboard_moniteur.png"

titles(10) = "Lecons et rapport prof"
bullets(10) = Array( _
    "Lecon liee a une classe et a un moniteur", _
    "Theme, sous theme, objectif pedagogique et passages bibliques", _
    "Cloture de la lecon puis envoi dans les rapports" _
)
durations(10) = 9
images(10) = captureDir & "\09_lecons.png"

titles(11) = "Fiche d evaluation"
bullets(11) = Array( _
    "Prise de note, interrogation, devoirs, assiduite, TP et evaluations generales", _
    "Impression possible pour le moniteur", _
    "Consultation admin en format imprimable" _
)
durations(11) = 9
images(11) = captureDir & "\10_evaluations.png"

titles(12) = "Rapports"
bullets(12) = Array( _
    "Rapports des lecons terminees", _
    "Rapports hebdomadaires pedagogiques", _
    "Suppression reservee a l administrateur" _
)
durations(12) = 8
images(12) = captureDir & "\11_rapports.png"

titles(13) = "Finances"
bullets(13) = Array( _
    "Rapports journaliers, hebdomadaires, mensuels et annuels", _
    "Suivi des offrandes, dons, depenses et observations", _
    "Impression des rapports financiers" _
)
durations(13) = 8
images(13) = captureDir & "\12_finances.png"

titles(14) = "Conclusion"
bullets(14) = Array( _
    "Ecodim couvre toutes les interfaces essentielles de gestion", _
    "L administrateur et le moniteur ont chacun leur espace adapte", _
    "La plateforme est prete pour une presentation claire du projet" _
)
durations(14) = 8
images(14) = captureDir & "\02_dashboard_admin.png"

Function JoinBullets(items)
    Dim i, text
    text = ""
    For i = 0 To UBound(items)
        If i > 0 Then
            text = text & vbCrLf
        End If
        text = text & ChrW(8226) & " " & items(i)
    Next
    JoinBullets = text
End Function

Sub AddPictureWithRetry(slideObj, imagePath, posLeft, posTop, picWidth, picHeight)
    Dim attempt
    For attempt = 1 To 8
        On Error Resume Next
        Err.Clear
        slideObj.Shapes.AddPicture imagePath, False, True, posLeft, posTop, picWidth, picHeight
        If Err.Number = 0 Then
            On Error GoTo 0
            Exit Sub
        End If
        On Error GoTo 0
        WScript.Sleep 800
    Next
End Sub

Function AddSlideWithRetry(presObj, slideIndex, layoutId)
    Dim attempt, createdSlide
    Set createdSlide = Nothing
    For attempt = 1 To 8
        On Error Resume Next
        Err.Clear
        Set createdSlide = presObj.Slides.Add(slideIndex, layoutId)
        If Err.Number = 0 Then
            On Error GoTo 0
            Set AddSlideWithRetry = createdSlide
            Exit Function
        End If
        On Error GoTo 0
        WScript.Sleep 800
    Next
    Set AddSlideWithRetry = createdSlide
End Function

Function CreatePresentationWithRetry(appObj)
    Dim attempt, createdPresentation
    Set createdPresentation = Nothing
    For attempt = 1 To 8
        On Error Resume Next
        Err.Clear
        Set createdPresentation = appObj.Presentations.Add
        If Err.Number = 0 Then
            On Error GoTo 0
            Set CreatePresentationWithRetry = createdPresentation
            Exit Function
        End If
        On Error GoTo 0
        WScript.Sleep 800
    Next
    Set CreatePresentationWithRetry = createdPresentation
End Function

Dim ppApp, pres, slide, i, textRange, status, elapsed, bodyShape
On Error Resume Next
LogLine "Ouverture de PowerPoint"
Set ppApp = CreateObject("PowerPoint.Application")
If Err.Number <> 0 Then
    LogLine "ERREUR ouverture PowerPoint: " & Err.Description
    WScript.Quit 1
End If
On Error GoTo 0

ppApp.Visible = True

LogLine "Creation de la presentation"
Set pres = CreatePresentationWithRetry(ppApp)
If pres Is Nothing Then
    LogLine "ERREUR: Presentation impossible a creer"
    ppApp.Quit
    logFile.Close
    WScript.Quit 1
End If

For i = 0 To 14
    LogLine "Creation de la slide " & (i + 1)
    Set slide = AddSlideWithRetry(pres, i + 1, 2)
    If slide Is Nothing Then
        LogLine "ERREUR: Slide impossible a creer"
        pres.Close
        ppApp.Quit
        logFile.Close
        WScript.Quit 1
    End If
    WScript.Sleep 400

    slide.Shapes.Title.TextFrame.TextRange.Text = titles(i)

    Set bodyShape = slide.Shapes.Placeholders(2)
    bodyShape.Left = 24
    bodyShape.Top = 120
    bodyShape.Width = 250
    bodyShape.Height = 390

    Set textRange = slide.Shapes.Placeholders(2).TextFrame.TextRange
    textRange.Text = JoinBullets(bullets(i))

    If fso.FileExists(images(i)) Then
        WScript.Sleep 300
        AddPictureWithRetry slide, images(i), 290, 120, 630, 354
    End If

    slide.SlideShowTransition.AdvanceOnTime = True
    slide.SlideShowTransition.AdvanceTime = durations(i)

Next

If fso.FileExists(pptxPath) Then fso.DeleteFile pptxPath, True
If fso.FileExists(mp4Path) Then fso.DeleteFile mp4Path, True

LogLine "Sauvegarde du fichier PPTX"
pres.SaveAs pptxPath

LogLine "Demarrage de CreateVideo"
pres.CreateVideo mp4Path, False, 8, 720, 24, 85

elapsed = 0
Do
    WScript.Sleep 5000
    elapsed = elapsed + 5
    status = pres.CreateVideoStatus
    LogLine "Statut video=" & status & " temps=" & elapsed & "s"
Loop While (status = 1 Or status = 2 Or Not fso.FileExists(mp4Path)) And elapsed < 900

If Not fso.FileExists(mp4Path) Then
    LogLine "ERREUR: Le fichier MP4 n a pas ete genere dans le delai prevu."
    pres.Close
    ppApp.Quit
    logFile.Close
    WScript.Quit 1
End If

LogLine "Generation terminee"
WScript.Echo "PPTX=" & pptxPath
WScript.Echo "MP4=" & mp4Path

On Error Resume Next
pres.Close
ppApp.Quit
logFile.Close
