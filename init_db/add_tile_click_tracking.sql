BEGIN;

CREATE TABLE IF NOT EXISTS tile_click_tracking (
    tile_id integer NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    tab_1 boolean NOT NULL DEFAULT false,
    tab_2 boolean NOT NULL DEFAULT false,
    tab_3 boolean NOT NULL DEFAULT false,
    clicked_at timestamp without time zone NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_tile_click_tracking_tile_id
    ON tile_click_tracking (tile_id);

CREATE INDEX IF NOT EXISTS idx_tile_click_tracking_clicked_at
    ON tile_click_tracking (clicked_at);

COMMIT;
