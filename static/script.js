// DOM-Elemente
const form = document.getElementById('kls-form');
const submitBtn = document.getElementById('submit-btn');
const cancelBtn = document.getElementById('cancel-btn');
const modal = document.getElementById('email-modal');
const closeModalBtn = document.getElementById('close-modal-btn');
const closeBtn = document.getElementById('close-modal');
const printEmailBtn = document.getElementById('print-email-btn');
const emailPreview = document.getElementById('email-preview');
const timeInput = document.getElementById('uhrzeit');
const DEBUG_EMAIL_PREVIEW = true;

// Inaktivitäts-Timer Variablen
let inactivityTimer = null;
let countdownTimer = null;
const INACTIVITY_TIMEOUT = 30000; // 30 Sekunden in Millisekunden
let remainingTime = 30; // Verbleibende Zeit in Sekunden
let timerStarted = false; // Flag: Timer bereits gestartet?

// ===== INAKTIVITÄTS-TIMER FUNKTIONEN =====
function updateTimerDisplay() {
    const timerElement = document.getElementById('timer-value');
    if (timerElement) {
        // Wenn Timer noch nicht gestartet, zeige "Bereit"
        if (!timerStarted) {
            timerElement.textContent = 'Bereit';
            timerElement.classList.remove('warning');
            return;
        }
        
        timerElement.textContent = remainingTime + 's';
        
        // Warnung aktivieren bei 10 Sekunden oder weniger
        if (remainingTime <= 10) {
            timerElement.classList.add('warning');
        } else {
            timerElement.classList.remove('warning');
        }
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
    // Timer nur starten, wenn noch nicht gestartet
    if (!timerStarted) {
        timerStarted = true;
        console.log('⏱️ Timer gestartet - erste Eingabe erkannt');
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
    
    // Modal schließen, falls offen
    closeModal();
    
    // Seite neu laden
    location.reload();
}

// Modal öffnen
function openModal(emailHtml) {
    emailPreview.innerHTML = emailHtml;
    modal.classList.add('show');
    // Timer zurücksetzen, wenn Modal geöffnet wird
    resetInactivityTimer();
}

// Modal schließen
function closeModal() {
    modal.classList.remove('show');
}

// E-Mail Drucken
function printEmail() {
    const printWindow = window.open('', '', 'height=500,width=900');
    printWindow.document.write(emailPreview.innerHTML);
    printWindow.document.close();
    printWindow.print();
}

// Wartennummer anzeigen (10 Sekunden)
function showWaitingNumber(waitingNumber, emailHtml) {
    if (!waitingNumber) {
        return;
    }

    let overlay = document.getElementById('waiting-number-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'waiting-number-overlay';
        overlay.innerHTML = `
            <div class="waiting-number-card">
                <div class="waiting-number-title">Ihre Wartennummer</div>
                <div class="waiting-number-value" id="waiting-number-value"></div>
                <div class="waiting-number-countdown" id="waiting-number-countdown"></div>
                <button class="waiting-number-debug" id="waiting-number-debug" type="button">Debug: Vorschau oeffnen</button>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    const valueEl = overlay.querySelector('#waiting-number-value');
    const countdownEl = overlay.querySelector('#waiting-number-countdown');
    let remaining = 10;

    valueEl.textContent = waitingNumber;
    countdownEl.textContent = `Anzeige schliesst in ${remaining}s`;
    overlay.classList.add('show');

    const debugBtn = overlay.querySelector('#waiting-number-debug');
    if (debugBtn && DEBUG_EMAIL_PREVIEW && emailHtml) {
        debugBtn.addEventListener('click', () => {
            clearTimeout(resetTimeoutId);
            overlay.classList.remove('show');
            openModal(emailHtml);
        });
    } else if (debugBtn) {
        debugBtn.disabled = true;
    }

    const intervalId = setInterval(() => {
        remaining -= 1;
        countdownEl.textContent = `Anzeige schliesst in ${remaining}s`;
        if (remaining <= 0) {
            clearInterval(intervalId);
        }
    }, 1000);

    const resetTimeoutId = setTimeout(() => {
        overlay.classList.remove('show');
        form.reset();
        closeModal();
        location.reload();
    }, 10000);
}

// Formular absenden
submitBtn.addEventListener('click', async (e) => {
    e.preventDefault();

    // Validierung durchführen
    if (!validateForm()) {
        alert('Bitte füllen Sie alle erforderlichen Felder aus.');
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

        if (!response.ok) {
            throw new Error('Netzwerkfehler: ' + response.statusText);
        }

        const data = await response.json();

        if (data.success) {
            // Wartennummer anzeigen
            if (data.email_html) {
                emailPreview.innerHTML = data.email_html;
            }
            showWaitingNumber(data.waiting_number, data.email_html);

            // Erfolgs-Benachrichtigung
            showNotification('✓ Formular erfolgreich verarbeitet!', 'success');

            if (data.email_sent === false) {
                showNotification('E-Mail wurde nicht gesendet (Konfiguration fehlt).', 'info');
            }
        } else {
            showNotification('✗ Fehler beim Verarbeiten des Formulars.', 'error');
        }
    } catch (error) {
        console.error('Fehler:', error);
        showNotification('✗ Ein Fehler ist aufgetreten: ' + error.message, 'error');
    }
});

// Formular zurücksetzen
cancelBtn.addEventListener('click', () => {
    if (confirm('Möchten Sie das Formular wirklich zurücksetzen?')) {
        form.reset();
        showNotification('Formular wurde zurückgesetzt.', 'info');
    }
});

// Modal schließen
closeModalBtn.addEventListener('click', closeModal);
closeBtn.addEventListener('click', closeModal);

// E-Mail drucken
printEmailBtn.addEventListener('click', printEmail);

// Modal schließen bei Klick außerhalb
window.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeModal();
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


// Formular-Validierung
function validateForm() {
    const vorname = document.getElementById('vorname').value.trim();
    const nachname = document.getElementById('nachname').value.trim();
    
    // Mindestens Vor- und Nachname erforderlich
    return vorname.length > 0 && nachname.length > 0;
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

// Timer-Display beim Laden initialisieren
window.addEventListener('load', () => {
    updateTimerDisplay();
});

// Informationen anzeigen beim Laden
console.log('Einchecken.-Kundencenter - AOK Niedersachsen Anwendung geladen');
console.log('⏱️ Inaktivitäts-Timeout: 30 Sekunden');
