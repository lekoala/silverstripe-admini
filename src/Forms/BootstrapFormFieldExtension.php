<?php

namespace LeKoala\Admini\Forms;

use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\SelectField;

/**
 * @extends SilverStripe\Forms\FormField
 */
class BootstrapFormFieldExtension extends Extension
{
    use BootstrapFormMessage;

    /**
     * Note: classes are applied on the form element, not on the holder
     *
     * @param array $attributes
     * @return void
     */
    public function updateAttributes(&$attributes)
    {
        $class = $attributes['class'] ?? '';
        $o = $this->owner;

        switch (true) {
            case $o instanceof ReadonlyField:
                $class = 'form-control ' . $class;
                break;
            case $o instanceof FormAction:
                $class = 'btn ' . $class;
                if (strpos($class, 'btn-') === false) {
                    $class .= " btn-primary";
                }
                break;
            case $o instanceof CheckboxSetField:
            case $o instanceof OptionsetField:
                $class = 'list-unstyled ' . $class;
                break;
            case $o instanceof CheckboxField:
                $class = 'form-check-input ' . $class;
                break;
            case $o instanceof TabSet:
                $class = 'tab-content ' . $class;
                break;
            case $o instanceof Tab:
                $class = 'tab-pane ' . $class;
                break;
            case $o instanceof SelectField:
                $class = 'form-select ' . $class;
                break;
            case $o instanceof TextField:
            case $o instanceof TextareaField:
                $class = 'form-control ' . $class;
                break;
        }
        $attributes['class'] = trim($class);
    }
}
