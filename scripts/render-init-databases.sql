-- Run once on the Render Postgres instance (idf-mobilites-db).
-- Same server, separate logical databases (required: Symfony, Umami and GlitchTip
-- cannot share one database because of table name conflicts, e.g. "user").
--
-- Render Dashboard → idf-mobilites-db → Connect → PSQL, then paste:

CREATE DATABASE umami;
CREATE DATABASE glitchtip;
