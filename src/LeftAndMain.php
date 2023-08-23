<?php

namespace LeKoala\Admini;

use LogicException;
use BadMethodCallException;
use SilverStripe\i18n\i18n;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\SS_List;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Cookie;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use LeKoala\Admini\Traits\Toasts;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use LeKoala\DeferBackend\CspProvider;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use LeKoala\DeferBackend\DeferBackend;
use SilverStripe\Control\HTTPResponse;
use LeKoala\Admini\Traits\JsonResponse;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;
use LeKoala\Admini\Subsites\HasSubsites;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Forms\PrintableTransformation;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;

/**
 * LeftAndMain is the parent class of all the two-pane views in the CMS.
 * If you are wanting to add more areas to the CMS, you can do it by subclassing LeftAndMain.
 *
 * This is essentially an abstract class which should be subclassed.
 *
 * @method bool alternateMenuDisplayCheck(Member $member = null)
 * @method bool alternateAccessCheck(Member $member = null)
 */
class LeftAndMain extends Controller implements PermissionProvider
{
    use JsonResponse;
    use Toasts;
    use HasSubsites;

    /**
     * The current url segment attached to the LeftAndMain instance
     *
     * @config
     * @var string
     */
    private static $url_segment = null;

    /**
     * @config
     * @var string Used by {@link AdminiRootController} to augment Director route rules for sub-classes of LeftAndMain
     */
    private static $url_rule = '/$Action/$ID/$OtherID';

    /**
     * @config
     * @var string
     */
    private static $menu_title;

    /**
     * An icon name for last-icon. You can check MaterialIcons class for values
     * @config
     * @var string
     */
    private static $menu_icon;

    /**
     * @config
     * @var int
     */
    private static $menu_priority = 0;

    /**
     * @config
     * @var int
     */
    private static $url_priority = 50;

    /**
     * A subclass of {@link DataObject}.
     *
     * Determines what is managed in this interface, through
     * {@link getEditForm()} and other logic.
     *
     * @config
     * @var string
     */
    private static $tree_class = null;

    /**
     * @var array
     */
    private static $allowed_actions = [
        'index',
        'save',
        'printable',
        'show',
        'EditForm',
    ];

    /**
     * Assign themes to use for cms
     *
     * @config
     * @var array
     */
    private static $admin_themes = [
        'lekoala/silverstripe-admini:forms',
        SSViewer::DEFAULT_THEME,
    ];

    /**
     * Codes which are required from the current user to view this controller.
     * If multiple codes are provided, all of them are required.
     * All CMS controllers require "CMS_ACCESS_LeftAndMain" as a baseline check,
     * and fall back to "CMS_ACCESS_<class>" if no permissions are defined here.
     * See {@link canView()} for more details on permission checks.
     *
     * @config
     * @var array
     */
    private static $required_permission_codes;

    /**
     * Namespace for session info, e.g. current record.
     * Defaults to the current class name, but can be amended to share a namespace in case
     * controllers are logically bundled together, and mainly separated
     * to achieve more flexible templating.
     *
     * @config
     * @var string
     */
    private static $session_namespace;

    /**
     * Register additional requirements through the {@link Requirements} class.
     * Used mainly to work around the missing "lazy loading" functionality
     * for getting css/javascript required after an ajax-call (e.g. loading the editform).
     *
     * YAML configuration example:
     * <code>
     * LeftAndMain:
     *   extra_requirements_javascript:
     *     - mysite/javascript/myscript.js
     * </code>
     *
     * @config
     * @var array
     */
    private static $extra_requirements_javascript = [];

    /**
     * YAML configuration example:
     * <code>
     * LeftAndMain:
     *   extra_requirements_css:
     *     mysite/css/mystyle.css:
     *       media: screen
     * </code>
     *
     * @config
     * @var array See {@link extra_requirements_javascript}
     */
    private static $extra_requirements_css = [];

    /**
     * @config
     * @var array See {@link extra_requirements_javascript}
     */
    private static $extra_requirements_themedCss = [];

    /**
     * If true, call a keepalive ping every 5 minutes from the CMS interface,
     * to ensure that the session never dies.
     *
     * @config
     * @var bool
     */
    private static $session_keepalive_ping = true;

    /**
     * Value of X-Frame-Options header
     *
     * @config
     * @var string
     */
    private static $frame_options = 'SAMEORIGIN';

    /**
     * The configuration passed to the supporting JS for each CMS section includes a 'name' key
     * that by default matches the FQCN of the current class. This setting allows you to change
     * the key if necessary (for example, if you are overloading CMSMain or another core class
     * and want to keep the core JS - which depends on the core class names - functioning, you
     * would need to set this to the FQCN of the class you are overloading).
     *
     * @config
     * @var string|null
     */
    private static $section_name = null;

    /**
     * @var array
     * @config
     */
    private static $casting = [
        'MainIcon' => 'HTMLText'
    ];

    /**
     * The urls used for the links in the Help dropdown in the backend
     *
     * @config
     * @var array
     */
    private static $help_links = [
        'CMS User help' => 'https://userhelp.silverstripe.org/en/4',
        'Developer docs' => 'https://docs.silverstripe.org/en/4/',
        'Community' => 'https://www.silverstripe.org/',
        'Feedback' => 'https://www.silverstripe.org/give-feedback/',
    ];

    /**
     * The href for the anchor on the Silverstripe logo
     *
     * @config
     * @var string
     */
    private static $application_link = '//www.silverstripe.org/';

    /**
     * The application name
     *
     * @config
     * @var string
     */
    private static $application_name = 'Silverstripe';

    /**
     * Current pageID for this request
     *
     * @var null
     */
    protected $pageID = null;

    /**
     * @var VersionProvider
     */
    protected $versionProvider;

    /**
     * @param Member $member
     * @return bool
     */
    public function canView($member = null)
    {
        if (!$member && $member !== false) {
            $member = Security::getCurrentUser();
        }

        // cms menus only for logged-in members
        if (!$member) {
            return false;
        }

        // alternative extended checks
        if ($this->hasMethod('alternateAccessCheck')) {
            $alternateAllowed = $this->alternateAccessCheck($member);
            if ($alternateAllowed === false) {
                return false;
            }
        }

        // Check for "CMS admin" permission
        if (Permission::checkMember($member, "CMS_ACCESS_LeftAndMain")) {
            return true;
        }

        // Check for LeftAndMain sub-class permissions
        $codes = $this->getRequiredPermissions();
        if ($codes === false) { // allow explicit FALSE to disable subclass check
            return true;
        }
        foreach ((array)$codes as $code) {
            if (!Permission::checkMember($member, $code)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of required permissions
     *
     * @return array|string|bool Code, array of codes, or false if no permission required
     */
    public static function getRequiredPermissions()
    {
        $class = get_called_class();
        // If the user is accessing LeftAndMain directly, only generic permissions are required.
        if ($class === self::class) {
            return 'CMS_ACCESS';
        }
        $code = Config::inst()->get($class, 'required_permission_codes');
        if ($code === false) {
            return false;
        }
        if ($code) {
            return $code;
        }
        return 'CMS_ACCESS_' . $class;
    }

    protected function includeGoogleFont()
    {
        $font = self::config()->google_font;
        if (!$font) {
            return;
        }
        $preconnect = <<<HTML
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
HTML;
        Requirements::insertHeadTags($preconnect, "googlefontspreconnect");
        Requirements::css("https://fonts.googleapis.com/css2?$font");
    }

    protected function includeLastIcon()
    {
        $preconnect = <<<HTML
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
HTML;
        Requirements::insertHeadTags($preconnect, "googlefontspreconnect");

        // Could also host locally https://marella.me/material-icons/demo/#two-tone
        Requirements::css("https://fonts.googleapis.com/icon?family=Material+Icons+Two+Tone");
        Requirements::javascript('lekoala/silverstripe-admini: client/js/last-icon.min.js', ["type" => "application/javascript"]);
        $nonce = CspProvider::getCspNonce();
        $lastIconScript = <<<JS
<script nonce="$nonce">
    window.LastIcon = {
            types: {
            material: "twotone",
            },
            defaultSet: "material",
            fonts: ["material"],
        };
</script>
JS;
        Requirements::insertHeadTags($lastIconScript, __FUNCTION__);
    }

    public function UseBootstrap5()
    {
        return true;
    }

    protected function includeFavicon()
    {
        $icon = $this->MainIcon();
        if (strpos($icon, "<img") === 0) {
            // Regular image
            $matches = [];
            preg_match('/src="([^"]+)"/', $icon, $matches);
            $url = $matches[1] ?? '';
            $html = <<<HTML
<link rel="icon" type="image/png" href="$url" />
HTML;
        } else {
            // Svg icon
            $encodedIcon = str_replace(['"', '#'], ['%22', '%23'], $icon);
            $html = <<<HTML
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,$encodedIcon" />
HTML;
        }

        Requirements::insertHeadTags($html, __FUNCTION__);
    }

    protected function includeThemeVariables()
    {
        $SiteConfig = SiteConfig::current_site_config();
        if (!$SiteConfig->PrimaryColor) {
            return;
        }
        $PrimaryColor = $SiteConfig->dbObject('PrimaryColor');
        if (!$PrimaryColor->hasMethod('Color')) {
            return;
        }

        $bg = $PrimaryColor->Color();
        // Black is too harsh
        $color = $PrimaryColor->ContrastColor('#020C11');
        $border = $PrimaryColor->HighlightColor();

        $styles = <<<CSS
.sidebar-brand {background: $bg; color: $color}
CSS;
        Requirements::customCSS($styles, __FUNCTION__);
    }

    /**
     * @return int
     */
    public function SessionKeepAlivePing()
    {
        return LeftAndMain::config()->uninherited('session_keepalive_ping');
    }

    /**
     * The icon to be used either as favicon or in the menu
     * Can return a <svg> or <img> tag
     */
    public function MainIcon(): string
    {
        // Can be provided by the SiteConfig as a svg string or an uploaded png
        $SiteConfig = SiteConfig::current_site_config();
        if ($SiteConfig->hasMethod('SvgIcon')) {
            return $SiteConfig->SvgIcon();
        }
        if ($SiteConfig->IconID) {
            return '<img src="' . ($SiteConfig->Icon()->Link() ?? '') . '" />';
        }
        $emoji = self::config()->svg_emoji ?? '💠';
        $icon = self::config()->svg_icon ?? '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 100 100"><text x="50%" y="52%" dominant-baseline="central" text-anchor="middle" font-size="120">' . $emoji . '</text></svg>';
        return $icon;
    }

    public function AdminDir(): string
    {
        $path = "js/admini.js";
        $resource = ModuleLoader::getModule('lekoala/silverstripe-base')->getResource($path);
        $dir = dirname($resource->getRelativePath());
        return $dir;
    }

    /**
     * Preload fonts
     */
    public function PreloadFonts()
    {
        $fonts = self::config()->preload_fonts;
        if (empty($fonts)) {
            return;
        }
        $dir = $this->AdminDir();

        $html = '';
        if (!empty($fonts)) {
            foreach ($fonts as $font) {
                $font = $dir . $font;
                // browsers will ignore preloaded fonts without the crossorigin attribute, which will cause the browser to actually fetch the font twice
                $html .= "<link rel=\"preload\" href=\"$font\" as=\"font\" type=\"font/woff2\" crossOrigin=\"anonymous\" >\n";
            }
        }
        Requirements::insertHeadTags($html, __FUNCTION__);
    }

    public function HasMinimenu(): bool
    {
        return (bool)Cookie::get("minimenu");
    }

    /**
     * In the CMS, we use tabs in the header
     * Do not render actual tabs from the form
     *
     * @param Form $form
     * @return void
     */
    public function setCMSTabset(Form $form)
    {
        if ($form->Fields()->hasTabSet()) {
            $form->Fields()->findOrMakeTab('Root')->setTemplate('SilverStripe\\Forms\\CMSTabSet');
        }
    }

    /**
     * Check if the current request has a X-Formschema-Request header set.
     * Used by conditional logic that responds to validation results
     *
     * @return bool
     */
    protected function getSchemaRequested()
    {
        return false;
    }

    protected function loadExtraRequirements()
    {
        $extraJs = $this->config()->get('extra_requirements_javascript');
        if ($extraJs) {
            foreach ($extraJs as $file => $config) {
                if (is_numeric($file)) {
                    if (is_string($config)) {
                        $file = $config;
                        $config = [];
                    } elseif (is_array($config)) {
                        $file = $config['src'];
                        unset($config['src']);
                    }
                }
                Requirements::javascript($file, $config);
            }
        }

        $extraCss = $this->config()->get('extra_requirements_css');
        if ($extraCss) {
            foreach ($extraCss as $file => $config) {
                if (is_numeric($file)) {
                    if (is_string($config)) {
                        $file = $config;
                        $config = [];
                    } elseif (is_array($config)) {
                        $file = $config['href'];
                        unset($config['href']);
                    }
                }

                Requirements::css($file, isset($config['media']) ? $config['media'] : null);
            }
        }

        $extraThemedCss = $this->config()->get('extra_requirements_themedCss');
        if ($extraThemedCss) {
            foreach ($extraThemedCss as $file => $config) {
                if (is_numeric($file)) {
                    if (is_string($config)) {
                        $file = $config;
                        $config = [];
                    } elseif (is_array($config)) {
                        $file = $config['href'];
                        unset($config['href']);
                    }
                }
                Requirements::themedCSS($file, isset($config['media']) ? $config['media'] : null);
            }
        }
    }

    /**
     * @uses LeftAndMainExtension->init()
     * @uses LeftAndMainExtension->accessedCMS()
     * @uses CMSMenu
     */
    protected function init()
    {
        parent::init();

        // SSViewer::config()->source_file_comments = true;

        // Lazy by default
        Config::modify()->set(\LeKoala\Tabulator\TabulatorGrid::class, "default_lazy_init", true);

        // Pure modal
        Config::modify()->set(\LeKoala\PureModal\PureModal::class, "move_modal_to_body", true);

        // Replace classes
        self::replaceServices();
        DeferBackend::config()->enable_js_modules = true;
        DeferBackend::replaceBackend();

        $this->showToasterMessage();
        $this->blockSubsiteRequirements();

        HTTPCacheControlMiddleware::singleton()->disableCache();

        SSViewer::setRewriteHashLinksDefault(false);
        ContentNegotiator::setEnabled(false);

        // set language based on current user locale
        $member = Security::getCurrentUser();
        if (!empty($member->Locale)) {
            i18n::set_locale($member->Locale);
        }

        $response = $this->extendedCanView();
        if ($response) {
            return $response;
        }

        // Don't continue if there's already been a redirection request.
        if ($this->redirectedTo()) {
            return $this->getResponse();
        }

        // Audit logging hook
        if (empty($_REQUEST['executeForm']) && !$this->getRequest()->isAjax()) {
            $this->extend('accessedCMS');
        }

        $this->includeFavicon();
        $this->includeLastIcon();
        $this->includeGoogleFont();
        $this->includeThemeVariables();

        Requirements::javascript('lekoala/silverstripe-admini: client/js/admini.min.js');
        // This must be applied last, so we put it at the bottom manually because requirements backend may inject stuff in the body
        // Requirements::customScript("window.admini.init()");
        Requirements::css('lekoala/silverstripe-admini: client/css/admini.min.css');
        Requirements::css('lekoala/silverstripe-admini: client/css/custom.css');

        //TODO: restore these features
        // Requirements::add_i18n_javascript('silverstripe/admin:client/lang');
        //TODO: replace moment by a modern alternative
        // Requirements::add_i18n_javascript('silverstripe/admin:client/dist/moment-locales', false, false, true);

        $this->loadExtraRequirements();
        $this->extend('init');

        // Assign default cms theme and replace user-specified themes
        // This allows us for instance to set custom form templates for BS5
        SSViewer::set_themes(LeftAndMain::config()->uninherited('admin_themes'));

        // Versioned support
        if (class_exists(Versioned::class)) {
            // Make ide happy by not using a potentially undefined class
            $class = Versioned::class;
            // Set the current reading mode
            $class::set_stage($class::DRAFT);
            // Set default reading mode to suppress ?stage=Stage querystring params in CMS
            $class::set_default_reading_mode($class::get_reading_mode());
        }
    }

    protected static function replaceServices()
    {
        $replacementServices = self::config()->replacement_services;
        if ($replacementServices) {
            $replacementConfig = [];
            foreach ($replacementServices as $replaced => $replacement) {
                if (!class_exists($replacement['class'])) {
                    continue;
                }
                $replacementConfig[$replaced] = $replacement;
            }
            Injector::inst()->load($replacementConfig);
        }
    }

    /**
     * Returns the link with the posted hash if any
     * Depends on a _hash input in the
     *
     * @param string $action
     * @return string
     */
    public function LinkHash($action = null): string
    {
        $request = $this->getRequest();
        $hash = null;
        if ($request->isPOST()) {
            $hash = $request->postVar("_hash");
        }
        $link = $this->Link($action);
        if ($hash) {
            $link .= $hash;
        }
        return $link;
    }

    /**
     * Allow customisation of the access check by a extension
     * Also all the canView() check to execute Controller::redirect()
     * @return HTTPResponse|null
     */
    protected function extendedCanView()
    {
        if ($this->canView() || $this->getResponse()->isFinished()) {
            return null;
        }

        // When access /admin/, we should try a redirect to another part of the admin rather than be locked out
        $menu = $this->MainMenu();
        foreach ($menu as $candidate) {
            $canView = $candidate->Link &&
                $candidate->Link != $this->Link()
                && $candidate->MenuItem->controller
                && singleton($candidate->MenuItem->controller)->canView();
            if ($canView) {
                $this->redirect($candidate->Link);
                return;
            }
        }

        if (Security::getCurrentUser()) {
            $this->getRequest()->getSession()->clear("BackURL");
        }

        // if no alternate menu items have matched, return a permission error
        $messageSet = array(
            'default' => _t(
                __CLASS__ . '.PERMDEFAULT',
                "You must be logged in to access the administration area; please enter your credentials below."
            ),
            'alreadyLoggedIn' => _t(
                __CLASS__ . '.PERMALREADY',
                "I'm sorry, but you can't access that part of the CMS.  If you want to log in as someone else, do"
                    . " so below."
            ),
            'logInAgain' => _t(
                __CLASS__ . '.PERMAGAIN',
                "You have been logged out of the CMS.  If you would like to log in again, enter a username and"
                    . " password below."
            ),
        );

        return Security::permissionFailure($this, $messageSet);
    }

    public function handleRequest(HTTPRequest $request): HTTPResponse
    {
        try {
            $response = parent::handleRequest($request);
        } catch (ValidationException $e) {
            // Nicer presentation of model-level validation errors
            $msgs = _t(__CLASS__ . '.ValidationError', 'Validation error') . ': '
                . $e->getMessage();
            $this->sessionMessage($msgs, "bad");
            return $this->redirectBack();
        }

        $title = $this->Title();

        if (!$response->getHeader('X-Title')) {
            $response->addHeader('X-Title', urlencode($title));
        }

        // Prevent clickjacking, see https://developer.mozilla.org/en-US/docs/HTTP/X-Frame-Options
        $originalResponse = $this->getResponse();
        $originalResponse->addHeader('X-Frame-Options', LeftAndMain::config()->uninherited('frame_options'));
        $originalResponse->addHeader('Vary', 'X-Requested-With');

        return $response;
    }

    /**
     * Overloaded redirection logic to trigger a fake redirect on ajax requests.
     * While this violates HTTP principles, its the only way to work around the
     * fact that browsers handle HTTP redirects opaquely, no intervention via JS is possible.
     * In isolation, that's not a problem - but combined with history.pushState()
     * it means we would request the same redirection URL twice if we want to update the URL as well.
     *
     * @param string $url
     * @param int $code
     * @return HTTPResponse
     */
    public function redirect($url, $code = 302): HTTPResponse
    {
        $response = parent::redirect($url, $code);
        if ($this->getRequest()->isAjax()) {
            return $this->redirectForAjax($response);
        }
        return $response;
    }

    public function redirectForAjax(HTTPResponse $response): HTTPResponse
    {
        if ($this->getRequest()->isAjax() && $response->getHeader('Location')) {
            $newResponse = LeftAndMain_HTTPResponse::cloneFrom($response);
            $newResponse->setStatusCode(200);
            $newResponse->removeHeader('Location');
            $newResponse->addHeader('X-Location', $response->getHeader('Location'));
            $newResponse->setIsFinished(true);
            $this->setResponse($newResponse);
            return $newResponse;
        }
        return $response;
    }

    public function redirectWithStatus($msg): HTTPResponse
    {
        $response = $this->redirectBack();
        $response->addHeader('X-Status', rawurlencode($msg));
        return $response;
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse|DBHTMLText
     */
    public function index($request)
    {
        return $this->renderWith($this->getViewer('show'));
    }

    /**
     * You should implement a Link() function in your subclass of LeftAndMain,
     * to point to the URL of that particular controller.
     *
     * @param string $action
     * @return string
     */
    public function Link($action = null)
    {
        // LeftAndMain methods have a top-level uri access
        if (static::class === LeftAndMain::class) {
            $segment = '';
        } else {
            // Get url_segment
            $segment = $this->config()->get('url_segment');
            if (!$segment) {
                throw new BadMethodCallException(
                    sprintf('LeftAndMain subclasses (%s) must have url_segment', static::class)
                );
            }
        }

        $link = Controller::join_links(
            AdminiRootController::admin_url(),
            $segment,
            '/', // trailing slash needed if $action is null!
            "$action"
        );
        $this->extend('updateLink', $link);
        return $link;
    }

    /**
     * Get menu title for this section (translated)
     *
     * @param string $class Optional class name if called on LeftAndMain directly
     * @param bool $localise Determine if menu title should be localised via i18n.
     * @return string Menu title for the given class
     */
    public static function menu_title($class = null, $localise = true)
    {
        if ($class && is_subclass_of($class, __CLASS__)) {
            // Respect oveloading of menu_title() in subclasses
            return $class::menu_title(null, $localise);
        }
        if (!$class) {
            $class = get_called_class();
        }

        // Get default class title
        $title = static::config()->get('menu_title');
        if (!$title) {
            $title = preg_replace('/Admin$/', '', $class);
        }

        // Check localisation
        if (!$localise) {
            return $title;
        }
        return i18n::_t("{$class}.MENUTITLE", $title);
    }

    /**
     * Return the name for the menu icon
     * @param string $class
     * @return string
     */
    public static function menu_icon_for_class($class)
    {
        return Config::inst()->get($class, 'menu_icon');
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse|DBHTMLText
     * @throws HTTPResponse_Exception
     */
    public function show($request)
    {
        // TODO Necessary for TableListField URLs to work properly
        // TODO: check why this is needed
        // if ($request->param('ID')) {
        //     $this->setCurrentPageID($request->param('ID'));
        // }
        return $this->renderWith($this->getViewer('show'));
    }

    //------------------------------------------------------------------------------------------//
    // Main UI components

    /**
     * Returns the main menu of the CMS.  This is also used by init()
     * to work out which sections the user has access to.
     *
     * @param bool $cached
     * @return SS_List
     */
    public function MainMenu($cached = true)
    {
        static $menuCache = null;
        if ($menuCache === null || !$cached) {
            // Don't accidentally return a menu if you're not logged in - it's used to determine access.
            if (!Security::getCurrentUser()) {
                return new ArrayList();
            }

            // Encode into DO set
            $menu = new ArrayList();
            $menuItems = CMSMenu::get_viewable_menu_items();

            // extra styling for custom menu-icons
            $menuIconStyling = '';

            if ($menuItems) {
                /** @var CMSMenuItem $menuItem */
                foreach ($menuItems as $code => $menuItem) {
                    // alternate permission checks (in addition to LeftAndMain->canView())
                    $alternateCheck = isset($menuItem->controller)
                        && $this->hasMethod('alternateMenuDisplayCheck')
                        && !$this->alternateMenuDisplayCheck($menuItem->controller);
                    if ($alternateCheck) {
                        continue;
                    }

                    // linking mode
                    $linkingmode = "link";
                    if ($menuItem->controller && get_class($this) == $menuItem->controller) {
                        $linkingmode = "current";
                    } elseif (strpos($this->Link(), $menuItem->url) !== false) {
                        if ($this->Link() == $menuItem->url) {
                            $linkingmode = "current";

                            // default menu is the one with a blank {@link url_segment}
                        } elseif (singleton($menuItem->controller)->config()->get('url_segment') == '') {
                            if ($this->Link() == AdminiRootController::admin_url()) {
                                $linkingmode = "current";
                            }
                        } else {
                            $linkingmode = "current";
                        }
                    }

                    // already set in CMSMenu::populate_menu(), but from a static pre-controller
                    // context, so doesn't respect the current user locale in _t() calls - as a workaround,
                    // we simply call LeftAndMain::menu_title() again
                    // if we're dealing with a controller
                    if ($menuItem->controller) {
                        $title = LeftAndMain::menu_title($menuItem->controller);
                    } else {
                        $title = $menuItem->title;
                    }

                    // Provide styling for custom $menu-icon. Done here instead of in
                    // CMSMenu::populate_menu(), because the icon is part of
                    // the CMS right pane for the specified class as well...
                    $IconName = '';
                    if ($menuItem->controller) {
                        $IconName = LeftAndMain::menu_icon_for_class($menuItem->controller);
                    } else {
                        $IconName = $menuItem->iconName;
                    }
                    if (!$IconName) {
                        $IconName = "arrow_right";
                    }
                    $menuItem->addExtraClass("sidebar-link");

                    $menu->push(new ArrayData([
                        "MenuItem" => $menuItem,
                        "AttributesHTML" => $menuItem->getAttributesHTML(),
                        "Title" => $title,
                        "Code" => $code,
                        "IconName" => $IconName,
                        "Link" => $menuItem->url,
                        "LinkingMode" => $linkingmode
                    ]));
                }
            }
            if ($menuIconStyling) {
                Requirements::customCSS($menuIconStyling);
            }

            $menuCache = $menu;
        }

        return $menuCache;
    }

    public function Menu()
    {
        return $this->renderWith($this->getTemplatesWithSuffix('_Menu'));
    }

    /**
     * @todo Wrap in CMSMenu instance accessor
     * @return ArrayData A single menu entry (see {@link MainMenu})
     */
    public function MenuCurrentItem()
    {
        $items = $this->MainMenu();
        return $items->find('LinkingMode', 'current');
    }

    /**
     * Return appropriate template(s) for this class, with the given suffix using
     * {@link SSViewer::get_templates_by_class()}
     *
     * @param string $suffix
     * @return string|array
     */
    public function getTemplatesWithSuffix($suffix)
    {
        $templates = SSViewer::get_templates_by_class(get_class($this), $suffix, __CLASS__);
        return SSViewer::chooseTemplate($templates);
    }

    public function Content()
    {
        return $this->renderWith($this->getTemplatesWithSuffix('_Content'));
    }

    /**
     * Get dataobject from the current ID
     *
     * @param int|DataObject $id ID or object
     * @return DataObject
     */
    public function getRecord($id = null)
    {
        if ($id === null) {
            $id = $this->currentPageID();
        }
        $className = $this->config()->get('tree_class');
        if (!$className) {
            return null;
        }
        if ($id instanceof $className) {
            /** @var DataObject $id */
            return $id;
        }
        if ($id === 'root') {
            return DataObject::singleton($className);
        }
        if (is_numeric($id)) {
            return DataObject::get_by_id($className, $id);
        }
        return null;
    }

    /**
     * Called by CMSBreadcrumbs.ss
     * @param bool $unlinked
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false)
    {
        $items = new ArrayList(array(
            new ArrayData(array(
                'Title' => $this->menu_title(),
                'Link' => ($unlinked) ? false : $this->Link()
            ))
        ));

        return $items;
    }

    /**
     * Save  handler
     *
     * @param array $data
     * @param Form $form
     * @return HTTPResponse
     */
    public function save($data, $form)
    {
        $request = $this->getRequest();
        $className = $this->config()->get('tree_class');

        // Existing or new record?
        $id = $data['ID'];
        if (is_numeric($id) && $id > 0) {
            $record = DataObject::get_by_id($className, $id);
            if ($record && !$record->canEdit()) {
                return Security::permissionFailure($this);
            }
            if (!$record || !$record->ID) {
                $this->httpError(404, "Bad record ID #" . (int)$id);
            }
        } else {
            if (!singleton($this->config()->get('tree_class'))->canCreate()) {
                return Security::permissionFailure($this);
            }
            $record = $this->getNewItem($id, false);
        }

        // save form data into record
        $form->saveInto($record, true);
        $record->write();
        $this->extend('onAfterSave', $record);

        //TODO: investigate if this is needed
        // $this->setCurrentPageID($record->ID);

        $message = _t(__CLASS__ . '.SAVEDUP', 'Saved.');
        $this->sessionMessage($message, "good");
        return $this->redirectBack();
    }

    /**
     * Create new item.
     *
     * @param string|int $id
     * @param bool $setID
     * @return DataObject
     */
    public function getNewItem($id, $setID = true)
    {
        $class = $this->config()->get('tree_class');
        $object = Injector::inst()->create($class);
        if ($setID) {
            $object->ID = $id;
        }
        return $object;
    }

    public function delete($data, $form)
    {
        $className = $this->config()->get('tree_class');

        $id = $data['ID'];
        $record = DataObject::get_by_id($className, $id);
        if ($record && !$record->canDelete()) {
            return Security::permissionFailure();
        }
        if (!$record || !$record->ID) {
            $this->httpError(404, "Bad record ID #" . (int)$id);
        }

        $record->delete();
        $this->sessionMessage(_t(__CLASS__ . '.DELETED', 'Deleted.'));
        return $this->redirectBack();
    }

    /**
     * Retrieves an edit form, either for display, or to process submitted data.
     * Also used in the template rendered through {@link Right()} in the $EditForm placeholder.
     *
     * This is a "pseudo-abstract" methoed, usually connected to a {@link getEditForm()}
     * method in an entwine subclass. This method can accept a record identifier,
     * selected either in custom logic, or through {@link currentPageID()}.
     * The form usually construct itself from {@link DataObject->getCMSFields()}
     * for the specific managed subclass defined in {@link LeftAndMain::$tree_class}.
     *
     * @param HTTPRequest $request Passed if executing a HTTPRequest directly on the form.
     * If empty, this is invoked as $EditForm in the template
     * @return Form Should return a form regardless wether a record has been found.
     *  Form might be readonly if the current user doesn't have the permission to edit
     *  the record.
     */
    public function EditForm($request = null)
    {
        return $this->getEditForm();
    }

    /**
     * Calls {@link SiteTree->getCMSFields()} by default to determine the form fields to display.
     *
     * @param int $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        if ($id === null || $id === 0) {
            $id = $this->currentPageID();
        }

        // Check record exists
        $record = $this->getRecord($id);
        if (!$record) {
            return null;
        }

        // Check if this record is viewable
        if ($record && !$record->canView()) {
            $response = Security::permissionFailure($this);
            $this->setResponse($response);
            return null;
        }

        $fields = $fields ?: $record->getCMSFields();
        if (!$fields) {
            throw new LogicException(
                "getCMSFields() returned null  - it should return a FieldList object.
                Perhaps you forgot to put a return statement at the end of your method?"
            );
        }

        // Add hidden fields which are required for saving the record
        // and loading the UI state
        if (!$fields->dataFieldByName('ClassName')) {
            $fields->push(new HiddenField('ClassName'));
        }

        $tree_class = $this->config()->get('tree_class');
        $needParentID = $tree_class::has_extension(Hierarchy::class) && !$fields->dataFieldByName('ParentID');
        if ($needParentID) {
            $fields->push(new HiddenField('ParentID'));
        }

        if ($record->hasMethod('getAdminiActions')) {
            $actions = $record->getAdminiActions();
        } else {
            $actions = $record->getCMSActions();
            // add default actions if none are defined
            if (!$actions || !$actions->count()) {
                if ($record->hasMethod('canEdit') && $record->canEdit()) {
                    $actions->push(
                        FormAction::create('save', _t('LeKoala\\Admini\\LeftAndMain.SAVE', 'Save'))
                            ->addExtraClass('btn btn-outline-primary')
                            ->setIcon(MaterialIcons::DONE)
                    );
                }
                if ($record->hasMethod('canDelete') && $record->canDelete()) {
                    $actions->push(
                        FormAction::create('delete', _t('LeKoala\\Admini\\LeftAndMain.DELETE', 'Delete'))
                            ->addExtraClass('btn btn-danger')
                            ->setIcon(MaterialIcons::DELETE)
                    );
                }
            }
        }

        $form = Form::create(
            $this,
            "EditForm",
            $fields,
            $actions
        )->setHTMLID('Form_EditForm');
        $form->addExtraClass('cms-edit-form needs-validation');
        $form->loadDataFrom($record);
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->setAttribute('data-validation-message', _t('LeKoala\\Admini\\LeftAndMain.FIELDS_INVALID', 'Some fields are invalid'));

        //TODO: check if this is needed
        $form->setRequestHandler(LeftAndMainFormRequestHandler::create($form));

        $validator = $record->getCMSCompositeValidator();
        if ($validator) {
            $form->setValidator($validator);
        } else {
            $form->unsetValidator();
        }

        // Check if this form is readonly
        if (!$record->canEdit()) {
            $readonlyFields = $form->Fields()->makeReadonly();
            $form->setFields($readonlyFields);
        }
        return $form;
    }

    /**
     * Returns a placeholder form, used by {@link getEditForm()} if no record is selected.
     * Our javascript logic always requires a form to be present in the CMS interface.
     *
     * @return Form
     */
    public function EmptyForm()
    {
        //TODO: check if this is needed. It's not very elegant
        $form = Form::create(
            $this,
            "EditForm",
            new FieldList(),
            new FieldList()
        )->setHTMLID('Form_EditForm');
        $form->unsetValidator();
        $form->addExtraClass('cms-edit-form');
        $form->addExtraClass('root-form');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');

        return $form;
    }

    /**
     * Renders a panel containing tools which apply to all displayed
     * "content" (mostly through {@link EditForm()}), for example a tree navigation or a filter panel.
     * Auto-detects applicable templates by naming convention: "<controller classname>_Tools.ss",
     * and takes the most specific template (see {@link getTemplatesWithSuffix()}).
     * To explicitly disable the panel in the subclass, simply create a more specific, empty template.
     *
     * @return string|bool HTML
     */
    public function Tools()
    {
        $templates = $this->getTemplatesWithSuffix('_Tools');
        if ($templates) {
            $viewer = SSViewer::create($templates);
            return $viewer->process($this);
        }
        return false;
    }

    /**
     * Renders a panel containing tools which apply to the currently displayed edit form.
     * The main difference to {@link Tools()} is that the panel is displayed within
     * the element structure of the form panel (rendered through {@link EditForm}).
     * This means the panel will be loaded alongside new forms, and refreshed upon save,
     * which can mean a performance hit, depending on how complex your panel logic gets.
     * Any form fields contained in the returned markup will also be submitted with the main form,
     * which might be desired depending on the implementation details.
     *
     * @return string|bool HTML
     */
    public function EditFormTools()
    {
        $templates = $this->getTemplatesWithSuffix('_EditFormTools');
        if ($templates) {
            $viewer = SSViewer::create($templates);
            return $viewer->process($this);
        }
        return false;
    }

    /**
     * @return Form|bool|array
     */
    public function printable()
    {
        $form = $this->getEditForm($this->currentPageID());
        if (!$form) {
            return false;
        }

        $form->transform(new PrintableTransformation());
        $form->setActions(null);

        Requirements::clear();

        // TODO: check admini print styles ?
        // Requirements::css('silverstripe/admin: dist/css/LeftAndMain_printable.css');

        return array(
            "PrintForm" => $form
        );
    }

    /**
     * Identifier for the currently shown record,
     * in most cases a database ID. Inspects the following
     * sources (in this order):
     * - GET/POST parameter named 'ID'
     * - URL parameter named 'ID'
     * - Session value namespaced by classname, e.g. "CMSMain.currentPage"
     *
     * @return int
     */
    public function currentPageID()
    {
        if ($this->pageID) {
            return $this->pageID;
        }
        if ($this->getRequest()->requestVar('ID') && is_numeric($this->getRequest()->requestVar('ID'))) {
            return $this->getRequest()->requestVar('ID');
        }

        if ($this->getRequest()->requestVar('CMSMainCurrentPageID') && is_numeric($this->getRequest()->requestVar('CMSMainCurrentPageID'))) {
            // see GridFieldDetailForm::ItemEditForm
            return $this->getRequest()->requestVar('CMSMainCurrentPageID');
        }

        if (isset($this->urlParams['ID']) && is_numeric($this->urlParams['ID'])) {
            return $this->urlParams['ID'];
        }

        if (is_numeric($this->getRequest()->param('ID'))) {
            return (int)$this->getRequest()->param('ID');
        }

        /** @deprecated */
        //TODO: check if we can remove this altogether
        $session = $this->getRequest()->getSession();
        return $session->get($this->sessionNamespace() . ".currentPage") ?: null;
    }

    /**
     * Uses {@link getRecord()} and {@link currentPageID()}
     * to get the currently selected record.
     *
     * @return DataObject
     */
    public function currentPage()
    {
        return $this->getRecord($this->currentPageID());
    }

    /**
     * Compares a given record to the currently selected one (if any).
     * Used for marking the current tree node.
     *
     * @param DataObject $record
     * @return bool
     */
    public function isCurrentPage(DataObject $record)
    {
        return ($record->ID == $this->currentPageID());
    }

    /**
     * @return string
     */
    protected function sessionNamespace()
    {
        $override = $this->config()->get('session_namespace');
        return $override ? $override : static::class;
    }

    /**
     * Return the version number of this application, ie. 'CMS: 4.2.1'
     *
     * @return string
     */
    public function CMSVersion()
    {
        return $this->getVersionProvider()->getVersion();
    }

    /**
     * Return the version number of the CMS in the 'major.minor' format, e.g. '4.2'
     * Will handle 4.10.x-dev by removing .x-dev
     *
     * @return string
     */
    public function CMSVersionNumber()
    {
        $moduleName = array_keys($this->getVersionProvider()->getModules())[0];
        $lockModules = $this->getVersionProvider()->getModuleVersionFromComposer([$moduleName]);
        if (!isset($lockModules[$moduleName])) {
            return '';
        }
        $version = $lockModules[$moduleName];
        if (preg_match('#^([0-9]+)\.([0-9]+)#', $version, $m)) {
            return $m[1] . '.' . $m[2];
        }
        return $version;
    }

    /**
     * @return SiteConfig
     */
    public function SiteConfig()
    {
        return class_exists(SiteConfig::class) ? SiteConfig::current_site_config() : null;
    }

    /**
     * Returns help_links in a format readable by a template
     * @return ArrayList
     */
    public function getHelpLinks()
    {
        $helpLinks = $this->config()->get('help_links');
        $formattedLinks = [];

        $helpLink = $this->config()->get('help_link');
        if ($helpLink) {
            Deprecation::notice('5.0', 'Use $help_links instead of $help_link');
            $helpLinks['CMS User help'] = $helpLink;
        }

        foreach ($helpLinks as $key => $value) {
            $translationKey = str_replace(' ', '', $key);

            $formattedLinks[] = [
                'Title' => _t(__CLASS__ . '.' . $translationKey, $key),
                'URL' => $value
            ];
        }

        return ArrayList::create($formattedLinks);
    }

    /**
     * @return string
     */
    public function ApplicationLink()
    {
        return $this->config()->get('application_link');
    }

    /**
     * Get the application name.
     *
     * @return string
     */
    public function getApplicationName()
    {
        return $this->config()->get('application_name');
    }

    /**
     * @return string
     */
    public function Title()
    {
        $app = $this->getApplicationName();
        return ($section = $this->SectionTitle()) ? sprintf('%s - %s', $app, $section) : $app;
    }

    /**
     * Return the title of the current section. Either this is pulled from
     * the current panel's menu_title or from the first active menu
     *
     * @return string
     */
    public function SectionTitle()
    {
        $title = $this->menu_title();
        if ($title) {
            return $title;
        }

        foreach ($this->MainMenu() as $menuItem) {
            if ($menuItem->LinkingMode != 'link') {
                return $menuItem->Title;
            }
        }
        return null;
    }

    /**
     * Generate a logout url with BackURL to the CMS
     *
     * @return string
     */
    public function LogoutURL()
    {
        return Controller::join_links(Security::logout_url(), '?' . http_build_query([
            'BackURL' => AdminiRootController::admin_url(),
        ]));
    }

    /**
     * Same as {@link ViewableData->CSSClasses()}, but with a changed name
     * to avoid problems when using {@link ViewableData->customise()}
     * (which always returns "ArrayData" from the $original object).
     *
     * @return string
     */
    public function BaseCSSClasses()
    {
        return $this->CSSClasses(Controller::class);
    }

    /**
     * @return string
     */
    public function Locale()
    {
        return DBField::create_field('Locale', i18n::get_locale());
    }

    public function providePermissions()
    {
        $perms = array(
            "CMS_ACCESS_LeftAndMain" => array(
                'name' => _t(__CLASS__ . '.ACCESSALLINTERFACES', 'Access to all CMS sections'),
                'category' => _t('SilverStripe\\Security\\Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
                'help' => _t(__CLASS__ . '.ACCESSALLINTERFACESHELP', 'Overrules more specific access settings.'),
                'sort' => -100
            )
        );

        // Add any custom ModelAdmin subclasses. Can't put this on ModelAdmin itself
        // since its marked abstract, and needs to be singleton instanciated.
        foreach (ClassInfo::subclassesFor(ModelAdmin::class) as $i => $class) {
            if ($class === ModelAdmin::class) {
                continue;
            }
            if (ClassInfo::classImplements($class, TestOnly::class)) {
                continue;
            }

            // Check if modeladmin has explicit required_permission_codes option.
            // If a modeladmin is namespaced you can apply this config to override
            // the default permission generation based on fully qualified class name.
            $code = $class::getRequiredPermissions();

            if (!$code) {
                continue;
            }
            // Get first permission if multiple specified
            if (is_array($code)) {
                $code = reset($code);
            }
            $title = LeftAndMain::menu_title($class);
            $perms[$code] = array(
                'name' => _t(
                    'LeKoala\\Admini\\LeftAndMain.ACCESS',
                    "Access to '{title}' section",
                    "Item in permission selection identifying the admin section. Example: Access to 'Files & Images'",
                    array('title' => $title)
                ),
                'category' => _t('SilverStripe\\Security\\Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
            );
        }

        return $perms;
    }

    /**
     * Set the SilverStripe version provider to use
     *
     * @param VersionProvider $provider
     * @return $this
     */
    public function setVersionProvider(VersionProvider $provider)
    {
        $this->versionProvider = $provider;
        return $this;
    }

    /**
     * Get the SilverStripe version provider
     *
     * @return VersionProvider
     */
    public function getVersionProvider()
    {
        if (!$this->versionProvider) {
            $this->versionProvider = new VersionProvider();
        }
        return $this->versionProvider;
    }
}
