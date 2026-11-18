<?php
/**
 * Plugin Name: Skyrocket FluentSMTP Email Test Endpoint
 * Description: Adds a secure REST API endpoint that triggers wp_mail() so you can test FluentSMTP delivery from outside WordPress.
 * Version: 1.0.3
 * Author: Skyrocket Digital Development
 *
 * Plugin Purpose:
 * Provides an authenticated WordPress REST API endpoint for sending test
 * emails through the site's configured email delivery system.
 *
 * The endpoint uses WordPress's native wp_mail() function, allowing
 * FluentSMTP (or another configured mail integration) to handle delivery.
 * This makes it possible to test SMTP configuration and email delivery
 * remotely without requiring access to the WordPress administration area.
 *
 * REST API Endpoint:
 * POST /wp-json/mailtest/v1/send
 *
 * Authentication:
 * Requires an authenticated WordPress user with the manage_options
 * capability. WordPress Application Passwords can be used for
 * authentication when making external API requests.
 *
 * Request Parameters:
 * - to (string, required): Recipient email address.
 *
 * Successful Response:
 * - success (boolean): Whether wp_mail() accepted the message for sending.
 * - site (string): The site's URL.
 * - to (string): The sanitised recipient email address.
 * - sent_via (string): Identifies the mail delivery mechanism.
 *
 * Error Responses:
 * - 400: Missing or invalid recipient email address.
 * - 401/403: Authentication or permission failure.
 *
 * Important:
 * A successful wp_mail() result indicates that WordPress successfully
 * handed the message to its configured mail transport. It does not
 * guarantee final delivery to the recipient's inbox.
 *
 * @package SkyrocketDigital
 * @subpackage FluentSMTPEmailTestEndpoint
 * @author   Skyrocket Digital Development
 * @version  1.0
 */

defined('ABSPATH') || exit;

// GitHub updater configuration
$github_updater_config = [
    'username' => 'lavamitts',
    'repository' => 'skyrocket-fluent-smtp-email-test',
    'plugin_file' => __FILE__,
];

// Updater code
require_once __DIR__ . '/vendor/lavamitts/skyrocket-plugin-updater/src/plugin-updater.php';


/**
 * Registers and handles the FluentSMTP email test REST API endpoint.
 *
 * Provides a remotely accessible, authenticated method of triggering
 * a test email through WordPress's native mailing functionality.
 *
 * @since 1.0
 */
class FluentSMTP_Email_Test_Endpoint
{
    /**
     * Initialise the plugin.
     *
     * Registers the REST API initialisation callback so the endpoint
     * is registered when WordPress prepares its REST API routes.
     *
     * @since 1.0
     *
     * @return void
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoint']);
    }

    /**
     * Register the email test REST API endpoint.
     *
     * Registers a POST endpoint at:
     *
     * /wp-json/mailtest/v1/send
     *
     * The endpoint delegates permission checking to permissions()
     * and email processing to send_test_email().
     *
     * @since 1.0
     *
     * @return void
     */
    public function register_endpoint()
    {
        register_rest_route('mailtest/v1', '/send', [
            'methods' => 'POST',
            'callback' => [$this, 'send_test_email'],
            'permission_callback' => [$this, 'permissions']
        ]);
    }

    /**
     * Check whether the current user can access the endpoint.
     *
     * Requires the authenticated user to have the manage_options
     * capability, which is normally restricted to administrators.
     *
     * Application Passwords can be used to authenticate external
     * requests without requiring an interactive WordPress login.
     *
     * @since 1.0
     *
     * @return bool True if the user has sufficient permissions,
     *              otherwise false.
     */
    public function permissions()
    {
        return current_user_can('manage_options');
    }

    /**
     * Validate the recipient and send a test email.
     *
     * Retrieves the recipient address from the REST API request,
     * sanitises it and verifies that a valid email address has
     * been supplied.
     *
     * If validation succeeds, constructs a simple test email using
     * the WordPress site name and URL, then sends it through wp_mail().
     *
     * The configured mail integration, such as FluentSMTP, handles
     * the actual email transport.
     *
     * @since 1.0
     *
     * @param WP_REST_Request $request The incoming REST API request.
     *
     * @return array|WP_Error Response containing the sending result,
     *                         or a WP_Error if the recipient is invalid.
     */
    public function send_test_email($request)
    {
        // Retrieve and sanitise the recipient email address.
        $to = sanitize_email($request->get_param('to'));

        // Reject missing or invalid recipient addresses.
        if (empty($to) || !is_email($to)) {
            return new WP_Error(
                'invalid_to',
                'Missing or invalid "to" email address',
                ['status' => 400]
            );
        }

        // Construct the test email subject and message.
        $subject = 'Test Email from ' . get_bloginfo('name');

        $message = "This is a test email sent via the FluentSMTP test endpoint.\n\n"
            . 'Site: ' . get_site_url();

        // Send the email using WordPress's configured mail transport.
        $sent = wp_mail($to, $subject, $message);

        // Return the result as a REST API response.
        return [
            'success' => (bool) $sent,
            'site' => get_site_url(),
            'to' => $to,
            'sent_via' => 'FluentSMTP (via wp_mail)'
        ];
    }
}

// Initialise the plugin.
new FluentSMTP_Email_Test_Endpoint();