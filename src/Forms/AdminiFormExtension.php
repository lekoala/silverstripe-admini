<?php

namespace LeKoala\Admini\Forms;

use SilverStripe\Core\Extension;

/**
 * @extends SilverStripe\Forms\Form
 */
class AdminiFormExtension extends Extension
{
    use BootstrapFormMessage;

    public function RightActions()
    {
        return $this->owner->Actions()->fieldByName('RightGroup');
    }
}
