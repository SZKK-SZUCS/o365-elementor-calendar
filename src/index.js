import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import huLocale from "@fullcalendar/core/locales/hu";
import "./style.scss";

class O365CalendarWidget {
  constructor($scope) {
    this.$scope = $scope;
    // Az Elementor átadja a widget konténerét (jQuery objektumként)
    this.container = $scope.find(".o365-fullcalendar-container")[0];

    if (!this.container) return;

    this.email = this.container.dataset.email;
    this.calendarId = this.container.dataset.calendarId;
    this.defaultView = this.container.dataset.defaultView || "dayGridMonth";

    this.initCalendar();
  }

  initCalendar() {
    if (!this.email || !this.calendarId) return;

    // src/index.js részlet
    const calendar = new Calendar(this.container, {
      plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
      initialView: this.defaultView,
      locale: huLocale,
      timeZone: "local", // Ez kulcsfontosságú az Outlook eseményeknél
      dayMaxEvents: true, // "több esemény" link, ha nem fér el
      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,listWeek",
      },
      eventTimeFormat: {
        hour: "2-digit",
        minute: "2-digit",
        meridiem: false,
        hour12: false,
      },
      events: (info, success, failure) =>
        this.fetchEvents(info, success, failure),
    });

    calendar.render();
  }

  fetchEvents(info, successCallback, failureCallback) {
    const url = `/wp-json/o365cal/v1/events?email=${encodeURIComponent(
      this.email,
    )}&calendar_id=${encodeURIComponent(
      this.calendarId,
    )}&start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(
      info.endStr,
    )}`;

    fetch(url)
      .then((response) => {
        if (!response.ok)
          throw new Error("Hálózati hiba a Microsoft Graph elérésekor");
        return response.json();
      })
      .then((data) => {
        if (data.success === false) {
          console.error("O365 API Hiba:", data.message);
          failureCallback(data.message);
          return;
        }
        successCallback(data);
      })
      .catch((error) => {
        console.error("Hiba az események lekérésekor:", error);
        failureCallback(error);
      });
  }

  openModal(event) {
    let overlay = document.querySelector(".o365-event-modal-overlay");

    if (!overlay) {
      overlay = document.createElement("div");
      overlay.className = "o365-event-modal-overlay";
      overlay.innerHTML = `
                <div class="o365-event-modal">
                    <button class="close-btn">&times;</button>
                    <h3 class="event-title"></h3>
                    <span class="event-time"></span>
                    <div class="event-location"></div>
                    <div class="event-body"></div>
                </div>
            `;
      document.body.appendChild(overlay);

      overlay
        .querySelector(".close-btn")
        .addEventListener("click", () => overlay.classList.remove("active"));
      overlay.addEventListener("click", (e) => {
        if (e.target === overlay) overlay.classList.remove("active");
      });
    }

    // Adatok betöltése
    overlay.querySelector(".event-title").textContent = event.title;

    const startStr = event.start.toLocaleString("hu-HU", {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
    const endStr = event.end
      ? event.end.toLocaleString("hu-HU", {
          hour: "2-digit",
          minute: "2-digit",
        })
      : "";
    overlay.querySelector(".event-time").textContent = endStr
      ? `${startStr} - ${endStr}`
      : startStr;

    const props = event.extendedProps;
    overlay.querySelector(".event-location").innerHTML = props.location
      ? `📍 ${props.location}`
      : "";
    overlay.querySelector(".event-body").innerHTML =
      props.body || props.bodyPreview || "<em>Nincs leírás.</em>";

    overlay.classList.add("active");
  }
}

// Inicializálás az Elementor ökoszisztémáján keresztül
jQuery(window).on("elementor/frontend/init", () => {
  // A widget neve a PHP class get_name() metódusából jön: 'o365_calendar'
  elementorFrontend.hooks.addAction(
    "frontend/element_ready/o365_calendar.default",
    function ($scope) {
      new O365CalendarWidget($scope);
    },
  );
});
