var baseurl = $('#uribase').val();

$(document).on('click', '#appointment-date', function () {
    date = $('#appointment-date').val();
});

$(document).ready(function () {

    $('[data-toggle="tooltip"]').tooltip();

    $('.schedule_form_btn').on('click', function () {
        var formid = $(this).closest('form').attr('id');
        var mrg_error = $('#' + formid + ' .field-addscheduleform-shift_one_end .help-block').text();

        if (mrg_error != '' || after_error != '' || eve_error != '') {
            return false;
        } else {
            $('form.schedule-form').submit();
        }
    });

    $('.addscheduleform-shift_one_start').timepicker({defaultTime: '08:00 A'});
    $('.addscheduleform-shift_one_end').timepicker({defaultTime: '12:00 P'});
    $('.addscheduleform-shift_two_start').timepicker({defaultTime: '12:00 P'});
    $('.addscheduleform-shift_two_end').timepicker({defaultTime: '5:00 P'});
    $('.addscheduleform-shift_three_start').timepicker({defaultTime: '5:00 P'});
    $('.addscheduleform-shift_three_end').timepicker({defaultTime: '10:00 P'});

    $(document).on('change', '.add_shift_list', function () {
        var getId = this.id;
        var formid = $(this).closest('form').attr('id');

        if ($("#" + formid + " #" + getId).is(":checked")) {
            $('#' + formid + '.schedule-form').yiiActiveForm('add', {
                id: getId + '_address',
                name: getId + '_address',
                container: '.field-' + getId + '_address', //or your cllass container
                input: '#' + getId + '_address',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {message: "Hospitals/Clinics cannot be blank."});
                }
            });

            $('#' + formid + '.schedule-form').yiiActiveForm('add', {
                id: getId + '_patient',
                name: getId + '_patient',
                container: '.field-' + getId + '_patient', //or your cllass container
                input: '#' + getId + '_patient',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {message: "Patient Limit cannot be blank."});
                }
            });

            $('#' + formid + '.schedule-form').yiiActiveForm('add', {
                id: getId + '_cfees',
                name: getId + '_cfees',
                container: '.field-' + getId + '_cfees', //or your cllass container
                input: '#' + getId + '_cfees',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {message: "Consultancy Fee cannot be blank."});
                }
            });

            $('#' + formid + '.schedule-form').yiiActiveForm('add', {
                id: getId + '_cdays',
                name: getId + '_cdays',
                container: '.field-' + getId + '_cdays', //or your cllass container
                input: '#' + getId + '_cdays',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {message: "Valid Days cannot be blank."});
                }
            });

            $('#' + formid + '.schedule-form').yiiActiveForm('add', {
                id: getId + '_efees',
                name: getId + '_efees',
                container: '.field-' + getId + '_efees', //or your cllass container
                input: '#' + getId + '_efees',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {message: "Emergency Fee cannot be blank."});
                }
            });

            $('#' + formid + '.schedule-form').yiiActiveForm('add', {
                id: getId + '_edays',
                name: getId + '_edays',
                container: '.field-' + getId + '_edays', //or your cllass container
                input: '#' + getId + '_edays',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {message: "Valid Days cannot be blank."});
                }
            });
        } else {
            $('#' + formid + '.schedule-form').yiiActiveForm('remove', getId + '_address');
            $('#' + formid + '.schedule-form').yiiActiveForm('remove', getId + '_patient');
            $('#' + formid + '.schedule-form').yiiActiveForm('remove', getId + '_cfees');
            $('#' + formid + '.schedule-form').yiiActiveForm('remove', getId + '_cdays');
            $('#' + formid + '.schedule-form').yiiActiveForm('remove', getId + '_efees');
            $('#' + formid + '.schedule-form').yiiActiveForm('remove', getId + '_edays');

            $('#' + formid + '.schedule-form .field-' + getId + '_address').removeClass('has-error');
            $('#' + formid + '.schedule-form .field-' + getId + '_patient').removeClass('has-error');
            $('#' + formid + '.schedule-form .field-' + getId + '_cfees').removeClass('has-error');
            $('#' + formid + '.schedule-form .field-' + getId + '_cdays').removeClass('has-error');
            $('#' + formid + '.schedule-form .field-' + getId + '_efees').removeClass('has-error');
            $('#' + formid + '.schedule-form .field-' + getId + '_edays').removeClass('has-error');

            $('#' + formid + '.schedule-form .field-' + getId + '_address div.help-block').empty();
            $('#' + formid + '.schedule-form .field-' + getId + '_patient div.help-block').empty();
            $('#' + formid + '.schedule-form .field-' + getId + '_cfees div.help-block').empty();
            $('#' + formid + '.schedule-form .field-' + getId + '_cdays div.help-block').empty();
            $('#' + formid + '.schedule-form .field-' + getId + '_efees div.help-block').empty();
            $('#' + formid + '.schedule-form .field-' + getId + '_edays div.help-block').empty();

        }
    });

    $(document).on('change', '#admin_rating_type', function () {
        var getValue = this.value;
        if (getValue == 'Admin') {
            $('#admin_rating_number').css('display', 'block');
            $('#update-rating').yiiActiveForm('add', {
                id: 'adminrating-rating',
                name: 'adminrating-rating',
                container: '.field-adminrating-rating', //or your cllass container
                input: '#adminrating-rating',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {message: "Enter custom rating for doctor."});
                }
            });
        } else {
            $('#admin_rating_number').css('display', 'none');
            $('#update-rating').yiiActiveForm('remove', 'adminrating-rating');
            $('#update-rating .field-adminrating-rating').removeClass('has-error');
            $('#update-rating .field-adminrating-rating p.help-block').empty();
        }

    });

    $(document).on("change", ".booking_fee", function () {
        var sum = 0;
        $(".booking_fee").each(function () {
            sum += +$(this).val();
        });
        $('#booking-fees').val(sum);
        if (sum > 100 || sum < 100) {
            $('#update-fee-percent').yiiActiveForm('add', {
                id: 'booking-fees',
                name: 'booking-fees',
                container: '.field-booking-fees', //or your cllass container
                input: '#booking-fees',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {
                        'message': 'Booking total must be 100.'
                    });
                    yii.validation.string(value, messages, {
                        'message': 'Booking total must be string.',
                        'min': 5,
                        'tooShort': 'Booking total must be 100.',
                        'max': 5,
                        'tooLong': 'Booking total must be 1000.',
                    });
                }
            });
        } else {
            $('#booking-fees').val('succs');
            $('#update-fee-percent').yiiActiveForm('remove', 'booking-fees');
            $('#update-fee-percent .field-booking-fees').removeClass('has-error');
            $('#update-fee-percent .field-booking-fees p.help-block').empty();
        }
    });

    $(document).on("change", ".cancel_fee", function () {
        var sum = 0;
        $(".cancel_fee").each(function () {
            sum += +$(this).val();
        });
        $('#cancel-fees').val(sum);
        if (sum > 100 || sum < 100) {
            $('#update-fee-percent').yiiActiveForm('add', {
                id: 'cancel-fees',
                name: 'cancel-fees',
                container: '.field-cancel-fees', //or your cllass container
                input: '#cancel-fees',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {
                        'message': 'Cancellation total must be 100.'
                    });
                    yii.validation.string(value, messages, {
                        'message': 'Cancellation total must be string.',
                        'min': 5,
                        'tooShort': 'Cancellation total must be 100.',
                        'max': 5,
                        'tooLong': 'Cancellation total must be 1000.',
                    });
                }
            });
        } else {
            $('#cancel-fees').val('succs');
            $('#update-fee-percent').yiiActiveForm('remove', 'cancel-fees');
            $('#update-fee-percent .field-cancel-fees').removeClass('has-error');
            $('#update-fee-percent .field-cancel-fees p.help-block').empty();
        }
    });

    $(document).on("change", ".reschedule_fee", function () {
        var sum = 0;
        $(".reschedule_fee").each(function () {
            sum += +$(this).val();
        });
        $('#reschedule-fees').val(sum);
        if (sum > 100 || sum < 100) {
            $('#update-fee-percent').yiiActiveForm('add', {
                id: 'reschedule-fees',
                name: 'reschedule-fees',
                container: '.field-reschedule-fees', //or your cllass container
                input: '#reschedule-fees',
                error: '.help-block', //or your class error
                validate: function (attribute, value, messages, deferred, $form) {
                    yii.validation.required(value, messages, {
                        'message': 'Reschedule total must be 100.'
                    });
                    yii.validation.string(value, messages, {
                        'message': 'Reschedule total must be string.',
                        'min': 5,
                        'tooShort': 'Reschedule total must be 100.',
                        'max': 5,
                        'tooLong': 'Reschedule total must be 1000.',
                    });
                }
            });
        } else {
            $('#reschedule-fees').val('succs');
            $('#update-fee-percent').yiiActiveForm('remove', 'reschedule-fees');
            $('#update-fee-percent .field-reschedule-fees').removeClass('has-error');
            $('#update-fee-percent .field-reschedule-fees p.help-block').empty();
        }
    });

    $(document).on('click', '.submit_fee_form', function () {
        $('.booking_fee').change();
        $('.cancel_fee').change();
        $('.reschedule_fee').change();
        $('#update-fee-percent').submit();
    });

    $(document).on('change', '#dailypatientlimitform-date', function () {
        $("#main-js-preloader").fadeIn();
        var date_sel = this.value;
        $.ajax({
            url: 'ajax-daily-shift',
            dataType: 'html',
            method: 'POST',
            data: {id: 7, date: date_sel},
            success: function (response) {
                $('#ajaxLoadShiftDetails').empty();
                $('#ajaxLoadShiftDetails').append(response);
                $("#main-js-preloader").fadeOut();
                $('.addscheduleform-shift_one_start').timepicker({defaultTime: '08:00 A'});
                $('.addscheduleform-shift_one_end').timepicker({defaultTime: '12:00 P'});
                $('.addscheduleform-shift_two_start').timepicker({defaultTime: '12:00 P'});
                $('.addscheduleform-shift_two_end').timepicker({defaultTime: '5:00 P'});
                $('.addscheduleform-shift_three_start').timepicker({defaultTime: '5:00 P'});
                $('.addscheduleform-shift_three_end').timepicker({defaultTime: '10:00 P'});
            }
        });
    });

    $(document).on('change', '#addappointmentform-date', function () {
        $("#main-js-preloader").fadeIn();
        var date_sel = this.value;
        $.ajax({
            url: 'ajax-appointments',
            dataType: 'html',
            method: 'POST',
            data: {id: 7, date: date_sel},
            success: function (response) {
                $('#ajaxLoadBookingDetails').empty();
                $('#ajaxLoadBookingDetails').append(response);
                $("#main-js-preloader").fadeOut();

            }
        });
    });

    $(document).on("change", "#userservicecharge-charge", function () {
        var service_charge = $('#userservicecharge-charge').val();
        var discount_charge = $('#userservicecharge-charge_discount').val();

        if (service_charge <= discount_charge) {
            var disc = service_charge - 1;
            $('#userservicecharge-charge_discount').attr('max', disc);
        } else {
        }
    });

    $(document).on("change", "#userservicecharge-charge_discount", function () {
        var service_charge = $('#userservicecharge-charge').val();
        var discount_charge = $('#userservicecharge-charge_discount').val();

        if (service_charge <= discount_charge) {
            var disc = service_charge - 1;
            $('#userservicecharge-charge_discount').attr('max', disc);
        } else {
        }
    });

});


function addValidationRules(formid, getId) {
    $('#' + formid + '.schedule-form').yiiActiveForm('add', {
        id: getId + '_address',
        name: getId + '_address',
        container: '.field-' + getId + '_address', //or your cllass container
        input: '#' + getId + '_address',
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Hospitals/Clinics cannot be blank."});
        }
    });

    $('#' + formid + '.schedule-form').yiiActiveForm('add', {
        id: getId + '_patient',
        name: getId + '_patient',
        container: '.field-' + getId + '_patient', //or your cllass container
        input: '#' + getId + '_patient',
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Patient Limit cannot be blank."});
        }
    });

    $('#' + formid + '.schedule-form').yiiActiveForm('add', {
        id: getId + '_cfees',
        name: getId + '_cfees',
        container: '.field-' + getId + '_cfees', //or your cllass container
        input: '#' + getId + '_cfees',
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Consultancy Fee cannot be blank."});
        }
    });

    $('#' + formid + '.schedule-form').yiiActiveForm('add', {
        id: getId + '_cdays',
        name: getId + '_cdays',
        container: '.field-' + getId + '_cdays', //or your cllass container
        input: '#' + getId + '_cdays',
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Valid Days cannot be blank."});
        }
    });

    $('#' + formid + '.schedule-form').yiiActiveForm('add', {
        id: getId + '_efees',
        name: getId + '_efees',
        container: '.field-' + getId + '_efees', //or your cllass container
        input: '#' + getId + '_efees',
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Emergency Fee cannot be blank."});
        }
    });

    $('#' + formid + '.schedule-form').yiiActiveForm('add', {
        id: getId + '_edays',
        name: getId + '_edays',
        container: '.field-' + getId + '_edays', //or your cllass container
        input: '#' + getId + '_edays',
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Valid Days cannot be blank."});
        }
    });
    
    $('#' + formid).yiiActiveForm('add', {
        id: 'addscheduleform-weekday-' + getId,
        name: 'addscheduleform-weekday-' + getId,
        container: '.field-addscheduleform-weekday-' + getId, //or your class container
        input: '#addscheduleform-weekday-' + getId,
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Select Days cannot be blank."});
        }
    });
    
    $('#' + formid).yiiActiveForm('add', {
        id: 'addscheduleform-weekday-' + getId,
        name: 'addscheduleform-weekday-' + getId,
        container: '.field-addscheduleform-weekday-' + getId, //or your class container
        input: '#addscheduleform-weekday-' + getId,
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Select Days cannot be blank."});
        }
    });

    $('#' + formid).yiiActiveForm('add', {
        id: 'addscheduleform-start_time-' + getId,
        name: 'addscheduleform-start_time-' + getId,
        container: '.field-addscheduleform-start_time-' + getId, //or your class container
        input: '#addscheduleform-start_time-' + getId,
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "From cannot be blank."});
        }
    });

    $('#' + formid).yiiActiveForm('add', {
        id: 'addscheduleform-end_time-' + getId,
        name: 'addscheduleform-end_time-' + getId,
        container: '.field-addscheduleform-end_time-' + getId, //or your cllass container
        input: '#addscheduleform-end_time-' + getId,
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "To cannot be blank."});
        }
    });

    $('#' + formid).yiiActiveForm('add', {
        id: 'addscheduleform-appointment_time_duration-' + getId,
        name: 'addscheduleform-appointment_time_duration-' + getId,
        container: '.field-addscheduleform-appointment_time_duration-' + getId, //or your cllass container
        input: '#addscheduleform-appointment_time_duration-' + getId,
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Appointment Time Duration cannot be blank."});
        }
    });

    $('#' + formid).yiiActiveForm('add', {
        id: 'addscheduleform-consultation_fees-' + getId,
        name: 'addscheduleform-consultation_fees-' + getId,
        container: '.field-addscheduleform-consultation_fees-' + getId, //or your cllass container
        input: '#addscheduleform-consultation_fees-' + getId,
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Consultancy Fee cannot be blank."});
        }
    });

    $('#' + formid).yiiActiveForm('add', {
        id: 'addscheduleform-emergency_fees-' + getId,
        name: 'addscheduleform-emergency_fees-' + getId,
        container: '.field-addscheduleform-emergency_fees-' + getId, //or your cllass container
        input: '#addscheduleform-emergency_fees-' + getId,
        error: '.help-block', //or your class error
        validate: function (attribute, value, messages, deferred, $form) {
            yii.validation.required(value, messages, {message: "Emergency Fee cannot be blank."});
        }
    });
}




function updateAddress(id) {
    $.ajax({
        url: 'update-address-modal',
        dataType: 'html',
        method: 'POST',
        data: {id: id},
        success: function (response) {
            $('#updateaddress').empty();
            $('#updateaddress').append(response);
            $('#updateaddress').modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });
        }
    });


}

$(document).on('click', '.remove_shiftbox_div', function () {
    $(this).parent().parent().remove();
    ShiftCount--;
});

$('#useraddressimages-image').on('change', function (e) {
    var numItems = $('.address_gallery .address_img_attac').length;
    var files = e.target.files,
            filesLength = files.length;
    var total = numItems + filesLength;
    if (total == 0) {
        $('div.address_gallery').removeClass('gallary_images');
    }
    if (total > 8) {
        alert("You can select maximum 8 images");
    } else {
        $('div.address_gallery').addClass('gallary_images');
        for (var i = 0; i < filesLength; i++) {
            var f = files[i]
            var fileReader = new FileReader();
            fileReader.onload = (function (e) {
                var file = e.target;
                $("<div class=\"address_img_attac\">" +
                        "<img class=\"imageThumb\" src=\"" + e.target.result + "\" title=\"" + file.name + "\"/>" +
                        "<span class=\"remove\"><i class='fa fa-trash'></i></span>" +
                        "</div>").appendTo("div.address_gallery");
                $(".remove").click(function () {
                    $(this).parent(".address_img_attac").remove();
                    var numItems = $('.address_gallery .address_img_attac').length;
                    if (numItems == 0) {
                        $('div.address_gallery').removeClass('gallary_images');
                    }
                });

            });
            fileReader.readAsDataURL(f);
        }
        //imagesPreview(this, 'div.address_gallery');
    }
});
$(document).on('click', '.address_attachment_upload', function () {
    $('#useraddressimages-image').click();
});

$('.addscheduleform-start_time').timepicker({defaultTime: '08:00 A'});
$('.addscheduleform-end_time').timepicker({defaultTime: '12:00 P'});

function getShiftSlots(doctor_id, userType, type, date, plus, operator) {
    if (type == 'shifts') {
        var action = 'ajax-address-list';
        $.ajax({
            url: baseurl + '/' + userType + '/' + action,
            dataType: 'html',
            method: 'POST',
            data: {user_id: doctor_id, type: type, date: date, plus: plus, operator: operator},
            beforeSend: function () {
                $('#appointment_shift_slots').css('opacity', '0.8');
            },
            success: function (response) {
                $('#appointment_shift_slots').css('opacity', '1');
                $('#appointment_shift_slots').html('');
                $('#appointment_shift_slots').append(response);
            }
        });
    } else if (type == 'history') {
        var action = 'ajax-history-content';
        $.ajax({
            url: baseurl + '/' + userType + '/' + action,
            dataType: 'html',
            method: 'POST',
            data: {user_id: doctor_id, type: type, date: date, plus: plus, operator: operator},
            beforeSend: function () {
                $('#history-content').css('opacity', '0.8');
            },
            success: function (response) {
                $('#history-content').css('opacity', '1');
                $('#history-content').html('');
                $('#history-content').append(response);
                $('.shift_slots').slick({
                    dots: false,
                    infinite: false,
                    slidesToShow: 3,
                    adaptiveHeight: false
                });
            }
        });
    } else if (type == 'user_history') {
        var action = 'ajax-user-statistics-data';
        $.ajax({
            url: baseurl + '/' + userType + '/' + action,
            dataType: 'html',
            method: 'POST',
            data: {user_id: doctor_id, type: type, date: date, plus: plus, operator: operator},
            beforeSend: function () {
                $('#history-content').css('opacity', '0.8');
            },
            success: function (response) {
                $('#history-content').css('opacity', '1');
                $('#history-content').html('');
                $('#history-content').append(response);
                $('.shift_slots').slick({
                    dots: false,
                    infinite: false,
                    slidesToShow: 3,
                    adaptiveHeight: false
                });
            }
        });
    } else {
        var action = 'get-date-shifts';
        $.ajax({
            url: baseurl + '/' + userType + '/' + action,
            dataType: 'html',
            method: 'POST',
            data: {user_id: doctor_id, type: type, date: date, plus: plus, operator: operator},
            beforeSend: function () {
                $('#appointment_shift_slots').css('opacity', '0.8');
            },
            success: function (response) {
                $('#appointment_shift_slots').css('opacity', '1');
                $('#appointment_shift_slots').html('');
                $('#appointment_shift_slots').append(response);
                $('.shift_slots').slick({
                    dots: false,
                    infinite: false,
                    slidesToShow: 3,
                    adaptiveHeight: false
                });
            }
        });
    }


}

function removeValidationRules(formid, getId, pagetype) {
    $('#' + formid).yiiActiveForm('remove', 'addscheduleform-weekday-' + getId);
    $('#' + formid).yiiActiveForm('remove', 'addscheduleform-start_time-' + getId);
    $('#' + formid).yiiActiveForm('remove', 'addscheduleform-end_time-' + getId);
    $('#' + formid).yiiActiveForm('remove', 'addscheduleform-appointment_time_duration-' + getId);
    $('#' + formid).yiiActiveForm('remove', 'addscheduleform-consultation_fees-' + getId);
    $('#' + formid).yiiActiveForm('remove', 'addscheduleform-emergency_fees-' + getId);

    $('#' + formid + ' .field-addscheduleform-weekday-' + getId).removeClass('has-error');
    $('#' + formid + ' .field-addscheduleform-start_time-' + getId).removeClass('has-error');
    $('#' + formid + ' .field-addscheduleform-end_time-' + getId).removeClass('has-error');
    $('#' + formid + ' .field-addscheduleform-appointment_time_duration-' + getId).removeClass('has-error');
    $('#' + formid + ' .field-addscheduleform-consultation_fees-' + getId).removeClass('has-error');
    $('#' + formid + ' .field-addscheduleform-emergency_fees-' + getId).removeClass('has-error');

    $('#' + formid + ' .field-addscheduleform-weekday-' + getId + ' div.help-block').empty();
    $('#' + formid + ' .field-addscheduleform-start_time-' + getId + ' div.help-block').empty();
    $('#' + formid + ' .field-addscheduleform-end_time-' + getId + ' div.help-block').empty();
    $('#' + formid + ' .field-addscheduleform-appointment_time_duration-' + getId + ' div.help-block').empty();
    $('#' + formid + ' .field-addscheduleform-consultation_fees-' + getId + ' div.help-block').empty();
    $('#' + formid + ' .field-addscheduleform-emergency_fees-' + getId + ' div.help-block').empty();
}

function feesvalidation(formid, field, getId, value, pagetype) {
    if (value > 0) {
        var newvalue = value - 1;
    } else {
        var newvalue = value;
    }

    if (getId == '0' && pagetype == 'add') {
        var checkvalue = $('#' + formid + ' #addscheduleform-' + field + '_discount').val();
        if (checkvalue > newvalue) {
            $('#' + formid + ' #addscheduleform-' + field + '_discount').val('');
        }
        $('#' + formid + ' #addscheduleform-' + field + '_discount').attr('max', newvalue);
    } else if (getId == '0' && pagetype == 'today_timing') {
        var checkvalue = $('#' + formid + ' #addscheduleform-' + field + '_discount').val();
        if (checkvalue > newvalue) {
            $('#' + formid + ' #addscheduleform-' + field + '_discount').val('');
        }
        $('#' + formid + ' #addscheduleform-' + field + '_discount').attr('max', newvalue);
    } else {
        var checkvalue = $('#' + formid + ' #addscheduleform-' + field + '_discount-' + getId).val();
        if (checkvalue > newvalue) {
            $('#' + formid + ' #addscheduleform-' + field + '_discount-' + getId).val('');
        }
        $('#' + formid + ' #addscheduleform-' + field + '_discount-' + getId).attr('max', newvalue);
    }
}

function shiftOneValue(formid, getId, pagetype) {
    var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    if (getId == '0' && pagetype == 'add') {
        var start_field = '#' + formid + ' #addscheduleform-start_time';
        var end_field = '#' + formid + ' #addscheduleform-end_time';
        var duration_field = '#' + formid + ' #addscheduleform-appointment_time_duration';
        var limit_field = '#' + formid + ' #addscheduleform-patient_limit';
    } else if (getId == '0' && pagetype == 'today_timing') {
        var start_field = '#' + formid + ' #addscheduleform-start_time';
        var end_field = '#' + formid + ' #addscheduleform-end_time';
        var duration_field = '#' + formid + ' #addscheduleform-appointment_time_duration';
        var limit_field = '#' + formid + ' #addscheduleform-patient_limit';
    } else {
        var start_field = '#' + formid + ' #addscheduleform-start_time-' + getId;
        var end_field = '#' + formid + ' #addscheduleform-end_time-' + getId;
        var duration_field = '#' + formid + ' #addscheduleform-appointment_time_duration-' + getId;
        var limit_field = '#' + formid + ' #addscheduleform-patient_limit-' + getId;
    }

    var start_time = $(start_field).val();
    var end_time = $(end_field).val();

    var d = new Date();
    var date = monthNames[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    var stt = new Date(date + ' ' + start_time);

    var endt = new Date(date + ' ' + end_time);
    stt = stt.getTime();
    endt = endt.getTime();

    if (stt == endt) {
        $(start_field).next('.help-block').addClass('errortime').text('Start-time must be smaller then End-time.');
        $(end_field).next('.help-block').addClass('errortime').text('End-time must be bigger then Start-time.');
        return false;
    } else if (stt > endt) {
        $(start_field).next('.help-block').addClass('errortime').text('Start-time must be smaller then End-time.');
        $(end_field).next('.help-block').addClass('errortime').text('End-time must be bigger then Start-time.');
        return false;
    } else {
        var diff = (endt - stt) / 1000;
        if (diff < 0)
            return false;
        diff /= 60;
        var finaldiff = Math.abs(Math.round(diff));
        if (finaldiff > 0) {
            $(duration_field).attr('max', finaldiff);
            $(limit_field).attr('max', finaldiff);
        }
        $(start_field).next('.help-block').removeClass('errortime').text('');
        $(end_field).next('.help-block').removeClass('errortime').text('');
        maxvalidation(formid, 'appointment_time_duration', getId, pagetype);
    }
}

function maxvalidation(formid, field, getId, pagetype) {

    if (getId == '0' && pagetype == 'add') {
        var checkvalue = $('#' + formid + ' #addscheduleform-' + field).val();
        var checkmax = $('#' + formid + ' #addscheduleform-' + field).attr('max');
        var checkmin = $('#' + formid + ' #addscheduleform-' + field).attr('min');
        var value = parseInt(checkvalue, 10);
        var max = parseInt(checkmax, 10);
        var min = parseInt(checkmin, 10);

        if (value > max) {
            $('#' + formid + ' #addscheduleform-' + field).val(max);
            var diff = (checkmax) / (max);

        } else if (value < min) {
            $('#' + formid + ' #addscheduleform-' + field).val(min);
            var diff = (checkmax) / (min);
        } else {
            var diff = (checkmax) / (value);
        }
        var max = parseInt(diff, 10);
        if (field == 'appointment_time_duration') {
            $('#' + formid + ' #addscheduleform-patient_limit').val(max);
        }

    } else if (getId == '0' && pagetype == 'today_timing') {
        var checkvalue = $('#' + formid + ' #addscheduleform-' + field).val();
        var checkmax = $('#' + formid + ' #addscheduleform-' + field).attr('max');
        var checkmin = $('#' + formid + ' #addscheduleform-' + field).attr('min');
        var value = parseInt(checkvalue, 10);
        var max = parseInt(checkmax, 10);
        var min = parseInt(checkmin, 10);

        if (value > max) {
            $('#' + formid + ' #addscheduleform-' + field).val(max);
            var diff = (checkmax) / (max);

        } else if (value < min) {
            $('#' + formid + ' #addscheduleform-' + field).val(min);
            var diff = (checkmax) / (min);
        } else {
            var diff = (checkmax) / (value);
        }
        var max = parseInt(diff, 10);
        if (field == 'appointment_time_duration') {
            $('#' + formid + ' #addscheduleform-patient_limit').val(max);
        }
    } else {
        var checkvalue = $('#' + formid + ' #addscheduleform-' + field + '-' + getId).val();
        var checkmax = $('#' + formid + ' #addscheduleform-' + field + '-' + getId).attr('max');
        var checkmin = $('#' + formid + ' #addscheduleform-' + field + '-' + getId).attr('min');
        var value = parseInt(checkvalue, 10);
        var max = parseInt(checkmax, 10);
        var min = parseInt(checkmin, 10);

        if (value > max) {
            $('#' + formid + ' #addscheduleform-' + field + '-' + getId).val(max);
            var diff = (checkmax) / (max);

        } else if (value < min) {
            $('#' + formid + ' #addscheduleform-' + field + '-' + getId).val(min);
            var diff = (checkmax) / (min);
        } else {
            var diff = (checkmax) / (value);
        }
        var max = parseInt(diff, 10);
        if (field == 'appointment_time_duration') {
            $('#' + formid + ' #addscheduleform-patient_limit-' + getId).val(max);
            $('#' + formid + ' #addscheduleform-patient_limit-' + getId).attr('max', max);
        }
    }
}

function patientcount(formid, field, getId, pagetype) {
    if (getId == '0' && pagetype == 'add') {
        var limit = $('#' + formid + ' #addscheduleform-' + field).val();
        var maxlimit = $('#' + formid + ' #addscheduleform-' + field).attr('max');
        var checkmax = $('#' + formid + ' #addscheduleform-appointment_time_duration').attr('max');
        var max = parseInt(checkmax, 10);

        if (limit > maxlimit) {
            $('#' + formid + ' #addscheduleform-' + field).val(maxlimit);
            limit = maxlimit;
        }
        var diff = (max) / (limit);
        var max = parseInt(diff, 10);
        $('#' + formid + ' #addscheduleform-appointment_time_duration').val(max);

    } else if (getId == '0' && pagetype == 'today_timing') {
        var limit = $('#' + formid + ' #addscheduleform-' + field).val();
        var maxlimit = $('#' + formid + ' #addscheduleform-' + field).attr('max');
        var checkmax = $('#' + formid + ' #addscheduleform-appointment_time_duration').attr('max');
        var max = parseInt(checkmax, 10);

        if (limit > maxlimit) {
            $('#' + formid + ' #addscheduleform-' + field).val(maxlimit);
            limit = maxlimit;
        }
        var diff = (max) / (limit);
        var max = parseInt(diff, 10);
        $('#' + formid + ' #addscheduleform-appointment_time_duration').val(max);

    } else {
        var limit = $('#' + formid + ' #addscheduleform-' + field + '-' + getId).val();
        var maxlimit = $('#' + formid + ' #addscheduleform-' + field + '-' + getId).attr('max');
        var checkmax = $('#' + formid + ' #addscheduleform-appointment_time_duration-' + getId).attr('max');
        var max = parseInt(checkmax, 10);

        if (limit > maxlimit) {
            $('#' + formid + ' #addscheduleform-' + field + '-' + getId).val(maxlimit);
            limit = maxlimit;
        }
        var diff = (max) / (limit);
        var max = parseInt(diff, 10);
        $('#' + formid + ' #addscheduleform-appointment_time_duration-' + getId).val(max);
    }
}

function shiftReminderValue(formid) {
    var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    var end_field = '#' + formid + ' #userreminder-reminder_time';

    var start_time = $("#hiddenEndTime").val();
    var start_date = $("#hiddenEndDate").val();
    var end_time = $(end_field).val();

    var selectedDate = $('#userreminder-reminder_date').val();

    if (selectedDate == '') {
        var d = new Date();
    } else {
        var d = new Date(selectedDate);
    }
    var date = monthNames[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();


    if (start_date == '') {
        var sd = new Date();
    } else {
        var sd = new Date(start_date);
    }
    var sdate = monthNames[sd.getMonth()] + ' ' + sd.getDate() + ', ' + sd.getFullYear();

    var stt = new Date(sdate + ' ' + start_time);

    var endt = new Date(date + ' ' + end_time);
    stt = stt.getTime();
    endt = endt.getTime();

    if (endt < stt) {
        $(end_field).next('.help-block').removeClass('errortime').text('');
        $('#userreminder-reminder_time').removeClass('diverrortime');

        $('#errorhiddencheck').val(0);
    } else {
        $(end_field).next('.help-block').addClass('errortime').text('Reminder must be less than appointment-time.');
        $('#userreminder-reminder_time').addClass('diverrortime');
        $('#errorhiddencheck').val(1);
        return false;
    }
}

function positionError(error) {
    var errorCode = error.code;
    var message = error.message;
    alert(message);
}

function savePosition(position) {
    console.log(position);
    latitude = position.coords.latitude;
    longitude = position.coords.longitude;
    var baseurl = $('#uribase').val();

    $.ajax({
        url: baseurl + '/search/set-location-cookie-navigation',
        // url:'https://205.147.102.6/g/sites/drspanel/search/set-location-cookie-navigation',
        dataType: 'json',
        method: 'POST',
        data: {lat: latitude, lng: longitude},
        success: function (response) {
            if (response.type == 'success') {
                // $('input#txtPlaces').val(response.city);
                console.log(response);
            } else {
            }
        }
    });
}