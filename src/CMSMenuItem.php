<?php

namespace LeKoala\Admini;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * A simple CMS menu item.
 *
 * Items can be added to the menu through custom {@link LeftAndMainExtension}
 * classes and {@link CMSMenu}.
 *
 * @see CMSMenu
 */
class CMSMenuItem
{
    use Injectable;

    /**
     * The (translated) menu title
     * @var string $title
     */
    public $title;

    /**
     * Relative URL
     * @var string $url
     */
    public $url;

    /**
     * Parent controller class name
     * @var string $controller
     */
    public $controller;

    /**
     * Menu priority (sort order)
     * @var integer $priority
     */
    public $priority;

    /**
     * Attributes for the link. For instance, custom data attributes or standard
     * HTML anchor properties.
     *
     * @var string
     */
    protected $attributes = [];

    /**
     * @var string
     */
    public $iconName;

    /**
     * Create a new CMS Menu Item
     *
     * @param string $title
     * @param string $url
     * @param string $controller Controller class name
     * @param integer $priority The sort priority of the item
     * @param string $iconName
     */
    public function __construct($title, $url, $controller = null, $priority = -1, $iconName = null)
    {
        $this->title = $title;
        $this->url = $url;
        $this->controller = $controller;
        $this->priority = $priority;
        $this->iconName = $iconName;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $extraClass
     */
    public function addExtraClass($extraClass)
    {
        $class = $this->attributes["class"] ?? "";
        $class .= " " . $extraClass;
        $this->attributes["class"] = $class;
    }

    /**
     * @param array $attrs
     * @return DBHTMLText
     */
    public function getAttributesHTML($attrs = null)
    {
        $excludeKeys = (is_string($attrs)) ? func_get_args() : null;

        if (!$attrs || is_string($attrs)) {
            $attrs = $this->attributes;
        }

        // Remove empty or excluded values
        foreach ($attrs as $key => $value) {
            if (($excludeKeys && in_array($key, $excludeKeys))
                || (!$value && $value !== 0 && $value !== '0')
            ) {
                unset($attrs[$key]);
                continue;
            }
        }

        // Create markkup
        $parts = array();

        foreach ($attrs as $name => $value) {
            if ($value === true) {
                $value = $name;
            }

            $parts[] = sprintf('%s="%s"', Convert::raw2att($name), Convert::raw2att($value));
        }

        /** @var DBHTMLText $fragment */
        $fragment = DBField::create_field('HTMLFragment', implode(' ', $parts));
        return $fragment;
    }
}
