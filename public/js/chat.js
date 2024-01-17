function add_message(message, element, user=true) {
    // Crée des éléments DOM pour le message et le rôle de l'utilisateur
    const divMessage = document.createElement('div');
    const pMessage = document.createElement('p');
    const divRole = document.createElement('div');

    // Définit les classes CSS pour le style
    divMessage.setAttribute("class", "divMessage");
    divRole.setAttribute("class", "role");
    pMessage.setAttribute("class", "message");

    // Ajoute le texte du message
    pMessage.textContent = message;

    // Affiche "User" ou "Recruteur" selon que 'user' est vrai ou faux
    if(user) {
        divRole.textContent = "User";
        divMessage.setAttribute("class", "divMessage user");
    } else {
        divRole.textContent = "Recruteur";
        divMessage.setAttribute("class", "divMessage recruteur");
    }

    // Ajoute les éléments de rôle et de message à divMessage
    divMessage.appendChild(divRole);
    divMessage.appendChild(pMessage);

    // Ajoute divMessage à l'élément passé en paramètre
    element.appendChild(divMessage);
}

function return_audio(message) {
    let audio;
    // Envoie une requête POST avec le message en JSON
    fetch('/return-audio', {
        method: 'POST',
        body: JSON.stringify({ message: message }),
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.blob())
    .then(blob => {
        // Crée un URL pour le blob audio et joue l'audio
        const url = URL.createObjectURL(blob);
        audio = new Audio(url);
        audio.play();
    })
    .catch(error => console.error('Error:', error));
}

function scrollToBottom() {
    // Sélectionne tous les éléments avec la classe 'role'
    const role = document.querySelectorAll('.role');
    // Fait défiler jusqu'au dernier élément 'role'
    role[role.length-1].scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.addEventListener('DOMContentLoaded', function() {
    // Obtient le premier message et joue l'audio correspondant
    const first_response = document.getElementById('first_response').textContent;
    const objFr = {"content":first_response}
    return_audio(objFr);


    let global_chat_message = document.getElementById('global_chat_message');
    let messageReturn = document.getElementById('messageReturn').value;
    let jsonParse = JSON.parse(messageReturn);
    let submitButton = document.getElementById('submit_button');

    let chatForm = document.getElementById('chat-form');
    chatForm.addEventListener('submit', function(e) {
        // Empêche le comportement par défaut du formulaire
        e.preventDefault();

        // Désactive le bouton de soumission
        submitButton.disabled = true;

        // Récupère le message de l'utilisateur et l'ajoute au chat
        let userMessage = document.getElementById('user-message').value;
        add_message(userMessage, global_chat_message);

        // Fait défiler jusqu'au dernier message
        scrollToBottom();

        // Construit le message et l'ajoute à l'historique du chat
        let actualArrayMessage = {
            "role": "user",
            "content": userMessage
        };
        jsonParse.push(actualArrayMessage);

        // Envoie le message au serveur et gère la réponse
        let actionUrl = chatForm.getAttribute('data-action-url');
        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: jsonParse })
        })
        .then(response => response.text())
        .then(response => {
            // Traite la réponse et ajoute la réponse du serveur au chat
            let responseParse = JSON.parse(response);
            jsonParse.push(responseParse.choices[0].message);
            add_message(responseParse.choices[0].message.content, global_chat_message, false);

            // Joue le message audio de la réponse
            return_audio(responseParse.choices[0].message);

            // Fait défiler jusqu'au dernier message
            scrollToBottom();

            // Réactive le bouton de soumission
            submitButton.disabled = false;
        })
        .catch(error => {
            // Gère les erreurs
            alert('Erreur lors de l\'envoi du message: ' + error);
        })
        .finally(() => {
            // Réinitialise le formulaire
            submitButton.disabled = false;
            document.getElementById('user-message').value = '';
        });
    });

    const microphone_btn = document.getElementById('microphone_btn');

    let mediaRecorder;
    let audioChunks = [];

    // Obtient l'accès au microphone et configure l'enregistreur
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
            // Initialise l'enregistreur avec le flux audio
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = event => {
                // Collecte les données audio
                audioChunks.push(event.data);
            };
            mediaRecorder.onstop = () => {
                // Crée un blob audio et l'envoie au serveur
                const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                const formData = new FormData();
                formData.append('audioFile', audioBlob, 'recording.wav');
            
                fetch('/upload-audio', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Traite la réponse et déclenche l'envoi du message
                    let userMessage = document.getElementById('user-message');
                    userMessage.value = data;
                    const btn_submit = document.getElementById('submit_button');
                    btn_submit.click();
                })
                .catch(error => {
                    // Gère les erreurs
                    console.error('Erreur lors de l\'envoi du fichier audio', error);
                });
            };
        });

    // Fonctions pour démarrer et arrêter l'enregistrement
    function startRecording() {
        audioChunks = [];
        mediaRecorder.start();
    }

    function stopRecording() {
        mediaRecorder.stop();
    }

    let record = false;

    // Gère l'enregistrement au clic sur le bouton microphone
    microphone_btn.addEventListener('click', (e)=> {
        e.preventDefault();

        let micro = document.getElementById('microphone_btn');

        if(record) {
            // Arrête l'enregistrement
            stopRecording();
            record = false;
            micro.classList.remove('micro_on');
            micro.classList.add('micro_off');
        } else {
            // Démarre l'enregistrement
            startRecording();
            record = true;
            micro.classList.add('micro_on');
            micro.classList.remove('micro_off');
        }
    });

});