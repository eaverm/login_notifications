<?php
/**
 * AdminManagePlugin Controller for managing Login Notifications Plugin settings.
 */

class AdminManagePlugin extends AppController
{
    /**
     * Initialize the controller and required components.
     */
    private function init()
    {
        // Load required models and set the company ID
        Loader::loadModels($this, ['Companies']);
        $this->company_id = Configure::get('Blesta.company_id');

        // Get settings related to the LoginNotificationsPlugin
        $this->LoginNotificationsSettings = $this->Companies->getSetting($this->company_id, 'LoginNotificationsPlugin');

        // Require login to access this page
        $this->parent->requireLogin();

        // Set the view and page title
        $this->view->setView(null, 'LoginNotifications.default');
        $this->parent->structure->set('page_title');

        // Load the PluginManager component
        $this->uses(['PluginManager']);
    }

    /**
     * Index action for displaying and handling Login Notifications Plugin settings.
     */
    public function index()
    {
        // Initialize the controller
        $this->init();
        $this->uses(['Settings', 'Plugins']);

        // Get the plugin ID from the URL or set it to null
        $plugin_id = (isset($this->get[0]) ? $this->get[0] : null);

        // Create a new instance of the Login Notifications Plugin
        $plugin = $this->Plugins->create('login_notifications');

        // Initialize settings as an empty object or load existing settings
        $settings = (object) ($this->LoginNotificationsSettings
            ? unserialize($this->LoginNotificationsSettings->value)
            : []);

        // Handle form submissions (redirect if the form is submitted)
        if (!empty($this->post)) {
            // Save the updated settings to the database
            $this->Companies->setSetting($this->company_id, 'LoginNotificationsPlugin', serialize($this->post));
            $this->setMessage('success', "Settings Saved Successfully!", false, null, false);
            $settings = (object)$this->post;
            $this->set('plugin_id', $plugin_id);
            $this->set('settings', $settings);
            $this->parent->flashmessage('message', 'Success: Settings Saved Successfully!');

            // Render the admin_manage_plugin partial with updated data
            $this->partial('admin_manage_plugin', compact("settings", "plugin_id"));

            // Redirect to the appropriate plugin management page (replace $plugin_id with the actual plugin ID)
            $this->redirect($this->base_uri . 'settings/company/plugins/manage/' . $plugin_id . '/');
        }

        // Return the view with the settings data
        return $this->partial('admin_manage_plugin', compact("settings"));
    }
}
