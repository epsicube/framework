---
title: "Execution Platform"
description: ""
---

The Execution Platform allows modules to define and run Activities and Workflows with robust tracking and retry logic.

## Concepts

### Activities

Atomic actions executed individually or chained.

### Workflows

Sequences of Activities executed with full state tracking.

## Features

* Extendable by any module
* Automatic execution tracking
* Database-backed history of all runs
* Transparent error handling and retry strategies

## Planned Features

* Workflow execution through:

    * **Temporal.io**
    * **Laravel Workflow**
    * Native synchronous driver (`sync`)
