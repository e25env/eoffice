<?php
/**
 * @filesource modules/repair/download.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */
// session
@session_cache_limiter('none');
@session_start();
if (isset($_SESSION[$_GET['id']])) {
  $file = $_SESSION[$_GET['id']];
  if (is_file($file['file'])) {
    $f = @fopen($file['file'], 'rb');
    if ($f) {
      // ดาวน์โหลดไฟล์
      header('Pragma: public');
      header('Expires: -1');
      header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
      header('Content-Type: '.$file['mime']);
      header('Content-Length: '.filesize($file['file']));
      header('Accept-Ranges: bytes');
      while (!feof($f)) {
        echo @fread($f, 1024 * 8);
        ob_flush();
        flush();
        if (connection_status() != 0) {
          @fclose($f);
          exit;
        }
      }
      @fclose($f);
      exit;
    }
  }
}
header('HTTP/1.0 404 Not Found');
