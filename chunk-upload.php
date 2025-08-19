<?php
error_reporting(E_ALL);

//////////////////////////////////////////
////////// custom variables //////////////

//////////////////////////////////////////

////////// common stuff //////////////

$logName = pathinfo(__FILE__, PATHINFO_FILENAME);
$logfile = $logName . '.log';
// we can't tell if behind a proxy or if any of these are set
$keys = ['REMOTE_ADDR','HTTP_X_FORWARDED_FOR','HTTP_CF_CONNECTING_IP','HTTP_X_REAL_IP'];
foreach ($keys as $key) { $remote_addr = isset($_SERVER[$key]) ? $_SERVER[$key] : $remote_addr;}
$remote_country = isset($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '??';

function logger($message='') {
  global $logfile, $remote_country, $remote_addr, $logName;
  file_put_contents($logfile, sprintf("%s %s [%-15s] %s: %s".PHP_EOL, date("Y-m-d H:i:s"), $remote_country, $remote_addr, $logName, $message), FILE_APPEND);
}
/////////////////////////////////////

logger(json_encode($_POST));
if (!isset($_POST)) {
  logger("_POST=EMPTY - ERROR");
  die();
} else {
  logger("dzuuid={$_POST['dzuuid']}");
}

if (!isset($_FILES)) {
  logger("_FILES=EMPTY - ERROR");
  die();
} else {
  logger("_FILES[name]={$_FILES['file']['name']} {$_FILES['file']['size']}");
}

// _POST variable = dzuuid = chunk
  // [dzuuid] => ac53da2b-b820-47dd-8d90-8b22a2c3a115
  // [dzchunkindex] => 0
  // [dztotalfilesize] => 359
  // [dzchunksize] => 1000000
  // [dztotalchunkcount] => 1
  // [dzchunkbyteoffset] => 0
$dzuuid = $_POST['dzuuid'];
$chunkIndex = $_POST['dzchunkindex'];
$chunkTotal = $_POST['dztotalchunkcount'];
$chunkName  = "chunk-{$dzuuid}-{$chunkIndex}";

// print_r($_FILES, true);
  // [name] => install-CC-2-platform.ini
  // [full_path] => install-CC-2-platform.ini
  // [type] => application/octet-stream
  // [tmp_name] => /tmp/phpmiDEIh
  // [error] => 0
  // [size] => 937
$baseName = pathinfo($_FILES["file"]["name"], PATHINFO_BASENAME);
$fileName = pathinfo($_FILES["file"]["name"], PATHINFO_FILENAME);
$fileExt  = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
$fileSize = $_FILES["file"]["size"];
$tmp_name = $_FILES['file']['tmp_name'];
// Remove anything which isn't a word, whitespace, number, or any of the following caracters: "-_~[]()."
// If you don't need to handle multi-byte characters
// you can use preg_replace rather than mb_ereg_replace
$baseName = mb_ereg_replace("([^\w\s\d\-_~\,\;\[\]\(\)\.])", '-', $baseName);
// Remove any runs of periods
$baseName = mb_ereg_replace("([\.]{2,})", '', $baseName);
// Remove anything which isn't a word, whitespace, number, or any of the following caracters: "-_~[]()"
$fileExt = mb_ereg_replace("([^\w\s\d\-_~\,\;\[\]\(\)])", '', $fileExt);

$targetChunk = join(DIRECTORY_SEPARATOR, array('/tmp', $chunkName));
// unlink if exist, or file will be appended!!
@unlink($targetChunk);


// ========================================
//   DEPENDENCY FUNCTIONS
// ========================================

// YOU CANNOT ADD file_put_contents($logfile) inside returnResponse or uploads will fail completely
function returnResponse($info = null, $filelink = null, $status = "ERROR") {
  logger("returnResponse: $status");
  
  if ($status == "ERROR") die (json_encode( array(
    "status" => $status,
    "info" => $info,
    "file_link" => $filelink
  )));
}

// ========================================
//   VALIDATION CHECKS
// ========================================

// blah, blah, blah validation stuff goes here
// if ($fileSize == 0) $returnResponse("targetChunk size = 0:", $targetChunk);
// $fileSize =0;
if ($fileSize == 0) {
  logger("debug $targetChunk = 0");
  returnResponse("targetChunk size = 0:", $targetChunk);
}

// ========================================
//   CHUNK UPLOAD
// ========================================

if (move_uploaded_file($tmp_name, $targetChunk)) {
  $status = 1;
} else {
  logger(sprintf("%s.%s -> $s", $fileName, $fileExt, $targetChunk));
}

// Be sure that the file has been uploaded
if ( !file_exists($targetChunk) ) returnResponse("targetChunk missing:", $targetChunk);
chmod($targetChunk, 0777) or returnResponse("Could not reset permissions on chunk {$targetChunk}");

returnResponse(null, null, "success");
logger("upload: the end");
