---
title: "Lifecycle"
description: "Understand the lifecycle phases of Epsicube modules and cores."
---

Epsicube introduces a multi-stage lifecycle for both modules and cores. Each module goes through the following phases:

- **Discovered** — Detected and indexed by the framework.
- **Loaded** — Prepared for registration and booting.

When a module is enabled, it proceeds to:

- **Registered** — Services, bindings, and integrations are registered.
- **Booted** — Routes, resources, and runtime logic are booted and made available to the application.

```mermaid
graph TD
%% Top: Module Preparation
    subgraph Top["Module Preparation"]
        MODS[Collect enabled modules]
        BOOTSTR[Collect bootstrappers]
        OPTIONS[Collect module options]
        INTEGR[Collect integrations]
        MODS --> OPTIONS
        MODS --> INTEGR
        MODS --> BOOTSTR
    end

%% Middle: Laravel Lifecycle
    subgraph Middle["Laravel Lifecycle"]
        APP[Application] --> BOOT[Bootstrapping]  --> REG[Register Service Providers] --> REGOPT[Inject options] --> REGMOD[Register Modules] --> BOOTSP[Boot Service Providers]  --> REGINT[Run integrations]  --> MID[Handle Request / Command]
    end

%% Connections between lines
    BOOT --> MODS
    BOOTSTR --> BOOT
    OPTIONS --> REGOPT
    INTEGR --> REGINT
    MODS --> REGMOD
```

```mermaid
flowchart LR
    subgraph Modules["Modules / Preparation"]
        MODS[Collect enabled modules] --> BOOTSTR[Collect bootstrappers]
        MODS --> OPTIONS[Collect module options]
        MODS --> INTEGR[Collect module integrations]
    end

    subgraph Laravel["Laravel Lifecycle"]
        APP[Application Bootstrapping] --> REG[Register Service Providers]
        REG --> REGOPT[Inject module options]
        REGOPT --> REGMOD[Register Modules]
        REGMOD --> BOOTSP[Boot Service Providers]
        BOOTSP --> REGINT[Run module integrations]
        REGINT --> HANDLE[Handle Request / Command]
    end

    %% Cross-lane connections
    BOOTSTR --> APP
    OPTIONS --> REGOPT
    INTEGR --> REGINT
    MODS --> REGMOD

```
