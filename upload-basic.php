<?php
//////////////////////////////////////////
////////// custom variables //////////////
$customFiles = array(
  "nQ" => array(
                "filename" => array('get-nQ.cmd','nQ.cmd'),
                ),
  "DCIM" => array(
                "extension" => array('NEF'),
                )
  );
//////////////////////////////////////////

////////// common stuff //////////////
$targetPath = join(DIRECTORY_SEPARATOR, array(__DIR__, "uploads"));
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

logger(json_encode($_FILES));  // {"userfile":{"name":"filename.ext","full_path":"filename.ext","type":"image\/jpeg","tmp_name":"\/tmp\/phpcfldoh9n05o1aoFpDDk","error":0,"size":661355}}

if (!isset($_FILES)) {
  logger("ERROR: _FILES=EMPTY");
  die();
}
if (!isset($_FILES['userfile'])) {
  logger("ERROR: userfile was not set");
  die();
}

// https://www.php.net/manual/en/features.file-upload.post-method.php
// Remove anything which isn't a word, whitespace, number, or any of the following caracters: "-_~[]()."
$fileName = mb_ereg_replace("([^\w\s\d\-_~\,\;\[\]\(\)\.])", '', pathinfo($_FILES['userfile']['name'], PATHINFO_FILENAME));
// also Remove any runs of periods
$fileName = mb_ereg_replace("([\.]{2,})", '', $fileName);
// Remove anything which isn't a word, whitespace, number, or any of the following caracters: "-_~[]()"
$fileExt = pathinfo($_FILES['userfile']['name'], PATHINFO_EXTENSION);

// logger("debug _GET=".print_r($_GET, true);
$targetBasename = ($fileExt) ? "{$fileName}.{$fileExt}" : $fileName;  // is there an extension?
logger("targetBasename={$targetBasename}");

function getTargetPath($customFiles, $targetBasename, $targetPath) {
  $fileExt = pathinfo($targetBasename, PATHINFO_EXTENSION);
  // Custom special case just for you: next step is to load the dict off a json file and have various subdirectories+fileLists
  foreach($customFiles as $customSubFolder => $arrCutomTypes) {
    if (array_key_exists("filename", $arrCutomTypes)) {
      if (in_array($targetBasename, $arrCutomTypes["filename"])) {
        $subFolder = $customSubFolder;
        $targetPath = join(DIRECTORY_SEPARATOR, array($targetPath , $customSubFolder) );
      }
    }
    if (array_key_exists("extension", $arrCutomTypes)) {
      if (in_array($fileExt, $arrCutomTypes["extension"])) {
        $subFolder = join(DIRECTORY_SEPARATOR, array($customSubFolder, date("Y-m-d")));
        $targetPath = join(DIRECTORY_SEPARATOR, array($targetPath , $subFolder));
        mkdir($targetPath, 0644);
      }
    }
  }
  return array("subFolder" => $subFolder, "targetPath" => $targetPath);
}
$targetPaths  = getTargetPath($customFiles, $targetBasename, $targetPath);
$targetFile   = join(DIRECTORY_SEPARATOR, array($targetPaths['targetPath'], $targetBasename));


logger("targetFile={$targetFile}");

echo '<pre>';
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $targetFile)) {
    echo "{$targetBasename} is valid, and was successfully uploaded.\n";
} else {
    echo "Possible file upload attack!\n";
}

print "</pre>";
