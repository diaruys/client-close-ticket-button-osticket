<?php
require_once INCLUDE_DIR . 'class.ajax.php';
require_once dirname(__file__) . '/config.php';

class ClientCloseTicketAjax extends AjaxController {

    private function getPluginConfig() {
        $cfg = array(
            'button_label' => 'Close My Ticket',
            'allowed_statuses' => array('open', 'answered'),
            'confirm_message' => 'Are you sure you want to close this ticket? This action cannot be undone.',
            'success_message' => 'Your ticket has been closed. Thank you!',
            'email_notifications_enabled' => true,
            'email_recipients' => array('owner', 'collaborators'),
            'email_subject' => 'Ticket #{ticket_number} closed',
            'email_body' => "Hello,\n\nTicket #{ticket_number} has been closed by {closed_by}.\n\nSubject: {ticket_subject}\n\nYou can view the ticket here:\n{ticket_url}\n\nThank you.",
        );

        if (!($namespace = $this->getPluginConfigNamespace()))
            return $cfg;

        $sql = 'SELECT `key`, value FROM '.CONFIG_TABLE
             . ' WHERE namespace='.db_input($namespace);
        $res = db_query($sql);
        while ($row = db_fetch_array($res)) {
            $key = $row['key'];
            $value = $row['value'];

            switch ($key) {
            case 'allowed_statuses':
            case 'email_recipients':
                $cfg[$key] = $this->decodeChoiceKeys($value, $cfg[$key]);
                break;
            case 'email_notifications_enabled':
                $cfg[$key] = (bool) $value;
                break;
            default:
                $cfg[$key] = $value;
            }
        }

        return $cfg;
    }

    private function getPluginConfigNamespace() {
        $sql = 'SELECT p.id AS plugin_id, i.id AS instance_id'
             . ' FROM '.PLUGIN_TABLE.' p'
             . ' LEFT JOIN '.PLUGIN_INSTANCE_TABLE.' i ON i.plugin_id = p.id'
             . ' WHERE p.install_path='.db_input('plugins/client-close-ticket')
             . ' ORDER BY ((i.flags & 1) > 0) DESC, i.id ASC'
             . ' LIMIT 1';
        $res = db_query($sql);
        if (!($row = db_fetch_array($res)) || !$row['plugin_id'])
            return null;

        if ($row['instance_id'])
            return sprintf('plugin.%d.instance.%d', $row['plugin_id'], $row['instance_id']);

        return sprintf('plugin.%d', $row['plugin_id']);
    }

    private function decodeChoiceKeys($value, $default=array()) {
        $decoded = json_decode($value, true);
        if (!is_array($decoded))
            return $default;

        $values = array();
        foreach ($decoded as $key => $val)
            $values[] = is_string($key) ? $key : $val;

        return array_values(array_filter($values));
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

        //if ($ticket->getUserId() != $user->getId())
        //    return $this->json(false, 'Permission denied.');

	$userId = $user->getId();
	$isOwner = ($ticket->getUserId() == $userId);
	$isCollaborator = false;
	if (!$isOwner) {
	    $sql = 'SELECT COUNT(*) FROM ost_thread_collaborator tc
		    JOIN ost_thread th ON th.id = tc.thread_id
		    WHERE th.object_id = '.db_input($ticket->getID()).'
		    AND th.object_type = "T"
		    AND tc.user_id = '.db_input($userId);
	    $res = db_query($sql);
	    $isCollaborator = (db_result($res) > 0);
	}
	if (!$isOwner && !$isCollaborator)
	    return $this->json(false, 'Permission denied.');

        $cfg           = $this->getPluginConfig();
        $allowedStatus = array('open');

        if ($cfg && !empty($cfg['allowed_statuses']) && is_array($cfg['allowed_statuses']))
            $allowedStatus = $cfg['allowed_statuses'];

        $currentStatus = strtolower($ticket->getStatus()->getName());
        if (!in_array($currentStatus, $allowedStatus))
            return $this->json(false, 'Ticket cannot be closed in its current status.');

        $errors       = array();
        $closedStatus = TicketStatus::lookup(array('name' => 'closed'));
        if (!$closedStatus)
            return $this->json(false, 'Closed status not found. Contact administrator.');

        if ($ticket->setStatus($closedStatus, 'Closed by client via self-service portal.', $errors)) {
            $this->sendTicketClosedEmail($ticket, $user, $cfg);

            $success = 'Your ticket has been closed. Thank you!';
            if ($cfg && !empty($cfg['success_message']))
                $success = $cfg['success_message'];
            return $this->json(true, $success);
        }

        $errMsg = !empty($errors) ? implode(' ', $errors) : 'Unable to close ticket.';
        return $this->json(false, $errMsg);
    }

    private function sendTicketClosedEmail($ticket, $closedBy, $pluginCfg=array()) {
        global $cfg, $ost;

        if (isset($pluginCfg['email_notifications_enabled'])
                && !$pluginCfg['email_notifications_enabled'])
            return true;

        if (!$ticket
                || !($recipients = $this->getClosureEmailRecipients($ticket, $pluginCfg))
                || !count($recipients)
                || !($dept = $ticket->getDept())) {
            return false;
        }

        $email = $dept->getEmail();
        if (!$email && $cfg)
            $email = $cfg->getDefaultEmail();

        if (!$email)
            return false;

        $subjectTemplate = !empty($pluginCfg['email_subject'])
            ? $pluginCfg['email_subject']
            : 'Ticket #{ticket_number} closed';
        $bodyTemplate = !empty($pluginCfg['email_body'])
            ? $pluginCfg['email_body']
            : "Hello,\n\nTicket #{ticket_number} has been closed by {closed_by}.\n\nSubject: {ticket_subject}\n\nYou can view the ticket here:\n{ticket_url}\n\nThank you.";

        $subject = $this->formatClosureEmailTemplate($subjectTemplate, $ticket, $closedBy);
        $body = $this->renderClosureEmailBody(
            $this->formatClosureEmailTemplate($bodyTemplate, $ticket, $closedBy)
        );

        $options = array('thread' => $ticket->getThread());
        $sent = $email->send($recipients, $subject, $body, null, $options);

        if (!$sent && $ost) {
            $ost->logWarning(
                'Ticket closure email failed',
                sprintf('Unable to send ticket closure email for ticket #%s.', $ticket->getNumber()),
                false
            );
        }

        return $sent;
    }

    private function renderClosureEmailBody($body) {
        $body = trim((string) $body);
        if ($this->containsHtml($body))
            return Format::safe_html($body);

        return nl2br(Format::htmlchars($body));
    }

    private function containsHtml($body) {
        return (bool) preg_match('#<\s*\/?\s*[a-z][^>]*>#i', $body);
    }

    private function getClosureEmailRecipients($ticket, $pluginCfg) {
        $selected = !empty($pluginCfg['email_recipients']) && is_array($pluginCfg['email_recipients'])
            ? $pluginCfg['email_recipients']
            : array('owner', 'collaborators');

        $notifyOwner = in_array('owner', $selected);
        $notifyCollaborators = in_array('collaborators', $selected);

        if ($notifyOwner && $notifyCollaborators)
            return $ticket->getRecipients('all');
        if ($notifyOwner)
            return $ticket->getRecipients('user');
        if ($notifyCollaborators)
            return $ticket->getRecipients('collabs');

        return null;
    }

    private function formatClosureEmailTemplate($template, $ticket, $closedBy) {
        global $cfg;

        $ticketUrl = ($cfg && $cfg->getBaseUrl())
            ? sprintf('%s/tickets.php?id=%d', $cfg->getBaseUrl(), $ticket->getId())
            : '';
        $closedByName = $closedBy && $closedBy->getName()
            ? (string) $closedBy->getName()
            : 'a client';
        $dept = $ticket->getDept();

        return strtr($template, array(
            '{ticket_number}' => $ticket->getNumber(),
            '{ticket_id}' => $ticket->getId(),
            '{ticket_subject}' => $ticket->getSubject(),
            '{closed_by}' => $closedByName,
            '{ticket_url}' => $ticketUrl,
            '{department}' => $dept ? $dept->getName() : '',
        ));
    }

    private function json($success, $message) {
        Http::response(200, json_encode(array(
            'success' => (bool) $success,
            'message' => $message,
        )), 'application/json');
    }
}
