# Base de donnees

Scripts suivis par Git pour deployer Venus sur une base vide.

## Deploiement a vide

```bash
psql -d venus -f database/schema.sql
```

`schema.sql` cree uniquement la structure.

Aucune donnee n'est versionnee dans ce dossier : pas de tuiles, pas de favoris, pas de mails, pas de statistiques, pas de categories en dur.

Les donnees de reference comme les categories doivent etre ajoutees par un script de donnees specifique a l'environnement, non versionne, ou via une fonctionnalite d'administration dediee.

## Donnees locales et dumps

Le dossier `init_db/` est ignore par Git et doit rester reserve aux dumps locaux, exports ponctuels et scripts de test non rejouables.
