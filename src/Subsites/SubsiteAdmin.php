<?php

namespace LeKoala\Admini\Subsites;

use LeKoala\Admini\ModelAdmin;
use LeKoala\Admini\MaterialIcons;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;
use SilverStripe\Subsites\Forms\GridFieldSubsiteDetailForm;

/**
 * Admin interface to manage and create {@link Subsite} instances.
 *
 * @package subsites
 */
class SubsiteAdmin extends ModelAdmin
{
    private static $managed_models = [Subsite::class];

    private static $url_segment = 'subsites';

    private static $menu_title = 'Subsites';

    private static $menu_icon = MaterialIcons::ACCOUNT_TREE;

    public $showImportForm = false;

    private static $tree_class = Subsite::class;

    public function canView($member = null)
    {
        if (!class_exists(SubsiteState::class)) {
            return false;
        }
        return parent::canView($member);
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $grid = $form->Fields()->dataFieldByName(str_replace('\\', '-', Subsite::class));
        if ($grid) {
            $grid->setItemRequestClass(TabulatorSubsiteDetailFormItemRequest::class);
        }

        return $form;
    }
}
