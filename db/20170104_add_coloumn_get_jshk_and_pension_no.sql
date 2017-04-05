-- Column: get_jshk

-- ALTER TABLE public.hrd_employee DROP COLUMN get_jshk;

ALTER TABLE public.hrd_employee ADD COLUMN get_jshk smallint;
ALTER TABLE public.hrd_employee ALTER COLUMN get_jshk SET DEFAULT 1;

-- Column: pension_no

-- ALTER TABLE public.hrd_employee DROP COLUMN pension_no;

ALTER TABLE public.hrd_employee ADD COLUMN pension_no character varying(100);