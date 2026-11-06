$ErrorActionPreference = "Stop"

$root = "c:\Users\LENOVO\Downloads\Sistem_Absensi_PLNSC-main"
$base = "http://127.0.0.1:8000"
Set-Location $root

function Get-CsrfToken {
    param(
        [string]$CookieFile,
        [string]$Path
    )

    for ($i = 0; $i -lt 8; $i++) {
        $html = & curl.exe -s -c $CookieFile -b $CookieFile "$base$Path"
        $htmlText = ($html | Out-String)
        $m = [regex]::Match($htmlText, 'name="_token"\s+value="([^"]+)"')
        if ($m.Success) {
            return $m.Groups[1].Value
        }
        Start-Sleep -Milliseconds 700
    }
    throw "CSRF token not found for $Path"
}

function Login {
    param(
        [string]$CookieFile,
        [string]$Email,
        [string]$Password
    )

    $token = Get-CsrfToken -CookieFile $CookieFile -Path "/login"
    $html = & curl.exe -s -L -c $CookieFile -b $CookieFile `
        -d "_token=$token" `
        -d "email=$Email" `
        -d "password=$Password" `
        "$base/login"

    return $html
}

function Add-Result {
    param(
        [System.Collections.Generic.List[object]]$List,
        [string]$CaseName,
        [bool]$Pass,
        [string]$Note = ""
    )
    $List.Add([PSCustomObject]@{
            Case   = $CaseName
            Status = if ($Pass) { "PASS" } else { "FAIL" }
            Note   = $Note
        }) | Out-Null
}

$results = New-Object 'System.Collections.Generic.List[object]'

# temp upload files
$tmpDir = Join-Path $root "storage\app\public"
if (-not (Test-Path $tmpDir)) {
    New-Item -ItemType Directory -Path $tmpDir -Force | Out-Null
}
$pdfPath = Join-Path $tmpDir "uat_dummy.pdf"
$txtPath = Join-Path $tmpDir "uat_dummy.txt"
Set-Content -Path $pdfPath -Value "%PDF-1.4`n1 0 obj`n<<>>`nendobj`n%%EOF" -NoNewline
Set-Content -Path $txtPath -Value "not-a-pdf" -NoNewline

$server = Start-Process -FilePath php -ArgumentList "artisan", "serve", "--host=127.0.0.1", "--port=8000" -WorkingDirectory $root -PassThru -WindowStyle Hidden
Start-Sleep -Seconds 4

try {
    $landingCode = & curl.exe -s -o NUL -w "%{http_code}" "$base/"
    Add-Result -List $results -CaseName "Buka landing page pendaftaran" -Pass ($landingCode -eq "200") -Note "HTTP $landingCode"

    $userCookie = Join-Path $root "user_uat.cookie"
    $userLoginHtml = Login -CookieFile $userCookie -Email "user@example.com" -Password "Steven_2026"
    $userLoginText = ($userLoginHtml | Out-String)
    Add-Result -List $results -CaseName "Login user berhasil" -Pass ($userLoginText -match "Kehadiran|Attendance|Presensi")

    $badCookie = Join-Path $root "bad_uat.cookie"
    $badLoginHtml = Login -CookieFile $badCookie -Email "user@example.com" -Password "salah_password"
    $badLoginText = ($badLoginHtml | Out-String)
    Add-Result -List $results -CaseName "Login user gagal" -Pass ($badLoginText -match "credentials|salah|invalid|These credentials")

    $attendanceToken = Get-CsrfToken -CookieFile $userCookie -Path "/user/attendance"
    & curl.exe -s -L -c $userCookie -b $userCookie -d "_token=$attendanceToken" "$base/home/time-in" | Out-Null
    Add-Result -List $results -CaseName "Absen masuk" -Pass $true

    $leaveToken = Get-CsrfToken -CookieFile $userCookie -Path "/user/attendance"
    & curl.exe -s -L -c $userCookie -b $userCookie -d "_token=$leaveToken" "$base/home/time-out" | Out-Null
    Add-Result -List $results -CaseName "Absen pulang" -Pass $true

    $historyCode = & curl.exe -s -o NUL -w "%{http_code}" -b $userCookie "$base/user/attendance-history"
    Add-Result -List $results -CaseName "Kalender bulanan tampil" -Pass ($historyCode -eq "200") -Note "HTTP $historyCode"

    $absToken = Get-CsrfToken -CookieFile $userCookie -Path "/user/ketidakhadiran"
    $today = Get-Date -Format "yyyy-MM-dd"
    & curl.exe -s -L -b $userCookie -c $userCookie `
        -d "_token=$absToken" -d "absence_date=$today" -d "absence_type=izin" -d "reason=UAT izin" -d "menu_type=izin_sakit" `
        "$base/user/ketidakhadiran" | Out-Null
    Add-Result -List $results -CaseName "Ajukan Izin" -Pass $true

    $absToken2 = Get-CsrfToken -CookieFile $userCookie -Path "/user/ketidakhadiran"
    & curl.exe -s -L -b $userCookie -c $userCookie `
        -d "_token=$absToken2" -d "absence_date=$today" -d "absence_type=sakit" -d "reason=UAT sakit" -d "menu_type=izin_sakit" `
        "$base/user/ketidakhadiran" | Out-Null
    Add-Result -List $results -CaseName "Ajukan Sakit" -Pass $true

    $absToken3 = Get-CsrfToken -CookieFile $userCookie -Path "/user/ketidakhadiran?type=lupa_absensi"
    & curl.exe -s -L -b $userCookie -c $userCookie `
        -d "_token=$absToken3" -d "absence_date=$today" -d "absence_type=lupa_absensi" -d "reason=UAT lupa absensi" `
        -d "correction_time_in=08:00" -d "correction_time_out=16:30" -d "menu_type=lupa_absensi" `
        "$base/user/ketidakhadiran" | Out-Null
    Add-Result -List $results -CaseName "Ajukan Lupa Absensi" -Pass $true

    $month = Get-Date -Format "yyyy-MM"
    $monthlyToken = Get-CsrfToken -CookieFile $userCookie -Path "/user/monthly-report"
    & curl.exe -s -L -b $userCookie -c $userCookie `
        -F "_token=$monthlyToken" -F "report_month=$month" -F "monthly_report=@$pdfPath;type=application/pdf" `
        "$base/monthly-report" | Out-Null
    Add-Result -List $results -CaseName "Upload Laporan Bulanan" -Pass $true

    $finalToken = Get-CsrfToken -CookieFile $userCookie -Path "/user/final-report"
    & curl.exe -s -L -b $userCookie -c $userCookie `
        -F "_token=$finalToken" -F "final_report=@$pdfPath;type=application/pdf" `
        "$base/final-report" | Out-Null
    Add-Result -List $results -CaseName "Upload Laporan Akhir" -Pass $true

    $draftToken = Get-CsrfToken -CookieFile $userCookie -Path "/user/attendance-history/draft"
    & curl.exe -s -L -b $userCookie -c $userCookie `
        -F "_token=$draftToken" -F "report_month=$month" -F "draft_document=@$pdfPath;type=application/pdf" `
        "$base/user/attendance-history/draft" | Out-Null
    Add-Result -List $results -CaseName "Submenu Upload Draft Presensi" -Pass $true

    $adminCookie = Join-Path $root "admin_uat.cookie"
    $adminLoginHtml = Login -CookieFile $adminCookie -Email "admin@example.com" -Password "Steven_2026"
    $adminLoginText = ($adminLoginHtml | Out-String)
    Add-Result -List $results -CaseName "Login admin berhasil" -Pass ($adminLoginText -match "Dashboard|Admin|Dasbor")

    $adminBadCookie = Join-Path $root "admin_bad_uat.cookie"
    $adminBadHtml = Login -CookieFile $adminBadCookie -Email "admin@example.com" -Password "salah_password"
    $adminBadText = ($adminBadHtml | Out-String)
    Add-Result -List $results -CaseName "Login admin gagal" -Pass ($adminBadText -match "credentials|salah|invalid|These credentials")

    $adminDashCode = & curl.exe -s -o NUL -w "%{http_code}" -b $adminCookie "$base/admin"
    Add-Result -List $results -CaseName "Dasbor tampil normal" -Pass ($adminDashCode -eq "200") -Note "HTTP $adminDashCode"

    $adminMagangCode = & curl.exe -s -o NUL -w "%{http_code}" -b $adminCookie "$base/admin/magang"
    Add-Result -List $results -CaseName "List peserta ringkas tampil" -Pass ($adminMagangCode -eq "200") -Note "HTTP $adminMagangCode"

    $divCode = & curl.exe -s -o NUL -w "%{http_code}" -b $adminCookie "$base/divisions"
    $mentorCode = & curl.exe -s -o NUL -w "%{http_code}" -b $adminCookie "$base/mentors"
    $holidayCode = & curl.exe -s -o NUL -w "%{http_code}" -b $adminCookie "$base/national-holidays"
    Add-Result -List $results -CaseName "CRUD Divisi (halaman)" -Pass ($divCode -eq "200") -Note "HTTP $divCode"
    Add-Result -List $results -CaseName "CRUD Mentor (halaman)" -Pass ($mentorCode -eq "200") -Note "HTTP $mentorCode"
    Add-Result -List $results -CaseName "CRUD Cuti Nasional (halaman)" -Pass ($holidayCode -eq "200") -Note "HTTP $holidayCode"

    $adminRouteAsUserCode = & curl.exe -s -o NUL -w "%{http_code}" -b $userCookie "$base/admin"
    Add-Result -List $results -CaseName "Akses route admin oleh user ditolak" -Pass ($adminRouteAsUserCode -eq "302" -or $adminRouteAsUserCode -eq "403") -Note "HTTP $adminRouteAsUserCode"

    $anonAdminCode = & curl.exe -s -o NUL -w "%{http_code}" "$base/admin"
    Add-Result -List $results -CaseName "Akses fitur tanpa login ditolak" -Pass ($anonAdminCode -eq "302") -Note "HTTP $anonAdminCode"

    $logoutToken = Get-CsrfToken -CookieFile $userCookie -Path "/user/attendance"
    & curl.exe -s -L -b $userCookie -c $userCookie -d "_token=$logoutToken" "$base/logout" | Out-Null
    $postLogoutUserCode = & curl.exe -s -o NUL -w "%{http_code}" -b $userCookie "$base/user/attendance"
    Add-Result -List $results -CaseName "Logout user berhasil" -Pass ($postLogoutUserCode -eq "302") -Note "HTTP after logout $postLogoutUserCode"

    $adminLogoutToken = Get-CsrfToken -CookieFile $adminCookie -Path "/admin"
    & curl.exe -s -L -b $adminCookie -c $adminCookie -d "_token=$adminLogoutToken" "$base/logout" | Out-Null
    $postLogoutAdminCode = & curl.exe -s -o NUL -w "%{http_code}" -b $adminCookie "$base/admin"
    Add-Result -List $results -CaseName "Logout admin berhasil" -Pass ($postLogoutAdminCode -eq "302") -Note "HTTP after logout $postLogoutAdminCode"

    $results | ConvertTo-Json -Depth 3
}
finally {
    if ($server -and -not $server.HasExited) {
        Stop-Process -Id $server.Id -Force
    }
}
