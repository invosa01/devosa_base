<?php
include_once('global.php');
include "../global/excel/Worksheet.php";
include "../global/excel/Workbook.php";
//include_once(getTemplate("words.inc"));
// fungsi untuk menampilkan header file excel
function headeringExcel($strFileName)
{
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=$strFileName");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
    header("Pragma: public");
}

$db = new CdbClass;
if ($db->connect()) {
    $strNow = date("d F Y");
    headeringExcel("slipGaji.xls");
    // Creating a workbook
    $wkb = new Workbook("-");
    $wks =& $wkb->add_worksheet('Slip');
    //-- buat daftar format ---
    $frmTitle =& $wkb->add_format();
    $frmTitle->set_size(10);
    $frmTitle->set_bold(1);
    $frmTitle->set_border(0);
    $frmTitle->set_align('center');
    $frmTitle->set_align('vcenter');
    $frmNormal =& $wkb->add_format();
    $frmNormal->set_size(8);
    $frmNormalBold =& $wkb->add_format();
    $frmNormalBold->set_size(8);
    $frmNormalBold->set_bold(1);
    $frmCurrency =& $wkb->add_format();
    $frmCurrency->set_size(8);
    $frmCurrency->set_align('right');
    // format column
    $wks->set_column(0, 0, 2);
    $wks->set_column(1, 1, 10);
    $wks->set_column(2, 2, 1);
    $wks->set_column(3, 3, 4);
    $wks->set_column(4, 4, 1);
    $wks->set_column(5, 5, 2);
    $wks->set_column(6, 6, 10);
    $wks->set_column(7, 7, 1);
    $wks->set_column(8, 8, 10);
    $wks->set_column(9, 9, 1);
    $wks->set_column(10, 10, 4);
    $wks->set_column(11, 11, 2);
    $wks->set_column(12, 12, 5);
    $wks->set_column(13, 13, 10);
    $intTop = 0;
    $wks->print_area(0, 0, 40, 15);
    $wks->set_landscape();
    $wks->set_paper(9);
    $fltHourPerMonth = getSetting("hour_per_month");
    if (!is_numeric($fltHourPerMonth)) {
        $fltHourPerMonth = "173";
    } // default
    $bolGanjil = true;
    $i = 0;
    foreach ($_REQUEST AS $kode => $value) {
        if (substr($kode, 0, 5) == 'chkID') {
            $strSQL = "SELECT t1.*, t2.employee_name, t3.position_name, t4.section_name, ";
            $strSQL .= "t5.department_name, t6.date_thru ";
            $strSQL .= "FROM hrd_salary_detail AS t1 ";
            $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
            $strSQL .= "LEFT JOIN hrd_position AS t3 ON t1.position_code = t3.position_code ";
            $strSQL .= "LEFT JOIN hrd_section AS t4 ON t1.section_code = t4.section_code ";
            $strSQL .= "LEFT JOIN hrd_department AS t5 ON t1.department_code = t5.department_code ";
            $strSQL .= "LEFT JOIN hrd_salary_master AS t6 ON t1.id_salary_master = t6.id ";
            $strSQL .= "WHERE t1.id = '$value' ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $intTop = ($i * 40);
                $fltBasic = $rowDb['workWage'] + $rowDb['experience'] + $rowDb['adjustment'];
                if ($fltHourPerMonth == 0) {
                    $fltBasicPerHour = 0;
                } else {
                    $fltBasicPerHour = round($fltBasic / $fltHourPerMonth);
                }
                $wks->write_string($intTop, 1, "SLIP GAJI", $frmTitle);
                $wks->write_string($intTop + 1, 1, pgDateFormat($rowDb['date_thru'], " F Y"));
                $wks->write_string($intTop + 3, 1, "NAMA", $frmNormal);
                $wks->write_string($intTop + 4, 1, "NIK", $frmNormal);
                $wks->write_string($intTop + 5, 1, "GOLONGAN", $frmNormal);
                $wks->write_string($intTop + 6, 1, "Total hari kerja", $frmNormal);
                $wks->write_string($intTop + 7, 1, "Lama lembur hari kerja", $frmNormal);
                $wks->write_string($intTop + 8, 1, "Lama lembur hari libur", $frmNormal);
                $wks->write_string($intTop + 9, 1, "Upah lembur/jam", $frmNormal);
                $wks->write_string($intTop + 11, 1, "PENDAPATAN", $frmNormalBold);
                $wks->write_string($intTop + 13, 1, "GAJI POKOK", $frmNormal);
                $wks->write_string($intTop + 14, 1, "TUNJ. PENYUMBANGAN", $frmNormal);
                $wks->write_string($intTop + 15, 1, "TUNJ. STRUKTURAL", $frmNormal);
                $wks->write_string($intTop + 16, 1, "TUNJ. KELUARGA", $frmNormal);
                $wks->write_string($intTop + 17, 1, "TUNJ. MAKAN SIANG", $frmNormal);
                $wks->write_string($intTop + 18, 1, "TUNJ. MAKAN MALAM", $frmNormal);
                $wks->write_string($intTop + 19, 1, "TUNJ. TRANSPORT", $frmNormal);
                $wks->write_string($intTop + 20, 1, "TUNJ. TRANSPORT LEMBUR", $frmNormal);
                $wks->write_string($intTop + 21, 1, "TUNJ. KEHADIRAN", $frmNormal);
                $wks->write_string($intTop + 22, 1, "TUNJ. LEMBUR HARI KERJA", $frmNormal);
                $wks->write_string($intTop + 23, 1, "TUNJ. LEMBUR HARI LIBUR", $frmNormal);
                $wks->write_string($intTop + 24, 1, "TUNJ. SHIFT", $frmNormal);
                $wks->write_string($intTop + 25, 1, "TUNJ. KEMAMPUAN KHUSUS", $frmNormal);
                $wks->write_string($intTop + 26, 1, "TUNJ. LAIN-LAIN", $frmNormal);
                $wks->write_string($intTop + 27, 1, "TUNJ. PAJAK PPH21", $frmNormal);
                $wks->write_string($intTop + 28, 1, "TUNJ. PENYESUAIAN(+)", $frmNormal);
                $wks->write_string($intTop + 30, 1, "GAJI KOTOR", $frmNormal);
                $wks->write_string($intTop + 33, 1, "PENERIMAAN BERSIH", $frmNormalBold);
                $wks->write_string($intTop + 3, 8, "DEPT.", $frmNormal);
                $wks->write_string($intTop + 4, 8, "BAGIAN", $frmNormal);
                $wks->write_string($intTop + 5, 8, "POSISI", $frmNormal);
                $wks->write_string($intTop + 6, 8, "Hadir", $frmNormal);
                $wks->write_string($intTop + 7, 8, "Sakit", $frmNormal);
                $wks->write_string($intTop + 8, 8, "Izin", $frmNormal);
                $wks->write_string($intTop + 9, 8, "Absen", $frmNormal);
                $wks->write_string($intTop + 11, 8, "POTONGAN", $frmNormalBold);
                $wks->write_string($intTop + 13, 8, "JAMSOSTEK", $frmNormal);
                $wks->write_string($intTop + 14, 8, "POT. MAKAN SIANG", $frmNormal);
                $wks->write_string($intTop + 15, 8, "POT. HARI KERJA", $frmNormal);
                $wks->write_string($intTop + 16, 8, "POT. PAJAK PPH21", $frmNormal);
                $wks->write_string($intTop + 17, 8, "TUNJ. PENYESUAIAN (-)", $frmNormal);
                $wks->write_string($intTop + 18, 8, "PINJAMAN KARYAWAN", $frmNormal);
                $wks->write_string($intTop + 19, 8, "SIMP. WAJIB KOPERASI", $frmNormal);
                $wks->write_string($intTop + 20, 8, "IURAN PUK FSPMI", $frmNormal);
                $wks->write_string($intTop + 21, 8, "TOTAL POTONGAN", $frmNormal);
                $wks->write_string($intTop + 23, 8, "CATATAN", $frmNormalBold);
                $wks->write_string($intTop + 24, 8, "GAJI POKOK", $frmNormal);
                $wks->write_string($intTop + 25, 8, "UPAH KERJA", $frmNormal);
                $wks->write_string($intTop + 26, 8, "TUNJG. PENGALAMAN", $frmNormal);
                $wks->write_string($intTop + 27, 8, "TUNJG. PENYESUAIAN", $frmNormal);
                $wks->write_string($intTop + 30, 8, "TOTAL", $frmNormalBold);
                $stremployee_name = "" . $rowDb['employee_name'];
                $stremployee_id = "" . $rowDb['employee_id'];
                $strDepartmentName = "" . $rowDb['department_name'];
                $strSectionName = "" . $rowDb['section_name'];
                $strPositionName = "" . $rowDb['position_name'];
                $strGradeCode = $rowDb['grade_code'];
                $strWorkingday = $rowDb['workingDay'];
                $strAttendance = $rowDb['normalAttendanceDay'];
                $strAbsence = $rowDb['absenceDay'];
                $strLeave = $rowDb['leaveDay'];
                $strMonthlyLeave = $rowDb['monthlyLeaveDay'];
                $strOTNormal = standardFormat($rowDb['normalOTMin'] / 60);
                $strOTHoliday = standardFormat($rowDb['holidayOTMin'] / 60);
                $strWorkWage = standardFormat($rowDb['workWage']);
                $strAllowExperience = standardFormat($rowDb['experience']);
                $strAllowAdjustment = standardFormat($rowDb['adjustment']);
                $strBasicSalary = standardFormat($fltBasic);
                $strSalaryPerHour = standardFormat($fltBasicPerHour);
                $strAllowContribution = standardFormat($rowDb['contribution']);
                $strAllowFamily = standardFormat($rowDb['wife'] + $rowDb['child']);
                $strAllowStructural = standardFormat($rowDb['structural']);
                $strAllowShift = standardFormat($rowDb['dayShift'] + $rowDb['nightShift']);
                $strAllowSpecial = standardFormat($rowDb['special']);
                $strAllowOther = standardFormat($rowDb['otherAllowance']);
                $strAdjustmentPlus = standardFormat($rowDb['adjustmentPlus']);
                $strAllowAttendance = standardFormat($rowDb['attendance']);
                $strAllowTransport = standardFormat($rowDb['transport']);
                $strAllowOTTransport = standardFormat($rowDb['transportOT']);
                $strAllowLunch = standardFormat($rowDb['lunch']);
                $strAllowDinner = standardFormat($rowDb['mealOT']);
                $strAllowOTNormal = standardFormat($rowDb['normalOT']);
                $strAllowOTHoliday = standardFormat($rowDb['holidayOT']);
                $strAllowMonthly = standardFormat($rowDb['monthlyLeave']);
                $strAdjustmentMinus = standardFormat($rowDb['adjustmentMinus']);
                $strTax = standardFormat($rowDb['tax']);
                $strJamsostek = standardFormat($rowDb['jamsostek']);
                $strKoperasi = standardFormat($rowDb['koperasi']);
                $strFSPMI = standardFormat($rowDb['fspmi']);
                $strLoan = standardFormat($rowDb['loan']);
                $strDeducLunch = standardFormat($rowDb['lunchDeduction']);
                $strDeducAttendance = standardFormat($rowDb['attendanceDeduction']);
                $strDeducOther = standardFormat($rowDb['otherDeduction']);
                $strTotalDeduction = standardFormat($rowDb['totalDeduction'] + $rowDb['tax']);
                $strGrossSalary = standardFormat($rowDb['totalGross'] + $rowDb['tax']);
                $strNetSalary = standardFormat($rowDb['totalNet']);
                $bolGanjil = !$bolGanjil;
                $i++;
            }
        }
    }
    $wkb->close();
}
?>