---
title: "Hyper-core"
description: ""
slug: "hypercore"
draft: true
---

# Multi-Core Architecture

Epsicube can manage multiple “cores” within the same application.
This experimental feature allows the primary application to spawn, configure, and control subordinate applications
dynamically.

## Capabilities

* Create and run secondary applications on-the-fly
* Inject modules into child cores programmatically
* Isolate or share configuration and options
* Mark certain modules as **Must-Use**, preventing accidental deactivation
