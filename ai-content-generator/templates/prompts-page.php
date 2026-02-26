<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>AI Content Generator — Промпты</h1>
    <p>Здесь вы можете редактировать все промпты, которые используются при генерации контента.</p>

    <form method="post" action="options.php">
        <?php
        settings_fields('aicg_prompts_group');
        do_settings_sections('aicg-prompts');
        submit_button('Сохранить промпты');
        ?>
    </form>

    <hr />
    <h2>Сброс к значениям по умолчанию</h2>
    <p>
        <button type="button" class="button button-secondary" id="aicg-reset-prompts">
            Сбросить все промпты
        </button>
        <span id="aicg-reset-result" style="margin-left:12px;"></span>
    </p>
    <p class="description">Все промпты будут заменены на значения по умолчанию. Текущие промпты будут потеряны.</p>
</div>

<script>
(function() {
    const btn    = document.getElementById('aicg-reset-prompts');
    const result = document.getElementById('aicg-reset-result');

    if (!btn) return;

    btn.addEventListener('click', function() {
        if (!confirm('Вы уверены? Все промпты будут сброшены к значениям по умолчанию.')) return;

        btn.disabled = true;
        result.textContent = 'Сбрасываю...';

        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'aicg_reset_prompts',
                nonce: '<?php echo esc_js(wp_create_nonce('aicg_reset_prompts_nonce')); ?>',
            }),
        })
        .then(r => r.json())
        .then(data => {
            result.textContent = data.data || 'Готово';
            result.style.color = data.success ? '#00a32a' : '#d63638';
            if (data.success) setTimeout(() => location.reload(), 1000);
        })
        .catch(() => { result.textContent = 'Ошибка сети'; result.style.color = '#d63638'; })
        .finally(() => { btn.disabled = false; });
    });
})();
</script>
