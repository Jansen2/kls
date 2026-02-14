<?php
require_once __DIR__ . '/config.php';
session_start();
require_once __DIR__ . '/includes/functions.php';
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einchecken im Kundencenter</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="static/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1 data-i18n="header.title">Einchecken im Kundencenter</h1>
                <p class="subtitle" data-i18n="header.subtitle">Wartelisten-Anmeldung</p>
            </div>
        </header>

        <main class="main-content">
            <section id="start-screen" class="screen start-screen">
                <div class="language-select" aria-label="Sprachauswahl">
                    <button type="button" class="lang-btn" data-lang="de">
                        <span class="flag flag-de" aria-hidden="true"></span>
                        <span data-i18n="lang.de">Deutsch</span>
                    </button>
                    <button type="button" class="lang-btn" data-lang="en">
                        <span class="flag flag-en" aria-hidden="true"></span>
                        <span data-i18n="lang.en">English</span>
                    </button>
                    <button type="button" class="lang-btn" data-lang="es">
                        <span class="flag flag-es" aria-hidden="true"></span>
                        <span data-i18n="lang.es">Espanol</span>
                    </button>
                    <button type="button" class="lang-btn" data-lang="fr">
                        <span class="flag flag-fr" aria-hidden="true"></span>
                        <span data-i18n="lang.fr">Francais</span>
                    </button>
                    <button type="button" class="lang-btn" data-lang="it">
                        <span class="flag flag-it" aria-hidden="true"></span>
                        <span data-i18n="lang.it">Italiano</span>
                    </button>
                </div>
                <h2 data-i18n="start.title">Willkommen im Kundencenter</h2>
                <p data-i18n="start.body">Bitte geben Sie im naechsten Schritt Ihre Daten ein, damit wir Ihnen eine Wartennummer zuweisen koennen.</p>
                <p class="privacy-note" data-i18n="start.privacy">Die Verarbeitung erfolgt gemaess den Datenschutzrichtlinien der AOK Niedersachsen.</p>
                <button type="button" class="btn btn-primary" id="start-btn" data-i18n="start.continue">Weiter</button>
            </section>

            <section id="form-screen" class="screen hidden">
                <form id="kls-form" method="POST" action="submit_form.php">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                    <section class="form-section">
                        <h2 class="section-title" data-i18n="section.personal">Persoenliche Daten</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="partnernummer"><span data-i18n="label.partnernummer">Krankenkassenkartennummer</span>: <span class="optional" data-i18n="label.optional">(optional)</span></label>
                                <input type="text" id="partnernummer" name="partnernummer" placeholder="z.B. 123456789" data-i18n-placeholder="placeholder.partnernummer">
                            </div>

                            <div class="form-group">
                                <label for="anrede"><span data-i18n="label.anrede">Anrede</span>: <span class="optional" data-i18n="label.optional">(optional)</span></label>
                                <select id="anrede" name="anrede">
                                    <option value="" data-i18n="option.select">-- Bitte waehlen --</option>
                                    <option value="Herr" data-i18n="option.mr">Herr</option>
                                    <option value="Frau" data-i18n="option.ms">Frau</option>
                                    <option value="Divers" data-i18n="option.diverse">Divers</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="vorname"><span data-i18n="label.vorname">Vorname</span>: <span class="required" data-i18n="label.required">(pflicht)</span></label>
                                <input type="text" id="vorname" name="vorname" placeholder="Max" data-i18n-placeholder="placeholder.vorname" required>
                            </div>

                            <div class="form-group">
                                <label for="nachname"><span data-i18n="label.nachname">Nachname</span>: <span class="required" data-i18n="label.required">(pflicht)</span></label>
                                <input type="text" id="nachname" name="nachname" placeholder="Mustermann" data-i18n-placeholder="placeholder.nachname" required>
                            </div>

                            <div class="form-group">
                                <label for="geburtsdatum"><span data-i18n="label.geburtsdatum">Geburtsdatum</span>: <span class="required" data-i18n="label.required">(pflicht)</span></label>
                                <input type="date" id="geburtsdatum" name="geburtsdatum" required>
                            </div>

                            <div class="form-group">
                                <label for="ansprechpartner"><span data-i18n="label.ansprechpartner">Ansprechpartner/Betreuer</span>: <span class="optional" data-i18n="label.optional">(optional)</span></label>
                                <input type="text" id="ansprechpartner" name="ansprechpartner" placeholder="Name des Betreuers" data-i18n-placeholder="placeholder.ansprechpartner">
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2 class="section-title" data-i18n="section.contact">Kontaktdaten</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="thema"><span data-i18n="label.thema">Thema</span>: <span class="optional" data-i18n="label.optional">(optional)</span></label>
                                <input type="text" id="thema" name="thema" placeholder="Anliegen eingeben" data-i18n-placeholder="placeholder.thema">
                            </div>

                            <div class="form-group">
                                <label for="wunschberater"><span data-i18n="label.wunschberater">Wunschberater</span>: <span class="optional" data-i18n="label.optional">(optional)</span></label>
                                <input type="text" id="wunschberater" name="wunschberater" placeholder="Name oder ID" data-i18n-placeholder="placeholder.wunschberater">
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2 class="section-title" data-i18n="section.appointment">Termindaten</h2>
                        <div class="form-grid">
                            <div class="form-group full-width checkbox-group">
                                <input type="checkbox" id="termin_angefragt" name="termin_angefragt">
                                <label for="termin_angefragt" class="checkbox-label" data-i18n="label.termin_toggle">Ich habe einen Termin angefragt</label>
                            </div>
                        </div>
                        <div id="termin-details" class="form-grid hidden">
                            <div class="form-group">
                                <label for="termin_tag"><span data-i18n="label.termin_tag">Termin Tag</span>: <span class="optional" data-i18n="label.optional">(optional)</span></label>
                                <input type="date" id="termin_tag" name="termin_tag">
                            </div>

                            <div class="form-group">
                                <label for="uhrzeit"><span data-i18n="label.uhrzeit">Uhrzeit</span>: <span class="optional" data-i18n="label.optional">(optional)</span></label>
                                <input type="time" id="uhrzeit" name="uhrzeit">
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2 class="section-title" data-i18n="section.note">Notiz</h2>
                        <div class="form-group full-width">
                            <label for="notiz"><span data-i18n="label.notiz">Notiz</span>: <span class="optional" data-i18n="label.optional">(optional)</span></label>
                            <textarea id="notiz" name="notiz" placeholder="Geben Sie hier zusätzliche Informationen ein..." data-i18n-placeholder="placeholder.notiz" rows="6"></textarea>
                        </div>
                    </section>

                    <section class="form-section button-section">
                        <button type="reset" class="btn btn-danger" id="cancel-btn" data-i18n="button.cancel">Abbrechen</button>
                        <button type="button" class="btn btn-primary" id="submit-btn" data-i18n="button.submit">Absenden</button>
                    </section>
                </form>
            </section>
        </main>
    </div>

    <div id="inactivity-overlay" class="inactivity-overlay hidden">
        <div class="inactivity-card">
            <div class="inactivity-label" data-i18n="timer.label">Inaktivitaet</div>
            <div class="inactivity-value" id="timer-value">30s</div>
            <div class="inactivity-warning" id="timer-warning"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="static/script.js"></script>
</body>
</html>
