<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web;

use Craft;

/**
 * Extends \yii\web\Session to add support for setting the session folder and creating it if it doesn’t exist.
 *
 * An instance of the HttpSession service is globally accessible in Craft via [[Application::httpSession `Craft::$app->getSession()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Session extends \yii\web\Session
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The session variable name used to store the authorization keys for the current session.
     * @see authorize()
     * @see deauthorize()
     * @see checkAuthorization()
     */
    public $authAccessParam;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Set the state-based property names
        $stateKeyPrefix = md5('Craft.'.get_class($this).'.'.Craft::$app->id);
        $config['flashParam'] = $stateKeyPrefix.'__flash';
        $config['authAccessParam'] = $stateKeyPrefix.'__auth_access';

        // Set the session name
        $this->setName(Craft::$app->getConfig()->get('phpSessionName'));

        // Set the default session cookie params
        $this->setCookieParams(Craft::cookieConfig());

        parent::__construct($config);
    }

    // Flash Data
    // -------------------------------------------------------------------------

    /**
     * Stores a notice in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash() `getFlash('notice')`]] or [[getAllFlashes()]].
     *
     * Only one flash notice can be stored at a time.
     *
     * @param string $message The message.
     *
     * @return void
     */
    public function setNotice(string $message)
    {
        $this->setFlash('notice', $message);
    }

    /**
     * Stores an error message in the user’s flash data.
     *
     * The message will be stored on the session, and can be retrieved by calling
     * [[getFlash() `getFlash('error')`]] or [[getAllFlashes()]].
     *
     * Only one flash error message can be stored at a time.
     *
     * @param string $message The message.
     *
     * @return void
     */
    public function setError(string $message)
    {
        $this->setFlash('error', $message);
    }

    /**
     * Stores a JS file from resources/ in the user’s flash data.
     *
     * The file will be stored on the session, and can be retrieved by calling [[getJsResourceFlashes()]] or
     * [[\craft\web\View::getBodyHtml()]].
     *
     * @param string $resource The resource path to the JS file.
     *
     * @return void
     */
    public function addJsResourceFlash(string $resource)
    {
        $resources = $this->getJsResourceFlashes(false);

        if (!in_array($resource, $resources, true)) {
            $resources[] = $resource;
            $this->setFlash('jsResources', $resources);
        }
    }

    /**
     * Returns the stored JS resource flashes.
     *
     * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
     *
     * @return array The stored JS resource flashes.
     */
    public function getJsResourceFlashes(bool $delete = true): array
    {
        return $this->getFlash('jsResources', [], $delete);
    }

    /**
     * Stores JS in the user’s flash data.
     *
     * The Javascript code will be stored on the session, and can be retrieved by calling
     * [[getJsFlashes()]] or [[\craft\web\View::getBodyHtml()]].
     *
     * @param string $js The Javascript code.
     *
     * @return void
     */
    public function addJsFlash(string $js)
    {
        $scripts = $this->getJsFlashes();
        $scripts[] = $js;
        $this->setFlash('js', $scripts);
    }

    /**
     * Returns the stored JS flashes.
     *
     * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
     *
     * @return array The stored JS flashes.
     */
    public function getJsFlashes(bool $delete = true): array
    {
        return $this->getFlash('js', [], $delete);
    }

    // Session-Based Authorization
    // -------------------------------------------------------------------------

    /**
     * Authorizes the user to perform an action for the duration of the session.
     *
     * @param string $action
     *
     * @return void
     */
    public function authorize(string $action)
    {
        $access = $this->get($this->authAccessParam, []);

        if (!in_array($action, $access, true)) {
            $access[] = $action;
            $this->set($this->authAccessParam, $access);
        }
    }

    /**
     * Deauthorizes the user from performing an action.
     *
     * @param string $action
     *
     * @return void
     */
    public function deauthorize(string $action)
    {
        $access = $this->get($this->authAccessParam, []);
        $index = array_search($action, $access, true);

        if ($index !== false) {
            array_splice($access, $index, 1);
            $this->set($this->authAccessParam, $access);
        }
    }

    /**
     * Returns whether the user is authorized to perform an action.
     *
     * @param string $action
     *
     * @return bool
     */
    public function checkAuthorization(string $action): bool
    {
        $access = $this->get($this->authAccessParam, []);

        return in_array($action, $access, true);
    }
}