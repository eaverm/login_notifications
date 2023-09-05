<?php
Configure::set('LoginNotifications.install.emails', [
    // Email configuration for staff login notification
    [
        'action' => 'LoginNotifications.adminlogin',  // Action identifier for the email
        'type' => 'staff',                             // Type of user (staff)
        'plugin_dir' => 'login_notifications',          // Plugin directory
        'tags' => '{ip_address}, {user}, {date}, {time}, {recovery_link}, {first_name}',
        'from' => 'notice@mydomain.com',               // Sender email address
        'from_name' => 'Admin Panel Login',     // Sender name
        'subject' => 'Login from {ip_address}',        // Email subject
        'text' => 'Dear {first_name},

We wanted to inform you that a successful login was detected on your account.

- Date: {date}
- Time: {time}
{% if location %}
- Location: {location}
{% endif %}
- IP Address: {ip_address}

If this login was indeed you, there is no need for further action. However, if you suspect that this login was not authorized by you, we recommend taking immediate action:

1. Login to your account securely.
2. Change your password immediately.
3. Review your recent account activity.

{% if two_factor_mode == "none" %}
For added security, we also recommend enabling two-factor authentication (2FA) on your account.
{% endif %}

{recovery_link}
',
        'html' => '<p>Dear {first_name},</p>

<p>We wanted to inform you that a successful login was detected on your account.</p>

<div>
    <p><strong>Date:</strong> {date}</p>
    <p><strong>Time:</strong> {time}</p>
    {% if location %}
      <p><strong>Location:</strong> {location}</p>
    {% endif %}
    <p><strong>IP Address:</strong> {ip_address}</p>
</div>


<p>If this login was indeed you, there is no need for further action. However, if you suspect that this login was not authorized by you, we recommend taking immediate action:</p>

<ol>
    <li>Login to your account securely.</li>
    <li>Change your password immediately.</li>
    <li>Review your recent account activity.</li>
</ol>

{% if two_factor_mode == "none" %}
<p>For added security, we also recommend enabling two-factor authentication (2FA) on your account.</p>
{% endif %}

<a href="{recovery_link}">Recovery Link</a>'
    ],

    // Email configuration for client login notification
    [
        'action' => 'LoginNotifications.clientlogin', // Action identifier for the email
        'type' => 'client',                           // Type of user (client)
        'plugin_dir' => 'login_notifications',        // Plugin directory
        'tags' => '{ip_address}, {user}, {date}, {time}, {recovery_link}, {first_name}',
        'from' => 'notice@mydomain.com',             // Sender email address
        'from_name' => 'Login Notification',   // Sender name
        'subject' => 'New Login from {ip_address}',      // Email subject
        'text' => 'Dear {first_name},

We wanted to inform you that a successful login was detected on your account.

- Date: {date}
- Time: {time}
{% if location %}
- Location: {location}
{% endif %}
- IP Address: {ip_address}

If this login was indeed you, there is no need for further action. However, if you suspect that this login was not authorized by you, we recommend taking immediate action:

1. Login to your account securely.
2. Change your password immediately.
3. Review your recent account activity.

{% if two_factor_mode == "none" %}
For added security, we also recommend enabling two-factor authentication (2FA) on your account.
{% endif %}

If you have any concerns or questions, please do not hesitate to reach out to our support team.

Thank you for choosing our service.

{recovery_link}
',
        'html' => '<p>Dear {first_name},</p>

<p>We wanted to inform you that a successful login was detected on your account.</p>

<div>
    <p><strong>Date:</strong> {date}</p>
    <p><strong>Time:</strong> {time}</p>
    {% if location %}
      <p><strong>Location:</strong> {location}</p>
    {% endif %}
    <p><strong>IP Address:</strong> {ip_address}</p>
</div>

<p>If this login was indeed you, there is no need for further action. However, if you suspect that this login was not authorized by you, we recommend taking immediate action:</p>

<ol>
    <li>Login to your account securely.</li>
    <li>Change your password immediately.</li>
    <li>Review your recent account activity.</li>
</ol>

{% if two_factor_mode == "none" %}
<p>For added security, we also recommend enabling two-factor authentication (2FA) on your account.</p>
{% endif %}

<p>If you have any concerns or questions, please do not hesitate to reach out to our support team.</p>

<p>Thank you for choosing our service.</p>

<a href="{recovery_link}">Recovery Link</a>'
    ]
]);
?>
