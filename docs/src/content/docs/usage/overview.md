---
title: "Overview"
description: "Explore modules and their management."
sidebar:
    order: 1
---

Each module represents a distinct set of functionality and acts as a self-contained component within the system. Modules can include services, integrations, workflows, user interfaces, or configuration logic.

Modules are implemented as Laravel **[Service Providers](https://laravel.com/docs/providers)**, meaning only enabled modules are loaded and can register bindings, routes, or event listeners.

## Key Benefits

* **Modularity** — Break applications into reusable, independent units.
* **Scalability** — Add new features incrementally by installing modules.
* **Dynamic Management** — Enable, disable, or extend modules without modifying core code.
* **Isolation** — Each module is self-contained, reducing the risk of conflicts between features, while still allowing controlled communication through the **[Integrations](/writing-module/declare-integrations/)** layer.

## Support

Since a module is simply a Service Provider, any existing Laravel package can be packaged as a module, allowing you to extend its capabilities and integrate it into a modular system.
