USE projet;

ALTER TABLE projet
  ADD COLUMN description TEXT NULL AFTER categorie;
