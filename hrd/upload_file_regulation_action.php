<?php
include_once('../global/session.php');
include_once('global.php');
$allowedExts = ["pdf"];
$extension = end(explode(".", $_FILES["file"]["name"]));
if ((($_FILES["file"]["type"] == "application/pdf"))
    && ($_FILES["file"]["size"] < 10000000)
    && in_array($extension, $allowedExts)
) {
  if ($_FILES["file"]["error"] > 0) {
    echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
  } else {
    echo "Upload: " . $_FILES["file"]["name"] . "<br>";
    echo "Type: " . $_FILES["file"]["type"] . "<br>";
    echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
    echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";
    SaveRegulation();
    /*if (!is_dir("regulation/{$no_reg}")) {
                  mkdir("regulation/{$no_reg}", 0775);
                }*/
    /*move_uploaded_file($_FILES["file"]["tmp_name"],
     "regulation/{$_FILES["file"]["name"]}");*/
    // header('Location:main.php');
    //echo("regulation/{$no_reg}/{$_FILES["file"]["name"]}");
  }
} else {
  echo "Invalid file";
}
function SaveRegulation()
{
  $db = new CdbClass;
  $no_reg_folder = str_replace('_', '/', $_POST['no_regulation']);
  $no_reg = $_POST['no_regulation'];
  $description = $_POST['desc_regulation'];
  $filename = $_FILES["file"]["name"];
  if ($db->connect()) {
    $strSQL = "INSERT INTO regulation_file
					(no_reg, description, file_name)
					VALUES
					('" . $no_reg . "','" . $description . "','" . $filename . "')";
    $res = $db->execute($strSQL);
    if ($res) {
      if (!is_dir("regulation/{$no_reg_folder}")) {
        mkdir("regulation/{$no_reg_folder}", 0775);
      }
      move_uploaded_file(
          $_FILES["file"]["tmp_name"],
          "regulation/{$no_reg_folder}/{$filename}"
      );
    }
  }
}

?> 