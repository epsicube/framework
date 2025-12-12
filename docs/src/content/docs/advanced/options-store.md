---
title: "Options Store"
description: "Manage and persist key–value options for modules and global configuration."

---

The Options Store is responsible for storing and retrieving key–value options for both individual modules and the global application scope.

Developers may implement their own storage drivers to customize how global or module-specific options are persisted.

By default, Epsicube uses the `options` database table to store all key–value entries.



## OptionStore and UndefinedValue

The `OptionStore` uses a special internal marker called **UndefinedValue**.
This value is returned by `get()` when an optional property has not been provided.

UndefinedValue enables the engine to:

- Differentiate missing fields from fields explicitly set to `null`
- Apply defaults or schema-level fallbacks correctly
- Preserve consistent behavior across all property types

You generally do not manipulate UndefinedValue directly, but it is fundamental to the schema resolution process.
