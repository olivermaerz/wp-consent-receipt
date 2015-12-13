<div class="wrap">
    <h2>WP Consent Receipt Settings</h2>
    <form method="post" action="options.php"> 


        <?php settings_fields('wp_consent_receipt'); ?>
        <?php do_settings_sections('wp_consent_receipt'); ?>
        
        <?php @submit_button(); ?>
    </form>
</div>
