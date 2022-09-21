<?php

namespace LeKoala\Admini\Forms;

use LeKoala\ModularBehaviour\ModularBehaviour;
use SilverStripe\Forms\ListboxField;

class BootstrapTagsField extends ListboxField
{
    use ModularBehaviour;

    public function Field($properties = [])
    {
        return $this->getModularField($properties);
    }

    public function getModularSrc()
    {
        return 'https://cdn.jsdelivr.net/npm/bootstrap5-tags@1.4/tags.min.js';
    }
}
