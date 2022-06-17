<?php

namespace LeKoala\Admini\Forms;

use LeKoala\Admini\LeftAndMain;
use SilverStripe\Core\Extension;
use LeKoala\Admini\MaterialIcons;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Control\Controller;

/**
 * Improve how actions are rendered if you are using the cms-actions module
 */
class AdminiCmsActionsExtension extends Extension
{
    /**
     * @param FieldList||FormAction[] $actions
     * @return void
     */
    public function onAfterUpdateCMSActions(FieldList $actions)
    {
        $controller = Controller::curr();

        // Avoid side effects on regular admin
        if (!$controller instanceof LeftAndMain) {
            return;
        }

        /** @var \SilverStripe\Forms\Tab $dropUpContainer */
        $dropUpContainer =  $actions->fieldByName('ActionMenus.MoreOptions');

        // Replace with our own logic
        if ($dropUpContainer) {
            $group = new AdminiDropUpField($dropUpContainer->getChildren());
            $actions->insertBefore('RightGroup', $group);
            $actions->removeByName('ActionMenus');
        }

        $this->updateStyle($actions);
    }

    protected function updateStyle($actions)
    {
        /** @var FormAction $action */
        foreach ($actions as $action) {
            if ($action->hasMethod("getChildren")) {
                foreach ($action->getChildren() as $child) {
                    $this->updateStyle($child);
                }
                continue;
            }
            if ($action->hasExtraClass('btn-outline-danger')) {
                $action->removeExtraClass('btn-outline-danger');
                $action->addExtraClass('btn-danger');
                if ($action->hasExtraClass('font-icon-trash-bin')) {
                    $action->removeExtraClass('font-icon-trash-bin');
                    $action->setIcon(MaterialIcons::DELETE);
                }
            }
            if ($action->hasExtraClass('btn-outline-primary')) {
                $action->removeExtraClass('btn-outline-primary');
                $action->addExtraClass('btn-outline-success');
                if ($action->hasExtraClass('font-icon-level-up')) {
                    $action->removeExtraClass('font-icon-level-up');
                    $action->setIcon(MaterialIcons::KEYBOARD_RETURN);
                }
                if ($action->hasExtraClass('font-icon-angle-double-left')) {
                    $action->removeExtraClass('font-icon-angle-double-left');
                    $action->setIcon(MaterialIcons::NAVIGATE_BEFORE);
                }
                if ($action->hasExtraClass('font-icon-angle-double-right')) {
                    $action->removeExtraClass('font-icon-angle-double-right');
                    $action->addExtraClass('btn-flex btn-icon-end');
                    $action->setIcon(MaterialIcons::NAVIGATE_NEXT);
                }
            }
            if ($action->hasExtraClass('btn-primary')) {
                $action->removeExtraClass('btn-primary');
                $action->addExtraClass('btn-success');
            }
        }
    }
}
