-- Table: hrd_salary_cost_sharing

-- DROP TABLE hrd_salary_cost_sharing;

CREATE TABLE hrd_salary_cost_sharing
(
  id serial NOT NULL,
  id_company_collector integer,
  id_company_collectible integer,
  id_employee integer,
  take_home_pay double precision,
  id_salary_master integer,
  CONSTRAINT hrd_salary_cost_sharing_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE hrd_salary_cost_sharing
  OWNER TO postgres;
