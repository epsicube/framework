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

- ajout d'une vue admin pour lister les templates/mailers (pourquoi pas action pour envoi)
- mailer configurable via BO (DB)

### MCP server

- renommer vers un truc plus global (eg: AI Manager)
- [IDEA] generate specific agent with only custom tools

### Execution platform

- ajout d'une vue admin pour lister les activities/workflows (readonly)
- logger les run des activities (configurable au run)
- Searchable enum (au moins filament et cli) pour les relations par exemple

## Schemas

- Terminer array et object avec uniqueItems et dynamicProperties
- Implementer conditionalProperty (
  ```php
  ConditionalProperty::make()
      ->when('field','value', Property::make())
      ->when('field','value', Property::make())
  
  ```

## Modules à implementer

- ACL modules

## Potentiel site .com

- Orienté com/impact
- Liste modules / store (y compris core) 
