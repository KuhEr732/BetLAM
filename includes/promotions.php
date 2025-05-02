<?php
// Überprüft, ob der Benutzer eingeloggt ist, indem die 'userId' in der Session überprüft wird.
// Wenn der Benutzer nicht eingeloggt ist, wird er zur Login-Seite weitergeleitet.
if (!isset($_SESSION['userId'])) {
    header("Location: index.php?page=login");  
    exit;  
}
?>

<style>
    /* Allgemeine Styling-Regeln für den Body */
    body { 
        font-family: sans-serif; 
        background: #ffffff; 
        color: #333; 
        text-align: center; 
        padding: 50px; 
    }

    /* Styling für den Button */
    button { 
        font-size: 20px; 
        padding: 12px 24px; 
        background: #129B7F; 
        color: white; 
        border: none; 
        border-radius: 10px; 
        cursor: pointer; 
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    /* Hover-Effekt für den Button */
    button:hover { 
        background: #0f7a66; 
    }

    /* Styling für die Nachricht (z.B. Erfolgs- oder Fehlermeldung) */
    .message { 
        margin-top: 30px; 
        font-size: 18px; 
        font-weight: bold; 
    }

    /* Fehler-Nachricht (rote Farbe) */
    .error { 
        color: #D32F2F; 
    }

    /* Erfolgs-Nachricht (grüne Farbe) */
    .success { 
        color: #388E3C; 
    }

    /* Container für animierte Münzen */
    #coin-container {
        position: relative;
        height: 0;
        pointer-events: none;
    }

    /* Keyframe-Animation für das Fallen der Münzen */
    @keyframes drop {
        to {
            transform: translateY(300px);  
            opacity: 0;  
        }
    }
</style>

<h1>🎉 Bonuszeit!</h1>
<p>Klicke den Button, um deine Belohnung zu erhalten.</p>
<!-- Button, der die Bonus-Funktion auslöst -->
<button onclick="claimBonus()">Bonus einlösen</button>

<!-- Container für animierte Münzen -->
<div id="coin-container" style="position: relative; height: 0;"></div>

<!-- Bereich, um Nachrichten anzuzeigen (z.B. Erfolgs- oder Fehlermeldung) -->
<div class="message" id="msg"></div>

<script>
// Funktion zum Einlösen des Bonus
function claimBonus() {
    // Ruft das PHP-Skript auf, das die Logik für die Bonuseinlösung enthält
    fetch('includes/bonus-handler.php')
        .then(response => response.json())  // Wandelt die Antwort in JSON um
        .then(data => {
            const msg = document.getElementById('msg');
            msg.textContent = data.message; // Setzt die Nachricht, die vom Server gesendet wurde
            msg.className = 'message ' + (data.status === 'success' ? 'success' : 'error'); // Ändert die Farbe je nach Status

            // Wenn der Bonus erfolgreich eingelöst wurde, werden Münzen angezeigt
            if (data.status === 'success') {
                showCoins();
            }
        })
        .catch(() => {
            const msg = document.getElementById('msg');
            msg.textContent = "Es ist ein Fehler aufgetreten."; // Fehlernachricht bei einem Fehler
            msg.className = 'message error'; // Setzt die Nachricht auf Fehlerstil
        });
}

// Funktion zur Anzeige von fallenden Münzen
function showCoins() {
    const container = document.getElementById('coin-container');
    
    // Erzeugt 20 Münzen, die auf dem Bildschirm erscheinen
    for (let i = 0; i < 20; i++) {
        const coin = document.createElement('div');
        coin.textContent = '🪙';  
        coin.style.position = 'absolute';
        coin.style.left = Math.random() * 100 + '%';  
        coin.style.top = '-30px';  
        coin.style.fontSize = '24px';  
        coin.style.animation = 'drop 1s ease-out forwards';  
        container.appendChild(coin);  

        // Entfernt die Münze nach der Animation (nach 1 Sekunde)
        setTimeout(() => container.removeChild(coin), 1000);
    }
}
</script>

<style>
/* Keyframe-Animation für das Fallen der Münzen */
@keyframes drop {
    to {
        transform: translateY(300px);  
        opacity: 0;  
    }
}
</style>
