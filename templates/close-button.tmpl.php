<?php
/**
 * templates/close-button.tmpl.php
 *
 * Renders the "Close My Ticket" button and confirmation modal.
 *
 * This file is included via output-buffer injection just before </body>
 * on the client ticket-view page.
 *
 * It reads plugin config for labels/messages, checks ticket status,
 * and outputs the button only when appropriate.
 */

// ------------------------------------------------------------------
// 1. Gather context
// ------------------------------------------------------------------

// Ticket ID from the URL (?id=NNN)
$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$ticketId) return; // Nothing to render

// Load the ticket
$ticket = Ticket::lookup($ticketId);
if (!$ticket) return;

// Load plugin config
$plugin        = Plugin::lookup('osticket:client-close-ticket');
$buttonLabel   = 'Close My Ticket';
$confirmMsg    = 'Are you sure you want to close this ticket? This action cannot be undone.';
$allowedStatus = array('open', 'answered');

if ($plugin) {
    $cfg = $plugin->getConfig();

    if ($cfg->get('button_label'))   $buttonLabel   = $cfg->get('button_label');
    if ($cfg->get('confirm_message')) $confirmMsg   = $cfg->get('confirm_message');
    if ($cfg->get('allowed_statuses') && is_array($cfg->get('allowed_statuses'))) {
        $allowedStatus = array_keys($cfg->get('allowed_statuses'));
    }
}

// Check ticket status
$currentStatus = strtolower($ticket->getStatus()->getName());
if (!in_array($currentStatus, $allowedStatus)) return; // Button not applicable

// Verify the visiting user owns this ticket
$user = UserAuthenticationBackend::getUser();
if (!$user || $ticket->getUserId() != $user->getId()) return;

// CSRF token for the AJAX request
global $ost;
$csrfToken = $ost ? $ost->getCSRF()->getToken('cct-close') : '';

// Escape for safe output
$safeTicketId  = (int) $ticketId;
$safeLabel     = htmlspecialchars($buttonLabel,  ENT_QUOTES, 'UTF-8');
$safeConfirm   = htmlspecialchars($confirmMsg,   ENT_QUOTES, 'UTF-8');
$safeCsrf      = htmlspecialchars($csrfToken,    ENT_QUOTES, 'UTF-8');

// AJAX endpoint — osTicket's own ajax.php at the web root
$ajaxUrl = ROOT_PATH . 'ajax.php';

?>
<!-- ================================================================
     Client Close Ticket Plugin — injected markup
     ================================================================ -->

<!-- Confirmation Modal -->
<div id="cct-modal-overlay" style="
    display:none; position:fixed; top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.5); z-index:9999; align-items:center;
    justify-content:center;">
  <div style="
      background:#fff; border-radius:6px; padding:28px 32px; max-width:420px;
      width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.18); text-align:center;">
    <p id="cct-modal-message" style="
        margin:0 0 22px; font-size:15px; color:#333; line-height:1.5;">
      <?= $safeConfirm ?>
    </p>
    <div style="display:flex; gap:12px; justify-content:center;">
      <button id="cct-btn-confirm" style="
          background:#d9534f; color:#fff; border:none; border-radius:4px;
          padding:9px 22px; font-size:14px; cursor:pointer; font-weight:600;">
        Yes, Close It
      </button>
      <button id="cct-btn-cancel" style="
          background:#f0f0f0; color:#555; border:none; border-radius:4px;
          padding:9px 22px; font-size:14px; cursor:pointer;">
        Cancel
      </button>
    </div>
  </div>
</div>

<!-- Floating Close Button -->
<div style="margin:18px 0; text-align:right;">
  <button id="cct-open-modal" style="
      background:#d9534f; color:#fff; border:none; border-radius:4px;
      padding:9px 20px; font-size:14px; cursor:pointer; font-weight:600;
      display:inline-flex; align-items:center; gap:6px;">
    <span>&#x2715;</span>
    <span><?= $safeLabel ?></span>
  </button>
</div>

<script>
(function () {
  'use strict';

  var overlay    = document.getElementById('cct-modal-overlay');
  var btnOpen    = document.getElementById('cct-open-modal');
  var btnConfirm = document.getElementById('cct-btn-confirm');
  var btnCancel  = document.getElementById('cct-btn-cancel');
  var msgEl      = document.getElementById('cct-modal-message');

  // Open modal
  btnOpen.addEventListener('click', function () {
    overlay.style.display = 'flex';
  });

  // Cancel — close modal
  btnCancel.addEventListener('click', function () {
    overlay.style.display = 'none';
  });

  // Close overlay by clicking outside the dialog box
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) overlay.style.display = 'none';
  });

  // Confirm — send AJAX close request
  btnConfirm.addEventListener('click', function () {
    btnConfirm.disabled = true;
    btnCancel.disabled  = true;
    btnConfirm.textContent = 'Closing…';

    var formData = new FormData();
    formData.append('do',        'client-close-ticket');
    formData.append('ticket_id', '<?= $safeTicketId ?>');
    formData.append('token',     '<?= $safeCsrf ?>');

    fetch('<?= $ajaxUrl ?>', {
      method:      'POST',
      credentials: 'same-origin',
      body:        formData,
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        // Show success message inside the modal, then redirect
        msgEl.textContent = data.message || 'Ticket closed successfully.';
        btnConfirm.style.display = 'none';
        btnCancel.textContent    = 'OK';
        btnCancel.disabled       = false;

        btnCancel.addEventListener('click', function () {
          // Redirect to the client ticket list
          window.location.href = '<?= ROOT_PATH ?>tickets.php';
        }, { once: true });

      } else {
        // Show error and re-enable buttons
        msgEl.textContent   = data.message || 'An error occurred. Please try again.';
        btnConfirm.disabled = false;
        btnCancel.disabled  = false;
        btnConfirm.textContent = 'Yes, Close It';
      }
    })
    .catch(function () {
      msgEl.textContent   = 'Network error. Please check your connection and try again.';
      btnConfirm.disabled = false;
      btnCancel.disabled  = false;
      btnConfirm.textContent = 'Yes, Close It';
    });
  });

})();
</script>
<!-- ================================================================
     End Client Close Ticket Plugin
     ================================================================ -->
