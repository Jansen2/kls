// DOM-Elemente
const form = document.getElementById('kls-form');
const submitBtn = document.getElementById('submit-btn');
const cancelBtn = document.getElementById('cancel-btn');
const timeInput = document.getElementById('uhrzeit');
const startScreen = document.getElementById('start-screen');
const formScreen = document.getElementById('form-screen');
const startBtn = document.getElementById('start-btn');
const inactivityOverlay = document.getElementById('inactivity-overlay');
const timerValue = document.getElementById('timer-value');
const timerWarning = document.getElementById('timer-warning');
const terminToggle = document.getElementById('termin_angefragt');
const terminDetails = document.getElementById('termin-details');
const languageButtons = document.querySelectorAll('.lang-btn');

// Inaktivitäts-Timer Variablen
let inactivityTimer = null;
let countdownTimer = null;
const INACTIVITY_TIMEOUT = 30000; // 30 Sekunden in Millisekunden
let remainingTime = 30; // Verbleibende Zeit in Sekunden
let timerStarted = false; // Flag: Timer bereits gestartet?
let isFormActive = false;
let currentLang = 'de';

const translations = {
    de: {
        'lang.de': 'Deutsch',
        'lang.en': 'English',
        'lang.es': 'Espanol',
        'lang.fr': 'Francais',
        'lang.it': 'Italiano',
        'header.title': 'Einchecken im Kundencenter',
        'header.subtitle': 'Wartelisten-Anmeldung',
        'start.title': 'Willkommen im Kundencenter',
        'start.body': 'Bitte geben Sie im naechsten Schritt Ihre Daten ein, damit wir Ihnen eine Wartennummer zuweisen koennen.',
        'start.privacy': 'Ihre Angaben werden ausschliesslich zur Bearbeitung Ihres Anliegens verarbeitet. Die Verarbeitung erfolgt gemaess den Datenschutzrichtlinien der AOK Niedersachsen.',
        'start.continue': 'Weiter',
        'section.personal': 'Persoenliche Daten',
        'section.note': 'Notiz',
        'section.contact': 'Kontaktdaten',
        'section.appointment': 'Termindaten',
        'label.partnernummer': 'Krankenkassenkartennummer',
        'label.anrede': 'Anrede',
        'label.vorname': 'Vorname',
        'label.nachname': 'Nachname',
        'label.geburtsdatum': 'Geburtsdatum',
        'label.ansprechpartner': 'Ansprechpartner/Betreuer',
        'label.notiz': 'Notiz',
        'label.thema': 'Thema',
        'label.wunschberater': 'Wunschberater',
        'label.termin_tag': 'Termin Tag',
        'label.uhrzeit': 'Uhrzeit',
        'label.termin_toggle': 'Ich habe einen Termin angefragt',
        'label.optional': '(optional)',
        'label.required': '(pflicht)',
        'option.select': '-- Bitte waehlen --',
        'option.mr': 'Herr',
        'option.ms': 'Frau',
        'option.diverse': 'Divers',
        'button.cancel': 'Abbrechen',
        'button.submit': 'Absenden',
        'timer.label': 'Inaktivitaet',
        'timer.ready': 'Bereit',
        'inactivity.message': 'Kehre in {s}s zur Startseite zurueck.',
        'waiting.title': 'Ihre Wartennummer',
        'waiting.countdown': 'Anzeige schliesst in {s}s',
        'validation.required': 'Bitte fuellen Sie alle erforderlichen Felder aus.',
        'confirm.reset': 'Moechten Sie das Formular wirklich zuruecksetzen?',
        'notification.success': '✓ Formular erfolgreich verarbeitet!',
        'notification.reset': 'Formular wurde zurueckgesetzt.',
        'error.parse': 'Fehler beim Parsen der Serverantwort',
        'error.format': 'Ungueltiges Antwortformat vom Server',
        'error.generic': 'Ein Fehler ist aufgetreten:',
        'placeholder.partnernummer': 'z.B. 123456789',
        'placeholder.vorname': 'Max',
        'placeholder.nachname': 'Mustermann',
        'placeholder.ansprechpartner': 'Name des Betreuers',
        'placeholder.notiz': 'Geben Sie hier zusaetzliche Informationen ein...',
        'placeholder.thema': 'Anliegen eingeben',
        'placeholder.wunschberater': 'Name oder ID'
    },
    en: {
        'lang.de': 'Deutsch',
        'lang.en': 'English',
        'lang.es': 'Espanol',
        'lang.fr': 'Francais',
        'lang.it': 'Italiano',
        'header.title': 'Check in at the Service Center',
        'header.subtitle': 'Waiting List Registration',
        'start.title': 'Welcome to the Service Center',
        'start.body': 'Please enter your data in the next step so we can assign a waiting number.',
        'start.privacy': 'Your information is processed solely to handle your request, in accordance with AOK Niedersachsen data protection guidelines.',
        'start.continue': 'Continue',
        'section.personal': 'Personal Data',
        'section.note': 'Note',
        'section.contact': 'Contact Details',
        'section.appointment': 'Appointment Details',
        'label.partnernummer': 'Insurance Card Number',
        'label.anrede': 'Salutation',
        'label.vorname': 'First Name',
        'label.nachname': 'Last Name',
        'label.geburtsdatum': 'Date of Birth',
        'label.ansprechpartner': 'Contact Person/Caregiver',
        'label.notiz': 'Note',
        'label.thema': 'Topic',
        'label.wunschberater': 'Preferred Advisor',
        'label.termin_tag': 'Appointment Date',
        'label.uhrzeit': 'Time',
        'label.termin_toggle': 'I requested an appointment',
        'label.optional': '(optional)',
        'label.required': '(required)',
        'option.select': '-- Please choose --',
        'option.mr': 'Mr',
        'option.ms': 'Ms',
        'option.diverse': 'Other',
        'button.cancel': 'Cancel',
        'button.submit': 'Submit',
        'timer.label': 'Inactivity',
        'timer.ready': 'Ready',
        'inactivity.message': 'Return to the start page in {s}s.',
        'waiting.title': 'Your Waiting Number',
        'waiting.countdown': 'Closing in {s}s',
        'validation.required': 'Please fill in all required fields.',
        'confirm.reset': 'Do you really want to reset the form?',
        'notification.success': '✓ Form processed successfully!',
        'notification.reset': 'Form has been reset.',
        'error.parse': 'Error parsing server response',
        'error.format': 'Invalid response format from server',
        'error.generic': 'An error occurred:',
        'placeholder.partnernummer': 'e.g. 123456789',
        'placeholder.vorname': 'Max',
        'placeholder.nachname': 'Mustermann',
        'placeholder.ansprechpartner': 'Name of caregiver',
        'placeholder.notiz': 'Add additional information...',
        'placeholder.thema': 'Enter topic',
        'placeholder.wunschberater': 'Name or ID'
    },
    es: {
        'lang.de': 'Deutsch',
        'lang.en': 'English',
        'lang.es': 'Espanol',
        'lang.fr': 'Francais',
        'lang.it': 'Italiano',
        'header.title': 'Registro en el centro de atencion',
        'header.subtitle': 'Registro en lista de espera',
        'start.title': 'Bienvenido al centro de atencion',
        'start.body': 'Por favor, introduzca sus datos en el siguiente paso para asignarle un numero de espera.',
        'start.privacy': 'Sus datos se tratan exclusivamente para gestionar su solicitud, conforme a las directrices de proteccion de datos de AOK Niedersachsen.',
        'start.continue': 'Continuar',
        'section.personal': 'Datos personales',
        'section.note': 'Nota',
        'section.contact': 'Datos de contacto',
        'section.appointment': 'Datos de cita',
        'label.partnernummer': 'Numero de tarjeta sanitaria',
        'label.anrede': 'Tratamiento',
        'label.vorname': 'Nombre',
        'label.nachname': 'Apellido',
        'label.geburtsdatum': 'Fecha de nacimiento',
        'label.ansprechpartner': 'Persona de contacto/cuidador',
        'label.notiz': 'Nota',
        'label.thema': 'Tema',
        'label.wunschberater': 'Asesor preferido',
        'label.termin_tag': 'Fecha de cita',
        'label.uhrzeit': 'Hora',
        'label.termin_toggle': 'He solicitado una cita',
        'label.optional': '(opcional)',
        'label.required': '(obligatorio)',
        'option.select': '-- Seleccione --',
        'option.mr': 'Sr.',
        'option.ms': 'Sra.',
        'option.diverse': 'Otro',
        'button.cancel': 'Cancelar',
        'button.submit': 'Enviar',
        'timer.label': 'Inactividad',
        'timer.ready': 'Listo',
        'inactivity.message': 'Volvera a la pagina de inicio en {s}s.',
        'waiting.title': 'Su numero de espera',
        'waiting.countdown': 'Se cierra en {s}s',
        'validation.required': 'Complete todos los campos obligatorios.',
        'confirm.reset': 'Desea restablecer el formulario?',
        'notification.success': '✓ Formulario procesado correctamente!',
        'notification.reset': 'Formulario restablecido.',
        'error.parse': 'Error al analizar la respuesta del servidor',
        'error.format': 'Formato de respuesta invalido del servidor',
        'error.generic': 'Ha ocurrido un error:',
        'placeholder.partnernummer': 'p. ej. 123456789',
        'placeholder.vorname': 'Max',
        'placeholder.nachname': 'Mustermann',
        'placeholder.ansprechpartner': 'Nombre del cuidador',
        'placeholder.notiz': 'Agregue informacion adicional...',
        'placeholder.thema': 'Ingrese tema',
        'placeholder.wunschberater': 'Nombre o ID'
    },
    fr: {
        'lang.de': 'Deutsch',
        'lang.en': 'English',
        'lang.es': 'Espanol',
        'lang.fr': 'Francais',
        'lang.it': 'Italiano',
        'header.title': 'Enregistrement au centre de service',
        'header.subtitle': 'Inscription sur liste d attente',
        'start.title': 'Bienvenue au centre de service',
        'start.body': 'Veuillez saisir vos donnees a l etape suivante afin que nous puissions attribuer un numero d attente.',
        'start.privacy': 'Vos donnees sont traitees uniquement pour gerer votre demande, conformement aux directives de protection des donnees de AOK Niedersachsen.',
        'start.continue': 'Continuer',
        'section.personal': 'Donnees personnelles',
        'section.note': 'Note',
        'section.contact': 'Coordonnees',
        'section.appointment': 'Details du rendez-vous',
        'label.partnernummer': 'Numero de carte d assurance',
        'label.anrede': 'Civilite',
        'label.vorname': 'Prenom',
        'label.nachname': 'Nom',
        'label.geburtsdatum': 'Date de naissance',
        'label.ansprechpartner': 'Contact/aidant',
        'label.notiz': 'Note',
        'label.thema': 'Sujet',
        'label.wunschberater': 'Conseiller souhaite',
        'label.termin_tag': 'Date du rendez-vous',
        'label.uhrzeit': 'Heure',
        'label.termin_toggle': 'J ai demande un rendez-vous',
        'label.optional': '(optionnel)',
        'label.required': '(obligatoire)',
        'option.select': '-- Veuillez choisir --',
        'option.mr': 'M.',
        'option.ms': 'Mme',
        'option.diverse': 'Autre',
        'button.cancel': 'Annuler',
        'button.submit': 'Envoyer',
        'timer.label': 'Inactivite',
        'timer.ready': 'Pret',
        'inactivity.message': 'Retour a la page de demarrage dans {s}s.',
        'waiting.title': 'Votre numero d attente',
        'waiting.countdown': 'Fermeture dans {s}s',
        'validation.required': 'Veuillez remplir tous les champs obligatoires.',
        'confirm.reset': 'Voulez-vous vraiment reinitialiser le formulaire?',
        'notification.success': '✓ Formulaire traite avec succes!',
        'notification.reset': 'Formulaire reinitialise.',
        'error.parse': 'Erreur lors de l analyse de la reponse du serveur',
        'error.format': 'Format de reponse invalide du serveur',
        'error.generic': 'Une erreur est survenue:',
        'placeholder.partnernummer': 'ex. 123456789',
        'placeholder.vorname': 'Max',
        'placeholder.nachname': 'Mustermann',
        'placeholder.ansprechpartner': 'Nom de l aidant',
        'placeholder.notiz': 'Ajoutez des informations...',
        'placeholder.thema': 'Saisir le sujet',
        'placeholder.wunschberater': 'Nom ou ID'
    },
    it: {
        'lang.de': 'Deutsch',
        'lang.en': 'English',
        'lang.es': 'Espanol',
        'lang.fr': 'Francais',
        'lang.it': 'Italiano',
        'header.title': 'Registrazione al centro servizi',
        'header.subtitle': 'Registrazione lista di attesa',
        'start.title': 'Benvenuto al centro servizi',
        'start.body': 'Inserisca i suoi dati nel prossimo passaggio per assegnarle un numero di attesa.',
        'start.privacy': 'I suoi dati sono trattati esclusivamente per gestire la sua richiesta, in conformita alle linee guida sulla privacy di AOK Niedersachsen.',
        'start.continue': 'Continua',
        'section.personal': 'Dati personali',
        'section.note': 'Nota',
        'section.contact': 'Dati di contatto',
        'section.appointment': 'Dettagli appuntamento',
        'label.partnernummer': 'Numero tessera sanitaria',
        'label.anrede': 'Titolo',
        'label.vorname': 'Nome',
        'label.nachname': 'Cognome',
        'label.geburtsdatum': 'Data di nascita',
        'label.ansprechpartner': 'Referente/caregiver',
        'label.notiz': 'Nota',
        'label.thema': 'Tema',
        'label.wunschberater': 'Consulente preferito',
        'label.termin_tag': 'Data appuntamento',
        'label.uhrzeit': 'Ora',
        'label.termin_toggle': 'Ho richiesto un appuntamento',
        'label.optional': '(opzionale)',
        'label.required': '(obbligatorio)',
        'option.select': '-- Selezionare --',
        'option.mr': 'Sig.',
        'option.ms': 'Sig.ra',
        'option.diverse': 'Altro',
        'button.cancel': 'Annulla',
        'button.submit': 'Invia',
        'timer.label': 'Inattivita',
        'timer.ready': 'Pronto',
        'inactivity.message': 'Torno alla pagina iniziale tra {s}s.',
        'waiting.title': 'Il suo numero di attesa',
        'waiting.countdown': 'Si chiude tra {s}s',
        'validation.required': 'Compilare tutti i campi obbligatori.',
        'confirm.reset': 'Vuole davvero reimpostare il modulo?',
        'notification.success': '✓ Modulo elaborato con successo!',
        'notification.reset': 'Modulo reimpostato.',
        'error.parse': 'Errore durante l analisi della risposta del server',
        'error.format': 'Formato di risposta non valido dal server',
        'error.generic': 'Si e verificato un errore:',
        'placeholder.partnernummer': 'es. 123456789',
        'placeholder.vorname': 'Max',
        'placeholder.nachname': 'Mustermann',
        'placeholder.ansprechpartner': 'Nome del caregiver',
        'placeholder.notiz': 'Aggiungi informazioni...',
        'placeholder.thema': 'Inserisci tema',
        'placeholder.wunschberater': 'Nome o ID'
    }
};

function t(key, vars = {}) {
    const dictionary = translations[currentLang] || translations.de;
    let value = dictionary[key] || translations.de[key] || key;
    Object.entries(vars).forEach(([name, replacement]) => {
        value = value.replace(`{${name}}`, replacement);
    });
    return value;
}

function applyTranslations() {
    document.querySelectorAll('[data-i18n]').forEach((element) => {
        const key = element.getAttribute('data-i18n');
        if (!key) {
            return;
        }
        element.textContent = t(key);
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
        const key = element.getAttribute('data-i18n-placeholder');
        if (!key) {
            return;
        }
        element.setAttribute('placeholder', t(key));
    });

    if (timerStarted) {
        updateTimerDisplay();
    }
}

function setLanguage(lang) {
    currentLang = translations[lang] ? lang : 'de';
    localStorage.setItem('kls-lang', currentLang);
    applyTranslations();
    languageButtons.forEach((button) => {
        button.classList.toggle('active', button.dataset.lang === currentLang);
    });
}

// ===== INAKTIVITÄTS-TIMER FUNKTIONEN =====
function updateTimerDisplay() {
    if (!timerValue || !timerWarning || !inactivityOverlay) {
        return;
    }

    const card = inactivityOverlay.querySelector('.inactivity-card');

    if (isFormActive) {
        if (remainingTime <= 25 && timerStarted) {
            inactivityOverlay.classList.add('show');
            inactivityOverlay.classList.remove('hidden');
        } else {
            inactivityOverlay.classList.remove('show');
            inactivityOverlay.classList.add('hidden');
        }
    } else {
        inactivityOverlay.classList.remove('show');
        inactivityOverlay.classList.add('hidden');
        return;
    }

    if (!timerStarted) {
        timerValue.textContent = t('timer.ready');
        timerValue.classList.remove('warning');
        timerWarning.textContent = '';
        return;
    }

    timerValue.textContent = remainingTime + 's';

    if (remainingTime <= 10) {
        timerValue.classList.add('warning');
        if (card) {
            card.classList.add('warning');
        }
        timerWarning.textContent = t('inactivity.message', { s: remainingTime });
    } else {
        timerValue.classList.remove('warning');
        if (card) {
            card.classList.remove('warning');
        }
        timerWarning.textContent = t('inactivity.message', { s: remainingTime });
    }
}

function startCountdown() {
    // Bestehenden Countdown löschen
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
    
    remainingTime = 30;
    updateTimerDisplay();
    
    countdownTimer = setInterval(() => {
        remainingTime--;
        updateTimerDisplay();
        
        if (remainingTime <= 0) {
            clearInterval(countdownTimer);
        }
    }, 1000);
}

function resetInactivityTimer() {
    if (!isFormActive) {
        return;
    }

    // Timer nur starten, wenn noch nicht gestartet
    if (!timerStarted) {
        timerStarted = true;
    }
    
    // Bestehenden Timer löschen
    if (inactivityTimer) {
        clearTimeout(inactivityTimer);
    }
    
    // Countdown zurücksetzen
    startCountdown();

    // Neuen Timer setzen
    inactivityTimer = setTimeout(() => {
        resetFormOnInactivity();
    }, INACTIVITY_TIMEOUT);
}

function resetFormOnInactivity() {
    // Formular leeren
    form.reset();

    showStartScreen();
}

function stopInactivityTimers() {
    if (inactivityTimer) {
        clearTimeout(inactivityTimer);
        inactivityTimer = null;
    }

    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

function showStartScreen() {
    isFormActive = false;
    timerStarted = false;
    remainingTime = 30;
    stopInactivityTimers();
    updateTimerDisplay();

    if (inactivityOverlay) {
        const card = inactivityOverlay.querySelector('.inactivity-card');
        if (card) {
            card.classList.remove('warning');
        }
    }

    if (formScreen) {
        formScreen.classList.add('hidden');
    }
    if (startScreen) {
        startScreen.classList.remove('hidden');
    }

    setLanguage('de');

    applyTranslations();
}

function showFormScreen() {
    isFormActive = true;
    timerStarted = false;
    remainingTime = 30;
    updateTimerDisplay();

    if (startScreen) {
        startScreen.classList.add('hidden');
    }
    if (formScreen) {
        formScreen.classList.remove('hidden');
    }

    setDefaultDateTime();
    resetInactivityTimer();
    updateTerminDetails();
    applyTranslations();
}

// Wartennummer anzeigen (10 Sekunden)
function showWaitingNumber(waitingNumber) {
    if (!waitingNumber) {
        return;
    }

    let overlay = document.getElementById('waiting-number-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'waiting-number-overlay';
        overlay.innerHTML = `
            <div class="waiting-number-card">
                <div class="waiting-number-title" data-i18n="waiting.title">Ihre Wartennummer</div>
                <div class="waiting-number-value" id="waiting-number-value"></div>
                <div class="waiting-number-countdown" id="waiting-number-countdown"></div>
            </div>
        `;
        document.body.appendChild(overlay);
        applyTranslations();
    }

    const valueEl = overlay.querySelector('#waiting-number-value');
    const countdownEl = overlay.querySelector('#waiting-number-countdown');
    let remaining = 10;

    valueEl.textContent = waitingNumber;
    countdownEl.textContent = t('waiting.countdown', { s: remaining });
    overlay.classList.add('show');

    isFormActive = false;
    stopInactivityTimers();
    updateTimerDisplay();

    const intervalId = setInterval(() => {
        remaining -= 1;
        countdownEl.textContent = t('waiting.countdown', { s: remaining });
        if (remaining <= 0) {
            clearInterval(intervalId);
        }
    }, 1000);

    const resetTimeoutId = setTimeout(() => {
        overlay.classList.remove('show');
        form.reset();
        showStartScreen();
    }, 10000);
}

// Formular absenden
submitBtn.addEventListener('click', async (e) => {
    e.preventDefault();

    // Validierung durchführen
    if (!validateForm()) {
        alert(t('validation.required'));
        return;
    }

    // Formular-Daten sammeln
    const formData = new FormData(form);

    try {
        // Anfrage an den Server
        const response = await fetch('/submit_form.php', {
            method: 'POST',
            body: formData
        });

        // Versuche Antwort zu parsen, unabhängig vom HTTP-Status
        let data = null;
        const contentType = response.headers.get('content-type');
        
        try {
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                console.error('Unexpected response format:', text);
                throw new Error(t('error.format'));
            }
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            throw new Error(t('error.parse'));
        }

        if (data.success) {
            // Wartennummer anzeigen
            showWaitingNumber(data.waiting_number);

            // Erfolgs-Benachrichtigung
            showNotification(t('notification.success'), 'success');

            // Formular zurücksetzen nach erfolgreicher Einreichung
            form.reset();
            showStartScreen();

        } else {
            // Zeige Fehlermeldung an
            let errorMessage = data.message || 'Fehler beim Verarbeiten des Formulars.';
            
            // Wenn spezifische Validierungsfehler vorhanden sind
            if (data.errors && typeof data.errors === 'object') {
                const errorList = Object.entries(data.errors)
                    .map(([field, message]) => `${field}: ${message}`)
                    .join('\n');
                errorMessage += '\n\n' + errorList;
            }
            
            showNotification('✗ ' + errorMessage, 'error');

        }
    } catch (error) {
        console.error('Fehler:', error);
        showNotification(`✗ ${t('error.generic')} ${error.message}`, 'error');
    }
});

// Formular zurücksetzen
cancelBtn.addEventListener('click', () => {
    if (confirm(t('confirm.reset'))) {
        form.reset();
        showNotification(t('notification.reset'), 'info');
        showStartScreen();
    }
});

// Timepicker initialisieren (Flatpickr)
function initTimePicker() {
    if (!timeInput || typeof flatpickr === 'undefined') {
        return;
    }

    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (isTouchDevice) {
        return;
    }

    flatpickr(timeInput, {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true
    });
}

initTimePicker();

// ===== SETZE DEFAULT DATUM UND UHRZEIT =====
function setDefaultDateTime() {
    const now = new Date();
    
    // Aktuelles Datum (YYYY-MM-DD)
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const dateString = `${year}-${month}-${day}`;
    
    // Aktuelle Uhrzeit auf 10 Minuten genau
    const hours = now.getHours();
    const minutes = Math.ceil(now.getMinutes() / 10) * 10;
    const timeString = `${String(hours).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
    
    const terminTagInput = document.getElementById('termin_tag');
    const zeitInput = document.getElementById('uhrzeit');
    
    if (terminTagInput && !terminTagInput.value) {
        terminTagInput.value = dateString;
    }
    
    if (zeitInput && !zeitInput.value) {
        zeitInput.value = timeString;
    }
}

function updateTerminDetails() {
    if (!terminDetails || !terminToggle) {
        return;
    }

    if (terminToggle.checked) {
        terminDetails.classList.remove('hidden');
        setDefaultDateTime();
    } else {
        terminDetails.classList.add('hidden');
        const terminTagInput = document.getElementById('termin_tag');
        const zeitInput = document.getElementById('uhrzeit');
        if (terminTagInput) {
            terminTagInput.value = '';
        }
        if (zeitInput) {
            zeitInput.value = '';
        }
    }
}

// Formular-Validierung
function validateForm() {
    const vorname = document.getElementById('vorname').value.trim();
    const nachname = document.getElementById('nachname').value.trim();
    const geburtsdatum = document.getElementById('geburtsdatum').value.trim();
    
    // Mindestens Vor- und Nachname erforderlich
    return vorname.length > 0 && nachname.length > 0 && geburtsdatum.length > 0;
}

// Benachrichtigungssystem
function showNotification(message, type = 'info') {
    // Erstelle Benachrichtigungs-Element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    // Styling für Benachrichtigungen
    const styles = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        z-index: 2000;
        animation: slideInRight 0.3s;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;

    const typeStyles = {
        success: 'background-color: #28a745; color: white;',
        error: 'background-color: #dc3545; color: white;',
        info: 'background-color: #003865; color: white;'
    };

    notification.setAttribute('style', styles + (typeStyles[type] || ''));

    // Füge Animation hinzu
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    
    if (!document.querySelector('style[data-notification-style]')) {
        style.setAttribute('data-notification-style', 'true');
        document.head.appendChild(style);
    }

    document.body.appendChild(notification);

    // Entferne Benachrichtigung nach 4 Sekunden
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 4000);
}

// Enter-Taste zum Absenden aktivieren
form.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && e.ctrlKey) {
        submitBtn.click();
    }
});

// ===== INAKTIVITÄTS-TRACKING =====
// Aktivität auf Formular-Elementen erfassen
const formInputs = form.querySelectorAll('input, textarea, select');
formInputs.forEach(input => {
    input.addEventListener('input', resetInactivityTimer);
    input.addEventListener('change', resetInactivityTimer);
    input.addEventListener('keydown', resetInactivityTimer);
    input.addEventListener('click', resetInactivityTimer);
});

// Globale Aktivität erfassen
document.addEventListener('keydown', resetInactivityTimer);
document.addEventListener('mousemove', resetInactivityTimer);
document.addEventListener('click', resetInactivityTimer);
document.addEventListener('touchstart', resetInactivityTimer);

// Timer-Display beim Laden initialisieren und Default-Werte setzen
window.addEventListener('load', () => {
    showStartScreen();
    const savedLang = localStorage.getItem('kls-lang');
    setLanguage(savedLang || 'de');
});

// Informationen anzeigen beim Laden
console.log('Einchecken.-Kundencenter - AOK Niedersachsen Anwendung geladen');
console.log('⏱️ Inaktivitäts-Timeout: 30 Sekunden');

if (startBtn) {
    startBtn.addEventListener('click', () => {
        showFormScreen();
    });
}

languageButtons.forEach((button) => {
    button.addEventListener('click', () => {
        setLanguage(button.dataset.lang || 'de');
    });
});

if (terminToggle) {
    terminToggle.addEventListener('change', updateTerminDetails);
}
