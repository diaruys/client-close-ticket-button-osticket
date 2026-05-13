<?php
require_once INCLUDE_DIR . 'class.ajax.php';
require_once dirname(__file__) . '/config.php';

class ClientCloseTicketAjax extends AjaxController {

    private function getPluginConfig() {
        $sql = 'SELECT `key`, value FROM '.CONFIG_TABLE.' WHERE namespace="plugin.3.instance.2"';
        $res = db_query($sql);
        $cfg = array();
        while ($row = db_fetch_array($res))
            $cfg[$row['key']] = $row['value'];
        return $cfg ?: null;
    }

    function closeTicket() {
        $ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
        if (!$ticketId)
            return $this->json(false, 'Invalid ticket.');

        $user = UserAuthenticationBackend::getUser();
        if (!$user)
            return $this->json(false, 'You must be logged in.');

        $ticket = Ticket::lookup($ticketId);
        if (!$ticket)
            return $this->json(false, 'Ticket not found.');

        if ($ticket->getUserId() != $user->getId())
            return $this->json(false, 'Permission denied.');

        $cfg           = $this->getPluginConfig();
        $allowedStatus = array('open');

        if ($cfg && !empty($cfg['allowed_statuses'])) {
            $decoded = json_decode($cfg['allowed_statuses'], true);
            if (is_array($decoded))
                $allowedStatus = array_keys($decoded);
        }

        $currentStatus = strtolower($ticket->getStatus()->getName());
        if (!in_array($currentStatus, $allowedStatus))
            return $this->json(false, 'Ticket cannot be closed in its current status.');

        $errors       = array();
        $closedStatus = TicketStatus::lookup(array('name' => 'closed'));
        if (!$closedStatus)
            return $this->json(false, 'Closed status not found. Contact administrator.');

        if ($ticket->setStatus($closedStatus, 'Closed by client via self-service portal.', $errors)) {
            $success = 'Your ticket has been closed. Thank you!';
            if ($cfg && !empty($cfg['success_message']))
                $success = $cfg['success_message'];
            return $this->json(true, $success);
        }

        $errMsg = !empty($errors) ? implode(' ', $errors) : 'Unable to close ticket.';
        return $this->json(false, $errMsg);
    }

    private function json($success, $message) {
        Http::response(200, json_encode(array(
            'success' => (bool) $success,
            'message' => $message,
        )), 'application/json');
    }
}
