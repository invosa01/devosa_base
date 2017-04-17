-- DROP TABLE hrd_leave_level_quota;

CREATE TABLE hrd_leave_level_quota
(
  id serial NOT NULL,
  level_code character varying(255), -- Level in this table is different from level in position/grade/functional
  max_quota integer, -- Maximum leave quota per level
  CONSTRAINT hrd_level_leave_quota_pk PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE hrd_leave_level_quota
  OWNER TO postgres;
COMMENT ON COLUMN hrd_leave_level_quota.level_code IS 'Level in this table is different from level in position/grade/functional';
COMMENT ON COLUMN hrd_leave_level_quota.max_quota IS 'Maximum leave quota per level';

