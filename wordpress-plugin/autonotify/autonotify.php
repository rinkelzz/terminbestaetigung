<?php
/**
 * Plugin Name: AutoNotify WhatsApp Connector
 * Description: Generiert Webhook-Links für automatische WhatsApp-Benachrichtigungen nach Buchungen oder Käufen.
 * Version: 0.1.0
 * Author: AutoNotify
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoNotifyWhatsAppConnector
{
    const OPTION_KEY = 'autonotify_settings';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_admin_page()
    {
        add_options_page(
            'AutoNotify',
            'AutoNotify',
            'manage_options',
            'autonotify',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings()
    {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);

        add_settings_section(
            'autonotify_general_section',
            __('Webhook-Konfiguration', 'autonotify'),
            function () {
                echo '<p>' . esc_html__('Pflege die Basisdaten für deinen AutoNotify-Webhook.', 'autonotify') . '</p>';
            },
            'autonotify'
        );

        add_settings_field(
            'business_name',
            __('Betriebsname', 'autonotify'),
            [$this, 'render_text_field'],
            'autonotify',
            'autonotify_general_section',
            [
                'label_for' => 'business_name',
                'placeholder' => 'Friseursalon Anna'
            ]
        );

        add_settings_field(
            'whatsapp_number',
            __('WhatsApp-Nummer', 'autonotify'),
            [$this, 'render_text_field'],
            'autonotify',
            'autonotify_general_section',
            [
                'label_for' => 'whatsapp_number',
                'placeholder' => '+4915112345678'
            ]
        );

        add_settings_field(
            'backend_url',
            __('Webhook-Ziel (notify.php)', 'autonotify'),
            [$this, 'render_text_field'],
            'autonotify',
            'autonotify_general_section',
            [
                'label_for' => 'backend_url',
                'placeholder' => 'https://example.com/backend/notify.php',
                'type' => 'url',
                'description' => __('Komplette URL zu deiner installierten notify.php auf dem Keyhelp-Server.', 'autonotify'),
            ]
        );

        add_settings_field(
            'message_template',
            __('Nachrichtenvorlage', 'autonotify'),
            [$this, 'render_textarea_field'],
            'autonotify',
            'autonotify_general_section',
            [
                'label_for' => 'message_template',
                'placeholder' => 'Hallo {{name}}, dein Termin am {{datum}} wurde bestätigt!'
            ]
        );
    }

    public function render_text_field($args)
    {
        $options = get_option(self::OPTION_KEY, []);
        $value = $options[$args['label_for']] ?? '';
        $type = isset($args['type']) ? esc_attr($args['type']) : 'text';
        printf(
            '<input type="%5$s" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" placeholder="%4$s"/>',
            esc_attr($args['label_for']),
            esc_attr(self::OPTION_KEY),
            esc_attr($value),
            esc_attr($args['placeholder'] ?? ''),
            $type
        );

        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function render_textarea_field($args)
    {
        $options = get_option(self::OPTION_KEY, []);
        $value = $options[$args['label_for']] ?? '';
        printf(
            '<textarea id="%1$s" name="%2$s[%1$s]" rows="5" class="large-text" placeholder="%4$s">%3$s</textarea>',
            esc_attr($args['label_for']),
            esc_attr(self::OPTION_KEY),
            esc_textarea($value),
            esc_attr($args['placeholder'] ?? '')
        );
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = get_option(self::OPTION_KEY, []);
        $shopId = $options['shop_id'] ?? wp_generate_password(12, false, false);
        if (empty($options['shop_id'])) {
            $options['shop_id'] = $shopId;
            update_option(self::OPTION_KEY, $options);
        }

        $webhookUrl = add_query_arg(
            ['shopid' => $shopId],
            site_url('/wp-json/autonotify/v1/notify')
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('AutoNotify Einstellungen', 'autonotify') . '</h1>';
        echo '<form action="options.php" method="post">';
        settings_fields(self::OPTION_KEY);
        do_settings_sections('autonotify');
        submit_button();
        echo '</form>';

        echo '<h2>' . esc_html__('Dein Webhook-Link', 'autonotify') . '</h2>';
        printf('<code>%s</code>', esc_html($webhookUrl));
        echo '<p>' . esc_html__('Diesen Link im Shop/Booking-Plugin als Webhook hinterlegen.', 'autonotify') . '</p>';

        echo '<h2>' . esc_html__('Status', 'autonotify') . '</h2>';
        if (empty($options['backend_url'])) {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__('Bitte trage den Webhook-Ziel-Link (notify.php) ein, sonst können keine Nachrichten versendet werden.', 'autonotify') . '</strong></p></div>';
        } else {
            echo '<div class="notice notice-success"><p><strong>' . esc_html__('Webhook-Ziel ist konfiguriert.', 'autonotify') . '</strong></p></div>';
        }
        echo '</div>';
    }

    public function sanitize_settings($input)
    {
        $current = get_option(self::OPTION_KEY, []);
        $sanitized = is_array($current) ? $current : [];

        if (isset($input['business_name'])) {
            $sanitized['business_name'] = sanitize_text_field($input['business_name']);
        }

        if (isset($input['whatsapp_number'])) {
            $sanitized['whatsapp_number'] = sanitize_text_field($input['whatsapp_number']);
        }

        if (isset($input['backend_url'])) {
            $sanitized['backend_url'] = esc_url_raw($input['backend_url']);
        }

        if (isset($input['message_template'])) {
            $sanitized['message_template'] = wp_kses_post($input['message_template']);
        }

        return $sanitized;
    }
}

new AutoNotifyWhatsAppConnector();

add_action('rest_api_init', function () {
    register_rest_route('autonotify/v1', '/notify', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (\WP_REST_Request $request) {
            $options = get_option(AutoNotifyWhatsAppConnector::OPTION_KEY, []);
            $expectedShopId = $options['shop_id'] ?? '';
            $incomingShopId = $request->get_param('shopid');

            if (!$expectedShopId || $incomingShopId !== $expectedShopId) {
                return new \WP_Error('invalid_shopid', __('Ungültiger Shop-ID Parameter', 'autonotify'), ['status' => 403]);
            }

            $backendUrl = isset($options['backend_url']) ? trim($options['backend_url']) : '';
            if (!$backendUrl) {
                return new \WP_Error('missing_backend_url', __('Kein Webhook-Ziel hinterlegt. Bitte trage die notify.php URL ein.', 'autonotify'), ['status' => 500]);
            }

            $payload = [
                'name' => $request->get_param('name'),
                'datum' => $request->get_param('datum'),
                'phone' => $request->get_param('phone'),
                'template' => $options['message_template'] ?? null,
            ];

            foreach (['name', 'datum', 'phone'] as $requiredField) {
                if (empty($payload[$requiredField])) {
                    return new \WP_Error('missing_field', sprintf(__('Feld "%s" ist erforderlich.', 'autonotify'), $requiredField), ['status' => 400]);
                }
            }

            $extra = $request->get_param('extra');
            $payload['extra'] = [];

            if (is_array($extra)) {
                $payload['extra'] = array_filter($extra, static function ($value) {
                    return $value !== null && $value !== '';
                });
            }

            if (!empty($options['business_name'])) {
                $payload['extra']['business_name'] = $options['business_name'];
            }

            if (!empty($options['whatsapp_number'])) {
                $payload['extra']['business_whatsapp'] = $options['whatsapp_number'];
            }

            if (empty($payload['extra'])) {
                unset($payload['extra']);
            }

            $targetUrl = add_query_arg(
                ['shopid' => $expectedShopId],
                $backendUrl
            );

            $response = wp_remote_post($targetUrl, [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($payload),
                'timeout' => 10,
            ]);

            if (is_wp_error($response)) {
                return new \WP_Error('webhook_failed', __('Versand an AutoNotify fehlgeschlagen.', 'autonotify'), ['status' => 502]);
            }

            return rest_ensure_response([
                'status' => 'ok',
            ]);
        },
    ]);
});
