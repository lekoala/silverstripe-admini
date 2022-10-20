<?php

namespace LeKoala\Admini;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TabSet;
use SilverStripe\Security\Group;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use LeKoala\Tabulator\TabulatorGrid;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionRole;
use SilverStripe\Security\PermissionProvider;

/**
 * Security section of the CMS
 */
class SecurityAdmin extends LeftAndMain implements PermissionProvider
{

    private static $url_segment = 'security';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $menu_title = 'Security';

    private static $tree_class = Group::class;

    private static $subitem_class = Member::class;

    private static $required_permission_codes = 'CMS_ACCESS_SecurityAdmin';

    private static $menu_icon = MaterialIcons::SECURITY;

    private static $allowed_actions = [
        'EditForm',
    ];


    /**
     * @return array
     */
    public static function getMembersFromSecurityGroupsIDs()
    {
        $sql = 'SELECT DISTINCT MemberID FROM Group_Members INNER JOIN Permission ON Permission.GroupID = Group_Members.GroupID WHERE Code LIKE \'CMS_%\' OR Code = \'ADMIN\'';
        return DB::query($sql)->column();
    }

    public function getEditForm($id = null, $fields = null)
    {
        $fields = new FieldList();
        $fields->push(new TabSet('Root'));

        // Build member fields (display only relevant security members)
        $membersOfGroups = self::getMembersFromSecurityGroupsIDs();
        $memberField = new TabulatorGrid(
            'Members',
            false,
            Member::get()->filter("ID", $membersOfGroups),
        );
        $membersTab = $fields->findOrMakeTab('Root.Users', _t(__CLASS__ . '.TABUSERS', 'Users'));
        $membersTab->push($memberField);

        // Build group fields
        $groupField = new TabulatorGrid(
            'Groups',
            false,
            Group::get(),
        );
        $groupsTab = $fields->findOrMakeTab('Root.Groups', Group::singleton()->i18n_plural_name());
        $groupsTab->push($groupField);

        // Add roles editing interface
        $rolesTab = null;
        if (Permission::check('APPLY_ROLES')) {
            $rolesField = new TabulatorGrid('Roles', false, PermissionRole::get());
            $rolesTab = $fields->findOrMakeTab('Root.Roles', PermissionRole::singleton()->i18n_plural_name());
            $rolesTab->push($rolesField);
        }

        // Build replacement form
        $form = Form::create(
            $this,
            'EditForm',
            $fields,
            new FieldList()
        )->setHTMLID('Form_EditForm');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $this->setCMSTabset($form);

        $this->extend('updateEditForm', $form);

        return $form;
    }

    public function Breadcrumbs($unlinked = false)
    {
        $crumbs = parent::Breadcrumbs($unlinked);

        // Name root breadcrumb based on which record is edited,
        // which can only be determined by looking for the fieldname of the GridField.
        // Note: Titles should be same titles as tabs in RootForm().
        $params = $this->getRequest()->allParams();
        if (isset($params['FieldName'])) {
            // TODO FieldName param gets overwritten by nested GridFields,
            // so shows "Members" rather than "Groups" for the following URL:
            // admin/security/EditForm/field/Groups/item/2/ItemEditForm/field/Members/item/1/edit
            $firstCrumb = $crumbs->shift();
            if ($params['FieldName'] == 'Groups') {
                $crumbs->unshift(new ArrayData(array(
                    'Title' => Group::singleton()->i18n_plural_name(),
                    'Link' => $this->Link() . '#Root_Groups'
                )));
            } elseif ($params['FieldName'] == 'Users') {
                $crumbs->unshift(new ArrayData(array(
                    'Title' => _t(__CLASS__ . '.TABUSERS', 'Users'),
                    'Link' => $this->Link() . '#Root_Users'
                )));
            } elseif ($params['FieldName'] == 'Roles') {
                $crumbs->unshift(new ArrayData(array(
                    'Title' => PermissionRole::singleton()->i18n_plural_name(),
                    'Link' => $this->Link() . '#Root_Roles'
                )));
            }
            $crumbs->unshift($firstCrumb);
        }

        return $crumbs;
    }

    public function providePermissions()
    {
        $title = $this->menu_title();
        return array(
            "CMS_ACCESS_SecurityAdmin" => [
                'name' => _t(
                    'LeKoala\\Admini\\LeftAndMain.ACCESS',
                    "Access to '{title}' section",
                    ['title' => $title]
                ),
                'category' => _t('SilverStripe\\Security\\Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
                'help' => _t(
                    __CLASS__ . '.ACCESS_HELP',
                    'Allow viewing, adding and editing users, as well as assigning permissions and roles to them.'
                )
            ],
            'EDIT_PERMISSIONS' => array(
                'name' => _t(__CLASS__ . '.EDITPERMISSIONS', 'Manage permissions for groups'),
                'category' => _t(
                    'SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY',
                    'Roles and access permissions'
                ),
                'help' => _t(
                    __CLASS__ . '.EDITPERMISSIONS_HELP',
                    'Ability to edit Permissions and IP Addresses for a group.'
                        . ' Requires the "Access to \'Security\' section" permission.'
                ),
                'sort' => 0
            ),
            'APPLY_ROLES' => array(
                'name' => _t(__CLASS__ . '.APPLY_ROLES', 'Apply roles to groups'),
                'category' => _t(
                    'SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY',
                    'Roles and access permissions'
                ),
                'help' => _t(
                    __CLASS__ . '.APPLY_ROLES_HELP',
                    'Ability to edit the roles assigned to a group.'
                        . ' Requires the "Access to \'Users\' section" permission.'
                ),
                'sort' => 0
            )
        );
    }
}
