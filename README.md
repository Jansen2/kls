# AOK Niedersachsen - KLS Empfang Webanwendung

Eine Python Flask-Webanwendung für die digitale Warteschlangen-Verwaltung in den Servicecentern der AOK Niedersachsen.

## Features

- 📋 **Wartelisten-Formular**: Erfassung von Besucherdaten (Versicherte und Nicht-Versicherte)
- 📧 **E-Mail-Generierung**: Automatische HTML-E-Mail-Erstellung mit allen Formulardaten
- 🔍 **IP-Tracking**: Erfassung der Absender-IP-Adresse
- 🯁 **Responsive Design**: Optimiert für Desktop, Tablet und Mobile
- 🆕 **Moderne UI**: Benutzerfreundliche Oberfläche basierend auf AOK-Design
- 📄 **Preview-Modus**: E-Mails werden vor dem Versenden angezeigt (aktuell noch nicht versendet)

## Installation

### Voraussetzungen
- Python 3.8 oder höher
- pip (Python Package Manager)

### Schritt-für-Schritt

1. **Repository klonen / Projektordner öffnen**
   ```bash
   cd kls-empfang
   ```

2. **Virtuelle Umgebung erstellen (empfohlen)**
   ```bash
   python -m venv venv
   ```

3. **Umgebung aktivieren**
   - Windows:
     ```bash
     venv\Scripts\activate
     ```
   - macOS/Linux:
     ```bash
     source venv/bin/activate
     ```

4. **Dependencies installieren**
   ```bash
   pip install -r requirements.txt
   ```

## Verwendung

### Starten der Anwendung

```bash
python app.py
```

Die Anwendung läuft dann unter `http://127.0.0.1:5000`

### Bedienung

1. Öffnen Sie die Anwendung im Browser (http://localhost:5000)
2. Füllen Sie das Formular mit den Besucherdaten aus:
   - **Persönliche Daten**: Partnernummer, Anrede, Vor- und Nachname, Geburtsdatum, Ansprechpartner
   - **Notiz**: Zusätzliche Informationen
   - **Kontaktdaten**: Thema, Wunschberater ID, Priorität
   - **Termindaten**: Termin und Uhrzeit
3. Klicken Sie auf **Absenden**
4. Eine HTML-E-Mail-Vorschau wird angezeigt
5. Sie können die E-Mail drucken oder schließen

## Formulardaten

Das Formular erfasst folgende Informationen:

### Persönliche Daten
- Partnernummer
- Anrede (Herr/Frau/Divers)
- Vorname
- Nachname
- Geburtsdatum
- Ansprechpartner/Betreuer

### Notiz
- Freitext-Feld für zusätzliche Informationen

### Kontaktdaten
- Thema
- Wunschberater ID
- Priorität (Checkbox)

### Termindaten
- Termin Tag
- Uhrzeit

## E-Mail-Funktion

Die generierte E-Mail enthält:
- Alle Formulardaten übersichtlich formatiert
- IP-Adresse des Absenders
- Zeitstempel der Eingabe
- Professionelles HTML-Design mit AOK-Branding

### Aktueller Stand
- ✓ E-Mail wird als HTML generiert und angezeigt
- ✓ IP-Adresse wird erfasst
- ⏳ E-Mail-Versand wird in zukünftiger Version implementiert

## Projektstruktur

```
kls/
├── app.py                 # Hauptanwendung (Flask)
├── requirements.txt       # Python-Dependencies
├── templates/
│   └── index.html        # HTML-Formular
├── static/
│   ├── style.css         # CSS-Styling
│   └── script.js         # JavaScript-Funktionalität
└── README.md             # Diese Datei
```

## Technologie-Stack

- **Backend**: Flask (Python Web Framework)
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Server**: Werkzeug WSGI
- **Design**: Responsive, Mobile-First

## Konfiguration

Die Anwendung läuft standardmäßig auf:
- **Host**: 127.0.0.1 (localhost)
- **Port**: 5000
- **Debug-Modus**: Ein (für Entwicklung)

Diese Einstellungen können in `app.py` angepasst werden:

```python
if __name__ == '__main__':
    app.run(debug=True, host='127.0.0.1', port=5000)
```

## Zukünftige Features

- 📧 E-Mail-Versand via SMTP
- 📎 Anlagen (Datei-Upload)
- ⚡ Schnellerfassung-Modus
- 💾 Datenspeicherung in Datenbank
- 🔐 Benutzerverwaltung und Authentifizierung
- 📊 Statistiken und Auswertungen
- 🔔 Benachrichtigungen

## Fehlerbehandlung

Fehler werden dem Benutzer durch Benachrichtigungen angezeigt. Serverseite-Fehler können in der Browser-Konsole (F12) angesehen werden.

## Browser-Kompatibilität

- Chrome/Chromium (aktuell)
- Firefox (aktuell)
- Safari (aktuell)
- Edge (aktuell)

## Support & Kontakt

Für Fragen oder Probleme bitte kontaktieren Sie den IT-Support der AOK Niedersachsen.

## Lizenz

© AOK Niedersachsen - Alle Rechte vorbehalten

## Entwickler-Hinweise

### Debug-Modus
Die Anwendung läuft im Debug-Modus, was Auto-Reload bei Dateiänderungen ermöglicht. Dies sollte für die Produktion deaktiviert werden.

### Logging
Alle Anfragen werden in der Konsole geloggt.

### CORS / Sicherheit
Für die Produktionsumgebung sollten folgende Maßnahmen durchgeführt werden:
- SSL/TLS aktivieren (HTTPS)
- Debug-Modus deaktivieren
- CORS-Richtlinien setzen
- Input-Validierung erweitern
