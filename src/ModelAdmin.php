<?php

namespace LeKoala\Admini;

use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use LeKoala\Tabulator\TabulatorGrid;
use SilverStripe\Control\Controller;

/**
 * Generates a three-pane UI for editing model classes, tabular results and edit forms.
 *
 * Relies on data such as {@link DataObject::$db} and {@link DataObject::getCMSFields()}
 * to scaffold interfaces "out of the box", while at the same time providing
 * flexibility to customize the default output.
 */
abstract class ModelAdmin extends LeftAndMain
{
    /**
     * @inheritdoc
     */
    private static $url_rule = '/$ModelClass/$Action';

    /**
     * List of all managed {@link DataObject}s in this interface.
     *
     * Simple notation with class names only:
     * <code>
     * array('MyObjectClass','MyOtherObjectClass')
     * </code>
     *
     * Extended notation with options (e.g. custom titles):
     * <code>
     * array(
     *   'MyObjectClass' => ['title' => "Custom title"]
     *   'urlslug' => ['title' => "Another title", 'dataClass' => MyNamespacedClass::class]
     * )
     * </code>
     *
     * Available options:
     * - 'title': Set custom titles for the tabs or dropdown names
     * - 'dataClass': The class name being managed. Defaults to the key. Useful for making shorter URLs or placing the same class in multiple tabs
     *
     * @config
     * @var array|string
     */
    private static $managed_models = null;

    /**
     * Override menu_priority so that ModelAdmin CMSMenu objects
     * are grouped together directly above the Help menu item.
     * @var float
     */
    private static $menu_priority = -0.5;

    /**
     * @var string
     */
    private static $menu_icon = MaterialIcons::STAR;

    private static $allowed_actions = array();

    private static $url_handlers = array(
        '$ModelClass/$Action' => 'handleAction'
    );

    /**
     * @var string The {@link \SilverStripe\ORM\DataObject} sub-class being managed during this object's lifetime.
     */
    protected $modelClass;

    /**
     * @var string The {@link \SilverStripe\ORM\DataObject} the currently active model tab, and key of managed_models.
     */
    protected $modelTab;

    /**
     * @config
     * @var int Amount of results to show per page
     */
    private static $page_length = 15;

    /**
     * Initialize the model admin interface. Sets up embedded jquery libraries and requisite plugins.
     *
     * Sets the `modelClass` field which determines which of the {@link DataObject} objects will have visible data. This
     * is determined by the URL (with the first slug being the name of the DataObject class to represent. If this class
     * is loaded without any URL, we pick the first DataObject from the list of {@link self::$managed_models}.
     */
    protected function init()
    {
        parent::init();

        $models = $this->getManagedModels();
        $this->modelTab = $this->getRequest()->param('ModelClass');

        // if we've hit the "landing" page
        if ($this->modelTab === null) {
            reset($models);
            $this->modelTab = key($models);
        }

        // security check for valid models
        if (!array_key_exists($this->modelTab, $models)) {
            // if it fails to match the string exactly, try reverse-engineering a classname
            $this->modelTab = $this->unsanitiseClassName($this->modelTab);

            if (!array_key_exists($this->modelTab, $models)) {
                throw new \RuntimeException(sprintf('ModelAdmin::init(): Invalid Model class %s', $this->modelTab));
            }
        }

        $this->modelClass = isset($models[$this->modelTab]['dataClass'])
            ? $models[$this->modelTab]['dataClass']
            : $this->modelTab;
    }

    /**
     * Overrides {@link \LeKoala\Admini\LeftAndMain} to ensure the active model class (the DataObject we are
     * currently viewing) is included in the URL.
     *
     * @inheritdoc
     */
    public function Link($action = null)
    {
        if (!$action) {
            $action = $this->sanitiseClassName($this->modelTab);
        }
        return parent::Link($action);
    }

    /**
     * @param int|null $id
     * @param \SilverStripe\Forms\FieldList $fields
     * @return \SilverStripe\Forms\Form A Form object with one tab per {@link \SilverStripe\Forms\GridField\GridField}
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = Form::create(
            $this,
            'EditForm',
            new FieldList(),
            new FieldList()
        )->setHTMLID('Form_EditForm');
        $form->Fields()->push($this->getTabulatorGrid($form));
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $editFormAction = Controller::join_links($this->Link($this->sanitiseClassName($this->modelTab)), 'EditForm');
        $form->setFormAction($editFormAction);

        $this->extend('updateEditForm', $form);

        return $form;
    }

    /**
     */
    protected function getTabulatorGrid(Form $form): TabulatorGrid
    {
        $field = new TabulatorGrid(
            $this->sanitiseClassName($this->modelTab),
            false, // false = no label, null = default label
            $this->getList(),
        );
        $field->setForm($form);
        $field->setPageSize(self::config()->page_length);

        $this->extend('updateTabulatorGrid', $field);
        return $field;
    }

    /**
     * You can override how ModelAdmin returns DataObjects by either overloading this method, or defining an extension
     * to ModelAdmin that implements the `updateList` method (and takes a {@link \SilverStripe\ORM\DataList} as the
     * first argument).
     *
     * For example, you might want to do this if this particular ModelAdmin should only ever show objects where an
     * Archived flag is set to false. That would be best done as an extension, for example:
     *
     * <code>
     * public function updateList(\SilverStripe\ORM\DataList $list)
     * {
     *     return $list->filter('Archived', false);
     * }
     * </code>
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getList()
    {
        $list = DataObject::singleton($this->modelClass)->get();
        $this->extend('updateList', $list);
        return $list;
    }

    /**
     * The model managed by this instance.
     * See $managed_models for potential values.
     *
     * @return string
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }

    /**
     * @return \SilverStripe\ORM\ArrayList An ArrayList of all managed models to build the tabs for this ModelAdmin
     */
    protected function getManagedModelTabs()
    {
        $models = $this->getManagedModels();
        $forms = new ArrayList();

        foreach ($models as $tab => $options) {
            $forms->push(new ArrayData(array(
                'Title' => $options['title'],
                'Tab' => $tab,
                // `getManagedModels` did not always return a `dataClass` attribute
                // Legacy behaviour is for `ClassName` to map to `$tab`
                'ClassName' => isset($options['dataClass']) ? $options['dataClass'] : $tab,
                'Link' => $this->Link($this->sanitiseClassName($tab)),
                'LinkOrCurrent' => ($tab == $this->modelTab) ? 'current' : 'link'
            )));
        }

        return $forms;
    }

    /**
     * Sanitise a model class' name for inclusion in a link
     *
     * @param string $class
     * @return string
     */
    protected function sanitiseClassName($class)
    {
        return str_replace('\\', '-', $class);
    }

    /**
     * Unsanitise a model class' name from a URL param
     *
     * @param string $class
     * @return string
     */
    protected function unsanitiseClassName($class)
    {
        return str_replace('-', '\\', $class);
    }

    /**
     * @return array Map of class name to an array of 'title' (see {@link $managed_models})
     */
    public function getManagedModels()
    {
        $models = $this->config()->get('managed_models');
        if (is_string($models)) {
            $models = array($models);
        }
        if (!count($models)) {
            throw new \RuntimeException(
                'ModelAdmin::getManagedModels():
				You need to specify at least one DataObject subclass in private static $managed_models.
				Make sure that this property is defined, and that its visibility is set to "private"'
            );
        }

        // Normalize models to have their model class in array key
        foreach ($models as $k => $v) {
            if (is_numeric($k)) {
                $models[$v] = ['dataClass' => $v, 'title' => singleton($v)->i18n_plural_name()];
                unset($models[$k]);
            } elseif (is_array($v) && !isset($v['dataClass'])) {
                $models[$k]['dataClass'] = $k;
            }
        }

        return $models;
    }

    /**
     * @param bool $unlinked
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false)
    {
        $items = parent::Breadcrumbs($unlinked);

        // Show the class name rather than ModelAdmin title as root node
        $models = $this->getManagedModels();
        $params = $this->getRequest()->getVars();
        if (isset($params['url'])) {
            unset($params['url']);
        }

        $items[0]->Title = $models[$this->modelTab]['title'];
        $items[0]->Link = Controller::join_links(
            $this->Link($this->sanitiseClassName($this->modelTab)),
            '?' . http_build_query($params)
        );

        return $items;
    }
}
