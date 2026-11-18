# FluentSMTP Email Test Endpoint

A lightweight WordPress plugin providing an authenticated REST API endpoint for testing outgoing email delivery through FluentSMTP.

## Overview

This plugin allows you to trigger a test email remotely without needing to access the WordPress administration dashboard.

It uses WordPress's native `wp_mail()` function, allowing FluentSMTP to handle delivery using the site's configured SMTP connection.

This is particularly useful for:

- Testing SMTP configuration after installation or modification.
- Verifying that WordPress can send outgoing emails.
- Testing email delivery from external scripts or applications.
- Diagnosing email delivery issues without manually triggering a WordPress notification.

## Requirements

- WordPress with REST API support.
- PHP compatible with the installed WordPress version.
- FluentSMTP or another configured WordPress mail transport.
- An authenticated WordPress administrator account.

## Installation

1. Copy the plugin PHP file into a directory within `wp-content/plugins/`.
2. Activate **FluentSMTP Email Test Endpoint** through the WordPress Plugins screen.
3. Ensure FluentSMTP is installed, configured and connected to a working mail transport.

No additional configuration is required.

## REST API

### Endpoint

```text
POST /wp-json/mailtest/v1/send
```

### Authentication

The endpoint requires an authenticated WordPress user with the `manage_options` capability.

WordPress Application Passwords are recommended for external integrations.

Generate an Application Password from:

**WordPress Admin → Users → Profile → Application Passwords**

Use HTTP Basic Authentication, supplying the WordPress username and generated Application Password.

Requests must be made over HTTPS.

### Request parameters

The endpoint accepts a single parameter:

| Parameter | Type | Required | Description |
|---|---|---|---|
| `to` | String | Yes | Recipient email address |

### Example request

Using cURL:

```bash
curl -X POST \
  "https://example.com/wp-json/mailtest/v1/send" \
  -u "wordpress_username:application_password" \
  -H "Content-Type: application/json" \
  -d '{"to":"recipient@example.com"}'
```

Replace the example domain, username, Application Password and recipient address with the appropriate values.

### Successful response

A successful request returns a JSON response similar to:

```json
{
    "success": true,
    "site": "https://example.com",
    "to": "recipient@example.com",
    "sent_via": "FluentSMTP (via wp_mail)"
}
```

The `success` property indicates whether WordPress's `wp_mail()` function reported that the message was successfully handed to the configured mail transport.

It does not independently confirm final delivery to the recipient's inbox.

### Error responses

| HTTP status | Error | Description |
|---|---|---|
| 400 | `invalid_to` | Missing or invalid recipient email address. |
| 401 | Authentication failure | Valid WordPress authentication was not supplied. |
| 403 | Permission denied | The authenticated user does not have the required capability. |

Example validation error:

```json
{
    "code": "invalid_to",
    "message": "Missing or invalid \"to\" email address",
    "data": {
        "status": 400
    }
}
```

## Email contents

The plugin generates a plain-text email with the following subject:

```text
Test Email from [Site Name]
```

The message body contains:

```text
This is a test email sent via the FluentSMTP test endpoint.

Site: https://example.com
```

The site name and URL are obtained dynamically from WordPress.

## Security considerations

- Access is restricted to authenticated users with the `manage_options` capability.
- Application Passwords provide a convenient authentication mechanism for external applications.
- HTTPS should always be used to protect authentication credentials in transit.
- Application Passwords should be treated as credentials and never exposed in publicly accessible scripts or repositories.
- Revoke Application Passwords that are no longer required.

The endpoint sends an email to the address supplied in the request. Anyone with access to an authorised administrator's credentials can therefore trigger outgoing emails.

## Technical details

| Property | Value |
|---|---|
| Plugin name | FluentSMTP Email Test Endpoint |
| Version | 1.0 |
| Namespace | `mailtest/v1` |
| Route | `/send` |
| HTTP method | POST |
| Callback | `send_test_email()` |
| Permission callback | `permissions()` |
| Mail function | `wp_mail()` |
| Required capability | `manage_options` |

## Troubleshooting

If the endpoint does not work:

1. Check that the plugin is activated.
2. Confirm that the WordPress REST API is accessible.
3. Verify that the request uses HTTPS.
4. Check that the Application Password is correct.
5. Ensure the authenticated account has the `manage


## Building a release

The plugin uses [Skyrocket Plugin Updater](https://github.com/lavamitts/skyrocket-plugin-updater) as a Composer dependency.

The updater dependency also provides the distribution build script, which prepares the plugin files for release.

After installing the Composer dependencies, run the following command from the plugin root directory:

```bash
./vendor/lavamitts/skyrocket-plugin-updater/tools/build-distribution.sh
```

This builds the release candidate, preparing a distributable version of the plugin without requiring the development dependency structure to be included in the release.

The resulting distribution package is intended for installation through the standard WordPress plugin installation process.
