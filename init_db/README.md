# Initialisation base de donnees

Ce dossier regroupe les scripts SQL a conserver avec le projet.

Les scripts temporaires prepares sur le serveur peuvent rester dans `/tmp/`
pendant les tests, puis etre copies ici uniquement quand ils sont valides.

Scripts principaux :

- `gpt_venus_preprod_ready.sql` : dump complet transforme pour recreer une preprod.
- `prod_migration_projects_details_20260603.sql` : migration a appliquer sur une prod existante.

Scripts historiques conserves au cas ou :

- `init.sql`
- `venus.sql`
