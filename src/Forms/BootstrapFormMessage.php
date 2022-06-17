<?php

namespace LeKoala\Admini\Forms;

trait BootstrapFormMessage
{
    /**
     * @var string[]
     */
    protected static $bootstrapAlertsMap = [
        'good' => 'alert-success',
        'bad' => 'alert-danger',
        'required' => 'alert-danger',
        'warning' => 'alert-warning',
    ];

    /**
     * Maps a SilverStripe message type to a Bootstrap alert type
     */
    public function getAlertType()
    {
        $type = $this->owner->getMessageType();

        if (isset(self::$bootstrapAlertsMap[$type])) {
            return self::$bootstrapAlertsMap[$type];
        }

        // Fallback to original
        return $type;
    }
}
