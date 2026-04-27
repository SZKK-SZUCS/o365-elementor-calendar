import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import huLocale from "@fullcalendar/core/locales/hu";
import "./style.scss";

class O365CalendarWidget {
  constructor($scope) {
    this.container = $scope.find(".o365-fullcalendar-container")[0];
    if (!this.container) return;

    this.email = this.container.dataset.email;
    this.calendarId = this.container.dataset.calendarId;

    this.eventCache = {};
    this.searchTerm = "";
    this.searchTimeout = null;

    this.initWrapper();
    this.initCalendar();

    // Figyeljük a képernyő átméretezését a reszponzív gombok miatt
    window.addEventListener(
      "resize",
      this.debounce(() => this.applyResponsiveSettings(), 200),
    );
  }

  // Megállapítja az eszköztípust
  getDeviceType() {
    const width = window.innerWidth;
    if (width <= 767) return "mobile";
    if (width <= 1024) return "tablet";
    return "desktop";
  }

  // Kiolvassa az aktuális eszközhöz tartozó beállításokat
  getCurrentConfig() {
    const device = this.getDeviceType();
    const viewsRaw =
      this.container.dataset[
        `views${device.charAt(0).toUpperCase() + device.slice(1)}`
      ];
    let defaultView =
      this.container.dataset[
        `default${device.charAt(0).toUpperCase() + device.slice(1)}`
      ];

    // Bolondbiztos: ha üres, kényszerítünk egyet
    let viewsArr = viewsRaw ? viewsRaw.split(",") : ["dayGridMonth"];
    if (!viewsArr.includes(defaultView)) defaultView = viewsArr[0];

    return { allowed: viewsArr, default: defaultView };
  }

  initWrapper() {
    const wrapper = document.createElement("div");
    wrapper.className = "o365-calendar-wrapper";

    const uiHtml = `
      <div class="o365-calendar-header-tools">
          <div class="o365-search-box">
              <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input type="text" class="o365-search-input" placeholder="Keresés (Cím, Helyszín, Leírás)...">
          </div>
      </div>
      <div class="o365-loader" style="display:none;"><div class="spinner"></div></div>
      <div class="o365-event-modal-overlay" style="opacity:0; visibility:hidden;">
          <div class="o365-event-modal">
              <button class="o365-modal-close">&times;</button>
              <h3 class="o365-modal-title"></h3>
              <div class="o365-modal-meta">
                  <div class="meta-item time">
                      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                      <span></span>
                  </div>
                  <div class="meta-item loc" style="display:none;">
                      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                      <span></span>
                  </div>
              </div>
              <div class="o365-modal-desc"></div>
          </div>
      </div>
    `;

    this.container.parentNode.insertBefore(wrapper, this.container);
    wrapper.innerHTML = uiHtml;
    wrapper.appendChild(this.container);

    this.searchInput = wrapper.querySelector(".o365-search-input");
    this.loader = wrapper.querySelector(".o365-loader");
    this.modalOverlay = wrapper.querySelector(".o365-event-modal-overlay");
    this.modalTitle = wrapper.querySelector(".o365-modal-title");
    this.modalTime = wrapper.querySelector(".meta-item.time span");
    this.modalLocWrap = wrapper.querySelector(".meta-item.loc");
    this.modalLocTxt = wrapper.querySelector(".meta-item.loc span");
    this.modalDesc = wrapper.querySelector(".o365-modal-desc");

    wrapper
      .querySelector(".o365-modal-close")
      .addEventListener("click", () => this.closeModal());
    this.modalOverlay.addEventListener("click", (e) => {
      if (e.target === this.modalOverlay) this.closeModal();
    });

    this.searchInput.addEventListener("input", (e) => {
      this.searchTerm = e.target.value.toLowerCase().trim();
      const cfg = this.getCurrentConfig();

      if (
        this.searchTerm.length > 0 &&
        this.calendar.view.type !== "listMonth"
      ) {
        this.calendar.changeView("listMonth");
      } else if (
        this.searchTerm.length === 0 &&
        this.calendar.view.type !== cfg.default
      ) {
        this.calendar.changeView(cfg.default);
      }
      this.calendar.refetchEvents();
    });
  }

  initCalendar() {
    const cfg = this.getCurrentConfig();

    this.calendar = new Calendar(this.container, {
      plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
      initialView: cfg.default,
      locale: huLocale,
      timeZone: "local",
      height: "100%", // Flexbox szülő adja a magasságot, ez belső scrollt generál
      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: cfg.allowed.length > 1 ? cfg.allowed.join(",") : "",
      },
      buttonText: {
        today: "Ma",
        month: "Hónap",
        week: "Heti Rács",
        listWeek: "Heti Lista",
        listMonth: "Havi Lista",
      },
      dayMaxEvents: true,
      eventTimeFormat: { hour: "2-digit", minute: "2-digit", hour12: false },

      loading: (isLoading) => {
        this.loader.style.display = isLoading ? "flex" : "none";
      },
      events: (info, success, failure) =>
        this.fetchEvents(info, success, failure),
      eventMouseEnter: (info) => this.showTooltip(info.el, info.event),
      eventMouseLeave: () => this.hideTooltip(),
      eventClick: (info) => {
        info.jsEvent.preventDefault();
        this.openModal(info.event);
      },
    });

    this.calendar.render();
  }

  applyResponsiveSettings() {
    if (!this.calendar) return;
    const cfg = this.getCurrentConfig();

    // Frissítjük a gombokat
    this.calendar.setOption("headerToolbar", {
      left: "prev,next today",
      center: "title",
      right: cfg.allowed.length > 1 ? cfg.allowed.join(",") : "",
    });

    // Ha az aktuális nézet nincs az engedélyezettek közt (mert pl asztaliról mobilra húzták az ablakot), vagy nincs keresés, alkalmazzuk a defaultot
    if (!this.searchTerm && !cfg.allowed.includes(this.calendar.view.type)) {
      this.calendar.changeView(cfg.default);
    }
  }

  fetchEvents(info, successCallback, failureCallback) {
    const cacheKey = `${info.startStr}_${info.endStr}`;
    if (this.eventCache[cacheKey])
      return successCallback(this.filterEvents(this.eventCache[cacheKey]));

    const url = `/wp-json/o365cal/v1/events?email=${encodeURIComponent(
      this.email,
    )}&calendar_id=${encodeURIComponent(
      this.calendarId,
    )}&start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(
      info.endStr,
    )}`;
    fetch(url)
      .then((res) => res.json())
      .then((data) => {
        if (data.success === false) return failureCallback(data.message);
        this.eventCache[cacheKey] = data;
        successCallback(this.filterEvents(data));
      })
      .catch((err) => failureCallback(err));
  }

  filterEvents(events) {
    if (!this.searchTerm) return events;
    return events.filter((e) => {
      const t = e.title?.toLowerCase() || "";
      const l = e.extendedProps?.location?.toLowerCase() || "";
      const d = e.extendedProps?.body?.toLowerCase() || "";
      return (
        t.includes(this.searchTerm) ||
        l.includes(this.searchTerm) ||
        d.includes(this.searchTerm)
      );
    });
  }

  showTooltip(el, event) {
    let tooltip = document.querySelector(".o365-calendar-tooltip");
    if (!tooltip) {
      tooltip = document.createElement("div");
      tooltip.className = "o365-calendar-tooltip";
      document.body.appendChild(tooltip);
    }

    const start = event.start.toLocaleTimeString("hu-HU", {
      hour: "2-digit",
      minute: "2-digit",
    });
    const loc = event.extendedProps.location
      ? `📍 ${event.extendedProps.location}`
      : "";
    tooltip.innerHTML = `<div class="tooltip-title">${event.title}</div><div class="tooltip-time">${start}</div><div class="tooltip-loc">${loc}</div>`;

    const rect = el.getBoundingClientRect();
    tooltip.style.left = `${rect.left + window.scrollX}px`;
    tooltip.style.top = `${
      rect.top + window.scrollY - tooltip.offsetHeight - 10
    }px`;
    tooltip.style.display = "block";
    setTimeout(() => (tooltip.style.opacity = "1"), 10);
  }

  hideTooltip() {
    const tooltip = document.querySelector(".o365-calendar-tooltip");
    if (tooltip) {
      tooltip.style.opacity = "0";
      setTimeout(() => (tooltip.style.display = "none"), 200);
    }
  }

  openModal(event) {
    this.hideTooltip();
    this.modalTitle.textContent = event.title;

    const opt = {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    };
    let timeStr = event.start.toLocaleString("hu-HU", opt);
    if (event.end && !event.allDay)
      timeStr +=
        " - " +
        event.end.toLocaleString("hu-HU", {
          hour: "2-digit",
          minute: "2-digit",
        });
    else if (event.allDay)
      timeStr =
        event.start.toLocaleDateString("hu-HU", {
          month: "long",
          day: "numeric",
        }) + " (Egész napos)";

    this.modalTime.textContent = timeStr;
    const loc = event.extendedProps?.location;
    if (loc) {
      this.modalLocTxt.textContent = loc;
      this.modalLocWrap.style.display = "flex";
    } else {
      this.modalLocWrap.style.display = "none";
    }

    this.modalDesc.innerHTML =
      event.extendedProps?.body || "<em>Nincs megadva leírás.</em>";
    this.modalOverlay.style.visibility = "visible";
    this.modalOverlay.style.opacity = "1";
  }

  closeModal() {
    this.modalOverlay.style.opacity = "0";
    setTimeout(() => {
      this.modalOverlay.style.visibility = "hidden";
    }, 300);
  }

  debounce(func, wait) {
    let timeout;
    return function (...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }
}

jQuery(window).on("elementor/frontend/init", () => {
  elementorFrontend.hooks.addAction(
    "frontend/element_ready/o365_calendar.default",
    function ($scope) {
      new O365CalendarWidget($scope);
    },
  );
});
