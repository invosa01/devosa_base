ALTER TABLE public.hrd_salary_detail ADD COLUMN basic_salary_actual double precision;
ALTER TABLE public.hrd_salary_detail ALTER COLUMN basic_salary_actual SET DEFAULT 0;