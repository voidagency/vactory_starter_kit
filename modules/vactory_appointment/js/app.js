(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.vactory_appointment = {
    attach: function (context, setting) {
      if (context !== document) {
        return;
      }
      var app = {};
      var isEditAppointment = drupalSettings.vactory_appointment.is_edit_appointment;
      var choosedDate = drupalSettings.vactory_appointment.choosed_date;
      var mobileChoosedDay = drupalSettings.vactory_appointment.mobile_choosed_day;
      var mobileChoosedTime = drupalSettings.vactory_appointment.mobile_choosed_time;
      var serverTimezoneOfsset = drupalSettings.vactory_appointment.server_timezone_ofsset;
      var events = JSON.parse(drupalSettings.vactory_appointment.adviser_appointments);
      var appointmentToEditID = null;
      var appointmentToEdit = null;
      var isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
      if (matchMedia('(max-width: 767.98px)').matches) {
        var hoursOptions = ['08', '09', '10', '11', '12', '13', '14', '15', '16', '17'];
        var minutesOptions = ['00', '30'];
        var currentDate = new Date();
        var year = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(currentDate);
        var month = new Intl.DateTimeFormat('en', { month: '2-digit' }).format(currentDate);
        var day = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(currentDate);
        currentDate = year + '-' + month + '-' + day;
        // Mobile case.
        selectDateWrapper = document.createElement("div");
        selectDateWrapper.setAttribute("class", 'mobile-date-wrapper');
        content = '<div class="date-wrapper mb-2">';
        content += '<label>' + Drupal.t('Choisissez la date de votre rendez-vous') + '</label>';
        content += '<input id="mobile-appointment-day" type="text" min="' + currentDate + '" placeholder="jj/mm/aaaa" class="appointment-date required bg-white form-control js-has-icon" readonly>';
        content += '</div>';
        content += '<div class="hours-wrapper">';
        content += '<label>' + Drupal.t("Choisissez l'heure de votre rendez-vous") + '</label>';
        content += '<div class="d-flex">';
        content += '<div><select id="mobile-appointment-hour" name="hours">';
        //content += '<option value="">' + Drupal.t("- Sélectionner l'heure -") + '</option>';
        hoursOptions.forEach(function (el) {
          content += '<option value="' + el + '">' + el + '</option>';
        });
        content += '</select></div>';
        content += '<span class="m-1 font-20 font-weight-bold align-middle">:</span>';
        content += '<div><select id="mobile-appointment-minutes" name="minutes">';
        minutesOptions.forEach(function (el) {
          content += '<option value="' + el + '">' + el + '</option>';
        });
        content += '</select></div></div>';
        content += '</div>';

        selectDateWrapper.innerHTML = content;
        $('#calendar').html(selectDateWrapper);
        $('#calendar').addClass('loaded');
        $('.appointment-date').datepicker({
          startDate: new Date(),
          todayHighlight: true,
          weekStart: 1,
          daysOfWeekDisabled: [0]
        });
        $('#mobile-appointment-day, #mobile-appointment-hour, #mobile-appointment-minutes').on('change', function (e) {
          choosedDay = $('#mobile-appointment-day').val();
          choosedHour = $('#mobile-appointment-hour').val();
          choosedMinutes = $('#mobile-appointment-minutes').val();
          if (choosedDay) {
            choosedDay = choosedDay.split("/");
            date = new Date(choosedDay[2], choosedDay[1]-1, choosedDay[0], choosedHour, choosedMinutes);
            $('input[name="appointment_date"]').val(getDateTime(date) + serverTimezoneOfsset);
          }
        });
        if (mobileChoosedDay.length && mobileChoosedTime.length) {
          hourValue = mobileChoosedTime.split(':')[0];
          minutesValue = mobileChoosedTime.split(':')[1];
          $('#mobile-appointment-day').val(mobileChoosedDay);
          $('#mobile-appointment-hour').val(hourValue);
          $('#mobile-appointment-minutes').val(minutesValue);
        }
      }
      else {
        var isFirstClick = true;
        var allLocales = [];
        var adviserHolidays = JSON.parse(drupalSettings.vactory_appointment.adviser_holidays);
        if (adviserHolidays) {
          adviserHolidays = adviserHolidays.map(function (holiday) {
            holiday.textColor = "rgba(0, 0, 0, 0.3)";
            holiday.color = "rgba(215, 215, 215, 0.3)";
            holiday.editable = false;
            return holiday;
          });
          events = events.concat(adviserHolidays);
        }
        var calendarHeader = {
          left:   'title',
          center: '',
          right:  'prev,next'
        };
        app.getNewEvent = function (appointmentID, dateStart) {};
        app.dateClickCallback = function (dateStart) {};
        if (isEditAppointment) {
          appointmentToEditID = drupalSettings.vactory_appointment.appointment_id;
          appointmentToEdit = events.filter(function (el) {
            return el.id === appointmentToEditID;
          })[0];
          console.log(events);
          console.log(appointmentToEditID);
          calendarHeader.right = 'today prev,next';
        }
        if (choosedDate) {
          events.push({
            id: 'user_selected_appointment',
            title: Drupal.t('Mon rendez-vous'),
            start: choosedDate,
            color: '#ffffff',
            textColor: '#2196f3',
            borderColor: '#2196f3',
            startEditable: true,
            durationEditable: false,
          });
        }
        $.getScript( "../../../../../libraries/fullcalendar/packages/core/locales-all.js", function(data) {
          allLocales = data;
        });
        app.options = {
          plugins:['timeGrid', 'interaction'],
          editable: true,
          allDaySlot: false,
          header: calendarHeader,
          footer: false,
          height: "auto",
          defaultDate: isEditAppointment ? new Date(appointmentToEdit.start) : drupalSettings.vactory_appointment.current_date,
          buttonText: {
            today:    Drupal.t("Aujourd'hui")
          },
          dateClick: function (dateInfo) {
            app.dateClickCallback(dateInfo.dateStr);
          },
          hiddenDays: [0],
          validRange: {
            start: drupalSettings.vactory_appointment.current_date,
          },
          minTime: '08:00:00',
          maxTime: '18:00:00',
          defaultTimedEventDuration: '00:30',
          hour12: true,
          titleFormat: {
            month: 'long',
            year: 'numeric',
            day: 'numeric',
            weekday: 'long'
          },
          columnHeaderFormat: { weekday: 'long', month: 'long', day: 'numeric' },
          slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            omitZeroMinute: true,
            meridiem: 'short'
          },
          locales: allLocales,
          locale: drupalSettings.vactory_appointment.lang_code,
          events: events,
          eventDrop: function (event, dayDelta, minuteDelta, allDay, revertFunc) {
            app.eventDropCallback(event, dayDelta, minuteDelta, allDay, revertFunc);
          },
          eventRender: function(infos) {
            var reg = new RegExp('^\\d+$');
            var infoEventId = infos.event.id;
            if(reg.test(infoEventId)) {
              infos.el.className += ' callendar-event-element';
            }
          }
        };
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, app.options);
        calendar.render();
        if (isEditAppointment) {
          var appointment = calendar.getEventById(appointmentToEditID);
          appointment.remove();
          if (!choosedDate) {
            appointmentToEdit.title = Drupal.t('Mon rendez-vous');
            appointmentToEdit.color = '#ffffff';
            appointmentToEdit.textColor = '#2196f3';
            appointmentToEdit.borderColor = '#2196f3';
            appointmentToEdit.startEditable = true;
            appointmentToEdit.durationEditable = false;
            calendar.addEvent(appointmentToEdit);
          }
        }
        app.dateClickCallback = function (dateStart) {
          appointment = events.filter(function(event) {
            eventDateStart = new Date(event.start);
            eventDateEnd = event.end ? new Date(event.end) : new Date(eventDateStart.getTime() + (30 * 60 * 1000));
            choosedDateStart = new Date(dateStart);
            return choosedDateStart >= eventDateStart && choosedDateStart < eventDateEnd;
          });
          // Check if clicked date has note already been reserved.
          if (Array.isArray(appointment) && !appointment.length) {
            selectedDate = new Date(dateStart);
            selectedDate = getDateTime(selectedDate) + serverTimezoneOfsset;
            $('input[name="appointment_date"]').val(selectedDate);
            appointmentID = isEditAppointment ? appointmentToEditID : 'user_selected_appointment';
            appointmentID = isEditAppointment && choosedDate ? 'user_selected_appointment' : appointmentID;
            if (!isFirstClick || isEditAppointment || choosedDate) {
              var appointment = calendar.getEventById(appointmentID);
              appointment.remove();
            }
            userEvent = app.getNewEvent(appointmentID, dateStart);
            calendar.addEvent(userEvent);
            isFirstClick = false;
          }
        };
        app.eventDropCallback = function (event, dayDelta, minuteDelta, allDay, revertFunc) {
          newEvent = event.event;
          var date = new Date(newEvent.start);
          $('input[name="appointment_date"]').val(getDateTime(date) + serverTimezoneOfsset);
        };
        app.getNewEvent = function (appointmentID, dateStart) {
          return {
            id: appointmentID,
            title: Drupal.t('Mon rendez-vous'),
            start: dateStart,
            color: '#ffffff',
            textColor: '#2196f3',
            borderColor: '#2196f3',
            startEditable: true,
            durationEditable: false,
          };
        }
      }
    }
  };

  // Format Date.
  function getDateTime(dt) {
    current_date = dt.getDate();
    current_month = dt.getMonth() + 1;
    current_year = dt.getFullYear();
    current_hrs = dt.getHours();
    current_mins = dt.getMinutes();
    current_secs = dt.getSeconds();
    // Add 0 before date, month, hrs, mins or secs if they are less than 0
    current_date = current_date < 10 ? '0' + current_date : current_date;
    current_month = current_month < 10 ? '0' + current_month : current_month;
    current_hrs = current_hrs < 10 ? '0' + current_hrs : current_hrs;
    current_mins = current_mins < 10 ? '0' + current_mins : current_mins;
    current_secs = current_secs < 10 ? '0' + current_secs : current_secs;
    // Current datetime
    // String such as 2016-07-16T19:20:30
    current_datetime = current_year + '-' + current_month + '-' + current_date + 'T' + current_hrs + ':' + current_mins + ':' + current_secs;
    return current_datetime;
  }
  // Get Formatted date time zone.
  function getTimeZoneOfsset(date) {
    var timezone_offset_min = date.getTimezoneOffset(),
      offset_hrs = parseInt(Math.abs(timezone_offset_min/60)),
      offset_min = Math.abs(timezone_offset_min%60),
      timezone_standard;
    if(offset_hrs < 10)
      offset_hrs = '0' + offset_hrs;
    if(offset_min < 10)
      offset_min = '0' + offset_min;
    // Add an opposite sign to the offset
    // If offset is 0, it means timezone is UTC
    if(timezone_offset_min < 0)
      timezone_standard = '+' + offset_hrs + ':' + offset_min;
    else if(timezone_offset_min > 0)
      timezone_standard = '-' + offset_hrs + ':' + offset_min;
    else if(timezone_offset_min === 0)
      timezone_standard = 'Z';
    return timezone_standard;
  }
})(jQuery, Drupal, drupalSettings);
