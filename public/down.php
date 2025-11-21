<?php
$remoteFileUrl = $_GET["url"];
$headers = get_headers($remoteFileUrl, 1);
$mimeType = $headers["Content-Type"];
$extension = "";
switch ($mimeType) {
  case 'image/jpeg':
    $extension = 'jpg';
    break;
  case 'image/webp':
    $extension = 'webp';
    break;
  case 'image/png':
    $extension = 'png';
    break;

  case 'video/mp4':
    $extension = 'mp4';
    break;
    // 添加其他MIME类型...  
}
// 本地临时文件路径  
$localTempFile = './' . time() . '.' . $extension;

// 使用cURL下载远程文件到本地临时文件  
$ch = curl_init($remoteFileUrl);
$fp = fopen($localTempFile, 'wb');
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_exec($ch);
curl_close($ch);
fclose($fp);


// 设置响应头，提供下载并指定文件名  
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . time() . '.' . $extension . '"');
header('Content-Length: ' . filesize($localTempFile));



try {
  // 将临时文件内容发送给客户端  
  readfile($localTempFile);

  // 发送完毕后删除临时文件  
  unlink($localTempFile);
} catch (Exception $e) {
  unlink($localTempFile);
}
