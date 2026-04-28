jQuery(document).ready(function ($) {
  const modalId = "o365-setup-modal";

  if ($(`#${modalId}`).length === 0) {
    $("body").append(`
            <div id="${modalId}" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,15,15,0.95); z-index:999999; align-items:center; justify-content:center; font-family:sans-serif;">
                <div style="background:#fff; width:550px; border-radius:12px; overflow:hidden; box-shadow:0 30px 90px rgba(0,0,0,0.5);">
                    <div style="background:#f8f9fa; padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                        <strong style="font-size:18px; color:#111;">O365 Naptár Párosítás</strong>
                        <button id="o365-close" style="background:none; border:none; font-size:28px; cursor:pointer; color:#999;">&times;</button>
                    </div>
                    <div style="padding:30px;">
                        <div id="o365-log" style="display:none; padding:12px; border-radius:6px; margin-bottom:20px; font-size:13px; line-height:1.4;"></div>

                        <div id="o365-step-1" class="o365-step">
                            <p style="margin-top:0; color:#555;"><strong>1. Lépés:</strong> Naptár tulajdonosának email címe:</p>
                            <input type="email" id="o365-email" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; margin-bottom:15px;" placeholder="pelda@domain.com">
                            <button id="o365-send" style="width:100%; padding:14px; background:#0073aa; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Kód Küldése</button>
                        </div>

                        <div id="o365-step-2" class="o365-step" style="display:none;">
                            <p style="margin-top:0; color:#555;"><strong>2. Lépés:</strong> Írd be a 6 számjegyű kódot (emailben érkezik):</p>
                            <input type="text" id="o365-code" style="width:100%; padding:15px; border:1px solid #ddd; border-radius:6px; text-align:center; font-size:26px; letter-spacing:6px;" placeholder="000000">
                            <button id="o365-verify" style="width:100%; padding:14px; background:#46b450; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600; margin-top:15px;">Ellenőrzés</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
  }

  // JAVÍTVA A VÁLTOZÓ NÉV
  const getNonce = () =>
    typeof o365_editor_globals !== "undefined" ? o365_editor_globals.nonce : "";

  const showLog = (msg, type = "info") => {
    const bg = type === "error" ? "#fcf0f1" : "#f0f6fb";
    const color = type === "error" ? "#d63638" : "#0073aa";
    $("#o365-log").text(msg).css({ background: bg, color: color }).fadeIn();
  };

  $(document).on("click", "#o365-trigger-wizard", function () {
    // Alaphelyzetbe állítjuk a modalt
    $("#o365-email").val("");
    $("#o365-step-1").show();
    $("#o365-step-2").hide();
    $("#o365-log").hide();
    $("#o365-code").val("");
    $("#o365-send").prop("disabled", false).text("Kód Küldése");
    $("#o365-verify").prop("disabled", false).text("Ellenőrzés");

    $("#o365-setup-modal").css("display", "flex");
  });

  $("#o365-close").on("click", () => $("#o365-setup-modal").hide());

  // 1. Lépés: Kód küldése
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
      beforeSend: (xhr) => xhr.setRequestHeader("X-WP-Nonce", getNonce()), // Nonce bekötve!
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

  // 2. Lépés: Hitelesítés és biztonságos újratöltés
  $("#o365-verify").on("click", function () {
    const code = $("#o365-code").val();
    const btn = $(this);
    btn.prop("disabled", true).text("Ellenőrzés...");

    $.ajax({
      url: "/wp-json/o365cal/v1/auth/verify-code",
      method: "POST",
      data: { email: $("#o365-email").val(), code: code },
      beforeSend: (xhr) => xhr.setRequestHeader("X-WP-Nonce", getNonce()), // Nonce bekötve!
    })
      .done((res) => {
        showLog("Sikeres hitelesítés! Ablak bezárása...");

        setTimeout(() => {
          $("#o365-setup-modal").hide();

          const successMsg =
            "O365 Hitelesítés sikeres! Kérlek FRISSÍTS RÁ AZ OLDALRA (F5), hogy a naptár-választó listában megjelenjenek az új naptárak!";
          if (typeof elementor !== "undefined" && elementor.notifications) {
            elementor.notifications.showToast({ message: successMsg });
          } else {
            alert(successMsg);
          }

          if (elementor && elementor.reloadPreview) {
            elementor.reloadPreview();
          }
        }, 1200);
      })
      .fail((err) => {
        showLog(err.responseJSON?.message || "Hibás kód.", "error");
        btn.prop("disabled", false).text("Ellenőrzés");
      });
  });

  // --- Resync Gomb Logika ---
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
});
