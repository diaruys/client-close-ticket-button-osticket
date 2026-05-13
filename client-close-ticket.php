<?php
require_once dirname(__file__) . '/config.php';

class ClientCloseTicketPlugin extends Plugin {

    var $config_class = 'ClientCloseTicketConfig';

    function bootstrap() {
        Signal::connect('ajax.client', function($dispatcher) {
            $dispatcher->append(
                url('^/tickets/close',
                    patterns(dirname(__file__) . '/ajax.php:ClientCloseTicketAjax',
                        url_post('^$', 'closeTicket')
                    )
                )
            );
        });

        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $uri    = $_SERVER['REQUEST_URI'] ?? '';

        $isTicketPage = ($script === 'tickets.php')
            && isset($_GET['id'])
            && is_numeric($_GET['id'])
            && strpos($uri, '/scp/') === false;

        if (!$isTicketPage) return;

        $ticketId = (int) $_GET['id'];
        $plugin   = $this;

        // Capture config values NOW before osTicket clears $this->config
        $rawCfg = $this->config;
        $cfgValues = array(
            'button_label'     => $rawCfg ? $rawCfg->get('button_label')     : null,
            'confirm_message'  => $rawCfg ? $rawCfg->get('confirm_message')  : null,
            'allowed_statuses' => $rawCfg ? $rawCfg->get('allowed_statuses') : null,
            'success_message'  => $rawCfg ? $rawCfg->get('success_message')  : null,
        );

        ob_start();
        register_shutdown_function(function() use ($ticketId, $plugin, $cfgValues) {
            $buffer = ob_get_clean();
            $inject = $plugin->renderCloseButton($ticketId, $cfgValues);
            if ($inject) {
                $buffer = preg_replace(
                    '/(<input[^>]+onClick="history\.go\(-1\)"[^>]*>)\s*<\/p>\s*<\/form>/s',
                    '$1' . "\n        " . $inject . "\n    </p>\n</form>",
                    $buffer
                );
            }
            echo $buffer;
        });
    }

    function renderCloseButton($ticketId, $cfgValues = array()) {
        $ticket = Ticket::lookup($ticketId);
        if (!$ticket) return '';

        $user = UserAuthenticationBackend::getUser();
        if (!$user || $ticket->getUserId() != $user->getId()) return '';

        $allowedStatus = array('open', 'answered');
        $buttonLabel   = 'Close My Ticket';
        $confirmMsg    = 'Are you sure you want to close this ticket? This action cannot be undone.';

        if (!empty($cfgValues['allowed_statuses']) && is_array($cfgValues['allowed_statuses']))
            $allowedStatus = array_keys($cfgValues['allowed_statuses']);
        if (!empty($cfgValues['button_label']))
            $buttonLabel = $cfgValues['button_label'];
        if (!empty($cfgValues['confirm_message']))
            $confirmMsg = strip_tags($cfgValues['confirm_message']);

        $currentStatus = strtolower($ticket->getStatus()->getName());
        if (!in_array($currentStatus, $allowedStatus)) return '';

        $ajaxUrl    = ROOT_PATH . 'ajax.php/tickets/close';
        $ticketsUrl = ROOT_PATH . 'tickets.php';

        $safeTicketId   = (int) $ticketId;
        $safeLabel      = htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8');
        $safeConfirm    = htmlspecialchars($confirmMsg,  ENT_QUOTES, 'UTF-8');
        $safeAjaxUrl    = htmlspecialchars($ajaxUrl,     ENT_QUOTES, 'UTF-8');
        $safeTicketsUrl = htmlspecialchars($ticketsUrl,  ENT_QUOTES, 'UTF-8');

        return <<<HTML
<input type="button" id="cct-open-modal" value="{$safeLabel}">

<div id="cct-dialog" style="display:none;position:fixed;z-index:1000;top:30%;left:50%;transform:translateX(-50%);background:#fff;border:1px solid #c0c0c0;border-radius:4px;padding:20px 24px;min-width:320px;max-width:420px;box-shadow:0 4px 16px rgba(0,0,0,0.15);text-align:center;font-family:inherit;">
  <p id="cct-msg" style="margin:0 0 18px;font-size:13px;color:#333;">{$safeConfirm}</p>
  <input type="button" id="cct-btn-confirm" value="Yes, Close It" style="background:#b92424;color:#fff;border:1px solid #a00;border-radius:3px;padding:6px 16px;font-size:13px;cursor:pointer;margin-right:6px;">
  <input type="button" id="cct-btn-cancel"  value="Cancel" style="padding:6px 16px;font-size:13px;cursor:pointer;">
</div>

<script>
(function($){
  var dialog    = $('#cct-dialog');
  var overlay   = $('#overlay');
  var msgEl     = $('#cct-msg');
  var csrfToken = $('meta[name="csrf_token"]').attr('content') || '';

  $('#cct-open-modal').on('click', function(){
    overlay.css({opacity:0.3,top:0,left:0}).show();
    dialog.show();
  });

  $('#cct-btn-cancel').on('click', function(){
    overlay.hide();
    dialog.hide();
  });

  overlay.on('click', function(){
    overlay.hide();
    dialog.hide();
  });

  $('#cct-btn-confirm').on('click', function(){
    $('#cct-btn-confirm, #cct-btn-cancel').prop('disabled', true);
    $('#cct-btn-confirm').val('Closing...');
    overlay.css({opacity:0.3,top:0,left:0}).show();
    $('#loading').css({top:$(window).height()/3, left:$(window).width()/2-160}).show();

    $.ajax({
      url: '{$safeAjaxUrl}',
      method: 'POST',
      data: { ticket_id: '{$safeTicketId}', __CSRFToken__: csrfToken },
      dataType: 'json',
      success: function(data) {
        $('#loading').hide();
        if (data.success) {
          msgEl.text(data.message || 'Ticket closed successfully.');
          $('#cct-btn-confirm').hide();
          $('#cct-btn-cancel').val('OK').prop('disabled', false).one('click', function(){
            window.location.href = '{$safeTicketsUrl}';
          });
        } else {
          msgEl.text(data.message || 'An error occurred.');
          $('#cct-btn-confirm, #cct-btn-cancel').prop('disabled', false);
          $('#cct-btn-confirm').val('Yes, Close It');
        }
      },
      error: function(xhr) {
        $('#loading').hide();
        msgEl.text('Unexpected error (' + xhr.status + '). Please try again.');
        $('#cct-btn-confirm, #cct-btn-cancel').prop('disabled', false);
        $('#cct-btn-confirm').val('Yes, Close It');
      }
    });
  });
})(jQuery);
</script>
HTML;
    }
}
