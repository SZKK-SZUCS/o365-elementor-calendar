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

    this.calendarId = this.container.dataset.calendarId;

    // Idő és Dátum korlátok beolvasása
    this.slotMin = this.container.dataset.slotMin || "00:00:00";
    this.slotMax = this.container.dataset.slotMax || "24:00:00";
    this.validStart = this.container.dataset.validStart || null;
    this.validEnd = this.container.dataset.validEnd || null;
    this.privacy = this.container.dataset.privacy || "mask";
    this.maskText = this.container.dataset.maskText || "Foglalt";
    this.categoryFilter = this.container.dataset.categoryFilter || "";
    this.useColors = this.container.dataset.useColors || "yes";
    this.displayEventTime = this.container.dataset.displayEventTime === "yes";

    this.eventCache = {};
    this.searchTerm = "";
    this.searchTimeout = null;
    this.currentOpenEvent = null;

    this.initWrapper();
    this.initCalendar();

    window.addEventListener(
      "resize",
      this.debounce(() => this.applyResponsiveSettings(), 200),
    );
  }

  getDeviceType() {
    const width = window.innerWidth;
    if (width <= 767) return "mobile";
    if (width <= 1024) return "tablet";
    return "desktop";
  }

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
              
              <div class="o365-modal-actions" style="margin-top: 25px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 20px; text-align: right;">
                  <button class="o365-export-ical-btn">
                      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 5px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                      Hozzáadás a naptárhoz
                  </button>
              </div>
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
    this.exportBtn = wrapper.querySelector(".o365-export-ical-btn");

    wrapper
      .querySelector(".o365-modal-close")
      .addEventListener("click", () => this.closeModal());
    this.modalOverlay.addEventListener("click", (e) => {
      if (e.target === this.modalOverlay) this.closeModal();
    });

    this.exportBtn.addEventListener("click", () => {
      if (this.currentOpenEvent) this.downloadICal(this.currentOpenEvent);
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
      displayEventTime: this.displayEventTime,
      locale: huLocale,
      timeZone: this.container.dataset.timezone || "local",

      height: "100%",
      expandRows: true,

      slotMinTime: this.slotMin,
      slotMaxTime: this.slotMax,
      validRange: {
        start: this.validStart ? this.validStart : null,
        end: this.validEnd ? this.validEnd : null,
      },

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

    setTimeout(() => {
      this.calendar.updateSize();
    }, 150);
  }

  applyResponsiveSettings() {
    if (!this.calendar) return;
    const cfg = this.getCurrentConfig();

    this.calendar.setOption("headerToolbar", {
      left: "prev,next today",
      center: "title",
      right: cfg.allowed.length > 1 ? cfg.allowed.join(",") : "",
    });

    if (!this.searchTerm && !cfg.allowed.includes(this.calendar.view.type)) {
      this.calendar.changeView(cfg.default);
    }
  }

  fetchEvents(info, successCallback, failureCallback) {
    const cacheKey = `${info.startStr}_${info.endStr}`;
    if (this.eventCache[cacheKey])
      return successCallback(this.filterEvents(this.eventCache[cacheKey]));

    const url = `/wp-json/o365cal/v1/events?calendar_id=${encodeURIComponent(
      this.calendarId,
    )}&start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(
      info.endStr,
    )}&privacy=${this.privacy}&mask_text=${encodeURIComponent(
      this.maskText,
    )}&category_filter=${encodeURIComponent(this.categoryFilter)}&use_colors=${
      this.useColors
    }`;

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
    this.hideTooltip();

    const tooltip = document.createElement("div");
    tooltip.className = "o365-calendar-tooltip";
    document.body.appendChild(tooltip);

    const start = event.start.toLocaleTimeString("hu-HU", {
      hour: "2-digit",
      minute: "2-digit",
    });

    // Emojik helyett inline SVG ikonok
    const timeIcon = `<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: text-bottom; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
    const locIcon = `<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: text-bottom; margin-right: 4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;

    const loc = event.extendedProps.location
      ? `<div class="tooltip-loc">${locIcon} ${event.extendedProps.location}</div>`
      : "";

    tooltip.innerHTML = `
        <div class="tooltip-title">${event.title}</div>
        <div class="tooltip-time">${timeIcon} ${start}</div>
        ${loc}
    `;

    const rect = el.getBoundingClientRect();
    const tooltipWidth = 250;

    let leftPos =
      rect.left + window.scrollX + rect.width / 2 - tooltipWidth / 2;
    if (leftPos < 10) leftPos = 10;

    tooltip.style.left = `${leftPos}px`;
    tooltip.style.top = `${
      rect.top + window.scrollY - tooltip.offsetHeight - 15
    }px`;
    tooltip.style.display = "block";

    requestAnimationFrame(() => {
      tooltip.style.opacity = "1";
    });
  }

  hideTooltip() {
    document
      .querySelectorAll(".o365-calendar-tooltip")
      .forEach((t) => t.remove());
  }

  openModal(event) {
    this.hideTooltip();
    this.currentOpenEvent = event;

    const isPrivate = event.extendedProps?.isPrivate;
    const lockIcon = isPrivate
      ? `<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 8px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`
      : "";
    this.modalTitle.innerHTML = lockIcon + event.title;

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
    if (loc && !isPrivate) {
      this.modalLocTxt.textContent = loc;
      this.modalLocWrap.style.display = "flex";
    } else {
      this.modalLocWrap.style.display = "none";
    }

    this.modalDesc.innerHTML = isPrivate
      ? "<em>Ez az esemény privátként lett megjelölve.</em>"
      : event.extendedProps?.body || "<em>Nincs megadva leírás.</em>";

    // Export gomb elrejtése privát eseménynél
    if (this.exportBtn)
      this.exportBtn.style.display = isPrivate ? "none" : "inline-block";

    this.modalOverlay.style.visibility = "visible";
    this.modalOverlay.style.opacity = "1";
  }

  closeModal() {
    this.modalOverlay.style.opacity = "0";
    setTimeout(() => {
      this.modalOverlay.style.visibility = "hidden";
    }, 300);
  }

  downloadICal(event) {
    const formatDate = (date) => {
      if (!date) return "";
      return (
        date
          .toISOString()
          .replace(/-|:|\.\d+/g, "")
          .substring(0, 15) + "Z"
      );
    };

    const start = formatDate(event.start);
    const end = event.end
      ? formatDate(event.end)
      : formatDate(new Date(event.start.getTime() + 60 * 60 * 1000));

    let ical = [
      "BEGIN:VCALENDAR",
      "VERSION:2.0",
      "PRODID:-//O365 Elementor Calendar//HU",
      "CALSCALE:GREGORIAN",
      "METHOD:PUBLISH",
      "BEGIN:VEVENT",
      `UID:${event.id || new Date().getTime()}`,
      `SUMMARY:${event.title}`,
      `DTSTAMP:${formatDate(new Date())}`,
      `DTSTART:${start}`,
    ];

    if (event.allDay) {
      const allDayStart = event.start
        .toISOString()
        .split("T")[0]
        .replace(/-/g, "");
      let allDayEnd = allDayStart;
      if (event.end)
        allDayEnd = event.end.toISOString().split("T")[0].replace(/-/g, "");
      ical[ical.length - 1] = `DTSTART;VALUE=DATE:${allDayStart}`;
      ical.push(`DTEND;VALUE=DATE:${allDayEnd}`);
    } else {
      ical.push(`DTEND:${end}`);
    }

    if (event.extendedProps?.location) {
      ical.push(`LOCATION:${event.extendedProps.location}`);
    }

    if (event.extendedProps?.body) {
      const tmp = document.createElement("DIV");
      tmp.innerHTML = event.extendedProps.body;
      let text = tmp.textContent || tmp.innerText || "";
      text = text.replace(/(\r\n|\n|\r)/gm, "\\n");
      ical.push(`DESCRIPTION:${text}`);
    }

    ical.push("END:VEVENT");
    ical.push("END:VCALENDAR");

    const blob = new Blob([ical.join("\r\n")], {
      type: "text/calendar;charset=utf-8",
    });
    const link = document.createElement("a");
    link.href = window.URL.createObjectURL(blob);

    const safeTitle = event.title.replace(/[^a-zA-Z0-9]/g, "_").toLowerCase();
    link.setAttribute("download", `${safeTitle}.ics`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  debounce(func, wait) {
    let timeout;
    return function (...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }
}

class O365AgendaWidget {
  constructor($scope) {
    this.container = $scope.find(".o365-agenda-container")[0];
    if (!this.container) return;

    this.calendarId = this.container.dataset.calendarId;
    this.limit = parseInt(this.container.dataset.limit) || 5;
    this.catFilter = this.container.dataset.categoryFilter || "";

    if (!this.calendarId) {
      this.container.innerHTML =
        '<div class="o365-empty">Válassz legalább egy naptárat a beállításokban!</div>';
      return;
    }

    this.config = {
      date: this.container.dataset.showDate === "yes",
      time: this.container.dataset.showTime === "yes",
      loc: this.container.dataset.showLoc === "yes",
      desc: this.container.dataset.showDesc === "yes",
      export: this.container.dataset.showExport === "yes",
    };

    this.loadAgenda();
  }

  loadAgenda() {
    const start = new Date().toISOString();
    const end = new Date(
      new Date().getTime() + 60 * 24 * 60 * 60 * 1000,
    ).toISOString();

    // Frissített URL email paraméter nélkül, mert a calendarId-ben benne van (email|id formában)
    const url = `/wp-json/o365cal/v1/events?calendar_id=${encodeURIComponent(
      this.calendarId,
    )}&start=${start}&end=${end}&category_filter=${encodeURIComponent(
      this.catFilter,
    )}`;

    fetch(url)
      .then((res) => res.json())
      .then((events) => {
        this.renderAgenda(events.slice(0, this.limit));
      })
      .catch(() => {
        this.container.innerHTML =
          '<div class="o365-error">Hiba a betöltéskor. Ellenőrizd a naptár párosítást!</div>';
      });
  }

  renderAgenda(events) {
    if (!events || !events.length) {
      this.container.innerHTML =
        '<div class="o365-empty">Nincsenek közelgő események.</div>';
      return;
    }

    let html = '<div class="o365-agenda-list">';
    events.forEach((event, idx) => {
      const startDate = new Date(event.start);
      const dateStr = startDate.toLocaleDateString("hu-HU", {
        month: "short",
        day: "numeric",
      });
      const timeStr = startDate.toLocaleTimeString("hu-HU", {
        hour: "2-digit",
        minute: "2-digit",
      });

      html += `
        <div class="o365-agenda-item">
          <div class="agenda-meta">
            ${
              this.config.date
                ? `<span class="agenda-date">${dateStr}</span>`
                : ""
            }
            ${
              this.config.time
                ? `<span class="agenda-time">${timeStr}</span>`
                : ""
            }
          </div>
          <div class="agenda-content">
            <div class="agenda-title">${event.title}</div>
            ${
              this.config.loc && event.extendedProps.location
                ? `<div class="agenda-loc">📍 ${event.extendedProps.location}</div>`
                : ""
            }
            ${
              this.config.desc && event.extendedProps.body
                ? `<div class="agenda-desc">${event.extendedProps.body}</div>`
                : ""
            }
          </div>
          ${
            this.config.export
              ? `
            <div class="agenda-actions">
              <button class="o365-agenda-export" title="Letöltés naptárba" data-idx="${idx}">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
              </button>
            </div>
          `
              : ""
          }
        </div>
      `;
    });
    html += "</div>";
    this.container.innerHTML = html;

    // Export gombok bekötése az eseményekhez
    this.container.querySelectorAll(".o365-agenda-export").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const index = e.currentTarget.dataset.idx;
        // Dummy widget példány az export funkció eléréséhez
        const exporter = new O365CalendarWidget(jQuery(this.container));
        exporter.downloadICal(events[index]);
      });
    });
  }
}

// Inicializálás az Elementorban
jQuery(window).on("elementor/frontend/init", () => {
  elementorFrontend.hooks.addAction(
    "frontend/element_ready/o365_agenda.default",
    ($scope) => {
      new O365AgendaWidget($scope);
    },
  );
});

jQuery(window).on("elementor/frontend/init", () => {
  elementorFrontend.hooks.addAction(
    "frontend/element_ready/o365_calendar.default",
    function ($scope) {
      new O365CalendarWidget($scope);
    },
  );
});
