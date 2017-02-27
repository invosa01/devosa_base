<?php
// ---- variabel untuk PPH21
/* clsTaxCalculation : kelas untuk mengelola perhitungan Pph21, per karyawan
*/
//Include cls_pph21_calculation
include_once("cls_pph21_calculation.php");

/* End new pph21 calculation */

class clsTaxCalculation
{

        var $arrEmpBaseTaxPaidTaxBefore; //kelas database

    var $arrPTKP; // daftar PTKP sesuai status keluarga

    var $bolNPWP; // nilai pendapatan kena pajak

    var $data; // tunj. jabatan

    var $fltMaxPosition = 500000; //apakah punya NPWP atau tidak

    var $fltPKP = 0; // periode kerja karyawan, dalam bulan, max 12 bulan

    var $fltPosition = 0; // kode status keluarga

    // atribut konstanta

var $intMethod = 1; // 0 : gross, 1 : gross up

    var $intPeriod; // maximum tunj. jabatan  - konstanta

var $strFamilyStatus;

    var $strIDEmployee = "";

    // konstruktor

    function clsTaxCalculation($db)
    {
        $this->data = $db;
        // inisialisasi
        $this->arrPTKP = [];
        $this->fltPosition = 0;
        $this->bolNPWP = false;
        $this->intPeriod = 12;
        $this->strFamilyStatus = "";
        $this->fltJamostekDeduction = 0;
        $this->initTaxReduction();
        $this->baseTaxBefore = 0;
        $this->taxBefore = 0;
        $this->baseIrrTaxBefore = 0;
        $this->intTaxMonth = 1;
        $this->strIDEmployee = "";
        $this->arrEmpBaseTaxPaidTaxBefore = [];
    }

    /* initTaxReduction_Old : fungsi untuk mengambil daftar PTKP (private)
        disimpan di atribut
    */

    function calculateBaseTaxBefore()
    {
        $this->intTaxMonth = 0;
        $this->baseTaxBefore = 0;
        $this->baseIrrTaxBefore = 0;
        $this->taxBefore = 0;
        if (isset($this->arrEmpBaseTaxPaidTaxBefore)) {
            $this->baseTaxBefore = $this->arrEmpBaseTaxPaidTaxBefore['base_tax'];
            $this->baseIrrTaxBefore = $this->arrEmpBaseTaxPaidTaxBefore['baseIrregular_tax'];
            $this->taxBefore = $this->arrEmpBaseTaxPaidTaxBefore['tax'];
            $this->intTaxMonth = $this->arrEmpBaseTaxPaidTaxBefore['total_month'];
        }
    }

    /* initTaxReduction : fungsi untuk mengambil daftar PTKP (private)
    disimpan di atribut, perbaikan oleh Heriyanto menggunakan tabel hrd_family_status_tax
*/

    function calculateBaseTaxNet($fltBasic, $fltJamsostekDeduction)
    {
        $fltJabatan = ($fltBasic + $fltJamsostekDeduction) * (5 / 100); // tunjangan jabatan untuk potongan pendapatan tidak kena pajak
        if ($fltJabatan > $this->fltMaxPosition) {
            $fltJabatan = $this->fltMaxPosition;
        }
        return ($fltBasic - $fltJabatan);
    }

    /* getTaxReduction : mengambil nilai PTKP berdasar status keluarga
        input  : status keluarga
        output : nilai PTKP, ambil dari atribut
    */

    function calculateIrregularPph21Gross(
        $fltBasic,
        $fltPIKP,
        $fltPTKP,
        $bolNPWP,
        $intMonth = 12,
        $fltJamsostekDeduction,
        $strIDEmployee
    ) {
        $countpph21 = new countPPH21($intMonth, $this->arrPTKP);
        $arrSalaryLimit2008 = [25000000, 50000000, 100000000, 200000000, 200000000]; // versi tahun 2008 ke bawah
        $arrTaxRate2008 = [0.05, 0.10, 0.15, 0.25, 0.35]; // versi tahun 2008 ke bawah
        $arrSalaryLimit = [50000000, 250000000, 500000000, 50000000000]; // versi 2009, yang punya NPWP
        $arrTaxRate = [0.05, 0.15, 0.25, 0.30]; // versi 2009 yang punya NPWP
        //$arrSalaryLimitNo   = array( 0, 50000000, 250000000, 500000000); // versi 2009 tanpa NPWP
        $arrTaxRateNo = [0.06, 0.18, 0.30, 0.36]; // versi 2009 tanpa NPWP
        //$fltMinBasic = 1300000; // minimal gaji yang dikenai pajak
        //$fltMaxBasic = 2600000; // maksimal gaji yang kena tanggunan pemerintah
        //$fltSubsidi  = 1300000; // jumlah pendapatan yang disubsidi pemerintah -- sepertinya gak dipakai
        $arrSalaryLimit = $arrSalaryLimit;//($bolNPWP) ? $this->arrSalaryLimit : $this->arrSalaryLimitNo;
        $arrTaxRate = ($bolNPWP) ? $arrTaxRate : $arrTaxRateNo;
        $fltTax = 0;
        // jika lebih kecil dari 1 juta, gak kena pajak
        // if ($fltBasic <= $fltMinBasic) {
        //  return 0;
        //}
        // hitung dulu tunjangan jabatan
        //echo $fltJamsostekDeduction;
        $fltJabatan = ($fltBasic + $fltJamsostekDeduction) * 0.05; // tunjangan jabatan untuk potongan pendapatan tidak kena pajak
        if ($fltJabatan > $this->fltMaxPosition) {
            $fltJabatan = $this->fltMaxPosition;
        }
        $fltJabatanIrregular = ((($intMonth * $fltJabatan) + ($fltPIKP * 0.05)) > (12 * $this->fltMaxPosition)) ? ((12 * $this->fltMaxPosition) - ($intMonth * $fltJabatan)) : ($fltPIKP * 0.05); // tunjangan jabatan untuk potongan pendapatan tidak kena pajak
        //echo ", jbtnIrr:".$fltJabatanIrregular.", month:".$intMonth.", incReg(thn):".($fltBasic + $fltJamsostekDeduction) * $intMonth;
        $fltTaxSubsidi = 0;
        //echo ", pkp(thn)+pikp:".($fltBasic * $intMonth + $fltPIKP);
        /* Perhitungan lama tetap dipertahankan untuk mencegah terjadinya error */
        /* Define $netincome untuk perhitungan pph21 yang baru */
        $netincome = $fltBasic - $fltJabatan;
        /* Start perhitungan pajak dengan kalkulasi baru disini */
        $netincomeannualize = $countpph21->anualizedIncome($netincome) - $fltJabatanIrregular;
        $taxablenetincome = $countpph21->countTaxableNetIncome($this->strFamilyStatus, $netincomeannualize);
        $monthlytax = 0;
        if ($bolNPWP) {
            $annualizetaxincomenpwp = $countpph21->calculateIncomeTaxAnnualized($taxablenetincome);
            $monthlytaxnpwp = $countpph21->calculateMonthlyIncomeTax($annualizetaxincomenpwp);
            $monthlytax = $monthlytaxnpwp;
        } else {
            $annualizetaxincomenonnpwp = $countpph21->calculateIncomeTaxAnnualized($taxablenetincome, false);
            $monthlytaxnonnpwp = $countpph21->calculateMonthlyIncomeTax($annualizetaxincomenonnpwp);
            $monthlytax = $monthlytaxnonnpwp;
        }
        /* End perhitungan pajak dengan kalkulasi baru */
        $fltBasic = ($fltBasic * $intMonth + $fltPIKP) - ($fltJabatan * $intMonth + $fltJabatanIrregular); // dikalikan dengan 12 bulan
        //echo ", baseAfterJbtn(thn):".$fltBasic;
        $fltTemp = floor(($fltBasic - $fltPTKP) / 1000) * 1000; // pendapatan yang kena pajak, dikurangi PTKP
        //echo ", afterPTKP:".$fltTemp;
        $intCount = count($arrSalaryLimit);
        for ($i = 0; $i < ($intCount); $i++) {
            if ($fltTemp > $arrSalaryLimit[$i]) {
                if ($i == 0) {
                    $fltTax += ($arrSalaryLimit[$i] * $arrTaxRate[$i]);
                    $fltTemp = $fltTemp - $arrSalaryLimit[$i];
                } else {
                    $fltTax += (($arrSalaryLimit[$i] - $arrSalaryLimit[$i - 1]) * $arrTaxRate[$i]);
                    $fltTemp = $fltTemp - $arrSalaryLimit[$i] + $arrSalaryLimit[$i - 1];
                }
                //echo $arrSalaryLimit[$i] ."-". $arrTaxRate[$i]."<br>";
            } else {
                $fltTax += ($fltTemp * $arrTaxRate[$i]);
                $fltTemp = 0;
                break;
            }
        }
        if ($fltTemp > 0) // masih ada sisa
        {
            $fltTax += ($fltTemp * $arrTaxRate[$i]);
        }
        //echo ", reg+irr:".$fltTax;
        /* Bypass nilai tax lama dengan yang baru disini */
        $fltTax = $monthlytax;
        return $fltTax;
    }

    /* setData : fungsi untuk mengisi data atribut yang diperlukan dalam perhitungan pajak
        input  : PKP, status keluarga, ada NPWP atau tidak, jumlah periode bulan
    */

    function calculateIrregularPph21GrossUp(
        $fltBasic,
        $fltPIKP,
        $fltPTKP,
        $bolNPWP,
        $intMonth = 12,
        $fltJamsostekDeduction,
        $strIDEmployee
    ) {
        $countpph21 = new countPPH21($intMonth, $this->arrPTKP);
        $arrSalaryLimit2008 = [25000000, 50000000, 100000000, 200000000, 200000000]; // versi tahun 2008 ke bawah
        $arrTaxRate2008 = [0.05, 0.10, 0.15, 0.25, 0.35]; // versi tahun 2008 ke bawah
        $arrSalaryLimit = [0, 50000000, 250000000, 500000000, 50000000000]; // versi 2009, yang punya NPWP
        $arrTaxRate = [0.05, 0.15, 0.25, 0.30]; // versi 2009 yang punya NPWP
        //$arrSalaryLimitNo   = array( 0, 50000000, 250000000, 500000000); // versi 2009 tanpa NPWP
        $arrTaxRateNo = [0.06, 0.18, 0.30, 0.36]; // versi 2009 tanpa NPWP
        //$fltMinBasic = 1300000; // minimal gaji yang dikenai pajak
        //$fltMaxBasic = 2600000; // maksimal gaji yang kena tanggunan pemerintah
        //$fltSubsidi  = 1300000; // jumlah pendapatan yang disubsidi pemerintah -- sepertinya gak dipakai
        $arrSalaryLimit = $arrSalaryLimit;//($bolNPWP) ? $this->arrSalaryLimit : $this->arrSalaryLimitNo;
        $arrTaxRate = ($bolNPWP) ? $arrTaxRate : $arrTaxRateNo;
        $fltTax = 0;
        $fltDelta = 1;
        // jika lebih kecil dari 1 juta, gak kena pajak
        // if ($fltBasic <= $fltMinBasic) {
        //  return 0;
        //}
        // ambil nilai tunjangan jabatan untuk regular income (yang sudah digrossup)
        $fltJabatan = $intMonth * $this->fltPosition;
        $fltAnnualMaxPosition = 12 * $this->fltMaxPosition;
        $fltTemp = $intMonth * ($fltBasic + $this->fltTaxRegular) - $fltJabatan;
        $fltAnnualPKP = $fltTemp - $fltPTKP;
        $fltRemainingPTKP = ($fltTemp > $fltPTKP) ? 0 : $fltPTKP - $fltTemp;
        $a = 0;
        $bolPuter = true;
        /* Kalkulasi lama tetap di pertahankan untuk menjamin gak ada error
       definisikan variable baru $netincome untuk mengambil net income karyawan
  */
        $netincome = $fltBasic + $this->fltTaxRegular - $this->fltPosition;
        /* Start perhitungan pajak dengan kalkulasi baru disini */
        $netincomeannualize = $countpph21->anualizedIncome($netincome);
        $taxablenetincome = $countpph21->countTaxableNetIncome($this->strFamilyStatus, $netincomeannualize);
        $monthlytax = 0;
        if ($bolNPWP) {
            $annualizetaxincomenpwp = $countpph21->calculateIncomeTaxAnnualized($taxablenetincome);
            $monthlytaxnpwp = $countpph21->calculateMonthlyIncomeTax($annualizetaxincomenpwp);
            $monthlytax = $monthlytaxnpwp;
        } else {
            $annualizetaxincomenonnpwp = $countpph21->calculateIncomeTaxAnnualized($taxablenetincome, false);
            $monthlytaxnonnpwp = $countpph21->calculateMonthlyIncomeTax($annualizetaxincomenonnpwp);
            $monthlytax = $monthlytaxnonnpwp;
        }
        /* Start perhitungan pajak dengan kalkulasi baru disini */
        while ($bolPuter) {
            $fltGross = $fltPIKP + $a;
            // hitung kembali tunjangan jabatan irregular
            $fltJabatanIrregular = (($fltJabatan + ($fltGross * 0.05)) > $fltAnnualMaxPosition) ? ($fltAnnualMaxPosition - $fltJabatan) : ($fltGross * 0.05);
            //assign subsidi jika ada
            //$fltTaxSubsidi = 0;
            $fltNet = $fltAnnualPKP + $fltGross - $fltJabatanIrregular; // dikalikan dengan 12 bulan
            $DPP = floor(($fltNet - $fltRemainingPTKP) / 1000) * 1000; // pendapatan yang kena pajak, dikurangi PTKP
            // Itung pembagian pajaknya
            $dummy = $DPP;  //$dummy = 130000000;
            for ($i = (count($arrSalaryLimit) - 1); $i >= 0; $i--) {
                if ($dummy > $arrSalaryLimit[$i]) :
                    $PARSING[$i] = $dummy - $arrSalaryLimit[$i];
                    $dummy = $arrSalaryLimit[$i];
                else :
                    $PARSING[$i] = 0;
                endif;
            }
            // Itung Pajak benernya
            $count = 0;  // variable utk menampung total pajak
            for ($i = 0; $i < count($PARSING); $i++) {
                $TAX[$i] = $PARSING[$i] * $arrTaxRate[$i];
                $count += $TAX[$i];
            }
            $b = $count - ($this->fltTaxRegular * $intMonth);
            //      echo "a : " . $a . "\t b : " . $b . "\n";
            // Cek cek cek
            if (abs($b - $a) >= $fltDelta) {
                $a = ($a + $b) / 2;
            } else {
                $bolPuter = false;
            }
        }
        //$fltTax = $b;//Bypass Nilai pajak yang lama
        /* Set nilai pajak dengan perhitungan pph21 yang baru */
        $fltTax = $monthlytax;
        $this->fltPosition = $fltJabatan;
        $this->fltTaxIrregular = $fltTax;
        return $fltTax;
    }

    function calculatePph21Gross($fltNetIncome, $bolNPWP, $intMonth = 12, $fltJamsostekDeduction)
    {
        $this->calculateBaseTaxBefore($fltNetIncome);
        $countpph21 = new countPPH21($intMonth, $this->arrPTKP);
        $netincomeannualize = 0;
        if (is_null($this->arrEmpBaseTaxPaidTaxBefore)) {
            $fltNetIncome = $this->calculateBaseTaxNet($fltNetIncome, $fltJamsostekDeduction);
            $netincomeannualize = $countpph21->anualizedIncome($fltNetIncome);
        } else {
            $netincomeannualize = (($this->baseTaxBefore + $fltNetIncome) - $this->baseIrrTaxBefore) * ($intMonth / $this->calcMonth) + $this->baseIrrTaxBefore;
        }
        $taxablenetincome = $countpph21->countTaxableNetIncome($this->strFamilyStatus, $netincomeannualize);
        $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized($taxablenetincome, $bolNPWP);
        $monthlytax = 0;
        if (is_null($this->arrEmpBaseTaxPaidTaxBefore)) {
            $monthlytax = $countpph21->calculateMonthlyIncomeTax($annualizetaxincome);
        } else {
            $monthlytax = ($annualizetaxincome - $this->taxBefore) / ($intMonth - $this->intTaxMonth);
        }
        $this->fltTaxRegular = $monthlytax;
        return $monthlytax;
    }

    /* getTax : fungsi untuk mulai melakukan perhitungan pajak      
    */

    function calculatePph21GrossUp($fltNetIncome, $bolNPWP, $intMonth = 12, $fltJamsostekDeduction)
    {
        $this->calculateBaseTaxBefore($fltNetIncome);
        $countpph21 = new countPPH21($intMonth, $this->arrPTKP);
        $netincomeannualize = 0;
        $loop = true;
        $fltGrossIncome = $fltNetIncome;
        $tempNetValue = $this->calculateBaseTaxNet($fltNetIncome, $fltJamsostekDeduction);
        //        if($this->strIDEmployee == "24306")
        //        {
        //            echo "TempNetValue " . $tempNetValue . "<br/>";
        //        }
        $firstTempValue = $tempNetValue;
        $taxAllowance = 0;
        $iteration = 0;
        $netIncomeCalculate = 0;
        while ($loop) {
            //            $netincomeannualize = $countpph21->anualizedIncome($tempNetValue);
            if (is_null($this->arrEmpBaseTaxPaidTaxBefore)) {
                $netincomeannualize = $countpph21->anualizedIncome($tempNetValue);
            } else {
                $netincomeannualize = (($this->baseTaxBefore + $tempNetValue) - $this->baseIrrTaxBefore) * ($intMonth / $this->intTaxMonth) + $this->baseIrrTaxBefore;
            }
            $taxablenetincome = $countpph21->countTaxableNetIncome($this->strFamilyStatus, $netincomeannualize);
            $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized($taxablenetincome, $bolNPWP);
            //            $monthlytax = $countpph21->calculateMonthlyIncomeTax($annualizetaxincome);
            if (is_null($this->arrEmpBaseTaxPaidTaxBefore)) {
                $monthlytax = $countpph21->calculateMonthlyIncomeTax($annualizetaxincome);
            } else {
                $monthlytax = ($annualizetaxincome - $this->taxBefore) / ($intMonth - $this->intTaxMonth + 1);
            }
            //print $this->strFamilyStatus.' '.$grossValue.' '.$tempNetValue.' '.$taxablenetincome.' '.$taxAllowance.' '.$monthlytax.' '.$iteration.'<br>';
            if ($monthlytax == $taxAllowance || $iteration > 100) {
                $netIncomeCalculate = $tempNetValue;
                $loop = false;
                //                 if($this->strIDEmployee == "24306")
                //                {
                //                    echo "Initial Base Tax $fltGrossIncome<br/>";
                //                    echo "Current Base Tax " . ($fltGrossIncome + $taxAllowance ) . "<br/>";
                //                    echo "Tax Allowance $taxAllowance <br/>";
                //                    echo "Monthly tax $monthlytax";
                //                }
            } else {
                $taxAllowance = ($taxAllowance + $monthlytax) / 2;
            }
            $tempNetValue = $this->calculateBaseTaxNet($fltGrossIncome + $taxAllowance, $fltJamsostekDeduction);
            $iteration++;
        }
        $this->fltTaxRegular = $monthlytax;
        return $monthlytax;
    }

    /* getIrregularTax : fungsi untuk mulai melakukan perhitungan pajak untuk pendapatan irregular
    */

    function getIrregularTax()
    {
        if ($this->intMethod == 1) // gross up
        {
            return $this->calculateIrregularPph21GrossUp(
                $this->fltPKP,
                $this->fltPIKP,
                $this->fltPTKP,
                $this->bolNPWP,
                $this->intPeriod,
                $this->fltJamsostekDeduction,
                $this->strIDEmployee
            );
        } else // gross
        {
            $fltRegIrr = $this->calculateIrregularPph21Gross(
                $this->fltPKP,
                $this->fltPIKP,
                $this->fltPTKP,
                $this->bolNPWP,
                $this->intPeriod,
                $this->fltJamsostekDeduction,
                $this->strIDEmployee
            );
            $fltReg = $this->fltTaxRegular * $this->intPeriod;
            $intResult = $fltRegIrr - $fltReg;
            return $intResult;
        }
    }

    /* calculateBaseTaxNet : fungsi untuk mengkalkulasi Net Base Tax dari base_tax */

    function getTax()
    {
        if ($this->intMethod == 1) // gross up
        {
            return $this->calculatePph21GrossUp(
                $this->fltPKP,
                $this->bolNPWP,
                $this->intPeriod,
                $this->fltJamsostekDeduction
            );
        } else // gross
        {
            return $this->calculatePph21Gross(
                $this->fltPKP,
                $this->bolNPWP,
                $this->intPeriod,
                $this->fltJamsostekDeduction
            );
        }
    }
    /* End calculateBaseTaxNet */
    /* New Function calculatePph21Gross only calculate tax with using 
        actual base income and irregular income 
        @var $fltBasic : Penghasilan kena pajak(belum dipotong tunjangan jabatan)
        @var $bolNPWP  : menggunakan npwp atau tidak (true/false)
        @var $intMonth : Total bulan dalam perhitungan (default 12)
        @var $fltJamsostekDeduction : Potongan jamsostek(digunakan untuk subsidi jabatan)
     */

    function getTaxReduction($strCode)
    {
        $fltResult = (isset($this->arrPTKP[$strCode])) ? $this->arrPTKP[$strCode] : 0;
        return $fltResult;
    }

    /* End calculatePph21Gross */

    function initTaxReduction()
    {
        $strSQL = "SELECT tax_status_code, ptkp FROM hrd_family_status_tax ";
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $this->arrPTKP[$row['tax_status_code']] = $row['ptkp'];
        }
    }

    /* New Function calculatePph21Gross only calculate tax with using
    actual base income and irregular income 
    @var $fltBasic : Penghasilan kena pajak(belum dipotong tunjangan jabatan)
    @var $bolNPWP  : menggunakan npwp atau tidak (true/false)
    @var $intMonth : Total bulan dalam perhitungan (default 12)
    @var $fltJamsostekDeduction : Potongan jamsostek(digunakan untuk subsidi jabatan)
    */

    function initTaxReduction_Old()
    {
        $strSQL = "SELECT family_status_code, tax_reduction FROM hrd_family_status ";
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $this->arrPTKP[$row['family_status_code']] = $row['tax_reduction'];
        }
    }
    /* End calculatePph21GrossUp */
    /* calculateIrregularPph21Gross : fungsi untuk menghitung/mengirimkan pajak Pph21, dengan metode gross, include pendapatan irregular
        input  : pendapatan kena pajak (PKP), PIKP, PTKP, periode bulan
        output : pajak
    */

    function setData($fltPKP, $strFamilyStatus, $bolNPWP = false, $intPeriod = 12, $fltJamsostekDeduction)
    {
        $this->fltPKP = (is_numeric($fltPKP)) ? $fltPKP : 0;
        $this->bolNPWP = $bolNPWP;
        $this->intPeriod = (is_numeric($intPeriod)) ? $intPeriod : 12;
        $this->strFamilyStatus = $strFamilyStatus;
        $this->fltPTKP = $this->getTaxReduction($strFamilyStatus);
        $this->fltJamsostekDeduction = $fltJamsostekDeduction;
        $this->fltTaxRegular = 0;
    }

    /* calculateIrregularPph21Gross : fungsi untuk menghitung/mengirimkan pajak Pph21, dengan metode gross, include pendapatan irregular
        input  : pendapatan kena pajak (PKP), PIKP, PTKP, periode bulan
        output : pajak
    */

    function setDataIncludeIrregular(
        $fltPKP,
        $fltPIKP,
        $strFamilyStatus,
        $bolNPWP = false,
        $intPeriod = 12,
        $fltJamsostekDeduction,
        $strIDEmployee,
        $strEmployeeStatus,
        $arrEmpBaseTaxPaidTaxBefore = null,
        $calcMonth = 1
    ) {
        $this->fltPKP = (is_numeric($fltPKP)) ? $fltPKP : 0;
        $this->fltPIKP = (is_numeric($fltPIKP)) ? $fltPIKP : 0;
        $this->bolNPWP = $bolNPWP;
        $this->intPeriod = (is_numeric($intPeriod)) ? $intPeriod : 12;
        $this->strFamilyStatus = $strFamilyStatus;
        $this->fltPTKP = $this->getTaxReduction($strFamilyStatus);
        $this->fltJamsostekDeduction = $fltJamsostekDeduction;
        $this->fltTaxRegular = 0;
        $this->strIDEmployee = $strIDEmployee;
        $this->intMethod = $strEmployeeStatus;
        $this->arrEmpBaseTaxPaidTaxBefore = $arrEmpBaseTaxPaidTaxBefore;
        $this->calcMonth = $calcMonth;
    }
}

?>