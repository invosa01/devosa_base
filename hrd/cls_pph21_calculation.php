<?php

/*
Class untuk menghitung pph21
*/

class countPPH21
{

	/*
	* @param $nonTaxableIncomeArray : Pada saat di object di instansiasi jika variable $nonTaxableIncomeArray
	*	tidak di define maka gunakan default value, berguna jika ada perubahan
	*	aturan pph21 dengan catatan struktur array sama yaitu :
	*	$statuspernikahan => $nilainontax
	* @param $filterAnnualizeIncomeTaxNPWP : jika $filterAnnualizeIncomeTaxNPWP tidak di define maka akan menggunakan default value,
	*	struktur array $filterAnnualizeIncomeTaxNPWP 
	* index 
	*		Object->value				:	Nilai pembanding
	*		Object->multiple		: Pengali taxable net Income
	*		Object->subtrahend 	: Pengurang hasil kali taxable net Income dengan Object->multiple
	*	@param $filterAnnualizeIncomeTaxNonNPWP : sama seperti $filterAnnualizeIncomeTaxNPWP	untuk karyawan tanpa npwp
	* @param $roundDownNetIncometo : untuk pembulatan taxable net income sampai satuan tertentu, default 3
	* @param $totalMonth : Total bulan , default 13
	*/
	function __construct(
			$totalMonth = 12,
			$nonTaxableIncomeArray = null,
			$filterAnnualizeIncomeTaxNPWP = null,
			$filterAnnualizeIncomeTaxNonNPWP = null,
			$roundDownNetIncometo = 3,
			$filterAnnualizeIncomeTaxGrossUp = null
	) {
		if (is_null($nonTaxableIncomeArray)) {
			$this->nonTaxableIncomeArray = [
					'TK' => 24300000,
					'K0' => 26325000,
					'K1' => 28350000,
					'K2' => 30375000,
					'K3' => 32400000,
			];
		} else {
			$this->nonTaxableIncomeArray = $nonTaxableIncomeArray;
		}
		if (is_null($filterAnnualizeIncomeTaxNPWP)) {
			$filter1 = new stdClass();
			$filter1->value = 50000000;
			$filter1->multiple = 0.05;
			$filter1->subtrahend = 0;
			$filter2 = new stdClass();
			$filter2->value = 250000000;
			$filter2->multiple = 0.15;
			$filter2->subtrahend = 5000000;
			$filter3 = new stdClass();
			$filter3->value = 500000000;
			$filter3->multiple = 0.25;
			$filter3->subtrahend = 30000000;
			$filter4 = new stdClass();
			$filter4->value = 10000000000;
			$filter4->multiple = 0.3;
			$filter4->subtrahend = 55000000;
			$this->filterAnnualizeIncomeTaxNPWP = [
					$filter1,
					$filter2,
					$filter3,
					$filter4
			];
		} else {
			$this->filterAnnualizeIncomeTaxNPWP = $filterAnnualizeIncomeTaxNPWP;
		}
		if (is_null($filterAnnualizeIncomeTaxNonNPWP)) {
			$filter1 = new stdClass();
			$filter1->value = 50000000;
			$filter1->multiple = 0.06;
			$filter1->subtrahend = 0;
			$filter2 = new stdClass();
			$filter2->value = 250000000;
			$filter2->multiple = 0.18;
			$filter2->subtrahend = 6000000;
			$filter3 = new stdClass();
			$filter3->value = 500000000;
			$filter3->multiple = 0.3;
			$filter3->subtrahend = 36000000;
			$filter4 = new stdClass();
			$filter4->value = 10000000000;
			$filter4->multiple = 0.36;
			$filter4->subtrahend = 66000000;
			$this->filterAnnualizeIncomeTaxNonNPWP = [
					$filter1,
					$filter2,
					$filter3,
					$filter4
			];
		} else {
			$this->filterAnnualizeIncomeTaxNonNPWP = $filterAnnualizeIncomeTaxNonNPWP;
		}
		$this->roundDownto = $roundDownNetIncometo;
		$this->totalMonth = $totalMonth;
		if (is_null($filterAnnualizeIncomeTaxGrossUp)) {
			$filter1 = new stdClass();
			$filter1->value = 47500000;
			$filter1->multiple = 5 / 95;
			$filter1->subtrahend = 0;
			$filter1->amount_added = 0;
			$filter2 = new stdClass();
			$filter2->value = 217500000;
			$filter2->multiple = 15 / 85;
			$filter2->subtrahend = 47500000;
			$filter2->amount_added = 2500000;
			$filter3 = new stdClass();
			$filter3->value = 405000000;
			$filter3->multiple = 25 / 75;
			$filter3->subtrahend = 217500000;
			$filter3->amount_added = 32500000;
			$filter4 = new stdClass();
			$filter4->value = 10000000000;
			$filter4->multiple = 30 / 70;
			$filter4->subtrahend = 405000000;
			$filter4->amount_added = 95000000;
			$this->filterAnnualizeIncomeTaxGrossUp = [
					$filter1,
					$filter2,
					$filter3,
					$filter4
			];
		} else {
			$this->filterAnnualizeIncomeTaxGrossUp = $filterAnnualizeIncomeTaxGrossUp;
		}
	}

	/* Fungsi mengkalkulasi net income bulanan menjadi setahun
	*	@param $netIncome : net income bulanan	
	*/
	function anualizedIncome($netIncome)
	{
		return $netIncome * $this->totalMonth;
	}

	/*
	* Fungsi untuk membulatkan kebawah sebanyak satuan yang diinginkan
	* @param $value 	:	Nilai yang ingin di bulatkan
	* @param $downto	: satuan yang diinginkan
	*/

	function calculateIncomeTaxAnnualized($taxableincome = 0, $npwp = true)
	{
		$incometaxannualize = 0;
		if ($taxableincome > 0) {
			if ($npwp) {
				if (!is_null($this->filterAnnualizeIncomeTaxNPWP)) {
					for ($i = 0; $i < count($this->filterAnnualizeIncomeTaxNPWP); $i++) {
						$value = $this->filterAnnualizeIncomeTaxNPWP[$i];
						if ($i == 0) {
							if ($taxableincome <= $value->value && $taxableincome > 0) {
								$incometaxannualize = ($taxableincome * $value->multiple) - $value->subtrahend;
							}
						} else {
							if ($taxableincome <= $value->value && $taxableincome > $this->filterAnnualizeIncomeTaxNPWP[($i - 1)]->value) {
								$incometaxannualize = ($taxableincome * $value->multiple) - $value->subtrahend;
							}
						}
					}
					return $incometaxannualize;
				} else {
					return 'You have to define annualize income tax npwp filter first';
				}
			} else {
				if (!is_null($this->filterAnnualizeIncomeTaxNonNPWP)) {
					for ($i = 0; $i < count($this->filterAnnualizeIncomeTaxNonNPWP); $i++) {
						$value = $this->filterAnnualizeIncomeTaxNonNPWP[$i];
						if ($i == 0) {
							if ($taxableincome <= $value->value && $taxableincome > 0) {
								$incometaxannualize = ($taxableincome * $value->multiple) - $value->subtrahend;
							}
						} else {
							if ($taxableincome <= $value->value && $taxableincome > $this->filterAnnualizeIncomeTaxNonNPWP[($i - 1)]->value) {
								$incometaxannualize = ($taxableincome * $value->multiple) - $value->subtrahend;
							}
						}
					}
					return $incometaxannualize;
				} else {
					return 'You have to define annualize income tax non npwp filter first';
				}
			}
		}
		return $incometaxannualize;
	}

	/*
	*	Fungsi untuk mengambil nilai non taxable income berdasarkan status pernikahan dan jumlah anak
	* @param $maritalStatus 	:	status pernikahan
	*/

	function calculateMonthlyIncomeTax($incomeTaxAnnualized)
	{
		return $this->roundDown($incomeTaxAnnualized / $this->totalMonth);
	}

	/*
	*	Fungsi untuk menghitung taxable net income berdasarkan status pernikahan + jumlah anak dan net income nya
	* @param $maritalStatus 	:	status pernikahan
	* @param $netIncomeAnnualize 	:	Net Income
	*/

	function countTaxableNetIncome($maritalStatus = null, $netIncomeAnnualize = null)
	{
		if (!is_null($maritalStatus) && !is_null($netIncomeAnnualize)) {
			$nontaxableincome = $this->getNonTaxableIncome($maritalStatus);
			$taxableincome = $netIncomeAnnualize - $nontaxableincome;
			return $this->roundDown($taxableincome, $this->roundDownto);
		} else {
			return 'Marital status/Net Income should not be null/empty';
		}
	}

	/*
	*	Fungsi untuk menghitung income tax annualize menggunakan npwp atau tidak
	* @param $taxableincome 	:	nilai yang dapat dihitung pajaknya (gunakan fungsi countTaxableNetIncome untuk perhitungannya)
	* @param $npwp 	:	menggunakan npwp atau tidak (true or false)
	*/

	function getNonTaxableIncome($maritalStatus = null)
	{
		if (!is_null($maritalStatus)) {
			if (!is_null($this->nonTaxableIncomeArray)) {
				$nontaxableincome = 0;
				foreach ($this->nonTaxableIncomeArray as $key => $value) {
					if ($maritalStatus == $key) {
						$nontaxableincome = $value;
					}
				}
				return $nontaxableincome;
			} else {
				return 'have to define non taxable income array first';
			}
		} else {
			return 'Marital status should not be null/empty';
		}
	}

	/*
	*	Fungsi untuk menghitung monthly tax
	* @param $incomeTaxAnnualized :	annualized tax income
	*/

	function roundDown($value, $downto = 0)
	{
		$parameter = pow(10, $downto);
		return floor($value / $parameter) * $parameter;
	}
}

?>