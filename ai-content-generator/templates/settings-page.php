<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>AI Content Generator — Настройки</h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('aicg_settings_group');
        do_settings_sections('aicg-settings');
        submit_button('Сохранить настройки');
        ?>
    </form>

    <hr />
    <h2>Проверка подключения</h2>
    <p>
        <button type="button" class="button" id="aicg-test-openrouter">Тест OpenRouter</button>
        <button type="button" class="button" id="aicg-test-supadata" style="margin-left:8px;">Тест Supadata</button>
        <span id="aicg-test-result" style="margin-left:12px;"></span>
    </p>
</div>

<script>
(function() {
    const nonce = '<?php echo esc_js(wp_create_nonce('aicg_test_nonce')); ?>';
    const result = document.getElementById('aicg-test-result');

    function testApi(action, btn) {
        btn.disabled = true;
        result.textContent = 'Проверяю...';
        result.style.color = '#666';

        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action, nonce }),
        })
        .then(r => r.json())
        .then(data => {
            result.textContent = data.data || (data.success ? 'OK' : 'Ошибка');
            result.style.color = data.success ? '#00a32a' : '#d63638';
        })
        .catch(() => { result.textContent = 'Ошибка сети'; result.style.color = '#d63638'; })
        .finally(() => { btn.disabled = false; });
    }

    document.getElementById('aicg-test-openrouter')?.addEventListener('click', function() {
        testApi('aicg_test_openrouter', this);
    });
    document.getElementById('aicg-test-supadata')?.addEventListener('click', function() {
        testApi('aicg_test_supadata', this);
    });
})();
</script>
