-- DROP TABLE setting_overtime;

CREATE TABLE setting_overtime
(
  id serial NOT NULL,
  code character varying(255),
  round_up boolean, -- true = round up; false = round down
  value integer, -- in minutes
  CONSTRAINT setting_overtime_pk PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE setting_overtime
  OWNER TO postgres;
COMMENT ON COLUMN setting_overtime.round_up IS 'true = round up; false = round down';
COMMENT ON COLUMN setting_overtime.value IS 'in minutes';



INSERT INTO setting_overtime (code, value, round_up) VALUES ('ot_in_round_up', 0, false);
INSERT INTO setting_overtime (code, value, round_up) VALUES ('ot_out_round_up', 0, false)