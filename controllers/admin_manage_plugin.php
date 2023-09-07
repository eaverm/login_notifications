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
         $pluginId = $this->get[0] ?? null;

         // Create a new instance of the Login Notifications Plugin
         $plugin = $this->Plugins->create('login_notifications');

         // Initialize settings as an empty object or load existing settings
         $settings = $this->LoginNotificationsSettings
             ? unserialize($this->LoginNotificationsSettings->value)
             : (object)[];

         // Handle form submissions (redirect if the form is submitted)
         if (!empty($this->post)) {
             // Save the updated settings to the database
             $this->Companies->setSetting($this->company_id, 'LoginNotificationsPlugin', serialize($this->post));
             $settings = (object)$this->post;

             // Flash a success message
             $this->parent->flashmessage('message', Language::_('LoginNotificationsPlugin.!success.save', true));

             // Redirect to the appropriate plugin management page
             $this->redirect($this->base_uri . 'settings/company/plugins/manage/' . $pluginId . '/');
         }

         // Set variables for the view
         $this->set('plugin_id', $pluginId);
         $this->set('settings', $settings);

         // Render the admin_manage_plugin partial with updated data
         return $this->partial('admin_manage_plugin', compact("settings", "pluginId"));
     }
}
