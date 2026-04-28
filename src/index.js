import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";
import allLocales from "@fullcalendar/core/locales-all";
import "./style.scss";

// KÖZÖS HELPER: Fordítások (i18n)
function getTrans(locale, key) {
  const lang = locale.split("-")[0];
  const dict = {
    hu: {
      allday: "Egész napos",
      join: "Csatlakozás",
      link: "Megnyitás",
      ongoing: "Folyamatban",
    },
    en: {
      allday: "All day",
      join: "Join",
      link: "Open link",
      ongoing: "In progress",
    },
    de: {
      allday: "Ganztägig",
      join: "Teilnehmen",
      link: "Öffnen",
      ongoing: "Läuft",
    },
  };
  return dict[lang] && dict[lang][key] ? dict[lang][key] : dict["en"][key];
}

// KÖZÖS HELPER: Link kereső
function getMeetingLink(text) {
  if (!text) return null;
  const urlRegex = /(https?:\/\/[^\s"']+)/g;
  const matches = text.match(urlRegex);
  if (!matches) return null;
  let link = matches.find(
    (url) =>
      url.includes("teams.microsoft.com") ||
      url.includes("zoom.us") ||
      url.includes("meet.google.com"),
  );
  return link || matches[0];
}

function getLinkIcon(url) {
  if (url.includes("teams.microsoft"))
    return `<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>`;
  if (url.includes("zoom.us") || url.includes("meet.google.com"))
    return `<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>`;
  return `<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>`;
}

// KÖZÖS HELPER: Dátum és Idő formázó (Többnapos és Egésznapos kezelés)
function formatEventDateTime(event, locale) {
  const startDate = new Date(event.start);
  let dateStr = startDate.toLocaleDateString(locale, {
    month: "short",
    day: "numeric",
  });
  let timeStr = "";

  if (event.allDay) {
    timeStr = getTrans(locale, "allday");
    if (event.end) {
      // Graph API az egésznapos end végét a következő nap éjfelére teszi. Visszavonunk 1 másodpercet.
      const endDate = new Date(new Date(event.end).getTime() - 1000);
      if (startDate.toDateString() !== endDate.toDateString()) {
        dateStr += ` - ${endDate.toLocaleDateString(locale, {
          month: "short",
          day: "numeric",
        })}`;
      }
    }
  } else {
    timeStr = startDate.toLocaleTimeString(locale, {
      hour: "2-digit",
      minute: "2-digit",
    });
    if (event.end) {
      const endDate = new Date(event.end);
      if (startDate.toDateString() !== endDate.toDateString()) {
        dateStr += ` - ${endDate.toLocaleDateString(locale, {
          month: "short",
          day: "numeric",
        })}`;
        timeStr += ` - ${endDate.toLocaleTimeString(locale, {
          hour: "2-digit",
          minute: "2-digit",
        })}`;
      } else {
        timeStr += ` - ${endDate.toLocaleTimeString(locale, {
          hour: "2-digit",
          minute: "2-digit",
        })}`;
      }
    }
  }
  return { dateStr, timeStr };
}

// KÖZÖS HELPER: iCal letöltés
function exportToICal(event) {
  const formatDate = (date) =>
    date
      ? date
          .toISOString()
          .replace(/-|:|\.\d+/g, "")
          .substring(0, 15) + "Z"
      : "";
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
    let allDayEnd = event.end
      ? event.end.toISOString().split("T")[0].replace(/-/g, "")
      : allDayStart;
    ical[ical.length - 1] = `DTSTART;VALUE=DATE:${allDayStart}`;
    ical.push(`DTEND;VALUE=DATE:${allDayEnd}`);
  } else {
    ical.push(`DTEND:${end}`);
  }

  if (event.extendedProps?.location)
    ical.push(`LOCATION:${event.extendedProps.location}`);
  if (event.extendedProps?.body) {
    const tmp = document.createElement("DIV");
    tmp.innerHTML = event.extendedProps.body;
    let text = tmp.textContent || tmp.innerText || "";
    text = text.replace(/(\r\n|\n|\r)/gm, "\\n");
    ical.push(`DESCRIPTION:${text}`);
  }

  ical.push("END:VEVENT", "END:VCALENDAR");
  const blob = new Blob([ical.join("\r\n")], {
    type: "text/calendar;charset=utf-8",
  });
  const link = document.createElement("a");
  link.href = window.URL.createObjectURL(blob);
  link.setAttribute(
    "download",
    `${event.title.replace(/[^a-zA-Z0-9]/g, "_").toLowerCase()}.ics`,
  );
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// ==========================================
// 1. FŐ NAPTÁR WIDGET
// ==========================================
class O365CalendarWidget {
  constructor($scope) {
    this.container = $scope.find(".o365-fullcalendar-container")[0];
    if (!this.container) return;

    this.calendarId = this.container.dataset.calendarId;
    this.locale = this.container.dataset.locale || "hu-HU";
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
              <input type="text" class="o365-search-input" placeholder="Keresés...">
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
              <div class="o365-modal-actions" style="margin-top: 25px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 20px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
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
    this.modalActions = wrapper.querySelector(".o365-modal-actions");

    wrapper
      .querySelector(".o365-modal-close")
      .addEventListener("click", () => this.closeModal());
    this.modalOverlay.addEventListener("click", (e) => {
      if (e.target === this.modalOverlay) this.closeModal();
    });

    this.searchInput.addEventListener("input", (e) => {
      this.searchTerm = e.target.value.toLowerCase().trim();
      const cfg = this.getCurrentConfig();
      if (this.searchTerm.length > 0 && this.calendar.view.type !== "listMonth")
        this.calendar.changeView("listMonth");
      else if (
        this.searchTerm.length === 0 &&
        this.calendar.view.type !== cfg.default
      )
        this.calendar.changeView(cfg.default);
      this.calendar.refetchEvents();
    });
  }

  initCalendar() {
    const cfg = this.getCurrentConfig();
    this.calendar = new Calendar(this.container, {
      plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
      initialView: cfg.default,
      displayEventTime: this.displayEventTime,
      locales: allLocales,
      locale: this.locale.split("-")[0],
      timeZone: this.container.dataset.timezone || "local",
      height: "100%",
      expandRows: true,
      slotMinTime: this.slotMin,
      slotMaxTime: this.slotMax,
      validRange: {
        start: this.validStart || null,
        end: this.validEnd || null,
      },
      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: cfg.allowed.length > 1 ? cfg.allowed.join(",") : "",
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
    if (!this.searchTerm && !cfg.allowed.includes(this.calendar.view.type))
      this.calendar.changeView(cfg.default);
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

    const start = event.start.toLocaleTimeString(this.locale, {
      hour: "2-digit",
      minute: "2-digit",
    });
    const timeIcon = `<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: text-bottom; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
    const locIcon = `<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: text-bottom; margin-right: 4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;

    const loc = event.extendedProps.location
      ? `<div class="tooltip-loc">${locIcon} ${event.extendedProps.location}</div>`
      : "";
    tooltip.innerHTML = `<div class="tooltip-title">${
      event.title
    }</div><div class="tooltip-time">${timeIcon} ${
      event.allDay ? getTrans(this.locale, "allday") : start
    }</div>${loc}`;

    const rect = el.getBoundingClientRect();
    let leftPos = rect.left + window.scrollX + rect.width / 2 - 125;
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

    const { dateStr, timeStr } = formatEventDateTime(event, this.locale);
    this.modalTime.textContent = `${dateStr} ${
      timeStr !== getTrans(this.locale, "allday")
        ? "| " + timeStr
        : "| " + getTrans(this.locale, "allday")
    }`;

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

    // Gombok generálása
    let buttonsHtml = "";
    const meetingLink = getMeetingLink(event.extendedProps?.body);
    if (meetingLink && !isPrivate) {
      const btnText =
        meetingLink.includes("teams") ||
        meetingLink.includes("zoom") ||
        meetingLink.includes("meet")
          ? getTrans(this.locale, "join")
          : getTrans(this.locale, "link");
      buttonsHtml += `<a href="${meetingLink}" target="_blank" class="o365-meeting-btn">${getLinkIcon(
        meetingLink,
      )} ${btnText}</a>`;
    }

    if (!isPrivate) {
      buttonsHtml += `
        <button class="o365-export-ical-btn">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 5px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Mentés naptárba
        </button>`;
    }

    this.modalActions.innerHTML = buttonsHtml;
    const exportBtn = this.modalActions.querySelector(".o365-export-ical-btn");
    if (exportBtn)
      exportBtn.addEventListener("click", () => exportToICal(event));

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

// ==========================================
// 2. AGENDA WIDGET
// ==========================================
class O365AgendaWidget {
  constructor($scope) {
    this.container = $scope.find(".o365-agenda-container")[0];
    if (!this.container) return;

    this.calendarId = this.container.dataset.calendarId;
    this.locale = this.container.dataset.locale || "hu-HU";
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
      modal: this.container.dataset.enableModal === "yes",
      grouping: this.container.dataset.grouping || "none",
      loadMore: this.container.dataset.showLoadMore === "yes",
    };

    this.loadMoreText =
      this.container.dataset.loadMoreText || "További események betöltése";
    this.allEvents = [];
    this.currentLimit = this.limit;

    this.initModal();
    this.loadAgenda();
  }

  initModal() {
    if (!this.config.modal) return;
    const modalHtml = `
      <div class="o365-event-modal-overlay agenda-modal" style="opacity:0; visibility:hidden;">
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
              <div class="o365-modal-actions" style="margin-top: 25px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 20px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
              </div>
          </div>
      </div>
    `;
    const wrapper = this.container.closest(".elementor-widget-container");
    if (!wrapper.querySelector(".o365-event-modal-overlay")) {
      wrapper.insertAdjacentHTML("beforeend", modalHtml);
    }
    this.overlay = wrapper.querySelector(".o365-event-modal-overlay");
    this.overlay.querySelector(".o365-modal-close").onclick = () =>
      this.closeModal();
    this.overlay.onclick = (e) => {
      if (e.target === this.overlay) this.closeModal();
    };
  }

  loadAgenda() {
    const start = new Date().toISOString();
    const end = new Date(
      new Date().getTime() + 365 * 24 * 60 * 60 * 1000,
    ).toISOString();
    const url = `/wp-json/o365cal/v1/events?calendar_id=${encodeURIComponent(
      this.calendarId,
    )}&start=${start}&end=${end}&category_filter=${encodeURIComponent(
      this.catFilter,
    )}`;

    fetch(url)
      .then((res) => res.json())
      .then((events) => {
        this.allEvents = events;
        this.renderAgenda();
      })
      .catch(() => {
        this.container.innerHTML =
          '<div class="o365-error">Hiba a betöltéskor.</div>';
      });
  }

  renderAgenda() {
    const listWrapper = this.container.querySelector(
      ".o365-agenda-list-wrapper",
    );
    const footer = this.container.querySelector(".o365-agenda-footer");
    if (!listWrapper || !footer) return;

    const visibleEvents = this.allEvents.slice(0, this.currentLimit);
    if (!visibleEvents || !visibleEvents.length) {
      listWrapper.innerHTML =
        '<div class="o365-empty">Nincsenek közelgő események.</div>';
      return;
    }

    let html = '<div class="o365-agenda-list">';
    let currentGroup = "";

    visibleEvents.forEach((event, idx) => {
      const { dateStr, timeStr } = formatEventDateTime(event, this.locale);

      if (this.config.grouping !== "none") {
        const startDate = new Date(event.start);
        let groupKey =
          this.config.grouping === "month"
            ? `${startDate.getFullYear()}-${startDate.getMonth()}`
            : startDate.toLocaleDateString(this.locale);
        let groupLabel =
          this.config.grouping === "month"
            ? startDate.toLocaleDateString(this.locale, {
                year: "numeric",
                month: "long",
              })
            : startDate.toLocaleDateString(this.locale, {
                month: "long",
                day: "numeric",
                weekday: "long",
              });

        if (groupKey !== currentGroup) {
          html += `<div class="agenda-group-header">${groupLabel}</div>`;
          currentGroup = groupKey;
        }
      }

      let actionsHtml = "";
      const meetingLink = getMeetingLink(event.extendedProps?.body);
      if (meetingLink) {
        actionsHtml += `
            <a href="${meetingLink}" target="_blank" class="o365-agenda-meeting-btn" title="Csatlakozás">
              ${getLinkIcon(meetingLink)}
            </a>
          `;
      }
      if (this.config.export) {
        actionsHtml += `
            <button class="o365-agenda-export" title="Letöltés naptárba" data-idx="${idx}">
              <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </button>
          `;
      }

      html += `
        <div class="o365-agenda-item ${
          this.config.modal ? "is-clickable" : ""
        }" data-idx="${idx}">
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
            actionsHtml
              ? `<div class="agenda-actions" style="display:flex;">${actionsHtml}</div>`
              : ""
          }
        </div>
      `;
    });
    html += "</div>";
    listWrapper.innerHTML = html;

    if (this.config.loadMore && this.allEvents.length > this.currentLimit) {
      footer.innerHTML = `<button class="o365-load-more-btn">${this.loadMoreText}</button>`;
      footer.querySelector(".o365-load-more-btn").onclick = () => {
        this.currentLimit += this.limit;
        this.renderAgenda();
        setTimeout(() => {
          listWrapper.scrollTop = listWrapper.scrollHeight;
        }, 50);
      };
    } else {
      footer.innerHTML = "";
    }

    listWrapper.querySelectorAll(".o365-agenda-item").forEach((item) => {
      item.onclick = (e) => {
        if (
          e.target.closest(".o365-agenda-export") ||
          e.target.closest(".o365-agenda-meeting-btn")
        )
          return;
        if (this.config.modal) {
          this.openModal(visibleEvents[item.dataset.idx]);
        }
      };
    });

    listWrapper.querySelectorAll(".o365-agenda-export").forEach((btn) => {
      btn.addEventListener("click", () =>
        exportToICal(visibleEvents[btn.dataset.idx]),
      );
    });
  }

  openModal(event) {
    const modal = this.overlay.querySelector(".o365-event-modal");
    const isPrivate = event.extendedProps?.isPrivate;
    const lockIcon = isPrivate
      ? `<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 8px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`
      : "";
    modal.querySelector(".o365-modal-title").innerHTML = lockIcon + event.title;

    const { dateStr, timeStr } = formatEventDateTime(event, this.locale);
    modal.querySelector(".meta-item.time span").textContent = `${dateStr} ${
      timeStr !== getTrans(this.locale, "allday")
        ? "| " + timeStr
        : "| " + getTrans(this.locale, "allday")
    }`;

    const locWrap = modal.querySelector(".meta-item.loc");
    if (event.extendedProps.location && !isPrivate) {
      locWrap.querySelector("span").textContent = event.extendedProps.location;
      locWrap.style.display = "flex";
    } else locWrap.style.display = "none";

    modal.querySelector(".o365-modal-desc").innerHTML = isPrivate
      ? "<em>Ez az esemény privátként lett megjelölve.</em>"
      : event.extendedProps?.body || "<em>Nincs leírás.</em>";

    const actionsWrap = modal.querySelector(".o365-modal-actions");
    let buttonsHtml = "";
    const meetingLink = getMeetingLink(event.extendedProps?.body);

    if (meetingLink && !isPrivate) {
      const btnText =
        meetingLink.includes("teams") ||
        meetingLink.includes("zoom") ||
        meetingLink.includes("meet")
          ? getTrans(this.locale, "join")
          : getTrans(this.locale, "link");
      buttonsHtml += `<a href="${meetingLink}" target="_blank" class="o365-meeting-btn">${getLinkIcon(
        meetingLink,
      )} ${btnText}</a>`;
    }

    if (!isPrivate) {
      buttonsHtml += `
        <button class="o365-export-ical-btn o365-agenda-modal-export">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 5px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Mentés naptárba
        </button>`;
    }

    actionsWrap.innerHTML = buttonsHtml;
    const exportBtn = actionsWrap.querySelector(".o365-agenda-modal-export");
    if (exportBtn) exportBtn.onclick = () => exportToICal(event);

    this.overlay.style.visibility = "visible";
    this.overlay.style.opacity = "1";
  }

  closeModal() {
    this.overlay.style.opacity = "0";
    setTimeout(() => {
      this.overlay.style.visibility = "hidden";
    }, 300);
  }
}

// ==========================================
// 3. SINGLE EVENT WIDGET
// ==========================================
class O365SingleEventWidget {
  constructor($scope) {
    this.container = $scope.find(".o365-single-event-container")[0];
    if (!this.container) return;

    this.calendarId = this.container.dataset.calendarId;
    this.locale = this.container.dataset.locale || "hu-HU";

    if (!this.calendarId) {
      this.container.innerHTML =
        '<div class="o365-single-mask">Kérlek válassz naptárat a beállításokban!</div>';
      return;
    }

    this.catFilter = this.container.dataset.categoryFilter || "";
    this.searchKeyword = this.container.dataset.searchKeyword
      ? this.container.dataset.searchKeyword.toLowerCase().trim()
      : "";
    this.searchStrictness =
      this.container.dataset.searchStrictness || "contains";
    this.expiryMode = this.container.dataset.expiryMode;
    this.maskText = this.container.dataset.maskText;

    this.config = {
      loc: this.container.dataset.showLoc === "yes",
      desc: this.container.dataset.showDesc === "yes",
      export: this.container.dataset.showExport === "yes",
      countdown: this.container.dataset.showCountdown === "yes",
    };

    this.countdownInterval = null;
    this.loadSingleEvent();
  }

  loadSingleEvent() {
    const start = new Date().toISOString();
    const end = new Date(
      new Date().getTime() + 365 * 24 * 60 * 60 * 1000,
    ).toISOString();
    const url = `/wp-json/o365cal/v1/events?calendar_id=${encodeURIComponent(
      this.calendarId,
    )}&start=${start}&end=${end}&category_filter=${encodeURIComponent(
      this.catFilter,
    )}`;

    fetch(url)
      .then((res) => res.json())
      .then((events) => {
        if (!events || !events.length) return this.handleEmpty();

        let targetEvent = null;
        if (this.searchKeyword) {
          targetEvent = events.find((e) => {
            const title = e.title ? e.title.toLowerCase().trim() : "";
            if (this.searchStrictness === "exact")
              return title === this.searchKeyword;
            else if (this.searchStrictness === "starts_with")
              return title.startsWith(this.searchKeyword);
            else {
              const locMatch =
                e.extendedProps?.location &&
                e.extendedProps.location
                  .toLowerCase()
                  .includes(this.searchKeyword);
              const descMatch =
                e.extendedProps?.body &&
                e.extendedProps.body.toLowerCase().includes(this.searchKeyword);
              return (
                title.includes(this.searchKeyword) || locMatch || descMatch
              );
            }
          });
        } else targetEvent = events[0];

        if (targetEvent) this.renderEvent(targetEvent);
        else this.handleEmpty();
      })
      .catch((err) => {
        console.error("O365 Single Event Error:", err);
        this.handleEmpty();
      });
  }

  handleEmpty() {
    if (this.expiryMode === "hide") {
      const widgetWrap = this.container.closest(
        ".elementor-widget-o365_single_event",
      );
      if (widgetWrap) widgetWrap.style.display = "none";
    } else {
      this.container.innerHTML = `<div class="o365-single-mask">${this.maskText}</div>`;
    }
  }

  renderEvent(event) {
    const { dateStr, timeStr } = formatEventDateTime(event, this.locale);
    const startDate = new Date(event.start);
    const monthStr = startDate
      .toLocaleDateString(this.locale, { month: "short" })
      .toUpperCase();

    let exportHtml = "";
    const meetingLink = getMeetingLink(event.extendedProps?.body);

    if (meetingLink) {
      exportHtml += `
        <a href="${meetingLink}" target="_blank" class="o365-single-meeting-btn" title="Csatlakozás">
           ${getLinkIcon(meetingLink)}
        </a>`;
    }

    if (this.config.export) {
      exportHtml += `
        <button class="o365-single-export" title="Naptárhoz adás">
           <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        </button>`;
    }

    let locHtml =
      this.config.loc && event.extendedProps?.location
        ? `
        <div class="single-event-loc">
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            ${event.extendedProps.location}
        </div>`
        : "";

    let descHtml = "";
    if (this.config.desc && event.extendedProps?.body) {
      const tmp = document.createElement("DIV");
      tmp.innerHTML = event.extendedProps.body;
      const plainText = tmp.textContent || tmp.innerText || "";
      const preview =
        plainText.length > 100
          ? plainText.substring(0, 100) + "..."
          : plainText;
      descHtml = `<div class="single-event-desc" style="font-size:13px; color:#777; margin-top:8px;">${preview}</div>`;
    }

    let countdownHtml = this.config.countdown
      ? `
        <div class="single-event-countdown" style="display:none;">
            <span class="cd-val" id="cd-days">0</span><span class="cd-label">nap</span>
            <span class="cd-val" id="cd-hours">00</span><span class="cd-label">ó</span>
            <span class="cd-val" id="cd-mins">00</span><span class="cd-label">p</span>
            <span class="cd-val" id="cd-secs">00</span><span class="cd-label">mp</span>
        </div>`
      : "";

    this.container.innerHTML = `
      <div class="o365-single-card">
        <div class="single-event-date-badge">
            <span class="day">${startDate.getDate()}</span>
            <span class="month">${monthStr}</span>
        </div>
        <div class="single-event-details">
          <div class="single-event-meta">${dateStr} | ${timeStr}</div>
          <h3 class="single-event-title">${event.title}</h3>
          ${locHtml}
          ${descHtml}
          ${countdownHtml}
        </div>
        <div style="display:flex;">
          ${exportHtml}
        </div>
      </div>
    `;

    if (this.config.export) {
      this.container
        .querySelector(".o365-single-export")
        .addEventListener("click", () => exportToICal(event));
    }

    if (this.config.countdown) this.startCountdown(startDate, event);
  }

  startCountdown(targetDate, event) {
    if (this.countdownInterval) clearInterval(this.countdownInterval);
    const cdWrap = this.container.querySelector(".single-event-countdown");
    if (!cdWrap) return;

    const update = () => {
      const now = new Date().getTime();
      const distance = targetDate.getTime() - now;

      if (distance < 0) {
        const endDate = event.end
          ? new Date(event.end).getTime()
          : targetDate.getTime() + 60 * 60 * 1000;
        if (now < endDate) {
          cdWrap.innerHTML = `<span class="cd-started">${getTrans(
            this.locale,
            "ongoing",
          )}</span>`;
        } else {
          clearInterval(this.countdownInterval);
          // Ha teljesen véget ért, frissítsük a listát, hogy hozza a következőt
          setTimeout(() => this.loadSingleEvent(), 2000);
          return;
        }
        cdWrap.style.display = "flex";
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor(
        (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60),
      );
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      cdWrap.querySelector("#cd-days").textContent = days;
      cdWrap.querySelector("#cd-hours").textContent =
        hours < 10 ? "0" + hours : hours;
      cdWrap.querySelector("#cd-mins").textContent =
        minutes < 10 ? "0" + minutes : minutes;
      cdWrap.querySelector("#cd-secs").textContent =
        seconds < 10 ? "0" + seconds : seconds;
      cdWrap.style.display = "flex";
    };

    update();
    this.countdownInterval = setInterval(update, 1000);
  }
}

// ==========================================
// 4. ELEMENTOR HOOKOK REGISZTRÁLÁSA
// ==========================================
jQuery(window).on("elementor/frontend/init", () => {
  elementorFrontend.hooks.addAction(
    "frontend/element_ready/o365_calendar.default",
    ($scope) => new O365CalendarWidget($scope),
  );
  elementorFrontend.hooks.addAction(
    "frontend/element_ready/o365_agenda.default",
    ($scope) => new O365AgendaWidget($scope),
  );
  elementorFrontend.hooks.addAction(
    "frontend/element_ready/o365_single_event.default",
    ($scope) => new O365SingleEventWidget($scope),
  );
});
