<?php
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * Login Notifications Plugin Class.
 *
 * This class represents a plugin in a Blesta-based application that handles login notifications.
 * It extends the base Plugin class and implements the EventInterface for event handling.
 */
class LoginNotificationsPlugin extends Plugin
{
  /**
   * Constructor for the plugin.
   */
  public function __construct()
  {
      // Load components required by this plugin
      Loader::loadComponents($this, ['Input', 'Record', 'Session', 'SettingsCollection', 'Net']);

      // Load Plugin Model
      // Todo: Load as needed
      Loader::loadModels($this, [
          'Settings',
          'Permissions',
          'PluginManager',
          'Plugins',
          'Clients',
          'Staff',
          'Emails',
          'EmailGroups',
          'Languages',
          'Date',
          'Companies',
          'Logs'
      ]);

      // Set company ID
      $this->company_id = Configure::get('Blesta.company_id');

      // Load language files for the plugin
      Language::loadLang('login_notifications_plugin', null, dirname(__FILE__) . DS . 'language' . DS);

      // Load plugin configuration from a JSON file
      $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
  }

    /**
     * Handle the installation of the plugin.
     *
     * @param int $plugin_id The ID of the plugin being installed.
     */
    public function install($plugin_id)
    {
        // Load configuration settings specific to the plugin
        Configure::load('login_notifications', dirname(__FILE__) . DS . 'config' . DS);

        // Retrieve the list of languages configured for the company
        $languages = $this->Languages->getAll(Configure::get('Blesta.company_id'));

        // Retrieve email configurations from the plugin's settings
        $emails = Configure::get('LoginNotifications.install.emails');

        // Define initial settings values
        $LoginNotificationsSettings = [
            'enable_admin_login_emails' => true,
            'enable_client_login_emails' => false,
            'send_on_new_ip' => false,
        ];

        // Set initial settings values
        $this->Companies->setSetting(Configure::get('Blesta.company_id'), 'LoginNotificationsPlugin', serialize($LoginNotificationsSettings));

        foreach ($emails as $email) {
            // Check if an email group already exists for the specified action
            $group = $this->EmailGroups->getByAction($email['action']);

            if ($group) {
                // Use the existing email group
                $group_id = $group->id;
            } else {
                // Create a new email group if it doesn't exist
                $group_id = $this->EmailGroups->add([
                    'action' => $email['action'],
                    'type' => $email['type'],
                    'plugin_dir' => $email['plugin_dir'],
                    'tags' => $email['tags']
                ]);
            }

            // Set the 'from' email address using the company's configured hostname
            if (isset(Configure::get('Blesta.company')->hostname)) {
                $email['from'] = str_replace(
                    '@mydomain.com',
                    '@' . Configure::get('Blesta.company')->hostname,
                    $email['from']
                );
            }

            // Add an email template associated with the email group
            $data = $this->Emails->add([
                'email_group_id' => $group_id,
                'company_id' => Configure::get('Blesta.company_id'),
                'lang' => 'en_us', // Language for the template (e.g., English)
                'from' => $email['from'],
                'from_name' => $email['from_name'],
                'subject' => $email['subject'],
                'text' => $email['text'],
                'html' => $email['html']
            ]);
        }
    }

    /**
     * Handle the uninstallation of the plugin.
     *
     * @param int $plugin_id The ID of the plugin being uninstalled.
     * @param bool $last_instance Indicates if this is the last instance of the plugin.
     */
    public function uninstall($plugin_id, $last_instance)
    {
        // Check if this is the last instance of the plugin (only perform cleanup if it's the last one)
        if ($last_instance) {

            // Load necessary models and configuration files
            Loader::loadModels($this, ['Emails', 'EmailGroups']);
            Configure::load('login_notifications', dirname(__FILE__) . DS . 'config' . DS);

            // Remove plugin settings from the company configuration
            $this->Companies->unsetSetting(Configure::get('Blesta.company_id'), 'LoginNotificationsPlugin');

            // Get the list of emails to be removed from the plugin's configuration
            $emails = Configure::get('LoginNotifications.install.emails');

            // Remove emails and email groups as necessary
            foreach ($emails as $email) {
                // Fetch the email template group created by this plugin based on the email action
                $group = $this->EmailGroups->getByAction($email['action']);

                // Delete all email templates belonging to this plugin's email group and company
                if ($group) {
                    $this->Emails->deleteAll($group->id, Configure::get('Blesta.company_id'));

                    if ($last_instance) {
                        // If this is the last instance of the plugin, also delete the email group
                        $this->EmailGroups->delete($group->id);
                    }
                }
            }
        }
    }


    /**
     * Define the events and their respective callback methods.
     *
     * @return array An array of event definitions.
     */
    public function getEvents()
    {
        return [
            [
                'event' => 'Users.login', // The event to listen for (User login event)
                'callback' => ['this', 'loginHandler'] // The callback method to execute when the event occurs
            ]
        ];
    }

    /**
     * Handler for the Users.login event
     *
     * @param EventInterface $event The event to process
     */
    public function loginHandler(EventInterface $event)
    {

        // Get event parameters and return value
        $params = $event->getParams();
        $return = $event->getReturnValue();

        // Set the timezone of the $this->Date object to the company's configured timezone in Blesta.
        $this->Date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));

        // Get LoginNotifications plugin settings
        $LoginNotificationsSettings = $this->Companies->getSetting($this->company_id, 'LoginNotificationsPlugin');
        $settings = unserialize($LoginNotificationsSettings->value);

        // Search for user logs
        $user_logs = $this->Logs->searchUserLogs(['user_id' => $params['user_id']]);

        // Check if the count of user logs is less than 2
        if (count($user_logs) < 2) {
            // Set the return value for the event and exit the function
            return $event->setReturnValue($return);
        } else {
          // Check if "send_on_new_ip" key exists in the settings array and $_SERVER['REMOTE_ADDR'] is set
          if (isset($settings['send_on_new_ip']) && $settings['send_on_new_ip'] == true && isset($_SERVER['REMOTE_ADDR'])) {
              // Check if the IP address matches the one from the 2nd item (previous login) in the array
              if ($_SERVER['REMOTE_ADDR'] == $user_logs[1]->ip_address) {
                  // Set the return value for the event and exit the function
                  return $event->setReturnValue($return);
              }
          }
      }

        // Check whether GeoIp is enabled
        $system_settings = $this->SettingsCollection->fetchSystemSettings();
        $use_geo_ip = ($system_settings['geoip_enabled'] == 'true');

        if ($use_geo_ip) {
            // Create a NetGeoIp instance if GeoIp is enabled
            if (!isset($this->NetGeoIp)) {
                $this->NetGeoIp = $this->Net->create('NetGeoIp');
            }

            // Get GeoIp location data for the user's IP address
            $geoData = $this->NetGeoIp->getLocation($_SERVER['REMOTE_ADDR']);
        } else {
          $geoData = null;
        }

        // Check if it's a staff login or a client login
        $staff_login = $this->Staff->getByUserId($params['user_id']);

        if ($staff_login === false) {
            // It's a client login
            $client_login = $this->Clients->getByUserId($params['user_id']);
            $this->sendLoginNotificationEmail('client', $client_login, $settings, $geoData, $params);
        } else {
            // It's a staff login
            $this->sendLoginNotificationEmail('admin', $staff_login, $settings, $geoData, $params);
        }

        // Set the return value for the event
        $event->setReturnValue($return);
    }

    /**
     * Sends a login notification email based on the user type (client or admin).
     *
     * @param string $type      The user type ('client' or 'admin').
     * @param object $user      The user object containing user information.
     * @param array  $settings  The notification settings.
     * @param array  $geoData  The GeoIP location data.
     * @param array  $params    The event parameters.
     */
     public function sendLoginNotificationEmail($type, $user, $settings, $geoData, $params)
     {
         // Check if email notifications are enabled for the specific user type
         if ($settings['enable_' . $type . '_login_emails'] == true) {
             // Initialize the tags array
             $tags = array(
                 'ip_address' => $_SERVER['REMOTE_ADDR'],
                 'user' => $user->username,
                 'date' => $this->Date->cast(time(), 'D, d M Y'),
                 'time' => $this->Date->cast(time(), 'g:i:s A'),
                 'recovery_link' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . Configure::get('Blesta.company')->hostname . '/' . Configure::get('Route.' . $type) . '/login/' . ($type == 'client' ? 'forgot' : 'reset'),
                 'first_name' => $user->first_name,
             );

             // Check if $geoData is not null and contains the expected elements
             if (!empty($geoData)) {
                 // Initialize the location string with the country name
                 $location = $geoData['country_name'];

                 // Check if city and region are not empty, and append them to the location string if they exist
                 if (!empty($geoData['city']) && !empty($geoData['region'])) {
                     $location = $geoData['city'] . ', ' . $geoData['region'] . ' - ' . $location;
                 } elseif (!empty($geoData['city'])) {
                     $location = $geoData['city'] . ' - ' . $location;
                 } elseif (!empty($geoData['region'])) {
                     $location = $geoData['region'] . ' - ' . $location;
                 }

                 // Assign the location string to the tags array
                 $tags['location'] = $location;
             }

             // Send login notification email
             $this->Emails->send(
                 'LoginNotifications.' . $type . 'login',
                 Configure::get("Blesta.company_id"),
                 NULL,
                 $user->email,
                 $tags,
                 NULL,
                 NULL,
                 NULL,
                 array('to_client_id' => $params['user_id'])
             );
         }
     }

}
