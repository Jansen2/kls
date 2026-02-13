from flask import Flask, render_template, request, jsonify
from datetime import datetime
import socket

app = Flask(__name__)

def get_client_ip():
    """Ermittelt die IP-Adresse des Clients"""
    if request.headers.getlist("X-Forwarded-For"):
        ip = request.headers.getlist("X-Forwarded-For")[0]
    else:
        ip = request.remote_addr
    return ip

def generate_email_html(form_data, client_ip):
    """Generiert eine HTML-E-Mail aus den Formulardaten"""
    
    email_html = f"""
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {{
                font-family: Arial, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }}
            .email-container {{
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border: 1px solid #ddd;
                border-radius: 5px;
                overflow: hidden;
            }}
            .email-header {{
                background-color: #009B50;
                color: #ffffff;
                padding: 20px;
                text-align: center;
            }}
            .email-header h1 {{
                margin: 0;
                font-size: 24px;
            }}
            .email-body {{
                padding: 20px;
            }}
            .section {{
                margin-bottom: 20px;
                border-bottom: 1px solid #e0e0e0;
                padding-bottom: 15px;
            }}
            .section:last-child {{
                border-bottom: none;
            }}
            .section-title {{
                font-weight: bold;
                color: #009B50;
                margin-bottom: 10px;
                font-size: 14px;
            }}
            .form-field {{
                margin-bottom: 8px;
                font-size: 13px;
            }}
            .form-field label {{
                display: inline-block;
                width: 150px;
                font-weight: bold;
                color: #333;
            }}
            .form-field-value {{
                color: #666;
                word-wrap: break-word;
            }}
            .notiz {{
                background-color: #f9f9f9;
                border-left: 4px solid #009B50;
                padding: 10px;
                margin-top: 5px;
                white-space: pre-wrap;
                word-wrap: break-word;
            }}
            .contact-info {{
                background-color: #f0f0f0;
                padding: 10px;
                border-radius: 3px;
                font-size: 12px;
                color: #666;
            }}
            .footer {{
                background-color: #f5f5f5;
                padding: 15px;
                font-size: 11px;
                color: #999;
                text-align: center;
                border-top: 1px solid #ddd;
            }}
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>Einchecken.-Kundencenter</h1>
                <p>AOK Niedersachsen - Wartelisten-Anmeldung</p>
            </div>
            
            <div class="email-body">
                <!-- Persönliche Daten -->
                <div class="section">
                    <div class="section-title">📋 PERSÖNLICHE DATEN</div>
                    <div class="form-field">
                        <label>Krankenkassenkartennummer:</label>
                        <span class="form-field-value">{form_data.get('partnernummer', 'N/A')}</span>
                    </div>
                    <div class="form-field">
                        <label>Anrede:</label>
                        <span class="form-field-value">{form_data.get('anrede', 'N/A')}</span>
                    </div>
                    <div class="form-field">
                        <label>Vorname:</label>
                        <span class="form-field-value">{form_data.get('vorname', 'N/A')}</span>
                    </div>
                    <div class="form-field">
                        <label>Nachname:</label>
                        <span class="form-field-value">{form_data.get('nachname', 'N/A')}</span>
                    </div>
                    <div class="form-field">
                        <label>Geburtsdatum:</label>
                        <span class="form-field-value">{form_data.get('geburtsdatum', 'N/A')}</span>
                    </div>
                    <div class="form-field">
                        <label>Ansprechpartner/Betreuer:</label>
                        <span class="form-field-value">{form_data.get('ansprechpartner', 'N/A')}</span>
                    </div>
                </div>
                
                <!-- Notiz -->
                <div class="section">
                    <div class="section-title">📝 NOTIZ</div>
                    <div class="notiz">{form_data.get('notiz', 'Keine Notiz')}</div>
                </div>
                
                <!-- Kontaktdaten -->
                <div class="section">
                    <div class="section-title">📞 KONTAKTDATEN</div>
                    <div class="form-field">
                        <label>Thema:</label>
                        <span class="form-field-value">{form_data.get('thema', 'N/A')}</span>
                    </div>
                    <div class="form-field">
                        <label>Wunschberater:</label>
                        <span class="form-field-value">{form_data.get('wunschberater', 'N/A')}</span>
                    </div>
                </div>
                
                <!-- Termindaten -->
                <div class="section">
                    <div class="section-title">📅 TERMINDATEN</div>
                    <div class="form-field">
                        <label>Termin Tag:</label>
                        <span class="form-field-value">{form_data.get('termin_tag', 'N/A')}</span>
                    </div>
                    <div class="form-field">
                        <label>Uhrzeit:</label>
                        <span class="form-field-value">{form_data.get('uhrzeit', 'N/A')}</span>
                    </div>
                </div>
                
                <!-- Metadaten -->
                <div class="section">
                    <div class="section-title">ℹ️ ZUSÄTZLICHE INFORMATIONEN</div>
                    <div class="contact-info">
                        <div><strong>Absender IP-Adresse:</strong> {client_ip}</div>
                        <div><strong>Zeitstempel:</strong> {datetime.now().strftime('%d.%m.%Y %H:%M:%S')}</div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>Diese E-Mail wurde automatisch generiert von der AOK Niedersachsen Empfangsanwendung.</p>
                <p>© AOK Niedersachsen - Alle Rechte vorbehalten</p>
            </div>
        </div>
    </body>
    </html>
    """
    return email_html

@app.route('/')
def index():
    """Startet die Anwendung - zeigt das Formular"""
    return render_template('index.html')

@app.route('/submit_form', methods=['POST'])
def submit_form():
    """Verarbeitet das Formular und generiert die E-Mail"""
    
    # Formulardaten extrahieren
    form_data = {
        'partnernummer': request.form.get('partnernummer', ''),
        'anrede': request.form.get('anrede', ''),
        'vorname': request.form.get('vorname', ''),
        'nachname': request.form.get('nachname', ''),
        'geburtsdatum': request.form.get('geburtsdatum', ''),
        'ansprechpartner': request.form.get('ansprechpartner', ''),
        'notiz': request.form.get('notiz', ''),
        'thema': request.form.get('thema', ''),
        'wunschberater': request.form.get('wunschberater', ''),
        'termin_tag': request.form.get('termin_tag', ''),
        'uhrzeit': request.form.get('uhrzeit', ''),
    }
    
    # Client-IP ermitteln
    client_ip = get_client_ip()
    
    # HTML-E-Mail generieren
    email_html = generate_email_html(form_data, client_ip)
    
    # Zurückgeben als JSON mit der HTML-E-Mail
    return jsonify({
        'success': True,
        'message': 'Formular erfolgreich verarbeitet',
        'email_html': email_html,
        'client_ip': client_ip,
        'timestamp': datetime.now().strftime('%d.%m.%Y %H:%M:%S')
    })

@app.route('/reset_form', methods=['POST'])
def reset_form():
    """Setzt das Formular zurück"""
    return jsonify({'success': True, 'message': 'Formular zurückgesetzt'})

if __name__ == '__main__':
    app.run(debug=True, host='127.0.0.1', port=5000)
