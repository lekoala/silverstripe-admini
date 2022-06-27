<?php

namespace LeKoala\Admini\SiteConfig;

use SilverStripe\Forms\Form;
use LeKoala\Admini\LeftAndMain;
use LeKoala\Admini\MaterialIcons;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\RecursivePublishable;

class SiteConfigLeftAndMain extends LeftAndMain
{
    /**
     * @var string
     */
    private static $url_segment = 'settings';

    /**
     * @var string
     */
    private static $url_rule = '/$Action/$ID/$OtherID';

    /**
     * @var int
     */
    private static $menu_priority = -1;

    /**
     * @var string
     */
    private static $menu_title = 'Settings';

    /**
     * @var string
     */
    private static $menu_icon = MaterialIcons::SETTINGS;

    /**
     * @var string
     */
    private static $tree_class = SiteConfig::class;

    /**
     * @var array
     */
    private static $required_permission_codes = array('EDIT_SITECONFIG');

    /**
     * Initialises the {@link SiteConfig} controller.
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @param null $id Not used.
     * @param null $fields Not used.
     *
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $siteConfig = SiteConfig::current_site_config();
        $fields = $siteConfig->getCMSFields();

        // Retrieve validator, if one has been setup (e.g. via data extensions).
        if ($siteConfig->hasMethod("getCMSValidator")) {
            $validator = $siteConfig->getCMSValidator();
        } else {
            $validator = null;
        }

        $actions = $siteConfig->getCMSActions();
        $saveAction = $actions->fieldByName("action_save_siteconfig");
        if ($saveAction) {
            $saveAction->removeExtraClass("btn-primary");
            $saveAction->addExtraClass("btn-outline-success");
            $saveAction->setIcon(MaterialIcons::DONE);
        }
        // $negotiator = $this->getResponseNegotiator();
        /** @var Form $form */
        $form = Form::create(
            $this,
            'EditForm',
            $fields,
            $actions,
            $validator
        )->setHTMLID('Form_EditForm');

        /*
        $form->setValidationResponseCallback(function (ValidationResult $errors) use ($negotiator, $form) {
            $request = $this->getRequest();
            if ($request->isAjax() && $negotiator) {
                $result = $form->forTemplate();
                return $negotiator->respond($request, array(
                    'CurrentForm' => function () use ($result) {
                        return $result;
                    }
                ));
            }
        });*/

        $this->setCMSTabset($form);

        $form->setHTMLID('Form_EditForm');
        $form->loadDataFrom($siteConfig);
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

        $actions = $actions->dataFields();
        if ($actions) {
            /** @var FormAction $action */
            foreach ($actions as $action) {
                $action->setUseButtonTag(true);
            }
        }

        $this->extend('updateEditForm', $form);

        return $form;
    }

    /**
     * Save the current sites {@link SiteConfig} into the database.
     * The method is defined in SiteConfig::getCMSActions
     *
     * @param array $data
     * @param Form $form
     * @return String
     */
    public function save_siteconfig($data, $form)
    {
        $data = $form->getData();
        $siteConfig = DataObject::get_by_id(SiteConfig::class, $data['ID']);
        $form->saveInto($siteConfig);
        $siteConfig->write();

        if ($siteConfig->hasExtension(RecursivePublishable::class)) {
            $siteConfig->publishRecursive();
        }

        $message = _t('SilverStripe\\Admin\\LeftAndMain.SAVEDUP', 'Saved.');

        // TODO: implement our own logic
        // if (Director::is_ajax()) {
        //     $this->response->addHeader(
        //         'X-Status',
        //         rawurlencode($message)
        //     );
        //     return $form->forTemplate();
        // }
        $this->successMessage($message);
        return $this->redirect($this->LinkHash());
    }

    public function Breadcrumbs($unlinked = false)
    {
        return new ArrayList(array(
            new ArrayData(array(
                'Title' => static::menu_title(),
                'Link' => $this->Link()
            ))
        ));
    }
}
