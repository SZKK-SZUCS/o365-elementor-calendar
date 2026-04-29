jQuery(document).ready(function ($) {
  const setupModalId = "o365-setup-modal";
  const pickerModalId = "o365-event-picker-modal";

  // --- SETUP WIZARD MODAL (Eredeti, kisebb leírás kiegészítéssel) ---
  if ($(`#${setupModalId}`).length === 0) {
    $("body").append(`
            <div id="${setupModalId}" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,15,15,0.95); z-index:999999; align-items:center; justify-content:center; font-family:sans-serif;">
                <div style="background:#fff; width:550px; border-radius:12px; overflow:hidden; box-shadow:0 30px 90px rgba(0,0,0,0.5);">
                    <div style="background:#f8f9fa; padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                        <strong style="font-size:18px; color:#111;">O365 Naptár Párosítás</strong>
                        <button id="o365-close" style="background:none; border:none; font-size:28px; cursor:pointer; color:#999;">&times;</button>
                    </div>
                    <div style="padding:30px;">
                        <div id="o365-log" style="display:none; padding:12px; border-radius:6px; margin-bottom:20px; font-size:13px; line-height:1.4;"></div>
                        <p style="font-size:13px; color:#666; margin-bottom:20px;">Hitelesítés után az oldal <strong>frissítése (F5) kötelező</strong>, hogy a naptárak megjelenjenek a bal oldali lenyíló listában!</p>
                        
                        <div id="o365-step-1" class="o365-step">
                            <p style="margin-top:0; color:#555;"><strong>1. Lépés:</strong> Naptár tulajdonosának email címe:</p>
                            <input type="email" id="o365-email" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; margin-bottom:15px;" placeholder="pelda@domain.com">
                            <button id="o365-send" style="width:100%; padding:14px; background:#0073aa; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Kód Küldése</button>
                        </div>

                        <div id="o365-step-2" class="o365-step" style="display:none;">
                            <p style="margin-top:0; color:#555;"><strong>2. Lépés:</strong> Írd be a 6 számjegyű kódot:</p>
                            <input type="text" id="o365-code" style="width:100%; padding:15px; border:1px solid #ddd; border-radius:6px; text-align:center; font-size:26px; letter-spacing:6px;" placeholder="000000">
                            <button id="o365-verify" style="width:100%; padding:14px; background:#46b450; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600; margin-top:15px;">Ellenőrzés</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
  }

  // --- ESEMÉNY KERESŐ MODAL (ÚJ) ---
  if ($(`#${pickerModalId}`).length === 0) {
    $("body").append(`
        <div id="${pickerModalId}" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,15,15,0.95); z-index:999999; align-items:center; justify-content:center; font-family:sans-serif;">
            <div style="background:#fff; width:600px; max-height:85vh; display:flex; flex-direction:column; border-radius:12px; overflow:hidden; box-shadow:0 30px 90px rgba(0,0,0,0.5);">
                <div style="background:#f8f9fa; padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                    <strong style="font-size:18px; color:#111;">Esemény Pontos Kiválasztása</strong>
                    <button id="o365-picker-close" style="background:none; border:none; font-size:28px; cursor:pointer; color:#999;">&times;</button>
                </div>
                <div id="o365-picker-content" style="padding:20px; overflow-y:auto; flex-grow:1; background:#fafafa;">
                    <div id="o365-picker-loading" style="text-align:center; padding:30px; color:#666;">
                        <i class="eicon-spinner eicon-animation-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                        Események betöltése a kiválasztott naptárból...
                    </div>
                    <div id="o365-picker-list" style="display:flex; flex-direction:column; gap:10px;"></div>
                </div>
            </div>
        </div>
    `);
  }

  const getNonce = () =>
    typeof o365_editor_globals !== "undefined" ? o365_editor_globals.nonce : "";

  const showLog = (msg, type = "info") => {
    const bg = type === "error" ? "#fcf0f1" : "#f0f6fb";
    const color = type === "error" ? "#d63638" : "#0073aa";
    $("#o365-log").text(msg).css({ background: bg, color: color }).fadeIn();
  };

  // --- SETUP WIZARD ESEMÉNYEK ---
  $(document).on("click", "#o365-trigger-wizard", function () {
    $("#o365-email").val("");
    $("#o365-step-1").show();
    $("#o365-step-2").hide();
    $("#o365-log").hide();
    $("#o365-code").val("");
    $("#o365-send").prop("disabled", false).text("Kód Küldése");
    $("#o365-verify").prop("disabled", false).text("Ellenőrzés");
    $(`#${setupModalId}`).css("display", "flex");
  });

  $("#o365-close").on("click", () => $(`#${setupModalId}`).hide());

  $("#o365-send").on("click", function () {
    const email = $("#o365-email").val();
    if (!email) return showLog("Email cím kötelező!", "error");
    const btn = $(this);
    btn.prop("disabled", true).text("Küldés...");
    showLog("Kód küldése folyamatban...");
    $.ajax({
      url: "/wp-json/o365cal/v1/auth/request-code",
      method: "POST",
      data: { email: email },
      beforeSend: (xhr) => xhr.setRequestHeader("X-WP-Nonce", getNonce()),
    })
      .done((res) => {
        $("#o365-step-1").hide();
        $("#o365-step-2").show();
        showLog(res.message);
      })
      .fail((err) => {
        showLog(err.responseJSON?.message || "Hiba történt.", "error");
        btn.prop("disabled", false).text("Kód Küldése");
      });
  });

  $("#o365-verify").on("click", function () {
    const code = $("#o365-code").val();
    const btn = $(this);
    btn.prop("disabled", true).text("Ellenőrzés...");
    $.ajax({
      url: "/wp-json/o365cal/v1/auth/verify-code",
      method: "POST",
      data: { email: $("#o365-email").val(), code: code },
      beforeSend: (xhr) => xhr.setRequestHeader("X-WP-Nonce", getNonce()),
    })
      .done((res) => {
        showLog("Sikeres hitelesítés! Ablak bezárása...");
        setTimeout(() => {
          $(`#${setupModalId}`).hide();
          alert(
            "O365 Hitelesítés sikeres!\n\nKérlek FRISSÍTS RÁ AZ OLDALRA (F5), hogy a naptárak megjelenjenek a listában!",
          );
          if (elementor && elementor.reloadPreview) elementor.reloadPreview();
        }, 1200);
      })
      .fail((err) => {
        showLog(err.responseJSON?.message || "Hibás kód.", "error");
        btn.prop("disabled", false).text("Ellenőrzés");
      });
  });

  $(document).on("click", "#o365-resync-btn", function (e) {
    e.preventDefault();
    const btn = $(this);
    const originalHtml = btn.html();
    btn
      .html('<i class="eicon-spinner eicon-animation-spin"></i> Töltés...')
      .prop("disabled", true);
    if (elementor && elementor.reloadPreview) {
      elementor.reloadPreview();
      setTimeout(() => btn.html(originalHtml).prop("disabled", false), 2500);
    }
  });

  // --- ESEMÉNY KERESŐ (EVENT PICKER) ESEMÉNYEK ---
  $("#o365-picker-close").on("click", () => $(`#${pickerModalId}`).hide());

  $(document).on("click", "#o365-trigger-event-picker", function () {
    const model = elementor.getPanelView().getCurrentPageView().model;
    const calIds = model.getSetting("calendar_id");

    if (!calIds || calIds.length === 0) {
      alert(
        "Kérlek, előbb válassz ki legalább egy naptárat az Adatforrás fülnél a bal oldalsávban!",
      );
      return;
    }

    $(`#${pickerModalId}`).css("display", "flex");
    $("#o365-picker-loading").show();
    $("#o365-picker-list").empty();

    // Lekérjük a következő 1 év eseményeit
    const start = new Date().toISOString();
    const end = new Date(
      new Date().getTime() + 365 * 24 * 60 * 60 * 1000,
    ).toISOString();
    const url = `/wp-json/o365cal/v1/events?calendar_id=${encodeURIComponent(
      calIds.join(","),
    )}&start=${start}&end=${end}`;

    $.ajax({
      url: url,
      method: "GET",
    })
      .done((events) => {
        $("#o365-picker-loading").hide();

        if (!events || events.length === 0) {
          $("#o365-picker-list").html(
            '<div style="color:#888; text-align:center; padding:20px;">Ebben a naptárban nincsenek közelgő események.</div>',
          );
          return;
        }

        events.forEach((event) => {
          const dateObj = new Date(event.start);
          const dateStr = dateObj.toLocaleDateString("hu-HU", {
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
          });

          const item = $(`
                <div class="o365-picker-item" style="padding:15px; background:#fff; border:1px solid #e0e0e0; border-radius:8px; cursor:pointer; transition:all 0.2s; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-weight:bold; color:#242943; margin-bottom:5px; font-size:14px;">${event.title}</div>
                        <div style="font-size:12px; color:#50adc9;">
                            <i class="eicon-clock-o" style="margin-right:4px;"></i>${dateStr}
                        </div>
                    </div>
                    <button style="background:#50adc9; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:12px; font-weight:bold; flex-shrink:0;">Kiválaszt</button>
                </div>
            `);

          // Hover effekt a listaelemre
          item.hover(
            function () {
              $(this)
                .css("border-color", "#50adc9")
                .css("box-shadow", "0 4px 10px rgba(0,0,0,0.05)");
            },
            function () {
              $(this).css("border-color", "#e0e0e0").css("box-shadow", "none");
            },
          );

          // Kiválasztás kattintásra
          item.on("click", function () {
            // Beállítjuk az Elementor Control értékét (event_id mező)
            model.setSetting("event_id", event.id);
            $(`#${pickerModalId}`).hide();

            // Automatikusan frissítjük a panelt, hogy renderelje a változást
            elementor.getPanelView().getCurrentPageView().render();
          });

          $("#o365-picker-list").append(item);
        });
      })
      .fail(() => {
        $("#o365-picker-loading").html(
          '<div style="color:#d63638; text-align:center;">Hiba történt az események lekérésekor. Győződj meg róla, hogy a naptár hitelesítve van.</div>',
        );
      });
  });
});
