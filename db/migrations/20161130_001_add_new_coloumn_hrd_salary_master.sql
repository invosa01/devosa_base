ALTER TABLE public.hrd_salary_master ADD COLUMN flag smallint;
ALTER TABLE public.hrd_salary_master ALTER COLUMN flag SET DEFAULT 0;
UPDATE hrd_salary_master SET flag = 0;
UPDATE hrd_salary_master SET flag = 1 WHERE id IN (24, 26);