ALTER TABLE public.hrd_functional DROP COLUMN flat_late_deduction;
ALTER TABLE public.hrd_functional ADD COLUMN flat_late_deduction double precision;
ALTER TABLE public.hrd_functional ALTER COLUMN flat_late_deduction SET DEFAULT 0;
COMMENT ON COLUMN public.hrd_functional.flat_late_deduction IS '-- Prosentase atau nilai Flat dikali ammount.';
UPDATE hrd_functional SET flat_late_deduction = 0;