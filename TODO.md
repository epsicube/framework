## Core

- rework process installation / auto-setup
- migrer hypercore dans la foundation
- obligation de presence d'un core avec default seeder/migration
- ajout de sous-groupe pour options ou (module,sous-groupe,key,value)
- typed schema resolution (like ExecutionSettingsGeneral::class) (see spatie settings)

## Documentation

- best practice structure module
- expliquer que chaque migration doit etre associé à un core specifique (concept interne)


- custom translator to handle ":%variable" with '#' to get locale formatted number
  - syntaxe ":%variable:precision:maxprecision"
    
    Decorates the translator to format numbers using the `:#key:precision:maxPrecision` syntax.
    
    @example ":%amount" -> "1,234.57" (Standard localized format)
    @example ":%amount:0" -> "1,235" (Fixed precision, no decimals)
    @example ":%amount:3" -> "1,234.568" (Fixed precision, 3 decimals)
    @example ":%amount::2" -> "1,234.57" (Max precision only: up to 2 decimals)
    @example ":%amount:2:4" -> "1,234.5678" (Range: between 2 and 4 decimals)
    @example ":%amount:0\:20" -> "1,234:20" (Escapes the colon)
        


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
  

# Module Form
- système de fragment
  - par exemple les champs seront mappé vers un prospect
    - firstname
    - lastname

## Adminisration

- how to extend panel
- applicaitonGroup

## MCP Server

## JSON-RPC Server

## Modules à implementer

- ACL modules

## Potentiel site .com

- Orienté com/impact
- Liste modules / store (y compris core) 
