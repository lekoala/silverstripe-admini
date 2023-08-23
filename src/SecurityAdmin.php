<?php

namespace LeKoala\Admini;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\ArrayList;
use LeKoala\Base\View\Bootstrap;
use SilverStripe\Security\Group;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Security\Security;
use LeKoala\Tabulator\TabulatorGrid;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Permission;
use LeKoala\Admini\Helpers\FileHelper;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\PermissionRole;
use LeKoala\CmsActions\CmsInlineFormAction;
use LeKoala\Admini\Forms\BootstrapAlertField;
use SilverStripe\Security\PermissionProvider;

/**
 * Security section of the CMS
 */
class SecurityAdmin extends ModelAdmin implements PermissionProvider
{
    private static $url_segment = 'security';

    private static $menu_title = 'Security';

    private static $managed_models = [
        'users' => [
            'title' => 'Users',
            'dataClass' => Member::class
        ],
        'groups' => [
            'title' => 'Groups',
            'dataClass' => Group::class
        ],
        // 'roles' => [
        //     'title' => 'Roles',
        //     'dataClass' => PermissionRole::class
        // ],
    ];

    private static $required_permission_codes = 'CMS_ACCESS_SecurityAdmin';

    private static $menu_icon = MaterialIcons::SECURITY;

    private static $allowed_actions = [
        'doClearLogs',
        'doRotateLogs',
        // new tabs
        'security_audit',
        'logs',
    ];

    public static function getMembersFromSecurityGroupsIDs(): array
    {
        $sql = 'SELECT DISTINCT MemberID FROM Group_Members INNER JOIN Permission ON Permission.GroupID = Group_Members.GroupID WHERE Code LIKE \'CMS_%\' OR Code = \'ADMIN\'';
        return DB::query($sql)->column();
    }

    /**
     * @param array $extraIDs
     * @return Member[]|ArrayList
     */
    public static function getMembersFromSecurityGroups(array $extraIDs = [])
    {
        $ids = array_merge(self::getMembersFromSecurityGroupsIDs(), $extraIDs);
        return Member::get()->filter('ID', $ids);
    }

    public function getManagedModels()
    {
        $models = parent::getManagedModels();

        // Add extra tabs
        if (Security::config()->login_recording) {
            $models['security_audit'] = [
                'title' => 'Security Audit',
                'dataClass' => LoginAttempt::class,
            ];
        }
        if (Permission::check('ADMIN')) {
            $models['logs'] = [
                'title' => 'Logs',
                'dataClass' => LoginAttempt::class, // mock class
            ];
        }

        return $models;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        // In security, we only show group members + current item (to avoid issue when creating stuff)
        $request = $this->getRequest();
        $dirParts = explode('/', $request->remaining());
        $currentID = isset($dirParts[3]) ? [$dirParts[3]] : [];

        switch ($this->modelTab) {
            case 'users':
                /** @var TabulatorGrid $members */
                $members = $form->Fields()->dataFieldByName('users');
                $membersOfGroups = self::getMembersFromSecurityGroups($currentID);
                $members->setList($membersOfGroups);

                // Add some security wise fields
                $sng = singleton($members->getModelClass());
                if ($sng->hasMethod('DirectGroupsList')) {
                    $members->addDisplayFields([
                        'DirectGroupsList' => 'Direct Groups'
                    ]);
                }
                if ($sng->hasMethod('Is2FaConfigured')) {
                    $members->addDisplayFields([
                        'Is2FaConfigured' => '2FA'
                    ]);
                }
                break;
            case 'groups':
                break;
            case 'security_audit':
                if (Security::config()->login_recording) {
                    $this->addAuditTab($form);
                }
                break;
            case 'logs':
                if (Permission::check('ADMIN')) {
                    $this->addLogTab($form);
                }
                break;
        }

        return $form;
    }

    protected function getLogFiles(): array
    {
        $logDir = Director::baseFolder();
        $logFiles = glob($logDir . '/*.log');
        return $logFiles;
    }

    protected function addLogTab(Form $form)
    {
        $logFiles = $this->getLogFiles();
        $logTab = $form->Fields();
        $logTab->removeByName('logs');

        foreach ($logFiles as $logFile) {
            $logName = pathinfo($logFile, PATHINFO_FILENAME);

            $logTab->push(new HeaderField($logName, ucwords($logName)));

            $filemtime = filemtime($logFile);
            $filesize = filesize($logFile);

            $logTab->push(new BootstrapAlertField($logName . 'Alert', _t('BaseSecurityAdminExtension.LogAlert', "Last updated on {updated}", [
                'updated' => date('Y-m-d H:i:s', $filemtime),
            ])));

            $lastLines = '<pre>' . FileHelper::tail($logFile, 10) . '</pre>';

            $logTab->push(new LiteralField($logName, $lastLines));
            $logTab->push(new LiteralField($logName . 'Size', '<p>' . _t('BaseSecurityAdminExtension.LogSize', "Total size is {size}", [
                'size' => FileHelper::humanFilesize($filesize)
            ]) . '</p>'));
        }

        $clearLogsBtn = new CmsInlineFormAction('doClearLogs', _t('BaseSecurityAdminExtension.doClearLogs', 'Clear Logs'));
        $logTab->push($clearLogsBtn);
        $rotateLogsBtn = new CmsInlineFormAction('doRotateLogs', _t('BaseSecurityAdminExtension.doRotateLogs', 'Rotate Logs'));
        $logTab->push($rotateLogsBtn);
    }

    public function doClearLogs(HTTPRequest $request)
    {
        foreach ($this->getLogFiles() as $logFile) {
            unlink($logFile);
        }
        $msg = "Logs cleared";
        return $this->redirectWithStatus($msg);
    }

    public function doRotateLogs(HTTPRequest $request)
    {
        foreach ($this->getLogFiles() as $logFile) {
            if (strpos($logFile, '-') !== false) {
                continue;
            }
            $newname = dirname($logFile) . '/' . pathinfo($logFile, PATHINFO_FILENAME) . '-' . date('Ymd') . '.log';
            rename($logFile, $newname);
        }
        $msg = "Logs rotated";
        return $this->redirectWithStatus($msg);
    }

    protected function addAuditTab(Form $form)
    {
        $auditTab = $form->Fields();
        $auditTab->removeByName('security_audit');

        $Member_SNG = Member::singleton();
        $membersLocked = Member::get()->where('LockedOutUntil > NOW()');
        if ($membersLocked->count()) {
            $membersLockedGrid = new TabulatorGrid('MembersLocked', _t('BaseSecurityAdminExtension.LockedMembers', "Locked Members"), $membersLocked);
            $membersLockedGrid->setForm($form);
            $membersLockedGrid->setDisplayFields([
                'Title' => $Member_SNG->fieldLabel('Title'),
                'Email' => $Member_SNG->fieldLabel('Email'),
                'LockedOutUntil' => $Member_SNG->fieldLabel('LockedOutUntil'),
                'FailedLoginCount' => $Member_SNG->fieldLabel('FailedLoginCount'),
            ]);
            $auditTab->push($membersLockedGrid);
        }

        $LoginAttempt_SNG = LoginAttempt::singleton();

        $getMembersFromSecurityGroupsIDs = self::getMembersFromSecurityGroupsIDs();
        $recentAdminLogins = LoginAttempt::get()->filter([
            'Status' => 'Success',
            'MemberID' => $getMembersFromSecurityGroupsIDs
        ])->limit(10)->sort('Created DESC');
        $recentAdminLoginsGrid = new TabulatorGrid('RecentAdminLogins', _t('BaseSecurityAdminExtension.RecentAdminLogins', "Recent Admin Logins"), $recentAdminLogins);
        $recentAdminLoginsGrid->setDisplayFields([
            'Created' => $LoginAttempt_SNG->fieldLabel('Created'),
            'IP' => $LoginAttempt_SNG->fieldLabel('IP'),
            'Member.Title' => $Member_SNG->fieldLabel('Title'),
            'Member.Email' => $Member_SNG->fieldLabel('Email'),
        ]);
        $recentAdminLoginsGrid->setForm($form);
        $auditTab->push($recentAdminLoginsGrid);

        $recentPasswordFailures = LoginAttempt::get()->filter('Status', 'Failure')->limit(10)->sort('Created DESC');
        $recentPasswordFailuresGrid = new TabulatorGrid('RecentPasswordFailures', _t('BaseSecurityAdminExtension.RecentPasswordFailures', "Recent Password Failures"), $recentPasswordFailures);
        $recentPasswordFailuresGrid->setDisplayFields([
            'Created' => $LoginAttempt_SNG->fieldLabel('Created'),
            'IP' => $LoginAttempt_SNG->fieldLabel('IP'),
            'Member.Title' => $Member_SNG->fieldLabel('Title'),
            'Member.Email' => $Member_SNG->fieldLabel('Email'),
            'Member.FailedLoginCount' => $Member_SNG->fieldLabel('FailedLoginCount'),
        ]);
        $recentPasswordFailuresGrid->setForm($form);
        $auditTab->push($recentPasswordFailuresGrid);
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
