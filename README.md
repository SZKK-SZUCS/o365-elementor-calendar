# O365 Elementor Calendar

Egy professzionális WordPress bővítmény, amely zökkenőmentesen integrálja a Microsoft Office 365 naptárakat az Elementor oldalépítővel. Három teljesen testreszabható, reszponzív widgetet biztosít, amelyekkel könnyedén megjelenítheted az O365 eseményeket a weboldaladon.

## Főbb funkciók

- **Három Elementor Widget:**
  - **O365 Naptár (Full):** Részletes naptár nézet (havi, heti, napi, lista) a FullCalendar motor hajtásával.
  - **O365 Agenda (Lista):** Közelgő események letisztult, görgethető és csoportosítható listája.
  - **O365 Kiemelt Esemény:** Egyedi kártya a legközelebbi, vagy kulcsszó alapján keresett eseményhez, beépített visszaszámlálóval.
- **Online Meeting Támogatás:** Automatikus Microsoft Teams, Zoom és Google Meet link felismerés, beépített "Csatlakozás" gombbal.
- **iCal Export:** Egygombos esemény letöltés és naptárba mentés (`.ics` fájl) a látogatóknak.
- **Privát Események Maszkolása:** Opcionálisan elrejthetők a privát események részletei (pl. csak "Foglalt"-ként jelennek meg).
- **Teljes Testreszabhatóság:** Az Elementor Stílus fülén keresztül minden szín, tipográfia, térköz, árnyék és gomb hover-effekt állítható.
- **Reszponzív Nézetek:** A naptár widgetnél asztali, tablet és mobil nézetre is külön megadhatók az alapértelmezett elrendezések.
- **Automatikus Frissítés:** Integrált Plugin Update Checker a zökkenőmentes jövőbeli frissítésekhez.

---

## Használati útmutató

### 1. Adatforrás és Setup

Minden widget rendelkezik egy **"Adatforrás & Setup"** szekcióval az Elementor szerkesztőjében.

- Itt kell megadnod az O365 naptár(ak) azonosítóját (Calendar ID).
- Kategória alapján is szűrhetsz, ha csak bizonyos típusú eseményeket szeretnél megjeleníteni.

### 2. Widgetek beállítása

#### O365 Naptár (Full)

- **Naptár beállítások:** Beállítható a napi kezdés és befejezés ideje, az időpontok láthatósága, valamint a privát események kezelése (Megjelenítés / Maszkolás / Elrejtés).
- **Nézetek (Reszponzív):** Külön engedélyezheted és állíthatod be az alapértelmezett nézeteket (Havi, Heti, Lista) asztali, tablet és mobil eszközökre.
- **Stílus:** Minden rácsvonal, mai nap kiemelés, gomb és tooltip színe testreszabható. O365 kategória színek natív támogatása.

#### O365 Agenda

- **Tartalom:** Beállítható a megjelenítendő események maximális száma, és a "További események betöltése" gomb.
- **Csoportosítás:** Az események napok vagy hónapok szerint is csoportosíthatók a listában.

#### O365 Kiemelt Esemény

- **Kiválasztás módja:** Dönthetsz úgy, hogy a legközelebbi eseményt mutassa, VAGY egy konkrét kulcsszóra (pl. "Szülői értekezlet") keressen a naptárban.
- **Megjelenítés:** Kapcsolható a helyszín, leírás, mentés gomb és a dinamikus visszaszámláló.
- **Lejárat:** Beállíthatod, mi történjen, ha nincs esemény (a widget elrejtése, vagy egyedi maszk-szöveg megjelenítése).

### 3. Modál ablakok

A listában és a naptárban az eseményekre kattintva egy letisztult modál (popup) ablak nyílik meg, amely tartalmazza:

- A pontos dátumot és időpontot.
- A helyszínt (ikonnal).
- A leírást.
- Exportálás naptárba gombot.
- Online csatlakozás gombot (ha Teams/Zoom/Meet linket észlel).

---

## Gyakori Kérdések (FAQ)

**Miért nem jelennek meg az események a weboldalon?**
Ellenőrizd az Elementor szerkesztőben, hogy az adott widget "Adatforrás & Setup" fülénél kiválasztottál-e legalább egy naptárat. Győződj meg arról is, hogy a Graph API hitelesítés sikeres volt (ezt a Setup varázsló gombja felett láthatod).

**Hogyan ismeri fel a naptár az online meeting (Teams/Zoom/Meet) linkeket?**
A bővítmény algoritmusa automatikusan átvizsgálja az esemény leírását. Ha Microsoft Teams, Zoom vagy Google Meet formátumú URL-t talál benne, automatikusan generál hozzá egy "Csatlakozás" gombot a felugró ablakban vagy az agenda listában.

**Mit jelent a "Privát események maszkolása"?**
Ha az Office 365 naptáradban egy eseményt privátnak (Private) jelölsz, a bővítmény ezt érzékeli. Maszkolás bekapcsolása esetén a naptárban az esemény időpontja továbbra is látszik (lefoglalja a helyet), de az eredeti címe, helyszíne és leírása rejtve marad, és helyette egy általad megadott szó (pl. "Foglalt") jelenik meg.

**Testreszabhatom a naptár vagy a lista színeit?**
Igen, mindhárom widget rendelkezik egy teljes körű Elementor "Stílus" füllel, ahol az összes rácsvonal, betűszín, háttérszín, lekerekítés, gomb hover és árnyék beállítható. Továbbá az O365 naptár kategóriaszíneit (színkódokat) is képes natívan átvenni a naptár widget, ha ez az opció be van kapcsolva.

**Megjeleníthetem csak egy bizonyos típusú (pl. "Oktatás") eseményeimet?**
Igen. Ha az O365 naptáradban kategóriákat használsz az eseményekhez, az Elementor widget "Adatforrás & Setup" szekciójában kiválaszthatod a "Kategória szűrés" legördülőből, hogy csak a kiválasztott kategóriájú események töltődjenek be.

---

## Fejlesztőknek (Tech Stack)

- **Frontend:** Vanilla JS, FullCalendar v6 (@fullcalendar/core, daygrid, timegrid, list)
- **Stílus:** SCSS / BEM architektúra (fordítás `@wordpress/scripts` csomaggal).
- **Backend:** PHP 7.4+, Elementor Widget API, WordPress REST API (`/wp-json/o365cal/v1/events`).
- **Függőségek (Composer):** `yahnis-elsts/plugin-update-checker` a GitHub-alapú frissítésekhez.

---

## Changelog (Verziótörténet)

Minden fontos változtatás ebben a szekcióban kerül dokumentálásra. A formátum a Keep a Changelog szabványt követi.

### [1.0.0] - 2026-04-28

#### Hozzáadva

- **Initial Release (Első kiadás)**
- O365 Naptár (FullCalendar) widget integráció.
- Reszponzív nézetek támogatása a naptárnál (Desktop/Tablet/Mobile külön nézet beállítások).
- Agenda (lista) widget csoportosítási és "Load More" (Továbbiak betöltése) funkcióval.
- Kiemelt esemény (Single Event) widget dinamikus visszaszámlálóval és kulcsszavas kereséssel.
- Esemény részletek modál ablak (Popup).
- Automatikus Teams, Zoom és Google Meet link kinyerés és "Csatlakozás" gomb generálás.
- Esemény naptárba mentése funkció (.ics fájl generálás kliens oldalon).
- Üres állapotok (Empty states) és privát események (maszkolás) elegáns kezelése.
- Teljeskörű Elementor Stílus fül támogatás minden widgetnél.
- Bővítmény frissítés ellenőrző (Plugin Update Checker) integrálása.

---

## Licenc

Ezt a bővítményt belső felhasználásra és egyedi projektekhez fejlesztették. Minden jog fenntartva.
