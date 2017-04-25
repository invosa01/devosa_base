<?php
// ---- variabel untuk PPH21
define("JOIN_DATE_LIMIT", "10");
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

    var $fltMaxPosition = 6000000; //apakah punya NPWP atau tidak

    var $fltPKP = 0; // periode kerja karyawan, dalam bulan, max 12 bulan

    var $fltPosition = 0; // kode status keluarga

    // atribut konstanta
    var $intTaxMethod = 1; // 0 : gross, 1 : gross up

    var $intTaxIrregularMethod = 1; // 0 : gross, 1 : gross up

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
        $this->baseTaxBefore = 0;
        $this->baseIrrTaxBefore = 0;
        $this->taxBefore = 0;
        $this->irrTaxBefore = 0;
        $this->PensionDeductionBefore = 0;
        $this->JamsostekDeductionBefore = 0;
        if (isset($this->arrEmpBaseTaxPaidTaxBefore)) {
            $this->baseTaxBefore = $this->arrEmpBaseTaxPaidTaxBefore['base_tax'];
            $this->baseIrrTaxBefore = $this->arrEmpBaseTaxPaidTaxBefore['base_irregular_tax'];
            $this->taxBefore = $this->arrEmpBaseTaxPaidTaxBefore['tax'];
            $this->irrTaxBefore = $this->arrEmpBaseTaxPaidTaxBefore['irregular_tax'];
            $this->PensionDeductionBefore = $this->arrEmpBaseTaxPaidTaxBefore['pension_deduction'];
            $this->JamsostekDeductionBefore = $this->arrEmpBaseTaxPaidTaxBefore['jamsostek_deduction'];
        }
    }

    /* getTaxReduction : mengambil nilai PTKP berdasar status keluarga
        input  : status keluarga
        output : nilai PTKP, ambil dari atribut
    */

    function calculateFunctionalCost($fltIncome)
    {
        $functionalCost = $fltIncome * 5 / 100;
        if ($functionalCost >= ($this->fltMaxPosition * $this->taxableMonth / 12)) {
            $functionalCost = $this->fltMaxPosition * $this->taxableMonth / 12;
        }
        return $functionalCost;
    }

    /**
     * Function to calculate tax pph21 flat gross method.
     * TODO: 2. Add irregular tax.
     *
     * @param $fltNetIncome
     * @param $fltIrrIncome
     * @param $bolNPWP
     * @param $fltPTKP
     * @param $fltJamsostekDeduction
     * @param $fltPensionDeduction
     * @param $taxableDayUpToEndOfYear
     * @param $taxableDayUpToCurrent
     * @param $taxableMonth
     * @param $currentTaxableMonth
     * @param $bolRegular
     *
     * @return float
     */
    function calculatePph21Annual(
        $fltNetIncome,
        $fltIrrIncome,
        $bolNPWP,
        $fltPTKP,
        $fltJamsostekDeduction,
        $fltPensionDeduction,
        $taxableDayUpToEndOfYear,
        $taxableDayUpToCurrent,
        $taxableMonth,
        $currentTaxableMonth,
        $bolRegular
    ) {
        $countpph21 = new countPPH21($taxableMonth, $this->arrPTKP);
        $netincomeannualize = (($fltNetIncome) + $fltIrrIncome) * $taxableMonth;                    //total income kena pajak disetahunkan
        $functionalCost = $this->calculateFunctionalCost($netincomeannualize);                                                        //tunjangan jabatan
        $jamsostekDeduction = $fltJamsostekDeduction * $taxableMonth;    //potongan jamsostek setahun
        $pensionDeduction = $fltPensionDeduction * $taxableMonth;    //potongan jamsostek setahun
        $taxablenetincome = $countpph21->roundDown(
            ($netincomeannualize - $functionalCost - $jamsostekDeduction - $pensionDeduction - $fltPTKP),
            3
        );                                    //total pendapatan kena pajak bersih
        if ($taxablenetincome <= 0) {
            $taxablenetincome = 0;
        }
        $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized(
            $taxablenetincome,
            $bolNPWP
        );                                //Pph Terhutang setahun
        $annualizetaxincomeNet = $this->calculatePph21AnnualNet(
            $fltNetIncome,
            $bolNPWP,
            $fltPTKP,
            $fltJamsostekDeduction,
            $fltPensionDeduction,
            $taxableDayUpToEndOfYear,
            $taxableDayUpToCurrent,
            $taxableMonth,
            $currentTaxableMonth
        );
        //$taxIrregular = ($annualizetaxincome - $annualizetaxincomeNet);
        //$taxUntilCurrentPeriod = ($annualizetaxincome - $taxIrregular);        //PPh terhutang sampai bulan ini
        $taxUntilCurrentPeriod = $annualizetaxincome/$taxableMonth;
        $yearlytax = $countpph21->roundDown(($taxUntilCurrentPeriod), 0);
        if ($bolRegular) {
            $this->fltTaxRegular = $yearlytax;
            return $yearlytax;
        } else {
            $yearlytaxIrregular = $countpph21->roundDown(($taxIrregular), 0);            //die()
            $this->fltTaxIrregular = $yearlytaxIrregular;
            return $yearlytaxIrregular;
        }
    }

    /**
     * Function to calculate pph21 flat gross up method.
     * TODO: 2. Add irregular tax.
     *
     * @param $fltNetIncome
     * @param $fltIrrIncome
     * @param $bolNPWP
     * @param $fltPTKP
     * @param $fltJamsostekDeduction
     * @param $fltPensionDeduction
     * @param $taxableDayUpToEndOfYear
     * @param $taxableDayUpToCurrent
     * @param $taxableMonth
     * @param $currentTaxableMonth
     * @param $bolRegular
     *
     * @return float
     */
    function calculatePph21AnnualGrossUp(
        $fltNetIncome,
        $fltIrrIncome,
        $bolNPWP,
        $fltPTKP,
        $fltJamsostekDeduction,
        $fltPensionDeduction,
        $taxableDayUpToEndOfYear,
        $taxableDayUpToCurrent,
        $taxableMonth,
        $currentTaxableMonth,
        $bolRegular
    ) {
        $fltTaxAllowance = 0;
        $fltTaxIrregularAllowance = 0;
        $bolLoop = true;
        $fltDelta = 0.01;
        $fltNetIncome = $fltNetIncome + $fltIrrIncome;
        $countpph21 = new countPPH21($taxableMonth, $this->arrPTKP);

        while ($bolLoop) {
            # Total income kena pajak disetahunkan.
            $netincomeannualize = ($fltNetIncome + $fltTaxAllowance) * $taxableMonth;
            $functionalCost = $this->calculateFunctionalCost($netincomeannualize);                                                        //tunjangan jabatan
            $jamsostekDeduction = $fltJamsostekDeduction * $taxableMonth;    //potongan jamsostek setahun
            $pensionDeduction = $fltPensionDeduction * $taxableMonth;    //potongan jamsostek setahun
            $taxablenetincome = $countpph21->roundDown(
                ($netincomeannualize - $functionalCost - $jamsostekDeduction - $pensionDeduction - $fltPTKP),
                3
            );                                    //total pendapatan kena pajak bersih
            if ($taxablenetincome <= 0) {
                $taxablenetincome = 0;
            }
            $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized(
                $taxablenetincome,
                $bolNPWP
            );                                //Pph Terhutang setahun
            $annualizetaxincomeNet = $this->calculatePph21AnnualNet(
                $fltNetIncome,
                $bolNPWP,
                $fltPTKP,
                $fltJamsostekDeduction,
                $fltPensionDeduction,
                $taxableDayUpToEndOfYear,
                $taxableDayUpToCurrent,
                $taxableMonth,
                $currentTaxableMonth
            );
            //$taxUntilCurrentPeriod = ($annualizetaxincome - $taxIrregular);        //PPh terhutang sampai bulan ini
            $taxUntilCurrentPeriod = $annualizetaxincome/$taxableMonth;
            //$taxIrregular = ($annualizetaxincome - $annualizetaxincomeNet) + $taxUntilCurrentPeriod;
            $yearlytax = $countpph21->roundDown(($taxUntilCurrentPeriod), 0);
            //$yearlytaxIrregular = $countpph21->roundDown(($taxIrregular), 0);            //die()
            if ((abs($yearlytax - $fltTaxAllowance) >= $fltDelta)) {
                $fltTaxAllowance = ($fltTaxAllowance + $yearlytax) / 2;
                //$fltTaxIrregularAllowance = ($fltTaxIrregularAllowance + $yearlytaxIrregular) / 2;
            }
            else {
                $bolLoop = false;
            }
        }
        if ($bolRegular) {
            $this->fltTaxRegular = $yearlytax;
            return $yearlytax;
        } else {
            $this->fltTaxIrregular = $yearlytaxIrregular;
            return $yearlytaxIrregular;
        }
    }

    function calculatePph21AnnualNet(
        $fltNetIncome,
        $bolNPWP,
        $fltPTKP,
        $fltJamsostekDeduction,
        $fltPensionDeduction,
        $taxableDayUpToEndOfYear,
        $taxableDayUpToCurrent,
        $taxableMonth,
        $currentTaxableMonth
    ) {
        $countpph21 = new countPPH21(12, $this->arrPTKP);
        $netincomeannualize = $fltNetIncome;                    //total income kena pajak disetahunkan
        $functionalCost = $this->calculateFunctionalCost(
            $netincomeannualize
        );                                                        //tunjangan jabatan
        $jamsostekDeduction = $fltJamsostekDeduction;    //potongan jamsostek setahun
        $pensionDeduction = $fltPensionDeduction;    //potongan jamsostek setahun
        $taxablenetincome = $countpph21->roundDown(
            ($netincomeannualize - $functionalCost - $jamsostekDeduction - $pensionDeduction - $fltPTKP),
            3
        );                                    //total pendapatan kena pajak bersih
        if ($taxablenetincome <= 0) {
            $taxablenetincome = 0;
        }
        $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized(
            $taxablenetincome,
            $bolNPWP
        );                                //Pph Terhutang setahun
        $taxUntilCurrentPeriod = $annualizetaxincome;        //PPh terhutang sampai bulan ini
        //PPh terhutang sampai bulan kemarin
        $yearlytax = $countpph21->roundDown(($taxUntilCurrentPeriod), 0);
        return $annualizetaxincome;
    }

    /* getTax : fungsi untuk mulai melakukan perhitungan pajak
 */

    function calculatePph21Gross(
        $fltNetIncome,
        $fltIrrIncome,
        $bolNPWP,
        $fltPTKP,
        $fltJamsostekDeduction,
        $fltPensionDeduction,
        $taxableDayUpToEndOfYear,
        $taxableDayUpToCurrent,
        $taxableMonth,
        $currentTaxableMonth,
        $bolRegular
    ) {
        $this->calculateBaseTaxBefore();
        $countpph21 = new countPPH21(12, $this->arrPTKP);
        $netincomeannualize = 0;
        $fltNetIncome;
        $this->baseTaxBefore;
        $fltNetIncome = $fltNetIncome + $this->baseTaxBefore;                                                                    //total upah kotor
        $fltIrrIncome = $fltIrrIncome + $this->baseIrrTaxBefore;                                                                //total irregular
        $netincomeannualize = ($taxableMonth / $currentTaxableMonth * $fltNetIncome) + $fltIrrIncome;                    //total income kena pajak disetahunkan
        $functionalCost = $this->calculateFunctionalCost(
            $netincomeannualize
        );                                                    //tunjangan jabatan
        $jamsostekDeduction = ($fltJamsostekDeduction + $this->JamsostekDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
        $pensionDeduction = ($fltPensionDeduction + $this->PensionDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
        $taxablenetincome = $countpph21->roundDown(
            ($netincomeannualize - $functionalCost - $jamsostekDeduction - $pensionDeduction - $fltPTKP),
            3
        );                                    //total pendapatan kena pajak bersih
        if ($taxablenetincome <= 0) {
            $taxablenetincome = 0;
        }
        $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized(
            $taxablenetincome,
            $bolNPWP
        );                                //Pph Terhutang setahun
        $taxIrregularUntilLastPeriod = $this->irrTaxBefore;                                                                            //PPh Irregular terhutang sampai bulan kemarin
        $taxableIncomeNet = $this->calculatePph21GrossAnnualizedNet(
            $this->fltPKP,
            $this->bolNPWP,
            $this->fltPTKP,
            $this->fltJamsostekDeduction,
            $this->fltPensionDeduction,
            $this->taxableDayUpToEndOfYear,
            $this->taxableDayUpToCurrent,
            $this->taxableMonth,
            $this->currentTaxableMonth
        );
        $annualizetaxincomeNet = $countpph21->calculateIncomeTaxAnnualized($taxableIncomeNet, $bolNPWP);
        $taxIrregular = $annualizetaxincome - $annualizetaxincomeNet;
        if ($bolRegular) {
            $taxUntilCurrentPeriod = ($annualizetaxincome - $taxIrregular) * $currentTaxableMonth / $taxableMonth;        //PPh terhutang sampai bulan ini
            $taxUntilLastPeriod = $this->taxBefore;                                                                                                    //PPh terhutang sampai bulan kemarin
            $monthlytax = $countpph21->roundDown(
                ($taxUntilCurrentPeriod - $taxUntilLastPeriod),
                0
            );            //die();					            //PPh terutang bulan ini
            $this->fltTaxRegular = $monthlytax;
            return $monthlytax;
        } else {
            $monthlytaxIrregular = $countpph21->roundDown(
                ($taxIrregular - $taxIrregularUntilLastPeriod),
                0
            );            //die()
            $this->fltTaxIrregular = $monthlytaxIrregular;
            return $monthlytaxIrregular;
        }
    }

    function calculatePph21GrossAnnualizedNet(
        $fltNetIncome,
        $bolNPWP,
        $fltPTKP,
        $fltJamsostekDeduction,
        $fltPensionDeduction,
        $taxableDayUpToEndOfYear,
        $taxableDayUpToCurrent,
        $taxableMonth,
        $currentTaxableMonth
    ) {
        $this->calculateBaseTaxBefore();
        $countpph21 = new countPPH21(12, $this->arrPTKP);
        $netincomeannualize = 0;
        $fltNetIncome = $fltNetIncome + $this->baseTaxBefore;                                                                    //total upah kotor
        $fltTotalIncome = $fltNetIncome;                                                                                        //total income kena pajak
        $netincomeannualize = $taxableMonth / $currentTaxableMonth * $fltTotalIncome;                                    //total income disetahunkan
        $functionalCost = $this->calculateFunctionalCost(
            $netincomeannualize
        );                                                        //tunjangan jabatan
        $jamsostekDeduction = ($fltJamsostekDeduction + $this->JamsostekDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
        $pensionDeduction = ($fltPensionDeduction + $this->PensionDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
        $taxablenetincome = $countpph21->roundDown(
            ($netincomeannualize - $functionalCost - $jamsostekDeduction - $pensionDeduction - $fltPTKP),
            3
        );                                    //total pendapatan kena pajak bersih
        if ($taxablenetincome <= 0) {
            $taxablenetincome = 0;
        }
        return $taxablenetincome;
    }

    /* New Function calculatePph21Gross only calculate tax with using
       actual base income and irregular income */

    function calculatePph21GrossUp(
        $fltNetIncome,
        $fltIrrIncome,
        $bolNPWP,
        $fltPTKP,
        $fltJamsostekDeduction,
        $fltPensionDeduction,
        $taxableDayUpToEndOfYear,
        $taxableDayUpToCurrent,
        $taxableMonth,
        $currentTaxableMonth,
        $bolRegular
    ) {
        $this->calculateBaseTaxBefore();
        $countpph21 = new countPPH21(12, $this->arrPTKP);
        $netincomeannualize = 0;
        $fltTax = 0;
        $fltDelta = 0.01;
        $taxAllowance = 0;
        $taxIrregularAllowance = 0;
        $bolPuter = true;
        $fltNetIncome0 = $fltNetIncome + $this->baseTaxBefore;                                                                    //total upah kotor
        $fltIrrIncome = $fltIrrIncome + $this->baseIrrTaxBefore;                                                                //total irregular
        $taxIrregularUntilLastPeriod = $this->irrTaxBefore;                                                                            //PPh Irregular terhutang sampai bulan kemarin
        $annualizetaxincomeNet = $this->calculatePph21GrossUpAnnualizedNet(
            $this->fltPKP,
            $this->bolNPWP,
            $this->fltPTKP,
            $this->fltJamsostekDeduction,
            $this->fltPensionDeduction,
            $this->taxableDayUpToEndOfYear,
            $this->taxableDayUpToCurrent,
            $this->taxableMonth,
            $this->currentTaxableMonth
        );
        while ($bolPuter) {
            $fltNetIncome = $fltNetIncome0 + $taxAllowance;
            if ($currentTaxableMonth > 0) {
                $netincomeannualize = (($taxableMonth / $currentTaxableMonth) * $fltNetIncome) + $fltIrrIncome + $taxIrregularAllowance;
            }                    //total income kena pajak disetahunkan
            else {
                $netincomeannualize = 0;
            }
            $functionalCost = $this->calculateFunctionalCost(
                $netincomeannualize
            );                                                        //tunjangan jabatan
            if ($currentTaxableMonth > 0) {
                $jamsostekDeduction = ($fltJamsostekDeduction + $this->JamsostekDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
                $pensionDeduction = ($fltPensionDeduction + $this->PensionDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
            } else {
                $jamsostekDeduction = 0;
                $pensionDeduction = 0;
            }
            $taxablenetincome = $countpph21->roundDown(
                ($netincomeannualize - $functionalCost - $jamsostekDeduction - $pensionDeduction - $fltPTKP),
                3
            );                                    //total pendapatan kena pajak bersih
            if ($taxablenetincome <= 0) {
                $taxablenetincome = 0;
            }
            $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized(
                $taxablenetincome,
                $bolNPWP
            );                                //Pph Terhutang setahun
            $taxIrregular = $annualizetaxincome - $annualizetaxincomeNet;
            $monthlytaxIrregular = $countpph21->roundDown(($taxIrregular - $taxIrregularUntilLastPeriod), 0);
            $taxUntilCurrentPeriod = ($annualizetaxincome - $taxIrregular) * $currentTaxableMonth / $taxableMonth;        //PPh terhutang sampai bulan ini
            $taxUntilLastPeriod = $this->taxBefore;                                                                                                    //PPh terhutang sampai bulan kemarin
            $monthlytax = $countpph21->roundDown(($taxUntilCurrentPeriod - $taxUntilLastPeriod), 0);
            if ((abs($monthlytax - $taxAllowance) >= $fltDelta)
            ) {
                $taxAllowance = ($taxAllowance + $monthlytax) / 2;
                //$taxIrregularAllowance = ($taxIrregularAllowance + $monthlytaxIrregular) / 2;
            } else {
                $bolPuter = false;
            }/*if ((abs($monthlytax - $taxAllowance) >= $fltDelta) && (abs(
                        $monthlytaxIrregular - $taxIrregularAllowance
                    ) >= $fltDelta)
            ) {
                $taxAllowance = ($taxAllowance + $monthlytax) / 2;
                $taxIrregularAllowance = ($taxIrregularAllowance + $monthlytaxIrregular) / 2;
            } else {
                $bolPuter = false;
            }*/
        }
        if ($bolRegular) {
            $this->fltTaxRegular = $monthlytax;
            return $monthlytax;
        } else {
            $this->fltTaxIrregular = $monthlytaxIrregular;
            return $monthlytaxIrregular;
        }
    }

    function calculatePph21GrossUpIrregular(
        $fltNetIncome,
        $fltIrrIncome,
        $bolNPWP,
        $fltPTKP,
        $fltJamsostekDeduction,
        $fltPensionDeduction,
        $taxableDayUpToEndOfYear,
        $taxableDayUpToCurrent,
        $taxableMonth,
        $currentTaxableMonth,
        $bolRegular
    ) {
        $this->calculateBaseTaxBefore();
        $countpph21 = new countPPH21(12, $this->arrPTKP);
        $netincomeannualize = 0;
        $fltTax = 0;
        $fltDelta = 0.01;
        $taxAllowance = 0;
        $taxIrregularAllowance = 0;
        $bolPuter = true;
        $fltNetIncome0 = $fltNetIncome + $this->baseTaxBefore;                                                                    //total upah kotor
        $fltIrrIncome = $fltIrrIncome + $this->baseIrrTaxBefore;                                                                //total irregular
        $taxIrregularUntilLastPeriod = $this->irrTaxBefore;                                                                            //PPh Irregular terhutang sampai bulan kemarin
        $annualizetaxincomeNet = $this->calculatePph21GrossUpAnnualizedNet(
            $this->fltPKP,
            $this->bolNPWP,
            $this->fltPTKP,
            $this->fltJamsostekDeduction,
            $this->fltPensionDeduction,
            $this->taxableDayUpToEndOfYear,
            $this->taxableDayUpToCurrent,
            $this->taxableMonth,
            $this->currentTaxableMonth
        );
        while ($bolPuter) {
            $fltNetIncome = $fltNetIncome0 + $taxAllowance;
            if ($currentTaxableMonth > 0) {
                $netincomeannualize = (($taxableMonth / $currentTaxableMonth) * $fltNetIncome) + $fltIrrIncome + $taxIrregularAllowance;
            }                    //total income kena pajak disetahunkan
            else {
                $netincomeannualize = 0;
            }
            $functionalCost = $this->calculateFunctionalCost(
                $netincomeannualize
            );                                                        //tunjangan jabatan
            if ($currentTaxableMonth > 0) {
                $jamsostekDeduction = ($fltJamsostekDeduction + $this->JamsostekDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
                $pensionDeduction = ($fltPensionDeduction + $this->PensionDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
            } else {
                $jamsostekDeduction = 0;
                $pensionDeduction = 0;
            }
            $taxablenetincome = $countpph21->roundDown(
                ($netincomeannualize - $functionalCost - $jamsostekDeduction - $pensionDeduction - $fltPTKP),
                3
            );                                    //total pendapatan kena pajak bersih
            if ($taxablenetincome <= 0) {
                $taxablenetincome = 0;
            }
            $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized(
                $taxablenetincome,
                $bolNPWP
            );                                //Pph Terhutang setahun
            $taxIrregular = $annualizetaxincome - $annualizetaxincomeNet;
            $monthlytaxIrregular = $countpph21->roundDown(($taxIrregular - $taxIrregularUntilLastPeriod), 0);
            $taxUntilCurrentPeriod = ($annualizetaxincome - $taxIrregular) * $currentTaxableMonth / $taxableMonth;        //PPh terhutang sampai bulan ini
            $taxUntilLastPeriod = $this->taxBefore;                                                                                                    //PPh terhutang sampai bulan kemarin
            $monthlytax = $countpph21->roundDown(($taxUntilCurrentPeriod - $taxUntilLastPeriod), 0);
            if ((abs($monthlytax - $taxAllowance) >= $fltDelta)
            ) {
                $taxAllowance = ($taxAllowance + $monthlytax) / 2;
                $taxIrregularAllowance = ($taxIrregularAllowance + $monthlytaxIrregular) / 2;
            } else {
                $bolPuter = false;
            }
            if ((abs($monthlytax - $taxAllowance) >= $fltDelta) && (abs(
                        $monthlytaxIrregular - $taxIrregularAllowance
                    ) >= $fltDelta)
            ) {
                $taxAllowance = ($taxAllowance + $monthlytax) / 2;
                $taxIrregularAllowance = ($taxIrregularAllowance + $monthlytaxIrregular) / 2;
            } else {
                $bolPuter = false;
            }
        }
        if ($bolRegular) {
            $this->fltTaxRegular = $monthlytax;
            return $monthlytax;
        } else {
            $this->fltTaxIrregular = $monthlytaxIrregular;
            return $monthlytaxIrregular;
        }
    }

    function calculatePph21GrossUpAnnualizedNet(
        $fltNetIncome,
        $bolNPWP,
        $fltPTKP,
        $fltJamsostekDeduction,
        $fltPensionDeduction,
        $taxableDayUpToEndOfYear,
        $taxableDayUpToCurrent,
        $taxableMonth,
        $currentTaxableMonth
    ) {
        $this->calculateBaseTaxBefore();
        $countpph21 = new countPPH21(12, $this->arrPTKP);
        $netincomeannualize = 0;
        $fltTax = 0;
        $fltDelta = 0.01;
        $taxAllowance = 0;
        $bolPuter = true;
        $fltNetIncome0 = $fltNetIncome + $this->baseTaxBefore;                                                                    //total upah kotor
        while ($bolPuter) {
            $fltNetIncome = $fltNetIncome0 + $taxAllowance;
            if ($currentTaxableMonth > 0) {
                $netincomeannualize = $taxableMonth / $currentTaxableMonth * $fltNetIncome;
            }                    //total income kena pajak disetahunkan
            else {
                $netincomeannualize = 0;
            }
            $functionalCost = $this->calculateFunctionalCost(
                $netincomeannualize
            );                                                        //tunjangan jabatan
            if ($currentTaxableMonth > 0) {
                $jamsostekDeduction = ($fltJamsostekDeduction + $this->JamsostekDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
                $pensionDeduction = ($fltPensionDeduction + $this->PensionDeductionBefore) * $taxableMonth / $currentTaxableMonth;    //potongan jamsostek setahun
            } else {
                $jamsostekDeduction = 0;
                $pensionDeduction = 0;
            }
            $taxablenetincome = $countpph21->roundDown(
                ($netincomeannualize - $functionalCost - $jamsostekDeduction - $pensionDeduction - $fltPTKP),
                3
            );                                    //total pendapatan kena pajak bersih
            if ($taxablenetincome <= 0) {
                $taxablenetincome = 0;
            }
            $annualizetaxincome = $countpph21->calculateIncomeTaxAnnualized(
                $taxablenetincome,
                $bolNPWP
            );                                //Pph Terhutang setahun
            $taxUntilCurrentPeriod = $annualizetaxincome * $currentTaxableMonth / $taxableMonth;        //PPh terhutang sampai bulan ini
            $taxUntilLastPeriod = $this->taxBefore;                                                                                                    //PPh terhutang sampai bulan kemarin
            $monthlytax = $countpph21->roundDown(($taxUntilCurrentPeriod - $taxUntilLastPeriod), 0);
            if (abs($monthlytax - $taxAllowance) >= $fltDelta) {
                $taxAllowance = ($taxAllowance + $monthlytax) / 2;
            } else {
                $bolPuter = false;
            }
        }
        return $annualizetaxincome;
    }

    /* New Function to calculate base tax months before */

    function calculateTaxPesangon($amount = null, $min = 50000000)
    {
        $pesangonTax = 0;
        if (!empty($amount)) {
            $pengali = $amount - $min;
            $pengali2 = $pengali - $min;
            if ($pengali <= 0) {
                $pesangonTax = (0 / 100) * $pengali;
            } else if ($pengali >= 0 && $pengali <= $min) {
                $pesangonTax = (5 / 100) * $pengali;
            } else {
                $pesangonTax = ((5 / 100) * $min) + ((15 / 100) * $pengali2);
            }
        }
        return $pesangonTax;
    }

    /* New Function to calculate functional cost deduction */

    function getTax($bolRegular)
    {
        if ($this->intTaxMethod == 1 && $this->intTaxIrregularMethod == 1) // gross up regular & irregular
        {
            return $this->calculatePph21GrossUp(
                $this->fltPKP,
                $this->fltPIKP,
                $this->bolNPWP,
                $this->fltPTKP,
                $this->fltJamsostekDeduction,
                $this->fltPensionDeduction,
                $this->taxableDayUpToEndOfYear,
                $this->taxableDayUpToCurrent,
                $this->taxableMonth,
                $this->currentTaxableMonth,
                $bolRegular
            );
        } elseif ($this->intTaxMethod == 1 && $this->intTaxIrregularMethod == 0) { // gross up regular saja, irregular tidak di gross up
            return $this->calculatePph21GrossUpIrregular(
                $this->fltPKP,
                $this->fltPIKP,
                $this->bolNPWP,
                $this->fltPTKP,
                $this->fltJamsostekDeduction,
                $this->fltPensionDeduction,
                $this->taxableDayUpToEndOfYear,
                $this->taxableDayUpToCurrent,
                $this->taxableMonth,
                $this->currentTaxableMonth,
                $bolRegular
            );
        } else // gross
        {
            return $this->calculatePph21Gross(
                $this->fltPKP,
                $this->fltPIKP,
                $this->bolNPWP,
                $this->fltPTKP,
                $this->fltJamsostekDeduction,
                $this->fltPensionDeduction,
                $this->taxableDayUpToEndOfYear,
                $this->taxableDayUpToCurrent,
                $this->taxableMonth,
                $this->currentTaxableMonth,
                $bolRegular
            );
        }
    }

    /**
     * Function to calculate pph21 flat
     * @param $bolRegular
     *
     * @return float
     */
    function getTaxAnnual($bolRegular)
    {
        # Calculate tax flat with gross method
        if ($this->intTaxMethod == 0) {
            return $this->calculatePph21Annual(
                $this->fltPKP,
                $this->fltPIKP,
                $this->bolNPWP,
                $this->fltPTKP,
                $this->fltJamsostekDeduction,
                $this->fltPensionDeduction,
                $this->taxableDayUpToEndOfYear,
                $this->taxableDayUpToCurrent,
                $this->taxableMonth,
                $this->currentTaxableMonth,
                $bolRegular
            );
        }
        # Calculate tax flat with gross up method
        else {
            return $this->calculatePph21AnnualGrossUp(
                $this->fltPKP,
                $this->fltPIKP,
                $this->bolNPWP,
                $this->fltPTKP,
                $this->fltJamsostekDeduction,
                $this->fltPensionDeduction,
                $this->taxableDayUpToEndOfYear,
                $this->taxableDayUpToCurrent,
                $this->taxableMonth,
                $this->currentTaxableMonth,
                $bolRegular
            );
        }
    }

    function getTaxReduction($strCode)
    {
        $fltResult = (isset($this->arrPTKP[$strCode])) ? $this->arrPTKP[$strCode] : 0;
        return $fltResult;
    }

    /* New Function calculatePph21Gross only calculate tax with using
  actual base income and irregular income */

    function initTaxReduction()
    {
        $strSQL = "SELECT family_status_code, tax_reduction FROM hrd_family_status ";
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $this->arrPTKP[$row['family_status_code']] = $row['tax_reduction'];
        }
    }

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

    /* Fungsi untuk mengkalkulasi tax pesangon
  * Sanusi a.k Degol | 2016-05-04 
  */

    function setDataIncludeIrregular(
        $fltPKP,
        $fltPIKP,
        $strFamilyStatus,
        $bolNPWP = false,
        $fltJamsostekDeduction = 0,
        $fltPensionDeduction = 0,
        $strIDEmployee,
        $tax_method,
        $tax_irregular_method,
        $arrEmpBaseTaxPaidTaxBefore = null,
        $calcMonth,
        $calcYear,
        $strJoinDate,
        $strResignDate,
        $bolExpat
    ) {
        $this->fltPKP = (is_numeric($fltPKP)) ? $fltPKP : 0;
        $this->fltPIKP = (is_numeric($fltPIKP)) ? $fltPIKP : 0;
        $this->bolNPWP = $bolNPWP;
        $this->strFamilyStatus = $strFamilyStatus;
        $this->fltPTKP = $this->getTaxReduction($strFamilyStatus);
        $this->fltJamsostekDeduction = $fltJamsostekDeduction;
        $this->fltPensionDeduction = $fltPensionDeduction;
        $this->fltIuran = $fltIuran;
        $this->fltTaxRegular = 0;
        $this->fltTaxIrregular = 0;
        $this->strIDEmployee = $strIDEmployee;
        $this->intTaxMethod = $tax_method;
        $this->intTaxIrregularMethod = $tax_irregular_method;
        $this->arrEmpBaseTaxPaidTaxBefore = $arrEmpBaseTaxPaidTaxBefore;
        $this->calcMonth = $calcMonth;
        $this->calcYear = $calcYear;
        $this->strJoinDate = $strJoinDate;
        $this->strResignDate = $strResignDate;
        $this->intEndOfYearMonth = 12;
        $this->intEndOfMonthDay = 30;
        $this->intJoinDateDay = date('j', strtotime($strJoinDate));
        $this->intJoinDateMonth = date('n', strtotime($strJoinDate));
        $this->intJoinDateYear = date('Y', strtotime($strJoinDate));
        $this->intResignDateDay = ($this->strResignDate != '') ? date('j', strtotime($strResignDate)) : 0;
        $this->intResignDateMonth = ($this->strResignDate != '') ? date('n', strtotime($strResignDate)) : 0;
        $this->intResignDateYear = ($this->strResignDate != '') ? date('Y', strtotime($strResignDate)) : 0;
        //== Inisialisasi untuk konstanta perhitungan==//
        $this->taxableDayUpToEndOfYear = 0;
        $this->taxableDayUpToCurrent = 0;
        $this->taxableMonth = 0;
        $this->currentTaxableMonth = 0;
        $this->intJoinDateYear;
        $this->calcYear;
        $this->calcMonth;
        if ($this->intJoinDateYear < $this->calcYear && $this->strResignDate == '') {
            $this->taxableDayUpToEndOfYear = $this->intEndOfYearMonth * $this->intEndOfMonthDay;
            $this->taxableDayUpToCurrent = $this->calcMonth * $this->intEndOfMonthDay;
            $this->taxableMonth = $this->intEndOfYearMonth;
            $this->currentTaxableMonth = $this->calcMonth;
        } elseif ($this->intJoinDateYear == $this->calcYear && $this->strResignDate == '') {
            $this->taxableDayUpToEndOfYear = ($this->intEndOfYearMonth - $this->intJoinDateMonth) * $this->intEndOfMonthDay + $this->intEndOfMonthDay + 1 - $this->intJoinDateDay;
            $this->taxableDayUpToCurrent = ($this->calcMonth - $this->intJoinDateMonth) * $this->intEndOfMonthDay + $this->intEndOfMonthDay + 1 - $this->intJoinDateDay;
            $this->taxableMonth = $this->intEndOfYearMonth - $this->intJoinDateMonth + 1;
            $this->currentTaxableMonth = $this->calcMonth - $this->intJoinDateMonth + 1;
            if ($this->intJoinDateDay >= JOIN_DATE_LIMIT) //JOIN_DATE_LIMIT in configuration.php, added on 26 March 2015
            {
                $this->taxableMonth -= 1;
                $this->currentTaxableMonth -= 1;
            }
        } elseif ($this->intJoinDateYear < $this->calcYear && $this->strResignDate != '') {
            $this->taxableDayUpToEndOfYear = $this->intResignDateMonth * $this->intEndOfMonthDay;
            $this->taxableDayUpToCurrent = $this->calcMonth * $this->intEndOfMonthDay;
            $this->taxableMonth = $this->intResignDateMonth;
            $this->currentTaxableMonth = $this->calcMonth;
        } elseif ($this->intJoinDateYear == $this->calcYear && $this->strResignDate != '') {
            $this->taxableDayUpToEndOfYear = ($this->intResignDateMonth - $this->intJoinDateMonth) * $this->intEndOfMonthDay + $this->intResignDateDay + 1 - $this->intJoinDateDay;
            $this->taxableDayUpToCurrent = ($this->calcMonth - $this->intJoinDateMonth) * $this->intEndOfMonthDay + $this->intResignDateDay + 1 - $this->intJoinDateDay;
            $this->taxableMonth = $this->intResignDateMonth - $this->intJoinDateMonth + 1;
            $this->currentTaxableMonth = $this->calcMonth - $this->intJoinDateMonth + 1;
            if ($this->intJoinDateDay >= JOIN_DATE_LIMIT) //JOIN_DATE_LIMIT in configuration.php, added on 26 March 2015
            {
                $this->taxableMonth -= 1;
                $this->currentTaxableMonth -= 1;
            }
        }
        $this->intPeriod = $this->taxableMonth;
        if ($bolExpat) {
            $this->taxableDayUpToEndOfYear = 360;
            $this->taxableMonth = 12;
        }
        if ($this->intResignDateYear == $this->calcYear && $this->intResignDateMonth == $this->calcMonth) {
            $this->currentTaxableMonth = $this->taxableMonth;
        }
    }
}

?>
