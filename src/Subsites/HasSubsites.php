<?php

namespace LeKoala\Admini\Subsites;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;

trait HasSubsites
{
    /**
     * @return Subsite
     */
    public function CurrentSubsite()
    {
        if (!class_exists(SubsiteState::class)) {
            return false;
        }
        $class = SubsiteState::class;
        $id = $class::singleton()->getSubsiteId();
        return DataObject::get_by_id(Subsite::class, $id);
    }

    public function ListSubsitesExpanded()
    {
        if (!class_exists(SubsiteState::class)) {
            return false;
        }

        $class = Subsite::class;
        $list = $class::all_accessible_sites();
        if ($list == null || $list->count() == 1 && $list->first()->DefaultSite == true) {
            return false;
        }

        $currentSubsite = $this->CurrentSubsite();
        $output = ArrayList::create();

        foreach ($list as $subsite) {
            $currentState = $currentSubsite && $subsite->ID == $currentSubsite->ID ? 'selected' : '';

            $SiteConfig = $subsite->SiteConfig();
            if (!$SiteConfig) {
                continue;
            }
            $PrimaryColor = $SiteConfig->dbObject('PrimaryColor');

            $output->push(ArrayData::create([
                'CurrentState' => $currentState,
                'ID' => $subsite->ID,
                'Title' => Convert::raw2xml($subsite->Title),
                'BackgroundColor' => $PrimaryColor->Color(),
                'Color' => $PrimaryColor->ContrastColor(),
            ]));
        }

        return $output;
    }

    public function blockSubsiteRequirements()
    {
        // We need this since block will try to resolve module path :-(
        if (!class_exists(SubsiteState::class)) {
            return;
        }
        Requirements::block('silverstripe/subsites:css/LeftAndMain_Subsites.css');
        Requirements::block('silverstripe/subsites:javascript/LeftAndMain_Subsites.js');
        Requirements::block('silverstripe/subsites:javascript/VirtualPage_Subsites.js');
    }
}
