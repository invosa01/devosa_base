CREATE TABLE hrd_employee_cost_sharing
(
  id serial NOT NULL,
  id_company integer,
  cost_percentage integer,
  id_employee integer,
  CONSTRAINT hrd_employee_cost_sharing_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE hrd_employee_cost_sharing
  OWNER TO postgres;
