<?php
/*
  KELAS UNTUK MELAKUKAN PERHITUNGAN GAJI
  Update:
    - 2008.12.30 (Yudi)
*/
include_once("../global/cls_date.php");
include_once("cls_absence.php");
include_once("cls_worktime.php");
include_once("cls_overtime.php");
include_once("cls_tax_calculation.php");
include_once('../classes/hrd/hrd_leave_allowance_base.php');
include_once('../classes/hrd/hrd_leave_allowance.php');

/*
  clsSalaryCalculation : kelas untuk melakukan perhitungan gaji karyawan
*/

class clsSalaryCalculation
{

    var $arrConf; // kelas database

    var $arrDA;

    var $arrDD; // data ID dari master salary calculation

    var $arrData; // daftar general setting

    var $arrDetail; // array data karyawan

    var $arrDetailEmployee; // array data karyawan

    var $arrEmployee; // data master

    var $arrLeaveEmployee;   // array salary detail, dengan index adalah id karyawan

    var $arrLoan; // array id_salary_detacail -> id_employee, untuk mencari id karyawan berdasar id salary detail

    var $arrMA; // master allowance

    var $arrMD; // master deduction

    var $data; // detail data allowance lain-lain

    var $objWork; // detail data deduction lain-lain

    var $strDataID; // detail data pinjaman, karena harus diupdate juga infonya

    var $strKriteria; // detail data pinjaman, karena harus diupdate juga infonya

    /* inisialisasi, konstruktor
        jika sudah ada data id master, bisa langsung diset, agar langsung mengambil data
        array kriteria adalah array untuk kriteria data karyawan yang akan termasuk dalam perhitungan gaji, jika ada
    */
    function clsSalaryCalculation(
        $db,
        $strID = "",
        $bolIrregular = false,
        $strDataDate = "",
        $arrKriteria = [],
        $strDateFrom = "",
        $strDateThru = ""
    ) {
        // inisialisasi
        $this->data = $db;
        $this->objWork = new clsWorkTime($db);
        $this->irregular = ($bolIrregular) ? "t" : "f";
        $this->strDataID = $strID;
        $this->arrLoan = [];
        $this->arrMAGrouped = [];
        $this->arrMA = [];
        $this->arrMD = [];
        $this->arrDA = [];
        $this->arrDD = [];
        $this->strKriteria = "";
        // ambil data setting
        $this->initGeneralSetting();
        // buat kriteria berdasarkan arrKriteria
        $strKriteria = "";
        if (count($arrKriteria) > 0) {
            foreach ($arrKriteria AS $kode => $value) {
                $this->strKriteria .= "AND \"$kode\" = '$value' ";
            }
        }
        // ambil data karyawan
        $this->initSalaryMaster($strDataDate, $strDateFrom, $strDateThru);
        $this->initEmployee($this->strKriteria);
        $this->initMasterAllowance();
        $this->initMasterDeduction();
        // ambil data perhitungan gaji, jika ada
        $this->arrDetailEmployee = [];
        $this->initSalaryDetail();
        /* Inisialisasi array untuk menyimpan base tax dan payed tax
     bulan sebelum bulan kalkulasi salary yang diinginkan
    */
        $this->arrBaseTaxPayedTaxBefore = [];
        /* bulan perhitungan gaji diambil dari bulan $strDateThru */
        $intMonth = date('n', strtotime($this->arrData['salary_date']));
        $intYear = date('Y', strtotime($this->arrData['salary_date']));
        $this->getArrayDetailBaseTaxPayedTaxBefore($intMonth, $intYear);
        $this->salaryCalcMonth = $intMonth;
        $this->salaryCalcYear = $intYear;
        /* Akhir modifikasi */
    }

    /* initGeneralSetting : fungsi untuk mengambil semua nilai konfigurasi di tabel setting umum (private)
    */
    function calculateAbsenceDeduction()
    {
        $strSQL = "SELECT t1.id, SUM(CASE WHEN is_leave is FALSE THEN 1 ELSE 0 END) AS total_absence
          FROM hrd_employee AS t1 LEFT JOIN
          (hrd_absence_detail AS t2
          LEFT JOIN hrd_absence_type AS t3 ON t2.absence_type = t3.code AND absence_date between '" . $this->arrData['date_from'] . "' AND '" . $this->arrData['date_thru'] . "')
          ON t1.id = t2.id_employee
          LEFT JOIN hrd_absence AS t4 ON t2.id_absence = t4.id
          WHERE 1=1 AND t4.status >= 2
          GROUP BY t1.id;";
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $intDeductionBase = 0;
            $intDeductionBase += $this->arrDetail[$row['id']]['basic_salary'] / 30;
            if ($this->isResignLastMonth($row['id'])) {
                $intDeductionBase = 0;
            }
            if ($row['total_absence'] == 1) {
                $this->arrDetail[$row['id']]['absence_deduction'] = $intDeductionBase * $row['total_absence'] * 0.25;
            } else if ($row['total_absence'] > 1 && $row['total_absence'] < 4) {
                $this->arrDetail[$row['id']]['absence_deduction'] = $intDeductionBase * $row['total_absence'] * 0.5;
            } else if ($row['total_absence'] >= 4) {
                $this->arrDetail[$row['id']]['absence_deduction'] = $intDeductionBase * $row['total_absence'];
            }
            if ((isset($this->arrConf['absence_deduction_tax'])) && $this->arrConf['absence_deduction_tax'] == 't') {
                $this->arrDetail[$row['id']]['base_tax'] -= $this->arrDetail[$row['id']]['absence_deduction'];
            }
        }
    }

    /* initEmployee : fungsi untuk mengambil data master karyawan, disimpan dalam array
        kriteria adalah kriteria untuk data karyawan tersebut
    */
    function calculateBasic($strKriteria = "")
    {
        // proses tunjangan lain-lain bulanan
        // perlu diupgrade
        $arrDt = explode("-", $this->arrData['salary_start_date']);
        $intTotalDay = lastDay($arrDt[1], $arrDt[0]);
        global $db;
        $objDt = new clsWorkTime();
        $strSQL = "
        SELECT t1.amount, t1.id_employee, t1.allowance_code, t2.prorate, t2.daily, t3.id_company
        FROM hrd_employee_allowance AS t1
        INNER JOIN (select * from hrd_allowance_type order by seq) AS t2 ON t1.allowance_code = t2.code
        LEFT JOIN hrd_employee AS t3 ON  t1.id_employee = t3.id
        WHERE t2.active = 't' AND id_salary_set = " . $this->arrData['id_salary_set'] . "
        AND  ((join_date, resign_date) OVERLAPS (DATE '" . $this->arrData['salary_start_date'] . "', DATE '" . $this->arrData['salary_finish_date'] . "')
          OR (join_date <= DATE '" . $this->arrData['salary_finish_date'] . "' AND resign_date is null))
          $strKriteria
      ";
        //die($strSQL);
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $strIDEmp = $row['id_employee'];
            $this->arrDetail[$strIDEmp]['working_day'] = $objDt->getTotalWorkDay(
                $db,
                $this->arrData['salary_start_date'],
                $this->arrData['salary_finish_date']
            );
            $intEffective = $this->getProrateDay($strIDEmp);
            $intInvEffective = 1 - $intEffective;
            $arrEmpProrate[$strIDEmp] = $fltProrate = $intEffective;
            $arrEmpInvProrate[$strIDEmp] = $fltInvProrate = $intInvEffective;
            $this->arrDetail[$row['id_employee']]['prorate'] = $fltProrate;
            $this->arrDA[$row['allowance_code']][$row['id_employee']] = $row;
            $this->arrDA[$row['allowance_code']][$row['id_employee']]['id_salary_master'] = $this->strDataID;
            if (isset ($this->arrMA[$row['allowance_code']]) && isset($this->arrDetail[$row['id_employee']]) && $row['amount'] != "") {
                if ($this->arrMA[$row['allowance_code']]['ot'] == 't') {
                    $this->arrDetail[$row['id_employee']]['base_ot'] += $row['amount'];
                } // as base ot
                if ($this->arrMA[$row['allowance_code']]['jams'] == 't') {
                    $this->arrDetail[$row['id_employee']]['base_jamsostek'] += $row['amount'];
                }
                /*
                 * 2016-11-26 | Ade Sanusi
                 * Base Jamsostek, Base BPJS Kesehatan diambil dari UMK Masing-masing Cabang.
                 */
                /*$employeeBranch = $this->arrDetail[$row['id_employee']]['branch_code'];
                $employeeCompanyId = $row['id_company'];
                $this->arrDetail[$row['id_employee']]['base_jamsostek'] = getBaseUmkByBranch(
                    $this->data,
                    $employeeBranch,
                    $employeeCompanyId
                );*/
                //$dump = "====>".$row['id_employee']."--".$employeeCompanyId.">>".$employeeBranch."<<".$this->arrDetail[$row['id_employee']]['base_jamsostek'];
                //var_dump($dump);
                //exit();
                //cek jika prorata atau harian
                if ($row['prorate'] == 't') {
                    if ($row['allowance_code'] == 'basic_salary') {
                        $this->arrDetail[$row['id_employee']]['basic_salary'] = $row['amount'];
                        $this->arrDetail[$row['id_employee']]['basic_salary_actual'] = $row['amount'] * $fltProrate;
                    }
                    $row['amount'] *= $fltProrate;
                    $this->arrDA[$row['allowance_code']][$row['id_employee']]['amount'] = $row['amount'];
                    //$this->arrDetail[$row['id_employee']]['base_jamsostek'] += $row['grade1_allowance']; // as base jamsostek
                } else if ($row['daily'] == 't') {
                    $row['amount'] *= $this->arrDetail[$row['id_employee']]['attendance_day'];
                    $this->arrDA[$row['allowance_code']][$row['id_employee']]['amount'] = $row['amount'];
                }//exit();
                if ($this->arrMA[$row['allowance_code']]['irregular'] == 't') {
                    $this->arrDetail[$row['id_employee']]['base_irregular_tax'] += $row['amount'];
                } // as base tax
                elseif ($this->arrMA[$row['allowance_code']]['tax'] == 't') {
                    $this->arrDetail[$row['id_employee']]['base_tax'] += $row['amount']; // as base tax
                }
            }
        }
        // ambil data gaji pokok dan tunjangan pokok per karyawan
        $strSQL = "
        SELECT * FROM hrd_employee_basic_salary as t1 WHERE id_salary_set = " . $this->arrData['id_salary_set'] . "
      ";
        //die($strSQL);
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $strIDEmp = $row['id_employee'];
            if (isset($this->arrDetail[$strIDEmp])) {
                // hitung jika ada prorata
                $fltProrate = (isset($arrEmpProrate[$row['id_employee']])) ? $arrEmpProrate[$row['id_employee']] : 1;
                $fltInvProrate = (isset($arrEmpInvProrate[$row['id_employee']])) ? $arrEmpInvProrate[$row['id_employee']] : 1;
                //proses gaji pokok
                //$this->arrDetail[$strIDEmp]['actual_basic_salary'] = ($row['basic_salary'] == "") ? 0 : $row['basic_salary'] ;
                //$this->arrDetail[$strIDEmp]['basic_salary'] = $fltProrate * $row['basic_salary']  ; // gaji adalah bulanan
                //$this->arrDetail[$strIDEmp]['base_ot']  += $this->arrDetail[$strIDEmp]['actual_basic_salary'];
                //$this->arrDetail[$strIDEmp]['base_tax'] += $this->arrDetail[$strIDEmp]['basic_salary'];
                //$this->arrDetail[$strIDEmp]['base_jamsostek'] += $this->arrDetail[$strIDEmp]['basic_salary'];
                //$this->arrDetail[$strIDEmp]['base_irregular_tax'] = 0;
                $this->arrDetail[$strIDEmp]['base_irregular_tax'] = $this->arrDetail[$strIDEmp]['base_irregular_tax'];
                //proses special allowance: seniority
                $this->compute(
                    $strIDEmp,
                    "seniority_allowance",
                    $this->arrConf["seniority_allowance"],
                    $this->arrEmployee[$strIDEmp]['work_year'],
                    $fltProrate
                );
                //proses special allowance: otmeal
                // $this->compute($strIDEmp, "otmeal_allowance", $this->arrConf["otmeal_allowance"], 1, $fltProrate);
                //proses special allowance: kerajinan
                $intMultiplier = floor(
                    $this->arrDetail[$strIDEmp]['attendance_day'] / $this->arrDetail[$strIDEmp]['working_day']
                );
                $this->compute(
                    $strIDEmp,
                    "kerajinan_allowance",
                    $this->arrConf["kerajinan_allowance"],
                    $intMultiplier,
                    $fltProrate
                );
                //proses tunjangan pokok bulanan
                //$this->arrMAGrouped[is_default][multival][daily]*/
                foreach ($this->arrMAGrouped['t']['t']['f'] as $strCode => $arrAllowance) {
                    $this->compute($strIDEmp, $strCode, $row[$strCode], 1, $fltProrate);
                }
                //proses tunjangan pokok harian
                if (isset($this->arrMAGrouped['t']['t']['t'])) {
                    foreach ($this->arrMAGrouped['t']['t']['t'] as $strCode => $arrAllowance) {
                        //nilai tunjangan dikalikan jumlah hari kehadiran
                        if ($strCode != "grade1_allowance" || $strCode != "grade2_allowance") { //Custom for ICW
                            $this->compute(
                                $strIDEmp,
                                $strCode,
                                $row[$strCode],
                                $this->arrDetail[$strIDEmp]['attendance_day'],
                                $fltProrate
                            );
                        }
                    }
                }
                //proses tunjangan shift
                //nilai tunjangan dikalikan jumlah kehadiran pada shift terkait
                $fltBS = $this->arrDA['basic_salary'][$strIDEmp]['amount'];
                //$this->compute($strIDEmp, "shift_allowance", (($this->objAtt->getDataShiftAllowance($strIDEmp) * 8 / 173) * $fltBS/ 100), 1, false);
                $this->compute($strIDEmp, "shift_allowance", $this->objAtt->getDataShiftAllowance($strIDEmp), 1, false);
                /*
                 * Ade Sanusi | 2016-12-9
                 * Uang Makan Berdasarkan By Grade1 (Dalam Kota) dan Grade2 (Luar Kota) dikali total Kehadiran Dalam Kota / Luar Kota
                 */
                $intTotalAbsenceDL = 0;
                $intTotalAbsenceDL = getTotalAbsenceByCode(
                    $this->data,
                    $strIDEmp,
                    "DL",
                    $this->arrData['date_from'],
                    $this->arrData['date_thru']
                );
                //$intTotalAttendanceDK = $this->arrDetail[$strIDEmp]['attendance_day'] - $intTotalAbsenceDL;
                $intTotalAttendanceDK = $this->arrDetail[$strIDEmp]['attendance_day'];
                /*//if($strIDEmp == 2931){
                    $EmployeeID = getEmployeeName($this->data,$strIDEmp);
                    echo "EmployeeID : ".$EmployeeID." == Total DL >> ".$intTotalAbsenceDL." << Total DK >> ".$intTotalAttendanceDK;
                    echo "<br/>";
                //}*/
                /*$EmployeeID = getEmployeeName($this->data, $strIDEmp);
                echo "EmployeeID : " . $EmployeeID . " == Total DL >> " . $intTotalAbsenceDL . " << Total DK >> " . $intTotalAttendanceDK;
                echo "<br/>";*/
                $this->compute(
                    $strIDEmp,
                    "grade1_allowance",
                    $row['grade1_allowance'],
                    $intTotalAttendanceDK,
                    $fltProrate
                );
                $this->compute(
                    $strIDEmp,
                    "grade2_allowance",
                    $row['grade2_allowance'],
                    $intTotalAbsenceDL,
                    $fltProrate
                );
                //print_r($this->arrDetail[3183]);die();
            }
        }
        //exit();
    }

    /* initEmployee : fungsi untuk mengambil data master karyawan, disimpan dalam array
        kriteria adalah kriteria untuk data karyawan tersebut
    */
    function calculateDayDiff($join_day, $thr_day)
    {
        $difference = $thr_day - $join_day;
        return $difference;
    }

    /* initMasterAllowance : fungsi untuk mengambil master allowance tambahan, simpan dalam variabel array
        jika dataID ada, maka ambil yang sudah tersimpan, jika belum, ambil dari data terkini
        bolUseLatest artinya MEMAKSA mengambil data master allowance yang terbaru
    */
    function calculateDeduction($strKriteria)
    {
        // ambil data setting absence deduction
        $strSQL = "
        SELECT deduction_code, absence_code FROM hrd_absence_deduction
        WHERE is_dependant = TRUE
      ";
        $res = $this->data->execute($strSQL);
        //echo $strSQL;
        while ($row = $this->data->fetchrow($res)) {
            $arrAbsenceDeduction[$row['deduction_code']][] = $row['absence_code'];
        }
        // ambil data tunjangan lain-lain
        $strSQL = "
        SELECT ta.amount, ta.id_employee, ta.deduction_code, ta.maxlink,
        (CASE WHEN ta.prorate IS TRUE THEN 't' ELSE 'f' END) AS prorate,
        (CASE WHEN ta.daily IS TRUE THEN 't' ELSE 'f' END) AS daily,
        (CASE WHEN (ta.maxlink <> '' AND ta.maxlink IS NOT NULL) THEN 't' ELSE 'f' END ) AS bolmaxlink,
        (CASE WHEN ((ta.maxlink <> '' AND ta.maxlink IS NOT NULL) AND t3.amount IS NOT NULL ) THEN t3.amount ELSE
          (CASE WHEN (ta.maxlink <> '' AND ta.maxlink IS NOT NULL) THEN CAST(ta.maxlink AS DOUBLE PRECISION) END) END) AS maxamount
        FROM (hrd_employee_deduction AS t1
        INNER JOIN (select code, active, maxlink, prorate, show, daily from hrd_deduction_type order by seq) AS t2 ON t1.deduction_code = t2.code) as ta
        LEFT JOIN hrd_employee_allowance AS t3 ON ta.id_employee = t3.id_employee AND t3.allowance_code = ta.maxlink AND ta.id_salary_set = t3.id_salary_set
        LEFT JOIN hrd_employee AS t5 ON ta.id_employee = t5.id
        WHERE ta.active = 't' AND ta.id_salary_set = " . $this->arrData['id_salary_set'] . " $strKriteria
      ";
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $this->arrEmployee[$strIDEmp]['prorate'] = 1;
            $strIDEmployee = $row['id_employee'];
            $strDeductionCode = $row['deduction_code'];
            $fltAmount = $row['amount'];
            $funcPDM = "compute_deduction_pdm_" . $row['prorate'] . $row['daily'] . $row['bolmaxlink'];
            //$funcPDM        = "compute_deduction_pdm_".$row['prorate'].$row['daily'].$row['bolmaxlink'];
            $intEffective = $this->getProrateDay($strIDEmployee);
            $this->arrDD[$strDeductionCode][$strIDEmployee] = $row;
            $this->arrDD[$strDeductionCode][$strIDEmployee]['id_salary_master'] = $this->strDataID;
            $fltProrate = $intEffective;
            $this->arrEmployee[$strIDEmp]['prorate'] = $fltProrate;
            if (isset($this->arrMD[$strDeductionCode]) && isset($this->arrDetail[$strIDEmployee]) && $fltAmount != "") {
                $tempAmount = $this->$funcPDM(
                    $fltAmount,
                    $row['maxamount'],
                    $strIDEmployee,
                    (isset ($arrAbsenceDeduction[$strDeductionCode]) ? $arrAbsenceDeduction[$strDeductionCode] : null)
                );
                $this->arrDD[$strDeductionCode][$strIDEmployee]['amount'] = $tempAmount;
                if ($this->arrMD[$strDeductionCode]['jams'] == 't') {
                    $this->arrDetail[$strIDEmployee]['base_jamsostek'] -= $tempAmount;
                }// as base jamsostek
                if ($this->arrMD[$strDeductionCode]['tax'] == 't') {
                    $this->arrDetail[$strIDEmployee]['base_tax'] -= $tempAmount;
                }
            }
        }
        // ambil data pinjaman yang perlu dibayar
        if ((isset($this->arrConf['loan_deduction_active'])) && $this->arrConf['loan_deduction_active'] == 't') {
            $strDateFromLoan = getNextDateNextMonth($this->arrData['salary_start_date'], 1);
            $this->getEmployeeLoan($strDateFromLoan, $this->arrData['salary_finish_date']);
            foreach ($this->arrDetail AS $strID => $arrTemp) {
                $fltLoan = (isset($this->arrLoan[$strID])) ? $this->arrLoan[$strID]['amount'] : 0;
                $this->arrDetail[$strID]['loan_deduction'] = $fltLoan;
                if ((isset($this->arrConf['loan_tax'])) && $this->arrConf['loan_tax'] == 't') {
                    $this->arrDetail[$strID]['base_tax'] -= $fltLoan;
                } // as base tax
                if ((isset($this->arrConf['loan_jams'])) && $this->arrConf['loan_jams'] == 't') {
                    $this->arrDetail[$strID]['base_jamsostek'] -= $fltLoan;
                } // as base jamsostek
            }
        }
        //       foreach($this->arrDetail AS $strID => $arrTemp)
        //       {
        //
        //         $intEffective = $this->getProrateDay($strID);
        //
        //         // hitung potongan dari keterlambatan dan pulang cepat
        //         $this->arrDetail[$strID]['absence_deduction'] = 0;
        // //        $this->arrDetail[$strID]['absence_deduction'] = $this->arrDA['basic_salary'][$strID]['amount']/ 168 * ($arrTemp['late_round'] + $arrTemp['early_round']);
        //
        //
        //         //ECHO "<br>".($arrTemp['late_min'] + $arrTemp['early_min']) ;
        //         $this->arrDetail[$strID]['base_tax'] -= $this->arrDetail[$strID]['absence_deduction'] ;
        //       }
    }

    /* initMasterDeduction : fungsi untuk mengambil master deduction tambahan, simpan dalam variabel array
        jika dataID ada, maka ambil yang sudah tersimpan, jika belum, ambil dari data terkini
        bolUseLatest artinya MEMAKSA mengambil data master deduction yang terbaru
    */
    function calculateJamsostek()
    {
        $fltJkkAllowance = (isset($this->arrConf['jkk_allowance'])) ? $this->arrConf['jkk_allowance'] : 0;
        $fltJkmAllowance = (isset($this->arrConf['jkm_allowance'])) ? $this->arrConf['jkm_allowance'] : 0;
        $fltJamsAllowance = (isset($this->arrConf['jamsostek_allowance'])) ? $this->arrConf['jamsostek_allowance'] : 0;
        $fltJamsDeduction = (isset($this->arrConf['jamsostek_deduction'])) ? $this->arrConf['jamsostek_deduction'] : 0;
        $fltBPJSAllowance = (isset($this->arrConf['bpjs_allowance'])) ? $this->arrConf['bpjs_allowance'] : 0;
        $fltBPJSDeduction = (isset($this->arrConf['bpjs_deduction'])) ? $this->arrConf['bpjs_deduction'] : 0;
        $fltPensionAllowance = (isset($this->arrConf['pension_allowance'])) ? $this->arrConf['pension_allowance'] : 0;
        $fltPensionDeduction = (isset($this->arrConf['pension_deduction'])) ? $this->arrConf['pension_deduction'] : 0;
        $fltJSHKDeduction = (isset($this->arrConf['jshk_deduction'])) ? $this->arrConf['jshk_deduction'] : 0;
        $bolJkkAllowanceTax = (isset($this->arrMA['jkk_allowance']['tax']) && $this->arrMA['jkk_allowance']['tax'] == 't');
        $bolJkmAllowanceTax = (isset($this->arrMA['jkm_allowance']['tax']) && $this->arrMA['jkm_allowance']['tax'] == 't');
        $bolJamsAllowanceTax = (isset($this->arrMA['jamsostek_allowance']['tax']) && $this->arrMA['jamsostek_allowance']['tax'] == 't');
        $bolJamsDeductionTax = (isset($this->arrMD['jamsostek_deduction']['tax']) && $this->arrMD['jamsostek_deduction']['tax'] == 't');
        $bolBPJSAllowanceTax = (isset($this->arrMA['bpjs_allowance']['tax']) && $this->arrMA['bpjs_allowance']['tax'] == 't');
        $bolBPJSDeductionTax = (isset($this->arrMD['bpjs_deduction']['tax']) && $this->arrMD['bpjs_deduction']['tax'] == 't');
        $bolPensionAllowanceTax = (isset($this->arrMA['pension_allowance']['tax']) && $this->arrMA['pension_allowance']['tax'] == 't');
        $bolPensionDeductionTax = (isset($this->arrMD['pension_deduction']['tax']) && $this->arrMD['pension_deduction']['tax'] == 't');
        $bolJSHKDeductionTax = (isset($this->arrMD['jshk_deduction']['tax']) && $this->arrMD['jshk_deduction']['tax'] == 't');
        foreach ($this->arrDetail AS $strID => $arrInfo) {
            $bolGetJamsostek = $this->arrEmployee[$strID]['get_jamsostek'];
            $bolGetBPJS = $this->arrEmployee[$strID]['get_bpjs'];
            $bolGetPension = $this->arrEmployee[$strID]['get_pension'];
            $bolGetJSHK = $this->arrEmployee[$strID]['get_jshk'];
            $fltProrate = $this->arrDetail[$strID]['prorate'];
            $baseJamsostek = 0;
            $baseBPJS = 0;
            $basePension = 0;
            /*
            * 2016-11-26 | Ade Sanusi
            * Base Jamsostek, Base BPJS Kesehatan diambil dari UMK Masing-masing Cabang.
            * $this->arrDetail[$strID]['base_jamsostek'] *= $this->arrDetail[$strID]['prorate'];
            * Prorate dihilangkan karena langsung dari UMK Cabang
            */
            //$employeeUmk = $this->arrDetail[$strID]['base_jamsostek'];
            //$basePension = ($this->arrDetail[$strID]['base_jamsostek'] > $this->arrConf['pension_max']) ? $this->arrConf['pension_max'] : $this->arrDetail[$strID]['base_jamsostek'];
            //$baseJamsostek = $employeeUmk;
            $baseJamsostek = $this->arrDetail[$strID]['base_jamsostek'];
            $basePension = ($baseJamsostek > $this->arrConf['pension_max']) ? $this->arrConf['pension_max'] : $baseJamsostek;
            $baseBPJS = ($baseJamsostek > $this->arrConf['bpjs_max']) ? $this->arrConf['bpjs_max'] : $baseJamsostek;
            $baseJSHK = $this->arrDetail[$strID]['base_jamsostek'];
            /*if ($fltProrate == 0) {
                $basePension = 0;
            }*/
            //base jamsostek di prorata
            $this->arrDetail[$strID]['jkk_allowance'] = ($fltJkkAllowance / 100) * $baseJamsostek;
            $this->arrDetail[$strID]['jkm_allowance'] = ($fltJkmAllowance / 100) * $baseJamsostek;
            $this->arrDetail[$strID]['jamsostek_allowance'] = ($fltJamsAllowance / 100) * $baseJamsostek;
            $this->arrDetail[$strID]['jamsostek_deduction'] = ($fltJamsDeduction / 100) * $baseJamsostek;
            if ($bolGetJamsostek != "1") {
                $this->arrDetail[$strID]['jkk_allowance'] = 0;
                $this->arrDetail[$strID]['jkm_allowance'] = 0;
                $this->arrDetail[$strID]['jamsostek_allowance'] = 0;
                $this->arrDetail[$strID]['jamsostek_deduction'] = 0;
            }
            if ($bolGetPension != "1") {
                $this->arrDetail[$strID]['pension_allowance'] = 0;
                $this->arrDetail[$strID]['pension_deduction'] = 0;
            } else {
                $this->arrDetail[$strID]['pension_allowance'] = ($fltPensionAllowance / 100) * $basePension;
                $this->arrDetail[$strID]['pension_deduction'] = ($fltPensionDeduction / 100) * $basePension;
            }
            //Custom for ICW
            //$baseBPJS = $this->arrConf['bpjs_max'] + $this->arrDA['tambahan_base_bpjs_kesehatan'][$strID]['amount'];
            /* $baseBPJSByRange = getBaseBpjsByRange($this->data, $baseBPJS, $this->arrEmployee[$strID]['company_id']);
             if ($baseBPJSByRange > 0) {
                 $baseBPJS = $baseBPJSByRange;
             }*/
            $this->arrDetail[$strID]['bpjs_allowance'] = ($fltBPJSAllowance / 100) * $baseBPJS;
            $this->arrDetail[$strID]['bpjs_deduction'] = ($fltBPJSDeduction / 100) * $baseBPJS;
            if ($bolGetBPJS != "1") {
                $this->arrDetail[$strID]['bpjs_allowance'] = 0;
                $this->arrDetail[$strID]['bpjs_deduction'] = 0;
            }
            $this->arrDetail[$strID]['jshk_deduction'] = ($fltJSHKDeduction / 100) * $baseJSHK;
            if ($bolGetJSHK != "1") {
                $this->arrDetail[$strID]['jshk_deduction'] = 0;
            }
            if (($this->isResignLastMonth($strID) && $this->isPaidProratedLastMonth(
                        $strID
                    ))
            ) {
                $this->arrDetail[$strID]['jkk_allowance'] = 0;
                $this->arrDetail[$strID]['jkm_allowance'] = 0;
                $this->arrDetail[$strID]['jamsostek_allowance'] = 0;
                $this->arrDetail[$strID]['jamsostek_deduction'] = 0;
                $this->arrDetail[$strID]['pension_allowance'] = 0;
                $this->arrDetail[$strID]['pension_deduction'] = 0;
                $this->arrDetail[$strID]['bpjs_allowance'] = 0;
                $this->arrDetail[$strID]['bpjs_deduction'] = 0;
                $this->arrDetail[$strID]['jshk_deduction'] = 0;
            }
            if ($bolJkkAllowanceTax) {
                $this->arrDetail[$strID]['base_tax'] += $this->arrDetail[$strID]['jkk_allowance'];
            } // as base tax
            if ($bolJkmAllowanceTax) {
                $this->arrDetail[$strID]['base_tax'] += $this->arrDetail[$strID]['jkm_allowance'];
            } // as base tax
            if ($bolJamsAllowanceTax) {
                $this->arrDetail[$strID]['base_tax'] += $this->arrDetail[$strID]['jamsostek_allowance'];
            } // as base tax
            if ($bolJamsDeductionTax) {
                $this->arrDetail[$strID]['base_tax'] -= $this->arrDetail[$strID]['jamsostek_deduction'];
            } // as base tax
            if ($bolPensionAllowanceTax) {
                $this->arrDetail[$strID]['base_tax'] += $this->arrDetail[$strID]['pension_allowance'];
            } // as base tax
            if ($bolPensionDeductionTax) {
                $this->arrDetail[$strID]['base_tax'] -= $this->arrDetail[$strID]['pension_deduction'];
            } // as base tax
            if ($bolBPJSAllowanceTax) {
                $this->arrDetail[$strID]['base_tax'] += $this->arrDetail[$strID]['bpjs_allowance'];
            } // as base tax
            if ($bolBPJSDeductionTax) {
                $this->arrDetail[$strID]['base_tax'] -= $this->arrDetail[$strID]['bpjs_deduction'];
            } // as base tax
            if ($bolJSHKDeductionTax) {
                $this->arrDetail[$strID]['base_tax'] -= $this->arrDetail[$strID]['jshk_deduction'];
            } // as base tax
        }
    }

    /* initSalaryMaster : fungsi untuk mengambil data gaji sesuai id, jika tidak ada, lakukan inisialisasi
    */
    function calculateLateDeductionByCompany(
        $idEmployee = null,
        $companyID = null,
        $totalLate = 0,
        $minLate = 0,
        $amount = 0,
        $persen = true,
        $amountPersen = 0
    ) {
        $lateDeduction = 0;
        $isLate = false;
        $isLate = $totalLate > $minLate ? true : false;
        if ($isLate = true) {
            //if ($companyID == 23){ //icw
            if ($persen = true) {
                $lateDeduction = $amount * ($amountPersen / 100);
            } else {
                $lateDeduction = $amount;
            }
            /*}
            }elseif($companyID == 24){ //baj

            }*/
        }
    }

    function calculateLateDeduction()
    {
        $minutes = 15;
        $strSQL = "SELECT t1.id, CASE WHEN (t2.late_duration - CASE WHEN t3.approved_duration > 0 THEN t3.approved_duration ELSE 0 END) > " . $minutes . " THEN (t2.late_duration - CASE WHEN t3.approved_duration > 0 THEN t3.approved_duration ELSE 0 END) ELSE 0 END AS total_late_minutes
          FROM hrd_employee AS t1
          LEFT JOIN hrd_attendance as t2 ON t1.id = t2.id_employee
          LEFT JOIN hrd_absence_partial as T3 ON t2.id_employee = t3.id_employee AND t2.attendance_date = t3.partial_absence_date AND partial_absence_type = '0'
          WHERE attendance_date between '" . $this->arrData['date_from'] . "' AND '" . $this->arrData['date_thru'] . "'
          AND attendance_date NOT IN (SELECT absence_date FROM hrd_absence_detail as t4 WHERE absence_date between '" . $this->arrData['date_from'] . "' AND '" . $this->arrData['date_thru'] . "' AND t4.id_employee = t1.id);";
        $res2 = $this->data->execute($strSQL);
        while ($row2 = $this->data->fetchrow($res2)) {
            $intDeductionBase = 0;
            $intDeductionBase += $this->arrDetail[$row2['id']]['basic_salary'] / 120;
            $intDeductionAmount = 0;
            if ($this->isResignLastMonth($row2['id'])) {
                $intDeductionBase = 0;
            }
            if ($row2['total_late_minutes'] > 15) {
                if ($row2['total_late_minutes'] <= 60) {
                    $intDeductionAmount = $intDeductionBase * 0.3;
                } else if ($row2['total_late_minutes'] <= 120) {
                    $intDeductionAmount = $intDeductionBase * 0.4;
                } else {
                    $intDeductionAmount = $intDeductionBase * 0.5;
                }
            }
            $this->arrDetail[$row2['id']]['late_deduction'] += $intDeductionAmount;
            if ((isset($this->arrConf['late_deduction_tax'])) && $this->arrConf['late_deduction_tax'] == 't') {
                $this->arrDetail[$row2['id']]['base_tax'] -= $intDeductionAmount;
            }
        }
    }

    function calculateLateDeductionByFunctional()
    {
        /*
         * SELECT t1.id, t1.employee_id, t2.attendance_date, t1.branch_code, t6.late_tolerance, t1.functional_code, t7.late_deduction, t7.late_deduction_amount, t7.flat_late_deduction, t2.late_duration, t3.approved_duration, (t2.late_duration - CASE WHEN (t3.approved_duration > 0 AND t3.approved_duration <= t2.late_duration) THEN t3.approved_duration WHEN (t3.approved_duration > t2.late_duration) THEN t2.late_duration ELSE 0 END) AS total_late_minutes
           FROM hrd_employee AS t1
           LEFT JOIN hrd_attendance as t2 ON t1.id = t2.id_employee
           LEFT JOIN hrd_absence_partial as t3 ON t2.id_employee = t3.id_employee AND t2.attendance_date = t3.partial_absence_date AND t3.partial_absence_type = '0' AND t3.status = 2
           LEFT JOIN hrd_branch AS t6 ON t1.branch_code = t6.branch_code
           LEFT JOIN hrd_functional AS t7 ON t1.functional_code = t7.functional_code
           WHERE attendance_date between '2016-10-17' AND '2016-11-14'
           AND attendance_date NOT IN (SELECT absence_date FROM hrd_absence_detail as t4 LEFT JOIN hrd_absence AS t5 ON t5.id = t4.id_absence WHERE absence_date between '2016-10-17' AND '2016-11-14' AND status =2 AND t4.id_employee = t1.id) ORDER BY employee_id ASC, attendance_date ASC;
         */
        /*$strSQL = "SELECT t1.id, CASE WHEN (t2.late_duration - CASE WHEN t3.approved_duration > 0 THEN t3.approved_duration ELSE 0 END) > " . $minutes . " THEN (t2.late_duration - CASE WHEN t3.approved_duration > 0 THEN t3.approved_duration ELSE 0 END) ELSE 0 END AS total_late_minutes
          FROM hrd_employee AS t1
          LEFT JOIN hrd_attendance as t2 ON t1.id = t2.id_employee
          LEFT JOIN hrd_absence_partial as T3 ON t2.id_employee = t3.id_employee AND t2.attendance_date = t3.partial_absence_date AND partial_absence_type = '0'
          WHERE attendance_date between '" . $this->arrData['date_from'] . "' AND '" . $this->arrData['date_thru'] . "'
          AND attendance_date NOT IN (SELECT absence_date FROM hrd_absence_detail as t4 WHERE absence_date between '" . $this->arrData['date_from'] . "' AND '" . $this->arrData['date_thru'] . "' AND t4.id_employee = t1.id);";*/
        $strSQL = "SELECT t1.id,  t1.employee_id, t2.attendance_date, t6.late_tolerance, t7.late_deduction, t7.late_deduction_amount, t7.flat_late_deduction,  (t2.late_duration - CASE WHEN (t3.approved_duration > 0 AND t3.approved_duration <= t2.late_duration) THEN t3.approved_duration WHEN (t3.approved_duration > t2.late_duration) THEN t2.late_duration ELSE 0 END) AS total_late_minutes
          FROM hrd_employee AS t1
          LEFT JOIN hrd_attendance as t2 ON t1.id = t2.id_employee
          LEFT JOIN hrd_absence_partial as t3 ON t2.id_employee = t3.id_employee AND t2.attendance_date = t3.partial_absence_date AND t3.partial_absence_type = '0' AND t3.status = 2
          LEFT JOIN hrd_branch AS t6 ON t1.branch_code = t6.branch_code
          LEFT JOIN hrd_functional AS t7 ON t1.functional_code = t7.functional_code
          WHERE attendance_date between '" . $this->arrData['date_from'] . "' AND '" . $this->arrData['date_thru'] . "'
          AND attendance_date NOT IN (SELECT absence_date FROM hrd_absence_detail as t4 LEFT JOIN hrd_absence AS t5 ON t5.id = t4.id_absence WHERE absence_date between '" . $this->arrData['date_from'] . "' AND '" . $this->arrData['date_thru'] . "' AND status =2 AND t4.id_employee = t1.id) ORDER BY t1.id ASC, t2.attendance_date ASC;";
        $res2 = $this->data->execute($strSQL);
        while ($row2 = $this->data->fetchrow($res2)) {
            $intLateTolerance = 0;
            $intDeductionAmount = 0;
            $intLateTolerance = ($row2['late_tolerance'] == "" || $row2['late_tolerance'] == "NULL") ? 0 : $row2['late_tolerance'];
            $bolLateDeduction = ($row2['late_deduction'] == "" || $row2['late_deduction'] == "NULL" || $row2['late_deduction'] == SQL_FALSE) ? false : true; //Jika True dia kena potongan telat, jika tidak 0
            $intLateDeductionDaily = ($row2['late_deduction_amount'] == "" || $row2['late_deduction_amount'] == "NULL") ? 0 : $row2['late_deduction_amount'];
            $intFlatOrPercent = ($row2['flat_late_deduction'] == "" || $row2['flat_late_deduction'] == "NULL") ? 0 : $row2['flat_late_deduction'];
            $bolFlatOrPercent = ($intFlatOrPercent == 0) ? true : false; // Jika Tru berarti dia Flat Jika False Berarti Persen
            $intLateInMinute = $row2['total_late_minutes'];
            if ($bolLateDeduction == true && $intLateInMinute > $intLateTolerance) {
                if ($bolFlatOrPercent == true) {
                    $intDeductionAmount = $intLateDeductionDaily;
                } else {
                    $intDeductionAmount = $intLateDeductionDaily * ($intFlatOrPercent / 100);
                }
            }
            $this->arrDetail[$row2['id']]['late_deduction'] += $intDeductionAmount;
            $employeeID = $row2['employee_id'];//$this->arrDetail[$intID]['employee_id'];
            $lateDate = $row2['attendance_date'];
            if ($employeeID == '08800069' || $employeeID == 'A0002' || $employeeID == 'A0003') {
                echo "Employee ID >>" . $employeeID . " | " . $row2['id'] . " << Date: " . $lateDate . " Late Deduction : " . $this->arrDetail[$row2['id']]['late_deduction'];
                echo "<br/>";
            }
            if ((isset($this->arrConf['late_deduction_tax'])) && $this->arrConf['late_deduction_tax'] == 't') {
                $this->arrDetail[$row2['id']]['base_tax'] -= $intDeductionAmount;
            }
        }
        //exit();
    }

    /* setSalaryDate : fungsi untuk mengisi atribut perhitungan gaji dengan tanggal perhitungan gaji
        input   : tanggal perhitungan, tanggal awal periode, tanggal akhir periode  -- semua format YYYY-MM-DD
    */
    function calculateLeaveAllowance()
    {
        foreach ($this->arrDetail AS $strIDEmp => $arrInfo) {
            if (!isset($this->arrLeaveEmployee[$strIDEmp])) {
                $this->arrDetail[$strIDEmp]['base_irregular_tax'] -= $this->arrDA['CUTI'][$strIDEmp]['amount'];
                $this->arrDetail[$strIDEmp]['base_irregular_tax'] -= $this->arrDA['CUTI_USD'][$strIDEmp]['amount'];
                $this->arrDetail[$strIDEmp]['base_irregular_tax'] -= $this->arrDA['CUTI_RP'][$strIDEmp]['amount'];
                $this->arrDetail[$strIDEmp]['base_irregular_tax'] -= $this->arrDA['base_CUTI_RP'][$strIDEmp]['amount'];
                $this->arrDetail[$strIDEmp]['base_irregular_tax'] -= $this->arrDA['CUTI_PTM'][$strIDEmp]['amount'];
                $this->arrDA['CUTI'][$strIDEmp]['amount'] = 0;
                $this->arrDA['CUTI_USD'][$strIDEmp]['amount'] = 0;
                $this->arrDA['CUTI_RP'][$strIDEmp]['amount'] = 0;
                $this->arrDA['base_CUTI_RP'][$strIDEmp]['amount'] = 0;
                $this->arrDA['CUTI_PTM'][$strIDEmp]['amount'] = 0;
            }
        }
    }

    /* getSalaryPeriodDate : fungsi untuk mengambil tanggal awal dan akhir dari periode perhitungan gaji
        misal perhitungan gaji desember 2008, berarti periode awal 2008-12-01 sampai 2008-12-31
        data disimpan di arrData['salary_start_date'] dan ['salary_finish_date']
    */
    function calculateMonthDiff($join_month, $thr_month, $year_diff)
    {
        if ($year_diff == 1) {
            $difference = 12 - $join_month + $thr_month;
        } else {
            $difference = $thr_month - $join_month;
        }
        return $difference;
    }

    /* getStandardWorkingDay : mengambil nilai durasi hari kerja, untuk perhitungan kehadiran dan lembur
        menghitung dari date_from-date_thru, simpan di meal_duration
    */
    function calculateOvertime()
    {
        $fltHourPerMonth = (isset($this->arrConf['hour_per_month'])) ? $this->arrConf['hour_per_month'] : 173; // default
        if ($fltHourPerMonth == 0) {
            $fltHourPerMonth = 173;
        }
        $objOT = new clsOvertimeReport(
            $this->data,
            $this->arrData['date_from'],
            $this->arrData['date_thru'],
            "",
            " AND id_company = " . $this->arrData['id_company']
        ); // cls_overtime.php
        $objOT->generateOvertimeSalaryReport($this->arrData['salary_date']);
        foreach ($this->arrDetail AS $strID => $arrInfo) {
            $intTotalOTAll = $objOT->getData($strID, "total_ot_all");
            // Shift OT
            //$fltExcessOT   = $this->arrDetail[$strID]['shift_hour'] - ($fltHourPerMonth * 60);
            //if ($fltExcessOT > 0) $objOT->setExcessOT($strID, $fltExcessOT);
            // Standard
            $intEarlyAutoDay = 0;
            if ($this->arrEmployee[$strID]['ot_platform'] == 2) { // 0 tidak ada ot, 1 base dari basic, 2 base dari platform
                $this->arrDetail[$strID]['base_ot'] = $this->arrEmployee[$strID]['ot_platform_amount'];
            }
            $this->arrDetail[$strID]['ot_per_hour'] = $this->arrDetail[$strID]['base_ot'] / $fltHourPerMonth * $this->arrConf['ot_percent'] / 100;
            // Half
            //if ($this->arrEmployee[$strID]['get_ot'] == 2 && $intTotalOTAll > ($this->arrConf['half_ot_max'] * 60)) // ada batas ot
            //$objOT->limitOvertime($strID, $this->arrConf['half_ot_max'] * 60);
            // Variable Assign
            $this->arrDetail[$strID]['ot1_min'] = $objOT->getData($strID, "total_ot_1");
            $this->arrDetail[$strID]['ot2_min'] = $objOT->getData($strID, "total_ot_2");
            $this->arrDetail[$strID]['ot2b_min'] = $objOT->getData($strID, "total_ot_2b");
            $this->arrDetail[$strID]['ot3_min'] = $objOT->getData($strID, "total_ot_3");
            $this->arrDetail[$strID]['ot4_min'] = $objOT->getData($strID, "total_ot_4");
            $this->arrDetail[$strID]['total_ot_min'] = $objOT->getData(
                $strID,
                "total_ot_all"
            ); //dalam menit sebelum dikali faktor
            $this->arrDetail[$strID]['otx_min'] = $objOT->getData(
                $strID,
                "total_ot_min"
            ); //dalam mennit setelah dikali faktor
            $this->arrDetail[$strID]['ot_day'] = $objOT->getData($strID, "total_ot_day");
            //$intEarlyAutoDay = $objOT->getData($strID, "early_auto_day");
            //$intOTMealCounter = $objOT->getData($strID, "ot_meal_counter"); //Jika Lebih dari 3 jam x 1 selain itu 0
            $intOTMealCounter = $this->arrDetail[$strID]['total_ot_min'] / 60;
            $this->arrDetail[$strID]['ot1'] = $objOT->getDataAllowance(
                $strID,
                1,
                $this->arrDetail[$strID]['ot_per_hour']
            );
            $this->arrDetail[$strID]['ot2'] = $objOT->getDataAllowance(
                $strID,
                2,
                $this->arrDetail[$strID]['ot_per_hour']
            );
            $this->arrDetail[$strID]['ot2b'] = $objOT->getDataAllowance(
                $strID,
                "2b",
                $this->arrDetail[$strID]['ot_per_hour']
            );
            $this->arrDetail[$strID]['ot3'] = $objOT->getDataAllowance(
                $strID,
                3,
                $this->arrDetail[$strID]['ot_per_hour']
            );
            $this->arrDetail[$strID]['ot4'] = $objOT->getDataAllowance(
                $strID,
                4,
                $this->arrDetail[$strID]['ot_per_hour']
            );
            //if ($this->arrEmployee[$strID]['get_ot'] == 0) {
            if ($this->arrEmployee[$strID]['ot_platform'] == 0) { // tidak dapat overtime
                $this->arrDetail[$strID]['overtime_allowance'] = 0;
            } else { // dianggap ada overtime
                $this->arrDetail[$strID]['overtime_allowance'] = $this->arrDetail[$strID]['ot1'] + $this->arrDetail[$strID]['ot2'] + $this->arrDetail[$strID]['ot2b'] + $this->arrDetail[$strID]['ot3'] + $this->arrDetail[$strID]['ot4'];
                $this->arrDetail[$strID]['overtime_allowance'] = ($this->arrDetail[$strID]['ot1_min'] * 1.5 + $this->arrDetail[$strID]['ot2_min'] * 2 + $this->arrDetail[$strID]['ot3_min'] * 3 + $this->arrDetail[$strID]['ot4_min'] * 4) / 60 * $this->arrDetail[$strID]['ot_per_hour'];
            }
            $fltOTMealAllowance = $this->arrEmployee[$strID]['ot_meal_fee'] * $intOTMealCounter;
            if ($this->arrEmployee[$strID]['ot_platform'] == 3) { //Jika Flat
                /*$this->arrDetail[$strID]['overtime_allowance'] = $fltOTMealAllowance;
                if ($this->arrDetail[$strID]['overtime_allowance'] > $this->arrEmployee[$strID]['ot_limit']) {
                    $this->arrDetail[$strID]['overtime_allowance'] = $this->arrEmployee[$strID]['ot_limit'];
                }*/
                if ($this->arrDetail[$strID]['overtime_allowance'] != $this->arrEmployee[$strID]['ot_platform_amount']) {
                    $this->arrDetail[$strID]['overtime_allowance'] = $this->arrEmployee[$strID]['ot_platform_amount'];
                }
            }
            if ($this->arrEmployee[$strID]['ot_platform'] == 4) { //Flat Hour = Tarif OT Per Jam dikali Total Jam OT
                $this->arrDetail[$strID]['overtime_allowance'] = $this->arrEmployee[$strID]['ot_platform_amount'] * $intOTMealCounter;
            }
            //$fltOTMealAllowance=($fltOTMealAllowance>$this->arrEmployee[$strID]['ot_limit']) ? $this->arrEmployee[$strID]['ot_limit'] : $fltOTMealAllowance;
            $this->arrDetail[$strID]['overtime_allowance'] += $fltOTMealAllowance;
            if ((isset($this->arrConf['overtime_allowance_tax'])) && $this->arrConf['overtime_allowance_tax'] == 't') {
                $this->arrDetail[$strID]['base_tax'] += $this->arrDetail[$strID]['overtime_allowance'];
            }
            if ((isset($this->arrConf['overtime_allowance_jams'])) && $this->arrConf['overtime_allowance_jams'] == 't') {
                $this->arrDetail[$strID]['base_jamsostek'] += $this->arrDetail[$strID]['overtime_allowance'];
            }
            /*$this->arrDetail[$strID]['otmeal_allowance'] += $fltOTMealAllowance;
            if ((isset($this->arrConf['otmeal_allowance_tax'])) && $this->arrConf['otmeal_allowance_tax'] == 't')
              $this->arrDetail[$strID]['base_tax']       += $this->arrDetail[$strID]['otmeal_allowance'];
            if ((isset($this->arrConf['otmeal_allowance_jams'])) && $this->arrConf['otmeal_allowance_jams'] == 't')
              $this->arrDetail[$strID]['base_jamsostek'] += $this->arrDetail[$strID]['otmeal_allowance'];*/
            //$this->arrDetail[$strID]['overtime_allowance']= $this->arrDetail[$strID]['otx_min'] *$this->arrDetail[$strID]['ot_per_hour']/60;
            //$this->arrDetail[$strID]['otx_min']= 60;
            //print_r($this->arrDetail[$strID]['overtime_allowance']);
        }
        //die();
        unset($objOT);
    }

    /* initSalaryDetail : fungsi untuk mengambil data detail gaji per karyawan, sesuai id dari data master
        jika tidak ada id, lakukan inisialisasi per data karyawan
    */
    function calculateSalaryTotal()
    {
        //check whether an allowance component is benefit and to be shown in salary slip
        //If the allowance to be shown in salary slip
        //  if the allowance is benefit, it adds the value of both income and deduction (maintain the balance)
        //  else, it adds the value of income
        //else,
        //  if the allowance is benefit, it doesn't add the value of both income and deduction (maintain the balance)
        //  else, it adds the value of income
        foreach ($this->arrMA as $strCode => $arrMADetail) {
            $strVar = "bol_" . $strCode . "_benefit";
            $$strVar = (isset($this->arrMA[$strCode]['benefit']) && $this->arrMA[$strCode]['benefit'] == 't');
            $strVar = "bol_" . $strCode . "_show";
            $$strVar = (isset($this->arrMA[$strCode]['show']) && $this->arrMA[$strCode]['show'] == 't');
            $strVar = "bol_" . $strCode . "_irregular";
            $$strVar = (isset($this->arrMA[$strCode]['irregular']) && $this->arrMA[$strCode]['irregular'] == 't');
        }
        $intRound = (isset($this->arrConf['salary_round']) && is_numeric(
                $this->arrConf['salary_round']
            )) ? $this->arrConf['salary_round'] : 1;
        foreach ($this->arrDetail AS $strID => $arrInfo) {
            $fltIncome = 0;
            $fltDeduction = 0;
            $fltIrregularIncome = 0;
            //$fltIncome += $arrInfo['tax'];         show the tax allowance as income if the tax method is gross up
            foreach ($this->arrMA as $strCode => $arrMADetail) {
                $strVarBenefit = "bol_" . $strCode . "_benefit";
                $strVarShow = "bol_" . $strCode . "_show";
                $strVarIrr = "bol_" . $strCode . "_irregular";
                if ($$strVarShow || (!$$strVarShow && !$$strVarBenefit)) {
                    if (isset($this->arrDA[$strCode][$strID]['amount'])) {
                        $fltTemp = $this->arrDA[$strCode][$strID]['amount'];
                    } else {
                        $fltTemp = (isset($arrInfo[$strCode])) ? $arrInfo[$strCode] : 0;
                    }
                    $fltIncome += $fltTemp;
                    if ($$strVarIrr) {
                        $fltIrregularIncome += $fltTemp;
                    }
                    if ($$strVarShow && $$strVarBenefit) {
                        $fltDeduction += $fltTemp;
                    }
                }
            }
            //$fltDeduction += $arrInfo['tax'];
            //$fltDeduction += $arrInfo['irregular_tax'];  // akan dihitung dalam fungsi tersendiri, jadi di exclude dulu
            foreach ($this->arrMD as $strCode => $arrMDDetail) {
                if (isset($this->arrDD[$strCode][$strID]['amount'])) {
                    $fltTemp = $this->arrDD[$strCode][$strID]['amount'];
                } else {
                    $fltTemp = (isset($arrInfo[$strCode])) ? $arrInfo[$strCode] : 0;
                }
                $fltDeduction += $fltTemp;
                // echo $strID."=====".$strCode."=====".$fltTemp."      >>>>>>>>>>>    ".$fltDeduction."<br>";
            }
            if (isset($arrInfo['zakat_deduction'])) {
                $fltDeduction -= $arrInfo['zakat_deduction'];
            } // akan dihitung dalam fungsi tersendiri, jadi di exclude dulu
            $this->arrDetail[$strID]['total_net'] = $fltIncome;// total pendapatan
            $this->arrDetail[$strID]['total_deduction'] = $fltDeduction;  // total potongan
            $this->arrDetail[$strID]['total_net_irregular'] = $fltIrregularIncome;      // irregular income - pajak irregular
            //before: total_gross_irregular jadi base irregular zakat (sehingga dipotong pajak dulu)
            //total irregular income
            //$this->arrDetail[$strID]['total_gross_irregular']   = $fltIrregularIncome - $arrInfo['irregular_tax'];
            //current: karena base zakat tidak dipotong pajak dulu, maka base zakat irregular juga tidak perlu dipotong pajak dulu, perhitungan yang menyertakan pajak dilakukan terakhir. Sehingga total gross irregular tidak perlu dihitung saat ini. Sebagai penggantinya pada perhitungan zakat: total_net_irregular: total pendapatan irregular, belum dikurangi pajak
        }
    }

    /* function to fix base_jamsostek */
    function calculateTHR()
    {
        $intBRYearLength = getIntervalDate($this->arrData['date_from_thr'], $this->arrData['date_thru_thr']) + 1;
        foreach ($this->arrDetail AS $strIDEmp => $arrInfo) {
            unset($fltProportion);
            $join = ($this->arrEmployee[$strIDEmp]['join_date'] == "") ? "" : date(
                'Y-m-d',
                strtotime(
                    $this->arrEmployee[$strIDEmp]['join_date']
                )
            );
            if ($join != "") {
                $thr = date('Y-m-d', strtotime($this->arrData['date_thru_thr']));
                $thr_year = (int)date("Y", strtotime($thr));
                $thr_month = (int)date("m", strtotime($thr));
                $thr_day = (int)date("d", strtotime($thr));
                $join_year = (int)date("Y", strtotime($join));
                $join_month = (int)date("m", strtotime($join));
                $join_day = (int)date("d", strtotime($join));
                $year_diff = $this->calculateYearDiff($join_year, $thr_year);
                $month_diff = $this->calculateMonthDiff($join_month, $thr_month, $year_diff);
                $day_diff = $this->calculateMonthDiff($join_day, $thr_day);
            }
            //cek masa kerja karyawan dengan acuan tanggal thr..
            if ($this->arrEmployee[$strIDEmp]['join_date'] == "" || $this->arrData['date_from_thr'] == "" || $this->arrData['date_thru_thr'] == "") {
                $fltProportion = 0;
            } elseif ($year_diff >= 2) {
                $fltProportion = 1;
            }                                            //..jika 12 bulan lebih maka menerima 100%
            elseif ($year_diff == 0 || $year_diff == 1) {
                if ($month_diff > 12) {
                    $fltProportion = 1;
                }                    //..jika 12 bulan lebih maka menerima 100%
                elseif ($month_diff == 12) {
                    if ($day_diff >= 0) {
                        $fltProportion = 1;
                    }                //..jika 12 bulan lebih maka menerima 100%, dispensasi 2 hari
                    else {
                        $fltProportion = ($month_diff - 1) / 12;
                    }
                } elseif ($month_diff > 3) {
                    if ($day_diff >= 0) {
                        $fltProportion = $month_diff / 12;
                    } else {
                        $fltProportion = ($month_diff - 1) / 12;
                    }            //..jika lebih dari 3 bulan menerima proporsional gaji tetap
                } elseif ($month_diff == 3) {
                    if ($day_diff >= 0) {
                        $fltProportion = $month_diff / 12;
                    }    //..jika lebih dari 3 bulan menerima proporsional gaji tetap
                    else {
                        $fltProportion = 0;
                    }
                } else {
                    $fltProportion = 0;
                }                                    //..jika di bawah 3 bulan, tidak menerima thr
            }
            if ($this->irregular == 't') {
                $this->arrDetail[$strIDEmp]['thr_allowance'] = $fltProportion * ($this->arrDetail[$strIDEmp]['basic_salary']);
            } else if ($this->irregular == 'f') {
                $this->arrDetail[$strIDEmp]['thr_allowance'] = 0;
            }
            //proses nilai thr_allowance sesuai parameter salary setting
            if (isset($this->arrMA['thr_allowance']['irregular']) && $this->arrMA['thr_allowance']['irregular'] == 't') {
                $this->arrDetail[$strIDEmp]['base_irregular_tax'] += $this->arrDetail[$strIDEmp]['thr_allowance'];
                //$this->arrDetail[$strIDEmp]['base_tax'] = 0;
            } else {
                if (isset($this->arrMA['thr_allowance']['tax']) && $this->arrMA['thr_allowance']['tax'] == 't') {
                    $this->arrDetail[$strIDEmp]['base_tax'] = $this->arrDetail[$strIDEmp]['thr_allowance'];
                }
            }
        }
    }

    function calculateTax()
    {
        $intRound = (isset($this->arrConf['salary_round']) && is_numeric(
                $this->arrConf['salary_round']
            )) ? $this->arrConf['salary_round'] : 1;
        $objTax = new clsTaxCalculation($this->data);
        foreach ($this->arrDetail AS $strID => $arrInfo) {
            //jika WNA 20%*base tax (PPh 26), else, sesuai PPh21
            if (strtolower($this->arrEmployee[$strID]['nationality']) == "wna") {
                //PPh26
                $fltTax = 0.2 * $arrInfo['base_tax'];
            } else {
                //PPh21
                //hitung masa kerja untuk menentukan pengali PKP disetahunkan
                $bolNPWP = (trim($arrInfo['npwp']) != "");
                $strFamilyStatus = $arrInfo['family_status_code'];
                $strTaxStatus = $arrInfo['tax_status_code'];
                $varSalaryDate = $this->arrData['salary_date'];
                $fltBasic = $arrInfo['base_tax'];
                $intTaxMethod = ($this->arrConf['tax_method'] == 't') ? 1 : 0;
                $intTaxIrregularMethod = ($this->arrConf['tax_irregular_method'] == 't') ? 1 : 0;
                $fltBasicIrregular = $arrInfo['base_irregular_tax'];
                $strJoinDate = $this->arrEmployee[$strID]['join_date'];
                $strResignDate = $this->arrEmployee[$strID]['resign_date'];
                $strEmployeeStatus = $this->arrEmployee[$strID]['employee_status'];
                $objTax->setDataIncludeIrregular(
                    $fltBasic,
                    $fltBasicIrregular,
                    $strTaxStatus,
                    $bolNPWP,
                    $this->arrDetail[$strID]['jamsostek_deduction'],
                    $this->arrDetail[$strID]['pension_deduction'],
                    $strID,
                    $intTaxMethod,
                    $intTaxIrregularMethod,
                    $this->arrBaseTaxPayedTaxBefore[$strID],
                    $this->salaryCalcMonth,
                    $this->salaryCalcYear,
                    $strJoinDate,
                    $strResignDate
                );
                # Check tax calculation method, 0 = cumulative. This calculation method is default in devosa, referencing base tax and tax from previous months.
                if ($this->arrConf['tax_calculation'] == '0') {
                    $fltTax = $objTax->getTax(true);
                    $fltIrregularTax = $objTax->getTax(false);
                } # Check tax calculation method, 1 = flat. This method is calculated referencing only base tax and tax in current month.
                else if ($this->arrConf['tax_calculation'] == '1') {
                    if ($this->salaryCalcMonth === 12 || (isset($strResignDate) && $strResignDate != '')) {
                        $fltTax = $objTax->getTax(true);
                        $fltIrregularTax = $objTax->getTax(false);
                    } else {
                        $fltTax = $objTax->getTaxAnnual(true);
                        $fltIrregularTax = $objTax->getTaxAnnual(false);
                    }
                }
            }
            $this->arrDetail[$strID]['tax'] = ($fltTax < 0) ? 0 : $fltTax;
            $this->arrDetail[$strID]['calculated_tax'] = ($fltTax < 0) ? $fltTax : 0;
            $this->arrDetail[$strID]['irregular_tax'] = ($fltIrregularTax < 0) ? 0 : $fltIrregularTax;
            $this->arrDetail[$strID]['tax_reduction'] = $objTax->fltPTKP;
            if ($intTaxMethod == 1 && $intTaxIrregularMethod == 1) {
                $this->arrDetail[$strID]['tax_allowance'] += $this->arrDetail[$strID]['tax']; // tambah nilai potongan
                $this->arrDetail[$strID]['base_tax'] += $this->arrDetail[$strID]['tax']; // tambah nilai potongan
                $this->arrDetail[$strID]['irregular_tax_allowance'] += $this->arrDetail[$strID]['irregular_tax']; // tambah nilai potongan
                $this->arrDetail[$strID]['base_irregular_tax'] += $this->arrDetail[$strID]['irregular_tax']; // tambah nilai potongan
            } elseif ($intTaxMethod == 1 && $intTaxIrregularMethod == 0) {
                $this->arrDetail[$strID]['tax_allowance'] += $this->arrDetail[$strID]['tax']; // tambah nilai potongan
                $this->arrDetail[$strID]['base_tax'] += $this->arrDetail[$strID]['tax']; // tambah nilai potongan
            }
            $this->arrDetail[$strID]['total_deduction'] += $this->arrDetail[$strID]['tax']; // tambah nilai potongan
            $this->arrDetail[$strID]['total_deduction'] += $this->arrDetail[$strID]['irregular_tax']; // tambah nilai potongan
            /*Tambahan kalkulasi tax pesangon disini */
            /*if (isset($this->arrDA['final_pesangon']) && isset($this->arrDA['final_pesangon'][$strID]) && isset($this->arrDA['final_pesangon'][$strID]['amount']) && $this->arrDA['final_pesangon'][$strID]['amount'] > 0) {
                $fltPesangon = $this->arrDA['final_pesangon'][$strID]['amount'];
                $this->arrDetail[$strID]['tax_pesangon'] = $objTax->calculateTaxPesangon($fltPesangon);
            } else {
                $this->arrDetail[$strID]['tax_pesangon'] = 0;
            }*/
            /*Tambahan kalkulasi tax pensiun disini */
            /*if (isset($this->arrDA['final_pensiun']) && isset($this->arrDA['final_pensiun'][$strID]) && isset($this->arrDA['final_pensiun'][$strID]['amount']) && $this->arrDA['final_pensiun'][$strID]['amount'] > 0) {
                $fltPensiun = $this->arrDA['final_pensiun'][$strID]['amount'];
                $this->arrDetail[$strID]['tax_pensiun'] = $objTax->calculateTaxPensiun($fltPensiun);
            } else {
                $this->arrDetail[$strID]['tax_pensiun'] = 0;
            }*/
            //$fltTotal = $this->arrDetail[$strID]['total_net'] - $this->arrDetail[$strID]['total_deduction'] + $this->arrDetail[$strID]['tax_allowance'] + $this->arrDetail[$strID]['irregular_tax_allowance'];// - $this->arrDetail[$strID]['tax_pesangon'];
            $fltTotal = $this->arrDetail[$strID]['total_net'] - $this->arrDetail[$strID]['total_deduction'] + $this->arrDetail[$strID]['tax_allowance'];// - $this->arrDetail[$strID]['tax_pesangon'];
            $fltIrrTotal = $this->arrDetail[$strID]['total_net_irregular'] - $this->arrDetail[$strID]['zakat_deduction_irregular'] - $this->arrDetail[$strID]['irregular_tax'];
            $fltRound = roundMoney($fltTotal, $intRound);
            $this->arrDetail[$strID]['total_gross'] = $fltTotal;      // total gaji yang diterima
            $this->arrDetail[$strID]['total_gross_round'] = $fltRound;      // total gaji yang diterima, dibulatkan
            $this->arrDetail[$strID]['total_gross_irregular'] = $fltIrrTotal;      // total gaji yang diterima, dibulatkan
            if ($this->irregular == 't') {
                if ($intTaxIrregularMethod == 1) {
                    $this->arrDetail[$strID]['total_gross'] = $fltBasicIrregular;
                } else {
                    $this->arrDetail[$strID]['total_gross'] = $fltBasicIrregular - $fltIrregularTax;
                }
                $fltRound = roundMoney($this->arrDetail[$strID]['total_gross'], $intRound);
                $this->arrDetail[$strID]['total_gross_irregular'] = $this->arrDetail[$strID]['total_gross'];
            }
            /*//Tambahan kalkulasi tax pesangon disini
            if (isset($this->arrDA['pesangon']) && isset($this->arrDA['pesangon'][$strID]) && isset($this->arrDA['pesangon'][$strID]['amount']) && $this->arrDA['pesangon'][$strID]['amount'] > 0){
                $fltPesangon = $this->arrDA['pesangon'][$strID]['amount'];
                $this->arrDetail[$strID]['tax_pesangon'] = $objTax->calculateTaxPesangon($fltPesangon);
      }else{
          $this->arrDetail[$strID]['tax_pesangon'] = 0;
      }*/
        }
        unset($objTax);
    }

    function calculateYearDiff($join_year, $thr_year)
    {
        $difference = $thr_year - $join_year;
        return $difference;
    }

    function calculateZakat()
    {
        $fltNishab = $this->arrConf['nishab'];
        $fltZakatPercentage = $this->arrConf['zakat_deduction'] / 100;
        foreach ($this->arrDetail as $strID => $arrInfo) {
            $this->arrDetail[$strID]['zakat_deduction'] = 0; // as zakat deduction
            if ($this->arrEmployee[$strID]['zakat'] != "t") {
                continue;
            }
            $fltNetIncome = $arrInfo['total_net'];
            $fltIrrNetIncome = $arrInfo['total_net_irregular'];
            //total pendapatan dikurangi minimum living cost dan pajak (sebelum dipotong deduction)
            //ada perubahan cara perhitungan zakat
            //before base zakat adalah hasil pendapatan dikrangi pajak
            //current base zakat adalah pendapatan sebelum dikurangi pajak
            $fltTemp = $fltNetIncome - $this->arrEmployee[$strID]['minimum_living_cost'] /*- $arrInfo['tax'] - $arrInfo['irregular_tax']*/
            ;
            //total pendapatan irregular minimum pajak irregular
            $fltIrrTemp = $fltIrrNetIncome - $this->arrEmployee[$strID]['minimum_living_cost'];
            if ($fltTemp > $fltNishab) {
                $fltTemp *= $fltZakatPercentage;
            } else {
                $fltTemp = 0;
            }
            if ($fltIrrTemp > $fltNishab) {
                $fltIrrTemp *= $fltZakatPercentage;
            } else {
                $fltIrrTemp = 0;
            }
            $intRound = (isset($this->arrConf['salary_round']) && is_numeric(
                    $this->arrConf['salary_round']
                )) ? $this->arrConf['salary_round'] : 1;
            $this->arrDetail[$strID]['zakat_deduction'] = $fltTemp; // as zakat deduction
            $this->arrDetail[$strID]['zakat_deduction_irregular'] = $fltIrrTemp; // as zakat deduction
            if (isset($this->arrConf['zakat_deduction_tax']) && $this->arrConf['zakat_deduction_tax'] == 't') {
                $this->arrDetail[$strID]['base_tax'] -= $this->arrDetail[$strID]['zakat_deduction'];
                $this->arrDetail[$strID]['base_irregular_tax'] -= $this->arrDetail[$strID]['zakat_deduction_irregular'];
            }
            $this->arrDetail[$strID]['total_deduction'] += $fltTemp; // tambah nilai potongan
            //tidak ada total_deduction_irregular karena deduction pada irregular income hanya zakat, tidak perlu ditotal
            //perubahan rule perhitungan zakat
            //before: zakat setelah pajak, sehingga nilainya adalah nilai akhir dan di simpan sebagai total gross
            /*
            $this->arrDetail[$strID]['total_gross']                 -= $fltTemp;  // total gaji diterima
            $this->arrDetail[$strID]['total_gross_round']           -= roundMoney($fltTemp, $intRound);  //total gaji diterima bulat
            $this->arrDetail[$strID]['total_gross_irregular']       -= $fltIrrTemp;  // total gaji diterima
            */
            //current: zakat sebelum pajak, nilainya bukan nilai akhir yang di simpan sebagai total gross
            //dipidahkan menjadi setelah hitung pajak
        }
    }

    /* getProrateDay :  fungsi untuk mengambil jumlah hari kerja karyawan yang baru masuk atau resign
        untuk keperluan prorata gaji pokok
      input : id employee
      output: jumlah hari, jika karyawan tersebut dianggap sebagai prorata
    */
    function compute($strIDEmp, $strCode, $initCode, $fltMultiplier, $fltProrate)
    {
        $this->arrDetail[$strIDEmp][$strCode] = ($initCode == "") ? 0 : $fltMultiplier * $initCode;
        if (isset($this->arrConf[$strCode . '_ot']) && $this->arrConf[$strCode . '_ot'] == 't') {
            $this->arrDetail[$strIDEmp]['base_ot'] += $this->arrDetail[$strIDEmp][$strCode];
        }
        if (isset($this->arrConf[$strCode . '_jams']) && $this->arrConf[$strCode . '_jams'] == 't') {
            $this->arrDetail[$strIDEmp]['base_jamsostek'] += $this->arrDetail[$strIDEmp][$strCode];
        }
        if (isset($this->arrConf[$strCode . '_prorate']) && $this->arrConf[$strCode . '_prorate'] == 't') {
            $this->arrDetail[$strIDEmp][$strCode] *= (INVERSE_PRORATE) ? $fltInvProrate : $fltProrate;
        }
        if (isset($this->arrConf[$strCode . '_tax']) && $this->arrConf[$strCode . '_tax'] == 't') {
            $this->arrDetail[$strIDEmp]['base_tax'] += $this->arrDetail[$strIDEmp][$strCode];
        }
    }

    /* getEmployeeLoan : cari data pinjaman yang perlu dibayar di bulan perhitungan gaji
        data disimpan di atribut arrLoan
        input : tanggal awal dan tanggal akhir, format SQL
    */
    function compute_deduction_pdm_fff($fltAmount, $fltMaxamount, $strIDEmployee = null, $arrAbsenceDediction = null)
    {
        return $fltAmount;
    }

    /**
     * Function to calculate daily deduction related to absence type. The setting is prorate = false, daily = true, maxlink = false.
     *
     * @param      $fltAmount
     * @param      $fltMaxamount
     * @param null $strIDEmployee
     * @param null $arrAbsenceDeduction
     *
     * @return mixed
     */
    function compute_deduction_pdm_ftf($fltAmount, $fltMaxamount, $strIDEmployee = null, $arrAbsenceDeduction = null)
    {
        global $db;
        $intEmployeeUnpaidAbsence = 0;
        $intStart = $this->arrData['date_from'];
        $intFinish = $this->arrData['date_thru'];
        $objAtt = new clsAbsenceReport($db, $intStart, $intFinish);
        $objAtt->generateAbsenceReport();
        if (isset($arrAbsenceDeduction) && count($arrAbsenceDeduction) > 0)
        {
          foreach($arrAbsenceDeduction as $strAbsenceCode)
          {

            $intEmployeeUnpaidAbsence += $objAtt->getDataAbsence($strIDEmployee, $strAbsenceCode);
          }
        }
        return ($fltAmount * $intEmployeeUnpaidAbsence);
    }

    /* compute : fungsi untuk menghitung data gaji yang terkait dengan parameter yang sesuai,
       input   : nik, kode tunjangan, nilai awal tunjangan terkait dari database, parameter pengali nilai unit gaji (default = 1), persentase gaji hasil prorata
    */
    function compute_deduction_pdm_ftt($fltAmount, $fltMaxamount, $strIDEmployee = null, $arrAbsenceDeduction = null)
    {
        $xAmount = $this->compute_deduction_pdm_ftf($fltAmount, $fltMaxamount, $strIDEmployee, $arrAbsenceDeduction);
        $yAmount = ($fltMaxamount < $xAmount) ? $fltMaxamount : $xAmount;
        //echo "<br>x=".$xAmount."| y=".$yAmount."| max=".$fltMaxamount."|";
        return $yAmount;
    }

    /* calculateOvertime : fungsi untuk menghitung tunjangan lembur, sesuai lembur yang dilakukan
    */
    function compute_deduction_pdm_fttkerajinan_allowance(
        $fltAmount,
        $fltMaxamount,
        $strIDEmployee = null,
        $arrAbsenceDeduction = null
    ) {
        return $this->compute_deduction_pdm_ftt(
            $fltAmount,
            $this->arrDetail[$strIDEmployee]['kerajinan_allowance'],
            $strIDEmployee,
            $arrAbsenceDeduction
        );
    }

    /* calculateTHR : fungsi untuk menghitung data THR (basic salary + fix allowance yang base jamsostek)
    */
    function compute_deduction_pdm_tff($fltAmount, $fltMaxamount, $strIDEmployee = null, $arrAbsenceDediction = null)
    {
        return ($fltAmount * $fltProrate);
    }

    /*fungsi untuk menghitung jeda bulan*/
    function compute_deduction_pdm_tft($fltAmount, $fltMaxamount, $strIDEmployee = null, $arrAbsenceDediction = null)
    {
        $fltAmount = compute_deduction_pdm_tff($fltAmount, $fltMaxamount, $strIDEmployee, $arrAbsenceDediction);
        return ($fltMaxamount < $fltAmount) ? $fltMaxamount : $fltAmount;
    }

    /*end calculateMonthDiff


    /*fungsi untuk menghitung jeda hari*/
    function compute_deduction_pdm_tftkerajinan_allowance(
        $fltAmount,
        $fltMaxamount,
        $strIDEmployee = null,
        $arrAbsenceDediction = null
    ) {
        return $this->compute_deduction_pdm_tft(
            $fltAmount,
            $this->arrDetail[$strIDEmployee]['kerajinan_allowance'],
            $strIDEmployee,
            $arrAbsenceDediction
        );
    }

    /*end calculateDayDiff

    /*fungsi untuk menghitung jeda tahun*/
    function fixBaseJamsostek($arrayDetail)
    {
        foreach ($arrayDetail as $idEmp => $detailValue) {
            $arrayDetail[$idEmp]['base_jamsostek'] = 0;
            $arrayDetail[$idEmp]['base_tax'] = 0;
            $arrayDetail[$idEmp]['tax_allowance'] = 0;
            $arrayDetail[$idEmp]['base_ot'] = 0;
        }
        return $arrayDetail;
    }
    /*end calculateYearDiff


      /*function calculateTHR()
      {
        $intTHRYearLength = getIntervalDate($this->arrData['date_from_thr'], $this->arrData['date_thru_thr']) + 1;
        foreach($this->arrDetail AS $strIDEmp => $arrInfo)
        {
          //cek masa kerja karyawan dengan acuan tanggal thr
          //jika 1 tahun atau lebih maka menerima 100%
          //jika 3 bulan sampai kuang dari 1 tahun, maka menerima proporsional
          //jika di bawah 3 bulan, tidak menerima thr
          unset($fltProportion);

          if($this->arrEmployee[$strIDEmp]['join_date'] == "")
            $fltProportion = 0;
          elseif(dateCompare($this->arrEmployee[$strIDEmp]['join_date'], $this->arrData['date_from_thr']) <= 0)
            $fltProportion = 1;
          /*elseif(dateCompare(getNextDateNextMonth($this->arrEmployee[$strIDEmp]['join_date'], 3), $this->arrData['date_thru_thr']) <= 0)
            $fltProportion = getIntervalDate($this->arrEmployee[$strIDEmp]['join_date'], $this->arrData['date_thru_thr']) / $intTHRYearLength;
          else
            $fltProportion = 0;*/
    /*else
      $fltProportion = (getIntervalDate($this->arrEmployee[$strIDEmp]['join_date'], $this->arrData['date_thru_thr']) + 1) / $intTHRYearLength;
    //ambil gaji all in dari nonfix salary jika ada
    $fltAllInSalary = (isset($this->arrDA['gaji_all_in'][$strIDEmp]['amount'])) ? $this->arrDA['gaji_all_in'][$strIDEmp]['amount'] : 0;


    $this->arrDetail[$strIDEmp]['thr_allowance'] =  $fltProportion * ($this->arrDetail[$strIDEmp]['basic_salary'] +
                                                    $this->arrDetail[$strIDEmp]['position_allowance'] +
                                                    $this->arrDetail[$strIDEmp]['meal_allowance'] +
                                                    $this->arrDetail[$strIDEmp]['transport_allowance'] +
                                                    $this->arrDetail[$strIDEmp]['vehicle_allowance'] + $fltAllInSalary) ;

    //proses nilai thr sesuai parameter salary setting
    if (isset($this->arrConf['thr_allowance_irregular']) && $this->arrConf['thr_allowance_irregular'] == 't' )
      $this->arrDetail[$strIDEmp]['base_irregular_tax'] += $this->arrDetail[$strIDEmp]['thr_allowance'];
    else
    {
      if (isset($this->arrConf['thr_allowance_ot']) && $this->arrConf['thr_allowance_ot'] == 't')
        $this->arrDetail[$strIDEmp]['base_ot'] += $this->arrDetail[$strIDEmp]['thr_allowance'];
      //if (isset($this->arrConf['thr_allowance_prorate']) && $this->arrConf['thr_allowance_prorate'] == 't')
        //$this->arrDetail[$strIDEmp]['thr_allowance'] *= $fltProrate;
      if (isset($this->arrConf['thr_allowance_jams']) && $this->arrConf['thr_allowance_jams'] == 't')
        $this->arrDetail[$strIDEmp]['base_jamsostek'] += $this->arrDetail[$strIDEmp]['thr_allowance'];
      if (isset($this->arrConf['thr_allowance_tax']) && $this->arrConf['thr_allowance_tax'] == 't')
        $this->arrDetail[$strIDEmp]['base_tax'] += $this->arrDetail[$strIDEmp]['thr_allowance'];
    }
  }
  }
  */
    /* calculateLeaveAllowance : fungsi untuk menghitung data Leave Allowance (basic salary + fix allowance yang base jamsostek)/2
    */
    function generateMasterAllowanceSQL($strCode)
    {
        $strResult = "";
        if ($strCode == "") {
            return "";
        }
        $arrInfo = $this->arrMA[$strCode];
        $strResult = "
        INSERT INTO hrd_salary_master_allowance (
          id_salary_master, allowance_code, \"show\", prorate, is_default,
          daily, ot, jams, tax, irregular, benefit,
          hidezero
        )
        VALUES (
          '" . $this->strDataID . "', '$strCode', '" . $arrInfo['show'] . "', '" . $arrInfo['prorate'] . "', '" . $arrInfo['is_default'] . "',
          '" . $arrInfo['daily'] . "', '" . $arrInfo['ot'] . "', '" . $arrInfo['jams'] . "', '" . $arrInfo['tax'] . "', '" . $arrInfo['irregular'] . "', '" . $arrInfo['benefit'] . "', '" . $arrInfo['hidezero'] . "'
        );
      ";
        return $strResult;
    }

    /* calculateAbsenceDeduction : menghitung potongan absen
    */
    function generateMasterDeductionSQL($strCode, $arrInfo = null)
    {
        $strResult = "";
        if ($strCode == "") {
            return "";
        }
        $arrInfo = $this->arrMD[$strCode];
        $strResult = "
        INSERT INTO hrd_salary_master_deduction (
          id_salary_master, deduction_code, \"show\", prorate, is_default,
          daily, ot, jams, tax, hidezero
        )
        VALUES (
          '" . $this->strDataID . "', '$strCode', '" . $arrInfo['show'] . "', '" . $arrInfo['prorate'] . "', '" . $arrInfo['is_default'] . "',
          '" . $arrInfo['daily'] . "', '" . $arrInfo['ot'] . "', '" . $arrInfo['jams'] . "', '" . $arrInfo['tax'] . "', '" . $arrInfo['hidezero'] . "'
        );
      ";
        return $strResult;
    }

    /* calculateLateDeduction : fungsi untuk menghitung late deduction untuk divisi selain TI, managerial tidak terhitung
    */
    function getArrayDetailBaseTaxPayedTaxBefore($intMonth, $intYear)
    {
        if ($intMonth >= 1) {
            $arrEmpBaseTaxPayedTaxBefore = [];
            for ($i = $intMonth; $i > 0; $i--) {
                $strSQL = "SELECT id FROM hrd_salary_master WHERE EXTRACT(MONTH FROM salary_date) = '$i' AND EXTRACT(YEAR FROM salary_date) = '$intYear' AND status=2 ORDER BY salary_date";
                $res = $this->data->execute($strSQL);
                while ($row = $this->data->fetchrow($res)) {
                    $salaryMasterID = $row['id'];
                    $strSQL2 = "SELECT id_employee, base_tax, base_irregular_tax, tax, irregular_tax, jamsostek_deduction, pension_deduction FROM hrd_salary_detail WHERE id_salary_master='$salaryMasterID'";
                    $res2 = $this->data->execute($strSQL2);
                    while ($row2 = $this->data->fetchrow($res2)) {
                        $arrEmpBaseTaxPayedTaxBefore[$row2['id_employee']][$i] = $row2;
                    }
                }
            }
            if (count($arrEmpBaseTaxPayedTaxBefore)) {
                foreach ($arrEmpBaseTaxPayedTaxBefore as $empID => $taxInfo) {
                    $this->arrBaseTaxPayedTaxBefore[$empID]['base_tax'] = 0;
                    $this->arrBaseTaxPayedTaxBefore[$empID]['base_irregular_tax'] = 0;
                    $this->arrBaseTaxPayedTaxBefore[$empID]['pension_deduction'] = 0;
                    $this->arrBaseTaxPayedTaxBefore[$empID]['jamsostek_deduction'] = 0;
                    $this->arrBaseTaxPayedTaxBefore[$empID]['tax'] = 0;
                    $this->arrBaseTaxPayedTaxBefore[$empID]['irregular_tax'] = 0;
                    foreach ($taxInfo as $key => $value) {
                        $fltPensionDeduction = $value['pension_deduction'];
                        $fltJamsostekDeduction = $value['jamsostek_deduction'];
                        $fltBasic = $value['base_tax'];
                        $fltBaseIrrTax = $value['base_irregular_tax'];
                        $fltIrrTax = $value['irregular_tax'];
                        $fltTax = $value['tax'];
                        $this->arrBaseTaxPayedTaxBefore[$empID]['base_tax'] = $this->arrBaseTaxPayedTaxBefore[$empID]['base_tax'] + $fltBasic;
                        $this->arrBaseTaxPayedTaxBefore[$empID]['base_irregular_tax'] = $this->arrBaseTaxPayedTaxBefore[$empID]['base_irregular_tax'] + $fltBaseIrrTax;
                        $this->arrBaseTaxPayedTaxBefore[$empID]['tax'] = $this->arrBaseTaxPayedTaxBefore[$empID]['tax'] + $fltTax;
                        $this->arrBaseTaxPayedTaxBefore[$empID]['irregular_tax'] = $this->arrBaseTaxPayedTaxBefore[$empID]['irregular_tax'] + $fltIrrTax;
                        $this->arrBaseTaxPayedTaxBefore[$empID]['jamsostek_deduction'] = $this->arrBaseTaxPayedTaxBefore[$empID]['jamsostek_deduction'] + $fltJamsostekDeduction;
                        $this->arrBaseTaxPayedTaxBefore[$empID]['pension_deduction'] = $this->arrBaseTaxPayedTaxBefore[$empID]['pension_deduction'] + $fltPensionDeduction;
                    }
                }
            }
            $strSQL = "SELECT t0.*, t1.id FROM hrd_tax_paid_before as t0 LEFT JOIN hrd_employee as t1 ON t0.employee_id = t1.employee_id  WHERE year = '$intYear'";
            $res = $this->data->execute($strSQL);
            while ($row = $this->data->fetchrow($res)) {
                $this->arrBaseTaxPayedTaxBefore[$row['id']]['base_tax'] = $this->arrBaseTaxPayedTaxBefore[$row['id']]['base_tax'] + $row['taxable_regular_income'];
                $this->arrBaseTaxPayedTaxBefore[$row['id']]['base_irregular_tax'] = $this->arrBaseTaxPayedTaxBefore[$row['id']]['base_irregular_tax'] + $row['taxable_irregular_income'];
                $this->arrBaseTaxPayedTaxBefore[$row['id']]['tax'] = $this->arrBaseTaxPayedTaxBefore[$row['id']]['tax'] + $row['tax_regular_paid'];
                $this->arrBaseTaxPayedTaxBefore[$row['id']]['irregular_tax'] = $this->arrBaseTaxPayedTaxBefore[$row['id']]['irregular_tax'] + $row['tax_irregular_paid'];
            }
        } else {
            $this->arrBaseTaxPayedTaxBefore = null;
        }
    }

    /* calculateDeduction : fungsi untuk mengambil (menghitung) data potongan lain-lain
    */
    function getEmployeeAllowanceDetail($strIDEmp, $strCode)
    {
        if (isset($this->arrDA[$strCode][$strIDEmp]['amount'])) {
            return $this->arrDA[$strCode][$strIDEmp]['amount'];
        } else {
            return 0;
        }
    }
    //prorated monthly deduction with maximum limit.
    //maximum limit is the maximum amount of deduction, is equal to a linked allowance
    function getEmployeeDeductionDetail($strIDEmp, $strCode)
    {
        if (isset($this->arrDD[$strCode][$strIDEmp]['amount'])) {
            return $this->arrDD[$strCode][$strIDEmp]['amount'];
        } else {
            return 0;
        }
    }
    //prorated monthly deduction with maximum limit.
    //maximum limit is the maximum amount of deduction, is equal to a linked allowance
    //special case when maximum limit refers to special allowance
    function getEmployeeLoan($strDateFrom, $strDateThru)
    {
        $strSQL = "SELECT * FROM hrd_loan WHERE status = 0
                  AND payment_from < '$strDateThru'
                  AND (payment_thru + interval '1 months') > '$strDateThru'  ";
        $resDb = $this->data->execute($strSQL);
        while ($rowDb = $this->data->fetchrow($resDb)) {
            if ($rowDb['periode'] == 0) {
                $fltLoan = 0;
            } else {
                $fltLoan = round((((100 + $rowDb['interest']) / 100) * $rowDb['amount']) / $rowDb['periode']);
            }
            //        if ($rowDb['id_employee']
            if (isset($this->arrLoan[$rowDb['id_employee']])) {
                $this->arrLoan[$rowDb['id_employee']]['amount'] += $fltLoan;
            } else {
                $this->arrLoan[$rowDb['id_employee']]['amount'] = $fltLoan;
            }
            $this->arrLoan[$rowDb['id_employee']]['id'][] = $rowDb['id'];
        }
    }

    //prorated deduction without maxlink
    function getEmployeeSalaryDetail($strIDEmp, $strField)
    {
        if ($strField == "total_unpaid_absence") {
            if (isset($this->arrDetail[$strIDEmp][$strField])) {
                return $this->arrDetail[$strIDEmp][$strField];
            } else {
                return 0;
            }
        }
        if (isset($this->arrDetail[$strIDEmp][$strField])) {
            return $this->arrDetail[$strIDEmp][$strField];
        } else {
            return "";
        }
    }
    //daily deduction with maximum limit
    //maximum limit is the maximum amount of deduction, is equal to a linked allowance
    function getFixComponent($strCode)
    {
        $arrTemp = explode("_", $strCode);
        $strType = $arrTemp[1] . "_code";
        $arrResult = [];
        if (isset($this->arrConf[$strCode . "_active"]) && $this->arrConf[$strCode . "_active"] == "t") {
            $bolActive = "t";
            $bolDefault = "t";
            $strName = (isset($this->arrConf[$strCode . "_name"]) && $this->arrConf[$strCode . "_name"] != "") ? $this->arrConf[$strCode . "_name"] : "";
            $bolShow = (isset($this->arrConf[$strCode . "_show"]) && $this->arrConf[$strCode . "_show"] != "") ? $this->arrConf[$strCode . "_show"] : "f";
            $bolProrate = (isset($this->arrConf[$strCode . "_prorate"]) && $this->arrConf[$strCode . "_prorate"] != "") ? $this->arrConf[$strCode . "_prorate"] : "f";
            $bolTax = (isset($this->arrConf[$strCode . "_tax"]) && $this->arrConf[$strCode . "_tax"] != "") ? $this->arrConf[$strCode . "_tax"] : "f";
            $bolIr = (isset($this->arrConf[$strCode . "_irregular"]) && $this->arrConf[$strCode . "_irregular"] != "") ? $this->arrConf[$strCode . "_irregular"] : "f";
            $bolJams = (isset($this->arrConf[$strCode . "_jams"]) && $this->arrConf[$strCode . "_jams"] != "") ? $this->arrConf[$strCode . "_jams"] : "f";
            $bolOT = (isset($this->arrConf[$strCode . "_ot"]) && $this->arrConf[$strCode . "_ot"] != "") ? $this->arrConf[$strCode . "_ot"] : "f";
            $bolBenefit = (isset($this->arrConf[$strCode . "_benefit"]) && $this->arrConf[$strCode . "_benefit"] != "") ? $this->arrConf[$strCode . "_benefit"] : "f";
            $bolDaily = (isset($this->arrConf[$strCode . "_daily"]) && $this->arrConf[$strCode . "_daily"] != "") ? $this->arrConf[$strCode . "_daily"] : "f";
            $bolHidezero = (isset($this->arrConf[$strCode . "_hidezero"]) && $this->arrConf[$strCode . "_hidezero"] != "") ? $this->arrConf[$strCode . "_hidezero"] : "f";
            $bolMultival = (isset($this->arrConf[$strCode . "_multival"]) && $this->arrConf[$strCode . "_multival"] != "") ? $this->arrConf[$strCode . "_multival"] : "f";
            $arrResult = [
                $strType     => $strCode,
                "show"       => $bolShow,
                "prorate"    => $bolProrate,
                "name"       => $strName,
                "is_default" => $bolDefault,
                "tax"        => $bolTax,
                "irregular"  => $bolIr,
                "hidezero"   => $bolHidezero,
                "daily"      => $bolDaily,
                "ot"         => $bolOT,
                "jams"       => $bolJams,
                "multival"   => $bolMultival
            ];
            if (end($arrTemp) == "allowance") {
                $arrResult['benefit'] = $bolBenefit;
            }
        }
        return $arrResult;
    }
    //daily deduction with maximum limit
    //maximum limit is the maximum amount of deduction, is equal to a linked allowance
    //special case when maximum limit refers to special allowance
    function getIDEmployeeFromDetailID($strID)
    {
        if (isset($this->arrDetailEmployee[$strID])) {
            return $this->arrDetailEmployee[$strID];
        } else {
            return "";
        }
    }

    /**
     * Function to get prorate value.
     *
     * @param $strID
     *
     * @return float|int
     */
    function getProrateDay($strID)
    {
        $intResult = 1;
        if ($strID != "" && isset($this->arrEmployee[$strID])) {
            if ($this->arrConf['prorate_method'] == '0' || $this->arrConf['prorate_method'] == '1') {
                # Get max value between join date and salary start date. If join date >= salary start date, the value is join date, else the value is salary start date.
                $intJoinSalaryStartDate = (dateCompare($this->arrEmployee[$strID]['join_date'], $this->arrData['salary_start_date']) >= 0) ? $this->arrEmployee[$strID]['join_date'] : $this->arrData['salary_start_date'];
                # Get min value between resign date and salary finish date. If resign date < salary finish date, the value is resign date, else the value is salary finish date.
                $intResignSalaryFinishDate = (dateCompare($this->arrEmployee[$strID]['resign_date'], $this->arrData['salary_finish_date']) < 0) ? $this->arrEmployee[$strID]['resign_date'] : $this->arrData['salary_finish_date'];
                if ($this->arrConf['prorate_method'] == '0') {
                    # If prorate method is 0, then prorate days is days differences between $intJoinSalaryDate and $intResignSalaryFinishDate, EXCLUDING weekends and national holiday.
                    $intProrateDays = $this->arrDetail[$strID]['working_day'];
                    $intResult = ($this->objWork->getTotalWorkDay($this->data, $intJoinSalaryStartDate, $intResignSalaryFinishDate))/$intProrateDays;
                }
                else if ($this->arrConf['prorate_method'] == '1') {
                    # If prorate method is 1, then prorate days is days differences between $intJoinSalaryDate and $intResignSalaryFinishDate, INCLUDING weekends and national holiday.
                    $intProrateDays = getIntervalDate($this->arrData['salary_start_date'], $this->arrData['salary_finish_date']) + 1;
                    $intResult = (getIntervalDate($intJoinSalaryStartDate, $intResignSalaryFinishDate) + 1)/$intProrateDays;
                }
            }
            else {
                # If prorate method is 2 or 3, then prorate days is set in general salary setting.
                $intProrateDays = (getSetting('prorate_days') == "NULL" || getSetting('prorate_days') == "") ? 0 : getSetting('prorate_days');
                if ($this->arrConf['prorate_method'] == '2') {
                    # Get day difference between join date and salary start date, EXCLUDING weekends and national holiday.
                    $intJoinSalaryStartPeriod = isset($this->arrEmployee[$strID]['join_date']) ? $this->objWork->getTotalWorkDay($this->data, $this->arrData['salary_start_date'], $this->arrEmployee[$strID]['join_date']) : 0;
                    # Get day difference between resign date and salary finish date, EXCLUDING weekends and national holiday.
                    $intResignSalaryFinishPeriod = isset($this->arrEmployee[$strID]['resign_date']) ? $this->objWork->getTotalWorkDay($this->data, $this->arrEmployee[$strID]['resign_date'], $this->arrData['salary_finish_date']) : 0;
                }
                else if ($this->arrConf['prorate_method'] == '3') {
                    # Get day difference between join date and salary start date, INCLUDING weekends and national holiday.
                    $intJoinSalaryStartPeriod = isset($this->arrEmployee[$strID]['join_date']) ? getIntervalDate($this->arrData['salary_start_date'], $this->arrEmployee[$strID]['join_date']) : 0;
                    # Get day difference between resign date and salary finish date, INCLUDING weekends and national holiday.
                    $intResignSalaryFinishPeriod = isset($this->arrEmployee[$strID]['resign_date']) ? getIntervalDate($this->arrEmployee[$strID]['resign_date'], $this->arrData['salary_finish_date']) : 0;
                }
                $intResult = ($intProrateDays-($intJoinSalaryStartPeriod+$intResignSalaryFinishPeriod))/$intProrateDays;
            }
        }
        return $intResult;
    }

    function getSalaryPeriodDate()
    {
        $objDt = new clsCommonDate();
        // hitung periode hari untuk perhingan gaji (bukan untuk kehadiran dan lembur)
        if ($objDt->validDate($this->arrData['salary_date'])) {
            $arrDt = explode("-", $this->arrData['salary_date']);
            $this->arrData['salary_start_date'] = $arrDt[0] . "-" . ($arrDt[1]) . "-01";
            $this->arrData['salary_finish_date'] = $arrDt[0] . "-" . $arrDt[1] . "-" . lastday($arrDt[1], $arrDt[0]);
        } else {
            $this->arrData['salary_start_date'] = $this->arrData['salary_finish_date'] = "";
        }
        unset($objDt);
    }

    /* calculateTax : fungsi untuk menghitung pph21 masing-masing karyawan
    */
    function getStandardWorkingDay()
    {
        global $db;
        $objW = new clsWorkTime($this->data);
        $this->arrData['meal_duration'] = $objW->getTotalWorkDay(
            $db,
            $this->arrData['date_from'],
            $this->arrData['date_thru']
        );
        unset($objW);
    }

    /* calculateZakat : fungsi untuk menghitung potongan zakat masing-masing karyawan
       total penghasilan di kurangi pajak ,dikurangi biaya hidup minimum, dikurangi nishab, dikalikan persentase zakat (2,5%)
       // update: base zakat adalah sebelum dikurangi pajak
    */
    function initEmployee($strKriteria = "")
    {
        //data kehadiran dan ketidakhadiran
        $this->objAtt = new clsAbsenceReport(
            $this->data, $this->arrData['date_from'], $this->arrData['date_thru']
        ); // cls_absence.php
        $this->objAtt->generateAttendanceReport();
        $this->objAtt->generateAbsenceReport();
        $this->objAtt->generatePartialAbsenceReport();
        $strSQL = "
        SELECT te.id, te.employee_id, te.employee_name, te.id_company,  te.management_code, te.division_code, te.zakat, te.get_jamsostek, te.get_bpjs, te.get_pension, te.get_jshk,
          te.department_code, te.sub_department_code, te.section_code, te.sub_section_code, te.position_code, te.branch_code, te.nationality, te.id_company,
          te.employee_status, te.gender, te.functional_code, te.grade_code, tg.ot_platform, tg.ot_platform_amount, te.npwp,
          te.family_status_code, te.tax_status_code, te.join_date, te.due_date, te.active, tp.position_group, tp.get_ot, tp.ot_limit, tp.ot_meal_fee,
          te.permanent_date, te.resign_date, tm.minimum_living_cost,
          EXTRACT (YEAR FROM AGE('" . $this->arrData['salary_finish_date'] . "', join_date)) AS work_year
          FROM (
          SELECT * FROM hrd_employee
          WHERE ((join_date, resign_date) OVERLAPS (DATE '" . $this->arrData['salary_start_date'] . "', DATE '" . $this->arrData['salary_finish_date'] . "')
          OR (join_date <= DATE '" . $this->arrData['salary_finish_date'] . "' AND (resign_date is null OR resign_date > '" . $this->arrData['salary_start_date'] . "'))
          $strKriteria)
        ) AS te
        LEFT JOIN hrd_position AS tp ON tp.position_code = te.position_code
        LEFT JOIN hrd_salary_grade AS tg ON tg.grade_code = te.grade_code
        LEFT JOIN hrd_minimum_living_cost AS tm ON tm.code = te.living_cost_code
      "; /*
 LEFT JOIN hrd_branch AS tb ON tb.branch_code = tb.branch_code
        LEFT JOIN hrd_minimum_living_cost AS tm ON tm.code = te.living_cost_code
      ";
 */
        //die($strSQL);
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            if ($row['id'] != "") {
                //assign group untuk subtotal, jika ada department
                if ($row['department_code'] == "" && $row['division_code'] == "") {
                    $row['grouper'] = $row['management_code'];
                } else if ($row['department_code'] == "") {
                    $row['grouper'] = $row['division_code'];
                } else if ($row['sub_department_code'] == "") {
                    $row['grouper'] = $row['department_code'];
                } else if ($row['section_code'] == "") {
                    $row['grouper'] = $row['sub_department_code'];
                } else {
                    $row['grouper'] = $row['section_code'];
                }
                $this->arrEmployee[$row['id']] = $row;
            }
        };
    }

    /* calculateJamsostek : fungsi untuk menghitung jamsostek masing-masing karyawan
    */
    function initGeneralSetting()
    {
        $this->arrConf = [];
        $strSQL = "SELECT * FROM all_setting WHERE template_name IS NULL";
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $this->arrConf[$row['code']] = $row['value'];
        }
    }

    /* calculateSalaryTotal : menghitung total gaji per karyawan,
        berdasar data gaji pokok, tunjangan dan potongan yang sudah ada
    */
    function initLeaveEmployee($strKriteria = "")
    {
        /*
        $tblHrdLeaveAllowanceBase = new cHrdLeaveAllowanceBase();
        $arrHrdLeaveAllowanceBase = $tblHrdLeaveAllowanceBase->findAll("id_employee IN (SELECT id FROM hrd_employee WHERE id_company = ".$this->arrData['id_company']." ", "id_employee, cut_off_date, cut_off_counter, EXTRACT(YEAR FROM cut_off_date) AS cut_off_year", "", null, 1, "id_employee");*/
        $strDataDate = $this->arrData['salary_date'];
        //$arrDate = extractDate($strDataDate);
        $strDataDateFrom = $this->arrData['salary_start_date'];
        $strDataDateThru = $this->arrData['salary_finish_date'];
        //$strLeaveMonth = intval(substr($strDataDate,5,2));
        //$strLeaveYear = intval(substr($strDataDate,0,4));
        foreach ($this->arrEmployee AS $strIDEmployee => $arrDetail) {
            $strJoinDate = $arrDetail['join_date'];
            $strEmployeeStatus = $arrDetail['employee_status'];
            $strTempDate = getNextDateNextMonth($strJoinDate, 11); //1 jan 2007
            $strTempYear = substr($strDataDate, 0, 4); //2013
            $strTempDate = $strTempYear . "-" . substr($strTempDate, 5, 5); //2013
            if ((dateCompare($strDataDateFrom, $strTempDate) <= 0 && dateCompare(
                        $strDataDateThru,
                        $strTempDate
                    ) >= 0) && $strTempDate != "" && $strEmployeeStatus == STATUS_PERMANENT
            ) {
                $this->arrLeaveEmployee[$strIDEmployee]['id_employee'] = $strIDEmployee;
                $this->arrLeaveEmployee[$strIDEmployee]['counter'] = 0;
                $this->arrLeaveEmployee[$strIDEmployee]['leave_allowance_date'] = $strDataDate;
                $this->arrLeaveEmployee[$strIDEmployee]['zakat'] = 0;
                $this->arrLeaveEmployee[$strIDEmployee]['tax'] = 0;
            }
        }
    }

    /* saveData : fungsi untuk menyimpan data gaji, baik data master maupun detail
        output : sukses / tidak
    */
    function initMasterAllowance($bolUseLatest = false)
    {
        if ($this->strDataID != "" && !$bolUseLatest) // ambil dari yang sudah ada
        {
            $strSQL = "
          SELECT t1.allowance_code, t1.\"show\", t1.prorate, t2.name, t1.is_default, t1.multival,
            t1.tax, t1.irregular, t1.benefit, t1.hidezero, t1.daily, t1.ot, t1.jams, 't' as active
          FROM hrd_salary_master_allowance AS t1
          LEFT JOIN (select * from hrd_allowance_type order by seq) AS t2 ON t1.allowance_code = t2.code
          WHERE id_salary_master = '" . $this->strDataID . "'
        ";
        } else {
            // ambil fix allowance multival monthly dari all_setting, fix
            $this->arrMATemp = getFixAllowance($this->data);
            foreach ($this->arrMATemp as $strMAKey => $strMAVal) {
                if (isset($this->arrConf[$strMAKey . "_active"]) && $this->arrConf[$strMAKey . "_active"] == 't') {
                    $this->arrMA[$strMAKey] = $this->getFixComponent($strMAKey);
                }
            }
            // ambil other allowance dari hrd_allowance_type
            $strSQL = "
          SELECT code as allowance_code, \"show\", prorate, name, 'f' as multival,'f' as is_default,
            tax, irregular, benefit, hidezero, daily, ot, jams, active, amount
          FROM hrd_allowance_type
          WHERE active = 't'
          order by seq
        ";
        }
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $this->arrMA[$row['allowance_code']] = $row;
        }
        foreach ($this->arrMA AS $strCode => $arrInfo) {
            if ($arrInfo['name'] == "") {
                $this->arrMA[$strCode]['name'] = $this->arrConf[$strCode . "_name"];
            }
            $this->arrMAGrouped[$arrInfo['is_default']][$arrInfo['multival']][$arrInfo['daily']][$strCode] = $arrInfo;
        }
    }

    /* getFixComponent : fungsi untuk melengkapi  arrMA atau arrMD, dengan item2 fix allowance atau deduction
        input  : kode allowance/deduction, $strType : 0 = allowance, 1 = deduction
        output : array
    */
    function initMasterDeduction($bolUseLatest = false)
    {
        if ($this->strDataID != "" && !$bolUseLatest) // ambil dari yang sudah ada
        {
            $strSQL = "
          SELECT t1.deduction_code, t1.\"show\", t1.prorate, t2.name, t1.is_default,
            t1.tax,  t1.hidezero, t1.daily, t1.ot, t1.jams, 't' as active
          FROM hrd_salary_master_deduction AS t1
          LEFT JOIN (select * from hrd_deduction_type order by seq) AS t2 ON t1.deduction_code = t2.code
          WHERE id_salary_master = '" . $this->strDataID . "'
        ";
        } else {
            if (isset($this->arrConf['loan_deduction_active']) && $this->arrConf['loan_deduction_active'] == 't') {
                $this->arrMD['loan_deduction'] = $this->getFixComponent("loan_deduction");
            }
            if (isset($this->arrConf['zakat_deduction_active']) && $this->arrConf['zakat_deduction_active'] == 't') {
                $this->arrMD['zakat_deduction'] = $this->getFixComponent("zakat_deduction");
            }
            if (isset($this->arrConf['jamsostek_deduction_active']) && $this->arrConf['jamsostek_deduction_active'] == 't') {
                $this->arrMD['jamsostek_deduction'] = $this->getFixComponent("jamsostek_deduction");
            }
            if (isset($this->arrConf['bpjs_deduction_active']) && $this->arrConf['bpjs_deduction_active'] == 't') {
                $this->arrMD['bpjs_deduction'] = $this->getFixComponent("bpjs_deduction");
            }
            if (isset($this->arrConf['pension_deduction_active']) && $this->arrConf['pension_deduction_active'] == 't') {
                $this->arrMD['pension_deduction'] = $this->getFixComponent("pension_deduction");
            }
            if (isset($this->arrConf['jshk_deduction_active']) && $this->arrConf['jshk_deduction_active'] == 't') {
                $this->arrMD['jshk_deduction'] = $this->getFixComponent("jshk_deduction");
            }
            if (isset($this->arrConf['absence_deduction_active']) && $this->arrConf['absence_deduction_active'] == 't') {
                $this->arrMD['absence_deduction'] = $this->getFixComponent("absence_deduction");
            }
            if (isset($this->arrConf['late_deduction_active']) && $this->arrConf['late_deduction_active'] == 't') {
                $this->arrMD['late_deduction'] = $this->getFixComponent("late_deduction");
            }
            // ambil dari master deduction
            $strSQL = "
          SELECT code as deduction_code, \"show\", prorate,  name,
            'f' as is_default, tax, hidezero, daily, ot, jams, active
          FROM hrd_deduction_type
          WHERE active = 't' order by seq
        ";
        }
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $this->arrMD[$row['deduction_code']] = $row;
        }
        foreach ($this->arrMD AS $strCode => $arrInfo) {
            if ($arrInfo['name'] == "") {
                $this->arrMD[$strCode]['name'] = $this->arrConf[$strCode . "_name"];
            }
        }
    }

    /* generateMasterAllowanceSQL : fungsi untuk membuat query master allowance, khusus yang sifatnya tetap
        informasi active dan sebagainya diambil dari general setting
        input  : kode allowance, array data (jika diambil dari tabel  - khusus untuk tunjangan tambahan)
        output : sintaks SQL
    */
    function initSalaryDetail()
    {
        $arrBlankDet = [ // array kosong untu inisialisasi data detail
                         "id"                        => "",
                         "id_salary_master"          => $this->strDataID,
                         "id_employee"               => "",
                         "employee_id"               => "",
                         "npwp"                      => "",
                         "functional_code"           => "",
                         "join_date"                 => null,
                         "resign_date"               => null,
                         "position_code"             => "",
                         "branch_code"               => "",
                         "grade_code"                => "",
                         "grouper"                   => "",
                         "division_code"             => "",
                         "department_code"           => "",
                         "sub_department_code"       => "",
                         "section_code"              => "",
                         "sub_section_code"          => "",
                         "employee_status"           => 0,
                         "family_status_code"        => "",
                         "tax_status_code"           => "",
                         "group_code"                => "",
                         "actual_basic_salary"       => 0,
                         // gaji pokok yang sebenarnya
                         "basic_salary"              => 0,
                         // gaji pokok yang diberikan, sudah dikenakan prorata jika ada
                         "working_day"               => 0,
                         "attendance_day"            => 0,
                         // total kehadiran
                         "paid_absence_day"          => 0,
                         // total absen yang dibayar
                         "unpaid_absence_day"        => 0,
                         // total absen yang tidak dibayar
                         "leave_day"                 => 0,
                         // total cuti (tidak dibayar)
                         "out_day"                   => 0,
                         // total hari yang tidak dianggap - dalam kasus karyawan baru masuk atau resign
                         "late_min"                  => 0,
                         // jumlah keterlambatan, dalam menit
                         "early_min"                 => 0,
                         // jumlah pulang awal, dalam menit
                         "late_round"                => 0,
                         // jumlah keterlambatan, dalam menit
                         "early_round"               => 0,
                         // jumlah pulang awal, dalam menit
                         "late_day"                  => 0,
                         // jumlah keterlambatan, dalam hari
                         "early_day"                 => 0,
                         // jumlah pulang awal, dalam hari
                         "ot_day"                    => 0,
                         // total hari kerja yang termasuk lembur
                         "ot1_min"                   => 0,
                         // total (dalam menit) lembur 1 (dikali 1.5)
                         "ot2_min"                   => 0,
                         // total (dalam menit) lembur 2 (dikali 2)
                         "ot2b_min"                  => 0,
                         // total (dalam menit) lembur 2 (dikali 2)
                         "ot3_min"                   => 0,
                         // total (dalam menit) lembur 3 (dikali 3)
                         "ot4_min"                   => 0,
                         // total (dalam menit) lembur 4 (dikali 4)
                         "ot1"                       => 0,
                         // total tunjangan lembur (dalam rupiah) untuk lembur 1
                         "ot2"                       => 0,
                         // total tunjangan lembur (dalam rupiah) untuk lembur 2
                         "ot2b"                      => 0,
                         // total tunjangan lembur (dalam rupiah) untuk lembur 2
                         "ot3"                       => 0,
                         // total tunjangan lembur (dalam rupiah) untuk lembur 3
                         "ot4"                       => 0,
                         // total tunjangan lembur (dalam rupiah) untuk lembur 4
                         "ot_per_hour"               => 0,
                         // nilai gaji per jam, untuk perhitungan ot
                         "base_ot"                   => 0,
                         // total gaji/pendapatan yang menjadi dasar perhitungan lembur
                         "shift_day"                 => 0,
                         // jumlah hari melakukan shift
                         "shift_hour"                => 0,
                         // jumlah durasi shift
                         "base_tax"                  => 0,
                         // total PKP - pendapatan kena pajak
                         "tax_reduction"             => 0,
                         // total PTKP - pendapatan tidak kena pajak
                         "tax_allowance"             => 0,
                         // total tunjangan pajak
                         "tax"                       => 0,
                         // total pajak
                         "base_irregular_tax"        => 0,
                         // total pajak
                         "irregular_tax"             => 0,
                         // total pajak
                         "base_jamsostek"            => 0,
                         // total gaji untuk dasar jamsostek
                         "zakat_deduction_irregular" => 0,
                         // zakat dibayar oleh karyawan
                         "total_deduction"           => 0,
                         // total potongan
                         "total_net"                 => 0,
                         // total pendapatan
                         "total_gross"               => 0,
                         // total pendapatan - total potongan (gaji yang diterima karyawan)
                         "total_gross_irregular"     => 0,
                         // total pendapatan - total potongan (gaji yang diterima karyawan)
                         "total_net_irregular"       => 0,
                         // total pendapatan - total potongan (gaji yang diterima karyawan)
                         "total_gross_round"         => 0,
                         // total gaji (THP) setelah dibulatkan
                         "benefit"                   => 0,
                         "overtime_allowance"        => 0,
                         // total tunjangan lembur yang diterima. bisa dimanfaatkan untuk karyawan staff yang tunjangan lemburnya all-in
                         "shift_allowance"           => 0,
                         // total tunjangan shift
                         //"attendance_allowance"  => 0, // tunjangan kehadiran
                         "jkk_allowance"             => 0,
                         // jamsostek dibayar oleh perusahaan
                         "jkm_allowance"             => 0,
                         // jamsostek dibayar oleh perusahaan
                         "jamsostek_allowance"       => 0,
                         // jamsostek dibayar oleh perusahaan
                         //"position_allowance"          => 0,
                         "transport_allowance"       => 0,
                         // tunjangan transport
                         "meal_allowance"            => 0,
                         // tunjangan makan
                         //"vehicle_allowance" => 0, // tunjangan kendaraan
                         "jamsostek_deduction"       => 0,
                         // jamsostek dibayar oleh karyawan
                         //"zakat_deduction"   => 0, // zakat dibayar oleh karyawan
                         "loan_deduction"            => 0,
                         // cicilan pinjaman yang dipotong langsung
                         "absence_deduction"         => 0
        ]; // potongan akibat keterlambatan/pulang cepat
        // ambil dulu data salary detail, salary allowance detail dan salary deduction detail
        if ($this->strDataID != "") {
            $strSQL = "SELECT * FROM hrd_salary_detail WHERE id_salary_master = '" . $this->strDataID . "' ";
            $res = $this->data->execute($strSQL);
            while ($row = $this->data->fetchrow($res)) {
                $this->arrDetail[$row['id_employee']] = $row;
                $this->arrDetailEmployee[$row['id']] = $row['id_employee'];
                /* PENAMBAHAN total unpaid absence */
                $this->arrDetail[$row['id_employee']]['unpaid_absence_day'] = $row['attendance_day'];
                /* END PENAMBAHAN */
            }
            /* coba membetulkan Recalculate */
            $this->arrDetail = $this->fixBaseJamsostek($this->arrDetail);
            /* end pembetulan */
            $strSQL = "SELECT * FROM hrd_salary_allowance WHERE id_salary_master = '" . $this->strDataID . "' ";
            $res = $this->data->execute($strSQL);
            while ($row = $this->data->fetchrow($res)) {
                $this->arrDA[$row['allowance_code']][$row['id_employee']] = $row;
            }
            $strSQL = "SELECT * FROM hrd_salary_deduction WHERE id_salary_master = '" . $this->strDataID . "' ";
            $res = $this->data->execute($strSQL);
            while ($row = $this->data->fetchrow($res)) {
                $this->arrDD[$row['deduction_code']][$row['id_employee']] = $row;
            }
        } else {
            // loop per karyawan, sesuai data di arrEmployee
            foreach ($this->arrEmployee AS $intID => $rowEmp) {
                $this->arrDetail[$intID] = $arrBlankDet;
                // isi data default dengan informasi karyawan
                $this->arrDetail[$intID]['id_employee'] = $intID;
                $this->arrDetail[$intID]['employee_id'] = $rowEmp['employee_id'];
                $this->arrDetail[$intID]['employee_name'] = $rowEmp['employee_name'];
                $this->arrDetail[$intID]['id_company'] = $rowEmp['id_company'];
                $this->arrDetail[$intID]['npwp'] = $rowEmp['npwp'];
                $this->arrDetail[$intID]['join_date'] = $rowEmp['join_date'];
                $this->arrDetail[$intID]['resign_date'] = $rowEmp['resign_date'];
                $this->arrDetail[$intID]['functional_code'] = $rowEmp['functional_code'];
                $this->arrDetail[$intID]['position_code'] = $rowEmp['position_code'];
                $this->arrDetail[$intID]['branch_code'] = $rowEmp['branch_code'];
                $this->arrDetail[$intID]['grade_code'] = $rowEmp['grade_code'];
                $this->arrDetail[$intID]['grouper'] = $rowEmp['grouper'];
                $this->arrDetail[$intID]['division_code'] = $rowEmp['division_code'];
                $this->arrDetail[$intID]['department_code'] = $rowEmp['department_code'];
                $this->arrDetail[$intID]['sub_department_code'] = $rowEmp['sub_department_code'];
                $this->arrDetail[$intID]['section_code'] = $rowEmp['section_code'];
                $this->arrDetail[$intID]['sub_section_code'] = $rowEmp['sub_section_code'];
                $this->arrDetail[$intID]['employee_status'] = $rowEmp['employee_status'];
                $this->arrDetail[$intID]['family_status_code'] = $rowEmp['family_status_code'];
                $this->arrDetail[$intID]['tax_status_code'] = $rowEmp['tax_status_code'];
                //isi data kehadiran
                $this->arrDetail[$intID]['working_day'] = $this->objAtt->getData($intID, "total_workday");
                $this->arrDetail[$intID]['attendance_day'] = $this->objAtt->getData($intID, "total_attendance");
                //$this->arrDetail[$intID]['paid_absence_day'] = $this->objAtt->getData($intID, "total_absence");
                $this->arrDetail[$intID]['paid_absence_day'] = $this->objAtt->getData($intID, "total_absence_dl");
                $this->arrDetail[$intID]['unpaid_absence_day'] = $this->objAtt->getData($intID, "total_unpaid_absence");
                $this->arrDetail[$intID]['leave_day'] = $this->objAtt->getData($intID, "total_leave");
                $this->arrDetail[$intID]['late_day'] = $this->objAtt->getData($intID, "total_late");
                $this->arrDetail[$intID]['early_day'] = $this->objAtt->getData($intID, "total_early");
                $this->arrDetail[$intID]['late_round'] = $this->objAtt->getData($intID, "total_late_round");
                $this->arrDetail[$intID]['early_round'] = $this->objAtt->getData($intID, "total_early_round");
                $this->arrDetail[$intID]['late_min'] = $this->objAtt->getData($intID, "total_late_min");
                $this->arrDetail[$intID]['early_min'] = $this->objAtt->getData($intID, "total_early_min");
                $this->arrDetail[$intID]['shift_hour'] = $this->objAtt->getDataShiftHour($intID);
                /*$employeeID = $this->arrDetail[$intID]['employee_id'];
                if ($employeeID == 'A0001' || $employeeID == 'A0002' || $employeeID == 'A0003' ){
                    echo "Employee ID >>".$this->arrDetail[$intID]['employee_id']." Working Day : ".$this->arrDetail[$intID]['working_day']." Attendance Day : ".$this->arrDetail[$intID]['attendance_day']." Late Day : ".$this->arrDetail[$intID]['late_day']." Early Day : ".$this->arrDetail[$intID]['early_day']." Absence Day : ".$this->arrDetail[$intID]['total_absence']." Unpaid Absence Day : ".$this->arrDetail[$intID]['total_unpaid_absence'];
                    echo "<br/>";
                }*/
            }//exit();
        }
    }

    /* generateMasterDeductionSQL : fungsi untuk membuat query master deduction, khusus yang sifatnya tetap
        informasi active dan sebagainya diambil dari general setting
        input  : kode deduction, array data (jika diambil dari tabel  - khusus untuk potongan tambahan)
        output : sintaks SQL
    */
    function initSalaryMaster($strDataDate, $strDateFrom = "", $strDateThru = "")
    {
        // iniisialisasi dulu
        $this->arrData = [
            "id"                 => $this->strDataID,
            "date_from"          => $strDateFrom,
            // dipakai untuk acuan ot dan uang makan/transport
            "date_thru"          => $strDateThru,
            "date_from_thr"      => "",
            // dipakai untuk acuan perhitungan masa kerja karyawan terkait dengan thr
            "date_thru_thr"      => "",
            "id_company"         => "",
            // Company yang bersangkutan
            //"salary_currency" => "",         // Company yang bersangkutan
            "id_salary_set"      => "",
            // Hanya menghitung irregular salary?
            "salary_date"        => $strDataDate,
            // tanggal perhitungan gaji -- aktual dihitung gaji tanggal berapa, misal 2008-12-25
            "salary_start_date"  => "",
            // tanggal pertama untuk periode perhitungan gaji. misal 2008-12-01
            "salary_finish_date" => "",
            // tanggal akhir untuk periode perhitungan gaji. misal 2008-12-31
            "meal_duration"      => "",
            // durasi kerja date_from - date_thru
            "hide_blank"         => "t",
            "note"               => "",
            "status"             => 0,
        ];
        if ($this->strDataID != "") {
            $strSQL = "SELECT * FROM hrd_salary_master WHERE id = '" . $this->strDataID . "' ";
            $res = $this->data->execute($strSQL);
            if ($row = $this->data->fetchrow($res)) {
                $this->arrData['date_from'] = $row['date_from'];
                $this->arrData['date_thru'] = $row['date_thru'];
                $this->arrData['salary_date'] = $row['salary_date'];
                $arrDt = explode("-", $this->arrData['salary_date']);
                $this->arrData['salary_start_date'] = $arrDt[0] . "-" . ($arrDt[1]) . "-01";
                $this->arrData['salary_finish_date'] = $arrDt[0] . "-" . $arrDt[1] . "-" . lastday($arrDt[1], $arrDt[0]);
                $this->arrData['date_from_thr'] = $row['date_from_thr'];
                $this->arrData['date_thru_thr'] = $row['date_thru_thr'];
                $this->arrData['id_company'] = $row['id_company'];
                //$this->arrData['salary_currency'] = $row['salary_currency'];
                $this->arrData['id_salary_set'] = $row['id_salary_set'];
                $this->arrData['hide_blank'] = $row['hide_blank'];
                $this->arrData['note'] = $row['note'];
                $this->irregular = $row['irregular'];
                $this->arrData['status'] = $row['status'];
                $this->getStandardWorkingDay();
            } else {
                $this->arrData['id'] = $this->strDataID = "";
            } // anggap gak ada
        }
        $this->getSalaryPeriodDate();
    }

    /**
     * Function to check whether salary last month is paid full or not.
     *
     * @param $strID
     *
     * @return bool
     */
    function isPaidLastMonth($strID)
    {
        $strSalaryDate = getNextDateNextMonth($this->arrData['salary_date'], -1);
        $arrDt = explode("-", $strSalaryDate);
        $strSalaryMonth = (int)$arrDt[1];
        $strSalaryYear = $arrDt[0];
        $strSQL = "SELECT count(*) as total_data FROM hrd_salary_detail AS t1 WHERE id_salary_master IN (SELECT id FROM hrd_salary_master AS t2 WHERE status >=2 AND EXTRACT(MONTH FROM salary_date) = $strSalaryMonth
                  AND EXTRACT(YEAR FROM salary_date) = $strSalaryYear) AND id_employee = $strID;";
        /*echo "Salary Month : ".$strSalaryMonth." Salary Year : ".$strSalaryYear."<br/>";
        echo $strSQL;
        die();*/
        $resDb = $this->data->execute($strSQL);
        while ($rowDb = $this->data->fetchrow($resDb)) {
            $intTotalData = $rowDb['total_data'];
        }
        if ($intTotalData > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function to check whether salary last month is paid prorate or not.
     *
     * @param $strID
     *
     * @return bool
     */
    function isPaidProratedLastMonth($strID)
    {
        $strSalaryDate = getNextDateNextMonth($this->arrData['salary_date'], -1);
        $arrDt = explode("-", $strSalaryDate);
        $strSalaryMonth = (int)$arrDt[1];
        $strSalaryYear = $arrDt[0];
        $strSQL = "SELECT count(*) as total_data FROM hrd_salary_detail AS t1 WHERE id_salary_master IN (SELECT id FROM hrd_salary_master AS t2 WHERE status >=2 AND EXTRACT(MONTH FROM salary_date) = $strSalaryMonth
                  AND EXTRACT(YEAR FROM salary_date) = $strSalaryYear) AND id_employee = $strID AND prorate <> 1;";
        $resDb = $this->data->execute($strSQL);
        while ($rowDb = $this->data->fetchrow($resDb)) {
            $intTotalData = $rowDb['total_data'];
        }
        if ($intTotalData > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function to check whether en employee is resign last month or not.
     *
     * @param $strID
     *
     * @return bool
     */
    function isResignLastMonth($strID)
    {
        $strSalaryDate = getNextDateNextMonth($this->arrData['salary_date'], -1);
        $arrDt = explode("-", $strSalaryDate);
        $strSalaryMonth = (int)$arrDt[1];
        $strSalaryYear = $arrDt[0];
        $strSQL = "SELECT count(*) as total_data FROM hrd_employee WHERE EXTRACT(MONTH FROM resign_date) = $strSalaryMonth
                  AND EXTRACT(YEAR FROM resign_date) = $strSalaryYear AND id = $strID;";
        $resDb = $this->data->execute($strSQL);
        while ($rowDb = $this->data->fetchrow($resDb)) {
            $intTotalData = $rowDb['total_data'];
        }
        if ($intTotalData > 0) {
            return true;
        } else {
            return false;
        }
    }

    /* getEmployeeSalaryDetail : fungsi mengambil data salary detail karyawan tertentu, berdasarkan id dan kode detail (field)
           asumsi perhitungan gaji sudah dilakukan
        input : id karyawan, field yang ada dalam salary detail
        output: nilai data yang ada di salary detail, sesuai field yang diinginkan
    */
    function saveData()
    {
        $bolOK = true;
        // proses save data
        $this->data->execute("begin");
        $strUserID = $_SESSION['sessionUserID'];
        // save data master dulu
        if ($this->strDataID == "") // insert new
        {
            $this->strDataID = $this->data->getNextID("hrd_salary_master_id_seq");
            $this->arrData['dataID'] = $this->strDataID;
            $strTHRDateFrom = (validStandardDate(
                    $this->arrData['date_from_thr']
                ) && $this->arrData['date_thru_thr'] != "") ? "'" . $this->arrData['date_from_thr'] . "'" : "NULL";
            $strTHRDateThru = (validStandardDate(
                    $this->arrData['date_thru_thr']
                ) && $this->arrData['date_thru_thr'] != "") ? "'" . $this->arrData['date_thru_thr'] . "'" : "NULL";
            $strSQL = "
          INSERT INTO hrd_salary_master (
            id, date_from, date_thru, date_from_thr, date_thru_thr, salary_date, status, id_company, id_salary_set, irregular, hide_blank, note,
            created, created_by, modified, modified_by, flag
          )
          VALUES (
            '" . $this->strDataID . "', '" . $this->arrData['date_from'] . "', '" . $this->arrData['date_thru'] . "', " . $strTHRDateFrom . ",  " . $strTHRDateThru . ", '" . $this->arrData['salary_date'] . "', 0, " . $this->arrData['id_company'] . ",
            '" . $this->arrData['id_salary_set'] . "', '" . $this->irregular . "', '" . $this->arrData['hide_blank'] . "', '" . $this->arrData['note'] . "',
            now(), '$strUserID', now(), '$strUserID', '" . $this->arrData['flag'] . "'
          );
        ";
        } else {
            $strSQL = "
          UPDATE hrd_salary_master
          SET modified = now(), modified_by = '$strUserID'
          WHERE id = '" . $this->strDataID . "';
        ";
        }
        $resExec = $this->data->execute($strSQL);
        if ($resExec == false) {
            //die(strSQL);
            $bolOK = false;
        }
        // save data master allowance
        if ($bolOK) {
            // hapus dulu yang lama
            $strSQL = "
          DELETE FROM hrd_salary_master_allowance WHERE id_salary_master = '" . $this->strDataID . "';
        ";
            $resExec = $this->data->execute($strSQL);
            // insert untuk yang tunj tetap dulu
            /*$strSQL  = $this->generateMasterAllowanceSQL("position_allowance");
            $strSQL .= $this->generateMasterAllowanceSQL("transport_allowance");
            $strSQL .= $this->generateMasterAllowanceSQL("vehicle_allowance");
            $strSQL .= $this->generateMasterAllowanceSQL("meal_allowance");
            $strSQL .= $this->generateMasterAllowanceSQL("overtime_allowance");
            $strSQL .= $this->generateMasterAllowanceSQL("jkk_allowance");
            $strSQL .= $this->generateMasterAllowanceSQL("jkm_allowance");
            $strSQL .= $this->generateMasterAllowanceSQL("jamsostek_allowance");
            $resExec = $this->data->execute($strSQL);
            if ($resExec == false) $bolOK = false;*/
            $strSQL = "";
            foreach ($this->arrMA AS $strCode => $arrM) {
                //if ($arrM['is_default'] == 'f') // hanya yang tambahan
                $strSQL .= $this->generateMasterAllowanceSQL($strCode, $arrM);
            }
            if ($strSQL != "") {
                $resExec = $this->data->execute($strSQL);
                if ($resExec == false) {
                    $bolOK = false;
                }
            }
        }
        // save data master deduction
        if ($bolOK) {
            // hapus dulu yang lama
            $strSQL = "
          DELETE FROM hrd_salary_master_deduction WHERE id_salary_master = '" . $this->strDataID . "';
        ";
            $resExec = $this->data->execute($strSQL);
            // insert untuk yang tunj tetap dulu
            /*$strSQL  = $this->generateMasterDeductionSQL("loan_deduction");
            $strSQL .= $this->generateMasterDeductionSQL("zakat_deduction");
            $strSQL .= $this->generateMasterDeductionSQL("jamsostek_deduction");
            $resExec = $this->data->execute($strSQL);
            if ($resExec == false) $bolOK = false;*/
            $strSQL = "";
            foreach ($this->arrMD AS $strCode => $arrM) {
                //if ($arrM['is_default'] == 'f') // hanya yang tambahan
                $strSQL .= $this->generateMasterDeductionSQL($strCode, $arrM);
            }
            if ($strSQL != "") {
                $resExec = $this->data->execute($strSQL);
                if ($resExec == false) {
                    $bolOK = false;
                }
            }
        }
        // save data detail salary
        // ambil data terlebih dahulu
        $this->calculateBasic($this->strKriteria);
        if (isset($this->arrConf['thr_allowance_active']) && $this->arrConf['thr_allowance_active'] == 't') {
            $this->calculateTHR();
        }
        // if(false)
        // //if (isset($this->arrConf['CUTI']) && $this->arrConf['CUTI_active'] == 't')
        // {
        //   $this->initLeaveEmployee($this->strKriteria);
        //   $this->calculateLeaveAllowance();
        // }
        if (isset($this->arrConf['overtime_allowance_active']) && $this->arrConf['overtime_allowance_active'] == 't') {
            $this->calculateOvertime();
        }
        $this->calculateDeduction($this->strKriteria);
        if (isset($this->arrConf['late_deduction_active']) && $this->arrConf['late_deduction_active'] == 't') {
            //$this->calculateLateDeduction();
            $this->calculateLateDeductionByFunctional();
        }
        if (isset($this->arrConf['absence_deduction_active']) && $this->arrConf['absence_deduction_active'] == 't') {
            $this->calculateAbsenceDeduction();
        }
        $this->calculateJamsostek();
        $this->calculateSalaryTotal();
        if (isset($this->arrConf['zakat_deduction_active']) && $this->arrConf['zakat_deduction_active'] == 't') {
            $this->calculateZakat();
        }
        $this->calculateTax();
        if ($bolOK) {
            $strSQL = "
          DELETE FROM hrd_salary_detail WHERE id_salary_master = '" . $this->strDataID . "';
          DELETE FROM hrd_salary_allowance WHERE id_salary_master = '" . $this->strDataID . "';
          DELETE FROM hrd_salary_deduction WHERE id_salary_master = '" . $this->strDataID . "';
        ";
            $resExec = $this->data->execute($strSQL);
            if ($resExec == false) {
                $bolOK = false;
            }
            foreach ($this->arrDetail AS $strID => $arrInfo) {
                // detail salary
                $strFields = $strValues = "";
                foreach ($arrInfo AS $strField => $strValue) {
                    if ($strField != "id") {
                        if ($strFields != "") {
                            $strFields .= ", ";
                        }
                        if ($strValues != "") {
                            $strValues .= ", ";
                        }
                        $strFields .= $strField;
                        if ($strField == "id_salary_master") {
                            $strValues .= "'" . $this->strDataID . "'";
                        } else {
                            $strValues .= ($strValue == "") ? "NULL" : "'$strValue'";
                        }
                    }
                }
                $strSQL = "
            INSERT INTO hrd_salary_detail ($strFields)
            VALUES ($strValues);
          ";
                // allowance
                foreach ($this->arrMA AS $strCode => $arrM) {
                    if (isset($this->arrDA[$strCode][$strID])) {
                        $fltAmount = ($this->arrDA[$strCode][$strID]['amount'] == "") ? 0 : $this->arrDA[$strCode][$strID]['amount'];
                        $strSQL .= "
                INSERT INTO hrd_salary_allowance (
                  id_salary_master, allowance_code, id_employee, amount
                ) VALUES (
                  '" . $this->strDataID . "', '$strCode', '$strID', '$fltAmount'
                );
              ";
                    }
                }
                // deduction
                foreach ($this->arrMD AS $strCode => $arrM) {
                    if (isset($this->arrDD[$strCode][$strID])) {
                        $fltAmount = ($this->arrDD[$strCode][$strID]['amount'] == "") ? 0 : $this->arrDD[$strCode][$strID]['amount'];
                        $strSQL .= "
                INSERT INTO hrd_salary_deduction (
                  id_salary_master, deduction_code, id_employee, amount
                ) VALUES (
                  '" . $this->strDataID . "', '$strCode', '$strID', '$fltAmount'
                );
              ";
                    }
                }
                $resExec = $this->data->execute($strSQL);
                if ($resExec == false) {
                    //die($strSQL);
                    $bolOK = false;
                }
            }
        }
        $this->data->execute("commit");
        // die('a');
        if (false) //if ($bolOK)
        {
            $this->data->execute("commit");
            if (isset($this->arrConf['leave_allowance_active']) && $this->arrConf['leave_allowance_active'] == 't') {
                $tblLeaveAllowance = new cHrdLeaveAllowance();
                $tblLeaveAllowance->delete("id_salary_master = " . $this->strDataID);
                foreach ($this->arrLeaveEmployee AS $strIDEmployee => $arrLeaveDetail) {
                    $arrLeaveDetail['id_salary_master'] = $this->strDataID;
                    $arrLeaveDetail['zakat'] = $this->arrDetail[$strIDEmployee]['zakat_deduction_irregular'];
                    $arrLeaveDetail['tax'] = $this->arrDetail[$strIDEmployee]['irregular_tax'];
                    $tblLeaveAllowance->insert($arrLeaveDetail);
                }
            }
        } else {
            $this->data->execute("rollback");
        }
        return $bolOK;
    }

    /* getEmployeeAllowanceDetail : fungsi mengambil nilai tunjangan detail karyawan tertentu (tunj lain-lain),
          berdasarkan id dan kode detail (field), asumsi perhitungan gaji sudah dilakukan
        input : id karyawan, kode tunjangan
        output: nilai tunjangan karyawan, sesuai kode yang diinginkan
    */
    function setApproved()
    {
        if ($this->strDataID != "") {
            $intStatus = SALARY_CALCULATION_APPROVED;
            $strSQL = "
          UPDATE hrd_salary_master SET status = '" . $intStatus . "'
          WHERE id = '" . $this->strDataID . "';
        ";
            $resExec = $this->data->execute($strSQL);
            if ($resExec != false) {
                $this->arrData['status'] = $intStatus;
            }
        }
    }

    /* getEmployeeDeductionDetail : fungsi mengambil nilai potongan detail karyawan tertentu (tunj lain-lain),
          berdasarkan id dan kode detail (field), asumsi perhitungan gaji sudah dilakukan
        input : id karyawan, kode potongan
        output: nilai potongan karyawan, sesuai kode yang diinginkan
    */
    function setFinish()
    {
        if ($this->strDataID != "") {
            $intStatus = SALARY_CALCULATION_FINISH;
            $strSQL = "
          UPDATE hrd_salary_master SET status = '" . $intStatus . "'
          WHERE id = '" . $this->strDataID . "';
        ";
            $resExec = $this->data->execute($strSQL);
            if ($resExec != false) {
                $this->arrData['status'] = $intStatus;
            }
        }
    }

    /* getBaseTaxPayedTaxBefore : fungsi untuk mengambil nilai base_tax bulan sebelum bulan
           kalkulasi salary*/
    function setSalaryDate(
        $strSalaryDate,
        $strDateFrom,
        $strDateTo,
        $strTHRDateFrom,
        $strTHRDateTo,
        $strCompany,
        $strSalarySet,
        $bolHideBlank,
        $strNote = "",
        $intFlag
    ) {
        $this->arrData['salary_date'] = $strSalaryDate;
        $this->arrData['date_from'] = $strDateFrom;
        $this->arrData['date_thru'] = $strDateTo;
        $this->arrData['date_from_thr'] = $strTHRDateFrom;
        $this->arrData['date_thru_thr'] = $strTHRDateTo;
        $this->arrData['id_company'] = $strCompany;
        //$this->arrData['salary_currency']  = $strCurrency;
        $this->arrData['hide_blank'] = ($bolHideBlank) ? "t" : "f";
        $this->arrData['flag'] = $intFlag;
        $this->arrData['note'] = ($strNote == getWords("(note)")) ? "" : $strNote;
        $this->getSalaryPeriodDate();
        $this->getStandardWorkingDay();
        $this->arrData['id_salary_set'] = $strSalarySet;
    }
    /* End getArrayDetailBaseTaxPayedTaxBefore */
}

?>
