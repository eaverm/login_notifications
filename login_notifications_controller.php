<?php
/**
 * Login Notifications parent controller
 *
 * @link https://bdtech.host BDTech LLC
 */
class LoginNotificationsController extends AppController
{
    /**
     * Require admin to be login and setup the view
     */
    public function preAction()
    {
////    // This statement tells the controller to look in the core view
////    // directory instead of the plugin view directory for structure files
        $this->structure->setDefaultView(APPDIR);
        parent::preAction();

////    // These statements tell the controller to look again the plugin
////    // view directory
        // Override default view directory
        $this->view->view = "default";

        $this->requireLogin();

        // Auto load language for the controller
        Language::loadLang(
            [Loader::fromCamelCase(get_class($this))],
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );
        Language::loadLang(
            'login_notifications_controller',
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );
    }
}
