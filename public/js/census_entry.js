$(document).ready(function() {
    let autosaveTimer;
    const autosaveDelay = 1000; // 1 second debounce
    const $form = $('form[action*="census/store"]');
    const $statusIndicator = $('#autosave-status');

    function updateStatus(text, className) {
        $statusIndicator.text(text).removeClass('text-muted text-success text-danger text-warning').addClass(className);
    }

    function doAutosave() {
        const formData = $form.serialize();
        const autosaveUrl = $form.attr('action').replace('store', 'autosave');

        updateStatus('กำลังบันทึก...', 'text-warning');

        $.ajax({
            url: autosaveUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateStatus('บันทึกอัตโนมัติสำเร็จ', 'text-success');
                    setTimeout(() => updateStatus('พร้อม', 'text-muted'), 3000);
                } else {
                    updateStatus('ข้อมูลไม่ผ่านการตรวจสอบ', 'text-danger');
                    console.error('Validation errors:', response.errors);
                }
            },
            error: function(xhr, status, error) {
                updateStatus('เกิดข้อผิดพลาดในการบันทึก', 'text-danger');
                console.error('AJAX error:', error);
            }
        });
    }

    $form.on('input change', 'input, select', function() {
        // Basic frontend validation: prevent negatives
        if ($(this).attr('type') === 'number' && $(this).val() < 0) {
            $(this).val(0);
        }

        clearTimeout(autosaveTimer);
        
        // Only autosave if identity fields are present
        if ($('#ward_id').val() && $('#record_date').val() && $('#shift').val()) {
            autosaveTimer = setTimeout(doAutosave, autosaveDelay);
        } else {
            updateStatus('เลือกแผนก วันที่ และเวร เพื่อเปิดใช้การบันทึกอัตโนมัติ', 'text-muted');
        }
    });
});
