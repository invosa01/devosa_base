ALTER TABLE public.hrd_functional ADD COLUMN late_deduction boolean;
ALTER TABLE public.hrd_functional ALTER COLUMN late_deduction SET DEFAULT FALSE;
ALTER TABLE public.hrd_functional ADD COLUMN late_deduction_amount double precision;
ALTER TABLE public.hrd_functional ALTER COLUMN late_deduction_amount SET DEFAULT 0;
ALTER TABLE public.hrd_functional ADD COLUMN flat_late_deduction boolean;
ALTER TABLE public.hrd_functional ALTER COLUMN flat_late_deduction SET DEFAULT TRUE;
COMMENT ON COLUMN public.hrd_functional.flat_late_deduction IS '-- Prosentase atau nilai pas dikali ammount.';
UPDATE hrd_functional SET late_deduction = FALSE , late_deduction_amount = 0, flat_late_deduction = TRUE;