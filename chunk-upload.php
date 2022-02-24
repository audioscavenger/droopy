<?php

/* ========================================
  VARIABLES
======================================== */

// chunk variables
$fileId = $_POST['dzuuid'];
$chunkIndex = $_POST['dzchunkindex'];
$chunkTotal = $_POST['dztotalchunkcount'];

// file path variables
$targetPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;
$fileExt = '.'.pathinfo(basename($_FILES['file']['name']), PATHINFO_EXTENSION);
$fileSize = $_FILES["file"]["size"];
$chunkName = "{$fileId}-{$chunkIndex}{$fileExt}";
$targetFile = $targetPath . $chunkName;

file_put_contents('upload.log', date("Y-m-d H:i:s") .' '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' '. $_SERVER["HTTP_X_REAL_IP"] .' '. basename($_FILES["file"]["name"]) .' '. basename($_FILES["file"]["size"]) .' START' .PHP_EOL, FILE_APPEND);
file_put_contents('upload.log', date("Y-m-d H:i:s") .' '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' '. $_SERVER["HTTP_X_REAL_IP"] .' source='. $chunkName .' => '. $targetFile .PHP_EOL, FILE_APPEND);

// change directory permissions
chmod(realpath($targetPath), 0777) or die("Could not modify directory permissions.");

/* ========================================
  DEPENDENCY FUNCTIONS
======================================== */

$returnResponse = function ($info = null, $filelink = null, $status = "ERROR") {
  file_put_contents('upload.log', date("Y-m-d H:i:s") .' upload: '. $info .' '. $filelink .' '. $status .PHP_EOL, FILE_APPEND);
  if ($status == "ERROR") die (json_encode( array(
    "status" => $status,
    "info" => $info,
    "file_link" => $filelink
  )));
};

/* ========================================
  VALIDATION CHECKS
======================================== */

// blah, blah, blah validation stuff goes here
if (mb_strlen(basename($_FILES["file"]["name"]), "UTF-8") == 0) $returnResponse("input fileName is null:", $targetFile);
if (basename($_FILES["file"]['size']) == 0) $returnResponse("targetFile size = 0:", $targetFile);

/* ========================================
  CHUNK UPLOAD
======================================== */

if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
  $status = 1;
} else {
  file_put_contents('upload.log', date("Y-m-d H:i:s") .' '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' '. $_SERVER["HTTP_X_REAL_IP"] .' '. basename($_FILES["file"]["name"]) .' '. basename($_FILES["file"]["size"]) .' ERROR moving file' .PHP_EOL, FILE_APPEND);
}

// Be sure that the file has been uploaded
if ( !file_exists($targetFile) ) $returnResponse("targetFile missing:", $targetFile);
chmod($targetFile, 0777) or $returnResponse("Could not reset permissions on chunk", $targetFile);

$returnResponse(null, null, "success");
