(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.vactory_appointment = {
    attach: function (context, setting) {
      var app = {};
      var allLocales = [];
      var calendar = {};
      var events = JSON.parse(drupalSettings.vactory_appointment.adviser_appointments);
      var adviserHolidays = JSON.parse(drupalSettings.vactory_appointment.adviser_holidays);
      var adviserAppointmentInfos = drupalSettings.vactory_appointment.adviser_appointment_infos;
      var serverTimezoneOfsset = drupalSettings.vactory_appointment.server_timezone_ofsset;
      events = events.map(function (event) {
        appointment = adviserAppointmentInfos.filter(function (el) {
          return el.id === event.id;
        });
        if (appointment) {
          event.title = Drupal.t('RDV de') + ' ' + appointment[0].full_name;
        }
        return event;
      });
      events = events.concat(adviserHolidays);
      var adviserHolidaysSerial = Array.isArray(adviserHolidays) && adviserHolidays.length > 0 ? parseInt(adviserHolidays[adviserHolidays.length - 1].id.split("_")[2]) + 1 : 0;
      var calendarHeader = {
        left:   'title',
        center: '',
        right:  'prev,next'
      };
      app.getNewEvent = function (appointmentID, dateStart) {};
      app.dateClickCallback = function (dateStart) {};
      app.eventRenderCallback = function (event, element) {};
      $.getScript( "../../../../../../../libraries/fullcalendar/packages/core/locales-all.js", function(data) {
        allLocales = data;
      });
      app.options = {
        plugins:['timeGrid', 'interaction'],
        editable: true,
        allDaySlot: false,
        header: calendarHeader,
        footer: false,
        height: "auto",
        defaultDate: drupalSettings.vactory_appointment.current_date,
        buttonText: {
          today:    Drupal.t("Aujourd'hui")
        },
        dateClick: function (dateInfo) {
          app.dateClickCallback(dateInfo.dateStr)
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
        locale: 'fr',
        events: events,
        eventDrop: function (event, dayDelta, minuteDelta, allDay, revertFunc) {
          app.eventDropCallback(event, dayDelta, minuteDelta, allDay, revertFunc);
        },
        eventRender: function(infos) {
          if (isNaN(infos.event.id)) {
            eventCloser = document.createElement("span");
            eventCloser.setAttribute("class", 'event-closer');
            eventCloser.setAttribute("style", 'position: absolute; right: 5px; top: 2px; z-index: 999; font-size: 18px;');
            infos.el.setAttribute("style", infos.el.getAttribute("style") + "position: relative; display: block;");
            eventCloser.append("×");
            infos.el.append(eventCloser);
            $(infos.el).find(".event-closer").click(function(e) {
              var appointment = calendar.getEventById(infos.event.id);
              appointment.remove();
              adviserHolidays = adviserHolidays.filter(function (holiday) {
                return holiday.id !== infos.event.id;
              });
              $('textarea[name="field_adviser_holiday[0][value]"]').val(JSON.stringify(adviserHolidays));
            });
          }
          else {
            appointmentInfo = adviserAppointmentInfos.filter(function (appointment) {
              return infos.event.id === appointment.id;
            });
            if (appointmentInfo) {
              appointmentInfo = appointmentInfo[0];
              eventDescription = document.createElement("div");
              eventDescription.setAttribute("class", 'event-description');
              eventDescription.setAttribute("data-date", infos.event.start);
              content = '<strong>' + appointmentInfo.full_name + '</strong><br>';
              content += '<strong>Tél:</strong> ' + appointmentInfo.telephone + '<br>';
              content += '<strong>E-mail:</strong> ' + '<a href="mailto:' + appointmentInfo.email + '">' + appointmentInfo.email + '</a><br>';
              eventDescription.innerHTML = content;
              infos.el.append(eventDescription);
              date = new Date(infos.event.start);
              dateHour = date.getHours();
              if (dateHour >= 10) {
                $(infos.el).hover(
                  function () {
                    $(this).find('.event-description').addClass('show');
                  },
                  function () {
                    $(this).find('.event-description').removeClass('show');
                  }
                );
              }
              else {
                $(infos.el).find('.event-description').addClass('event-description-bottom');
                $(infos.el).hover(
                  function () {
                    $(this).find('.event-description').addClass('show-bottom');
                    $(this).siblings().css("z-index", 0);
                  },
                  function () {
                    $(this).find('.event-description').removeClass('show-bottom');
                    $(this).siblings().css("z-index", 1);
                  }
                );
              }
            }
          }
        },
        eventResize: function(infos) {
          app.eventResizeCallback(infos);
        }

      };
      var calendarEl = document.getElementById('adviser-calendar');
      calendar = new FullCalendar.Calendar(calendarEl, app.options);
      calendar.render();
      app.dateClickCallback = function (dateStart) {
        appointment = events.filter(function(event) {
          return dateStart.indexOf(event.start) >= 0;
        });
        appointment = appointment.concat(adviserHolidays.filter(function (holiday) {
          return dateStart.indexOf(holiday.start) >= 0;
        }));
        // Check if clicked date has note already been reserved.
        if (Array.isArray(appointment) && !appointment.length) {
          $('#adviser-holiday-title-wrapper').show();
          $('.adviser-holiday-title-bg').show();
          holidayID = 'adviser_holiday_' + adviserHolidaysSerial;
          adviserHoliday = app.getNewEvent(holidayID, dateStart);
          adviserHolidays.push(adviserHoliday);
          calendar.addEvent(adviserHoliday);
          adviserHolidaysSerial++;
          $('textarea[name="field_adviser_holiday[0][value]"]').val(JSON.stringify(adviserHolidays));
        }
      };
      $('#adviser-holiday-title-button').click(function (e) {
        e.preventDefault();
        var title = $('input[name="adviser-holiday-title"]').val();
        if (title) {
          adviserHoliday = adviserHolidays.pop();
          adviserHoliday.title = Drupal.t(title);
          adviserHolidays.push(adviserHoliday);
          adviserHolidayEvent = calendar.getEventById(adviserHoliday.id);
          adviserHolidayEvent.remove();
          calendar.addEvent(adviserHoliday);
          $('textarea[name="field_adviser_holiday[0][value]"]').val(JSON.stringify(adviserHolidays));
          $('#adviser-holiday-title-wrapper').hide();
          $('.adviser-holiday-title-bg').hide();
          $('.adviser-title-error').hide();
          $('input[name="adviser-holiday-title"]').val("");
        }
        else {
          $('.adviser-title-error').show();
          $('.adviser-title-error').text(Drupal.t("Veuillez saisir l'intitulé de votre congés ou fermer la présente popin pour utiliser l'intitulé par défaut."));
        }
      });
      $('span.adviser-holiday-title-closer').click(function (e) {
        $('#adviser-holiday-title-wrapper').hide();
        $('.adviser-holiday-title-bg').hide();
        $('.adviser-title-error').hide();
        $('input[name="adviser-holiday-title"]').val("");
      });
      app.eventDropCallback = function (event, dayDelta, minuteDelta, allDay, revertFunc) {
        newEvent = event.event;
        oldEvent = event.oldEvent;
        newHoliday = app.getNewEvent(newEvent.id, newEvent.start);
        newHoliday.end = newEvent.end;
        adviserHolidays = adviserHolidays.filter(function (holiday) {
          return holiday.id !== oldEvent.id;
        });
        adviserHolidays.push(newHoliday);
        $('textarea[name="field_adviser_holiday[0][value]"]').val(JSON.stringify(adviserHolidays));
      };
      app.getNewEvent = function (appointmentID, dateStart) {
        return {
          id: appointmentID,
          title: Drupal.t('Conseiller en congés'),
          start: dateStart,
          color: '#fff',
          textColor: '#2196f3',
          borderColor: '#2196f3'
        };
      };
      app.eventResizeCallback = function (infos) {
        if (infos.event.end !== null) {
          concernedHoliday = adviserHolidays.filter(function (holiday) {
            return holiday.id === infos.event.id;
          });
          concernedHoliday = concernedHoliday[0];
          date = new Date(infos.event.end);
          concernedHoliday.end = getDateTime(date) + serverTimezoneOfsset;
          adviserHolidays = adviserHolidays.filter(function (holiday) {
            return holiday.id !== infos.event.id;
          });
          adviserHolidays.push(concernedHoliday);
          $('textarea[name="field_adviser_holiday[0][value]"]').val(JSON.stringify(adviserHolidays));
        }
      };
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
