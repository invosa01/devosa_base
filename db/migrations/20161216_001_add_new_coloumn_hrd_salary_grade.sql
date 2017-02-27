ALTER TABLE public.hrd_salary_grade ADD COLUMN ot_platform smallint;
--ALTER TABLE public.hrd_salary_grade ALTER COLUMN ot_platform SET NOT NULL;
ALTER TABLE public.hrd_salary_grade ALTER COLUMN ot_platform SET DEFAULT 0;
COMMENT ON COLUMN public.hrd_salary_grade.ot_platform IS '0 - Tidak Ada Overtime
1 - OT Pemerintah diambil Dari Basic Salary
2 - OT Pemerintah diambil Dari Platform Amount
3 - Flat dari Platform Amount
4 - OT Per Jam';
ALTER TABLE public.hrd_salary_grade ADD COLUMN ot_platform_amount double precision;
ALTER TABLE public.hrd_salary_grade ALTER COLUMN ot_platform_amount SET DEFAULT 0;
--ALTER TABLE public.hrd_salary_grade ALTER COLUMN ot_platform_amount SET NOT NULL;
COMMENT ON COLUMN public.hrd_salary_grade.ot_platform_amount IS 'Amount untuk Base Platform Overtime';
--UPDATE hrd_salary_grade SET ot_platform =0, ot_platform_amount =0