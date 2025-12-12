## Core
- rework process installation / auto-setup
- migrer hypercore dans la foundation
- obligation de presence d'un core avec default seeder/migration

## Documentation

- best practice structure module
- expliquer que chaque migration doit etre associé à un core specifique (concept interne)

- Execution Platform
  - Ecrire la doc
  - les commands `activities:list`, `workflows:list`, `activities:run`
  - `ActivityAction` pour l'admin
## Modules

### Mailing system

- ajout d'une vue admin pour lister les templates/mailers
- mailer configurable via BO (DB)

### MCP server

- renommer vers un truc plus global (eg: AI Manager)
- [IDEA] generate specific agent with only custom tools

### Execution platform

- ajout d'une vue admin pour lister les activities/workflows (readonly)
- logger les run des activities (configurable au run)

## Schemas

- gestion des enums avec resolver, type enum ( couplé à un type ?? )
- corriger FilamentExporter 'repeater' 'repeatableEntry' qui ne fonctionne pas en mode simple, 
  - utiliser quelque chose de custom via modal/table

## Modules à implementer

- ACL modules

## Potentiel site .com

- Orienté com/impact
- Liste modules / store (y compris core) 
