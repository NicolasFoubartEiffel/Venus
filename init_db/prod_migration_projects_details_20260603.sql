BEGIN;

CREATE OR REPLACE FUNCTION pg_temp.venus_html_escape(value text)
RETURNS text
LANGUAGE sql
IMMUTABLE
AS $$
    SELECT replace(
        replace(
            replace(COALESCE(value, ''), '&', '&amp;'),
            '<',
            '&lt;'
        ),
        '>',
        '&gt;'
    )
$$;

CREATE OR REPLACE FUNCTION pg_temp.venus_normalize_title(value text)
RETURNS text
LANGUAGE sql
IMMUTABLE
AS $$
    SELECT lower(
        translate(
            btrim(COALESCE(value, '')),
            'ABCDEFGHIJKLMNOPQRSTUVWXYZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïòóôõöùúûüýÿ',
            'abcdefghijklmnopqrstuvwxyzaaaaaaceeeeiiiiooooouuuuyaaaaaaceeeeiiiiooooouuuuyy'
        )
    )
$$;

CREATE OR REPLACE FUNCTION pg_temp.venus_linkify_text_urls(html text)
RETURNS text
LANGUAGE plpgsql
AS $$
DECLARE
    part text;
    output text := '';
    inside_anchor boolean := false;
BEGIN
    IF html IS NULL OR html = '' THEN
        RETURN html;
    END IF;

    FOR part IN
        SELECT token[1]
        FROM regexp_matches(html, '(<[^>]+>|[^<]+)', 'g') AS matches(token)
    LOOP
        IF part ~* '^<a(\s|>)' THEN
            inside_anchor := true;
            output := output || part;
        ELSIF part ~* '^</a\s*>' THEN
            inside_anchor := false;
            output := output || part;
        ELSIF part LIKE '<%' THEN
            output := output || part;
        ELSIF inside_anchor THEN
            output := output || part;
        ELSE
            part := regexp_replace(
                part,
                '(https?://[^[:space:]<>"'']*[^[:space:]<>"''\.,;:!\?\)\]\}])',
                '<a href="\1" target="_blank" rel="noopener noreferrer">\1</a>',
                'gi'
            );

            part := regexp_replace(
                part,
                '(^|[^"/=])(www\.[^[:space:]<>"'']*[^[:space:]<>"''\.,;:!\?\)\]\}])',
                '\1<a href="https://\2" target="_blank" rel="noopener noreferrer">\2</a>',
                'gi'
            );

            part := regexp_replace(
                part,
                '(^|[^[:alnum:]_./"''=:-])([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})',
                '\1<a href="mailto:\2">\2</a>',
                'gi'
            );

            output := output || part;
        END IF;
    END LOOP;

    RETURN output;
END;
$$;

UPDATE projects
SET contact_details =
    '<p>' || pg_temp.venus_html_escape(btrim(contact_title)) || '</p>' ||
    COALESCE(contact_details, '')
WHERE NULLIF(btrim(contact_title), '') IS NOT NULL
  AND pg_temp.venus_normalize_title(contact_title) NOT IN (
      'non renseigne',
      'contact',
      'contacts',
      'ressource',
      'ressources',
      'ressources documentaires',
      'description avancee'
  )
  AND COALESCE(contact_details, '') NOT LIKE
      '<p>' || pg_temp.venus_html_escape(btrim(contact_title)) || '</p>%';

UPDATE projects
SET resources_details =
    '<p>' || pg_temp.venus_html_escape(btrim(resources_title)) || '</p>' ||
    COALESCE(resources_details, '')
WHERE NULLIF(btrim(resources_title), '') IS NOT NULL
  AND pg_temp.venus_normalize_title(resources_title) NOT IN (
      'non renseigne',
      'contact',
      'contacts',
      'ressource',
      'ressources',
      'ressources documentaires',
      'description avancee'
  )
  AND COALESCE(resources_details, '') NOT LIKE
      '<p>' || pg_temp.venus_html_escape(btrim(resources_title)) || '</p>%';

UPDATE projects
SET description_details =
    '<p>' || pg_temp.venus_html_escape(btrim(description_title)) || '</p>' ||
    COALESCE(description_details, '')
WHERE NULLIF(btrim(description_title), '') IS NOT NULL
  AND pg_temp.venus_normalize_title(description_title) NOT IN (
      'non renseigne',
      'contact',
      'contacts',
      'ressource',
      'ressources',
      'ressources documentaires',
      'description avancee'
  )
  AND COALESCE(description_details, '') NOT LIKE
      '<p>' || pg_temp.venus_html_escape(btrim(description_title)) || '</p>%';

UPDATE projects
SET contact_details = pg_temp.venus_linkify_text_urls(contact_details)
WHERE contact_details IS NOT NULL
  AND contact_details ~* '(https?://|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})';

UPDATE projects
SET resources_details = pg_temp.venus_linkify_text_urls(resources_details)
WHERE resources_details IS NOT NULL
  AND resources_details ~* '(https?://|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})';

UPDATE projects
SET description_details = pg_temp.venus_linkify_text_urls(description_details)
WHERE description_details IS NOT NULL
  AND description_details ~* '(https?://|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})';

ALTER TABLE projects
    DROP COLUMN IF EXISTS contact_title,
    DROP COLUMN IF EXISTS resources_title,
    DROP COLUMN IF EXISTS description_title;

COMMIT;
