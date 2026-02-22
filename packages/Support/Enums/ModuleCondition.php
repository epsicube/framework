<?php

declare(strict_types=1);

namespace Epsicube\Support\Enums;

enum ModuleCondition
{
    /**
     * The module must exist in the system.
     *
     * Use this when you need access to the module's files or classes,
     * regardless of whether it is currently booting its logic.
     */
    case PRESENT;

    /**
     * The module must be strictly absent from the system.
     *
     * Use this to prevent installation conflicts with incompatible modules.
     */
    case ABSENT;

    /**
     * The module must be installed and turned ON.
     *
     * Use this for hard dependencies where you need the module's services.
     */
    case ENABLED;

    /**
     * The module must be installed but turned OFF.
     *
     * Use this when a module's presence is required for static analysis or
     * assets, but its execution would interfere with your logic.
     */
    case DISABLED;

    /**
     * Alias for ENABLED.
     */
    case ACTIVE;

    /**
     * The module must not be running.
     *
     * This is satisfied if the module is either missing OR disabled.
     */
    case INACTIVE;
}
