# PowerShell FTP Deploy Script for DataPadi
# Deploys both datapadi-shop and fallback wuaze sites

param(
  [string]$ShopLocal = "D:\datapadi\datapadi.shop\htdocs",
  [string]$ShopRemote = "/datapadi.shop/htdocs",
  [string]$FallbackLocal = "D:\datapadi\htdocs",
  [string]$FallbackRemote = "/htdocs",
  [string]$FtpHost = "ftpupload.net",
  [int]$FtpPort = 21,
  [string]$FtpUser = "if0_38664997",
  [string]$FtpPass = "49p5qd32"
)

function Upload-FtpTree {
  param(
    [string]$LocalRoot,
    [string]$RemoteRoot
  )

  Write-Host ""
  Write-Host "Uploading $LocalRoot -> $RemoteRoot" -ForegroundColor Cyan

  $items = Get-ChildItem -Recurse -File $LocalRoot -ErrorAction SilentlyContinue
  $uploaded = 0
  
  foreach ($item in $items) {
    $rel = $item.FullName.Substring($LocalRoot.Length).TrimStart('\').Replace('\','/')
    
    if ($rel -like ".git/*" -or $rel -like ".vscode/*" -or $rel -like "node_modules/*" -or $rel -like "*.log" -or $rel -like "cron_logs/*" -or $rel -eq "phpinfo.php.bak") { 
      continue 
    }

    $remotePath = "ftp://$FtpHost$RemoteRoot/$rel"

    $dirPart = [System.IO.Path]::GetDirectoryName($rel).Replace('\','/')
    if ($dirPart) {
      $parts = $dirPart.Split("/", [System.StringSplitOptions]::RemoveEmptyEntries)
      $built = $RemoteRoot.TrimEnd('/')
      foreach ($p in $parts) {
        $built = "$built/$p"
        try {
          $mkReq = [System.Net.FtpWebRequest]::Create("ftp://$FtpHost$built")
          $mkReq.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
          $mkReq.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
          $mkReq.UseBinary = $true
          $mkReq.UsePassive = $true
          $mkReq.KeepAlive = $false
          $mkResp = $mkReq.GetResponse()
          $mkResp.Close()
        } catch { }
      }
    }

    try {
      $req = [System.Net.FtpWebRequest]::Create($remotePath)
      $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
      $req.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
      $req.UseBinary = $true
      $req.UsePassive = $true
      $req.KeepAlive = $false

      $bytes = [System.IO.File]::ReadAllBytes($item.FullName)
      $req.ContentLength = $bytes.Length
      $stream = $req.GetRequestStream()
      $stream.Write($bytes, 0, $bytes.Length)
      $stream.Close()
      $resp = $req.GetResponse()
      $resp.Close()

      $uploaded++
      Write-Host "  OK $rel" -ForegroundColor Green
    } catch {
      Write-Host "  FAIL $rel - $($_.Exception.Message)" -ForegroundColor Red
    }
  }

  Write-Host "Uploaded $uploaded files" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== Deploying datapadi-shop ===" -ForegroundColor Yellow
Upload-FtpTree -LocalRoot $ShopLocal -RemoteRoot $ShopRemote

Write-Host ""
Write-Host "=== Deploying datapadi-fallback ===" -ForegroundColor Yellow
Upload-FtpTree -LocalRoot $FallbackLocal -RemoteRoot $FallbackRemote

Write-Host ""
Write-Host "All deployments completed." -ForegroundColor Green
Write-Host ""
