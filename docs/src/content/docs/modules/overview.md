---
title: "Overview"
description: "Explore modules and their management."
sidebar:
  order: 1
---

Modules are self-contained units that encapsulate features, services, and configurations.

They can be **enabled**, **disabled**, and **extended dynamically**, providing a modular and flexible system architecture.

Modules can be managed via the CLI or through the administration panel. Several Artisan commands are available to check status, enable, or disable modules.

Because each module is implemented as a Laravel `ServiceProvider`, only enabled modules are booted and can register bindings.
