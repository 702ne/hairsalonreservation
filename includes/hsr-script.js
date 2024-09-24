jQuery(document).ready(function($) {
    /*
    $('#hsr_date').change(function() {
        var selectedDate = $(this).val();
        if (selectedDate) {
            console.log(selectedDate);
            let myData = {
                action: "hsr_get_staff_availability",
                inputData: selectedDate,                
                nonce: "<?php echo wp_create_nonce('hsr_ajax_nonce'); ?>"
            }
            $.ajax({
                url: ajaxUrl,
                dataType: 'json',
                data: myData,
                method: "POST",
                success: function(data) {                   
                    //console.log("Response from server:", data);

                    if (data.success) {
                        var availabilities = data.data;
                        console.log("Availability:", data.data);
                        var select = $('#hsr_staff_time');
                        select.empty();
                        if (availabilities.length > 0) {
                            $.each(availabilities, function(index, slot) {
                                select.append($('<option></option>')
                                    .attr('value', slot.id)
                                    .text(slot.staff_name + ' - ' + slot.time));
                            })
                        }
                        $('#staff-time-container').show();
                    }
                },
            }).error(function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX 요청 실패:', textStatus, errorThrown);
                alert('서버 요청에 실패했습니다. 관리자에게 문의해주세요.');
            })
        } else {
            console.log('no date selected');
            $('#staff-time-container').hide();
        }
    });
    */
});