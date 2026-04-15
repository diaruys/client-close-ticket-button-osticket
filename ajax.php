<?php
require_once INCLUDE_DIR . 'class.ajax.php';

class ClientCloseTicketAjax extends AjaxController {

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

        $plugin        = Plugin::lookup('osticket:client-close-ticket');
        $allowedStatus = array('open', 'answered');
        if ($plugin) {
            $cfg = $plugin->getConfig();
            if ($cfg->get('allowed_statuses') && is_array($cfg->get('allowed_statuses')))
                $allowedStatus = array_keys($cfg->get('allowed_statuses'));
        }

        $currentStatus = strtolower($ticket->getStatus()->getName());
        if (!in_array($currentStatus, $allowedStatus))
            return $this->json(false, 'Ticket cannot be closed in its current status.');

        $errors = array();
        $closedStatus = TicketStatus::lookup(array('name' => 'closed'));
        if (!$closedStatus)
            return $this->json(false, 'Closed status not found. Contact administrator.');

        if ($ticket->setStatus($closedStatus, 'Closed by client via self-service portal.', $errors)) {
            $success = 'Your ticket has been closed. Thank you!';
            if ($plugin) {
                $msg = $plugin->getConfig()->get('success_message');
                if ($msg) $success = $msg;
            }
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
