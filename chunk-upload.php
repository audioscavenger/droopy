<?php
// TODO: mkdir subfolders
// TODO: use json config files

//////////////////////////////////////////
////////// custom variables //////////////
$uploadPath = "elFinder" . DIRECTORY_SEPARATOR . "files";
//////////////////////////////////////////

$keys = ['REMOTE_ADDR','HTTP_X_FORWARDED_FOR','HTTP_CF_CONNECTING_IP','HTTP_X_REAL_IP'];  // we can't tell if behind a proxy or if any of these are set
foreach ($keys as $key) { $remote_addr = isset($_SERVER[$key]) ? $_SERVER[$key] : '';}
$remote_country = isset($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '??';

// chunk variables
$fileId = $_POST['dzuuid'];
$chunkIndex = $_POST['dzchunkindex'];
$chunkTotal = $_POST['dztotalchunkcount'];

// file path variables
$logfile = 'chunk-upload.log';
$targetPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . $uploadPath . DIRECTORY_SEPARATOR;
$baseName = basename($_FILES["file"]["name"]);
// Remove anything which isn't a word, whitespace, number, or any of the following caracters: "-_~[]()."
// If you don't need to handle multi-byte characters
// you can use preg_replace rather than mb_ereg_replace
// $baseName = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\)\.])", '', $baseName);
$baseName = mb_ereg_replace("([^\w \d\-_~,;\[\]\(\)\.])", '', $baseName);
// Remove any runs of periods
$baseName = mb_ereg_replace("([\.]{2,})", '.', $baseName);

$fileExt = '.'.pathinfo(basename($_FILES["file"]["name"]), PATHINFO_EXTENSION);
$fileExt = ($fileExt == '.') ? '' : $fileExt;
$fileSize = $_FILES["file"]["size"];
$chunkName = "{$fileId}-{$chunkIndex}{$fileExt}";

// Custom special case just for you: next step is to load the dict off a json file and have various subdirectories+fileLists
$customFiles = array(
  "nQ"  => array('get-nQ.cmd','nQ.cmd')
);
foreach($customFiles as $customSubFolder => $arrCutomFiles)
{
  if (in_array($baseName, $arrCutomFiles)) $targetPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . $customSubFolder . DIRECTORY_SEPARATOR;
}

$targetFile = $targetPath . $chunkName;
// unlink if exist, or file will be appended!!
@unlink($targetFile);

file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $remote_country .' , '. $remote_addr .' , chunk-upload: '. "fileName={$baseName}" .' '. $fileSize .'b START' .PHP_EOL, FILE_APPEND);
file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $remote_country .' , '. $remote_addr .' , chunk-upload: '. "targetPath={$targetPath}" .PHP_EOL, FILE_APPEND);
file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $remote_country .' , '. $remote_addr .' , chunk-upload: '. 'source='. $chunkName .' => '. $chunkName .PHP_EOL, FILE_APPEND);

// change directory permissions
// chmod(realpath($targetPath), 0777) or die("Could not modify directory permissions.");

/* ========================================
  DEPENDENCY FUNCTIONS
======================================== */

// YOU CANNOT ADD file_put_contents($logfile) inside $returnResponse or uploads will fail completely
$returnResponse = function ($info = null, $filelink = null, $status = "ERROR") {
  file_put_contents('chunk-upload-response.log', date("Y-m-d H:i:s") .' chunk-upload: '. $info .' '. $filelink .' '. $status .PHP_EOL, FILE_APPEND);
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
if ($fileSize == 0) $returnResponse("targetFile size = 0:", $targetFile);

/* ========================================
  CHUNK UPLOAD
======================================== */

if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
  $status = 1;
} else {
  file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $remote_country .' , '. $remote_addr .' , chunk-upload: '. basename($_FILES["file"]["name"]) .' '. $fileSize .'b ERROR moving file' .PHP_EOL, FILE_APPEND);
}

// Be sure that the file has been uploaded
if ( !file_exists($targetFile) ) $returnResponse("targetFile missing:", $targetFile);
chmod($targetFile, 0777) or $returnResponse("Could not reset permissions on chunk", $targetFile);

$returnResponse(null, null, "success");
