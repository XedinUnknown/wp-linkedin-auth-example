<div class="wrap">
    <div class="rai-settings">
    <?php if (!empty($error_message)): ?>
        <div class="error">
            <p><?php echo $error_message ?></p>
        </div>
    <?php elseif ($is_linking && $is_authorized): ?>
        <div class="updated">
            <p><?php echo __('Authorized!', 'rest-api-import') ?></p>
        </div>
    <?php elseif (!$is_linking): ?>
        <div class="updated">
            <p><?php echo __('Unlinked!', 'rest-api-import') ?></p>
        </div>
    <?php endif ?>

        <a id="<?php echo $linkedin_auth_button_id ?>" class="button button-primary" href="<?php if (!$is_linked): ?><?php echo esc_attr($url_to_link) ?><?php else: ?><?php echo esc_attr($url_to_unlink) ?><?php endif ?>">
            <?php if (!$is_linked): ?>
                <?php echo __('Link', 'rest-api-import') ?>
            <?php else: ?>
                <?php echo __('Unlink', 'rest-api-import') ?>
            <?php endif ?>
        </a>
    </div>
</div>
