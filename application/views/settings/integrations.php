<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="stg-panel" role="tabpanel" id="stg-panel-integrations" data-stg-tab="integrations" data-stg-keywords="integrations zoom teams google meet sso webhook smtp" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Integrations</h2>
      <p class="stg-card-kicker">Connect video conferencing, identity providers, and webhooks.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-integration-grid">
        <?php
        $integrations = [
          ['name' => 'Zoom', 'desc' => 'Virtual classroom links for live sessions.'],
          ['name' => 'Google Meet', 'desc' => 'Schedule Meet links from course modules.'],
          ['name' => 'Microsoft Teams', 'desc' => 'Teams meetings for instructor-led training.'],
          ['name' => 'Single sign-on', 'desc' => 'SAML/OIDC enterprise login.'],
          ['name' => 'Webhooks', 'desc' => 'Outbound events to your systems.'],
        ];
        foreach ($integrations as $item):
        ?>
        <div class="stg-integration">
          <h4><?= htmlspecialchars($item['name']) ?></h4>
          <p><?= htmlspecialchars($item['desc']) ?></p>
          <span class="stg-status stg-status--delayed">Coming soon</span>
        </div>
        <?php endforeach; ?>
      </div>
      <p class="stg-help">SMTP is configured under <button type="button" class="stg-nav-link stg-inline-tab" data-stg-tab="notifications" style="display:inline;padding:0;border:0;background:0;color:var(--ka-primary,#6dabcf);font-weight:600;">Notifications</button>.</p>
    </div>
  </div>
</section>
