---
title: "Options Store"
description: "Manage and persist key–value options for modules and global configuration."

---

The Options Store is responsible for storing and retrieving key–value options for both individual modules and the global application scope.

Developers may implement their own storage drivers to customize how global or module-specific options are persisted.

By default, Unigale uses the `options` database table to store all key–value entries.
