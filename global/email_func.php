<?php
include_once('../global/common_function.php');
include_once('../global/employee_function.php');
include_once('global.php');
include_once('../includes/phpmailer/class.phpmailer.php');
include_once('../includes/phpmailer/class.smtp.php');
include_once('../includes/phpmailer/PHPMailerAutoload.php');
$db = new CdbClass;
$db->connect();
$employeeID = "";
$module_permission = "";
$type = 0;
/**
 * function untuk mendapatkan id group yang berhak menerima email
 *
 * @param  int $status     menentukan status new, check atau approved
 * @param  str $module     menentukan module mana yang digunakan
 * @param  str $employeeID employeeID untuk compare dengan data
 *
 * @return str             string id group dalam bentuk query
 */
function getDataEmail($status, $module, $employeeID)
{
  global $db;
  global $type;
  global $module_permission;
  $flag = false;
  $candidate = getPermission($status, $module_permission);
  // $strSQL = "SELECT * FROM adm_email_setting";
  // $resTmp = $db->execute($strSQL);
  // $i = 0;
  // $strResult = "";
  // while($rowTmp = $db->fetchrow($resTmp))
  // {
  //   $candidate[$i] = $rowTmp['id_adm_group'];
  //   $i++;
  // }
  $strSQL = "SELECT branch_code, management_code, division_code, department_code, section_code, sub_section_code
             FROM hrd_employee WHERE employee_id = '$employeeID'";
  $rowTmp = $db->execute($strSQL);
  $res = $db->fetchrow($rowTmp);
  $employee_branch = ($res['branch_code'] != "") ? $res['branch_code'] : "";
  $employee_management = ($res['management_code'] != "") ? $res['management_code'] : "";
  $employee_department = ($res['department_code'] != "") ? $res['department_code'] : "";
  $employee_division = ($res['division_code'] != "") ? $res['division_code'] : "";
  $employee_section = ($res['section_code'] != "") ? $res['section_code'] : "";
  $employee_sub_section = ($res['sub_section_code'] != "") ? $res['sub_section_code'] : "";
  if (!empty($candidate)) {
    foreach ($candidate as $group => $id) {
      $arrDataBranch = [];
      $strSQL = "SELECT branch FROM adm_email_setting WHERE id_adm_group = $id";
      $resTmp = $db->execute($strSQL);
      while ($rowTmp = $db->fetchrow($resTmp)) {
        $arrDataBranch = unserialize($rowTmp['branch']);
      }
      if (in_array($employee_branch, $arrDataBranch) || empty($arrDataBranch)) {
        $arrDataManagement = [];
        $strSQL = "SELECT management FROM adm_email_setting WHERE id_adm_group = $id";
        $resTmp = $db->execute($strSQL);
        while ($rowTmp = $db->fetchrow($resTmp)) {
          $arrDataManagement = unserialize($rowTmp['management']);
        }
        if (in_array($employee_management, $arrDataManagement) || empty($arrDataManagement)) {
          $arrDataDivision = [];
          $strSQL = "SELECT division FROM adm_email_setting WHERE id_adm_group = $id";
          $resTmp = $db->execute($strSQL);
          while ($rowTmp = $db->fetchrow($resTmp)) {
            $arrDataDivision = unserialize($rowTmp['division']);
          }
          if (in_array($employee_division, $arrDataDivision) || empty($arrDataDivision)) {
            $arrDataDepartment = [];
            $strSQL = "SELECT department FROM adm_email_setting WHERE id_adm_group = $id";
            $resTmp = $db->execute($strSQL);
            while ($rowTmp = $db->fetchrow($resTmp)) {
              $arrDataDepartment = unserialize($rowTmp['department']);
            }
            if (in_array($employee_department, $arrDataDepartment) || empty($arrDataDepartment)) {
              $arrDataSection = [];
              $strSQL = "SELECT section FROM adm_email_setting WHERE id_adm_group = $id";
              $resTmp = $db->execute($strSQL);
              while ($rowTmp = $db->fetchrow($resTmp)) {
                $arrDataSection = unserialize($rowTmp['section']);
              }
              if (in_array($employee_section, $arrDataSection) || empty($arrDataSection)) {
                $arrDataSubSection = [];
                $strSQL = "SELECT sub_section FROM adm_email_setting WHERE id_adm_group = $id";
                $resTmp = $db->execute($strSQL);
                while ($rowTmp = $db->fetchrow($resTmp)) {
                  $arrDataSubSection = unserialize($rowTmp['sub_section']);
                }
                if (in_array($employee_sub_section, $arrDataSubSection) || empty($arrDataSubSection)) {
                  $strResult .= " OR t2.id_adm_group = $id";
                }
              }
            }
          }
        }
      }
    }
  }
  return $strResult;
}

/**
 * function untuk mendapatkan nama employee di email
 *
 * @param  int $id id_employee
 *
 * @return str     str result
 */
function getEmployeeNameEmail($id)
{
  global $db;
  $strResult = getEmployeeName($db, $id) . " [" . getEmployeeIDEmail($id) . "]";
  return $strResult;
}

/**
 * function untuk mendapatkan employee id
 *
 * @param  int $id id_employee
 *
 * @return str $result employee_id
 */
function getEmployeeIDEmail($id)
{
  global $db;
  $strSQL = "SELECT employee_id FROM hrd_employee WHERE id = $id";
  $resExec = $db->execute($strSQL);
  $result = $db->fetchrow($resExec);
  $result = $result['employee_id'];
  return $result;
}

function getEmailTo($strGroupID)
{
  global $db;
  $tempTo = [];
  $strSQL = "SELECT t1.email,t1.employee_name
             FROM hrd_employee as t1
             JOIN adm_user as t2 ON t2.employee_id = t1.employee_id
             JOIN adm_group as t3 ON t2.id_adm_group = t3.id_adm_group
             WHERE 1=2 $strGroupID";
  //  die($strSQL);
  $resExec = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resExec)) {
    $tempTo[$rowDb['email']] = $rowDb['employee_name'];
  }
  return $tempTo;
}

function getSubject($status, $module, $id)
{
  global $employeeID;
  global $type;
  global $module_permission;
  $employeeID = $id;
  $type = $status;
  $module_permission = $module;
  $strSubject = "";
  if ($status == 1) {
    $strSubject = "[$module ID: $id]New Checked $module Entry";
  } elseif ($status == 6) {
    $strSubject = "[$module ID: $id]New Approved 2 $module Entry";
  } elseif ($status == 2) {
    $strSubject = "[$module ID: $id]New Approved 1 $module Entry";
  } elseif ($status == -1) {
    $strSubject = "[$module ID: $id]New Denied $module Entry";
  } else { //use in entry form
    $strSubject = "[$module ID: $id]New $module Entry Created";
  }
  return $strSubject;
}

function getBody($status, $module, $string, $modifyBy)
{
  global $db;
  $strBody = "";
  $strModifyBy = getEmployeeNameEmail($modifyBy);
  $strModifyBy = ($strModifyBy == ' []') ? "INVOSA" : $strModifyBy;
  if ($status == 1) {
    $strBody = "This $module entry has been checked by $strModifyBy on " . date("Y-m-d") . "<br><br>";
    $strBody .= "$string";
  } elseif ($status == 2) {
    $strBody = "This $module entry has been approved 1 by $strModifyBy on " . date("Y-m-d") . "<br><br>";
    $strBody .= "$string";
  } elseif ($status == 6) {
    $strBody = "This $module entry has been approved 2 by $strModifyBy on " . date("Y-m-d") . "<br><br>";
    $strBody .= "$string";
  } elseif ($status == -1) {
    $strBody = "This $module entry has been denied by $strModifyBy on " . date("Y-m-d") . "<br><br>";
    $strBody .= "$string";
  } else {
    $strBody = "This $module entry has created by $strModifyBy on " . date("Y-m-d") . "<br><br>";
    $strBody .= "$string";
  }
  return $strBody;
}

function getPermission($type, $module)
{
  global $db;
  $key = "";
  $i = 0;
  $candidate = [];
  // die($status);
  if ($type == 0) {
    $key = "new_";
  } elseif ($type == 1) {
    $key = "check_";
  } elseif ($type == 2) {
    $key = "approve_";
  }
  if ($type != 6) {
    if ($module == 'Absence' || $module == 'Absence Updated') {
      $key .= "absence";
    } elseif ($module == 'Overtime' || $module == 'Overtime Updated') {
      $key .= "ot";
    } elseif ($module == 'Recruitment' || $module == 'Recruitment Updated') {
      $key .= "recruitment";
    } else {
      $key .= "employee";
    }
    $strSQL = "SELECT id_adm_group FROM adm_email_setting WHERE $key = 't'";
    // die($strSQL);
    $resExec = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resExec)) {
      // die("ok");
      $candidate[$i] = $rowDb['id_adm_group'];
      $i++;
    }
  }
  // die(print_r($candidate));
  return $candidate;
}

/**
 * Sending email and write log to hrd_email_log
 *
 * @param         $db
 * @param  array  $to      array of email target
 * @param  string $subject subject of email
 * @param  string $body    body of email
 * @param  array  $cc      array of email cc(if exist)
 * @param  string $altBody alt body of email(if exist)
 *
 * @return send email
 */
function sendMail(
    $subject,
    $body,
    $cc = null,
    $altBody = null,
    $senderMailView = null,
    $senderMailName = null,
    $mailTo = null
) {
  global $db;
  global $employeeID;
  global $type;
  global $module_permission;
  // die($type);
  $mail = new PHPMailer;
  $sender = 'amos@invosa.com'; // Sender Email Address
  //$mail->SMTPDebug = 3;                               // Enable verbose debug output
  $strGroupID = getDataEmail($type, $module_permission, $employeeID);
  if (empty($mailTo)) {
    $to = getEmailTo($strGroupID);
  } else {
    $to = $mailTo;
  }
  //$mail->isSMTP();                                      // Set mailer to use SMTP
  $mail->Host = 'localhost'; // Specify main and backup SMTP servers
  $mail->SMTPAuth = false; // Enable SMTP authentication
  //$mail->Username   = $sender; // SMTP username
  //$mail->Password   = 'amos1234'; // SMTP password
  //$mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
  $mail->Port = 25; // TCP port to connect to
  if (empty($senderMailView)) {
    $mail->From = $sender; // Email for display
    $mail->FromName = "Devosa"; // Name for display
    $mail->addReplyTo($sender);
  } else {
    if (empty($senderMailName)) {
      $senderMailName = "Devosa";
    }
    $mail->From = $senderMailView;
    $mail->FromName = $senderMailName; // Name for display
    $mail->addReplyTo($senderMailView);
  }
  if (count($to) > 0) {
    foreach ($to as $recipient => $name) {
      $mail->addAddress($recipient, $name);
    }
  }
  // if(count($cc)>0){
  //     foreach($cc as $toCC => $name){
  //          $mail->addCC($toCC, $name);                         // Add if there is CC1
  //     }
  // }
  //$mail->addBCC('bcc@example.com');
  //$mail->addAttachment('/var/tmp/file.tar.gz');       // Add attachments
  //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');  // Optional name
  $mail->isHTML(true);                                  // Set email format to HTML
  $mail->Subject = $subject;
  $mail->Body = $body;
  $mail->AltBody = $altBody;
  if (!$mail->send()) {
    $strMessage = 'Message could not be sent.';
    $strMessage .= 'Mailer Error: ' . $mail->ErrorInfo;
  } else {
    $strMessage = "Message sent";
    // $strSQL  = "INSERT INTO hrd_email_log (created, ";
    // $strSQL .= "\"to\", \"subject\", \"body\") ";
    // $strSQL .= "VALUES(now(), '$to', '$mail->Subject', '$mail->Body')";
    // $resExec=$db->execute($strSQL);
  }
}

?>
