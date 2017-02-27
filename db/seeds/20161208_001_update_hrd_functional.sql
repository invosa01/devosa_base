UPDATE hrd_functional SET late_deduction = TRUE , late_deduction_amount = 25000 ,  flat_late_deduction = 50 WHERE functional_code = 'MARK-ASSMAN-ICW';
UPDATE hrd_functional SET late_deduction = TRUE , late_deduction_amount = 2500 ,  flat_late_deduction = 0 WHERE functional_code = 'LO-ICW';

UPDATE hrd_employee SET functional_code = 'LO-ICW' WHERE employee_id = 'A0001';
UPDATE hrd_employee SET functional_code = 'MARK-ASSMAN-ICW' WHERE employee_id = 'A0003';
