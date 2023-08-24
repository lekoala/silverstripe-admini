<?php

namespace LeKoala\Admini\Forms;

trait BootstrapFormMessage
{
    /**
     * @var string[]
     */
    protected static $bootstrapAlertsMap = [
        'success' => 'alert-success',
        'good' => 'alert-success',
        'error' => 'alert-danger',
        'bad' => 'alert-danger',
        'required' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
    ];

    /**
     * Maps a SilverStripe message type to a Bootstrap alert type
     */
    public function getAlertType()
    {
        $type = $this->owner->getMessageType();
        if (!$type) {
            $type = 'info';
        }
        if (isset(self::$bootstrapAlertsMap[$type])) {
            return self::$bootstrapAlertsMap[$type];
        }

        // Fallback to original
        return $type;
    }

    public function BootstrapAlertType()
    {
        return $this->getAlertType();
    }
}
