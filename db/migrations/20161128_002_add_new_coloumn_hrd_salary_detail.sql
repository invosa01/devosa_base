ALTER TABLE public.hrd_salary_detail ADD COLUMN sub_department_code character varying(50);
ALTER TABLE public.hrd_salary_detail ADD COLUMN functional_code character varying(50);
ALTER TABLE public.hrd_salary_detail ADD COLUMN join_date date;
ALTER TABLE public.hrd_salary_detail ALTER COLUMN join_date SET DEFAULT NULL;
ALTER TABLE public.hrd_salary_detail ADD COLUMN resign_date date;
ALTER TABLE public.hrd_salary_detail ALTER COLUMN resign_date SET DEFAULT NULL;
