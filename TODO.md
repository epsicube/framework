## Core

- support modules in root composer.json / or / migrate module declaration into bootstrap/modules.php
- rework process installation / auto-setup
- make:command pour le projet
- migrer hypercore dans la foundation
- obligation de presence d'un core avec default seeder/migration

## Documentation

- reecrire la doc sans 'make:provider', mais avec 'make:module'
- execution platform commands
- administration default url '/epsicube'
- best practice structure module
- expliquer que chaque migration doit etre associé à un core specifique (concept interne)
- AU niveau du schema required nullable default
  - required (impose la présence du champ)
  - default (ne pas pas etre combiner avec required)
  - nullable veut dire: accepte une valeur null (si required, il faut absolument envoyer la valeur null)
  - gestion UndefinedValue sur askPrompt (l'idéal c'est d'unset la propriété du tableau en amont quand inexistante)

- OptionStore:
  - notion de UndefinedValue pour get() pour trigger le fallback schema

## Modules

### Administration

- ~~support du type null/undefined pour les schemas, avec reset~~

### Mailing system

- ajout d'une vue admin pour lister les templates/mailers
- mailer configurable via BO
- option pour desactiver l'ingestion des mailer par default de la config (defaut désactivé)

### MCP server

- renommer vers un truc plus global (eg: AI Manager)
- ~~validation auto sur base de schema des input (moins utile pour output)~~
- [IDEA] generate specific agent with only custom tools

### Execution platform

- ajout d'une vue admin pour lister les activities/workflows (readonly)
- possibilité via action et 'Activities::run' de lancer une activity depuis Admin
- logger les run des activities (configurable au run)
- raccourci 'Action::make' pour une activité spécifique ex: 'RunActivityAction::make()' avec support valeurs pre-definie caché du form
- Validation inputSchema au `run`
- Validation OutputSchema (optionnel ??)

## Schemas

- gestion des enums avec resolver, type enum ( couplé à un type ?? )
- ~~gerer correctement le type null et Undefined~~
- ~~implementer le support de nullable (pas la même chose que required, voir Undefined)~~
- corriger FilamentExporter 'repeater' 'repeatableEntry' qui ne fonctionne pas en mode simple, 
  - utiliser quelque chose de custom via modal/table
~~- cacher hintIcon sur filament quand pas de description~~
- method standard pour recuperer une donnée validée, avec les default, ...
  - mettre en jeu sur execution, mcp tools, ...

## Modules à implementer

- ACL modules

## Potentiel site .com

- Orienté com/impact
- Liste modules / store (y compris core) 
