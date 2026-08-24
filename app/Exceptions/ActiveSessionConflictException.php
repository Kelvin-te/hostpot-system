<?php

namespace App\Exceptions;

use App\Models\HotspotSession;
use Exception;

/**
 * Thrown when a device with an existing active session attempts to start a
 * session for a different package. We never silently create a second
 * concurrent active session for the same device.
 */
class ActiveSessionConflictException extends Exception
{
    public HotspotSession $existingSession;

    public function __construct(HotspotSession $existingSession)
    {
        $this->existingSession = $existingSession;

        parent::__construct('Device already has an active session on a different package.');
    }
}
