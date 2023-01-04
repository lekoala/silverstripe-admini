<?php

namespace LeKoala\Admini\Traits;

use Exception;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationResult;

/**
 * @link https://getbootstrap.com/docs/5.0/components/toasts/
 */
trait Toasts
{
    /**
     * @return HTTPRequest
     */
    abstract public function getRequest();

    /**
     * Set a message to the session, for display next time a page is shown.
     *
     * @param string $message the text of the message
     * @param string $type Should be set to good, bad, or warning.
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * @return void
     */
    public function sessionMessage($message, $type = ValidationResult::TYPE_ERROR, $cast = ValidationResult::CAST_TEXT)
    {
        $color = "primary";
        switch ($type) {
            case "bad":
            case ValidationResult::TYPE_ERROR:
                $color = "danger";
                break;
            case "success":
            case ValidationResult::TYPE_GOOD:
                $color = "success";
                break;
            case ValidationResult::TYPE_WARNING:
                $color = "warning";
                break;
        }
        $this->getRequest()->getSession()->set('ToastMessage', [
            'Message' => $message,
            'Type' => $type,
            'ThemeColor' => $color,
            'Cast' => $cast,
        ]);
    }

    /**
     * An helper for sessionMessage
     */
    public function successMessage(string $message, string $cast = ValidationResult::CAST_TEXT)
    {
        $this->sessionMessage($message, ValidationResult::TYPE_GOOD, $cast);
    }

    /**
     * An helper for sessionMessage
     */
    public function errorMessage(string $message, string $cast = ValidationResult::CAST_TEXT)
    {
        $this->sessionMessage($message, ValidationResult::TYPE_ERROR, $cast);
    }

    /**
     * An helper for sessionMessage
     */
    public function warningMessage(string $message, string $cast = ValidationResult::CAST_TEXT)
    {
        $this->sessionMessage($message, ValidationResult::TYPE_WARNING, $cast);
    }

    public function showToasterMessage()
    {
        $ToastMessage = $this->ToastMessage();
        if ($ToastMessage) {
            $Body = addslashes($ToastMessage->Message);

            // Don't hide errors automatically
            $autohide = $ToastMessage->Type == ValidationResult::TYPE_GOOD ? "true" : "false";
            $autohide = true;
            $toastScript = <<<JS
toaster({
    body: '{$Body}',
    className: 'border-0 bg-{$ToastMessage->ThemeColor} text-white',
    autohide: {$autohide}
});
JS;
            Requirements::customScript($toastScript, __FUNCTION__);
        }
    }

    /**
     * Get the toast message
     *
     * @return ArrayData
     */
    public function ToastMessage()
    {
        $session = $this->getRequest()->getSession();
        try {
            $ToastMessage = $session->get('ToastMessage');
        } catch (Exception $ex) {
            $ToastMessage = null; // Session can be null (eg : Security)
        }
        if (!$ToastMessage) {
            return;
        }
        $session->clear('ToastMessage');
        return new ArrayData($ToastMessage);
    }
}
