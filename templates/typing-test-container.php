<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="btt-container">
    <div class="btt-header">
        <h2><?php echo esc_html__('Typing Speed Test', 'typing-test'); ?></h2>
        <p><?php echo esc_html__('Choose difficulty, practice, and unlock your certificate after passing all levels.', 'typing-test'); ?></p>
    </div>

    <div class="btt-difficulty" role="group" aria-label="Difficulty">
        <button type="button" class="btt-diff-btn" data-difficulty="easy">🟢 <?php echo esc_html__('Easy', 'typing-test'); ?></button>
        <button type="button" class="btt-diff-btn is-active" data-difficulty="medium">🟡 <?php echo esc_html__('Medium', 'typing-test'); ?></button>
        <button type="button" class="btt-diff-btn" data-difficulty="hard">🔴 <?php echo esc_html__('Hard', 'typing-test'); ?></button>
    </div>

    <div class="btt-controls">
        <div class="btt-control-group">
            <label for="btt-duration-select"><?php echo esc_html__('Duration', 'typing-test'); ?></label>
            <select id="btt-duration-select">
                <option value="30">30s</option>
                <option value="60" selected>1m</option>
                <option value="120">2m</option>
                <option value="180">3m</option>
                <option value="300">5m</option>
            </select>
        </div>
        <div class="btt-control-group">
            <label for="btt-passage-select"><?php echo esc_html__('Select Passage', 'typing-test'); ?></label>
            <select id="btt-passage-select"></select>
        </div>
        <div class="btt-button-group">
            <button id="btt-start-btn" class="btt-start-btn"><?php echo esc_html__('Start Test', 'typing-test'); ?></button>
            <button id="btt-new-btn" class="btt-secondary-btn" type="button" title="<?php esc_attr_e('Random Passage', 'typing-test'); ?>">🎲</button>
            <button id="btt-reset-btn" class="btt-secondary-btn" type="button" title="<?php esc_attr_e('Reset', 'typing-test'); ?>">🔄</button>
        </div>
    </div>

    <div class="btt-progress">
        <div class="btt-progress-title"><?php echo esc_html__('Certificate Progress', 'typing-test'); ?></div>
        <div class="btt-progress-badges">
            <span class="btt-badge" id="btt-badge-easy"><?php _e('Easy', 'typing-test'); ?>: <strong>—</strong></span>
            <span class="btt-badge" id="btt-badge-medium"><?php _e('Medium', 'typing-test'); ?>: <strong>—</strong></span>
            <span class="btt-badge" id="btt-badge-hard"><?php _e('Hard', 'typing-test'); ?>: <strong>—</strong></span>
        </div>
        <button id="btt-certificate-btn" class="btt-certificate-btn" type="button" disabled>
            <?php echo esc_html__('Download Certificate', 'typing-test'); ?>
        </button>
        <div id="btt-certificate-hint" class="btt-hint"></div>
    </div>

    <div class="btt-stats">
        <div class="btt-stat"><strong><?php echo esc_html__('Time Left', 'typing-test'); ?>:</strong> <span id="btt-time-left">0</span></div>
        <div class="btt-stat"><strong><?php echo esc_html__('WPM', 'typing-test'); ?>:</strong> <span id="btt-wpm">0</span></div>
        <div class="btt-stat"><strong><?php echo esc_html__('Accuracy', 'typing-test'); ?>:</strong> <span id="btt-accuracy">0</span>%</div>
        <div class="btt-stat"><strong><?php echo esc_html__('Correct', 'typing-test'); ?>:</strong> <span id="btt-correct">0</span></div>
        <div class="btt-stat"><strong><?php echo esc_html__('Wrong', 'typing-test'); ?>:</strong> <span id="btt-wrong">0</span></div>
    </div>

    <div class="btt-result" id="btt-result" aria-live="polite"></div>

    <div class="btt-test-area">
        <div id="btt-text" class="btt-text"></div>
        <textarea id="btt-input" class="btt-input" placeholder="<?php echo esc_attr__('Start typing here…', 'typing-test'); ?>" disabled></textarea>
    </div>

    <!-- Certificate modal -->
    <div class="btt-modal" id="btt-modal" aria-hidden="true">
        <div class="btt-modal__backdrop" data-btt-close></div>
        <div class="btt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="btt-modal-title">
            <div class="btt-modal__header">
                <h3 id="btt-modal-title"><?php echo esc_html__('Download Certificate', 'typing-test'); ?></h3>
                <button type="button" class="btt-icon-btn" data-btt-close aria-label="Close">✕</button>
            </div>
            <div class="btt-modal__body">
                <label for="btt-user-name"><?php echo esc_html__('Enter your full name', 'typing-test'); ?></label>
                <input id="btt-user-name" class="btt-input-text" type="text" placeholder="e.g., Rashidul Islam" maxlength="60" />
                <div class="btt-modal__note" id="btt-modal-note"></div>
            </div>
            <div class="btt-modal__footer">
                <button type="button" class="btt-secondary-btn" data-btt-close><?php echo esc_html__('Cancel', 'typing-test'); ?></button>
                <button type="button" id="btt-generate-certificate" class="btt-start-btn"><?php echo esc_html__('Generate PDF', 'typing-test'); ?></button>
            </div>
        </div>
    </div>

    <!-- Hidden certificate template for rendering -->
    <div class="btt-certificate" id="btt-certificate" aria-hidden="true">
        <div class="btt-cert__border">
            <div class="btt-cert__top">
                <div class="btt-cert__brand"><?php echo esc_html($cert_settings['brandName']); ?></div>
                <div class="btt-cert__title"><?php echo esc_html__('CERTIFICATE OF ACHIEVEMENT', 'typing-test'); ?></div>
                <div class="btt-cert__subtitle"><?php echo esc_html__('Typing Speed Test', 'typing-test'); ?></div>
            </div>

            <div class="btt-cert__body">
                <div class="btt-cert__text"><?php echo esc_html__('This is to certify that', 'typing-test'); ?></div>
                <div class="btt-cert__name" id="btt-cert-name">—</div>
                <div class="btt-cert__text"><?php echo esc_html__('has successfully completed all three difficulty levels (Easy, Medium, Hard) and achieved the required typing performance.', 'typing-test'); ?></div>

                <div class="btt-cert__table" id="btt-cert-table">
                    <!-- Filled by JS -->
                </div>
            </div>

            <div class="btt-cert__footer">
                <div>
                    <div class="btt-cert__meta"><span><?php echo esc_html__('Issued by:', 'typing-test'); ?></span> <strong><?php echo esc_html($cert_settings['issuerWebsite']); ?></strong></div>
                    <div class="btt-cert__meta"><span><?php echo esc_html__('Date:', 'typing-test'); ?></span> <strong id="btt-cert-date">—</strong></div>
                </div>
                <div class="btt-cert__id"><?php echo esc_html__('Certificate ID:', 'typing-test'); ?> <strong id="btt-cert-id">—</strong></div>
            </div>
        </div>
    </div>
</div>
