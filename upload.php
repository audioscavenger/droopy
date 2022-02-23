<?php
$target_dir = "uploads".PHP_EOL;
$input_file = $_FILES["file"]["tmp_name"];
// basename() may prevent filesystem traversal attacks;
$target_file = basename($_FILES["file"]["name"]);
// Remove anything which isn't a word, whitespace, number
// or any of the following caracters -_~,;[]().
// If you don't need to handle multi-byte characters
// you can use preg_replace rather than mb_ereg_replace
// Thanks @Łukasz Rysiak!
$target_file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $target_file);
// Remove any runs of periods (thanks falstro!)
$target_file = mb_ereg_replace("([\.]{2,})", '', $target_file);

if (mb_strlen(basename($_FILES["file"]["name"]), "UTF-8") > 0) {
  if (basename($_FILES["file"]['size']) > 0) {
    // $_SERVER["HTTP_X_FORWARDED_FOR"]
    // $_SERVER["HTTP_USER_AGENT"]
    file_put_contents('upload.log', date("Y-m-d H:i:s") .' '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' '. $_SERVER["HTTP_X_REAL_IP"] .' '. basename($_FILES["file"]["name"]) .' '. basename($_FILES["file"]["size"]) .' OK' .PHP_EOL, FILE_APPEND);
    if (move_uploaded_file($input_file, $target_dir.$target_file)) {
      $status = 1;
    } else {
    file_put_contents('upload.log', date("Y-m-d H:i:s") .' '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' '. $_SERVER["HTTP_X_REAL_IP"] .' '. basename($_FILES["file"]["name"]) .' '. basename($_FILES["file"]["size"]) .' ERROR moving file' .PHP_EOL, FILE_APPEND);
    }
  } else {
    file_put_contents('upload.log', date("Y-m-d H:i:s") .' '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' '. $_SERVER["HTTP_X_REAL_IP"] .' '. basename($_FILES["file"]["name"]) .' '. basename($_FILES["file"]["size"]) .' ERROR FILE EMPTY' .PHP_EOL, FILE_APPEND);
  }
} else {
  file_put_contents('upload.log', date("Y-m-d H:i:s") .' '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' '. $_SERVER["HTTP_X_REAL_IP"] .' '. basename($_FILES["file"]["name"]) .' '. basename($_FILES["file"]["size"]) .' ERROR FILE NAME ' .PHP_EOL, FILE_APPEND);
}
