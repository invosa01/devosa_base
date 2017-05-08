ALTER TABLE hrd_employee ADD COLUMN leave_level_code character varying(50);

ALTER TABLE hrd_employee
  ADD CONSTRAINT hrd_employee_fk_hrd_leave_level_employee FOREIGN KEY (leave_level_code)
      REFERENCES hrd_leave_level_quota (level_code) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE hrd_leave_level_quota
  ADD CONSTRAINT level_code_unique_constraint UNIQUE(level_code);