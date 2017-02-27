<?php
require("class.phpmailer.php");
$mail = new PHPMailer();
$mail->IsSMTP();                                      // set mailer to use SMTP
$mail->Host = "mail.invosa.com:26";  // specify main and backup server
$mail->SMTPAuth = true;     // turn on SMTP authentication
$mail->Username = "san@invosa.com";  // SMTP username
$mail->Password = "Sinaga1987"; // SMTP password
$mail->From = "san@invosa.com";
$mail->FromName = "Mailer";
$mail->AddAddress("san@invosa.com", "Sanhenra");
//$mail->AddAddress("yudi@invosa.com");                  // name is optional
$mail->AddReplyTo("san@invosa.com", "Sanhenra");
$mail->WordWrap = 50;                                 // set word wrap to 50 characters
$mail->AddAttachment("E:\Project_Invosa\Other\mailer.php");         // add attachments    // optional name
$mail->IsHTML(true);                                  // set email format to HTML
$mail->Subject = "Coba Kirim email pake PHP";
$mail->Body = "Email ini dikirim dengan menggunakan PHP";
$mail->AltBody = "Powered by telkomsel@smartphone";
if (!$mail->Send()) {
    echo "Message could not be sent. <p>";
    echo "Mailer Error: " . $mail->ErrorInfo;
    exit;
}
//trial@invosa.com, password : trial123
echo "Message has been sent";
?>

