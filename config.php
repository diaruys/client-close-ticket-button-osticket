<?php
/**
 * config.php
 *
 * Admin-panel configuration for the Client Close Ticket plugin.
 * Accessible via: Admin Panel > Manage > Plugins > Client Close Ticket > Settings
 */
require_once INCLUDE_DIR . 'class.plugin.php';

class ClientCloseTicketConfig extends PluginConfig {

    /**
     * Define the settings form shown in the Admin panel.
     */
    function getOptions() {
        return array(

            'button_label' => new TextboxField(array(
                'label'       => 'Button Label',
                'hint'        => 'Text shown on the close button.',
                'default'     => 'Close My Ticket',
                'required'    => true,
                'configuration' => array('size' => 40, 'length' => 60),
            )),

            'allowed_statuses' => new ChoiceField(array(
                'label'   => 'Allowed Ticket Statuses',
                'hint'    => 'Button is shown only when the ticket is in one of these statuses.',
                'default' => array('open' => 'open', 'answered' => 'answered'),
                'choices' => array(
                    'open'     => 'Open',
                    'answered' => 'Answered',
                    'closed'   => 'Closed',
                ),
                'configuration' => array('multiselect' => true),
            )),

            'confirm_message' => new TextareaField(array(
                'label'   => 'Confirmation Dialog Message',
                'hint'    => 'Message shown to the client in the popup before closing.',
                'default' => 'Are you sure you want to close this ticket? '
                           . 'This action cannot be undone.',
                'required' => true,
                'configuration' => array('rows' => 3, 'cols' => 60),
            )),

            'success_message' => new TextboxField(array(
                'label'   => 'Success Message',
                'hint'    => 'Message shown after the ticket is successfully closed.',
                'default' => 'Your ticket has been closed. Thank you!',
                'required' => true,
                'configuration' => array('size' => 60, 'length' => 120),
            )),
        );
    }
}
