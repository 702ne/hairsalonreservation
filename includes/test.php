
<script>
//simple
jQuery(document).ready(function($) {
    $('#hsr_date').change(function() {
        var selectedDate = $(this).val();
        if (selectedDate) {
            console.log(selectedDate);
            $.ajax({
                url: hsr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hsr_get_staff_availability',
                    date: selectedDate,
                    nonce: hsr_ajax.nonce
                },
                dataType: 'json',
                success: function(response) {
                    console.log(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX 요청 실패:', textStatus, errorThrown);
                    alert('서버 요청에 실패했습니다. 관리자에게 문의해주세요.');
                }
            });
        } else {
            console.log('no date selected');
            $('#staff-time-container').hide();
        }
    });
});


//original
jQuery(document).ready(function($) {
    $('#hsr_date').change(function() {
        var selectedDate = $(this).val();
        console.log(selectedDate);
        $('#staff-time-container').show();
        if (selectedDate) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hsr_get_staff_availability',
                    date: selectedDate,
                    nonce: '<?php echo wp_create_nonce('hsr_ajax_nonce'); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var availabilities = response.data;
                        console.log(availabilities);
                        var select = $('#hsr_staff_time');
                        select.empty();
                        if (availabilities.length > 0) {
                            $.each(availabilities, function(index, slot) {
                                select.append($('<option></option>')
                                    .attr('value', slot.id)
                                    .text(slot.staff_name + ' - ' + slot.time));
                            });
                            $('#staff-time-container').show();
                        } else {
                            select.append($('<option></option>').text('해당 날짜에 예약 가능한 시간이 없습니다.'));
                            $('#staff-time-container').show();
                        }
                    } else {
                        console.error('직원의 가용 시간을 불러오는데 실패했습니다:', response.data);
                        alert('직원의 가용 시간을 불러오는데 실패했습니다. 관리자에게 문의해주세요.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX 요청 실패:', textStatus, errorThrown);
                    alert('서버 요청에 실패했습니다. 관리자에게 문의해주세요.');
                }
            });
        } else {
            $('#staff-time-container').hide();
        }
    });
});
</script>