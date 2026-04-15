<?php
/**
 * plugin.php - Manifest for the Client Close Ticket plugin.
 *
 * Place the entire `client-close-ticket` folder inside:
 *   <osticket_root>/include/plugins/
 *
 * Then go to Admin Panel > Manage > Plugins > Add New Plugin to install it.
 */
return array(
    'id'          => 'osticket:client-close-ticket',   # notrans
    'version'     => '1.0.0',
    'name'        => /* trans */ 'Client Close Ticket',
    'author'      => 'Your Name',
    'description' => /* trans */ 'Adds a "Close My Ticket" button on the client-side ticket view. '
                   . 'Clients can self-close their own tickets (when status is Open or Answered) '
                   . 'after confirming a popup dialog.',
    'url'         => '',
    'plugin'      => 'client-close-ticket.php:ClientCloseTicketPlugin',
);
