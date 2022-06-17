<?php

namespace LeKoala\Admini;

use RuntimeException;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Control\HTTPResponse;

class CMSProfileController extends LeftAndMain
{
    private static $url_segment = 'myprofile';

    private static $menu_title = 'My Profile';

    private static $required_permission_codes = false;

    private static $tree_class = Member::class;

    private static $menu_icon = MaterialIcons::PERSON;

    private static $ignore_menuitem = true; // access through custom ui

    public function getEditForm($id = null, $fields = null)
    {
        $user = Security::getCurrentUser();
        if (!$user) {
            throw new RuntimeException("No user");
        }
        $form = parent::getEditForm($user->ID, $fields);

        if (!$form) {
            throw new RuntimeException("No form for '$id'");
        }
        if ($form instanceof HTTPResponse) {
            return $form;
        }

        $form->Fields()->removeByName('LastVisited');
        $form->Fields()->push(new HiddenField('ID', null, Security::getCurrentUser()->ID));

        $form->Actions()->unshift(
            FormAction::create('save', _t('LeKoala\\Admini\\LeftAndMain.SAVE', 'Save'))
                ->setIcon(MaterialIcons::DONE)
                ->addExtraClass('btn-outline-success')
                ->setUseButtonTag(true)
        );

        $form->Actions()->removeByName('action_delete');

        if ($member = Security::getCurrentUser()) {
            $form->setValidator($member->getValidator());
        } else {
            $form->setValidator(Member::singleton()->getValidator());
        }

        $this->setCMSTabset($form);

        return $form;
    }

    public function canView($member = null)
    {
        if (!$member && $member !== false) {
            $member = Security::getCurrentUser();
        }

        // cms menus only for logged-in members
        if (!$member) {
            return false;
        }

        // Check they can access the CMS and that they are trying to edit themselves
        $canAccess = Permission::checkMember($member, "CMS_ACCESS")
            && $member->ID === Security::getCurrentUser()->ID;

        if ($canAccess) {
            return true;
        }

        return false;
    }

    public function save($data, $form)
    {
        /** @var Member $Member */
        $member = Member::get()->byID($data['ID']);
        if (!$member) {
            return $this->httpError(404);
        }
        $origLocale = $member->Locale;

        if (!$member->canEdit()) {
            $this->sessionMessage(_t(__CLASS__ . '.CANTEDIT', 'You don\'t have permission to do that'), 'bad');
            return $this->redirectBack();
        }

        $response = parent::save($data, $form);

        // Current locale has changed
        if (isset($data['Locale']) && $origLocale != $data['Locale']) {
            // TODO: implement our own ajax loading
            // $response->addHeader('X-Reload', true);
            // $response->addHeader('X-ControllerURL', $this->Link());
        }

        return $response;
    }

    /**
     * Only show first element, as the profile form is limited to editing
     * the current member it doesn't make much sense to show the member name
     * in the breadcrumbs.
     *
     * @param bool $unlinked
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false)
    {
        $items = parent::Breadcrumbs($unlinked);
        return new ArrayList(array($items[0]));
    }
}
