<?php

$ignoreCompatFile = __DIR__ . '/_compat/_manifest_exclude';
// Just in case you don't use the SiteConfig module
if (!class_exists(SilverStripe\SiteConfig\SiteConfig::class)) {
    require_once "_compat/SiteConfig/SiteConfig.php";
    // Remove it or it will be ignored by class manifest and we won't find SiteConfig in subClasses of DataObject
    if (is_file($ignoreCompatFile)) {
        unlink($ignoreCompatFile);
    }
} elseif (defined(SilverStripe\SiteConfig\SiteConfig::IS_COMPAT)) {
    file_put_contents($ignoreCompatFile, "");
}
