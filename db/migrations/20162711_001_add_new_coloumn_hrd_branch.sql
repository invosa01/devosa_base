ALTER TABLE public.hrd_branch ADD COLUMN umk double precision;
ALTER TABLE public.hrd_branch ALTER COLUMN umk SET DEFAULT 0;
ALTER TABLE public.hrd_branch ADD COLUMN company_id integer;
