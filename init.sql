
-- 3. Donner les droits à l'utilisateur "cipen"
GRANT ALL PRIVILEGES ON DATABASE Venus TO cipen;

-- 4. Création des tables

-- Table des catégories
CREATE TABLE IF NOT EXISTS categories (
                                          id SERIAL PRIMARY KEY,
                                          name TEXT NOT NULL,
                                          important INT DEFAULT 0
);

-- Table des projets / cases
CREATE TABLE IF NOT EXISTS projects (
                                        id SERIAL PRIMARY KEY,
                                        title TEXT NOT NULL,
                                        task TEXT NOT NULL,
                                        link TEXT NOT NULL,
                                        hint TEXT NOT NULL,
                                        description TEXT,
                                        category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
                                        start_date DATE,
                                        end_date DATE
);

-- 5. Donner les droits sur les tables à l'utilisateur "cipen"
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO cipen;

-- 6. Pour que cipen ait aussi les droits sur les futures tables
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO cipen;
